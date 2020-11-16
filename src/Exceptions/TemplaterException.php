<?php

namespace Scaleplan\Templater\Exceptions;

use function Scaleplan\Translator\translate;

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
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     *
     * @throws \ReflectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ContainerTypeNotSupportingException
     * @throws \Scaleplan\DependencyInjection\Exceptions\DependencyInjectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ParameterMustBeInterfaceNameOrClassNameException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ReturnTypeMustImplementsInterfaceException
     */
    public function __construct(string $message = '', int $code = 0, \Throwable $previous = null)
    {
        parent::__construct(
            $message ?: translate(static::MESSAGE) ?: static::MESSAGE,
            $code ?: static::CODE,
            $previous
        );
    }
}
