# Mongor ORM

## Installation

### Aritsan

	php artisan bundle:install mongor

### Bundle Registration

Add the following to your **application/bundles.php** file:

	'mongor' => array(
		'autoloads' => array(
			'map' => array(
				'Mongor\\Model'    => '(:bundle)/model.php',
           	 	'Mongor\\MongoDB'    => '(:bundle)/mongodb.php',
            	'Mongor\\Hydrator'    => '(:bundle)/hydrator.php',
			),
		),
	),

For connection add this to **application/config/database.php** after sqlsrv:

	'mongor' => array(
			'hostname'   => 'localhost',
			'connect'    => true,
			'timeout'    => '',
			'replicaSet' => '',
			'db'         => 'oscar',
			'username'   => '',
			'password'   => '',
	),

## Utilisation

### Models

To use a model you need to create a file in models folder with the name of the class just like Eloquent

	class User extends Mongor\Model {}

Where User is name(lower case) of the collection in database


## Copyright

This is a bundle based on [mikelbring library](https://github.com/mikelbring/Mongor), I only adapted for use with Laravel 3 with models, it's basic so if you have any issues please submit.
