<?php

namespace Scaleplan\Templater\Exceptions;

/**
 * Class TemplaterException
 *
 * @package Scaleplan\Templater\Exceptions
 */
class TemplaterException extends \Exception
{
    public const MESSAGE = 'Ошибка шаблонизатора.';
    public const CODE = 500;

    /**
     * TemplaterException constructor.
     *
     * @param string|null $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(string $message = '', int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message ?: static::MESSAGE, $code ?: static::CODE, $previous);
    }
}
