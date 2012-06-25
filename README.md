# Mongor ORM

## Installation

### Aritsan

	php artisan bundle:install mongor

### Bundle Registration

Add the following to your **application/bundles.php** file:

```php
'mongor' => array('auto' => true),
```

Edit **config/database.php** with your database connection details.


## Use

### Models

To use a model you need to create a file in the models folder with the name of the class, just like Eloquent

	class User extends Mongor\Model {}

Where User is the name (lower case) of the collection in MongoDB.

### Auth

Using MongoDB for authentication is now very easy! Only change the driver in **application/config/auth.php** to **mongo** and set the corresponding model.

## Copyright

This is a bundle based on [mikelbring library](https://github.com/mikelbring/Mongor), I only adapted it for use with Laravel 3 with models. It's very basic, so if you have any issues please fork or write an issue ticket.
