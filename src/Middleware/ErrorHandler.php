<?php

namespace Pawon\Middleware;

use Zend\Diactoros\Stream;
use Pawon\Functional\Singledispatch;
use Zend\Expressive\Template\TemplateRendererInterface as Template;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Zend\Diactoros\Response\HtmlResponse;

class ErrorHandler
{
    protected $hander;

    protected $template;

    /**
     *
     */
    public function __construct(Template $template)
    {
        $this->template = $template;
        $this->handler = new Singledispatch([$this, 'defaultHandler']);
    }
    /**
     *
     */
    public function __invoke($error, Request $request, Response $response, callable $next = null)
    {
        return call_user_func($this->handler, $error, $request, $response, $next);
    }

    /**
     *
     */
    public function register($cls, $func = null)
    {
        return $this->handler->register($cls, $func);
    }

    /**
     *
     */
    public function defaultHandler($error, Request $request, Response $response, callable $next = null)
    {
        if ($error instanceof TokenMismatchException) {
            $html = $this->template->render('error::csrf-403', [
                'title' => 'Forbidden',
                'main'  => 'CSRF verification failed. Request aborted.'
            ]);
            return new HtmlResponse($html, 403);
        }

        return $next($request, $response, $error);
    }
}
