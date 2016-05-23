<?php

namespace Pawon\Queue\Console\Commands;

use Pawon\Console\Command;
use Illuminate\Support\Arr;
use Pawon\Queue\Processor\Listener;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class Listen extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'queue:listen';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Listen to a given queue';

    /**
     * The queue listener instance.
     *
     * @var \Illuminate\Queue\Listener
     */
    protected $listener;

    /**
     * Create a new queue listen command.
     *
     * @param  \Illuminate\Queue\Listener  $listener
     * @return void
     */
    public function __construct(Listener $listener)
    {
        parent::__construct();

        $this->listener = $listener;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $this->setListenerOptions();

        $delay = $this->input->getOption('delay');

        $memory = $this->input->getOption('memory');

        $connection = $this->input->getArgument('connection');

        $queue = $this->getQueue($connection);
        $this->info("Listening new job on $connection for queue $queue");
        $this->listener->listen(
            $connection,
            $queue,
            $delay,
            $memory
        );
    }

    /**
     * Get the name of the queue connection to listen on.
     *
     * @param  string  $connection
     * @return string
     */
    protected function getQueue($connection)
    {
        $config = $this->container->get('config');

        if (is_null($connection)) {
            $connection = Arr::get($config, 'queue.default');
        }

        $queue = Arr::get($config, "queue.connections.{$connection}.queue", 'default');

        return $this->input->getOption('queue') ?: $queue;
    }

    /**
     * Set the options on the queue listener.
     *
     * @return void
     */
    protected function setListenerOptions()
    {
        $this->listener->setSleep($this->option('sleep'));

        $this->listener->setMaxTries($this->option('tries'));
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['connection', InputArgument::OPTIONAL, 'The name of connection'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['queue', null, InputOption::VALUE_OPTIONAL, 'The queue to listen on', null],

            ['delay', null, InputOption::VALUE_OPTIONAL, 'Amount of time to delay failed jobs', 0],

            ['memory', null, InputOption::VALUE_OPTIONAL, 'The memory limit in megabytes', 128],

            ['sleep', null, InputOption::VALUE_OPTIONAL, 'Seconds to wait before checking queue for jobs', 3],

            ['tries', null, InputOption::VALUE_OPTIONAL, 'Number of times to attempt a job before logging it failed', 0],
        ];
    }
}
