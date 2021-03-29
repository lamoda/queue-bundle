<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle\Tests\Unit;

use Lamoda\QueueBundle\Command\QueueConsumerCommand;
use Lamoda\QueueBundle\Consumer;
use Lamoda\QueueBundle\Entity\QueueRepository;
use Lamoda\QueueBundle\Factory\EntityFactory;
use Lamoda\QueueBundle\Factory\PublisherFactory;
use Lamoda\QueueBundle\Handler\HandlerInterface;
use Lamoda\QueueBundle\Job\AbstractJob;
use Lamoda\QueueBundle\Publisher;
use Lamoda\QueueBundle\Service\DelayService;
use Lamoda\QueueBundle\Service\QueueRequeueService;
use Lamoda\QueueBundle\Service\QueueService;
use OldSound\RabbitMqBundle\RabbitMq\Producer;
use PHPUnit_Framework_MockObject_MockObject;

trait QueueCommonServicesTrait
{
    /**
     * @return EntityFactory | PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockEntityFactory(?array $methods = null)
    {
        return $this->getQueueMockService(EntityFactory::class, $methods);
    }

    /**
     * @return QueueService | PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockQueueService(?array $methods = null)
    {
        return $this->getQueueMockService(QueueService::class, $methods);
    }

    /**
     * @return Publisher | PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockPublisher(?array $methods = null)
    {
        return $this->getQueueMockService(Publisher::class, $methods);
    }

    /**
     * @return PublisherFactory | PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockPublisherFactory(?array $methods = null)
    {
        return $this->getQueueMockService(PublisherFactory::class, $methods);
    }

    /**
     * @return Consumer | PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockConsumer(?array $methods = null)
    {
        return $this->getQueueMockService(Consumer::class, $methods);
    }

    /**
     * @return QueueRequeueService | PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockQueueRequeueService(?array $methods = null)
    {
        return $this->getQueueMockService(QueueRequeueService::class, $methods);
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject | QueueConsumerCommand
     */
    protected function getQueueMockConsumerCommand(?array $methods = null)
    {
        return $this->getQueueMockService(QueueConsumerCommand::class, $methods);
    }

    /**
     * @return QueueRepository | PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockQueueRepository(?array $methods = null)
    {
        return $this->getQueueMockService(QueueRepository::class, $methods);
    }

    /**
     * @return HandlerInterface | PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockHandler(?array $methods = null)
    {
        return $this->getQueueMockService(HandlerInterface::class, $methods);
    }

    /**
     * @return Producer | PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockProducer(?array $methods = null)
    {
        return $this->getQueueMockService(Producer::class, $methods);
    }

    /**
     * @return AbstractJob | PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockAbstractJob(?array $methods = null)
    {
        return $this->getMockBuilder(AbstractJob::class)
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMockForAbstractClass();
    }

    private function getQueueMockService(
        string $serviceName,
        ?array $methods = null
    ): PHPUnit_Framework_MockObject_MockObject {
        return $this->getMockBuilder($serviceName)
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();
    }

    /**
     * @return DelayService | PHPUnit_Framework_MockObject_MockObject
     */
    private function getMockDelayService(?array $methods = null)
    {
        return $this->getMockBuilder(DelayService::class)
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();
    }

    abstract protected function getMockBuilder($className);
}
