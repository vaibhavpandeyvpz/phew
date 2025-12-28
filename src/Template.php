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
 * Class Template
 *
 * Represents a template instance. Handles rendering of template files with support
 * for blocks, layouts, partials, and variable injection. Templates can extend layouts
 * and define reusable content blocks.
 */
class Template implements TemplateInterface
{
    /**
     * Currently open block metadata (name and append flag).
     *
     * @var array{name: string, append: bool}|null
     */
    protected ?array $__block = null;

    /**
     * All defined blocks with their content and append flags.
     *
     * @var array<string, array{append: bool, content: string}>
     */
    protected array $__blocks = [];

    /**
     * Layout template information (name and optional variables).
     *
     * @var array{name: string, vars: array<string, mixed>|null}|null
     */
    protected ?array $__layout = null;

    /**
     * Variables available in the template.
     *
     * @var array<string, mixed>
     */
    protected array $__vars;

    /**
     * Template constructor.
     *
     * @param  ViewInterface  $__view  The view engine instance
     * @param  string  $__path  The filesystem path to the template file
     * @param  array<string, mixed>|null  $vars  Optional variables to pass to the template
     */
    public function __construct(
        protected readonly ViewInterface $__view,
        protected readonly string $__path,
        ?array $vars = null
    ) {
        $this->__vars = $vars ?? [];
        if (isset($this->__vars['this'])) {
            unset($this->__vars['this']);
        }
    }

    /**
     * Magic method to invoke registered functions from templates.
     *
     * Allows calling registered functions directly on the template instance
     * (e.g., $this->uppercase('hello')).
     *
     * @param  string  $name  The function name
     * @param  array<int, mixed>  $arguments  Function arguments
     * @return mixed The return value of the function
     *
     * @throws ViewException If the function is not registered
     */
    public function __call(string $name, array $arguments): mixed
    {
        return $this->__view->invoke($name, $arguments);
    }

    /**
     * Begin a new block with the given name.
     *
     * Starts output buffering to capture block content. The block name 'content'
     * is reserved for layout content injection. Nested blocks are not supported.
     *
     * @param  string  $name  The name of the block (cannot be 'content')
     * @param  bool  $append  If true, content will be appended to existing block content
     *
     * @throws ViewException If the block name is 'content' or if a block is already open
     */
    public function begin(string $name, bool $append = false): void
    {
        if ($name === 'content') {
            throw new ViewException("Block name '{$name}' is reserved.");
        }
        if ($this->__block !== null) {
            throw new ViewException('Nested blocks inside blocks is not yet allowed.');
        }
        $this->__block = ['name' => $name, 'append' => $append];
        ob_start();
    }

    /**
     * Get the content of a block by name.
     *
     * @param  string  $name  The name of the block
     * @return string The block content, or empty string if block doesn't exist
     */
    public function block(string $name): string
    {
        return $this->__blocks[$name]['content'] ?? '';
    }

    /**
     * Escape a value for safe HTML output.
     *
     * Converts special characters to HTML entities to prevent XSS attacks.
     * Handles both single and double quotes.
     *
     * @param  mixed  $value  The value to escape (will be cast to string)
     * @return string The escaped HTML string
     */
    public function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES);
    }

    /**
     * End the current block and store its content.
     *
     * Stops output buffering, processes the content based on append behavior,
     * and stores the block. If no layout is set, the content is immediately
     * echoed. If a block with the same name already exists, the behavior depends
     * on the existing block's append flag.
     *
     * @throws ViewException If no block was started
     */
    public function end(): void
    {
        if ($this->__block === null) {
            throw new ViewException('You must begin a block before you end.');
        }

        $name = $this->__block['name'];
        $append = $this->__block['append'];
        $content = ob_get_clean();

        if (isset($this->__blocks[$name])) {
            $next = $this->__blocks[$name];
            $content = $next['append'] ? $content.$next['content'] : $next['content'];
        }

        if ($this->__layout === null) {
            echo $content;
        }

        $this->__blocks[$name] = [
            'append' => $append,
            'content' => $content,
        ];
        $this->__block = null;
    }

    /**
     * Fetch and render a partial template.
     *
     * Partials are useful for reusable template fragments. Variables passed here
     * are merged with the current template's variables, with partial-specific
     * variables taking precedence.
     *
     * @param  string  $name  The name of the partial template (can include namespace)
     * @param  array<string, mixed>|null  $vars  Optional variables to pass to the partial
     * @return string The rendered partial content
     *
     * @throws ViewException If the partial template cannot be found
     */
    public function fetch(string $name, ?array $vars = null): string
    {
        $path = $this->__view->find($name);
        $vars = array_merge($this->__vars, $vars ?? []);
        $partial = new Template($this->__view, $path, $vars);

        return $partial->render();
    }

    /**
     * Set a layout template to wrap the current template.
     *
     * The layout will be rendered after the current template, with the current
     * template's content available as the 'content' block. All blocks defined
     * in the current template will be available in the layout.
     *
     * @param  string  $name  The name of the layout template (can include namespace)
     * @param  array<string, mixed>|null  $vars  Optional variables to pass to the layout
     */
    public function layout(string $name, ?array $vars = null): void
    {
        $this->__layout = ['name' => $name, 'vars' => $vars];
    }

    /**
     * Render the template and return its content.
     *
     * Extracts variables into the template scope, includes the template file,
     * and processes any layout if one was set. The rendered content is returned
     * with leading whitespace trimmed.
     *
     * @return string The rendered template content
     */
    public function render(): string
    {
        ob_start();
        extract($this->__vars, EXTR_SKIP);
        /** @noinspection PhpIncludeInspection */
        include $this->__path;
        $content = ob_get_clean();

        if ($this->__layout !== null) {
            $path = $this->__view->find($this->__layout['name']);
            $vars = array_merge($this->__vars, $this->__layout['vars'] ?? []);
            $layout = new Template($this->__view, $path, $vars);
            $layout->__blocks = $this->__blocks;
            $layout->__blocks['content'] = [
                'append' => false,
                'content' => $content,
            ];
            $content = $layout->render();
            $this->__layout = null;
        }

        return ltrim($content);
    }
}
