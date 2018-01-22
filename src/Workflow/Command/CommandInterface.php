<?php

namespace Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Workflow\Command;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Workflow\Event\Event;

/**
 * Class CommandInterface
 * @package Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Workflow\Command
 */
interface CommandInterface
{
    /**
     * @param Event $event
     *
     * @return mixed
     */
    public function execute(Event $event, ContainerInterface $container): bool;
}
