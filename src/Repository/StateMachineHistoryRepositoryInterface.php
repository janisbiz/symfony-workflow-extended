<?php

namespace Janisbiz\SymfonyWorkflowExtended\Repository;

interface StateMachineHistoryRepositoryInterface
{
    /**
     * @param string $schemaName
     * @param int $schemaIdentifier
     * @param string $transition
     *
     * @return null|object
     */
    public function findLastLogEntry(string $schemaName, int $schemaIdentifier, string $transition);
}
