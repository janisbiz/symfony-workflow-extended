<?php

namespace Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Traits;

use Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Exception\InvalidCommandException;
use Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Workflow\Command\CommandInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Workflow\Event\Event;

/**
 * Trait CommandTrait
 * @package Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Traits
 */
trait CommandTrait
{
    /** @var string[] */
    private $commands;

    /** @var CommandInterface[] */
    private $commandObjects = [];

    /**
     * @param Event $event
     * @param ContainerInterface $container
     * @param string|null $logMethodName
     * @param array ...$params
     *
     * @return bool
     */
    public function executeCommands(
        Event $event,
        ContainerInterface $container,
        string $logMethodName = null,
        ... $params
    ): bool {
        $executedCommands = [];
        $result = true;

        foreach ($this->commandObjects as $commandName => $commandObject) {
            $result = $commandObject->execute($event, $container);
            $executedCommands[] = [$commandName => $result];

            if ($result === false) {
                break;
            }
        }

        if ($logMethodName !== null && !empty($executedCommands)) {
            array_unshift($params, $executedCommands);
            call_user_func_array(
                [$this, $logMethodName],
                $params
            );
        }

        return $result;
    }

    /**
     * @return string[]
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * @return CommandInterface[]
     */
    public function getCommandObjects(): array
    {
        return $this->commandObjects;
    }

    /**
     * @param array|null $commands
     *
     * @return CommandTrait
     */
    private function setCommands(array $commands = null): self
    {
        $this->commands = $commands;

        return $this;
    }

    /**
     * @return CommandTrait
     * @throws InvalidCommandException
     */
    private function validateCommands(): self
    {
        foreach ($this->commands as &$command) {
            if (substr($command, 0, 1) !== '\\') {
                $command = sprintf('\%s', $command);
            }

            $commandObject = new $command();

            if (!is_subclass_of($commandObject, CommandInterface::class)) {
                throw new InvalidCommandException(
                    sprintf('Command "%s" should extend "%s"', $command, CommandInterface::class)
                );
            }

            $this->commandObjects[$command] = $commandObject;
        }

        return $this;
    }
}
