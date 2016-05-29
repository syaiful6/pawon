<?php

namespace Pawon\Queue\Processor;

use Generator;
use Illuminate\Contracts\Queue\Job;
use Pawon\Queue\Failed\FailedJobProviderInterface as FailedJob;
use Illuminate\Contracts\Queue\Factory as FactoryContract;

class Listener
{
    /**
     * The amount of seconds to wait before polling the queue.
     *
     * @var int
     */
    protected $sleep = 3;

    /**
     * The amount of times to try a job before logging it failed.
     *
     * @var int
     */
    protected $maxTries = 0;

    /**
     * @var
     */
    protected $factory;

    /**
     *
     */
    protected $failer;

    /**
     *
     */
    public function __construct(FactoryContract $factory, FailedJob $failer)
    {
        $this->factory = $factory;
        $this->failer = $failer;
    }

    /**
     *
     */
    public function listen($connectionName, $queue, $delay, $memory = 128)
    {
        $scheduler = new Scheduler();
        $scheduler->add($this->collectQueue(
            $connectionName,
            $queue,
            $delay
        ));
        $scheduler->run();
    }

    /**
     *
     */
    protected function collectQueue($connectionName, $queue, $delay, $memory = 128)
    {
        while (true) {
            $connection = $this->factory->connection($connectionName);
            $job = $this->getNextJob($connection, $queue);
            if (!is_null($job)) {
                yield $this->newTask($this->process(
                    $connectionName,
                    $job,
                    $delay
                ));
            }

            sleep($this->sleep);
            yield;

            if ($this->memoryExceeded($memory)) {
                break;
            }
        }
    }

    /**
     * Get the next job from the queue connection.
     *
     * @param \Illuminate\Contracts\Queue\Queue $connection
     * @param string                            $queue
     *
     * @return \Illuminate\Contracts\Queue\Job|null
     */
    protected function getNextJob($connection, $queue)
    {
        if (is_null($queue)) {
            return $connection->pop();
        }

        foreach (explode(',', $queue) as $queue) {
            if (!is_null($job = $connection->pop($queue))) {
                return $job;
            }
        }
    }

    /**
     * Process a given job from the queue.
     *
     * @param string                          $connection
     * @param \Illuminate\Contracts\Queue\Job $job
     * @param int                             $maxTries
     * @param int                             $delay
     *
     * @return array|null
     *
     * @throws \Throwable
     */
    public function process($connection, Job $job, $delay = 0)
    {
        if ($this->maxTries > 0 && $job->attempts() > $this->maxTries) {
            yield $this->logFailedJob($connection, $job);

            return;
        }

        try {
            yield $job->fire();

            yield ['job' => $job, 'failed' => false];
        } finally {
            if (!$job->isDeleted()) {
                yield $job->release($delay);
            }
        }
    }

    /**
     *
     */
    protected function newTask(Generator $coroutine)
    {
        return new SystemCall(
            function (Task $task, Scheduler $scheduler) use ($coroutine) {
                $task->setSendValue($scheduler->add($coroutine));
                $scheduler->schedule($task);
            }
        );
    }

    /**
     *
     */
    protected function getTaskId()
    {
        return new SystemCall(function (Task $task, Scheduler $scheduler) {
            $task->setSendValue($task->getTaskId());
            $scheduler->schedule($task);
        });
    }

    /**
     * Determine if the memory limit has been exceeded.
     *
     * @param int $memoryLimit
     *
     * @return bool
     */
    public function memoryExceeded($memoryLimit)
    {
        return (memory_get_usage() / 1024 / 1024) >= $memoryLimit;
    }

    /**
     * Log a failed job into storage.
     *
     * @param string                          $connection
     * @param \Illuminate\Contracts\Queue\Job $job
     *
     * @return array
     */
    protected function logFailedJob($connection, Job $job)
    {
        if ($this->failer) {
            $this->failer->log($connection, $job->getQueue(), $job->getRawBody());

            $job->delete();

            $job->failed();
        }

        return ['job' => $job, 'failed' => true];
    }

    /**
     * Get the amount of seconds to wait before polling the queue.
     *
     * @return int
     */
    public function getSleep()
    {
        return $this->sleep;
    }

    /**
     * Set the amount of seconds to wait before polling the queue.
     *
     * @param int $sleep
     */
    public function setSleep($sleep)
    {
        $this->sleep = $sleep;
    }

    /**
     * Set the amount of times to try a job before logging it failed.
     *
     * @param int $tries
     */
    public function setMaxTries($tries)
    {
        $this->maxTries = $tries;
    }
}
