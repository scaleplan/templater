PQSkaTpl
========

Template engine for PHP.

Templates are HTML documents, 
standardization is based on adding specific classes to the HTML templates: 
we insert classes that indicate what data and where to insert. 
When creating an object, we pass the path to the template file to the designer, 
after that, we can add certain data to the generated page, 
most often these are samples from the database. 
The data should be in the form of an array of records, where the record is an associative array.
You can insert data either in the HTML element text or in some attributes.

For example:

Have a template block:

```
<div class= "user parent">
    <span class= "in_text_name" ></span>
    <img class= "in_src_image">
    <a class= "in_href_link in_class_user_link_class" >Account</a>
</div>
```

And data:

```
$data = [
    [
        'name' = > 'Aleksandr',
        'image' = > '/img/photo.jpg',
        'link' = > '/users/12'
        'user_link_class' = > 'trash-user'
    ]
];
```

Then running the code:

```
$page = new PQSkaTpl ('/templates/main.html');
echo (string) $page->setMultiData ($data, '.user');
```

We get the HTML:

```
<div class= "user">
    <span class= "in_text_name" >Aleksandr</span>
    <img class=" in_src_image"src="/img/photo.jpg">
    <a class= "in_href_link in_class_user_link_class trash-user" href= "/users/12 " >Account</a>
</div>
```


If the data contains multiple records, the template block will be copied as many times, 
how many records contain data. Blocks will be inserted one after another.

<br>

[Documentation](docs_en)
