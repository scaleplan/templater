<small> avtomon </small>

PQSkaTpl
========

Templateizer

Description
-----------

Class PQSkaTpl

Signature
---------

- **class**.

Constants
---------

class sets the following constants:

  - [`ALLOWED_ATTRS`](#ALLOWED_ATTRS) &mdash; Attributes into which values ​​can be inserted

Properties
----------

class sets the following properties:

  - [`$template`](#$template) &mdash; Template/page
  - [`$cloneClassName`](#$cloneClassName) &mdash; The css class, indicating that the item needs to be copied to insert data
  - [`$noDisplayClass`](#$noDisplayClass) &mdash; CSS class for hiding items
  - [`$parentSelector`](#$parentSelector) &mdash; Root node selector for data insertion
  - [`$noDataSelector`](#$noDataSelector) &mdash; Block selector with a message about the absence of data
  - [`$subparentSelector`](#$subparentSelector) &mdash; Selector for recursive inserts

### `$template`<a name="template"> </a>

Template/page

#### Signature

- **protected** property.
- Can be one of the following types:
  - `string`
  - `phpQueryObject`
  - `QueryTemplatesParse`
  - `QueryTemplatesSource`
  - `QueryTemplatesSourceQuery`

### `$cloneClassName`<a name="cloneClassName"> </a>

The css class, indicating that the item needs to be copied to insert data

#### Signature

- **protected** property.
- The value of `string`.

### `$noDisplayClass`<a name="noDisplayClass"> </a>

CSS class for hiding items

#### Signature

- **protected** property.
- The value of `string`.

### `$parentSelector`<a name="parentSelector"> </a>

Root node selector for data insertion

#### Signature

- **protected** property.
- The value of `string`.

### `$noDataSelector`<a name="noDataSelector"> </a>

Block selector with a message about the absence of data

#### Signature

- **protected** property.
- The value of `string`.

### `$subparentSelector`<a name="subparentSelector"> </a>

Selector for recursive inserts

#### Signature

- **protected** property.
- The value of `string`.

Methods
-------

Class methods class:

  - [`init()`](#init) &mdash; Setting the object configuration
  - [`__construct()`](#__construct) &mdash; Constructor
  - [`getTemplate()`](#getTemplate) &mdash; Return template/page
  - [`setMultiData()`](#setMultiData) &mdash; Insert several homogeneous records into the template (with each copy creating a copy of the parent DOM object)
  - [`isInsertable()`](#isInsertable) &mdash; Check for insertion of insertion marks
  - [`modifyElement()`](#modifyElement) &mdash; Filling an Item with Data
  - [`setData()`](#setData) &mdash; Paste data into the template DOM object
  - [`dataDependsCheck()`](#dataDependsCheck) &mdash; If there is no data, then it hides the elements dependent on this data
  - [`isShowNoData()`](#isShowNoData) &mdash; Show block with message about missing data, if no data

### `init()`<a name="init"> </a>

Setting the object configuration

#### Signature

- **public** method.
- It can take the following parameter (s):
  - `$settings`(`array`) - settings
- Returns nothing.

### `__construct()`<a name="__construct"> </a>

Constructor

#### Signature

- **public** method.
- It can take the following parameter (s):
  - `$tplPath`(`string`) - the name of the template file
  - `$settings`(`array`) - settings
- Returns nothing.
- Throws one of the following exceptions:
  - [`avtomon\PQSkaTplException`](../avtomon/PQSkaTplException.md)

### `getTemplate()`<a name="getTemplate"> </a>

Return template/page

#### Signature

- **public** method.
- Can return one of the following values:
  - `phpQueryObject`
  - `QueryTemplatesSource`
  - `QueryTemplatesParse`
  - `QueryTemplatesSourceQuery`
  - `string`

### `setMultiData()`<a name="setMultiData"> </a>

Insert several homogeneous records into the template (with each copy creating a copy of the parent DOM object)

#### Signature

- **public** method.
- It can take the following parameter (s):
  - `$data`(`array`) - data to insert
  - `$parent`(`string`| `phpQueryObject`|`QueryTemplatesSource`| `QueryTemplatesParse`|`QueryTemplatesSourceQuery`) is the selector of the DOM object into which you want to insert data
- Can return one of the following values:
  - `phpQueryObject`
  - `QueryTemplatesParse`
  - `QueryTemplatesSource`
  - `QueryTemplatesSourceQuery`
- Throws one of the following exceptions:
  - [`avtomon\PQSkaTplException`](../avtomon/PQSkaTplException.md)

### `isInsertable()`<a name="isInsertable"> </a>

Check for insertion of insertion marks

#### Signature

- **protected** method.
- It can take the following parameter (s):
  - `$labels`(`array`) - label
  - `$matches`(`array`) - array of matches
- Returns the `bool`value.

### `modifyElement()`<a name="modifyElement"> </a>

Filling an Item with Data

#### Signature

- **protected** method.
- It can take the following parameter (s):
  - `$element`(`phpQueryObject`| `QueryTemplatesSource`|`QueryTemplatesParse`| `QueryTemplatesSourceQuery`) - phpQuery-element
  - `$key`(`string`) - the name of the element to insert
  - `$value`(`string`| `null`) - value to insert
- Can return one of the following values:
  - `phpQueryObject`
  - `QueryTemplatesSource`
  - `QueryTemplatesParse`
  - `QueryTemplatesSourceQuery`
  - `bool`

### `setData()`<a name="setData"> </a>

Paste data into the template DOM object

#### Signature

- **public** method.
- It can take the following parameter (s):
  - `$data`(`array`) - data to insert
  - `$parent`(`string`| `phpQueryObject`|`QueryTemplatesSource`| `QueryTemplatesParse`|`QueryTemplatesSourceQuery`) - DOM object
- Can return one of the following values:
  - `phpQueryObject`
  - `QueryTemplatesParse`
  - `QueryTemplatesSource`
  - `QueryTemplatesSourceQuery`
- Throws one of the following exceptions:
  - [`avtomon\PQSkaTplException`](../avtomon/PQSkaTplException.md)

### `dataDependsCheck()`<a name="dataDependsCheck"> </a>

If there is no data, then it hides the elements dependent on this data

#### Signature

- **protected** method.
- It can take the following parameter (s):
  - `$data`(`array`) - data
  - `$parent`(`phpQueryObject`| `QueryTemplatesSource`|`QueryTemplatesParse`| `QueryTemplatesSourceQuery`) - the element in which the data should be inserted
- Returns the `bool`value.

### `isShowNoData()`<a name="isShowNoData"> </a>

Show block with message about missing data, if no data

#### Signature

- **protected** method.
- It can take the following parameter (s):
  - `$data`(`array`) - data
  - `$parent`(`phpQueryObject`| `QueryTemplatesSource`|`QueryTemplatesParse`| `QueryTemplatesSourceQuery`) - the element in which the data should be inserted
- Returns the `bool`value.

