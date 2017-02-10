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
 * Interface TemplateInterface
 * @package Phew
 */
interface TemplateInterface
{
    /**
     * @param string $name
     * @param bool $append
     */
    public function begin($name, $append = false);

    /**
     * @param string $name
     * @return string
     */
    public function block($name);

    public function end();

    /**
     * @param string $name
     * @param array|null $vars
     * @return string
     */
    public function fetch($name, array $vars = null);

    /**
     * @param string $name
     * @param array|null $vars
     */
    public function layout($name, array $vars = null);

    /**
     * @return string
     */
    public function render();
}
