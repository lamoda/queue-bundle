<?php

declare(strict_types=1);

namespace Lamoda\QueueBundle;

use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializerInterface;
use Lamoda\QueueBundle\Entity\QueueEntityInterface;
use Lamoda\QueueBundle\Exception\AttemptsReachedException;
use Lamoda\QueueBundle\Exception\RuntimeException;
use Lamoda\QueueBundle\Exception\UnexpectedValueException;
use Lamoda\QueueBundle\Handler\JobHandler;
use Lamoda\QueueBundle\Service\DelayService;
use Lamoda\QueueBundle\Service\QueueService;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Throwable;

class Consumer implements ConsumerInterface
{
    /** @var QueueService */
    protected $queueService;

    /** @var JobHandler */
    protected $jobHandler;

    /** @var SerializerInterface */
    protected $serializer;

    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var LoggerInterface */
    protected $logger;

    /** @var DelayService */
    protected $delayService;

    public function __construct(
        QueueService $queueService,
        JobHandler $jobHandler,
        SerializerInterface $serializer,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        DelayService $delayService
    ) {
        $this->queueService = $queueService;
        $this->jobHandler = $jobHandler;
        $this->serializer = $serializer;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->delayService = $delayService;
    }

    /**
     * @param AMQPMessage $message
     *
     * @throws \Exception
     *
     * @return int
     */
    public function execute(AMQPMessage $message): int
    {
        $data = json_decode($message->body, true);

        try {
            if (!isset($data['id'])) {
                throw new UnexpectedValueException(ConstantMessage::AMQP_DATA_DAMAGED);
            }

            $this->entityManager->clear();

            return $this->doExecute(
                $this->queueService->getToProcess($data['id'])
            );
        } catch (UnexpectedValueException $exception) {
            $this->logger->alert($exception->getMessage(), $this->getMessageLogParams($message));

            return self::MSG_REJECT;
        } catch (AttemptsReachedException $exception) {
            $this->logger->alert($exception->getMessage(), $this->getMessageLogParams($message));

            return self::MSG_REJECT;
        }
    }

    protected function doExecute(QueueEntityInterface $queueEntity): int
    {
        $queueData = $queueEntity->getData();
        $queueName = $queueEntity->getName();
        $queueId = $queueEntity->getId();

        $this->logger->info('START: ' . $queueName, [
            'queue_id' => $queueId, 'message_data' => $queueData,
        ]);

        try {
            $this->executeQueue($queueEntity);
            $queueEntity->setDone();
        } catch (RuntimeException $exception) {
            $this->logger->info(
                sprintf(ConstantMessage::CONSUMER_JOB_EXECUTING_FAILED, $queueName),
                $this->buildExceptionContext($exception, $queueEntity)
            );

            $this->delayService->delayQueue($queueEntity);
        } catch (Throwable $exception) {
            $this->logger->alert(
                sprintf(ConstantMessage::CONSUMER_JOB_EXECUTING_UNPREDICTABLE_FAILED, $queueName),
                $this->buildExceptionContext($exception, $queueEntity)
            );
            $queueEntity->setError();
        } finally {
            $this->queueService->save($queueEntity);

            $this->logger->info(
                'END:' . $queueName,
                [
                    'queue_id' => $queueId, 'message_data' => $queueData,
                ]
            );
        }

        return self::MSG_ACK;
    }

    protected function executeQueue(QueueEntityInterface $queueEntity): void
    {
        $data = json_encode($queueEntity->getData());

        /** @var QueueInterface $job */
        $job = $this->serializer->deserialize($data, $queueEntity->getJobName(), 'json');

        $this->jobHandler->handle($job);
    }

    private function getMessageLogParams(AMQPMessage $message): array
    {
        return [
            'amqp_message_body' => $message->body,
        ];
    }

    private function buildExceptionContext(Throwable $exception, QueueEntityInterface $queue): array
    {
        return [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'id' => $queue->getId(),
            'job_name' => $queue->getJobName(),
        ];
    }
}
