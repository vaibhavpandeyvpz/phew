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
 * Class ViewException
 *
 * Exception thrown by the view engine when errors occur during template
 * operations such as template not found, invalid block operations,
 * or function invocation errors.
 */
class ViewException extends \LogicException {}
