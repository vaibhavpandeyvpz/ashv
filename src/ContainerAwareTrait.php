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

use Interop\Container\ContainerInterface;

/**
 * Class ContainerAwareTrait
 * @package Ashv
 */
trait ContainerAwareTrait
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param ContainerInterface $container
     * @return static
     */
    public function withContainer(ContainerInterface $container)
    {
        $clone = clone $this;
        $clone->container = $container;
        return $clone;
    }
}
