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

use Ashv\ContainerAwareInterface;
use Ashv\ContainerAwareTrait;
use Ashv\ExceptionInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class ExceptionHandlerMiddleware
 * @package Ashv\Middleware
 */
class ExceptionHandlerMiddleware implements ContainerAwareInterface, MiddlewareInterface
{
    use ContainerAwareTrait;

    /**
     * @var array
     */
    protected static $messages = [
        401 => 'You are not authorized to access this page or resource.',
        403 => 'You are not permitted to access this page or resource.',
        404 => 'The page or resource you are looking for does not exist.',
        405 => 'This page or resource is not accessible via current HTTP method.',
        500 => 'Unfortunately, something went wrong at server.',
    ];

    /**
     * ExceptionHandlerMiddleware constructor.
     * @param bool $errors
     */
    public function __construct($errors = true)
    {
        if ($errors) {
            set_error_handler(function ($errno, $errstr, $errfile, $errline) {
                throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
            });
        }
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        try {
            return $delegate->process($request);
        } catch (\Exception $e) {
            if ($this->container->has('logger')) {
                $this->container->get('logger')->error($e);
            }
            return $this->render($this->container->get('response'), $e);
        }
    }

    /**
     * @param ResponseInterface $into
     * @param \Exception $e
     * @return ResponseInterface
     */
    protected function render(ResponseInterface $into, \Exception $e)
    {
        $debug = $this->container->has('debug') ? $this->container->get('debug') : false;
        $stacktrace = 'Use $container["debug"] = true to enable stack traces.';
        if ($debug) {
            $stacktrace = nl2br(htmlentities(self::trace($e)));
        }
        $heading = get_class($e);
        $shortname = basename($heading);
        $code = $e instanceof ExceptionInterface ? $e->getStatusCode() : 500;
        $message = isset(self::$messages[$code]) ? self::$messages[$code] : self::$messages[500];
        $into->getBody()->write(<<<HTML
<!doctype html>
<html lang="en">
<head>
    <title>{$code}: {$shortname}</title>
    <style>
        abbr { border-bottom: 1px dashed }
        body {
            background: #ececec;
            font: 12px/1.5 Helvetica, Arial, Verdana, sans-serif;
            margin: 0;
            padding: 24px;
        }
        hr {
            border-width: 0 0 0 0;
            border-bottom: 1px solid #eee;
        }
        .content {
            background: #fff;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-shadow: 0 2px 8px #ddd;
            margin: 0 auto;
            max-width: 1200px;
            padding: 12px 24px;
        }
        .content .stacktrace {
            background: #efefef;
            border: 1px solid #ccc;
            border-radius: 2px;
            margin: 12px 0;
            overflow: auto;
            padding: 12px;
            white-space: nowrap;
        }
        .content .stacktrace code { font-family: 'Courier New', Courier, Arial, monospace }
        .content h1, .content h2 {
            margin: 0 0 4px 0;
            font-size: 24px;
        }
        .content h1 { font-size: 24px }
        .content h2 { font-size: 20px }
        .content h1 span, .content h2 span { font-weight: normal }
        .content h1 span { color: #aaa }
        .content p {
            font-size: 16px;
            margin: 4px 0 0 0;
        }
    </style>
</head>
<body>
<div class="content">
    <h1>
        <span>{$code}</span>: <abbr title="{$heading}">{$shortname}</abbr>
    </h1>
    <p>{$message}</p>
    <hr>
    <div class="stacktrace">
        <code>{$stacktrace}</code>
    </div>
</div>
</body>
</html>
HTML
);
        return $into->withHeader('Content-Type', 'text/html')
            ->withStatus($code);
    }

    /**
     * @param \Exception $e
     * @return string
     */
    protected static function trace(\Exception $e)
    {
        $result = sprintf('%s: %s', get_class($e), $e->getMessage()) . "\n";
        $result .= $e->getTraceAsString() . "\n";
        if ($previous = $e->getPrevious()) {
            $result .= self::trace($previous) . "\n";
        }
        return rtrim($result, "\n");
    }
}
