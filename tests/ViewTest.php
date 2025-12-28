<?php

declare(strict_types=1);

namespace Phew\Tests;

use Phew\ExtensionInterface;
use Phew\View;
use Phew\ViewException;
use Phew\ViewInterface;
use PHPUnit\Framework\TestCase;

/**
 * Test suite for the View class.
 *
 * Tests template folder management, template finding, rendering,
 * function registration, extension installation, and ArrayAccess implementation.
 */
class ViewTest extends TestCase
{
    /**
     * Temporary directory for test template files.
     */
    private string $templateDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->templateDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'phew_tests_'.uniqid();
        mkdir($this->templateDir, 0777, true);
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

    public function test_add_folder_without_namespace(): void
    {
        $view = new View;
        $result = $view->add($this->templateDir);

        $this->assertSame($view, $result);
    }

    public function test_add_folder_with_namespace(): void
    {
        $view = new View;
        $result = $view->add($this->templateDir, 'admin');

        $this->assertSame($view, $result);
    }

    public function test_add_multiple_folders_to_same_namespace(): void
    {
        $view = new View;
        $dir1 = $this->templateDir.DIRECTORY_SEPARATOR.'dir1';
        $dir2 = $this->templateDir.DIRECTORY_SEPARATOR.'dir2';
        mkdir($dir1, 0777, true);
        mkdir($dir2, 0777, true);

        $view->add($dir1, 'ns');
        $view->add($dir2, 'ns');

        // Create file in second directory
        $templateFile = $dir2.DIRECTORY_SEPARATOR.'test.php';
        file_put_contents($templateFile, 'Hello');

        // Should find in second directory (searches in order)
        $result = $view->find('ns:test.php');
        $this->assertSame($templateFile, $result);
    }

    public function test_add_folder_trims_trailing_separator(): void
    {
        $view = new View;
        $view->add($this->templateDir.DIRECTORY_SEPARATOR);

        $templateFile = $this->templateDir.DIRECTORY_SEPARATOR.'test.php';
        file_put_contents($templateFile, 'Hello');

        $result = $view->find('test.php');
        $this->assertSame($templateFile, $result);
    }

    public function test_find_template_without_namespace(): void
    {
        $view = new View;
        $view->add($this->templateDir);

        $templateFile = $this->templateDir.DIRECTORY_SEPARATOR.'test.php';
        file_put_contents($templateFile, 'Hello');

        $result = $view->find('test.php');
        $this->assertSame($templateFile, $result);
    }

    public function test_find_template_with_namespace(): void
    {
        $view = new View;
        $view->add($this->templateDir, 'admin');

        $templateFile = $this->templateDir.DIRECTORY_SEPARATOR.'test.php';
        file_put_contents($templateFile, 'Hello');

        $result = $view->find('admin:test.php');
        $this->assertSame($templateFile, $result);
    }

    public function test_find_template_with_subdirectory(): void
    {
        $view = new View;
        $view->add($this->templateDir);

        $subDir = $this->templateDir.DIRECTORY_SEPARATOR.'sub';
        mkdir($subDir, 0777, true);
        $templateFile = $subDir.DIRECTORY_SEPARATOR.'test.php';
        file_put_contents($templateFile, 'Hello');

        $result = $view->find('sub/test.php');
        $this->assertSame($templateFile, $result);
    }

    public function test_find_template_with_leading_separator(): void
    {
        $view = new View;
        $view->add($this->templateDir);

        $templateFile = $this->templateDir.DIRECTORY_SEPARATOR.'test.php';
        file_put_contents($templateFile, 'Hello');

        $result = $view->find(DIRECTORY_SEPARATOR.'test.php');
        $this->assertSame($templateFile, $result);
    }

    public function test_find_template_throws_exception_when_not_found(): void
    {
        $view = new View;
        $view->add($this->templateDir);

        $this->expectException(ViewException::class);
        $this->expectExceptionMessage("Could not locate 'nonexistent.php' template in defined folders.");

        $view->find('nonexistent.php');
    }

    public function test_find_template_throws_exception_when_namespace_not_found(): void
    {
        $view = new View;
        $view->add($this->templateDir, 'admin');

        $this->expectException(ViewException::class);
        $this->expectExceptionMessage("Could not locate 'test.php' template in defined folders.");

        $view->find('nonexistent:test.php');
    }

