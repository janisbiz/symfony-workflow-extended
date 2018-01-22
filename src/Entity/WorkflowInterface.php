<?php

namespace Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Entity;

interface WorkflowInterface
{
    /**
     * @param string $marking
     *
     * @return $this
     */
    public function setMarking(string $marking);

    /**
     * @return string
     */
    public function getMarking(): ?string;

    /**
     * @param string $status
     *
     * @return $this
     */
    public function setStatus($status);

    /**
     * @return string
     */
    public function getStatus();

    /**
     * @return int
     */
    public function getWorkflowIdentifier(): ?int;
}
