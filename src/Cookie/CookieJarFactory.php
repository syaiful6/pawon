<?php

namespace Pawon\Cookie;

use Interop\Container\ContainerInterface;

class CookieJarFactory
{
    /**
     *
     */
    public function __invoke(ContainerInterface $container)
    {
        $cookiejar = new CookieJar();
        if ($container->has('config')) {
            $config = $container->get('config');
            $session = $config['session'];
            if (isset($session['path']) && isset($session['domain'])) {
                $cookiejar->setDefaultCookieTails(
                    $session['path'],
                    $session['domain'],
                    $session['secure'],
                    $session['httponly']
                );
            }
        }
        return $cookiejar;
    }
}
