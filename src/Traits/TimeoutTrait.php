<?php

namespace Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Traits;

use Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Entity\WorkflowInterface;
use Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Enum\TimeoutEnum;
use Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Exception\InvalidTimeoutParameterException;
use Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Exception\MissingTimeoutParameterException;
use Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Service\StateMachineTimeoutService;

/**
 * Trait TimeoutTrait
 * @package Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Traits
 */
trait TimeoutTrait
{
    /**
     * @param array|null $timeouts
     *
     * @return TimeoutTrait
     * @throws MissingTimeoutParameterException
     */
    public function validateTimeouts(array &$timeouts = null): self
    {
        if ($timeouts === null) {
            /** Nothing to validate... returning $this */
            return $this;
        }

        if (!is_array($timeouts)) {
            throw new InvalidTimeoutParameterException(sprintf(
                'Timeout option parameters should be passed as array consisting of options: "%s"',
                implode(', ', TimeoutEnum::OPTIONS)
            ));
        }

        foreach ($timeouts as $i => &$timeout) {
            foreach ([TimeoutEnum::TIMEOUT_OPTION_NAME, TimeoutEnum::TIMEOUT_OPTION_INTERVAL] as $option) {
                if (empty($timeout[$option])) {
                    throw new MissingTimeoutParameterException(sprintf(
                        'Please provide timeout %s as parameter "%s" for timeout with index "%d"',
                        TimeoutEnum::TIMEOUT_OPTION_INTERVAL,
                        TimeoutEnum::TIMEOUT_OPTION_INTERVAL,
                        $i
                    ));
                }
            }

            if (!$timeout[TimeoutEnum::TIMEOUT_OPTION_INTERVAL] instanceof \DateInterval) {
                if (strstr($timeout[TimeoutEnum::TIMEOUT_OPTION_INTERVAL], '::') === false) {
                    $timeout[TimeoutEnum::TIMEOUT_OPTION_INTERVAL] =
                        new \DateInterval($timeout[TimeoutEnum::TIMEOUT_OPTION_INTERVAL]);
                } else {
                    $classNameAndMethodName = explode('::', $timeout[TimeoutEnum::TIMEOUT_OPTION_INTERVAL]);

                    $timeout[TimeoutEnum::TIMEOUT_OPTION_INTERVAL] =
                        $classNameAndMethodName[0]::$classNameAndMethodName[1];
                }

                if (!$timeout[TimeoutEnum::TIMEOUT_OPTION_INTERVAL] instanceof \DateInterval) {
                    throw new InvalidTimeoutParameterException('Interval is not an instance of DateInterval!');
                }
            }
        }

        return $this;
    }


    /**
     * @param StateMachineTimeoutService $stateMachineTimeoutService
     * @param WorkflowInterface $subject
     * @param array|null $timeouts
     *
     * @return TimeoutTrait
     */
    protected function registerTimeouts(
        StateMachineTimeoutService $stateMachineTimeoutService,
        WorkflowInterface $subject,
        array &$timeouts = null
    ): self {
        if (!empty($timeouts)) {
            foreach ($timeouts as $timeout) {
                $stateMachineTimeoutService->createTimeout(
                    $this->getStateMachine(),
                    $subject,
                    $timeout
                );
            }
        }

        return $this;
    }
}
