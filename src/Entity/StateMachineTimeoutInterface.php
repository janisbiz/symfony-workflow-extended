<?php

namespace Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Entity;

interface StateMachineTimeoutInterface
{
    /**
     * Get id
     *
     * @return int
     */
    public function getId(): int;

    /**
     * Set schemaName
     *
     * @param string $schemaName
     *
     * @return StateMachineTimeoutInterface
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
     * @param int $schemaIdentifier
     *
     * @return StateMachineTimeoutInterface
     */
    public function setSchemaIdentifier(int $schemaIdentifier);

    /**
     * Get schemaIdentifier
     *
     * @return integer
     */
    public function getSchemaIdentifier(): int;

    /**
     * Set schemaStatus
     *
     * @param string $schemaStatus
     *
     * @return StateMachineTimeoutInterface
     */
    public function setSchemaStatus(string $schemaStatus);

    /**
     * Get schemaStatus
     *
     * @return string
     */
    public function getSchemaStatus(): string;

    /**
     * Set name
     *
     * @param string $name
     *
     * @return StateMachineTimeoutInterface
     */
    public function setName(string $name);

    /**
     * Get name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Set type
     *
     * @param string $type
     *
     * @return StateMachineTimeoutInterface
     */
    public function setType(string $type);

    /**
     * Get type
     *
     * @return string
     */
    public function getType(): string;

    /**
     * Set active
     *
     * @param boolean $active
     *
     * @return StateMachineTimeoutInterface
     */
    public function setActive(bool $active);

    /**
     * Get active
     *
     * @return boolean
     */
    public function getActive(): bool;

    /**
     * Set timeToRun
     *
     * @param \DateTime $timeToRun
     *
     * @return StateMachineTimeoutInterface
     */
    public function setTimeToRun(\DateTime $timeToRun);

    /**
     * Get timeToRun
     *
     * @return \DateTime
     */
    public function getTimeToRun(): \DateTime;

    /**
     * Set commands
     *
     * @param array $commands
     *
     * @return StateMachineTimeoutInterface
     */
    public function setCommands(array $commands);

    /**
     * Get parameters
     *
     * @return array
     */
    public function getCommands(): array;

    /**
     * Set executedAt
     *
     * @param \DateTime $executedAt
     *
     * @return StateMachineTimeoutInterface
     */
    public function setExecutedAt(\DateTime $executedAt);

    /**
     * Get executedAt
     *
     * @return \DateTime
     */
    public function getExecutedAt(): \DateTime;

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return StateMachineTimeoutInterface
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
     * @return StateMachineTimeoutInterface
     */
    public function setUpdatedAt(\DateTime $updatedAt);

    /**
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt(): \DateTime;
}
