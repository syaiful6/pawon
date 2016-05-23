<?php

namespace Pawon\Queue\Connectors;

use Pheanstalk\Pheanstalk;
use Illuminate\Support\Arr;
use Pheanstalk\PheanstalkInterface;
use Pawon\Queue\BeanstalkdQueue;

class BeanstalkdConnector implements Connector
{
    /**
     * Establish a queue connection.
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public function connect(array $config)
    {
        $pheanstalk = new Pheanstalk($config['host'], Arr::get($config, 'port', PheanstalkInterface::DEFAULT_PORT));

        return new BeanstalkdQueue(
            $pheanstalk,
            $config['queue'],
            Arr::get($config, 'ttr', Pheanstalk::DEFAULT_TTR)
        );
    }
}
