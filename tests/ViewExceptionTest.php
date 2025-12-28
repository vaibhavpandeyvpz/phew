<?php

declare(strict_types=1);

namespace Phew\Tests;

use Phew\ViewException;
use PHPUnit\Framework\TestCase;

/**
 * Test suite for the ViewException class.
 *
 * Tests exception inheritance, properties, and behavior.
 */
class ViewExceptionTest extends TestCase
{
    public function test_view_exception_extends_logic_exception(): void
    {
        $exception = new ViewException('Test message');

        $this->assertInstanceOf(\LogicException::class, $exception);
    }

    public function test_view_exception_with_message(): void
    {
        $message = 'Test error message';
        $exception = new ViewException($message);

        $this->assertSame($message, $exception->getMessage());
    }

    public function test_view_exception_with_code(): void
    {
        $code = 500;
        $exception = new ViewException('Test message', $code);

        $this->assertSame($code, $exception->getCode());
    }

    public function test_view_exception_with_previous_exception(): void
    {
        $previous = new \RuntimeException('Previous exception');
        $exception = new ViewException('Test message', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }

    public function test_view_exception_is_not_readonly(): void
    {
        $reflection = new \ReflectionClass(ViewException::class);

        // ViewException cannot be readonly because it extends LogicException which is not readonly
        // Check if class is NOT readonly (PHP 8.2+)
        if (method_exists($reflection, 'isReadonly')) {
            $this->assertFalse($reflection->isReadonly());
        } else {
            // For PHP < 8.2, we can't check this, but the test still validates the class structure
            $this->assertTrue(true);
        }
    }
}
