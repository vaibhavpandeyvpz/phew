<?php

/*
 * This file is part of vaibhavpandeyvpz/phew package.
 *
 * (c) Vaibhav Pandey <contact@vaibhavpandey.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.md.
 */

namespace Phew;

/**
 * Interface ExtensionInterface
 * @package Phew
 */
interface ExtensionInterface
{
    /**
     * @param ViewInterface $view
     */
    public function extend(ViewInterface $view);
}
