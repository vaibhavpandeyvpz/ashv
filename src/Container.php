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

use Katora\Container as Katora;
use Sandesh\ResponseFactory;
use Sandesh\ResponseSender;
use Tez\PathCompiler;

/**
 * Class Container
 * @package Ashv
 */
class Container extends Katora
{
    /**
     * Container constructor.
     * @param array $values
     */
    public function __construct(array $values = [])
    {
        $this['dispatcher'] = $this->share(function ($container) {
            return (new Dispatcher())
                ->withContainer($container);
        });
        $this['path_compiler'] = $this->share(function () {
            return new PathCompiler();
        });
        $this['response'] = function () {
            return (new ResponseFactory())
                ->createResponse();
        };
        $this['response_sender'] = $this->share(function () {
            return new ResponseSender();
        });
        $this['server_request'] = $this->share(function () {
            return (new ServerRequestFactory())
                ->createServerRequest($_SERVER);
        });
        $this->alias('request', 'server_request');
        foreach ($values as $key => $value) {
            $this->set($key, $value);
        }
    }
}
