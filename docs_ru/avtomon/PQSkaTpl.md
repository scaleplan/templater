<small>avtomon</small>

PQSkaTpl
========

Шаблонизатор

Описание
-----------

Class PQSkaTpl

Сигнатура
---------

- **class**.

Константы
---------

class устанавливает следующие константы:

- [`ALLOWED_ATTRS`](#ALLOWED_ATTRS) &mdash; Атрибуты, в которые можно вставлять значения

Свойства
----------

class устанавливает следующие свойства:

- [`$template`](#$template) &mdash; Шаблон/страница
- [`$cloneClassName`](#$cloneClassName) &mdash; Класс css, указывающий на то что элемент нужно копировать для вставки данных
- [`$noDisplayClass`](#$noDisplayClass) &mdash; CSS-класс для скрытия элементов
- [`$parentSelector`](#$parentSelector) &mdash; Селектор корня узла для вставки данных
- [`$noDataSelector`](#$noDataSelector) &mdash; Селектор блока с сообщением об отсутствии данных
- [`$subparentSelector`](#$subparentSelector) &mdash; Селектор для рекурсивных вставок

### `$template` <a name="template"></a>

Шаблон/страница

#### Сигнатура

- **protected** property.
- Может быть одного из следующих типов:
    - `string`
    - `phpQueryObject`
    - `QueryTemplatesParse`
    - `QueryTemplatesSource`
    - `QueryTemplatesSourceQuery`

### `$cloneClassName` <a name="cloneClassName"></a>

Класс css, указывающий на то что элемент нужно копировать для вставки данных

#### Сигнатура

- **protected** property.
- Значение `string`.

### `$noDisplayClass` <a name="noDisplayClass"></a>

CSS-класс для скрытия элементов

#### Сигнатура

- **protected** property.
- Значение `string`.

### `$parentSelector` <a name="parentSelector"></a>

Селектор корня узла для вставки данных

#### Сигнатура

- **protected** property.
- Значение `string`.

### `$noDataSelector` <a name="noDataSelector"></a>

Селектор блока с сообщением об отсутствии данных

#### Сигнатура

- **protected** property.
- Значение `string`.

### `$subparentSelector` <a name="subparentSelector"></a>

Селектор для рекурсивных вставок

#### Сигнатура

- **protected** property.
- Значение `string`.

Методы
-------

Методы класса class:

- [`init()`](#init) &mdash; Установка конфигурации объекта
- [`__construct()`](#__construct) &mdash; Конструктор
- [`getTemplate()`](#getTemplate) &mdash; Вернуть шаблон/страницу
- [`setMultiData()`](#setMultiData) &mdash; Вставить в шаблон несколько однородных записей (при этом на каждую запись создается копия DOM-объекта-родителя)
- [`isInsertable()`](#isInsertable) &mdash; Проверка на наличеие метки вставки
- [`modifyElement()`](#modifyElement) &mdash; Заполнение элемента данными
- [`setData()`](#setData) &mdash; Вставить данные в DOM-объект шаблона
- [`dataDependsCheck()`](#dataDependsCheck) &mdash; Если данных нет, то прячет зависимые от этих данных элементы
- [`isShowNoData()`](#isShowNoData) &mdash; Показать блок с сообщением об отсутствии данных, если данных нет

### `init()` <a name="init"></a>

Установка конфигурации объекта

#### Сигнатура

- **public** method.
- Может принимать следующий параметр(ы):
    - `$settings` (`array`) - настройки
- Ничего не возвращает.

### `__construct()` <a name="__construct"></a>

Конструктор

#### Сигнатура

- **public** method.
- Может принимать следующий параметр(ы):
    - `$tplPath` (`string`) - имя файла шаблона
    - `$settings` (`array`) - настройки
- Ничего не возвращает.
- Выбрасывает одно из следующих исключений:
    - [`avtomon\PQSkaTplException`](../avtomon/PQSkaTplException.md)

### `getTemplate()` <a name="getTemplate"></a>

Вернуть шаблон/страницу

#### Сигнатура

- **public** method.
- Может возвращать одно из следующих значений:
    - `phpQueryObject`
    - `QueryTemplatesSource`
    - `QueryTemplatesParse`
    - `QueryTemplatesSourceQuery`
    - `string`

### `setMultiData()` <a name="setMultiData"></a>

Вставить в шаблон несколько однородных записей (при этом на каждую запись создается копия DOM-объекта-родителя)

#### Сигнатура

- **public** method.
- Может принимать следующий параметр(ы):
    - `$data` (`array`) - данные для вставки
    - `$parent` (`string`|`phpQueryObject`|`QueryTemplatesSource`|`QueryTemplatesParse`|`QueryTemplatesSourceQuery`) - селектор DOM-объекта, в который нужно вставить данные
- Может возвращать одно из следующих значений:
    - `phpQueryObject`
    - `QueryTemplatesParse`
    - `QueryTemplatesSource`
    - `QueryTemplatesSourceQuery`
- Выбрасывает одно из следующих исключений:
    - [`avtomon\PQSkaTplException`](../avtomon/PQSkaTplException.md)

### `isInsertable()` <a name="isInsertable"></a>

Проверка на наличеие метки вставки

#### Сигнатура

- **protected** method.
- Может принимать следующий параметр(ы):
    - `$labels` (`array`) - метка
    - `$matches` (`array`) - массив совпадений
- Возвращает `bool` value.

### `modifyElement()` <a name="modifyElement"></a>

Заполнение элемента данными

#### Сигнатура

- **protected** method.
- Может принимать следующий параметр(ы):
    - `$element` (`phpQueryObject`|`QueryTemplatesSource`|`QueryTemplatesParse`|`QueryTemplatesSourceQuery`) - phpQuery-элемент
    - `$key` (`string`) - имя элемента для вставки
    - `$value` (`string`|`null`) - значение для вставки
- Может возвращать одно из следующих значений:
    - `phpQueryObject`
    - `QueryTemplatesSource`
    - `QueryTemplatesParse`
    - `QueryTemplatesSourceQuery`
    - `bool`

### `setData()` <a name="setData"></a>

Вставить данные в DOM-объект шаблона

#### Сигнатура

- **public** method.
- Может принимать следующий параметр(ы):
    - `$data` (`array`) - данные для вставки
    - `$parent` (`string`|`phpQueryObject`|`QueryTemplatesSource`|`QueryTemplatesParse`|`QueryTemplatesSourceQuery`) - DOM-объект
- Может возвращать одно из следующих значений:
    - `phpQueryObject`
    - `QueryTemplatesParse`
    - `QueryTemplatesSource`
    - `QueryTemplatesSourceQuery`
- Выбрасывает одно из следующих исключений:
    - [`avtomon\PQSkaTplException`](../avtomon/PQSkaTplException.md)

### `dataDependsCheck()` <a name="dataDependsCheck"></a>

Если данных нет, то прячет зависимые от этих данных элементы

#### Сигнатура

- **protected** method.
- Может принимать следующий параметр(ы):
    - `$data` (`array`) - данные
    - `$parent` (`phpQueryObject`|`QueryTemplatesSource`|`QueryTemplatesParse`|`QueryTemplatesSourceQuery`) - элемент, в который данные должны вставляться
- Возвращает `bool` value.

### `isShowNoData()` <a name="isShowNoData"></a>

Показать блок с сообщением об отсутствии данных, если данных нет

#### Сигнатура

- **protected** method.
- Может принимать следующий параметр(ы):
    - `$data` (`array`) - данные
    - `$parent` (`phpQueryObject`|`QueryTemplatesSource`|`QueryTemplatesParse`|`QueryTemplatesSourceQuery`) - элемент, в который данные должны вставляться
- Возвращает `bool` value.

