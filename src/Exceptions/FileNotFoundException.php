<?php

namespace Scaleplan\Templater\Exceptions;

use function Scaleplan\Translator\translate;

/**
 * Class FileNotFoundException
 *
 * @package Scaleplan\Templater\Exceptions
 */
class FileNotFoundException extends TemplaterException
{
    public const MESSAGE = 'templater.tpl-file-not-found';
    public const CODE = 404;

    /**
     * FileNotFoundException constructor.
     *
     * @param string $filePath
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
    public function __construct(string $filePath, string $message = '', int $code = 0, \Throwable $previous = null)
    {
        parent::__construct(
            translate($message ?: static::MESSAGE, ['file_path' => $filePath,]),
            static::CODE ?: $code,
            $previous
        );
    }
}
