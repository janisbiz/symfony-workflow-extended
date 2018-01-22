<?php

namespace Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Enum;

/**
 * Class TimeoutEnum
 * @package Janisbiz\SymfonyWorkflowExtended\WorkflowBundle\Enum
 */
class TimeoutEnum
{
    const TIMEOUT_OPTION_COMMANDS = 'commands';
    const TIMEOUT_OPTION_INTERVAL = 'interval';
    const TIMEOUT_OPTION_NAME = 'name';
    const TIMEOUT_OPTION_ON_FAILURE = 'on_failure';
    const TIMEOUT_OPTION_ON_SUCCESS = 'on_success';
    const TIMEOUT_OPTION_TYPE = 'type';

    const OPTIONS = [
        self::TIMEOUT_OPTION_COMMANDS,
        self::TIMEOUT_OPTION_INTERVAL,
        self::TIMEOUT_OPTION_NAME,
        self::TIMEOUT_OPTION_ON_FAILURE,
        self::TIMEOUT_OPTION_ON_SUCCESS,
        self::TIMEOUT_OPTION_TYPE,
    ];
}
