<?php

/*
 * This file is part of vaibhavpandeyvpz/ashv package.
 *
 * (c) Vaibhav Pandey <contact@vaibhavpandey.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.md.
 */

namespace Ashv\Middleware;

use Ashv\ContainerAwareInterface;
use Ashv\ContainerAwareTrait;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class CorsVerifierMiddleware
 * @package Ashv\Middleware
 */
class CorsVerifierMiddleware implements ContainerAwareInterface, MiddlewareInterface
{
    use ContainerAwareTrait;
    use ScopedMiddlewareTrait;

    const OPT_ALLOW_CREDENTIALS = 'allow_credentials';

    const OPT_ALLOWED_HEADERS = 'allowed_headers';

    const OPT_ALLOWED_METHODS = 'allowed_methods';

    const OPT_ALLOWED_ORIGINS = 'allowed_origins';

    const OPT_EXPOSE_HEADERS = 'expose_headers';

    const OPT_MAX_AGE = 'max_age';

    /**
     * @var array
     */
    protected $options = [
        self::OPT_ALLOW_CREDENTIALS => false,
        self::OPT_ALLOWED_HEADERS => ['X-Requested-With'],
        self::OPT_ALLOWED_METHODS => ['DELETE', 'GET', 'PATCH', 'POST', 'PUT'],
        self::OPT_ALLOWED_ORIGINS => '*',
        self::OPT_EXPOSE_HEADERS => ['Content-Length'],
        self::OPT_MAX_AGE => 0,
    ];

    /**
     * CorsVerifierMiddleware constructor.
     * @param array|null $options
     */
    public function __construct(array $options = null)
    {
        $options = array_merge($this->options, (array)$options);
        $options[self::OPT_ALLOW_CREDENTIALS] = (bool)$options[self::OPT_ALLOW_CREDENTIALS];
        $options[self::OPT_ALLOWED_HEADERS] = array_map('strtolower', $options[self::OPT_ALLOWED_HEADERS]);
        $options[self::OPT_MAX_AGE] = (int)$options[self::OPT_MAX_AGE];
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        if ($this->isRequestInScope($request) && $this->isCorsRequest($request)) {
            if ($this->isPreflightRequest($request)) {
                if ($this->isValidPreFlightRequest($request)) {
                    return $this->finalizePreFlightResponse($request);
                }
                return $this->container->get('response')->withStatus(403);
            } elseif ($this->isValidRequest($request)) {
                return $this->finalizeResponse($request, $delegate->process($request));
            }
            return $this->container->get('response')->withStatus(403);
        }
        return $delegate->process($request);
    }

    // <editor-fold desc="Helpers">

    /**
     * @param RequestInterface $request
     * @return ResponseInterface
     */
    protected function finalizePreFlightResponse(RequestInterface $request)
    {
        /** @var ResponseInterface $response */
        $response = $this->container->get('response');
        if ($this->options[self::OPT_ALLOW_CREDENTIALS]) {
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        }
        if (count($this->options[self::OPT_ALLOWED_HEADERS])) {
            $response = $response->withHeader('Access-Control-Allow-Headers', $this->options[self::OPT_ALLOWED_HEADERS]);
        }
        $response = $response->withHeader('Access-Control-Allow-Methods', $this->options[self::OPT_ALLOWED_METHODS])
            ->withHeader('Access-Control-Allow-Origin', $request->getHeaderLine('Origin'));
        if ($this->options[self::OPT_MAX_AGE] > 0) {
            $response = $response->withHeader('Access-Control-Max-Age', (string)$this->options[self::OPT_MAX_AGE]);
        }
        return $response;
    }

    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    protected function finalizeResponse(RequestInterface $request, ResponseInterface $response)
    {
        if ($this->options[self::OPT_ALLOW_CREDENTIALS]) {
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        }
        if (count($this->options[self::OPT_EXPOSE_HEADERS])) {
            $response = $response->withHeader('Access-Control-Expose-Headers', $this->options[self::OPT_EXPOSE_HEADERS]);
        }
        $response = $response->withHeader('Access-Control-Allow-Origin', $request->getHeaderLine('Origin'));
        if ($response->hasHeader('Vary')) {
            $response = $response->withAddedHeader('Vary', 'Origin');
        } else {
            $response = $response->withHeader('Vary', 'Origin');
        }
        return $response;
    }

    /**
     * @param RequestInterface $request
     * @return bool
     */
    protected function isCorsRequest(RequestInterface $request)
    {
        return $request->hasHeader('Origin');
    }

    /**
     * @param RequestInterface $request
     * @return bool
     */
    protected function isHavingValidHeaders(RequestInterface $request)
    {
        $headers = $request->getHeader('Access-Control-Request-Headers');
        foreach ($headers as $header) {
            if (!in_array(strtolower($header), $this->options[self::OPT_ALLOWED_HEADERS])) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param RequestInterface $request
     * @return bool
     */
    protected function isHavingValidMethod(RequestInterface $request)
    {
        return in_array(
            $request->getHeaderLine('Access-Control-Request-Method'),
            $this->options[self::OPT_ALLOWED_METHODS]
        );
    }

    /**
     * @param RequestInterface $request
     * @return bool
     */
    protected function isHavingValidOrigin(RequestInterface $request)
    {
        if ($this->options[self::OPT_ALLOWED_ORIGINS] !== '*') {
            return in_array($request->getHeaderLine('Origin'), $this->options[self::OPT_ALLOWED_ORIGINS]);
        }
        return true;
    }

    /**
     * @param RequestInterface $request
     * @return bool
     */
    protected function isPreFlightRequest(RequestInterface $request)
    {
        return $this->isCorsRequest($request)
            && $request->hasHeader('Access-Control-Request-Method')
            && ($request->getMethod() === 'OPTIONS');
    }

    /**
     * @param RequestInterface $request
     * @return bool
     */
    protected function isValidPreFlightRequest(RequestInterface $request)
    {
        return $this->isHavingValidOrigin($request)
            && $this->isHavingValidMethod($request)
            && $this->isHavingValidHeaders($request);
    }

    /**
     * @param RequestInterface $request
     * @return bool
     */
    protected function isValidRequest(RequestInterface $request)
    {
        return $this->isHavingValidOrigin($request);
    }

    // </editor-fold>
}
