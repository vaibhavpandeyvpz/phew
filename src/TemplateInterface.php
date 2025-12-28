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
 * Interface TemplateInterface
 *
 * Interface for template rendering. Provides methods for managing blocks,
 * layouts, partials, and rendering templates.
 */
interface TemplateInterface
{
    /**
     * Begin a new block with the given name.
     *
     * @param  string  $name  The name of the block (cannot be 'content' as it's reserved)
     * @param  bool  $append  If true, content will be appended to existing block content
     *
     * @throws ViewException If the block name is 'content' or if a block is already open
     */
    public function begin(string $name, bool $append = false): void;

    /**
     * Get the content of a block by name.
     *
     * @param  string  $name  The name of the block
     * @return string The block content, or empty string if block doesn't exist
     */
    public function block(string $name): string;

    /**
     * End the current block and store its content.
     *
     * @throws ViewException If no block was started
     */
    public function end(): void;

    /**
     * Fetch and render a partial template.
     *
     * @param  string  $name  The name of the partial template (can include namespace)
     * @param  array<string, mixed>|null  $vars  Optional variables to pass to the partial
     * @return string The rendered partial content
     *
     * @throws ViewException If the partial template cannot be found
     */
    public function fetch(string $name, ?array $vars = null): string;

    /**
     * Set a layout template to wrap the current template.
     *
     * @param  string  $name  The name of the layout template (can include namespace)
     * @param  array<string, mixed>|null  $vars  Optional variables to pass to the layout
     */
    public function layout(string $name, ?array $vars = null): void;

    /**
     * Render the template and return its content.
     *
     * @return string The rendered template content
     */
    public function render(): string;
}
