<?php

namespace Scaleplan\Templater\Exceptions;

/**
 * Class FilePathNotSetException
 *
 * @package Scaleplan\Templater\Exceptions
 */
class FilePathNotSetException extends TemplaterException
{
    public const MESSAGE = 'Template path not set.';
    public const CODE = 404;
}
