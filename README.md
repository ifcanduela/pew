# Pew-Pew-Pew

> An amateur PHP framework.

Pew-Pew-Pew is a PHP 7 framework for simple websites.

I've used this for all my latest personal projects and it's usable -- although a little bit unreliable -- I break things on a weekly basis. Use at your own risk or, better yet, don't use at all.

# Install

    $ git clone https://github.com/ifcanduela/pew-app
    $ cd pew-app
    $ composer install
    $ php -S localhost:8000 -t www

# Getting started

Pew web applications are made up of **controller actions**, which are scripts bound to URLs. When a URL is requested it is compared against the defined routes, and if one matches its controller and action are used to generate a response.

Routes are defined in the `app\config\routes.php` file. This file should return an array with route declarations, which come in three flavours: full, short and resource routes. A full route declaration looks like this:

```php
[
    'path' => '/post[/{slug}]',
    'controller' => 'Posts@view',
    'methods' => 'GET',
    'defaults' => [
        'slug' => 'home'
    ]
]
```

This route will match URLs like `posts` and `posts/my-blog-post`. In both cases the `view()`  method of the `\app\controllers\Posts` class will be called. If the `slug` part of the URL is not present, a value of `home` will be passed to the method's `$slug` argument.

> Check the readme file for [nikic/FastRoute](https://github.com/nikic/FastRoute#defining-routes) to learn about path patterns.

An almost-equivalent route can be written in short form:

```php
'/post[/{slug}]' => 'Posts@view',
```

The slug here is optional, but it has no default, which means you have to give it one in the controller action:

```php
class Post
{
    public function view($slug = 'home')
    {
        return [
            'post' => Post::findOneBySlug($slug)
        ];
    }
}
```

You can also define a `slug` key in the `app\config\config.php`, since action methods receive their argument values from the injection container, and the config file is one of the sources (the others being the GET and POST arrays, the services configuration file (`pew\config\bootstrap.php`) and the route itself).

Resource-style routes are more automated and better fit for a CRUD-like user interface:

```php
[
    'resource' => 'Posts'
]
```

By defining a resource route, you will get these five routes:

- `'/posts' => 'Posts@index'`
- `'/posts/add/' => 'Posts@add'`
- `'/posts/{id}' => 'Posts@view'`
- `'/posts/{id}/edit' => 'Posts@edit'`
- `'/posts/{id}/delete' => 'Posts@delete'`

These routes will be given names (`posts_index`, `posts_view`, etc.) so they can be used with the `url_to()` helper function.

## Create a controller

Controllers can be just simple classes with one or more methods. A base controller is provided, which simplifies redirect and JSON responses, but it's not required. A basic controller might look like this:

```php
<?php

namespace app\controllers;

use app\models\Cat;

class Cats
{
    public function view($name)
    {
        $cats = Cat::find()
            ->where(['name' => $name]))
            ->orderBy('date_of_birth ASC')
            ->all();

        return [
            'cats' => $cats,
        ];
    }
}

```

In this case, the template file `app\views\cats\view.php` will be rendered, with a variable called `$cats` being available. The template is automatically selected based on the controller and action names, but it can be modified by requesting the `$view` object to be injected:

```php
public function view($name, $view)
{
    $view->template('animals/overview');

    // ...
}
```

The base controller class add the `renderJson()` and `redirect()`  methods, whose results must be `return`ed by the action method:

```php
public function index($name, $request)
{
    if ($request->isPost()) {
        return $this->renderJson(Cat::find()->all());
    }

    return $this->redirect('/home');
}
```

The return value of the action is used to create variables in the template. A return value of `false` will prevent any template from being rendered.

## Models

Model classes represent records in a database table and will hold most of the real code of the application. This is a quick example (`app\models\Cat.php`):

```php
<?php

namespace app\models;

class Cat extends \pew\Model
{
    public $tableName = 'cats';
}
```

By extending `\pew\Model`, you get access to some features, like the `find()` and `fromArray()` static methods and the `save()` method that will persist the model data to the database.

## Templating

Templates are chosen automatically using the controller and action names, but they can also be set explicitly. In the following example, setting the template to `cats/view` has no effect because that's the predefined view name for the `Cats::view()` action:

```php
<?php

namespace app\controllers;

class Cats
{
    public function view($name, $view)
    {
        $view->template('cats/view');

        return [
            'cat' => $this->model->find_by_name($name)
        ];
    }
}
```

Template names are always relative to the `app/views` directory, and must not include the `.php` extension.

Inside the `app/views/cats/view.php` template we can use the `$cat` variable to print information about the cat:

```php
<div class="cat-info">
    <h1><?= $cat->name ?></h1>

    <ul>
        <?php foreach ($cat->hobbies as $hobby): ?>
            <li><?= $hobby ?></li>
        <?php endforeach ?>
    </ul>
</div>
```

There's also the possibility of rendering the view within a layout:

```php
<?php $this->layout('layouts/main') ?>

<div class="cat-info">
    <h1><?= $cat->name ?></h1>

    <!-- ... -->
</div>
```
