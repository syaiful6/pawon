<?php

namespace Pawon\Queue;

use League\Tactician\CommandBus;
use Illuminate\Contracts\Queue\Job;

class CallQueuedHandler
{
    /**
     *
     */
    protected $command;

    /**
     *
     */
    public function __construct(CommandBus $command)
    {
        $this->command = $command;
    }

    /**
     * Handle the queued job.
     *
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  array  $data
     * @return void
     */
    public function __invoke(Job $job, array $data)
    {
        $command = $this->setJobInstanceIfNecessary(
            $job,
            unserialize($data['command'])
        );

        $this->command->handle($command);

        if (! $job->isDeletedOrReleased()) {
            $job->delete();
        }
    }

    /**
     * Set the job instance of the given class if necessary.
     *
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  mixed  $instance
     * @return mixed
     */
    protected function setJobInstanceIfNecessary(Job $job, $instance)
    {
        if (in_array('App\Queue\InteractsWithQueue', class_uses_recursive(get_class($instance)))) {
            $instance->setJob($job);
        }

        return $instance;
    }

    /**
     * Call the failed method on the job instance.
     *
     * @param  array  $data
     * @return void
     */
    public function failed(array $data)
    {
        $command = unserialize($data['command']);

        if (method_exists($command, 'failed')) {
            $command->failed();
        }
    }
}
