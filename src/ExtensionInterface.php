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
 * Interface ExtensionInterface
 *
 * Interface for creating extensions that can extend the view engine's functionality.
 * Extensions can register functions, modify behavior, or add new features.
 */
interface ExtensionInterface
{
    /**
     * Extend the view engine with additional functionality.
     *
     * This method is called when the extension is installed via View::install().
     * The extension should use this opportunity to register functions, modify
     * the view instance, or perform any setup needed.
     *
     * @param  ViewInterface  $view  The view instance to extend
     */
    public function extend(ViewInterface $view): void;
}
