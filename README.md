# Mongor ORM

## Installation

### Aritsan

	php artisan bundle:install mongor

### Bundle Registration

Add the following to your **application/bundles.php** file:

```php
'mongor' => array('auto' => true),
```

For connection add this to **application/config/database.php** after sqlsrv:

```php
'mongor' => array(
		'hostname'   => 'localhost',
		'connect'    => true,
		'timeout'    => '',
		'replicaSet' => '',
		'db'         => 'oscar',
		'username'   => '',
		'password'   => '',
),
```

## Use

### Models

To use a model you need to create a file in models folder with the name of the class just like Eloquent

	class User extends Mongor\Model {}

Where User is name(lower case) of the collection in database

### Auth

You can use Mongo for authentication, is easy only change in **application/config/auth.php** driver in **mongo** and set the model

## Copyright

This is a bundle based on [mikelbring library](https://github.com/mikelbring/Mongor), I only adapted for use with Laravel 3 with models, it's basic so if you have any issues please submit.
