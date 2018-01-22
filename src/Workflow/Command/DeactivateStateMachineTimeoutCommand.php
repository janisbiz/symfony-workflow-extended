<?php

namespace Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Workflow\Command;

use Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Entity\WorkflowInterface;
use Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Service\StateMachineTimeoutService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Workflow\Event\Event;

/**
 * Class DeactivateStateMachineTimeoutCommand
 * @package Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Workflow\Command
 */
class DeactivateStateMachineTimeoutCommand implements CommandInterface
{
    /**
     * @param Event $event
     * @param ContainerInterface $container
     *
     * @return bool
     */
    public function execute(Event $event, ContainerInterface $container): bool
    {
        /** @var WorkflowInterface $workflow */
        $workflow = $event->getSubject();

        $container
            ->get(StateMachineTimeoutService::class)
            ->deactivateStateBasedTimeouts($workflow)
        ;

        return true;
    }
}
