<?php

namespace Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Command;

use Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Service\StateMachineTimeoutService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class StateMachineTimeoutCommand
 * @package Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Command
 */
class TimeoutCommand extends ContainerAwareCommand
{
    use LockableTrait;

    const NAME = 'janisbiz:symfony-workflow-extended:timeout';

    /** @var InputInterface */
    private $input;

    /** @var OutputInterface */
    private $output;

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @throws \InvalidArgumentException
     * @return bool
     */
    public function execute(InputInterface $input, OutputInterface $output): bool
    {
        $this->input = $input;
        $this->output = $output;

        $this->lock(self::NAME);

        $this->getContainer()->get(StateMachineTimeoutService::class)->resolveTimeouts();

        $this->release();

        return true;
    }

    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Executes state-machine timeouts.')
        ;
    }
}
