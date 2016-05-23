<?php

namespace Pawon\Auth\Access;

use Pawon\Auth\Exceptions\PermissionDenied;
use Zend\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

trait AccessTrait
{
    /**
     * handle denied request. If shouldPipeToError return truthy then it will
     * call next callback with instance of PermissionDenied. It will give the error
     * handler do whatever they want. If it return falsely then we will redirect
     * them returned by getNoPermissionRedirectPath
     *
     * @param Psr\Http\Message\ServerRequestInterface $request
     * @param Psr\Http\Message\ResponseInterface $response
     * @param callable $next
     */
    protected function handleNoPermission(
        Request $request,
        Response $response,
        callable $next
    ) {
        if ($this->shouldPipeToError()) {
            $error = new PermissionDenied($this->getPermissionDeniedMessage());

            return $next($request, $response, $error);
        }

        $flash = $request->getAttribute('_messages');
        if (method_exists($flash, 'warning')) {
            $flash->warning($this->getPermissionDeniedMessage());
        }
        return new RedirectResponse($this->getNoPermissionRedirectPath($request));
    }

    /**
     * Give the user an helpfull message here
     *
     * @return string
     */
    protected function getPermissionDeniedMessage()
    {
        return property_exists($this, 'permissionDeniedMessage')
        ? $this->permissionDeniedMessage
        : 'You did not have permission to do that';
    }

    /**
     * Determine whether the request should pipe to error middleware
     *
     * @return boolean
     */
    protected function shouldPipeToError()
    {
        return property_exists($this, 'pipeToError')
        ? $this->pipeToError
        : false;
    }

    /**
     * If shouldPipeToError return falsely, then we will response the request
     * with redirect response. So here you can specify the redirect path
     *
     * @param Psr\Http\Message\ServerRequestInterface $request
     * @return string
     */
    protected function getNoPermissionRedirectPath(Request $request)
    {
        return property_exists($this, 'redirectPath')
            ? $this->redirectPath
            : '/';
    }
}
