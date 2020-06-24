<?php

namespace Scaleplan\Templater\Exceptions;

/**
 * Class FilePathNotSetException
 *
 * @package Scaleplan\Templater\Exceptions
 */
class FilePathNotSetException extends TemplaterException
{
    public const MESSAGE = 'templater.tmp-path-set-failed';
    public const CODE = 404;
}
