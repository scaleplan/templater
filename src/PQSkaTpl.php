<?php

namespace avtomon;

use phpQuery;

/**
 * Класс ошибок
 *
 * Class PQSkaTplException
 * @package avtomon
 */
class PQSkaTplException extends CustomException
{
}

/**
 * Шаблонизатор
 *
 * Class PQSkaTpl
 * @package avtomon
 */
class PQSkaTpl
{
    /**
     * Шаблон/страница
     *
     * @var string|\phpQueryObject|\QueryTemplatesParse|\QueryTemplatesSource|\QueryTemplatesSourceQuery
     */
    protected $template = '';

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
     * Атрибуты, в которые можно вставлять значения
     *
     * @var array
     */
    protected $allowedAttrs = [
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
        'data-object-src'
    ];

    /**
     * Установка конфигурации объекта
     *
     * @param array $settings - настройки
     */
    public function init(array $settings): void
    {
        foreach ($settings as $setting => $value) {
            if (isset($this->{$setting})) {
                $this->{$setting} = $value;
            }
        }
    }

    /**
     * Конструктор
     *
     * @param string $tplPath - имя файла шаблона
     * @param array $settings - настройки
     *
     * @throws PQSkaTplException
     */
    public function __construct(string $tplPath, array $settings = [])
    {
        if (!file_exists($tplPath)) {
            throw new PQSkaTplException("Файл шаблона $tplPath не существует");
        }

        $this->template = phpQuery::newDocumentFileHTML($tplPath);
        $this->init($settings);
    }

    /**
     * Вернуть шаблон/страницу
     *
     * @return phpQueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery|string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Вставить в шаблон несколько однородных записей (при этом на каждую запись создается копия DOM-объекта-родителя)
     *
     * @param array $data - данные для вставки
     * @param string|phpQueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery $parent - селектор DOM-объекта, в который нужно вставить данные
     *
     * @return \phpQueryObject|\QueryTemplatesParse|\QueryTemplatesSource|\QueryTemplatesSourceQuery
     *
     * @throws PQSkaTplException
     */
    public function setMultiData(array $data, $parent)
    {
        if (\is_string($parent) && empty((string) ($parent = $this->template->find($parent)))) {
            throw new PQSkaTplException('Искомый DOM-элемент не найден');
        }

        if (\is_array($data) && !$parent->hasClass($this->cloneClassName)) {
            $this->setData($data, $parent);
            return $parent->parent();
        }

        if (empty($data[0])) {
            $data = [$data];
        }

        if (\is_array($data[0]) && !$this->dataDependsCheck($data[0], $parent)) {
            return $parent->parent();
        }

        foreach ($data as $row) {
            if (!\is_array($row)) {
                continue;
            }

            $clone = $parent->clone();
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
    protected function isInsertable(array $labels, array &$matches): bool
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
     * @param \phpQueryObject|\QueryTemplatesSource|\QueryTemplatesParse|\QueryTemplatesSourceQuery $element - phpQuery-элемент
     * @param string $key - имя элемента для вставки
     * @param string $value - значение для вставки
     *
     * @return \phpQueryObject|\QueryTemplatesSource|\QueryTemplatesParse|\QueryTemplatesSourceQuery|bool
     */
    protected function modifyElement(&$element, string &$key, ?string &$value)
    {
        if (!$value) {
            return false;
        }

        if (!preg_match_all('/in_(' . implode('|', $this->allowedAttrs) . ")_$key/i", $element->attr('class'), $matches)) {
            return false;
        }

        $matches = $matches[1];

        if ($this->isInsertable(['text'], $matches)) {
            $element->html($value);
        }

        if ($this->isInsertable(['class'], $matches)) {
            $element->addClass($value);
        }

        if ($this->isInsertable(['href'], $matches)) {
            $element->attr('href', $element->attr('href') . $value);
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
     * @param string|\phpQueryObject|\QueryTemplatesSource|\QueryTemplatesParse|\QueryTemplatesSourceQuery $parent - DOM-объект
     *
     * @return \phpQueryObject|\QueryTemplatesParse|\QueryTemplatesSource|\QueryTemplatesSourceQuery
     *
     * @throws PQSkaTplException
     */
    public function setData(array $data, &$parent)
    {
        if (\is_string($parent) && empty((string) $parent = $this->template->find($parent))) {
            throw new PQSkaTplException('Искомый DOM-элемент не найден');
        }

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
            //$value = @json_decode($value, true) ?? (string) $value;
            if (\is_array($value)) {
                $this->setMultiData($value, $parent->find(".$key{$this->subparentSelector}"));
            } else {
                $this->modifyElement($parent, $key, $value);

                $parent->find("*[class*=_$key]")->each(function ($element) use ($key, $value)
                {
                    $element = pq($element);
                    $this->modifyElement($element, $key, $value);
                });
            }
        }

        $parent->removeClass($this->cloneClassName);
        return $parent;
    }

    /**
     * Если данных нет, то прячет зависимые от этих данных элементы
     *
     * @param array $data - данные
     * @param \phpQueryObject|\QueryTemplatesSource|\QueryTemplatesParse|\QueryTemplatesSourceQuery $parent - элемент, в который данные должны вставляться
     *
     * @return bool
     */
    protected function dataDependsCheck(array &$data, &$parent): bool
    {
        if ($data) {
            return true;
        }

        if (!((string) $dependsParent = $parent->parents('*[data-depends-on]'))) {
            return true;
        }

        $dependsSelector = $dependsParent->attr('data-depends-on');
        if ($parent->is($dependsSelector)) {
            $dependsParent->addClass($this->noDisplayClass);
            return false;
        }

        return true;
    }

    /**
     * Показать блок с сообщением об отсутствии данных, если данных нет
     *
     * @param array $data - данные
     * @param \phpQueryObject|\QueryTemplatesSource|\QueryTemplatesParse|\QueryTemplatesSourceQuery $parent - элемент, в который данные должны вставляться
     *
     * @return bool
     */
    protected function isShowNoData(array &$data, &$parent): bool
    {
        if (!$data) {
            if (!($parent = $parent->parents($this->parentSelector)) || !((string) $noDataElement = $parent->find($this->noDataSelector))) {
                return true;
            }

            $parent->children()->addClass($this->noDisplayClass);
            $noDataElement->removeClass($this->noDisplayClass);
            return false;
        }

        return true;
    }

}
