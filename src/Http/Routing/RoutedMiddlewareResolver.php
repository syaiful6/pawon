<?php

namespace Pawon\Http\Routing;

interface RoutedMiddlewareResolver
{
	/**
	 *
	 */
	public function resolve($middleware);
}