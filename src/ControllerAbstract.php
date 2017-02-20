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

use Phew\ViewInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class ControllerAbstract
 * @package Ashv
 */
abstract class ControllerAbstract implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @param mixed $data
     * @param string $type
     * @return ResponseInterface
     */
    protected function json($data, $type = 'application/json')
    {
        $response = $this->response();
        $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', $type);
    }

    /**
     * @param string $to
     * @param int $code
     * @return ResponseInterface
     */
    protected function redirect($to, $code = 302)
    {
        return $this->response($code)
            ->withHeader('Location', $to);
    }

    /**
     * @param string $template
     * @param array|object $data
     * @param string $type
     * @return ResponseInterface
     */
    protected function render($template, $data = null, $type = 'text/html')
    {
        /** @var ViewInterface $view */
        $view = $this->container->get('view');
        $contents = $view->fetch($template, $data);
        $response = $this->response();
        $response->getBody()->write($contents);
        return $response->withHeader('Content-Type', $type);
    }

    /**
     * @param int $code
     * @return ResponseInterface
     */
    protected function response($code = 200)
    {
        /** @var ResponseInterface $response */
        $response = $this->container->get('response');
        return $response->withStatus($code);
    }
}
