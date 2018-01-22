<?php

namespace Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Service;

use Doctrine\Common\Persistence\ObjectManager;
use Janisbiz\SymfonyWorkflowExtended\Repository\StateMachineTimeoutRepositoryInterface;
use Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Entity\StateMachineTimeoutInterface;
use Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Entity\WorkflowInterface;
use Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Enum\OptionsEnum;
use Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Enum\TimeoutEnum;
use Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Exception\InvalidTimeoutTransitionException;
use Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Exception\TimeoutObjectNotFoundException;
use Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Traits\CommandTrait;
use Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Traits\TimeoutTrait;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\StateMachine;
use Symfony\Component\Workflow\Transition;

/**
 * Class StateMachineTimeoutService
 * @package Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Service
 */
class StateMachineTimeoutService
{
    use TimeoutTrait;
    use CommandTrait;

    const TIMEOUT_TYPE_BASED_ON_STATUS = 'based_on_status';
    const TIMEOUT_TYPE_INDEPENDENT = 'independent';

    /** @var ContainerInterface */
    private $container;

    /** @var ObjectManager */
    private $entityManager;

    /** @var ContainerBuilder */
    private $containerBuilder;

    /**
     * StateMachineTimeoutService constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->entityManager = $this->container->get('doctrine')->getManager();
    }

    /**
     * @param StateMachine|null $stateMachine
     * @param string|null $status
     *
     * @return StateMachineTimeoutService
     */
    public function resolveTimeouts(StateMachine $stateMachine = null, string $status = null): self
    {
        /** @var StateMachineTimeoutRepositoryInterface $stateMachineTimeoutRepository */
        $stateMachineTimeoutRepository = $this->entityManager->getRepository(StateMachineTimeout::class);
        $stateMachineTimeouts = $stateMachineTimeoutRepository->findTimeoutsToExecute(
            $stateMachine ? $stateMachine->getName() : null,
            $status
        );

        /** @var StateMachine[] $stateMachines */
        $stateMachines = [];

        foreach ($stateMachineTimeouts as $stateMachineTimeout) {
            if (!array_key_exists($stateMachineTimeout->getSchemaName(), $stateMachines)) {
                $stateMachines[$stateMachineTimeout->getSchemaName()] =
                    $this->container->get(sprintf('state_machine.%s', $stateMachineTimeout->getSchemaName()));
            }

            $subject = $this->getTimeoutObject(
                $stateMachineTimeout->getSchemaName(),
                $stateMachineTimeout->getSchemaIdentifier()
            );

            $timeoutConfig = $this->getTimeoutConfig(
                $stateMachines[$stateMachineTimeout->getSchemaName()],
                $stateMachineTimeout
            );

            if ($commands = $timeoutConfig[TimeoutEnum::TIMEOUT_OPTION_COMMANDS] ?? null) {
                $result = $this
                    ->setCommands($commands)
                    ->validateCommands()
                    ->executeCommands(
                        new Event($subject, new Marking(), new Transition('_', null, null)),
                        $this->container,
                        'logCommands',
                        $stateMachineTimeout
                    );

                $onFailure = $timeoutConfig[TimeoutEnum::TIMEOUT_OPTION_ON_FAILURE] ?? null;

                if ($result === false
                    && $onFailure
                    && $stateMachines[$stateMachineTimeout->getSchemaName()]->can($subject, $onFailure)
                ) {
                    $stateMachines[$stateMachineTimeout->getSchemaName()]->apply($subject, $onFailure);

                    $this->markTimeoutAsExecuted($stateMachineTimeout);

                    continue;
                }
            }

            $onSuccess = $timeoutConfig[TimeoutEnum::TIMEOUT_OPTION_ON_SUCCESS] ?? null;
            if ($onSuccess && $stateMachines[$stateMachineTimeout->getSchemaName()]->can($subject, $onSuccess)) {
                $stateMachines[$stateMachineTimeout->getSchemaName()]->apply($subject, $onSuccess);
            }

            $this->markTimeoutAsExecuted($stateMachineTimeout);
        }

        return $this;
    }

