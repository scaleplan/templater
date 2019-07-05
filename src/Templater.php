<?php

namespace Scaleplan\Templater;

use PhpQuery\PhpQueryObject;
use PhpQuery\PhpQuery;
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
    public const ATTR_HTML            = 'html';
    public const ATTR_CLASS           = 'class';
    public const ATTR_TEXT            = 'text';
    public const ATTR_VAL             = 'val';
    public const ATTR_VALUE           = 'value';
    public const ATTR_ID              = 'id';
    public const ATTR_SRC             = 'src';
    public const ATTR_TITLE           = 'title';
    public const ATTR_HREF            = 'href';
    public const ATTR_DATA_OBJECT_SRC = 'data-object-src';
    public const ATTR_DATA_TYPE       = 'data-type';
    public const ATTR_DATA_FILE_TYPE  = 'data-file-type';
    public const ATTR_DATA_FORM       = 'data-form';
    public const ATTR_DATA_SRC        = 'data-src';
    public const ATTR_DATA_ACCOUNT_ID = 'data-account-id';
    public const ATTR_DATA_HREF       = 'data-href';

    /**
     * Атрибуты, в которые можно вставлять значения
     */
    public const ALLOWED_ATTRS = [
        self::ATTR_HTML,
        self::ATTR_CLASS,
        self::ATTR_TEXT,
        self::ATTR_VAL,
        self::ATTR_VALUE,
        self::ATTR_ID,
        self::ATTR_SRC,
        self::ATTR_TITLE,
        self::ATTR_HREF,
        self::ATTR_DATA_OBJECT_SRC,
        self::ATTR_DATA_TYPE,
        self::ATTR_DATA_FILE_TYPE,
        self::ATTR_DATA_FORM,
        self::ATTR_DATA_SRC,
        self::ATTR_DATA_ACCOUNT_ID,
        self::ATTR_DATA_HREF,
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
    protected $includesAttribute = 'data-includes';

    /**
     * @var string
     */
    protected $includesTypesAttribute = 'data-includes-types';

    /**
     * @var string
     */
    protected $defaultIncludeType = 'prepend';

    /**
     * @var array
     */
    protected $forbiddenSelectors;

    /**
     * @var bool
     */
    protected $renderByMustache = true;

    /**
     * @var string
     */
    protected $dataInAttribute = 'data-in';

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
     * @throws \PhpQuery\Exceptions\PhpQueryException
     * @throws \Scaleplan\Helpers\Exceptions\EnvNotFoundException
     * @throws \Exception
     */
    public function renderIncludes() : void
    {
        static $privateViewsPath, $publicViewsPath;
        if (!$privateViewsPath) {
            $locale = locale_accept_from_http($_SERVER['HTTP_ACCEPT_LANGUAGE']) ?: get_required_env('DEFAULT_LANG');
            $privateViewsPath = get_required_env('BUNDLE_PATH')
                . get_required_env('VIEWS_PATH')
                . get_required_env('PRIVATE_TEMPLATES_PATH')
                . '/' . $locale;

            $publicViewsPath = get_required_env('BUNDLE_PATH')
                . get_required_env('VIEWS_PATH')
                . get_required_env('PUBLIC_TEMPLATES_PATH')
                . '/' . $locale;
        }

        $this->getTemplate()->find("[$this->includesAttribute]")->each(function ($element)
        use ($privateViewsPath, $publicViewsPath) {
            $element = PhpQuery::pq($element);
            $paths = explode(', ', $element->attr($this->includesAttribute));
            $includeTypes = explode(', ', $element->attr($this->includesTypesAttribute));
            $includeType = $includeTypes[0] ?: $this->defaultIncludeType;
            foreach ($paths as $index => $path) {
                $path = "$path.html";
                $tplPath = file_exists($privateViewsPath . '/' . $path)
                    ? $privateViewsPath . '/' . $path
                    : $publicViewsPath . '/' . $path;
                $includeType = $includeTypes[$index] ?: $includeType;
                $element->$includeType(file_get_contents($tplPath));
            }
        });
    }

    /**
     * Удалить запрещенные к показу селекторы
     *
     * @throws \PhpQuery\Exceptions\PhpQueryException
     * @throws \Exception
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
     * @return PhpQueryObject
     *
     * @throws \Exception
     */
    public function getTemplate() : PhpQueryObject
    {
        static $template;
        if (!$template) {
            $template = PhpQuery::newDocumentFileHTML($this->templatePath);
        }

        return $template;
    }

    /**
     * Вставить в шаблон несколько однородных записей (при этом на каждую запись создается копия DOM-объекта-родителя)
     *
     * @param array $data - данные для вставки
     * @param string|PhpQueryObject $parent
     *
     * @return PhpQueryObject
     *
     * @throws DomElementNotFountException
     * @throws \PhpQuery\Exceptions\PhpQueryException
     * @throws \Exception
     */
    public function setMultiData(array $data, $parent) : PhpQueryObject
    {
        if (\is_string($parent) && !($parent = $this->getTemplate()->find($parent))->length) {
            throw new DomElementNotFountException();
        }

        if (!$this->isShowNoData($data, $parent)) {
            return $parent->parent();
        }

        if (empty($data[0])) {
            $data = [$data];
        }

        $parent->find('[data-depends-on]')->each(function($element) use ($data) {
            $element = PhpQuery::pq($element);
            $this->dataDependsCheck($data[0], $element);
        });

        if (!$parent->hasClass($this->cloneClassName)) {
            $this->setData($data, $parent);
            return $parent->parent();
        }

        return $this->fillingMultiData($data, $parent);
    }

    /**
     * @param array $data
     * @param PhpQueryObject $parent
     *
     * @return PhpQueryObject
     * @throws DomElementNotFountException
     * @throws \PhpQuery\Exceptions\PhpQueryException
     */
    protected function fillingMultiData(array $data, PhpQueryObject $parent) : PhpQueryObject
    {
        $clone = $parent->clone();
        if ($this->renderByMustache && count($data) > 1) {
            $mustacheTpl = (string)$this->setData($data[0], $clone, true);
            foreach ($data as $row) {
                if (!\is_array($row)) {
                    continue;
                }

                $filledTpl = urldecode($mustacheTpl);
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
     * @param PhpQueryObject $element
     * @param string $key - имя элемента для вставки
     * @param string $value - значение для вставки
     *
     * @return PhpQueryObject
     *
     * @throws \PhpQuery\Exceptions\PhpQueryException
     */
    protected function modifyElement(&$element, string &$key, ?string &$value) : PhpQueryObject
    {
        $matches = array_map('trim', explode(',', (string)$element->attr("{$this->dataInAttribute}-$key")));

        if (!array_filter($matches)) {
            return $element;
        }

        if ($this->isInsertable([static::ATTR_HTML,], $matches)) {
            $element->html($value);
        }

        if ($this->isInsertable([static::ATTR_TEXT,], $matches)) {
            $element->text($value);
        }

        if ($this->isInsertable([static::ATTR_CLASS,], $matches)) {
            $element->addClass($value);
        }

        if ($this->isInsertable([static::ATTR_HREF,], $matches)) {
            $element->attr('href', str_replace("{{$key}}", $value, $element->attr(static::ATTR_HREF)));
        }

        if ($this->isInsertable([static::ATTR_DATA_HREF,], $matches)) {
            $element->attr('data-href', str_replace("{{$key}}", $value, $element->attr(static::ATTR_DATA_HREF)));
        }

        if ($this->isInsertable([static::ATTR_VAL, static::ATTR_VALUE,], $matches)) {
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
     * @param string|PhpQueryObject $parent
     * @param bool $generateMustache
     *
     * @return PhpQueryObject
     *
     * @throws DomElementNotFountException
     * @throws \PhpQuery\Exceptions\PhpQueryException
     * @throws \Exception
     */
    public function setData(array $data, &$parent, bool $generateMustache = false) : PhpQueryObject
    {
        if (\is_string($parent) && !($parent = $this->getTemplate()->find($parent))->length) {
            throw new DomElementNotFountException();
        }

        $parent->removeClass($this->cloneClassName);

        if (isset($data[0])) {
            $data = $data[0];
        }

        foreach ($data AS $key => $value) {
            if (!$this->dataDependsCheck($data, $parent, $key)) {
                return $parent;
            }

            if ($generateMustache) {
                $value = "{{$key}}";
            }

            if (\is_array($value)) {
                $this->setMultiData($value, $parent->find(".$key{$this->subparentSelector}"));
                continue;
            }

            $this->modifyElement($parent, $key, $value);

            $parent->find("[{$this->dataInAttribute}-$key]")->each(function ($element) use ($key, $value) {
                $element = PhpQuery::pq($element);
                $this->modifyElement($element, $key, $value);
            });
        }

        return $parent;
    }

    /**
     * Если данных нет, то прячет зависимые от этих данных элементы
     *
     * @param array $data
     * @param PhpQueryObject $element
     * @param string|null $key
     *
     * @return bool
     *
     * @throws \PhpQuery\Exceptions\PhpQueryException
     */
    protected function dataDependsCheck(array $data, PhpQueryObject $element, string $key = null) : bool
    {
        if (null !== $key) {
            $data = $data[$key];
        }

        if ($element->is('[data-depends-on]')) {
            $dependsParent = $element;
        } else {
            $dependsParent = $element->parents('[data-depends-on]');
        }

        if (!$dependsParent->length) {
            return true;
        }

        $dependsKey = $dependsParent->attr('data-depends-on');
        if (\is_array($data) && !array_key_exists($dependsKey, $data)) {
            $dependsParent->remove();
            return false;
        }

        if ($dependsKey === $key) {
            $dependsParent->addClass($this->noDisplayClass);
            return false;
        }

        return true;
    }

    /**
     * Показать блок с сообщением об отсутствии данных, если данных нет
     *
     * @param array $data - данные
     * @param PhpQueryObject $parent
     *
     * @return bool
     *
     * @throws \PhpQuery\Exceptions\PhpQueryException
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
