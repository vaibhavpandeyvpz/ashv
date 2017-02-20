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

use Ashv\Exception\MethodNotAllowedException;
use Ashv\Exception\NotFoundException;
use Ashv\Middleware\ContentLengthMiddleware;
use Ashv\Middleware\ExceptionHandlerMiddleware;
use Interop\Container\ContainerInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Sandesh\ResponseSenderInterface;
use Tez\RouteCollection;
use Tez\Router;
use Vidyut\Pipeline;
use Vidyut\PipelineInterface;

/**
 * Class Application
 * @package Ashv
 */
class Application extends RouteCollection implements MiddlewareInterface, MiddlewarePipelineInterface
{
    /**
     * @var Container|ContainerInterface
     */
    protected $container;

    /**
     * @var PipelineInterface
     */
    protected $pipeline;

    /**
     * Application constructor.
     * @param ContainerInterface|array|null $container
     */
    public function __construct($container = null)
    {
        if (is_null($container) || is_array($container)) {
            $container = new Container((array)$container);
        }
        $this->container = $container;
        $this->pipeline = new Pipeline();
        $this->before(new ContentLengthMiddleware());
        $this->before(new ExceptionHandlerMiddleware());
    }

    /**
     * {@inheritdoc}
     */
    public function before($middleware)
    {
        if ($middleware instanceof ContainerAwareInterface) {
            $middleware = $middleware->withContainer($this->container);
        }
        $this->pipeline->pipe($middleware);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(RequestInterface $request = null)
    {
        $request = $request ?: $this->container->get('request');
        $level = ob_get_level();
        ob_start();
        $pipeline = clone $this->pipeline;
        $response = $pipeline->pipe($this)->process($request);
        if ($response instanceof ResponseInterface) {
            /** @var ResponseSenderInterface $sender */
            $sender = $this->container->get('response_sender');
            $sender->send($response, $level);
            return $response;
        }
        throw new \UnexpectedValueException(sprintf(
            "Application must end with an instance of '%s'; '%s' given",
            'Psr\\Http\\Message\\ResponseInterface',
            is_object($response) ? get_class($response) : gettype($response)
        ));
    }

    /**
     * @param ModuleInterface $module
     * @return static
     */
    public function install(ModuleInterface $module)
    {
        $module->configure($this->container);
        $module->enqueue($this, $this->container);
        $module->route($this);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $router = new Router($this, $this->container->get('path_compiler'));
        $result = $router->match($request->getMethod(), $request->getUri()->getPath());
        if ($result->isFound()) {
            $params = $result->getParameters();
            $params['request'] = $request;
            /** @var DispatcherInterface $dispatcher */
            $dispatcher = $this->container->get('dispatcher');
            return $dispatcher->dispatch($result->getCallback(), $params);
        } elseif ($result->isNotAllowed()) {
            throw new MethodNotAllowedException();
        }
        throw new NotFoundException();
    }

    /**
     * @param string $path
     * @param string $controller
     * @return static
     */
    public function resource($path, $controller)
    {
        $this->get($path, [$controller, 'list']);
        $this->get("{$path}/{id}", [$controller, 'index']);
        $this->post($path, [$controller, 'create']);
        $this->put("{$path}/{id}", [$controller, 'update']);
        $this->delete("{$path}/{id}", [$controller, 'delete']);
        return $this;
    }
}
