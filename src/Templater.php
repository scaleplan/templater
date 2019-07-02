<?php

namespace Scaleplan\Templater;

use function Scaleplan\Helpers\get_required_env;
use Scaleplan\Templater\Exceptions\DomElementNotFountException;
use Scaleplan\Templater\Exceptions\FileNotFountException;

/**
 * Шаблонизатор
 *
 * Class Templater
 *
 * @package Scaleplan\Templater
 */
class Templater implements TemplaterInterface
{
    /**
     * Атрибуты, в которые можно вставлять значения
     */
    public const ALLOWED_ATTRS = [
        'class',
        'text',
        'val',
        'value',
        'id',
        'src',
        'title',
        'href',
        'data-object-src',
        'data-type',
        'data-file-type',
        'data-form',
        'data-src',
        'data-object-src',
        'data-account-id',
        'data-href',
    ];

    /**
     * @var array
     */
    protected $settings;

    /**
     * Шаблон/страница
     *
     * @var string
     */
    protected $templatePath;

    /**
     * Класс css, указывающий на то что элемент нужно копировать для вставки данных
     *
     * @var string
     */
    protected $cloneClassName = 'clone';

    /**
     * CSS-класс для скрытия элементов
     *
     * @var string
     */
    protected $noDisplayClass = 'no-display';

    /**
     * Селектор корня узла для вставки данных
     *
     * @var string
     */
    protected $parentSelector = '.parent';

    /**
     * Селектор блока с сообщением об отсутствии данных
     *
     * @var string
     */
    protected $noDataSelector = '.no-data';

    /**
     * Селектор для рекурсивных вставок
     *
     * @var string
     */
    protected $subparentSelector = '.subparent';

    /**
     * @var string
     */
    protected $includeAttribute = 'data-include';

    /**
     * @var array
     */
    protected $forbiddenSelectors;

    /**
     * @var bool
     */
    protected $renderByMustache = true;

    /**
     * Установка конфигурации объекта
     *
     * @param array $settings - настройки
     */
    public function init(array $settings) : void
    {
        foreach ($settings as $setting => $value) {
            if (isset($this->{$setting})) {
                $this->{$setting} = $value;
            }
        }

        $this->settings = $settings;
    }

    /**
     * Импортирования компоненты представления
     *
     * @throws \Scaleplan\Helpers\Exceptions\EnvNotFoundException
     */
    public function renderIncludes() : void
    {
        static $privateViewsPath, $publicViewsPath;
        if (!$privateViewsPath) {
            $privateViewsPath = get_required_env('BUNDLE_PATH')
                . get_required_env('VIEWS_PATH')
                . get_required_env('PRIVATE_TEMPLATES_PATH');

            $publicViewsPath = get_required_env('BUNDLE_PATH')
                . get_required_env('VIEWS_PATH')
                . get_required_env('PUBLIC_TEMPLATES_PATH');
        }

        $this->getTemplate()->find("[$this->includeAttribute]")->each(function ($element)
        use ($privateViewsPath, $publicViewsPath) {
            $element = pq($element);
            $tplPath = $privateViewsPath . '/' . $element->attr($this->includeAttribute);
            if (!file_exists($tplPath)) {
                $tplPath = $publicViewsPath . '/' . $element->attr($this->includeAttribute);
            }

            $element->html(file_get_contents($tplPath));
        });
    }

    /**
     * Удалить запрещенные к показу селекторы
     */
    public function removeForbidden() : void
    {
        if (!$this->forbiddenSelectors || !\is_array($this->forbiddenSelectors)) {
            return;
        }

        $forbiddenSelector = implode(', ', $this->forbiddenSelectors);
        $this->getTemplate()->find($forbiddenSelector)->remove();
    }

    /**
     * Конструктор
     *
     * @param string $tplPath - имя файла шаблона
     * @param array $settings - настройки
     *
     * @throws FileNotFountException
     */
    public function __construct(string $tplPath, array $settings = [])
    {
        if (!file_exists($tplPath)) {
            throw new FileNotFountException();
        }

        $this->templatePath = $tplPath;
        $this->init($settings);
    }

    /**
     * Вернуть шаблон/страницу
     *
     * @return \phpQueryObject
     */
    public function getTemplate() : \phpQueryObject
    {
        static $template;
        if (!$template) {
            $template = \phpQuery::newDocumentFileHTML($this->templatePath);
        }

        return $template;
    }

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
    public function setMultiData(array $data, $parent) : \phpQueryObject
    {
        if (\is_string($parent) && !($parent = $this->getTemplate()->find($parent))->length) {
            throw new DomElementNotFountException();
        }

        if (!$parent->hasClass($this->cloneClassName)) {
            $this->setData($data, $parent);
            return $parent->parent();
        }

        if (empty($data[0])) {
            $data = [$data];
        }

        if (!$this->dataDependsCheck($data[0], $parent)) {
            return $parent->parent();
        }

        return $this->fillingMultiData($data, $parent);
    }

