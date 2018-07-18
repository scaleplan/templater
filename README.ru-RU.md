# PQSkaTpl

Шаблонизатор для PHP.

#### Установка

``
composer reqire avtomon/pqskatpl
``

<br>

#### Описание

Шаблонами являются HTML-документы, 
шаблонизация основана на добавлении определенных классов в HTML-шаблоны: 
мы вставляем классы указывающие какие данные  и куда нужно вставлять. 
При создании объекта мы передаём конструктору путь к файлу шаблона, 
после этого мы можем добавить определенные данные в формируемую страницу, 
чаще всего это выборки из базы данных. 
Данные должны быть в виде массива записей, где запись это ассоциативный массив.
Данные можно вставлять либо в текст элементов HTML либо в какие-либо атрибуты.

Например:

Имеем блок шаблона:

```
<div class="user parent">
    <span class="in_text_name"></span>
    <img class="in_src_image">
    <a class="in_href_link in_class_user_link_class">Account</a>
</div>
```

И данные:

```
$data = [
    [
        'name' => 'Aleksandr',
        'image' => '/img/photo.jpg',
        'link' => '/users/12'
        'user_link_class' => 'trash-user'
    ]
];
```

Тогда выполнив код:

```
$page = new PQSkaTpl('/templates/main.html');
echo (string) $page->setMultiData($data, '.user');
```

Мы получим HTML:

```
<div class="user">
    <span class="in_text_name">Aleksandr</span>
    <img class="in_src_image" src="/img/photo.jpg">
    <a class="in_href_link in_class_user_link_class trash-user" href="/users/12">Account</a>
</div>
```


Если данные содержат несколько записей - блок шаблона будет копирован столько раз, 
сколько записей содержат данные. Блоки будут вставляться друг за другом.

<br>

[Документация](docs_ru)
