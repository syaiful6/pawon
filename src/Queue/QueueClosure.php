<?php

namespace Pawon\Queue;

use Illuminate\Contracts\Encryption\Encrypter as EncrypterContract;

class QueueClosure
{
    /**
     * The encrypter instance.
     *
     * @var \Illuminate\Contracts\Encryption\Encrypter
     */
    protected $crypt;

    /**
     * Create a new queued Closure job.
     *
     * @param \Illuminate\Contracts\Encryption\Encrypter $crypt
     */
    public function __construct(EncrypterContract $crypt)
    {
        $this->crypt = $crypt;
    }

    /**
     * Fire the Closure based queue job.
     *
     * @param \Illuminate\Contracts\Queue\Job $job
     * @param array                           $data
     */
    public function __invoke($job, $data)
    {
        $closure = unserialize($this->crypt->decrypt($data['closure']));

        $closure($job);
    }
}
