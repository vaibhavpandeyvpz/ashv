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
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class ClientIpMiddleware
 * @package Ashv\Middleware
 */
class ClientIpMiddleware implements MiddlewareInterface
{
    const ATTR_CLIENT_IP = 'client_ip';

    /**
     * @var array
     */
    protected $headers = [
        'Forwarded',
        'Forwarded-For',
        'X-Forwarded-For',
        'X-Forwarded',
        'X-Cluster-Client-Ip',
        'Client-Ip',
    ];

    /**
     * @param ServerRequestInterface $request
     * @return string|null
     */
    protected function getIpFromRequest(ServerRequestInterface $request)
    {
        if ($request->hasHeader('X-Real-IP') && self::isValidIpAddress($ip = $request->getHeaderLine('X-Real-IP'))) {
            return $ip;
        }
        $server = $request->getServerParams();
        if (isset($server['REMOTE_ADDR']) && self::isValidIpAddress($ip = $server['REMOTE_ADDR'])) {
            return $ip;
        }
        foreach ($this->headers as $name) {
            if ($request->hasHeader($name)) {
                $ips = $request->getHeader($name);
                if (self::isValidIpAddress($ip = trim($ips[0]))) {
                    return $ip;
                }
            }
        }
    }

    /**
     * @param string $ip
     * @return bool
     */
    protected static function isValidIpAddress($ip)
    {
        return false !== filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6);
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $ip = $this->getIpFromRequest($request);
        $request = $request->withAttribute(self::ATTR_CLIENT_IP, $ip);
        return $delegate->process($request);
    }
}
