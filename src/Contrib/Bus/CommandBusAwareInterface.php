<?php

namespace Pawon\Contrib\Bus;

use League\Tactician\CommandBus;

interface CommandBusAwareInterface
{
    /**
     *
     */
    public function setCommandBus(CommandBus $commandBus);

    /**
     *
     */
    public function getCommandBus();
}
