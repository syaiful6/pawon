<?php

namespace Pawon\Auth\Password;

use Illuminate\Support\Arr;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Database\ConnectionInterface;
use Interop\Container\ContainerInterface as Container;
use Illuminate\Contracts\Auth\PasswordBroker as PasswordBrokerContract;

class PasswordResetServiceFactory
{
    /**
     *
     */
    public function __invoke(Container $container, $requestedName, array $options = null)
    {
        $name = str_replace(__NAMESPACE__, '', $requestedName);
        if ($name[0] === '\\') {
            $name = substr($name, 1);
        }
        $name = str_replace('Interface', '', $name);

        if (method_exists($this, "create$name")) {
            return call_user_func([$this, "create$name"], $container);
        } elseif ($requestedName === PasswordBrokerContract::class) {
            return $this->createPasswordBroker($container);
        } else {
            throw new \RuntimeException("can\'t create $requestedName");
        }
    }

    /**
     *
     */
    public function createPasswordBroker(Container $container)
    {
        $mailer = $container->get(Mailer::class);
        $repository = $container->get(TokenRepositoryInterface::class);
        $configs = $container->get('config');
        $userModel = Arr::get($configs, 'auth.model');
        $template = Arr::get($configs, 'auth.passwords.user.template');

        return new PasswordBroker($repository, $mailer, $userModel, $template);
    }

    /**
     *
     */
    protected function createTokenRepository(Container $container)
    {
        $db = $container->get(ConnectionInterface::class);
        $configs = $container->get('config');
        $key = $configs['key'];
        $table = Arr::get($configs, 'auth.passwords.user.table');
        $expire = Arr::get($configs, 'auth.passwords.user.expire');

        return new DatabaseTokenRepository($db, $table, $key, $expire);
    }
}
