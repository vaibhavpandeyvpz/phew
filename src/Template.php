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
 * Class Template
 * @package Phew
 */
class Template implements TemplateInterface
{
    /**
     * @var array|null
     */
    protected $__block;

    /**
     * @var array
     */
    protected $__blocks = array();

    /**
     * @var array|null
     */
    protected $__layout;

    /**
     * @var string
     */
    protected $__path;

    /**
     * @var array
     */
    protected $__vars;

    /**
     * @var ViewInterface
     */
    protected $__view;

    /**
     * Template constructor.
     * @param ViewInterface $view
     * @param string $path
     * @param array|null $vars
     */
    public function __construct(ViewInterface $view, $path, array $vars = null)
    {
        $this->__view = $view;
        $this->__path = $path;
        $this->__vars = (array)$vars;
        if (isset($this->__vars['this'])) {
            unset($this->__vars['this']);
        }
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call($name, array $arguments)
    {
        return $this->__view->invoke($name, $arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function begin($name, $append = false)
    {
        if ('content' === $name) {
            throw new ViewException("Block name '{$name}' is reserved.");
        } elseif (null !== $this->__block) {
            throw new ViewException('Nested blocks inside blocks is not yet allowed.');
        }
        $this->__block = compact('name', 'append');
        ob_start();
    }

    /**
     * {@inheritdoc}
     */
    public function block($name)
    {
        return isset($this->__blocks[$name]) ? $this->__blocks[$name]['content'] : '';
    }

    /**
     * @param string $value
     * @return string
     */
    public function e($value)
    {
        return htmlspecialchars($value, ENT_QUOTES);
    }

    /**
     * {@inheritdoc}
     */
    public function end()
    {
        if (null === $this->__block) {
            throw new ViewException('You must begin a block before you end.');
        }
        extract($this->__block);
        /**
         * @var string $name
         * @var bool $append
         */
        $block = array(
            'append' => $append,
            'content' => ob_get_clean(),
        );
        if (isset($this->__blocks[$name])) {
            $next = $this->__blocks[$name];
            if ($next['append']) {
                $block['content'] .= $next['content'];
            } else {
                $block['content'] = $next['content'];
            }
        }
        $this->__blocks[$name] = $block;
        $this->__block = null;
    }

    /**
     * {@inheritdoc}
     */
    public function fetch($name, array $vars = null)
    {
        $path = $this->__view->find($name);
        $vars = array_merge($this->__vars, (array)$vars);
        $partial = new Template($this->__view, $path, $vars);
        return $partial->render();
    }

    /**
     * {@inheritdoc}
     */
    public function layout($name, array $vars = null)
    {
        $this->__layout = compact('name', 'vars');
    }

    /**
     * {@inheritdoc}
     */
    public function render()
    {
        ob_start();
        extract($this->__vars, EXTR_SKIP);
        /** @noinspection PhpIncludeInspection */
        include $this->__path;
        $content = ob_get_clean();
        if (null !== $this->__layout) {
            $path = $this->__view->find($this->__layout['name']);
            $vars = array_merge($this->__vars, (array)$this->__layout['vars']);
            $layout = new Template($this->__view, $path, $vars);
            $layout->__blocks = $this->__blocks;
            $layout->__blocks['content'] = array(
                'append' => false,
                'content' => $content,
            );
            $content = $layout->render();
            $this->__layout = null;
        }
        return ltrim($content);
    }
}
