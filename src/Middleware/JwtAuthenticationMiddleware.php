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
use Ashv\Exception\BadRequestException;
use Ashv\Exception\UnauthorizedException;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Jweety\EncoderInterface;
use Jweety\ExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class JwtAuthenticationMiddleware
 * @package Ashv\Middleware
 */
class JwtAuthenticationMiddleware implements ContainerAwareInterface, MiddlewareInterface
{
    use ContainerAwareTrait;
    use ScopedMiddlewareTrait;

    const ATTR_JWT = 'jwt';

    const HEADER_NAME = 'Authorization';

    const TOKEN_REGEX = '~^Bearer\s+(?<jwt>.*)$~i';

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        if ($this->isRequestInScope($request)) {
            $header = $request->getHeaderLine(self::HEADER_NAME);
            if (!$header || !preg_match(self::TOKEN_REGEX, $header, $matches)) {
                throw new BadRequestException(sprintf("Request does not have a valid '%s' header", self::HEADER_NAME));
            }
            /** @var EncoderInterface $encoder */
            $encoder = $this->container->get('jwt_encoder');
            try {
                $jwt = $encoder->decode($matches['jwt']);
                $request = $request->withAttribute(self::ATTR_JWT, $jwt);
            } catch (ExceptionInterface $e) {
                throw new UnauthorizedException('Unable to decode JWT token in request headers', 0, $e);
            }
        }
        return $delegate->process($request);
    }
}
