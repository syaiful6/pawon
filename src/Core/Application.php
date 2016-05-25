<?php

namespace Pawon\Core;

use Traversable;
use Zend\Diactoros\Response;
use Pawon\Http\Response as PawonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Interop\Container\ContainerInterface;
use Zend\Expressive\Router\RouterInterface;
use Zend\Expressive\Application as ExpressiveApp;
use function Itertools\zip;
use function Itertools\to_array;

class Application extends ExpressiveApp
{

    /**
     *
     */
    public function __construct(
        RouterInterface $router,
        ContainerInterface $container,
        callable $finalHandler = null
    ) {

        parent::__construct($router, $container, $finalHandler);
    }

    /**
     *
     */
    public function boot(ServerRequestInterface $request, callable $startResponse)
    {
        $response = new new Response();
        $response = $this($request, $response);

        $status = sprintf(
           '%d %s',
           $response->getStatusCode(),
           $response->getReasonPhrase()
        );
        $headers = $response->getHeaders();
        $headers = zip(array_keys($headers), array_values($headers));
        // start!
        $startResponse($status, to_array($headers));
        $body = $response->getBody();
        if ($body->isSeekable()) {
            $body->rewind();
        }
        if (!$body instanceof \Traversable) {
            return [$body->getContents()];
        }

        return $body;
    }
}
