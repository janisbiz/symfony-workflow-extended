<?php

namespace Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Service;

use Doctrine\Common\Persistence\ObjectManager;
use Janisbiz\SymfonyWorkflowExtended\Repository\StateMachineHistoryRepositoryInterface;
use Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Entity\StateMachineHistoryInterface;
use Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Entity\WorkflowInterface;
use Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Enum\OptionsEnum;
use Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Traits\CommandTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Debug\TraceableEventDispatcher;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\StateMachine;

/**
 * Class AbstractWorkflowEventService
 * @package Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Workflow\Service
 */
abstract class AbstractWorkflowEventService
{
    use CommandTrait;

    /** @var ContainerInterface */
    private $container;

    /** @var StateMachine */
    private $stateMachine;

    /** @var array */
    private $options;

    /** @var Event */
    private $event;

    /** @var string */
    private $eventName;

    /** @var TraceableEventDispatcher */
    private $traceableEventDispatcher;

    /**
     * AbstractWorkflowEventService constructor.
     *
     * @param ContainerInterface $container
     * @param string $stateMachineName
     * @param string[] $options
     */
    public function __construct(ContainerInterface $container, string $stateMachineName, array $options = [])
    {
        $this->options = $options;

        $this
            ->setCommands($this->options[OptionsEnum::OPTION_COMMANDS] ?? [])
            ->validateCommands();

        $this->container = $container;
        $this->stateMachine = strlen($stateMachineName) > 0
            ? $this->container->get(sprintf('state_machine.%s', $stateMachineName))
            : null;
    }

    /**
     * @param string $name
     * @param array $arguments
     *
     * @return bool
     */
    public function __call(string $name, array $arguments): bool
    {
        if (!isset($arguments[0]) || !($arguments[0] instanceof Event)) {
            throw new \InvalidArgumentException(
                'Parameter 1 of function "%s" should be instance of "%s"',
                $name,
                Event::class
            );
        }

        if (!isset($arguments[1]) || !is_string($arguments[1])) {
            throw new \InvalidArgumentException(
                'Parameter 2 of function "%s" must be with type of string',
                $name
            );
        }

        if (!isset($arguments[2]) || !($arguments[2] instanceof TraceableEventDispatcher)) {
            throw new \InvalidArgumentException(
                'Parameter 3 of function "%s" should be instance of "%s"',
                $name,
                TraceableEventDispatcher::class
            );
        }

        $this->event = $arguments[0];
        $this->eventName = $arguments[1];
        $this->traceableEventDispatcher = $arguments[2];

        return $this->executeCommands($this->event, $this->container, 'logCommands');
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer(): ?ContainerInterface
    {
        return $this->container;
    }

    /**
     * @return StateMachine
     */
    public function getStateMachine(): ?StateMachine
    {
        return $this->stateMachine;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @return Event
     */
    public function getEvent(): ?Event
    {
        return $this->event;
    }

    /**
     * @return string
     */
    public function getEventName(): ?string
    {
        return $this->eventName;
    }

    /**
     * @return TraceableEventDispatcher
     */
    public function getTraceableEventDispatcher(): ?TraceableEventDispatcher
    {
        return $this->traceableEventDispatcher;
    }

    /**
     * @param Event $event
     *
     * @return WorkflowInterface
     */
    protected function getWorkflowFromEvent(Event $event): WorkflowInterface
    {
        return $event->getSubject();
    }

    /**
     * @param array $executedCommands
     *
     * @return AbstractWorkflowEventService
     */
    private function logCommands(array $executedCommands): self
    {
        if (!$this->stateMachine) {
            /** Can't do logging, if there is no state-machine(usually due to global events) */
            return $this;
        }

        /** @var ObjectManager $entityManager */
        $entityManager = $this->container->get('doctrine')->getManager();

        /** @var StateMachineHistoryRepositoryInterface $stateMachineHistoryRepository */
        /**
         * TODO: Make history entity repository configurable
         */
        $stateMachineHistoryRepository = $entityManager->getRepository(StateMachineHistory::class);

        /** @var StateMachineHistoryInterface $lastStateMachineHistoryEntry */
        $lastStateMachineHistoryEntry = $stateMachineHistoryRepository->findLastLogEntry(
            $this->stateMachine->getName(),
            $this->event->getSubject()->getWorkflowIdentifier(),
            $this->event->getTransition()->getName()
        );

        $currentCommands = $lastStateMachineHistoryEntry->getCommands() ?? [];
        foreach ($executedCommands as $executedCommand) {
            $currentCommands[] = $executedCommand;
        }
        $lastStateMachineHistoryEntry->setCommands($currentCommands);

        $entityManager->persist($lastStateMachineHistoryEntry);
        $entityManager->flush();

        /** Detaching last entry from repository cache, so every time executed, we get the last entry */
        $entityManager->detach($lastStateMachineHistoryEntry);

        return $this;
    }
}