    public function test_find_template_with_colon_at_start_is_treated_as_no_namespace(): void
    {
        $view = new View;
        $view->add($this->templateDir);

        $templateFile = $this->templateDir.DIRECTORY_SEPARATOR.':test.php';
        file_put_contents($templateFile, 'Hello');

        // Colon at position 0 is not treated as namespace separator
        $result = $view->find(':test.php');
        $this->assertSame($templateFile, $result);
    }

    public function test_fetch_template(): void
    {
        $view = new View;
        $view->add($this->templateDir);

        $templateFile = $this->templateDir.DIRECTORY_SEPARATOR.'test.php';
        file_put_contents($templateFile, 'Hello <?= $name ?>');

        $result = $view->fetch('test.php', ['name' => 'World']);
        $this->assertSame('Hello World', $result);
    }

    public function test_fetch_template_merges_vars(): void
    {
        $view = new View;
        $view->add($this->templateDir);
        $view['global'] = 'Global';

        $templateFile = $this->templateDir.DIRECTORY_SEPARATOR.'test.php';
        file_put_contents($templateFile, '<?= $global ?> <?= $local ?>');

        $result = $view->fetch('test.php', ['local' => 'Local']);
        $this->assertSame('Global Local', $result);
    }

    public function test_fetch_template_with_null_vars(): void
    {
        $view = new View;
        $view->add($this->templateDir);
        $view['name'] = 'World';

        $templateFile = $this->templateDir.DIRECTORY_SEPARATOR.'test.php';
        file_put_contents($templateFile, 'Hello <?= $name ?>');

        $result = $view->fetch('test.php', null);
        $this->assertSame('Hello World', $result);
    }

    public function test_register_function(): void
    {
        $view = new View;
        $result = $view->register('uppercase', fn (string $str) => strtoupper($str));

        $this->assertSame($view, $result);
    }

    public function test_invoke_registered_function(): void
    {
        $view = new View;
        $view->register('add', fn (int $a, int $b) => $a + $b);

        $result = $view->invoke('add', [5, 3]);
        $this->assertSame(8, $result);
    }

    public function test_invoke_throws_exception_when_function_not_registered(): void
    {
        $view = new View;

        $this->expectException(ViewException::class);
        $this->expectExceptionMessage("Function 'nonexistent' does not exist in template context.");

        $view->invoke('nonexistent', []);
    }

    public function test_install_extension(): void
    {
        $view = new View;
        $extension = new class implements ExtensionInterface
        {
            public bool $extended = false;

            public function extend(ViewInterface $view): void
            {
                $this->extended = true;
            }
        };

        $result = $view->install($extension);

        $this->assertSame($view, $result);
        $this->assertTrue($extension->extended);
    }

    public function test_offset_exists(): void
    {
        $view = new View;
        $view['test'] = 'value';

        $this->assertTrue(isset($view['test']));
        $this->assertFalse(isset($view['nonexistent']));
    }

    public function test_offset_get(): void
    {
        $view = new View;
        $view['name'] = 'World';

        $this->assertSame('World', $view['name']);
    }

    public function test_offset_set(): void
    {
        $view = new View;
        $view['test'] = 'value';

        $this->assertSame('value', $view['test']);
    }

    public function test_offset_set_with_null_offset(): void
    {
        $view = new View;
        // When using [] syntax, PHP calls offsetSet with null as the offset
        // ArrayAccess allows this and should use the next integer key
        $view[null] = 'value';

        // Check that the value was stored (PHP will use null as the key)
        $this->assertTrue(isset($view[null]));
        $this->assertSame('value', $view[null]);
    }

    public function test_offset_unset(): void
    {
        $view = new View;
        $view['test'] = 'value';

        unset($view['test']);

        $this->assertFalse(isset($view['test']));
    }

    public function test_array_access_integration(): void
    {
        $view = new View;
        $view['name'] = 'John';
        $view['age'] = 30;

        $this->assertSame('John', $view['name']);
        $this->assertSame(30, $view['age']);

        unset($view['age']);
        $this->assertFalse(isset($view['age']));
    }

    public function test_fetch_trims_leading_whitespace(): void
    {
        $view = new View;
        $view->add($this->templateDir);

        $templateFile = $this->templateDir.DIRECTORY_SEPARATOR.'test.php';
        file_put_contents($templateFile, "\n\nHello");

        $result = $view->fetch('test.php');
        $this->assertSame('Hello', $result);
    }
}
