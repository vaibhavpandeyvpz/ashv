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
use Katora\Container;
use Tez\RouteCollectionInterface;

/**
 * Interface ModuleInterface
 * @package Ashv
 */
interface ModuleInterface
{
    /**
     * @param Container|ContainerInterface $container
     */
    public function configure($container);

    /**
     * @param MiddlewarePipelineInterface $pipeline
     * @param ContainerInterface $container
     */
    public function enqueue(MiddlewarePipelineInterface $pipeline, ContainerInterface $container);

    /**
     * @param RouteCollectionInterface $collection
     */
    public function route(RouteCollectionInterface $collection);
}
