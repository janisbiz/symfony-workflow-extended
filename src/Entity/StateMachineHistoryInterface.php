<?php

namespace Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Entity;

interface StateMachineHistoryInterface
{
    /**
     * Get id
     *
     * @return integer
     */
    public function getId(): int;

    /**
     * Set schemaName
     *
     * @param string $schemaName
     *
     * @return StateMachineHistoryInterface
     */
    public function setSchemaName(string $schemaName);

    /**
     * Get schemaName
     *
     * @return string
     */
    public function getSchemaName(): string;

    /**
     * Set schemaIdentifier
     *
     * @param integer $schemaIdentifier
     *
     * @return StateMachineHistoryInterface
     */
    public function setSchemaIdentifier(int $schemaIdentifier);

    /**
     * Get schemaIdentifier
     *
     * @return int
     */
    public function getSchemaIdentifier(): int;

    /**
     * Set transition
     *
     * @param string $transition
     *
     * @return StateMachineHistoryInterface
     */
    public function setTransition(string $transition);

    /**
     * Get transition
     *
     * @return string
     */
    public function getTransition(): string;

    /**
     * Set initialState
     *
     * @param string $initialState
     *
     * @return StateMachineHistoryInterface
     */
    public function setInitialState(string $initialState);

    /**
     * Get initialState
     *
     * @return string
     */
    public function getInitialState(): string;
    /**
     * Set finalState
     *
     * @param string $finalState
     *
     * @return StateMachineHistoryInterface
     */
    public function setFinalState(string $finalState);

    /**
     * Get finalState
     *
     * @return string
     */
    public function getFinalState(): string;

    /**
     * Set commands
     *
     * @param array $commands
     *
     * @return StateMachineHistoryInterface
     */
    public function setCommands(array $commands);

    /**
     * Get parameters
     *
     * @return array
     */
    public function getCommands(): array;

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return StateMachineHistoryInterface
     */
    public function setCreatedAt(\DateTime $createdAt);

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime;

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     *
     * @return StateMachineHistoryInterface
     */
    public function setUpdatedAt(\DateTime $updatedAt);

    /**
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt(): \DateTime;
}
