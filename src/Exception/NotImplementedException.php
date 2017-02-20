<?php

/*
 * This file is part of vaibhavpandeyvpz/ashv package.
 *
 * (c) Vaibhav Pandey <contact@vaibhavpandey.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.md.
 */

namespace Ashv\Exception;

use Ashv\ExceptionInterface;

/**
 * Class NotImplementedException
 * @package Ashv\Exception
 */
class NotImplementedException extends \Exception implements ExceptionInterface
{
    /**
     * {@inheritdoc}
     */
    public function getStatusCode()
    {
        return 501;
    }
}
