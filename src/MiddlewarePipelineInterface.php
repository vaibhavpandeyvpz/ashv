<?php

/*
 * This file is part of vaibhavpandeyvpz/ashv package.
 *
 * (c) Vaibhav Pandey <contact@vaibhavpandey.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.md.
 */

namespace Ashv;

use Interop\Http\ServerMiddleware\MiddlewareInterface;

/**
 * Interface MiddlewarePipelineInterface
 * @package Ashv
 */
interface MiddlewarePipelineInterface
{
    /**
     * @param MiddlewareInterface|callable $middleware
     * @return static
     */
    public function before($middleware);
}
