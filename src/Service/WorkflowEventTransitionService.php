<?php

namespace Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Service;

use Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Entity\WorkflowInterface;
use Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Enum\OptionsEnum;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class WorkflowEventTransitionService
 * @package Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Service
 */
class WorkflowEventTransitionService extends AbstractWorkflowEventService
{
    /** @var string */
    private $onFailure;

    /**
     * WorkflowEventEnterService constructor.
     *
     * @param ContainerInterface $container
     * @param string $stateMachine
     * @param string[] $options
     * @param string|null $onFailure
     */
    public function __construct(
        ContainerInterface $container,
        string $stateMachine,
        array $options = [],
        string $onFailure = null
    ) {
        parent::__construct($container, $stateMachine, $options);

        $this->onFailure = $this->getOptions()[OptionsEnum::OPTION_ON_FAILURE] ?? null;
    }

    /**
     * @param string $name
     * @param array $arguments
     *
     * @return bool
     */
    public function __call(string $name, array $arguments): bool
    {
        if (!parent::__call($name, $arguments)) {
            return $this->onFailure();
        }

        return true;
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

            /** Mocking final marking due to onSuccess/onFailure transitions */
            $this->getEvent()->getMarking()->mark($this->getWorkflowFromEvent($this->getEvent())->getMarking());

            return true;
        }

        return false;
    }
}
