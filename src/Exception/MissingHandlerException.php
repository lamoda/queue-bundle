<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle\Exception;

use Lamoda\QueueBundle\ConstantMessage;
use Throwable;

class MissingHandlerException extends \RuntimeException
{
    public function __construct(string $jobName, int $code = 0, Throwable $previous = null)
    {
        parent::__construct(sprintf(ConstantMessage::JOB_HANDLER_NOT_REGISTERED, $jobName), $code, $previous);
    }
}
