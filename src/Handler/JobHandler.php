<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle\Handler;

use Lamoda\QueueBundle\Exception\MissingHandlerException;
use Lamoda\QueueBundle\QueueInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class JobHandler implements HandlerInterface
{
    /** @var ContainerInterface */
    private $container;

    /** @var array */
    private $jobToServiceIdMapping;

    public function __construct(ContainerInterface $container, array $jobToServiceIdMapping)
    {
        $this->container = $container;
        $this->jobToServiceIdMapping = $jobToServiceIdMapping;
    }

    public function handle(QueueInterface $job): void
    {
        $jobName = get_class($job);

        $this->getHandler($jobName)->handle($job);
    }

    /**
     * @return object | HandlerInterface
     */
    protected function getHandler(string $jobName): HandlerInterface
    {
        if (!isset($this->jobToServiceIdMapping[$jobName])) {
            throw new MissingHandlerException($jobName);
        }

        return $this->container->get($this->jobToServiceIdMapping[$jobName]);
    }
}
