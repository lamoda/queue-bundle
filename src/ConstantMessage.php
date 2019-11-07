<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle;

class ConstantMessage
{
    public const AMQP_BROKER_IS_DOWN = 'The AMQP broker is down';
    public const AMQP_ERROR = 'AMQP error';
    public const AMQP_TIMEOUT_ERROR = 'AMQP timeout error';
    public const AMQP_DATA_DAMAGED = 'Data was damaged. Remove message from queue';
    public const QUEUE_CONSUMER_COMMAND_UNEXPECTED_ERROR = 'Queue consumer command unexpected error';

    public const JOB_HANDLER_COMPILE_ERROR = 'The `%s` tag must always have a `job` attribute';
    public const JOB_HANDLER_NOT_REGISTERED = 'Job handler for `%s` not registered';

    public const CONSUMER_JOB_EXECUTING_FAILED = 'Executing of job "%s" failed';
    public const CONSUMER_JOB_EXECUTING_UNPREDICTABLE_FAILED = 'Executing of job "%s" unpredictable failed';

    public const PUBLISHER_NOT_FOUND = 'Publisher `%s` not found';

    public const QUEUE_ENTITY_NOT_FOUND                    = 'The queue with id "%d" was not found';
    public const QUEUE_ENTITY_NOT_FOUND_IN_SUITABLE_STATUS = 'The queue "%s" with job "%s" was not found in suitable status. Actual status is "%s"';
    public const QUEUE_ATTEMPTS_REACHED                    = 'The queue "%s" has reached it\'s attempts count maximum';
    public const QUEUE_CAN_NOT_REQUEUE                     = 'Can not requeue messages';
    public const QUEUE_CAN_NOT_REPUBLISH                   = 'Can not republish messages';
    public const QUEUE_SUCCESS_REPUBLISH                   = 'Queue republish successfully completed';
}
