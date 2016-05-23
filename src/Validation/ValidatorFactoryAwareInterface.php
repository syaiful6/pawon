<?php

namespace Pawon\Validation;

use Illuminate\Contracts\Validation\Factory as FactoryContract;

interface ValidatorFactoryAwareInterface
{
    /**
     *
     */
    public function setValidatorFactory(FactoryContract $factory);
}
