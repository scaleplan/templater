<?php

namespace Scaleplan\Templater;

use PhpQuery\PhpQuery;
use PhpQuery\PhpQueryObject;
use Scaleplan\Templater\Exceptions\DomElementNotFountException;
use Scaleplan\Templater\Exceptions\FileNotFountException;
use Scaleplan\Templater\Exceptions\FilePathNotSetException;
use Scaleplan\Templater\Exceptions\TemplaterException;
use function Scaleplan\Helpers\get_required_env;

/**
 * Шаблонизатор
 *
 * Class Templater
 *
 * @package Scaleplan\Templater
 */
class Templater implements TemplaterInterface
{
    public const ATTR_HTML      = 'html';
    public const ATTR_CLASS     = 'class';
    public const ATTR_TEXT      = 'text';
    public const ATTR_VAL       = 'val';
    public const ATTR_VALUE     = 'value';
    public const ATTR_HREF      = 'href';
    public const ATTR_DATA_HREF = 'data-href';
    public const ATTR_ACTION    = 'action';

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
     * @var string
     */
    protected $userRole;

    /**
     * @var string
     */
    protected $dataDependsOnAttribute = 'data-depends-on';

    /**
     * @var PhpQueryObject
     */
    protected $template;

    /**
     * Конструктор
     *
     * @param string $tplPath - имя файла шаблона
     * @param array $settings - настройки
     */
    public function __construct(string $tplPath = null, array $settings = [])
    {
        $this->templatePath = $tplPath;
        $this->init($settings);
    }

    /**
     * @param string $userRole
     */
    public function setUserRole(string $userRole) : void
    {
        $this->userRole = $userRole;
    }

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
     * @param string $path
     * @param string|null $userRole
     *
     * @return string
     *
     * @throws TemplaterException
     * @throws \Scaleplan\Helpers\Exceptions\EnvNotFoundException
     */
    public static function getTplPath(string $path, string $userRole = null) : string
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

        $filePath = "$path.html";

        $pathArray = explode('/', $path);
        $tplName = array_pop($pathArray);
        $fileDirectory = implode('/', $pathArray);
        $roleFilePath = "$fileDirectory/$userRole-$tplName.html";

        $tplPath = file_exists($privateViewsPath . '/' . $roleFilePath)
            ? $privateViewsPath . '/' . $roleFilePath
            : $publicViewsPath . '/' . $roleFilePath;

        if (!file_exists($tplPath)) {
            $tplPath = file_exists($privateViewsPath . '/' . $filePath)
                ? $privateViewsPath . '/' . $filePath
                : $publicViewsPath . '/' . $filePath;
        }

        if (!file_exists($tplPath)) {
            throw new TemplaterException('Файл шаблона не существует.');
        }

        return $tplPath;
    }

    /**
     * Импортирования компоненты представления
     *
     * @throws \PhpQuery\Exceptions\PhpQueryException
     * @throws \Exception
     */
    public function renderIncludes() : void
    {
        $this->getTemplate()->find("[$this->includesAttribute]")->each(function ($element) {
            $element = PhpQuery::pq($element);
            $paths = array_map('trim', explode(',', $element->attr($this->includesAttribute)));
            $includeTypes = explode(', ', $element->attr($this->includesTypesAttribute));
            $includeType = $includeTypes[0] ?: $this->defaultIncludeType;
            foreach ($paths as $index => $path) {
                $tplPath = static::getTplPath($path, $this->userRole);

                if (!empty($includeTypes[$index])) {
                    $includeType = $includeTypes[$index];
                }

                switch ($includeType) {
                    case 'prepend':
                    case 'append':
                    case 'after':
                    case 'before':
                        $element->$includeType(file_get_contents($tplPath));
                        break;

                    case 'instead':
                        $element->replaceWith(file_get_contents($tplPath));
                        break;

                    case 'into':
                        $element->html(file_get_contents($tplPath));
                        break;
                }

                $element->removeAttr($this->includesAttribute);
                $element->removeAttr($this->includesTypesAttribute);
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
     * Вернуть шаблон/страницу
     *
     * @return PhpQueryObject
     *
     * @throws \Exception
     */
    public function getTemplate() : PhpQueryObject
    {
        if (!$this->template) {
            if (!$this->templatePath) {
                throw new FilePathNotSetException();
            }

            if (!file_exists($this->templatePath)) {
                throw new FileNotFountException();
            }

            $this->template = PhpQuery::newDocumentFileHTML($this->templatePath);
        }

        return $this->template;
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
        if (\is_string($parent) && !($parent = $this->getTemplate()->find($parent))->count()) {
            throw new DomElementNotFountException();
        }

        if (!$this->isShowNoData($data, $parent)) {
            return $parent->parent();
        }

        $this->dataDependsCheck($data, $parent);

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
     * @param $value - значение для вставки
     *
     * @return PhpQueryObject
     *
     * @throws \PhpQuery\Exceptions\PhpQueryException
     */
    protected function modifyElement(PhpQueryObject $element, string &$key, &$value) : PhpQueryObject
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
            $element->attr(static::ATTR_HREF, str_replace("{{$key}}", $value, $element->attr(static::ATTR_HREF)));
        }

        if ($this->isInsertable([static::ATTR_ACTION,], $matches)) {
            $element->attr(static::ATTR_ACTION, str_replace("{{$key}}", $value, $element->attr(static::ATTR_ACTION)));
        }

        if ($this->isInsertable([static::ATTR_DATA_HREF,], $matches)) {
            $element->attr(
                static::ATTR_DATA_HREF,
                str_replace("{{$key}}", $value, $element->attr(static::ATTR_DATA_HREF))
            );
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
        if (\is_string($parent) && !($parent = $this->getTemplate()->find($parent))->count()) {
            throw new DomElementNotFountException();
        }

        $parent->removeClass($this->cloneClassName);

        if (isset($data[0])) {
            $data = $data[0];
        }

        foreach ($data AS $key => $value) {
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
                if (!$this->dataDependsCheck($value, $element)) {
                    return;
                }

                $this->modifyElement($element, $key, $value);
            });
        }

        return $parent;
    }

    /**
     * Если данных нет, то прячет зависимые от этих данных элементы
     *
     * @param $data
     * @param PhpQueryObject $element
     *
     * @return bool
     *
     * @throws \PhpQuery\Exceptions\PhpQueryException
     */
    protected function dataDependsCheck($data, PhpQueryObject $element) : bool
    {
        if ($data) {
            return true;
        }

        if ($element->is("[{$this->dataDependsOnAttribute}]")) {
            $dependsParents = $element;
        } else {
            $dependsParents = $element->parents("[{$this->dataDependsOnAttribute}]")
                ->filter(function ($parent) use ($element) {
                    $parent = PhpQuery::pq($parent);
                    return $element->is($parent->attr($this->dataDependsOnAttribute));
                });
        }

        if (!$dependsParents->count()) {
            return true;
        }

        $dependsParents->addClass($this->noDisplayClass);

        return false;
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
    protected function isShowNoData(array &$data, PhpQueryObject $parent) : bool
    {
        if (!$data) {
            if (!($p = $parent->parents($this->parentSelector))->count()
                || !($noDataElement = $p->children($this->noDataSelector))->count()) {
                return true;
            }

            $p->children()->addClass($this->noDisplayClass);
            $noDataElement->removeClass($this->noDisplayClass);
            return false;
        }

        return true;
    }
}
