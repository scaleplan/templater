<?php

namespace Scaleplan\Templater\Exceptions;

/**
 * Class DomElementNotFountException
 *
 * @package Scaleplan\Templater\Exceptions
 */
class DomElementNotFountException extends TemplaterException
{
    public const MESSAGE = 'DOM element ":selector" not found.';
    public const CODE = 404;

    /**
     * DomElementNotFountException constructor.
     *
     * @param string $selector
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(string $selector = null, string $message = '', int $code = 0, \Throwable $previous = null)
    {
        parent::__construct(
            str_replace(':selector', $selector, $message ?: static::MESSAGE),
            $code ?: static::CODE,
            $previous
        );
    }
}
