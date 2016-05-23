<?php

namespace Pawon\Contrib\Http;

use Interop\Container\ContainerInterface;

class WebMiddlewareFactory
{
    protected $webStack = [
        'Pawon\Cookie\QueueMiddleware',
        'Pawon\Session\SessionMiddleware',
        'Pawon\Middleware\Csrf',
        'Pawon\Auth\AuthenticationMiddleware',
        'Pawon\Flash\FlashMessageMiddleware',
        'Pawon\Middleware\ContextProcessor',
    ];

    protected $skip = '/api';

    /**
     *
     */
    public function __invoke(ContainerInterface $container)
    {
        $web = new SkipMiddlewarePipe($container, $this->skip);

        foreach ($this->webStack as $m) {
            $web->pipe($m);
        }

        return $web;
    }
}
