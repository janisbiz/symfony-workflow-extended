<?php

namespace Janisbiz\SymfonyWorkflowExtended\Repository;

use Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Entity\StateMachineTimeoutInterface;
use Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Entity\WorkflowInterface;

interface StateMachineTimeoutRepositoryInterface
{
    /**
     * @param string            $schemaName
     * @param WorkflowInterface $subject
     *
     * @return array
     */
    public function findActiveStateBasedTimeouts(string $schemaName, WorkflowInterface $subject): array;

    /**
     * @param string            $schemaName
     * @param WorkflowInterface $subject
     *
     * @return array
     */
    public function findActiveIndependentTimeouts(string $schemaName, WorkflowInterface $subject): array;

    /**
     * @param string|null $schemaName
     * @param string|null $schemaStatus
     *
     * @return StateMachineTimeoutInterface[]
     */
    public function findTimeoutsToExecute(string $schemaName = null, string $schemaStatus = null): array;
}
