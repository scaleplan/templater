<?php

namespace Scaleplan\Templater\Exceptions;

/**
 * Class FileNotFountException
 *
 * @package Scaleplan\Templater\Exceptions
 */
class FileNotFountException extends TemplaterException
{
    public const MESSAGE = 'Template file not found.';
    public const CODE = 404;
}
