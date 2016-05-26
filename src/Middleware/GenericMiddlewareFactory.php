<?php

namespace Pawon\Middleware;

use Pawon\Cookie;
use Interop\Container\ContainerInterface;
use Zend\Expressive\Template\TemplateRendererInterface;

class GenericMiddlewareFactory
{
    /**
     *
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ) {
        $name = str_replace(__NAMESPACE__, '', $requestedName);
        if ($name[0] === '\\') {
            $name = substr($name, 1);
        }
        if (method_exists($this, "create$name")) {
            return call_user_func([$this, "create$name"], $container);
        } else {
            throw new \RuntimeException("can\'t create $requestedName");
        }
    }

    /**
     *
     */
    protected function createErrorHandler($container)
    {
        $config = $container->has('config') ? $container->get('config') : [];
        $error = new ErrorHandler(
            $container->get(TemplateRendererInterface::class),
            $debug = $config['debug']
        );
        if ($debug) {
            $error->setDebuggingHandler(
                $container->get('Pawon\Core\Whoops'),
                $container->get('Pawon\Core\WhoopsPageHandler')
            );
        }

        return $error;
    }

    /**
     *
     */
    private function createCsrf($container)
    {
        $config = $container->has('config') ? $container->get('config') : [];
        $setting = isset($config['session']) ? $config['session'] : [];
        if ($container->has(Cookie\CookieFactory::class)) {
            $cookieJar = $container->get(Container\CookieFactory::class);
        } elseif ($container->has(Cookie\QueueingCookieFactory::class)) {
            $cookieJar = $container->get(Cookie\QueueingCookieFactory::class);
        } else {
            throw new \RuntimeException(
                'cant create csrf middleware! no cookiejar on container'
            );
        }

        return new Csrf($cookieJar, $setting);
    }

    /**
     *
     */
    private function createContextProcessor($container)
    {
        $config = $container->has('config') ? $container->get('config') : [];
        $setting = isset($config['templates']) ? $config['templates'] : [];
        $processors = isset($setting['context_processors']) ? $setting['context_processors'] : [];
        // map the processors, so ContextProcessor can consume it. It expect array
        // of callable. Whereas the context_processors config maybe a service available
        // on container. So fetch it.
        $processors = array_map(function ($processor) use ($container) {

            if (is_callable($processor)) {
                return $processor;
            }
            // otherwise create this service
            return $container->get($processor);
        }, $processors);
        if ($container->has(TemplateRendererInterface::class)) {
            $renderer = $container->get(TemplateRendererInterface::class);
            return new ContextProcessor($renderer, $processors);
        }
        throw new \RuntimeException(sprintf(
            'cant create ContextProcessor, %s service not available on container',
            TemplateRendererInterface::class
        ));
    }
}
