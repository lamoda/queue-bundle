<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle\Tests\Unit;

use Doctrine\ORM\EntityManager;
use JMS\Serializer\Serializer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcher;

trait SymfonyMockTrait
{
    /**
     * @param array $methods
     *
     * @return Container | \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockServiceContainer(array $methods = [])
    {
        return $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();
    }

    /**
     * @param array $methods
     *
     * @return EntityManager | \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockEntityManager(array $methods = [])
    {
        return $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();
    }

    /**
     * @param array $methods
     *
     * @return LoggerInterface | \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockLogger(array $methods = [])
    {
        return $this->getMockBuilder(LoggerInterface::class)
            ->setMethods($methods)
            ->getMockForAbstractClass();
    }

    /**
     * @param array $methods
     *
     * @return Serializer | \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getJMSSerializer(array $methods = [])
    {
        return $this->getMockBuilder(Serializer::class)
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();
    }

    /**
     * @param array $methods
     *
     * @return ArgvInput | \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockInput(array $methods = [])
    {
        return $this->getMockBuilder(ArgvInput::class)
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();
    }

    /**
     * @param array $methods
     *
     * @return ConsoleOutput | \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockOutput(array $methods = [])
    {
        return $this->getMockBuilder(ConsoleOutput::class)
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();
    }

    /**
     * @param array $methods
     *
     * @return EventDispatcher | \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockEventDispatcher(array $methods = [])
    {
        return $this->getMockBuilder(EventDispatcher::class)
            ->setMethods($methods)
            ->getMock();
    }

    abstract protected function getMockBuilder($className);
}
