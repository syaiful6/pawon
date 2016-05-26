<?php

namespace Pawon\Auth\Access;

use Pawon\Http\Middleware\FrameInterface;
use Psr\Http\Message\ServerRequestInterface as Request;

trait AccessTrait
{
    /**
     * handle denied request. If shouldPipeToError return truthy then it will
     * call next callback with instance of PermissionDenied. It will give the error
     * handler do whatever they want. If it return falsely then we will redirect
     * them returned by getNoPermissionRedirectPath
     *
     */
    protected function handleNoPermission(Request $request, FrameInterface $frame)
    {
        if ($this->shouldPipeToError()) {
            throw new PermissionDenied($this->getPermissionDeniedMessage());
        }

        $flash = $request->getAttribute('_messages');
        if (method_exists($flash, 'warning')) {
            $flash->warning($this->getPermissionDeniedMessage());
        }

        return $frame->getResponseFactory()->make('', 302, [
            'location', $this->getNoPermissionRedirectPath($request)
        ]);
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
