<?php

declare(strict_types=1);

/*
 * This file is part of vaibhavpandeyvpz/phew package.
 *
 * (c) Vaibhav Pandey <contact@vaibhavpandey.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Phew;

/**
 * Interface ViewInterface
 *
 * Main interface for the view engine. Provides methods for managing template folders,
 * rendering templates, registering functions, and installing extensions.
 * Also implements ArrayAccess for convenient variable management.
 */
interface ViewInterface extends \ArrayAccess
{
    /**
     * Add a template folder to the view engine.
     *
     * @param  string  $folder  The path to the template folder
     * @param  string  $ns  Optional namespace for the folder (e.g., 'admin', 'partials')
     * @return static Returns self for method chaining
     */
    public function add(string $folder, string $ns = ''): static;

    /**
     * Fetch and render a template with optional variables.
     *
     * @param  string  $template  Template name (can include namespace, e.g., 'admin:users')
     * @param  array<string, mixed>|null  $vars  Optional variables to pass to the template
     * @return string The rendered template content
     *
     * @throws ViewException If the template cannot be found
     */
    public function fetch(string $template, ?array $vars = null): string;

    /**
     * Find the full path to a template file.
     *
     * @param  string  $template  Template name (can include namespace, e.g., 'admin:users')
     * @return string The full filesystem path to the template
     *
     * @throws ViewException If the template cannot be located in any defined folders
     */
    public function find(string $template): string;

    /**
     * Install an extension to extend the view engine's functionality.
     *
     * @param  ExtensionInterface  $extension  The extension to install
     * @return static Returns self for method chaining
     */
    public function install(ExtensionInterface $extension): static;

    /**
     * Invoke a registered function with the given arguments.
     *
     * @param  string  $function  The name of the registered function
     * @param  array<int, mixed>  $arguments  Arguments to pass to the function
     * @return mixed The return value of the function
     *
     * @throws ViewException If the function is not registered
     */
    public function invoke(string $function, array $arguments): mixed;

    /**
     * Register a callable function that can be invoked from templates.
     *
     * @param  string  $function  The name of the function to register
     * @param  callable  $callback  The callable to register
     * @return static Returns self for method chaining
     */
    public function register(string $function, callable $callback): static;
}
