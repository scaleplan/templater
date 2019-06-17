<?php

namespace Scaleplan\Templater\Exceptions;

/**
 * Class DomElementNotFountException
 *
 * @package Scaleplan\Templater\Exceptions
 */
class DomElementNotFountException extends TemplaterException
{
    public const MESSAGE = 'DOM element not found.';
    public const CODE = 404;
}