    /**
     * @param array $data
     * @param \phpQueryObject $parent
     *
     * @return \phpQueryObject
     *
     * @throws DomElementNotFountException
     */
    protected function fillingMultiData(array $data, \phpQueryObject $parent) : \phpQueryObject
    {
        $clone = $parent->clone();
        if ($this->renderByMustache && count($data) > 1) {
            $mustacheTpl = (string)$this->setData($data[0], $clone, true);
            foreach ($data as $row) {
                if (!\is_array($row)) {
                    continue;
                }

                $filledTpl = $mustacheTpl;
                foreach ($row as $field => $value) {
                    $filledTpl = str_replace("{{$field}}", $value, $filledTpl);
                }

                $parent->after($filledTpl);
            }

            return $parent->parent();
        }

        foreach ($data as $row) {
            if (!\is_array($row)) {
                continue;
            }

            $parent->after($clone);
            $this->setData($row, $clone);
        }

        return $parent->parent();
    }

    /**
     * Проверка на наличеие метки вставки
     *
     * @param array $labels - метка
     * @param array $matches - массив совпадений
     *
     * @return bool
     */
    protected function isInsertable(array $labels, array &$matches) : bool
    {
        if (array_intersect($labels, $matches)) {
            $matches = array_diff($matches, $labels);
            return true;
        }

        return false;
    }

    /**
     * Заполнение элемента данными
     *
     * @param \phpQueryObject $element
     * @param string $key - имя элемента для вставки
     * @param string $value - значение для вставки
     *
     * @return \phpQueryObject
     */
    protected function modifyElement(&$element, string &$key, ?string &$value) : \phpQueryObject
    {
        if (!$this->dataDependsCheck($value, $element)) {
            return $element;
        }

        $pattern = '/in_(' . implode('|', static::ALLOWED_ATTRS) . ")_$key/i";
        if (!preg_match_all($pattern, $element->attr('class'), $matches)) {
            return $element;
        }

        $matches = $matches[1];

        if ($this->isInsertable(['html'], $matches)) {
            $element->html($value);
        }

        if ($this->isInsertable(['text'], $matches)) {
            $element->text($value);
        }

        if ($this->isInsertable(['class'], $matches)) {
            $element->addClass($value);
        }

        if ($this->isInsertable(['href',], $matches)) {
            $element->attr('href', str_replace("{{$key}}", $value, $element->attr('href')));
        }

        if ($this->isInsertable(['data-href'], $matches)) {
            $element->attr('data-href', str_replace("{{$key}}", $value, $element->attr('data-href')));
        }

        if ($this->isInsertable(['val', 'value'], $matches)) {
            $element->val($value);
        }

        foreach ($matches as $attr) {
            $element->attr($attr, $value);
        }

        return $element;
    }

    /**
     * Вставить данные в DOM-объект шаблона
     *
     * @param array $data - данные для вставки
     * @param string|\phpQueryObject $parent
     * @param bool $generateMustache
     *
     * @return \phpQueryObject
     *
     * @throws DomElementNotFountException
     */
    public function setData(array $data, &$parent, bool $generateMustache = false) : \phpQueryObject
    {
        if (\is_string($parent) && !($parent = $this->getTemplate()->find($parent))->length) {
            throw new DomElementNotFountException();
        }

        $parent->removeClass($this->cloneClassName);

        if (isset($data[0])) {
            $data = $data[0];
        }

        if (!$this->dataDependsCheck($data, $parent)) {
            return $parent;
        }

        if (!$this->isShowNoData($data, $parent)) {
            return $parent;
        }

        foreach ($data AS $key => $value) {
            if ($generateMustache) {
                $value = "{{$key}}";
            }

            if (\is_array($value)) {
                $this->setMultiData($value, $parent->find(".$key.{$this->subparentSelector}"));
                continue;
            }

            $this->modifyElement($parent, $key, $value);

            $parent->find("[class*=_$key]")->each(function ($element) use ($key, $value) {
                $element = pq($element);
                $this->modifyElement($element, $key, $value);
            });
        }

        return $parent;
    }

    /**
     * Если данных нет, то прячет зависимые от этих данных элементы
     *
     * @param $data - данные
     * @param \phpQueryObject $element
     *
     * @return bool
     */
    protected function dataDependsCheck(&$data, &$element) : bool
    {
        if ($element->is('[data-depends-on]')) {
            $dependsParent = $element;
        } else {
            $dependsParent = $element->parents('[data-depends-on]');
        }

        if (!$dependsParent->length) {
            return true;
        }

        $dependsSelector = $dependsParent->attr('data-depends-on');
        if ($dependsSelector === 'all' || $element->is($dependsSelector)) {
            if ($dependsParent->is('[data-hide-if]')) {
                if ($dependsParent->attr('data-hide-if') == $data) {
                    $dependsParent->addClass($this->noDisplayClass);
                    return false;
                }
            } elseif (null === $data || (\is_array($data) && !$data)) {
                $dependsParent->addClass($this->noDisplayClass);
                return false;
            }
        }

        return true;
    }

    /**
     * Показать блок с сообщением об отсутствии данных, если данных нет
     *
     * @param array $data - данные
     * @param \phpQueryObject $parent
     *
     * @return bool
     */
    protected function isShowNoData(array &$data, &$parent) : bool
    {
        if (!$data) {
            if (!($parent = $parent->parents($this->parentSelector))->length
                || !($noDataElement = $parent->children($this->noDataSelector))->length) {
                return true;
            }

            $parent->children()->addClass($this->noDisplayClass);
            $noDataElement->removeClass($this->noDisplayClass);
            return false;
        }

        return true;
    }

}
