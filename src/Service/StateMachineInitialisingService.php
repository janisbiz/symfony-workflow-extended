<?php

namespace Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Service;

use Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Entity\WorkflowInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\Debug\TraceableEventDispatcher;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\StateMachine;
use Symfony\Component\Workflow\Transition;

/**
 * Class StateMachineInitialisingService
 * @package Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Service
 */
class StateMachineInitialisingService
{
    const TRANSITION_INITIALISING_NAME = '_';

    /** @var ContainerInterface */
    private $container;

    /** @var TraceableEventDispatcher */
    private $eventDispatcher;

    /**
     * StateMachineInitialisingService constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->eventDispatcher = $this->container->get('event_dispatcher');
    }

    /**
     * @param WorkflowInterface $object
     * @param StateMachine $stateMachine
     *
     * @return WorkflowInterface
     */
    public function initializeObject(WorkflowInterface $object, StateMachine $stateMachine): WorkflowInterface
    {
        if (!$object->getStatus()) {
            throw new \InvalidArgumentException('There is no "status" for $object!');
        }

        if (!in_array($object->getStatus(), $stateMachine->getDefinition()->getPlaces())) {
            throw new \InvalidArgumentException(
                sprintf('Status "%s" of the $object does not exist!', $object->getStatus())
            );
        }

        $this->eventDispatcher->dispatch(
            sprintf('workflow.%s.enter.%s', $stateMachine->getName(), $object->getStatus()),
            new Event(
                $object,
                new Marking(),
                new Transition(self::TRANSITION_INITIALISING_NAME, [$object->getStatus()], [$object->getStatus()])
            )
        );

        return $object;
    }
}
