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

use Psr\Http\Message\ServerRequestInterface;

/**
 * Class ScopedMiddlewareTrait
 * @package Ashv\Middleware
 */
trait ScopedMiddlewareTrait
{
    /**
     * @var array
     */
    protected $methods;

    /**
     * @var array
     */
    protected $paths = [];

    /**
     * @param ServerRequestInterface $request
     * @return bool
     */
    protected function isRequestInScope(ServerRequestInterface $request)
    {
        if (empty($this->methods) || in_array($request->getMethod(), $this->methods)) {
            if (empty($this->paths)) {
                return true;
            }
            $path = $request->getUri()->getPath();
            foreach ($this->paths as $data) {
                if ($data['regex'] && preg_match("~^{$data['path']}$~", $path)) {
                    return true;
                } elseif (!$data['regex'] && ($path === $data['path'])) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param string|array $methods
     * @return static
     */
    public function whenMethodEquals($methods)
    {
        $this->methods = (array)$methods;
        return $this;
    }

    /**
     * @param string|array $paths
     * @param bool $regex
     * @return static
     */
    public function whenPathEquals($paths, $regex = false)
    {
        $paths = (array)$paths;
        foreach ($paths as $path) {
            $this->paths[] = compact('path', 'regex');
        }
        return $this;
    }
}
