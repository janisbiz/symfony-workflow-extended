<?php

namespace Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Service;

use Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Entity\WorkflowInterface;
use Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Enum\OptionsEnum;
use Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Traits\TimeoutTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class WorkflowEventEnteredService extends AbstractWorkflowEventService
{
    use TimeoutTrait;

    /** @var string */
    private $onSuccess;

    /** @var string */
    private $onFailure;

    /** @var array */
    private $timeouts;

    /** @var StateMachineTimeoutService */
    private $stateMachineTimeoutService;

    /**
     * WorkflowEventEnterService constructor.
     *
     * @param ContainerInterface $container
     * @param string $stateMachine
     * @param string[] $options
     */
    public function __construct(
        ContainerInterface $container,
        string $stateMachine,
        array $options = []
    ) {
        parent::__construct($container, $stateMachine, $options);

        $this->onSuccess = $this->getOptions()[OptionsEnum::OPTION_ON_SUCCESS] ?? null;
        $this->onFailure = $this->getOptions()[OptionsEnum::OPTION_ON_FAILURE] ?? null;
        $this->timeouts = $this->getOptions()[OptionsEnum::OPTION_TIMEOUTS] ?? null;

        $this->stateMachineTimeoutService = $this->getContainer()->get(StateMachineTimeoutService::class);

        $this->validateTimeouts($this->timeouts);
    }

    /**
     * @param string $name
     * @param array $arguments
     *
     * @return bool
     */
    public function __call(string $name, array $arguments): bool
    {
        $result = parent::__call($name, $arguments);

        $this
            ->registerTimeouts(
                $this->stateMachineTimeoutService,
                $this->getWorkflowFromEvent($this->getEvent()),
                $this->timeouts
            );

        return $result === true ? $this->onSuccess() : $this->onFailure();
    }

    /**
     * @return bool
     */
    protected function onSuccess(): bool
    {
        if (is_null($this->onSuccess)) {
            return true;
        }

        return $this->executeTransaction(
            $this->getWorkflowFromEvent($this->getEvent()),
            $this->onSuccess
        );
    }

    /**
     * @param WorkflowInterface $subject
     * @param string $transaction
     *
     * @return bool
     */
    protected function executeTransaction(WorkflowInterface $subject, string $transaction): bool
    {
        if ($this->getStateMachine()->can($subject, $transaction)) {
            $this->getStateMachine()->apply($subject, $transaction);

            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    protected function onFailure(): bool
    {
        if (is_null($this->onFailure)) {
            return true;
        }

        return $this->executeTransaction(
            $this->getWorkflowFromEvent($this->getEvent()),
            $this->onFailure
        );
    }
}
