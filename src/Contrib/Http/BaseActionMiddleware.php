<?php

namespace Pawon\Contrib\Http;

use League\Tactician\CommandBus;
use Pawon\Http\Middleware\MiddlewareInterface;
use Pawon\Contrib\Bus\CommandBusAwareInterface;
use Pawon\Validation\ValidatorFactoryAwareInterface;
use Zend\Expressive\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Contracts\Validation\Factory as FactoryContract;

abstract class BaseActionMiddleware implements
    MiddlewareInterface,
    ValidatorFactoryAwareInterface,
    CommandBusAwareInterface
{
    use DispatchMethod, ValidateRequest;

    protected $validationFactory;

    /**
     *
     */
    protected $renderer;

    /**
     *
     */
    protected $commandBus;

    /**
     *
     */
    public function setValidatorFactory(FactoryContract $factory)
    {
        $this->validationFactory = $factory;
    }

    /**
     *
     */
    public function setCommandBus(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    /**
     *
     */
    public function getCommandBus()
    {
        return $this->commandBus;
    }

    /**
     *
     */
    public function setTemplateRenderer(TemplateRendererInterface $renderer)
    {
        $this->renderer = $renderer;
    }
}
