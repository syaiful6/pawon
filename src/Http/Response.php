<?php

namespace Pawon\Http;

use Countable;

class Response extends BaseResponse implements Countable
{
    /**
     *
     */
    public function count()
    {
        return 1;
    }
}
