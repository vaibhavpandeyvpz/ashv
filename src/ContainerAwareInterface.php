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
 * Interface ContainerAwareInterface
 * @package Ashv
 */
interface ContainerAwareInterface
{
    /**
     * @param ContainerInterface $container
     * @return static
     */
    public function withContainer(ContainerInterface $container);
}
