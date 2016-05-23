<?php

namespace Pawon\Queue\Jobs;

use Illuminate\Contracts\Queue\Job as JobContract;

interface Job extends JobContract
{
    /**
     *
     */
    public function __invoke();
}
