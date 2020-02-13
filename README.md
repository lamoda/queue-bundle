# Lamoda Queue Bundle

[![Build Status](https://travis-ci.org/lamoda/queue-bundle.svg?branch=master)](https://travis-ci.org/lamoda/queue-bundle)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/lamoda/queue-bundle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/lamoda/queue-bundle/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/lamoda/queue-bundle/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/lamoda/queue-bundle/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/lamoda/queue-bundle/badges/build.png?b=master)](https://scrutinizer-ci.com/g/lamoda/queue-bundle/build-status/master)

Symfony bundle for convenient work with queues. Currently it supports RabbitMQ.

## Installation

1. Install bundle
	```bash
	composer require lamoda/queue-bundle
	```

1. Extend `Lamoda\QueueBundle\Entity\QueueEntityMappedSuperclass`

    ```php
    use Doctrine\ORM\Mapping as ORM;
    use Lamoda\QueueBundle\Entity\QueueEntityMappedSuperclass;
 
    /**
     * @ORM\Entity(repositoryClass="Lamoda\QueueBundle\Entity\QueueRepository")
     */
    class Queue extends QueueEntityMappedSuperclass
    {
    }
    ```

1. Configure bundle parameters
   
   ```yaml
   lamoda_queue:
       ## required
       entity_class: App\Entity\Queue
       max_attempts: 5
       batch_size_per_requeue: 5
       batch_size_per_republish: 5
       ## optional (will use for default delay Geometric Progression Strategy)
       strategy_delay_geometric_progression_start_interval_sec: 60
       strategy_delay_geometric_progression_multiplier: 2
   ```
   
1. Register bundle

    ``` php
    class AppKernel extends Kernel
    {
        // ...
        
        public function registerBundles()
        {
            $bundles = [
                // ...
                new Lamoda\QueueBundle\LamodaQueueBundle(),
                // ...
            ];
    
            return $bundles;
        }
        
        // ...
    }
    ```
    
    or add to `config/bundles.php`
    
    ```php
    return [
        // ...
        Lamoda\QueueBundle\LamodaQueueBundle::class => ['all' => true],
        // ...
    ];
    ```

1. Migrate schema

    1. ``` doctrine:migrations:diff  ``` to create migration for ``` queue ``` table
    1. ``` doctrine:migrations:migrate ``` - apply the migration

## Setup

### Create new exchange

1. Define new exchange constant

    ```php
    namespace App\Constant;
    
    class Exchanges
    {
        public const DEFAULT = 'default';
    }
    ```

1. Add new node to `old_sound_rabbit_mq.producers` with previous defined constant name, example:

    ```yaml
    old_sound_rabbit_mq:
        producers:
            default:
                connection: default
                exchange_options:
                    name: !php/const App\Constant\Exchanges::DEFAULT
                    type: "direct"
    ```

### Create new queue

1. Define new queue constant

    ```php
    namespace App\Constant;
    
    class Queues
    {
        public const NOTIFICATION = 'notification';
    }
    ```

1. Register consumer for queue in `old_sound_rabbit_mq.consumers` with previous defined constant name, example:

    ```yaml
    old_sound_rabbit_mq:
        consumers:
            notification:
                connection: default
                exchange_options:
                    name: !php/const App\Constant\Exchanges::DEFAULT
                    type: "direct"
                queue_options:
                    name: !php/const App\Constant\Queues::NOTIFICATION
                    routing_keys:
                      - !php/const App\Constant\Queues::NOTIFICATION
                callback: "lamoda_queue.consumer"
    ```

1. Create job class, extend `AbstractJob` by example:

    ```php
    namespace App\Job;
    
    use App\Constant\Exchanges;
    use App\Constant\Queues;
    use Lamoda\QueueBundle\Job\AbstractJob;
    use JMS\Serializer\Annotation as JMS;
    
    class SendNotificationJob extends AbstractJob
    {
        /**
         * @var string
         *
         * @JMS\Type("int")
         */
        private $message;
    
        public function __construct(string $message)
        {
            $this->message = $message;
        }
    
        public function getDefaultQueue(): string
        {
            return Queues::NOTIFICATION;
        }
    
        public function getDefaultExchange(): string
        {
            return Exchanges::DEFAULT;
        }
    }
    ```

1. Create job handler, implement HandlerInterface by example:

    ```php
    namespace App\Handler;
    
    use Lamoda\QueueBundle\Handler\HandlerInterface;
    use Lamoda\QueueBundle\QueueInterface;
    
    class SendNotificationHandler implements HandlerInterface
    {
        public function handle(QueueInterface $job): void
        {
            // implement service logic here
        }
    }
    ```

1. Tag handler at service container

    ```yaml
    services:
        App\Handler\SendNotificationHandler:
            public: true
            tags:
                - { name: queue.handler, handle: App\Job\SendNotificationJob }
    ```

1. Configure delay strategy parameters (optional)
   
   ```yaml
   lamoda_queue:
   ...
       queues:
           queue_one: 'delay_arithmetic_progression'
           queue_two: 'delay_special_geometric_progression'
   
   # Settings of special behaviors for Delay strategies (optional)
   services:
     lamoda_queue.strategy.delay.arithmetic_progression:
       class: Lamoda\QueueBundle\Strategy\Delay\ArithmeticProgressionStrategy
       tags:
         - { name: 'lamoda_queue_strategy', key: 'delay_arithmetic_progression' }
       arguments:
         - 60 # start_interval_sec parameter
         - 1700 # multiplier parameter
     
    lamoda_queue.strategy.delay.geometric_progression:
       class: Lamoda\QueueBundle\Strategy\Delay\GeometricProgressionStrategy
       tags:
         - { name: 'lamoda_queue_strategy', key: 'delay_special_geometric_progression' }
       arguments:
         - 70 # start_interval_sec parameter
         - 4 # multiplier parameter

   ```
   In this block, you can config special delay behaviors for each queue. For this, you have to register new services that 
   use one of several base strategies (ArithmeticProgressionStrategy, GeometricProgressionStrategy) or yours (for this you 
   have to make Service Class that implements DelayStrategyInterface).
   
   Each strategy service has to have a tag with name `lamoda_queue_strategy` and unique `key`.
   After this, you can use these `keys` for matching with queues in `lamoda_queue.queues` section.
   
   By default, use GeometricProgressionStrategy with params (their you can customize in `lamoda_queue` config section):
   ```
       strategy_delay_geometric_progression_start_interval_sec: 60
       strategy_delay_geometric_progression_multiplier: 2
   ```

6. Add queue name in "codeception.yml" at `modules.config.AMQP.queues`

7. Execute `./bin/console queue:init` command


## Usage

### Init exchange and queues

```
./bin/console queue:init
```

### Add job to queue

```php
$job = new SendNotificationJob($id);
$container->get(Lamoda\QueueBundle\Factory\PublisherFactory::class)->publish($job);
```

### Run queued job

```
./bin/console queue:consume notification
```

### Requeue failed queues

```
./bin/console queue:requeue
```

## Advanced usage

You can queue any primitive class, just implement `QueueInterface`:

```php
namespace App\Process;

use Lamoda\QueueBundle\Entity\QueueInterface;

class MyProcess implements QueueInterface
{
    // implement interface functions
}
```

```yaml
services:
    App\Handler\MyProcessHandler:
        public: true
        tags:
            - { name: queue.handler, handle: App\Process\MyProcess }
```

```php
$process = new MyProcess();
$container->get('queue.publisher')->publish($process);
```

## How to rerun queues

If you want to rerun queue, throw `Lamoda\QueueBundle\Exception\RuntimeException`.

If you want mark queue as failed, throw any another kind of exception.

```php
namespace App\Handler;

use Lamoda\QueueBundle\Handler\HandlerInterface;
use Lamoda\QueueBundle\QueueInterface;

class SendNotificationHandler implements HandlerInterface
{
    public function handle(QueueInterface $job): void
    {
        // implement service logic here
        
        // Rerun queue
        if ($rerun === true) {
            throw new Lamoda\QueueBundle\Exception\RuntimeException('Error message');
        }
        
        // Mark queue as failed
        if ($failed === true) {
            throw new \Exception();
        }
    }
}
```

By default delay time is calculated exponentially. You can affect it through configuration.

```yaml
lamoda_queue:
  ## required
  ## ...
  max_attempts: 5
  ## optional
  strategy_delay_geometric_progression_start_interval_sec: 60
  strategy_delay_geometric_progression_multiplier: 2
```

## Events

### Lamoda\QueueBundle\Event\QueueAttemptsReachedEvent

When consumer wants to execute reached maximum attempts queue.

Properties:
  + Queue Entity `QueueAttemptsReachedEvent::getQueue()`

## Development

### PHP Coding Standards Fixer

```bash
make php-cs-check
make php-cs-fix
```

### Tests

Unit

```bash
make test-unit
```
