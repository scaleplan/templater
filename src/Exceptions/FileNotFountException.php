<?php

namespace Scaleplan\Templater\Exceptions;

/**
 * Class FileNotFountException
 *
 * @package Scaleplan\Templater\Exceptions
 */
class FileNotFountException extends TemplaterException
{
    public const MESSAGE = 'Template file ":file_path" not found.';
    public const CODE = 404;

    /**
     * FileNotFountException constructor.
     *
     * @param string $filePath
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(string $filePath, string $message = '', int $code = 0, \Throwable $previous = null)
    {
        parent::__construct(str_replace(':file_path', $filePath, $message ?: static::MESSAGE), $code, $previous);
    }
}
