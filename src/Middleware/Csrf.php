<?php

namespace Pawon\Middleware;

use Headbanger\Set;
use Zend\Diactoros\Uri;
use Illuminate\Support\Str;
use Pawon\Cookie\CookieFactory;
use Pawon\Http\Middleware\FrameInterface;
use Pawon\Http\Middleware\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Csrf implements MiddlewareInterface
{
    const CSRF_KEY_LENGTH = 32;

    const CSRF_COOKIE_NAME = 'csrftoken';

    const CSRF_COOKIE_AGE = 31449600;

    /**
     * @var App\Cookie\CookieFactory
     */
    protected $cookiejar;

    /**
     * @var bool To indicate the csrf token used by the template
     */
    protected $csrfTokenUsed = false;

    /**
     * @var bool to signal that token should rotate/create new token
     */
    protected $shouldRotate = false;

    /**
     * @var array For setting cookie
     */
    protected $configs;

    /**
     *
     */
    public function __construct(CookieFactory $cookiejar, array $configs)
    {
        $this->cookiejar = $cookiejar;
        $this->configs = $configs;
    }

    /**
     *
     */
    protected function rejectRequest($reason)
    {
        throw new TokenMismatchException($reason);
    }

    /**
     *
     */
    public function handle(Request $request, FrameInterface $frame)
    {
        // try to get from cookie
        $cookies = $request->getCookieParams();
        if (isset($cookies[static::CSRF_COOKIE_NAME])) {
            $csrftoken = $this->sanitizeToken($cookies[static::CSRF_COOKIE_NAME]);
            $request = $request->withAttribute('CSRF_COOKIE', $csrftoken);
        } else {
            $csrftoken = '';
            // set for next
            $request = $request->withAttribute(
                'CSRF_COOKIE',
                $this->getNewCsrfToken()
            );
        }
        // only check if the request method is not "safe"
        if (!in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS', 'TRACE'])) {
            if ($request->getAttribute('DONT_ENFORCE_CSRF_CHECK', false)) {
                /*
                 * Mechanism to turn off CSRF checks for test suite.
                 */
                return $frame->next($request);
            }

            if (($uri = $request->getUri()->getScheme()) === 'https') {
                $server = $request->getServerParams();
                $referer = isset($server['HTTP_REFERER'])
                    ? $server['HTTP_REFERER']
                    : false;
                if (!$referer) {
                    $this->rejectRequest(
                        'Referer checking failed - no Referer.'
                    );
                }
                $goodReferrer = sprintf('https://$s:%s/', $uri->getHost(), $uri->getPort());
                if (!$this->sameOrigin($referer, $goodReferrer)) {
                    $reason = sprintf(
                        'Referer checking failed - %s does not match %s.',
                        $referer,
                        $goodReferrer
                    );

                    return $this->rejectRequest($reason);
                }
            }
            if (!$csrftoken) {
                return $this->rejectRequest('CSRF cookie not set.');
            }

            $requestcsrftoken = '';
            if ($request->getMethod() === 'POST') {
                $post = $request->getParsedBody();
                $requestcsrftoken = isset($post['csrfmiddlewaretoken'])
                    ? $post['csrfmiddlewaretoken']
                    : '';
            }
            if ($requestcsrftoken === '') {
                $requestcsrftoken = $request->getHeader('X-CSRFTOKEN');
            }
            if (!$this->compareCsrfToken($requestcsrftoken, $csrftoken)) {
                return $this->rejectRequest(
                    'CSRF token missing or incorrect.'
                );
            }
        }

        $request = $request
            ->withAttribute('CSRF_TOKEN_GET', [$this, 'getToken'])
            ->withAttribute('CSRF_TOKEN_ROTATE', [$this, 'rotateToken']);
        // we need to save the token
        $response = $frame->next($request);
        // not used, maybe there are no form on the template
        if (!$this->csrfTokenUsed) {
            return $response;
        }
        $response = $this->addCookieToResponse($csrftoken, $response);

        return $this->patchVaryHeader($response);
    }

    /**
     * Add cookie to response so it's easier for our frontend developer.
     */
    protected function addCookieToResponse($csrftoken, Response $response)
    {
        if ($this->shouldRotate) {
            $token = $this->getNewCsrfToken();
        } else {
            $token = $csrftoken ?: $this->getNewCsrfToken();
        }
        $cookie = $this->cookiejar->make(
            self::CSRF_COOKIE_NAME,
            $token,
            null,
            self::CSRF_COOKIE_AGE,
            $this->configs['path'],
            $this->configs['domain'],
            $this->configs['secure'],
            $this->configs['httponly']
        );

        return $response->withAddedHeader('Set-Cookie', $cookie);
    }

    /**
     *
     */
    protected function patchVaryHeader($response)
    {
        if ($response->hasHeader('Vary')) {
            $vary = $response->getHeader('Vary');
        } else {
            $vary = [];
        }

        $set = new Set(array_map('strtolower', $vary));
        $newAdded = array_filter(['cookie'], function ($item) use ($set) {
            return !$set->contains($item);
        });
        $vary = implode(', ', array_merge($vary, $newAdded));

        return $response->withHeader('Vary', $vary);
    }

    /**
     *
     */
    protected function compareCsrfToken($token1, $token2)
    {
        if (!is_string($token1) || !is_string($token2)) {
            return false;
        }

        return hash_equals($token1, $token2);
    }

    /**
     *
     */
    protected function sameOrigin($uri1, $uri2)
    {
        list($uri1, $uri2) = [new Uri($uri1), new Uri($uri2)];

        $o1 = [$uri1->getScheme(), $uri1->getHost(),
            $uri1->getPort() ?: $this->protocolToPort($uri1->getScheme($uri1->getScheme())), ];
        $o2 = [$uri2->getScheme(), $uri2->getHost(),
            $uri2->getPort() ?: $this->protocolToPort($uri1->getScheme($uri2->getScheme())), ];

        return $o1 === $o2;
    }

    /**
     *
     */
    protected function protocolToPort($protocol)
    {
        $map = [
            'http' => 80,
            'https' => 443,
        ];

        return $map[$protocol];
    }

    /**
     *
     */
    protected function getNewCsrfToken()
    {
        return Str::random(static::CSRF_KEY_LENGTH);
    }

    /**
     *
     */
    public function getToken(Request $request)
    {
        $this->csrfTokenUsed = true;

        return $request->getAttribute('CSRF_COOKIE');
    }

    /**
     *
     */
    public function rotateToken()
    {
        $this->shouldRotate = true;
        $this->csrfTokenUsed = true;
    }

    /**
     *
     */
    protected function sanitizeToken($token)
    {
        if (strlen($token) > static::CSRF_KEY_LENGTH) {
            return $this->getNewCsrfToken();
        }
        $token = preg_replace('/[^a-zA-Z0-9]+/', '', $token);
        if ($token === '') {
            return $this->getNewCsrfToken();
        }

        return $token;
    }
}
