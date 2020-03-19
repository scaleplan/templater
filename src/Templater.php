<?php
declare(strict_types=1);

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
    public const ATTR_SRC       = 'src';
    public const ATTR_CHECKED   = 'checked';
    public const ATTR_SELECTED  = 'selected';

    public const INCLUDE_TYPE_PREPEND = 'prepend';
    public const INCLUDE_TYPE_APPEND  = 'append';
    public const INCLUDE_TYPE_AFTER   = 'after';
    public const INCLUDE_TYPE_BEFORE  = 'before';
    public const INCLUDE_TYPE_INSTEAD = 'instead';
    public const INCLUDE_TYPE_INTO    = 'into';

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
     * @var string
     */
    protected $defaultAttributePrefix = 'data-default-';

    /**
     * @var array
     */
    protected $currentDefaults = [];

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
            if (property_exists($this, $setting)) {
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
            $locale = \Locale::acceptFromHttp((string)($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''))
                ?: get_required_env('DEFAULT_LANG');
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
            throw new TemplaterException("Файл шаблона '$tplPath' не существует.");
        }

        return $tplPath;
    }

    /**
     * @param string|PhpQueryObject $selectorOrElement
     *
     * @return array
     *
     * @throws DomElementNotFountException
     * @throws \PhpQuery\Exceptions\PhpQueryException
     * @throws \Exception
     */
    public function getIncludes($selectorOrElement) : array
    {
        if (!($selectorOrElement instanceof PhpQueryObject)) {
            $selectorOrElement = $this->getTemplate()->find($selectorOrElement);
        }

        if (!$selectorOrElement->count()) {
            throw new DomElementNotFountException();
        }

        return [
            array_filter(array_map('trim', explode(',', $selectorOrElement->attr($this->includesAttribute) ?? ''))),
            array_filter(array_map('trim', explode(',', $selectorOrElement->attr($this->includesTypesAttribute) ?? ''))),
        ];
    }

    /**
     * @param $selectorOrElement
     * @param array $includes
     *
     * @param array $includesTypes
     *
     * @throws DomElementNotFountException
     * @throws \PhpQuery\Exceptions\PhpQueryException
     * @throws \Exception
     */
    public function setIncludes($selectorOrElement, array $includes, array $includesTypes) : void
    {
        if (!($selectorOrElement instanceof PhpQueryObject)) {
            $selectorOrElement = $this->getTemplate()->find($selectorOrElement);
        }

        if (!$selectorOrElement->count()) {
            throw new DomElementNotFountException();
        }

        $selectorOrElement->attr($this->includesAttribute, implode(',', $includes));
        $selectorOrElement->attr($this->includesTypesAttribute, implode(',', $includesTypes));
    }

    /**
     * @param $selectorOrElement
     * @param array $includes
     *
     * @param array $includesTypes
     *
     * @throws DomElementNotFountException
     * @throws \PhpQuery\Exceptions\PhpQueryException
     */
    public function addIncludes($selectorOrElement, array $includes, array $includesTypes) : void
    {
        [$presentIncludes, $presentIncludesTypes] = $this->getIncludes($selectorOrElement);
        $this->setIncludes(
            $selectorOrElement,
            array_merge($presentIncludes, $includes),
            array_merge($presentIncludesTypes, $includesTypes)
        );
    }

    /**
     * @param $selectorOrElement
     * @param array $toRemove
     *
     * @throws DomElementNotFountException
     * @throws \PhpQuery\Exceptions\PhpQueryException
     */
    public function removeIncludes($selectorOrElement, array $toRemove) : void
    {
        [$presentIncludes, $presentIncludesTypes] = $this->getIncludes($selectorOrElement);
        $newIncludes = $presentIncludes;
        $newIncludesTypes = $presentIncludesTypes;
        foreach ($toRemove as $toRemoveInclude) {
            if (false === ($index = array_search($toRemoveInclude, $presentIncludes, true))) {
                continue;
            }

            unset($newIncludes[$index], $newIncludesTypes[$index]);
        }

        $this->setIncludes($selectorOrElement, $newIncludes, $newIncludesTypes);
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
            $paths = array_filter(array_map(
                'trim',
                explode(',', $element->attr($this->includesAttribute) ?? '')
            ));
            $includeTypes = array_filter(array_map(
                'trim',
                explode(',', $element->attr($this->includesTypesAttribute) ?? '')
            ));
            $includeType = $includeTypes[0] ?: $this->defaultIncludeType;
            foreach ($paths as $index => $path) {
                $tplPath = static::getTplPath($path, $this->userRole);

                if (!empty($includeTypes[$index])) {
                    $includeType = $includeTypes[$index];
                }

                switch ($includeType) {
                    case static::INCLUDE_TYPE_PREPEND:
                    case static::INCLUDE_TYPE_APPEND:
                    case static::INCLUDE_TYPE_AFTER:
                    case static::INCLUDE_TYPE_BEFORE:
                        $element->$includeType(file_get_contents($tplPath));
                        break;

                    case static::INCLUDE_TYPE_INSTEAD:
                        $element->replaceWith(file_get_contents($tplPath));
                        break;

                    case static::INCLUDE_TYPE_INTO:
                        $element->html(file_get_contents($tplPath));
                        break;
                }
            }

            $element->removeAttr($this->includesAttribute);
            $element->removeAttr($this->includesTypesAttribute);
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
                throw new FileNotFountException($this->templatePath);
            }

            $this->template = PhpQuery::newDocumentFileHTML($this->templatePath);
        }

        return $this->template;
    }

    /**
     * @param PhpQueryObject $template
     */
    public function setTemplate(PhpQueryObject $template) : void
    {
        $this->template = $template;
    }

    /**
     * @param array $data
     * @param $parent
     *
     * @return PhpQueryObject|null
     *
     * @throws \PhpQuery\Exceptions\PhpQueryException
     */
    public function setOptionalMultiData(array $data, $parent) : ?PhpQueryObject
    {
        try {
            return $this->setMultiData($data, $parent);
        } catch (DomElementNotFountException $e) {
            return null;
        }
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
        if (\is_string($parent) && ($selector = $parent) && !($parent = $this->getTemplate()->find($parent))->count()) {
            throw new DomElementNotFountException($selector);
        }

        if (!$parent->count()) {
            return $parent;
        }

        $this->dataDependsCheck($data, $parent);
        $this->isShowNoData($data, $parent);

        if (!$data) {
            return $parent;
        }

        if (!$parent->hasClass($this->cloneClassName)) {
            $this->setData($data, $parent);
            return $parent;
        }

        $this->fillingMultiData($data, $parent);

        return $parent;
    }

    /**
     * @param array $data
     * @param PhpQueryObject $parent
     */
    protected function fillingMultiData(array $data, PhpQueryObject $parent) : void
    {
        $parent->each(function ($element) use ($data) {
            $element = PhpQuery::pq($element);
            if ($this->renderByMustache && count($data) > 1) {
                $mustacheTpl = $this->setData($data[0], $element->clone(), true);
                foreach ($data as $row) {
                    if (!\is_array($row)) {
                        continue;
                    }

                    $filledTpl = urldecode((string)$mustacheTpl);
                    foreach ($row as $field => $value) {
                        if (null === $value || '' === $value) {
                            $value = $this->currentDefaults[$field] ?? '';
                        }

                        if (\is_array($value)) {
                            $filledTpl = PhpQuery::pq($filledTpl);
                            $this->setMultiData($value, $filledTpl->find(".$field{$this->subparentSelector}"));
//                            $filledTpl->find()->each(function ($subparent) use (&$value, &$filledTpl) {
//                                $subparent = PhpQuery::pq($subparent);
//                                $newTpl = $this->setMultiData($value, $subparent);
//                                $filledTpl = str_replace((string)$subparent, (string)$newTpl, urldecode((string)$filledTpl));
//                            });
                            $filledTpl = urldecode((string)$filledTpl);
                            continue;
                        }

                        $filledTpl = str_replace("{{$field}}", $value, $filledTpl);
                    }

                    $filledTpl = PhpQuery::pq($filledTpl);
                    $element->after($filledTpl);
                    $element = $filledTpl;
                }

                return;
            }

            foreach ($data as $row) {
                if (!\is_array($row)) {
                    continue;
                }

                $clone = $this->setData($row, $element->clone());
                $element->after($clone);
                $element = $clone;
            }
        });
    }

    /**
     * Проверка на наличеие метки вставки
     *
     * @param array $labels - метка
     * @param array $matches - массив совпадений
     *
     * @return array
     */
    protected function isInsertable(array $labels, array &$matches) : array
    {
        if ($existedLabels = array_intersect($labels, $matches)) {
            return [array_diff($matches, $labels), $existedLabels];
        }

        return [$matches, []];
    }

    /**
     * Заполнение элемента данными
     *
     * @param PhpQueryObject $elements
     * @param string $key - имя элемента для вставки
     * @param $value - значение для вставки
     *
     * @return PhpQueryObject
     */
    protected function modifyElement(PhpQueryObject $elements, string &$key, &$value) : PhpQueryObject
    {
        $elements->each(function ($element) use ($key, $value) {

            $element = PhpQuery::pq($element);
            $matches = array_filter(array_map('trim', explode(
                ',',
                (string)$element->attr("{$this->dataInAttribute}-$key")
            )));

            if (!$matches) {
                return $element;
            }

            if (null === $value || '' === $value) {
                $value = $element->attr("{$this->defaultAttributePrefix}$key") ?? '';
            }

            if ($this->isInsertable([static::ATTR_HTML,], $matches)[1]) {
                $element->html($value);
            }

            if ($this->isInsertable([static::ATTR_TEXT,], $matches)[1]) {
                $element->text(is_string($value) ? strip_tags($value) : $value);
            }

            if ($this->isInsertable([static::ATTR_CLASS,], $matches)[1]) {
                $element->addClass($value);
            }

            if ($this->isInsertable([static::ATTR_CHECKED], $matches)[1]) {
                if ($value) {
                    $element->attr(static::ATTR_CHECKED, static::ATTR_CHECKED);
                } else {
                    $element->removeAttr(static::ATTR_CHECKED);
                }
            }

            if ($this->isInsertable([static::ATTR_SELECTED], $matches)[1]) {
                if ($value) {
                    $element->attr(static::ATTR_SELECTED, static::ATTR_SELECTED);
                } else {
                    $element->removeAttr(static::ATTR_SELECTED);
                }
            }

            foreach ($matches as $attr) {
                $attrValue = $element->attr($attr);
                if ($attrValue && strpos($attrValue, "{{$key}}") !== false) {
                    $element->attr($attr, str_replace("{{$key}}", $value, $attrValue));
                    continue;
                }

                $element->attr($attr, $value);
            }

            return $element;
        });

        return $elements;
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
    public function setData(array $data, $parent, bool $generateMustache = false) : PhpQueryObject
    {
        if (\is_string($parent) && ($selector = $parent) && !($parent = $this->getTemplate()->find($parent))->count()) {
            throw new DomElementNotFountException($selector);
        }

        $parent->removeClass($this->cloneClassName);

        if (isset($data[0])) {
            $data = $data[0];
        }

        if ($generateMustache) {
            $this->currentDefaults = [];
        }

        foreach ($data AS $key => $value) {
            if ($generateMustache) {
                $defaultAttr = "{$this->defaultAttributePrefix}$key";
                $this->currentDefaults[$key] = $parent
                    ->find("[$defaultAttr]")
                    ->attr($defaultAttr);
                $value = "{{$key}}";
            }

            if (\is_array($value)) {
                $this->setMultiData($value, $parent->find(".$key{$this->subparentSelector}"));
                continue;
            }

            $this->modifyElement($parent, $key, $value);

            $parent->find("[{$this->dataInAttribute}-$key]")->each(function ($element)
            use ($key, $value, $generateMustache) {
                $element = PhpQuery::pq($element);
                if (!$generateMustache && !$this->dataDependsCheck($value, $element)) {
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
        if (null !== $data && (!is_array($data) || (is_array($data) && [] !== $data))) {
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
        if (!($p = $parent->parents($this->parentSelector))->count()
            || !($noDataElement = $p->children($this->noDataSelector))->count()) {
            return true;
        }

        if (!$data) {
            $noDataElement
                ->removeClass($this->noDisplayClass)
                ->siblings('*:not(.clone)')->addClass($this->noDisplayClass);
            return false;
        }

        $noDataElement
            ->addClass($this->noDisplayClass)
            ->siblings('*:not(.clone)')->removeClass($this->noDisplayClass);

        return true;
    }
}
