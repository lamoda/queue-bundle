<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle\Job;

use JMS\Serializer\Annotation as JMS;
use Lamoda\QueueBundle\Job\Feature\Queueable;
use Lamoda\QueueBundle\QueueInterface;

/**
 * @JMS\ExclusionPolicy(value="NONE")
 */
abstract class AbstractJob implements QueueInterface
{
    use Queueable;
}
