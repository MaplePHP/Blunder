<?php

/**
 * Class BlunderErrorException
 *
 * Extends PHP's ErrorException with support for a custom "pretty" message,
 * and allows preserving original exception file and line information when
 * rethrowing exceptions with modified types.
 *
 * Useful for enhanced exception handling, presentation, and debugging
 * within the Blunder framework.
 *
 * @package    MaplePHP\Blunder
 * @author     Daniel Ronkainen
 * @license    Apache-2.0 license, Copyright © Daniel Ronkainen
 *             Don't delete this comment, it's part of the license.
 */

namespace MaplePHP\Blunder\Exceptions;

use ErrorException;

final class BlunderSoftException extends ErrorException
{
}