    /**
     * @param string $schemaName
     * @param int $schemaIdentifier
     *
     * @return WorkflowInterface
     * @throws TimeoutObjectNotFoundException
     */
    private function getTimeoutObject(string $schemaName, int $schemaIdentifier): WorkflowInterface
    {
        foreach ($this->entityManager->getConfiguration()->getMetadataDriverImpl()->getAllClassNames() as $className) {
            if ($this->entityManager->getClassMetadata($className)->getTableName() === $schemaName) {
                /** @var WorkflowInterface $object */
                if (!$object = $this->entityManager->getRepository($className)->find($schemaIdentifier)) {
                    break;
                }

                return $object;
            }
        }

        throw new TimeoutObjectNotFoundException(sprintf(
            'Could not find timeout object by identifier "%d" on schema "%s"',
            $schemaIdentifier,
            $schemaName
        ));
    }

    /**
     * @param StateMachine $stateMachine
     * @param StateMachineTimeoutInterface $stateMachineTimeout
     *
     * @return array|null
     */
    private function getTimeoutConfig(StateMachine $stateMachine, StateMachineTimeoutInterface $stateMachineTimeout): ?array
    {
        if (!$this->containerBuilder) {
            $this->containerBuilder = new ContainerBuilder();

            $loader = new XmlFileLoader($this->containerBuilder, new FileLocator());
            $loader->load($this->container->getParameter('debug.container.dump'));
        }

        foreach ($this->containerBuilder->getServiceIds() as $serviceId) {
            if (preg_match(sprintf('/^state_machine[.]%s[.]./', $stateMachine->getName()), $serviceId)) {
                try {
                    $definition = $this->containerBuilder->getDefinition($serviceId);
                } catch (\Exception $e) {
                    /** Could not get definition, continuing loop */
                    continue;
                }

                $arguments = $definition->getArguments();
                $timeouts = $arguments['options'][OptionsEnum::OPTION_TIMEOUTS] ?? [];

                if (empty($timeouts)) {
                    continue;
                }

                $tags = $definition->getTag('kernel.event_listener');

                $validStatuses = [];
                foreach ($tags as $tag) {
                    /** Extracting valid statuses for time-out from service tags */

                    /** Matches pattern workflow.[stat_machine_name].[event].[status_name] */
                    preg_match('/^workflow[.]\w+[.]\w+[.](?<status>\w+)$/', $tag['event'] ?? null, $matches);

                    if ($matches['status'] ?? null) {
                        $validStatuses[] = $matches['status'];
                    }
                }

                if (empty($validStatuses) || !in_array($stateMachineTimeout->getSchemaStatus(), $validStatuses)) {
                    continue;
                }

                if (!empty($timeouts)) {
                    foreach ($timeouts as $timeout) {
                        if ($timeout[TimeoutEnum::TIMEOUT_OPTION_NAME] === $stateMachineTimeout->getName()) {
                            return $timeout;
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param StateMachineTimeoutInterface $stateMachineTimeout
     *
     * @return StateMachineTimeoutService
     */
    private function markTimeoutAsExecuted(StateMachineTimeoutInterface $stateMachineTimeout): self
    {
        $stateMachineTimeout
            ->setActive(false)
            ->setExecutedAt(new \DateTime())
        ;

        $this->entityManager->initializeObject($stateMachineTimeout);
        $this->entityManager->persist($stateMachineTimeout);
        $this->entityManager->flush();

        return $this;
    }

    /**
     * @param StateMachine $stateMachine
     * @param WorkflowInterface $subject
     * @param array $timeoutOptions
     *
     * @return StateMachineTimeoutInterface
     * @throws \Exception
     */
    public function createTimeout(
        StateMachine $stateMachine,
        WorkflowInterface $subject,
        array $timeoutOptions
    ): StateMachineTimeoutInterface {
        /** Doing timeout validation, just in case */
        $timeouts = [$timeoutOptions];
        $this->validateTimeouts($timeouts);
        $timeoutOptions = $timeouts[0];

        $transitionOnSuccess = $timeoutOptions[TimeoutEnum::TIMEOUT_OPTION_ON_SUCCESS] ?? null;
        $transitionOnFailure = $timeoutOptions[TimeoutEnum::TIMEOUT_OPTION_ON_FAILURE] ?? null;

        if ($transitionOnSuccess !== null && !$stateMachine->can($subject, $transitionOnSuccess)) {
            throw new InvalidTimeoutTransitionException(sprintf(
                'Can not execute transition on success("%s") on "%s" when status is "%s"',
                $transitionOnSuccess,
                get_class($subject),
                $subject->getStatus()
            ));
        }

        if ($transitionOnFailure !== null && !$stateMachine->can($subject, $transitionOnFailure)) {
            throw new InvalidTimeoutTransitionException(sprintf(
                'Can not execute transition on failure("%s") on "%s" when status is "%s"',
                $transitionOnFailure,
                get_class($subject),
                $subject->getStatus()
            ));
        }

        /**
         * TODO: Make admin logging configurable
         */
//        if ($this->container->has('security.token_storage')) {
//            $token = $this->container->get('security.token_storage')->getToken();
//
//            if ($token !== null) {
//                $admin = $token->getUser();
//            }
//        }
//
//        if (!isset($admin) || !is_object($admin)) {
//            $admin = $this->entityManager->getRepository(Admin::class)->find(Admin::SYSTEM_USER_ID);
//        }

        $stateMachineTimeout = (new StateMachineTimeout())
            ->setSchemaName($stateMachine->getName())
            ->setSchemaIdentifier($subject->getWorkflowIdentifier())
            ->setSchemaStatus($subject->getStatus())
            ->setName($timeoutOptions[TimeoutEnum::TIMEOUT_OPTION_NAME])
            ->setTimeToRun((new \DateTime())->add($timeoutOptions[TimeoutEnum::TIMEOUT_OPTION_INTERVAL]))
            ->setType($timeoutOptions[TimeoutEnum::TIMEOUT_OPTION_TYPE]
                ?? StateMachineTimeoutService::TIMEOUT_TYPE_BASED_ON_STATUS)
//            ->setCreatedBy($admin)
//            ->setUpdatedBy($admin)
        ;

        $this->entityManager->persist($stateMachineTimeout);
        $this->entityManager->flush();

        return $stateMachineTimeout;
    }

    /**
     * @param WorkflowInterface $subject
     *
     * @return StateMachineTimeoutService
     */
    public function deactivateStateBasedTimeouts(WorkflowInterface $subject): self
    {
        return $this->deactivateTimeouts($subject);
    }

    /**
     * @param WorkflowInterface $subject
     * @param bool $stateBased
     *
     * @return StateMachineTimeoutService
     */
    private function deactivateTimeouts(WorkflowInterface $subject, bool $stateBased = true): self
    {
        /** @var StateMachineTimeoutRepositoryInterface $entityRepository */
        /**
         * TODO: Make history entity repository configurable
         */
        $entityRepository = $this->entityManager->getRepository(StateMachineTimeout::class);

        if ($stateBased) {
            /** @var StateMachineTimeoutInterface[] $activeTimeouts */
            $activeTimeouts = $entityRepository->findActiveStateBasedTimeouts(
                $this->entityManager->getClassMetadata(get_class($subject))->getTableName(),
                $subject
            );
        } else {
            /** @var StateMachineTimeoutInterface[] $activeTimeouts */
            $activeTimeouts = $entityRepository->findActiveIndependentTimeouts(
                $this->entityManager->getClassMetadata(get_class($subject))->getTableName(),
                $subject
            );
        }

        if (!empty($activeTimeouts)) {
            foreach ($activeTimeouts as $activeTimeout) {
                $activeTimeout->setActive(false);
            }

            $this->entityManager->flush();
            $this->entityManager->clear();
        }

        return $this;
    }

    /**
     * @param WorkflowInterface $subject
     *
     * @return StateMachineTimeoutService
     */
    public function deactivateIndependentTimeouts(WorkflowInterface $subject): self
    {
        return $this->deactivateTimeouts($subject, false);
    }

    /**
     * This function is used as callback for Stat-Machine command execution(logging)
     *
     * @param array $executedCommands
     * @param StateMachineTimeoutInterface $stateMachineTimeout
     *
     * @return StateMachineTimeoutService
     */
    private function logCommands(array $executedCommands, StateMachineTimeoutInterface $stateMachineTimeout): self
    {
        if (!empty($executedCommands)) {
            $stateMachineTimeout->setCommands($executedCommands);
        }

        return $this;
    }
}
