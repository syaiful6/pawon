<?php

namespace Pawon\Queue;

use Exception;
use Throwable;
use Pawon\Queue\Jobs\SyncJob;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Contracts\Queue\Queue as QueueContract;

class SyncQueue extends Queue implements QueueContract
{
    /**
     * Push a new job onto the queue.
     *
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return mixed
     *
     * @throws \Exception|\Throwable
     */
    public function push($job, $data = '', $queue = null)
    {
        $queueJob = $this->resolveJob($this->createPayload($job, $data, $queue));

        try {
            $queueJob->fire();
        } catch (Exception $e) {
            $this->handleFailedJob($queueJob);

            throw $e;
        } catch (Throwable $e) {
            $this->handleFailedJob($queueJob);

            throw $e;
        }

        return 0;
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param  string  $payload
     * @param  string  $queue
     * @param  array   $options
     * @return mixed
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        //
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param  \DateTime|int  $delay
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return mixed
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        return $this->push($job, $data, $queue);
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param  string  $queue
     * @return \Illuminate\Contracts\Queue\Job|null
     */
    public function pop($queue = null)
    {
        //
    }

    /**
     * Resolve a Sync job instance.
     *
     * @param  string  $payload
     * @return \Illuminate\Queue\Jobs\SyncJob
     */
    protected function resolveJob($payload)
    {
        return new SyncJob($this->container, $payload);
    }

    /**
     * Handle the failed job.
     *
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @return array
     */
    protected function handleFailedJob(Job $job)
    {
        $job->failed();
    }
}
