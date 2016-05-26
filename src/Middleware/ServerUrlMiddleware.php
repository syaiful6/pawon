<?php

namespace Pawon\Middleware;

use Zend\Expressive\Helper\ServerUrlHelper;
use Pawon\Http\Middleware\FrameInterface;
use Pawon\Http\Middleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface as Request;

class ServerUrlMiddleware implements MiddlewareInterface
{
	/**
     * @var Zend\Expressive\Helper\ServerUrlHelper
     */
    private $helper;

    /**
     * @param Zend\Expressive\Helper\ServerUrlHelper $helper
     */
    public function __construct(ServerUrlHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
	 *
	 */
	public function handle(Request $request, FrameInterface $frame)
	{
		$this->helper->setUri($request->getUri());
        return $frame->next($request, $response);
	}
}
