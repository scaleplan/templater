<?php

namespace Scaleplan\Templater;

use PhpQuery\PhpQueryObject;
use Scaleplan\Templater\Exceptions\DomElementNotFountException;

/**
 * Шаблонизатор
 *
 * Class Templater
 *
 * @package Scaleplan\Templater
 */
interface TemplaterInterface
{
    /**
     * Установка конфигурации объекта
     *
     * @param array $settings - настройки
     */
    public function init(array $settings) : void;

    /**
     * Импортирования компоненты представления
     */
    public function renderIncludes() : void;

    /**
     * Удалить запрещенные к показу селекторы
     */
    public function removeForbidden() : void;

    /**
     * Вернуть шаблон/страницу
     *
     * @return PhpQueryObject
     */
    public function getTemplate() : PhpQueryObject;

    /**
     * Вставить в шаблон несколько однородных записей (при этом на каждую запись создается копия DOM-объекта-родителя)
     *
     * @param array $data - данные для вставки
     * @param string|PhpQueryObject $parent
     *
     * @return PhpQueryObject
     *
     * @throws DomElementNotFountException
     */
    public function setMultiData(array $data, $parent) : PhpQueryObject;

    /**
     * Вставить данные в DOM-объект шаблона
     *
     * @param array $data - данные для вставки
     * @param string|PhpQueryObject $parent
     *
     * @return PhpQueryObject
     *
     * @throws DomElementNotFountException
     */
    public function setData(array $data, $parent) : PhpQueryObject;
}
