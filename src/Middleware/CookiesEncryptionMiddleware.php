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
use Psr\Http\Message\ServerRequestInterface;
use Sandesh\CookieFactory;

/**
 * Class CookiesEncryptionMiddleware
 * @package Ashv\Middleware
 */
class CookiesEncryptionMiddleware implements ContainerAwareInterface, MiddlewareInterface
{
    use ContainerAwareTrait;

    /**
     * @var string
     */
    protected $cipher;

    /**
     * @var string
     */
    protected $key;

    /**
     * CookiesEncryptionMiddleware constructor.
     * @param string $key
     * @param string $cipher
     */
    public function __construct($key, $cipher = 'AES-128-CBC')
    {
        $this->key = $key;
        $this->cipher = $cipher;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $cookies = $request->getCookieParams();
        foreach ($cookies as $key => $value) {
            if ($decrypted = self::decrypt($value, $this->key, $this->cipher)) {
                $cookies[$key] = $decrypted;
            }
        }
        $response = $delegate->process($request->withCookieParams($cookies));
        if ($response->hasHeader('Set-Cookie')) {
            $cookies = $response->getHeader('Set-Cookie');
            $factory = $this->container->has('cookie_factory')
                ? $this->container->get('cookie_factory')
                : new CookieFactory();
            foreach ($cookies as $i => $cookie) {
                $cookie = $factory->createCookie($cookie);
                if ($encrypted = self::encrypt($cookie->getValue(), $this->key, $this->cipher)) {
                    $cookies[$i] = (string)$cookie->withValue($encrypted);
                }
            }
            return $response->withoutHeader('Set-Cookie')
                ->withHeader('Set-Cookie', $cookies);
        }
        return $response;
    }

    // <editor-fold desc="Helpers">

    /**
     * @param string $payload
     * @return string
     */
    protected static function decode($payload)
    {
        $remainder = strlen($payload) % 4;
        if ($remainder) {
            $padding = 4 - $remainder;
            $payload .= str_repeat('=', $padding);
        }
        return base64_decode(strtr($payload, '-_', '+/'));
    }

    /**
     * @param string $payload
     * @param string $key
     * @param string $cipher
     * @return string
     */
    protected static function decrypt($payload, $key, $cipher)
    {
        $parts = explode('.', $payload);
        if (count($parts) === 2) {
            return openssl_decrypt(self::decode($parts[0]), $cipher, $key, OPENSSL_RAW_DATA, self::decode($parts[1]));
        }
        return false;
    }

    /**
     * @param string $payload
     * @return string
     */
    protected static function encode($payload)
    {
        return str_replace('=', '', strtr(base64_encode($payload), '+/', '-_'));
    }

    /**
     * @param string $payload
     * @param string $key
     * @param string $cipher
     * @return string|bool
     */
    protected static function encrypt($payload, $key, $cipher)
    {
        $size = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($size);
        if ($enc = openssl_encrypt($payload, $cipher, $key, OPENSSL_RAW_DATA, $iv)) {
            return sprintf('%s.%s', self::encode($enc), self::encode($iv));
        }
        return false;
    }

    // </editor-fold>
}
