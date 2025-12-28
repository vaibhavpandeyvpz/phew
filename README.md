# vaibhavpandeyvpz/phew

A simple, lightweight, and native PHP view engine with support for template inheritance (layouts), blocks, partials, and namespaces. Built with just 2 core classes and zero dependencies.

[![Latest Version](https://img.shields.io/packagist/v/vaibhavpandeyvpz/phew.svg?style=flat-square)](https://packagist.org/packages/vaibhavpandeyvpz/phew)
[![Downloads](https://img.shields.io/packagist/dt/vaibhavpandeyvpz/phew.svg?style=flat-square)](https://packagist.org/packages/vaibhavpandeyvpz/phew)
[![PHP Version](https://img.shields.io/packagist/php-v/vaibhavpandeyvpz/phew.svg?style=flat-square)](https://packagist.org/packages/vaibhavpandeyvpz/phew)
[![License](https://img.shields.io/packagist/l/vaibhavpandeyvpz/phew.svg?style=flat-square)](LICENSE)
[![Build Status](https://img.shields.io/github/actions/workflow/status/vaibhavpandeyvpz/phew/tests.yml?branch=master&style=flat-square)](https://github.com/vaibhavpandeyvpz/phew/actions)

## Features

- 🚀 **Lightweight**: Just 2 core classes, zero dependencies
- 📦 **Native PHP**: No compilation, no special syntax to learn
- 🎨 **Layout Inheritance**: Extend parent templates with blocks
- 🧩 **Partials**: Reusable template fragments
- 📁 **Namespaces**: Organize templates with namespaces
- 🔧 **Extensible**: Register custom functions and extensions
- ✅ **Type Safe**: Full PHP 8.2+ type hints and strict types
- 🧪 **Well Tested**: 100% code coverage

## Requirements

- PHP 8.2 or higher

## Installation

Install via Composer:

```bash
composer require vaibhavpandeyvpz/phew
```

## Quick Start

### Basic Usage

```php
<?php

use Phew\View;

// Create a view instance
$view = new View();

// Add template folder
$view->add(__DIR__ . '/templates');

// Render a template
echo $view->fetch('welcome.php', ['name' => 'World']);
```

**templates/welcome.php:**

```php
Hello, <?= $name ?>!
```

### Using ArrayAccess for Variables

```php
<?php

use Phew\View;

$view = new View();
$view->add(__DIR__ . '/templates');

// Set variables using array syntax
$view['title'] = 'My Page';
$view['user'] = ['name' => 'John', 'email' => 'john@example.com'];

// Variables are available in all templates
echo $view->fetch('page.php');
```

**templates/page.php:**

```php
<h1><?= $title ?></h1>
<p>Welcome, <?= $user['name'] ?>!</p>
```

## Layout Inheritance

Layouts allow you to define a base template and inject content into it:

**templates/layout.php:**

```php
<!DOCTYPE html>
<html>
<head>
    <title><?= $this->block('title') ?></title>
</head>
<body>
    <header><?= $this->block('header') ?></header>
    <main><?= $this->block('content') ?></main>
</body>
</html>
```

**templates/page.php:**

```php
<?php
$this->begin('title');
?>My Page Title<?php
$this->end();

$this->begin('header');
?>Welcome<?php
$this->end();

$this->layout('layout.php');
?>
<p>This is the main content.</p>
```

## Blocks

Blocks allow you to define reusable content sections:

```php
<?php
// Define a block
$this->begin('sidebar');
?>
<ul>
    <li>Item 1</li>
    <li>Item 2</li>
</ul>
<?php
$this->end();

// Use the block later
echo $this->block('sidebar');
```

### Block Append Mode

You can append content to existing blocks:

```php
<?php
// First definition
$this->begin('scripts', true);
?>console.log('First script');<?php
$this->end();

// Appends to existing block
$this->begin('scripts', true);
?>console.log('Second script');<?php
$this->end();

// Outputs both scripts
echo $this->block('scripts');
```

## Partials

Partials are reusable template fragments:

**templates/partials/header.php:**

```php
<header>
    <h1><?= $title ?></h1>
    <nav>...</nav>
</header>
```

**templates/page.php:**

```php
<?= $this->fetch('partials/header.php', ['title' => 'My Site']) ?>
<main>Content here</main>
```

## Namespaces

Organize templates using namespaces:

```php
<?php

$view = new View();

// Add folders with namespaces
$view->add(__DIR__ . '/admin/templates', 'admin');
$view->add(__DIR__ . '/public/templates', 'public');

// Use namespace when fetching
echo $view->fetch('admin:dashboard.php');
echo $view->fetch('public:home.php');
```

## Custom Functions

Register custom functions that can be called from templates:

```php
<?php

$view = new View();
$view->add(__DIR__ . '/templates');

// Register a function
$view->register('uppercase', fn(string $str) => strtoupper($str));
$view->register('formatDate', function($date) {
    return date('Y-m-d', strtotime($date));
});
```

**templates/page.php:**

```php
<p><?= $this->uppercase('hello world') ?></p>
<p><?= $this->formatDate('2024-01-01') ?></p>
```

## HTML Escaping

Use the `e()` method to escape HTML output:

```php
<?php
$userInput = '<script>alert("XSS")</script>';
?>

<p><?= $this->e($userInput) ?></p>
<!-- Outputs: &lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt; -->
```

## Extensions

Create extensions to add custom functionality:

```php
<?php

use Phew\ExtensionInterface;
use Phew\ViewInterface;

class MyExtension implements ExtensionInterface
{
    public function extend(ViewInterface $view): void
    {
        $view->register('myFunction', fn() => 'Hello from extension!');
    }
}

$view = new View();
$view->install(new MyExtension());
```

## Error Handling

The engine throws `Phew\ViewException` when errors occur:

```php
<?php

use Phew\View;
use Phew\ViewException;

try {
    $view = new View();
    echo $view->fetch('nonexistent.php');
} catch (ViewException $e) {
    echo 'Template error: ' . $e->getMessage();
}
```

## Advanced Examples

### Complex Layout with Multiple Blocks

**templates/layout.php:**

```php
<!DOCTYPE html>
<html>
<head>
    <title><?= $this->block('title') ?> - <?= $siteName ?></title>
    <?= $this->block('head') ?>
</head>
<body>
    <?= $this->block('header') ?>
    <main><?= $this->block('content') ?></main>
    <?= $this->block('footer') ?>
</body>
</html>
```

**templates/article.php:**

```php
<?php
$this->begin('title');
?>Article Title<?php
$this->end();

$this->begin('head');
?><meta name="description" content="Article description"><?php
$this->end();

$this->begin('header');
?><?= $this->fetch('partials/nav.php') ?><?php
$this->end();

$this->begin('content');
?>
<article>
    <h1><?= $article['title'] ?></h1>
    <p><?= $article['content'] ?></p>
</article>
<?php
$this->end();

$this->begin('footer');
?><?= $this->fetch('partials/footer.php') ?><?php
$this->end();

$this->layout('layout.php', ['siteName' => 'My Blog']);
```

## API Reference

### View Class

- `add(string $folder, string $ns = ''): static` - Add a template folder
- `fetch(string $template, ?array $vars = null): string` - Render a template
- `find(string $template): string` - Find template path
- `register(string $function, callable $callback): static` - Register a function
- `invoke(string $function, array $arguments): mixed` - Invoke a registered function
- `install(ExtensionInterface $extension): static` - Install an extension
- Implements `ArrayAccess` for variable management

### Template Methods (available in templates)

- `$this->begin(string $name, bool $append = false): void` - Start a block
- `$this->end(): void` - End current block
- `$this->block(string $name): string` - Get block content
- `$this->layout(string $name, ?array $vars = null): void` - Set layout
- `$this->fetch(string $name, ?array $vars = null): string` - Render partial
- `$this->e(mixed $value): string` - Escape HTML
- `$this->{function}(...$args)` - Call registered function

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Support

For issues, questions, or feature requests, please use the [GitHub issue tracker](https://github.com/vaibhavpandeyvpz/phew/issues).
