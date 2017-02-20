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

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Sandesh\Cookie;

/**
 * Class SessionCookieMiddleware
 * @package Ashv\Middleware
 */
class SessionCookieMiddleware implements MiddlewareInterface, \SessionHandlerInterface
{
    use ScopedMiddlewareTrait;

    const OPT_DOMAIN = 'domain';

    const OPT_EXPIRY = 'expiry';

    const OPT_HTTP_ONLY = 'http_only';

    const OPT_NAME = 'name';

    const OPT_PATH = 'path';

    const OPT_SECURE = 'secure';

    /**
     * @var array
     */
    protected $options = [
        self::OPT_DOMAIN => null,
        self::OPT_HTTP_ONLY => true,
        self::OPT_NAME => 'PHPSESS',
        self::OPT_PATH => '/',
        self::OPT_SECURE => false,
    ];

    /**
     * SessionCookieMiddleware constructor.
     * @param array|null $options
     */
    public function __construct(array $options = null)
    {
        $options = array_merge($this->options, (array)$options);
        if (empty($options[self::OPT_EXPIRY])) {
            throw new \InvalidArgumentException(sprintf(
                "Option '%s' is required and must not be empty", self::OPT_EXPIRY
            ));
        }
        $this->options = $options;
    }

    /**
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    protected function finalize(ResponseInterface $response)
    {
        $cookie = (new Cookie($this->options[self::OPT_NAME]))
            ->withDomain($this->options[self::OPT_DOMAIN])
            ->withExpiry($this->options[self::OPT_EXPIRY])
            ->withHttpOnly($this->options[self::OPT_HTTP_ONLY])
            ->withPath($this->options[self::OPT_PATH])
            ->withSecure($this->options[self::OPT_SECURE])
            ->withValue(json_encode($_SESSION));
        $cookie = (string)$cookie;
        if ($response->hasHeader('Set-Cookie')) {
            $replaced = false;
            $cookies = $response->getHeader('Set-Cookie');
            foreach ($cookies as $i => $preset) {
                if (strpos($preset, $this->options[self::OPT_NAME] . '=') === 0) {
                    $cookies[$i] = $cookie;
                    $replaced = true;
                    break;
                }
            }
            if (!$replaced) {
                $cookies[] = $cookie;
            }
            return $response->withHeader('Set-Cookie', $cookies);
        }
        return $response->withHeader('Set-Cookie', $cookie);
    }

    /**
     * @param ServerRequestInterface $request
     */
    protected function initialize(ServerRequestInterface $request)
    {
        ini_set('session.use_cookies', 0);
        session_cache_limiter(false);
        session_set_save_handler($this);
        if (session_id() == '') {
            session_start();
        }
        $cookies = $request->getCookieParams();
        if (isset($cookies[$this->options[self::OPT_NAME]])) {
            $session = json_decode(urldecode($cookies[$this->options[self::OPT_NAME]]), true);
            if (is_array($session)) {
                array_walk($session, function ($value, $key) {
                    $_SESSION[$key] = $value;
                });
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        if ($this->isRequestInScope($request)) {
            $this->initialize($request);
            $response = $delegate->process($request);
            return $this->finalize($response);
        }
        return $delegate->process($request);
    }

    // <editor-fold desc="Dummy">

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function destroy($session_id)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function gc($maxlifetime)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function open($save_path, $name)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function read($session_id)
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function write($session_id, $session_data)
    {
        return true;
    }

    // </editor-fold>
}
