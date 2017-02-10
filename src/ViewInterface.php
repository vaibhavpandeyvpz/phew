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
 * Interface ViewInterface
 * @package Phew
 */
interface ViewInterface extends \ArrayAccess
{
    /**
     * @param string $folder
     * @param string $ns
     * @return static
     */
    public function add($folder, $ns = '');

    /**
     * @param string $template
     * @param array|null $vars
     * @return string
     */
    public function fetch($template, array $vars = null);

    /**
     * @param string $template
     * @return string
     * @throws ViewException
     */
    public function find($template);

    /**
     * @param ExtensionInterface $extension
     * @return static
     */
    public function install(ExtensionInterface $extension);

    /**
     * @param string $function
     * @param array $arguments
     * @return mixed
     * @throws ViewException
     */
    public function invoke($function, array $arguments);

    /**
     * @param string $function
     * @param callable $callback
     * @return static
     */
    public function register($function, $callback);
}
