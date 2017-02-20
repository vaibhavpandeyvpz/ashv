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

use Ashv\Exception\ForbiddenException;
use Ashv\Exception\UnauthorizedException;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Jweety\JwtInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class JwtAccessControlMiddleware
 * @package Ashv\Middleware
 */
class JwtAccessControlMiddleware implements MiddlewareInterface
{
    use ScopedMiddlewareTrait;

    /**
     * @var array
     */
    protected $rules = [];

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        if ($this->isRequestInScope($request)) {
            /** @var JwtInterface $jwt */
            $jwt = $request->getAttribute(JwtAuthenticationMiddleware::ATTR_JWT);
            if (is_null($jwt)) {
                throw new UnauthorizedException();
            }
            $path = $request->getUri()->getPath();
            foreach ($this->rules as $rule) {
                extract($rule);
                /**
                 * @var string $pattern
                 * @var string $claim
                 * @var array $values
                 */
                if (preg_match("~^{$pattern}$~", $path)) {
                    if (!$jwt->hasClaim($claim)) {
                        throw new ForbiddenException("JWT does not have '{$claim}' claim");
                    }
                    if (!in_array($value = $jwt->getClaim($claim), $values)) {
                        throw new ForbiddenException(sprintf(
                            "Rule (%s) for '%s' requires '%s' claim to be one of %s; %s given",
                            $pattern, $path, $claim, implode(', ', $values), $value
                        ));
                    }
                }
            }
        }
        return $delegate->process($request);
    }

    /**
     * @param string $pattern
     * @param string $claim
     * @param string|array $values
     * @return static
     */
    public function withAccessRule($pattern, $claim, $values)
    {
        $values = (array)$values;
        $this->rules[] = compact('pattern', 'claim', 'values');
        return $this;
    }
}
