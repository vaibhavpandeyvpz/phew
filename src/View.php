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
 * Class View
 * @package Phew
 */
class View implements ViewInterface
{
    /**
     * @var array
     */
    protected $vars = array();

    /**
     * @var array
     */
    protected $functions;

    /**
     * @var array
     */
    protected $folders = array();

    /**
     * {@inheritdoc}
     */
    public function add($folder, $ns = '')
    {
        $folder = rtrim($folder, DIRECTORY_SEPARATOR);
        if (isset($this->folders[$ns])) {
            $this->folders[$ns][] = $folder;
        } else {
            $this->folders[$ns] = array($folder);
        }
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function fetch($template, array $vars = null)
    {
        $path = $this->find($template);
        $vars = array_merge($this->vars, (array)$vars);
        $template = new Template($this, $path, $vars);
        return ltrim($template->render());
    }

    /**
     * {@inheritdoc}
     */
    public function find($template)
    {
        $folders = array();
        if (strpos($template, ':') > 0) {
            list($ns, $template) = explode(':', $template, 2);
            if (isset($this->folders[$ns])) {
                $folders = $this->folders[$ns];
            }
        } elseif (isset($this->folders[''])) {
            $folders = $this->folders[''];
        }
        foreach ($folders as $folder) {
            $path = $folder . DIRECTORY_SEPARATOR . ltrim($template, DIRECTORY_SEPARATOR);
            if (is_file($path)) {
                return $path;
            }
        }
        throw new ViewException("Could not locate '{$template}' template in defined folders.");
    }

    /**
     * {@inheritdoc}
     */
    public function install(ExtensionInterface $extension)
    {
        $extension->extend($this);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function invoke($function, array $arguments)
    {
        if (isset($this->functions[$function])) {
            $callback = $this->functions[$function];
            return call_user_func_array($callback, $arguments);
        }
        throw new ViewException("Function '{$function}' does not exist in template context.");
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return isset($this->vars[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->vars[$offset];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        $this->vars[$offset] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        unset($this->vars[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function register($function, $callback)
    {
        $this->functions[$function] = $callback;
        return $this;
    }
}
