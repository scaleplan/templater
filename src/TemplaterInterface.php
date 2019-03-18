<?php

namespace Scaleplan\Templater;

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
     * @return \phpQueryObject
     */
    public function getTemplate() : \phpQueryObject;

    /**
     * Вставить в шаблон несколько однородных записей (при этом на каждую запись создается копия DOM-объекта-родителя)
     *
     * @param array $data - данные для вставки
     * @param string|\phpQueryObject $parent
     *
     * @return \phpQueryObject
     *
     * @throws DomElementNotFountException
     */
    public function setMultiData(array $data, $parent) : \phpQueryObject;

    /**
     * Вставить данные в DOM-объект шаблона
     *
     * @param array $data - данные для вставки
     * @param string|\phpQueryObject $parent
     *
     * @return \phpQueryObject
     *
     * @throws DomElementNotFountException
     */
    public function setData(array $data, &$parent) : \phpQueryObject;
}
