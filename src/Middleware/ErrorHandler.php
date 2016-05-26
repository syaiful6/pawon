<?php

namespace Pawon\Middleware;

use Exception;
use Whoops\Run as Whoops;
use Whoops\Handler\PrettyPageHandler;
use Pawon\Functional\Singledispatch;
use Pawon\Http\Middleware\FrameInterface;
use Pawon\Http\Middleware\MiddlewareInterface;
use Pawon\Http\Exceptions\HttpExceptionInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Expressive\Template\TemplateRendererInterface as Template;

class ErrorHandler implements MiddlewareInterface
{
    protected $hander;

    protected $template;

    protected $debug;

    protected $whoopsHandler;

    protected $whoops;

    /**
     *
     */
    public function __construct(Template $template, $debug = false)
    {
        $this->template = $template;
        $this->debug = $debug;
        $this->handler = new Singledispatch([$this, 'defaultHandler']);
        $this->register(TokenMismatchException::class, [$this, 'handleTokenMistMatch']);
        $this->register(HttpExceptionInterface::class, [$this, 'handleHttpException']);
    }
    /**
     *
     */
    public function handle(Request $request, FrameInterface $frame)
    {
        try {
            return $frame->next($request);
        } catch (Exception $e) {
            return call_user_func($this->handler, $e, $request, $frame);
        }
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
    public function handleTokenMistMatch(
        Exception $error,
        Request $request,
        FrameInterface $frame
    ) {
        $html = $this->template->render('error::csrf-403', [
            'title' => 'Forbidden',
            'main'  => 'CSRF verification failed. Request aborted.'
        ]);
        return $frame->getResponseFactory()->make($html, 403);
    }

    /**
     *
     */
    public function handleHttpException(
        Exception $error,
        Request $request,
        FrameInterface $frame
    ) {
        $status = $error->getStatusCode();
        $html = $this->template->render('error::'.$status, compact('error', 'request'));
        return $frame->getResponseFactory()->make($html, $status, $error->getHeaders());
    }

    /**
     *
     */
    public function setDebuggingHandler(Whoops $whoops, PrettyPageHandler $whoopsHandler)
    {
        $this->whoops = $whoops;
        $this->whoopsHandler = $whoopsHandler;
    }

    /**
     *
     */
    public function defaultHandler(
        Exception $error,
        Request $request,
        FrameInterface $frame
    ) {
        if (!$this->debug) {
            $html = $this->template->render('error::500', compact('error', 'request'));
            return $frame->getResponseFactory->make($html, 500);
        }

        $this->prepareWhoopsHandler($request);
        $this->whoops->pushHandler($this->whoopsHandler);

        return $frame->getResponseFactory()->make($this->whoops->handleException($error), 500);
    }

    /**
     * Prepare the Whoops page handler with a table displaying request information
     *
     * @param Request $request
     */
    private function prepareWhoopsHandler(Request $request)
    {
        $uri = $request->getUri();
        $this->whoopsHandler->addDataTable('Pawon Application Request', [
            'HTTP Method'            => $request->getMethod(),
            'URI'                    => (string) $uri,
            'Script'                 => $request->getServerParams()['SCRIPT_NAME'],
            'Headers'                => $request->getHeaders(),
            'Cookies'                => $request->getCookieParams(),
            'Attributes'             => $request->getAttributes(),
            'Query String Arguments' => $request->getQueryParams(),
            'Body Params'            => $request->getParsedBody(),
        ]);
    }
}
