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
 * Class View
 *
 * Main view engine class. Manages template folders, variables, registered functions,
 * and provides methods to render templates. Implements ArrayAccess for convenient
 * variable management using array syntax.
 */
class View implements ViewInterface
{
    /**
     * Template variables accessible in templates.
     *
     * @var array<string, mixed>
     */
    protected array $vars = [];

    /**
     * Registered functions that can be called from templates.
     *
     * @var array<string, callable>
     */
    protected array $functions = [];

    /**
     * Template folders organized by namespace.
     * Key is the namespace (empty string for default), value is array of folder paths.
     *
     * @var array<string, array<string>>
     */
    protected array $folders = [];

    /**
     * Add a template folder to the view engine.
     *
     * @param  string  $folder  The path to the template folder
     * @param  string  $ns  Optional namespace for the folder (e.g., 'admin', 'partials')
     * @return static Returns self for method chaining
     */
    public function add(string $folder, string $ns = ''): static
    {
        $this->folders[$ns][] = rtrim($folder, DIRECTORY_SEPARATOR);

        return $this;
    }

    /**
     * Fetch and render a template with optional variables.
     *
     * Variables passed here will be merged with existing variables set via ArrayAccess.
     * Template-specific variables take precedence over global variables.
     *
     * @param  string  $template  Template name (can include namespace, e.g., 'admin:users')
     * @param  array<string, mixed>|null  $vars  Optional variables to pass to the template
     * @return string The rendered template content (leading whitespace trimmed)
     *
     * @throws ViewException If the template cannot be found
     */
    public function fetch(string $template, ?array $vars = null): string
    {
        $path = $this->find($template);
        $vars = array_merge($this->vars, $vars ?? []);
        $template = new Template($this, $path, $vars);

        return ltrim($template->render());
    }

    /**
     * Find the full path to a template file.
     *
     * Supports namespace syntax: 'namespace:template' will look in folders
     * registered for that namespace. If no namespace is provided, looks in
     * the default namespace (empty string).
     *
     * @param  string  $template  Template name (can include namespace, e.g., 'admin:users')
     * @return string The full filesystem path to the template
     *
     * @throws ViewException If the template cannot be located in any defined folders
     */
    public function find(string $template): string
    {
        $folders = [];
        $colonPos = strpos($template, ':');
        if ($colonPos !== false && $colonPos > 0) {
            [$ns, $template] = explode(':', $template, 2);
            $folders = $this->folders[$ns] ?? [];
        } else {
            $folders = $this->folders[''] ?? [];
        }

        $template = ltrim($template, DIRECTORY_SEPARATOR);
        foreach ($folders as $folder) {
            $path = $folder.DIRECTORY_SEPARATOR.$template;
            if (is_file($path)) {
                return $path;
            }
        }
        throw new ViewException("Could not locate '{$template}' template in defined folders.");
    }

    /**
     * Install an extension to extend the view engine's functionality.
     *
     * @param  ExtensionInterface  $extension  The extension to install
     * @return static Returns self for method chaining
     */
    public function install(ExtensionInterface $extension): static
    {
        $extension->extend($this);

        return $this;
    }

    /**
     * Invoke a registered function with the given arguments.
     *
     * Uses first-class callable syntax for efficient function invocation.
     *
     * @param  string  $function  The name of the registered function
     * @param  array<int, mixed>  $arguments  Arguments to pass to the function
     * @return mixed The return value of the function
     *
     * @throws ViewException If the function is not registered
     */
    public function invoke(string $function, array $arguments): mixed
    {
        if (! isset($this->functions[$function])) {
            throw new ViewException("Function '{$function}' does not exist in template context.");
        }

        return $this->functions[$function](...$arguments);
    }

    /**
     * Check if a variable exists (ArrayAccess implementation).
     *
     * @param  mixed  $offset  The variable name
     * @return bool True if the variable exists, false otherwise
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->vars[$offset]);
    }

    /**
     * Get a variable value (ArrayAccess implementation).
     *
     * @param  mixed  $offset  The variable name
     * @return mixed The variable value
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->vars[$offset];
    }

    /**
     * Set a variable value (ArrayAccess implementation).
     *
     * @param  mixed  $offset  The variable name (can be null for auto-increment)
     * @param  mixed  $value  The variable value
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->vars[$offset] = $value;
    }

    /**
     * Unset a variable (ArrayAccess implementation).
     *
     * @param  mixed  $offset  The variable name
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->vars[$offset]);
    }

    /**
     * Register a callable function that can be invoked from templates.
     *
     * Registered functions can be called from templates using the magic __call method
     * on the Template instance (e.g., $this->myFunction($arg1, $arg2)).
     *
     * @param  string  $function  The name of the function to register
     * @param  callable  $callback  The callable to register
     * @return static Returns self for method chaining
     */
    public function register(string $function, callable $callback): static
    {
        $this->functions[$function] = $callback;

        return $this;
    }
}
