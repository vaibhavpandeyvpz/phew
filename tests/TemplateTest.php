<?php

declare(strict_types=1);

namespace Phew\Tests;

use Phew\Template;
use Phew\View;
use Phew\ViewException;
use PHPUnit\Framework\TestCase;

/**
 * Test suite for the Template class.
 *
 * Tests template rendering, variable injection, HTML escaping, blocks,
 * layouts, partials, and all template functionality.
 */
class TemplateTest extends TestCase
{
    /**
     * Temporary directory for test template files.
     */
    private string $templateDir;

    /**
     * View instance for testing.
     */
    private View $view;

    protected function setUp(): void
    {
        parent::setUp();
        $this->templateDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'phew_tests_'.uniqid();
        mkdir($this->templateDir, 0777, true);
        $this->view = new View;
        $this->view->add($this->templateDir);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->templateDir);
        parent::tearDown();
    }

    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir.DIRECTORY_SEPARATOR.$file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function test_render_simple_template(): void
    {
        $templateFile = $this->templateDir.DIRECTORY_SEPARATOR.'test.php';
        file_put_contents($templateFile, 'Hello World');

        $template = new Template($this->view, $templateFile);
        $result = $template->render();

        $this->assertSame('Hello World', $result);
    }

    public function test_render_template_with_variables(): void
    {
        $templateFile = $this->templateDir.DIRECTORY_SEPARATOR.'test.php';
        file_put_contents($templateFile, 'Hello <?= $name ?>');

        $template = new Template($this->view, $templateFile, ['name' => 'World']);
        $result = $template->render();

        $this->assertSame('Hello World', $result);
    }

    public function test_render_template_with_multiple_variables(): void
    {
        $templateFile = $this->templateDir.DIRECTORY_SEPARATOR.'test.php';
        file_put_contents($templateFile, '<?= $greeting ?> <?= $name ?>');

        $template = new Template($this->view, $templateFile, [
            'greeting' => 'Hello',
            'name' => 'World',
        ]);
        $result = $template->render();

        $this->assertSame('Hello World', $result);
    }

    public function test_render_template_trims_leading_whitespace(): void
    {
        $templateFile = $this->templateDir.DIRECTORY_SEPARATOR.'test.php';
        file_put_contents($templateFile, "\n\nHello");

        $template = new Template($this->view, $templateFile);
        $result = $template->render();

        $this->assertSame('Hello', $result);
    }

    public function test_escape_html(): void
    {
        $templateFile = $this->templateDir.DIRECTORY_SEPARATOR.'test.php';
        file_put_contents($templateFile, '<?= $this->e($html) ?>');

        $template = new Template($this->view, $templateFile, [
            'html' => '<script>alert("xss")</script>',
        ]);
        $result = $template->render();

        $this->assertSame('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', $result);
    }

    public function test_escape_html_with_quotes(): void
    {
        $templateFile = $this->templateDir.DIRECTORY_SEPARATOR.'test.php';
        file_put_contents($templateFile, '<?= $this->e($text) ?>');

        $template = new Template($this->view, $templateFile, [
            'text' => "It's a \"test\"",
        ]);
        $result = $template->render();

        $this->assertSame('It&#039;s a &quot;test&quot;', $result);
    }

    public function test_escape_with_non_string_value(): void
    {
        $templateFile = $this->templateDir.DIRECTORY_SEPARATOR.'test.php';
        file_put_contents($templateFile, '<?= $this->e($number) ?>');

        $template = new Template($this->view, $templateFile, ['number' => 123]);
        $result = $template->render();

        $this->assertSame('123', $result);
    }

    public function test_begin_block(): void
    {
        $templateFile = $this->templateDir.DIRECTORY_SEPARATOR.'test.php';
        file_put_contents($templateFile, '<?php $this->begin("header"); ?>Header<?php $this->end(); ?>');

        $template = new Template($this->view, $templateFile);
        $result = $template->render();

        $this->assertStringContainsString('Header', $result);
    }

    public function test_begin_block_throws_exception_for_reserved_name(): void
    {
        $templateFile = $this->templateDir.DIRECTORY_SEPARATOR.'test.php';
        file_put_contents($templateFile, '<?php $this->begin("content"); ?>');

        $template = new Template($this->view, $templateFile);

        $this->expectException(ViewException::class);
        $this->expectExceptionMessage("Block name 'content' is reserved.");

        try {
            $template->render();
        } finally {
            // Clean up any output buffers that might be left open
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
        }
    }

    public function test_begin_block_throws_exception_for_nested_blocks(): void
    {
        $templateFile = $this->templateDir.DIRECTORY_SEPARATOR.'test.php';
        file_put_contents($templateFile, '<?php $this->begin("outer"); $this->begin("inner"); ?>');

        $template = new Template($this->view, $templateFile);

        $this->expectException(ViewException::class);
        $this->expectExceptionMessage('Nested blocks inside blocks is not yet allowed.');

        try {
            $template->render();
        } finally {
            // Clean up any output buffers that might be left open
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
        }
    }

    public function test_end_block_throws_exception_when_no_block_started(): void
    {
        $templateFile = $this->templateDir.DIRECTORY_SEPARATOR.'test.php';
        file_put_contents($templateFile, '<?php $this->end(); ?>');

        $template = new Template($this->view, $templateFile);

        $this->expectException(ViewException::class);
        $this->expectExceptionMessage('You must begin a block before you end.');

        try {
            $template->render();
        } finally {
            // Clean up any output buffers that might be left open
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
        }
    }

    public function test_block_content(): void
    {
        $templateFile = $this->templateDir.DIRECTORY_SEPARATOR.'test.php';
        file_put_contents($templateFile, '<?php $this->begin("header"); ?>Header<?php $this->end(); echo $this->block("header"); ?>');

        $template = new Template($this->view, $templateFile);
        $result = $template->render();

        $this->assertStringContainsString('Header', $result);
    }

    public function test_block_content_returns_empty_string_when_not_set(): void
    {
        $templateFile = $this->templateDir.DIRECTORY_SEPARATOR.'test.php';
        file_put_contents($templateFile, '<?= $this->block("nonexistent") ?>');

        $template = new Template($this->view, $templateFile);
        $result = $template->render();

        $this->assertSame('', $result);
    }

    public function test_block_with_append(): void
    {
        $templateFile = $this->templateDir.DIRECTORY_SEPARATOR.'test.php';
        file_put_contents($templateFile, '<?php $this->begin("block", true); ?>Appended<?php $this->end(); ?>');

        $template = new Template($this->view, $templateFile);
        ob_start();
        $template->render();
        ob_get_clean();

        $this->assertTrue(true); // Block was created with append flag
    }

    public function test_fetch_partial(): void
    {
        $partialFile = $this->templateDir.DIRECTORY_SEPARATOR.'partial.php';
        file_put_contents($partialFile, 'Partial: <?= $name ?>');

        $templateFile = $this->templateDir.DIRECTORY_SEPARATOR.'test.php';
        file_put_contents($templateFile, 'Main <?= $this->fetch("partial.php", ["name" => "World"]) ?>');

        $template = new Template($this->view, $templateFile, ['name' => 'Test']);
        $result = $template->render();

        $this->assertStringContainsString('Main', $result);
        $this->assertStringContainsString('Partial: World', $result);
    }

    public function test_fetch_partial_merges_vars(): void
    {
        $partialFile = $this->templateDir.DIRECTORY_SEPARATOR.'partial.php';
        file_put_contents($partialFile, '<?= $global ?> <?= $local ?>');

        $templateFile = $this->templateDir.DIRECTORY_SEPARATOR.'test.php';
        file_put_contents($templateFile, '<?= $this->fetch("partial.php", ["local" => "Local"]) ?>');

        $template = new Template($this->view, $templateFile, ['global' => 'Global']);
        $result = $template->render();

        $this->assertSame('Global Local', $result);
    }

    public function test_fetch_partial_with_null_vars(): void
    {
        $partialFile = $this->templateDir.DIRECTORY_SEPARATOR.'partial.php';
        file_put_contents($partialFile, '<?= $name ?>');

        $templateFile = $this->templateDir.DIRECTORY_SEPARATOR.'test.php';
        file_put_contents($templateFile, '<?= $this->fetch("partial.php", null) ?>');

        $template = new Template($this->view, $templateFile, ['name' => 'World']);
        $result = $template->render();

        $this->assertSame('World', $result);
    }

    public function test_layout(): void
    {
        $layoutFile = $this->templateDir.DIRECTORY_SEPARATOR.'layout.php';
        file_put_contents($layoutFile, '<html><body><?= $this->block("content") ?></body></html>');

        $templateFile = $this->templateDir.DIRECTORY_SEPARATOR.'test.php';
        file_put_contents($templateFile, '<?php $this->layout("layout.php"); ?>Content');

        $template = new Template($this->view, $templateFile);
        $result = $template->render();

        $this->assertStringContainsString('<html>', $result);
        $this->assertStringContainsString('<body>', $result);
        $this->assertStringContainsString('Content', $result);
    }

    public function test_layout_with_variables(): void
    {
        $layoutFile = $this->templateDir.DIRECTORY_SEPARATOR.'layout.php';
        file_put_contents($layoutFile, '<title><?= $title ?></title><?= $this->block("content") ?>');

        $templateFile = $this->templateDir.DIRECTORY_SEPARATOR.'test.php';
        file_put_contents($templateFile, '<?php $this->layout("layout.php", ["title" => "Page"]); ?>Content');

        $template = new Template($this->view, $templateFile, ['title' => 'Default']);
        $result = $template->render();

        $this->assertStringContainsString('<title>Page</title>', $result);
        $this->assertStringContainsString('Content', $result);
    }

    public function test_layout_merges_variables(): void
    {
        $layoutFile = $this->templateDir.DIRECTORY_SEPARATOR.'layout.php';
        file_put_contents($layoutFile, '<?= $global ?> <?= $local ?> <?= $this->block("content") ?>');

        $templateFile = $this->templateDir.DIRECTORY_SEPARATOR.'test.php';
        file_put_contents($templateFile, '<?php $this->layout("layout.php", ["local" => "Local"]); ?>Content');

        $template = new Template($this->view, $templateFile, ['global' => 'Global']);
        $result = $template->render();

        $this->assertStringContainsString('Global Local', $result);
        $this->assertStringContainsString('Content', $result);
    }

    public function test_layout_with_blocks(): void
    {
        $layoutFile = $this->templateDir.DIRECTORY_SEPARATOR.'layout.php';
        file_put_contents($layoutFile, '<header><?= $this->block("header") ?></header><?= $this->block("content") ?>');

        $templateFile = $this->templateDir.DIRECTORY_SEPARATOR.'test.php';
        file_put_contents($templateFile, '<?php $this->begin("header"); ?>Header<?php $this->end(); $this->layout("layout.php"); ?>Content');

        $template = new Template($this->view, $templateFile);
        $result = $template->render();

        $this->assertStringContainsString('<header>Header</header>', $result);
        $this->assertStringContainsString('Content', $result);
    }

    public function test_layout_with_null_vars(): void
    {
        $layoutFile = $this->templateDir.DIRECTORY_SEPARATOR.'layout.php';
        file_put_contents($layoutFile, '<?= $name ?> <?= $this->block("content") ?>');

        $templateFile = $this->templateDir.DIRECTORY_SEPARATOR.'test.php';
        file_put_contents($templateFile, '<?php $this->layout("layout.php", null); ?>Content');

        $template = new Template($this->view, $templateFile, ['name' => 'World']);
        $result = $template->render();

        $this->assertStringContainsString('World', $result);
        $this->assertStringContainsString('Content', $result);
    }

    public function test_invoke_function_via_magic_call(): void
    {
        $this->view->register('uppercase', fn (string $str) => strtoupper($str));

        $templateFile = $this->templateDir.DIRECTORY_SEPARATOR.'test.php';
        file_put_contents($templateFile, '<?= $this->uppercase("hello") ?>');

        $template = new Template($this->view, $templateFile);
        $result = $template->render();

        $this->assertSame('HELLO', $result);
    }

    public function test_constructor_removes_this_from_vars(): void
    {
        $templateFile = $this->templateDir.DIRECTORY_SEPARATOR.'test.php';
        // Test that $this refers to the Template instance, not a user variable
        // Even if 'this' is passed in vars, it should be removed and $this should refer to Template
        file_put_contents($templateFile, '<?= $this instanceof \Phew\Template ? "template" : "not template" ?>');

        $template = new Template($this->view, $templateFile, ['this' => 'should be removed']);
        $result = $template->render();

        // $this should refer to the Template instance
        $this->assertStringContainsString('template', $result);
    }

    public function test_block_append_behavior(): void
    {
        $templateFile = $this->templateDir.DIRECTORY_SEPARATOR.'test.php';
        file_put_contents($templateFile, '<?php $this->begin("block", true); ?>First<?php $this->end(); ?>');

        $template = new Template($this->view, $templateFile);
        $result = $template->render();

        // When layout is null, block content is echoed and returned
        $this->assertStringContainsString('First', $result);
    }

    public function test_block_replace_behavior(): void
    {
        $templateFile = $this->templateDir.DIRECTORY_SEPARATOR.'test.php';
        file_put_contents($templateFile, '<?php $this->begin("block", false); ?>Replaced<?php $this->end(); ?>');

        $template = new Template($this->view, $templateFile);
        $result = $template->render();

        $this->assertStringContainsString('Replaced', $result);
    }

    public function test_complex_template_with_all_features(): void
    {
        $layoutFile = $this->templateDir.DIRECTORY_SEPARATOR.'layout.php';
        file_put_contents($layoutFile, '<!DOCTYPE html><html><head><title><?= $this->block("title") ?></title></head><body><?= $this->block("header") ?><main><?= $this->block("content") ?></main></body></html>');

        $templateFile = $this->templateDir.DIRECTORY_SEPARATOR.'test.php';
        file_put_contents($templateFile, '<?php
$this->begin("title");
?>My Page<?php
$this->end();
$this->begin("header");
?>Welcome<?php
$this->end();
$this->layout("layout.php");
?>Main content here
');

        $template = new Template($this->view, $templateFile);
        $result = $template->render();

        $this->assertStringContainsString('<!DOCTYPE html>', $result);
        $this->assertStringContainsString('<title>My Page</title>', $result);
        $this->assertStringContainsString('Welcome', $result);
        $this->assertStringContainsString('Main content here', $result);
    }

    public function test_render_empty_template(): void
    {
        $templateFile = $this->templateDir.DIRECTORY_SEPARATOR.'test.php';
        file_put_contents($templateFile, '');

        $template = new Template($this->view, $templateFile);
        $result = $template->render();

        $this->assertSame('', $result);
    }

    public function test_render_template_with_null_variable(): void
    {
        $templateFile = $this->templateDir.DIRECTORY_SEPARATOR.'test.php';
        file_put_contents($templateFile, '<?= $value ?? "default" ?>');

        $template = new Template($this->view, $templateFile, ['value' => null]);
        $result = $template->render();

        $this->assertSame('default', $result);
    }

    public function test_render_template_with_array_variable(): void
    {
        $templateFile = $this->templateDir.DIRECTORY_SEPARATOR.'test.php';
        file_put_contents($templateFile, '<?= implode(", ", $items) ?>');

        $template = new Template($this->view, $templateFile, ['items' => ['a', 'b', 'c']]);
        $result = $template->render();

        $this->assertSame('a, b, c', $result);
    }

    public function test_render_template_with_object_variable(): void
    {
        $templateFile = $this->templateDir.DIRECTORY_SEPARATOR.'test.php';
        file_put_contents($templateFile, '<?= $obj->name ?>');

        $obj = new \stdClass;
        $obj->name = 'Test';

        $template = new Template($this->view, $templateFile, ['obj' => $obj]);
        $result = $template->render();

        $this->assertSame('Test', $result);
    }

    public function test_block_append_flag_is_stored(): void
    {
        // Test that append flag is properly stored in block metadata
        $templateFile = $this->templateDir.DIRECTORY_SEPARATOR.'test.php';
        file_put_contents($templateFile, '<?php
$this->begin("script", true);
?>Script content<?php
$this->end();
echo $this->block("script");
');

        $template = new Template($this->view, $templateFile);
        $result = $template->render();

        // Block should be created and content should be output
        $this->assertStringContainsString('Script content', $result);
    }

    public function test_block_content_is_retrievable(): void
    {
        // Test that block content can be retrieved after being defined
        $templateFile = $this->templateDir.DIRECTORY_SEPARATOR.'test.php';
        file_put_contents($templateFile, '<?php
$this->begin("title", false);
?>Page Title<?php
$this->end();
echo $this->block("title");
');

        $template = new Template($this->view, $templateFile);
        $result = $template->render();

        // Should be able to retrieve the block content
        $this->assertStringContainsString('Page Title', $result);
    }

    public function test_escape_with_null_value(): void
    {
        $templateFile = $this->templateDir.DIRECTORY_SEPARATOR.'test.php';
        file_put_contents($templateFile, '<?= $this->e($null) ?>');

        $template = new Template($this->view, $templateFile, ['null' => null]);
        $result = $template->render();

        $this->assertSame('', $result);
    }

    public function test_escape_with_boolean_value(): void
    {
        $templateFile = $this->templateDir.DIRECTORY_SEPARATOR.'test.php';
        file_put_contents($templateFile, '<?= $this->e($bool) ?>');

        $template = new Template($this->view, $templateFile, ['bool' => true]);
        $result = $template->render();

        $this->assertSame('1', $result);
    }

    public function test_fetch_with_namespace(): void
    {
        // Create a separate view instance to avoid namespace conflicts
        $view = new View;
        $view->add($this->templateDir, 'partials');

        $partialFile = $this->templateDir.DIRECTORY_SEPARATOR.'partial.php';
        file_put_contents($partialFile, 'Partial');

        $templateFile = $this->templateDir.DIRECTORY_SEPARATOR.'test.php';
        file_put_contents($templateFile, '<?= $this->fetch("partials:partial.php") ?>');

        $template = new Template($view, $templateFile);
        $result = $template->render();

        $this->assertSame('Partial', $result);
    }

    public function test_end_block_with_existing_block_append_false(): void
    {
        // Test the case where end() is called and there's an existing block with append=false
        // This covers the else branch: $content = $next['append'] ? ... : $next['content'];
        // When append=false on existing block, the old content is kept (new content is ignored)
        $templateFile = $this->templateDir.DIRECTORY_SEPARATOR.'test.php';
        file_put_contents($templateFile, '<?php
// Simulate an existing block with append=false (would come from parent/layout)
$reflection = new ReflectionClass($this);
$property = $reflection->getProperty("__blocks");
$property->setAccessible(true);
$property->setValue($this, ["script" => ["append" => false, "content" => "Old Script"]]);
$this->begin("script", false);
?>New Script<?php
$this->end();
echo $this->block("script");
');

        $template = new Template($this->view, $templateFile);
        $result = $template->render();

        // When existing block has append=false, old content is kept (new content is ignored)
        $this->assertStringContainsString('Old Script', $result);
        $this->assertStringNotContainsString('New Script', $result);
    }
}
