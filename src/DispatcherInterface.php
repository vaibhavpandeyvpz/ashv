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

use Psr\Http\Message\ResponseInterface;

/**
 * Interface DispatcherInterface
 * @package Ashv
 */
interface DispatcherInterface
{
    /**
     * @param mixed $callback
     * @param array $params
     * @return ResponseInterface
     */
    public function dispatch($callback, array $params);
}
