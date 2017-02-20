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

use Psr\Http\Message\UploadedFileInterface;
use Sandesh\ServerRequestFactory as BaseRequestFactory;
use Sandesh\UploadedFile;

/**
 * Class ServerRequestFactory
 * @package Ashv
 */
class ServerRequestFactory extends BaseRequestFactory
{
    /**
     * {@inheritdoc}
     */
    public function createServerRequest(array $server, $method = null, $uri = null)
    {
        $request = parent::createServerRequest($server, $method, $uri);
        $type = $request->getHeaderLine('Content-Type');
        $body = ($request->getMethod() === 'POST')
            ? (in_array($type, ['application/x-www-form-urlencoded', 'multipart/form-data']) ? $_POST : null)
            : null;
        $files = self::getUploadedFiles($_FILES);
        return $request->withCookieParams($_COOKIE)
            ->withParsedBody($body)
            ->withQueryParams($_GET)
            ->withUploadedFiles($files);
    }

    /**
     * @param array $file
     * @return UploadedFileInterface|UploadedFileInterface[]
     */
    protected static function getUploadedFile(array $file)
    {
        if (is_array($file['tmp_name'])) {
            $files = [];
            foreach (array_keys($file['tmp_name']) as $key) {
                $files[$key] = new UploadedFile(
                    $file['tmp_name'][$key],
                    (int)$file['size'][$key],
                    (int)$file['error'][$key],
                    $file['name'][$key],
                    $file['type'][$key]
                );
            }
            return $files;
        }
        return new UploadedFile(
            $file['tmp_name'],
            (int)$file['size'],
            (int)$file['error'],
            $file['name'],
            $file['type']
        );
    }

    /**
     * @param array $files
     * @return UploadedFileInterface[]
     */
    public static function getUploadedFiles(array $files)
    {
        $list = [];
        foreach ($files as $key => $data) {
            if (is_array($data)) {
                if (isset($data['tmp_name'])) {
                    $list[$key] = self::getUploadedFile($data);
                } else {
                    $list[$key] = self::getUploadedFiles($data);
                }
            }
        }
        return $list;
    }
}
