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

/**
 * Interface ExceptionInterface
 * @package Ashv
 */
interface ExceptionInterface
{
    /**
     * @return int
     */
    public function getStatusCode();
}
