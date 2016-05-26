<?php

namespace Pawon\Middleware;

use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Router\RouteResult;
use Pawon\Http\Middleware\FrameInterface;
use Pawon\Http\Middleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface as Request;

class UrlHelperMiddleware implements MiddlewareInterface
{
	/**
	 * @var Zend\Expressive\Helper\UrlHelper
	 */
	private $helper;

	/**
	 *
	 */
	public function __construct(UrlHelper $helper)
	{
		$this->helper = $helper;
	}

	/**
	 *
	 */
	public function handle(Request $request, FrameInterface $frame)
	{
		$result = $request->getAttribute(RouteResult::class, false);

        if ($result instanceof RouteResult) {
            $this->helper->setRouteResult($result);
        }

        return $frame->next($request);
	}
}
