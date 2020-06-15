<?php

namespace Scaleplan\Templater\Exceptions;

/**
 * Class FilePathNotSetException
 *
 * @package Scaleplan\Templater\Exceptions
 */
class FilePathNotSetException extends TemplaterException
{
    public const MESSAGE = 'Невозможно установить путь к шаблону.';
    public const CODE = 404;
}
