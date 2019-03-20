<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle\Tests\Unit\Job;

use JMS\Serializer\Annotation as JMS;
use Lamoda\QueueBundle\Job\AbstractJob;

class StubJob extends AbstractJob
{
    /**
     * @var int
     *
     * @JMS\Type("integer")
     */
    private $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getDefaultQueue(): string
    {
        return 'queue';
    }

    public function getDefaultExchange(): string
    {
        return 'exchange';
    }
}
