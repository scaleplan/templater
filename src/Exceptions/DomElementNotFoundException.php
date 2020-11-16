<?php

namespace Scaleplan\Templater\Exceptions;

use function Scaleplan\Translator\translate;

/**
 * Class DomElementNotFoundException
 *
 * @package Scaleplan\Templater\Exceptions
 */
class DomElementNotFoundException extends TemplaterException
{
    public const MESSAGE = 'templater.element-not-found';
    public const CODE = 404;

    /**
     * DomElementNotFoundException constructor.
     *
     * @param string|null $selector
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
    public function __construct(string $selector = null, string $message = '', int $code = 0, \Throwable $previous = null)
    {
        parent::__construct(
            translate($message ?: static::MESSAGE, ['selector' => $selector,]),
            $code ?: static::CODE,
            $previous
        );
    }
}
