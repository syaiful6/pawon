<?php

namespace Pawon\Contrib;

use League\Tactician\CommandBus;
use Interop\Container\ContainerInterface;
use Pawon\Contrib\Bus\CommandBusAwareInterface;
use Pawon\Contrib\Http\BaseActionMiddleware;
use Pawon\Validation\ValidatorFactoryAwareInterface;
use Zend\ServiceManager\Initializer\InitializerInterface;
use Zend\Expressive\Template\TemplateRendererInterface as Template;
use Illuminate\Contracts\Validation\Factory as FactoryContract;

class GenericFactoryInitializer
{
    public function __invoke(ContainerInterface $container, $instance)
    {
        if ($instance instanceof ValidatorFactoryAwareInterface) {
            $instance->setValidatorFactory($container->get(FactoryContract::class));
        }

        if ($instance instanceof CommandBusAwareInterface) {
            $instance->setCommandBus($container->get(CommandBus::class));
        }

        if ($instance instanceof BaseActionMiddleware) {
            $instance->setTemplateRenderer($container->get(Template::class));
        }
    }
}
