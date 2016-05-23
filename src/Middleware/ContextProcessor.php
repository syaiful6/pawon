<?php

namespace Pawon\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Zend\Expressive\Template\TemplateRendererInterface as Renderer;
use function Itertools\iter;

class ContextProcessor
{
    /**
     * an array of callable
     *
     * @var callable[]
     */
    protected $processors;

    /**
     * the template renderer
     *
     * @var \Zend\Expressive\Template\TemplateRendererInterface
     */
    protected $renderer;

    /**
     * @param \Zend\Expressive\Template\TemplateRendererInterface $renderer
     * @param callable[]
     */
    public function __construct(Renderer $renderer, array $processors = [])
    {
        $this->processors = $processors;
        $this->renderer = $renderer;
    }

    /**
     *
     */
    public function __invoke(Request $request, Response $response, callable $next = null)
    {
        $template = $this->renderer;
        // we will spin through all processor an add them to template
        foreach ($this->processors as $processor) {
            if (!is_callable($processor)) {
                throw new \RuntimeException(sprintf(
                    '%s template context processor not callable.',
                    is_object($processor) ? get_class($processor) : $processor
                ));
            }
            $context = $this->getIterableContext($processor, $request);
            foreach ($context as $k => $v) {
                $template->addDefaultParam(Renderer::TEMPLATE_ALL, $k, $v);
            }
        }
        return $next($request, $response);
    }

    /**
     *
     */
    protected function getIterableContext($processor, $request)
    {
        try {
            return iter($processor($request));
        } catch (\InvalidArgumentException $e) {
            throw new \RuntimeException(
                sprintf(
                    '%s processor return invalid context. Must return array or traversable',
                    is_object($processor) ? get_class($processor) : $processor
                ),
                500,
                $e
            );
        }
    }
}
