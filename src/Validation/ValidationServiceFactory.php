<?php

namespace Pawon\Validation;

use Interop\Container\ContainerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Contracts\Validation\Factory as FactoryContract;

class ValidationServiceFactory
{
    /**
     *
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ) {
        if ($requestedName === FactoryContract::class) {
            return $this->createValidationFactory($container);
        } elseif ($requestedName === PresenceVerifierInterface::class) {
            return $this->createPresenceVerifier($container);
        } else {
            throw new \RuntimeException("can\'t create $requestedName");
        }
    }

    /**
     *
     */
    protected function createValidationFactory(ContainerInterface $container)
    {
        if ($container->has(TranslatorInterface::class)) {
            $translator = $container->get(TranslatorInterface::class);

            $factory = new Factory($translator, $container);

            if ($container->has(PresenceVerifierInterface::class)) {
                $presence = $container->get(PresenceVerifierInterface::class);
                $factory->setPresenceVerifier($presence);
            }
            return $factory;
        }

        throw new \RuntimeException('no translation service');
    }

    /**
     *
     */
    protected function createPresenceVerifier(ContainerInterface $container)
    {
        if ($container->has(ConnectionResolverInterface::class)) {
            return new DatabasePresenceVerifier(
                $container->get(ConnectionResolverInterface::class)
            );
        }

        throw new \RuntimeException(sprintf(
            'cant create %s without database connection service',
            PresenceVerifierInterface::class
        ));
    }
}
