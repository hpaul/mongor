<?php

Autoloader::map(array(
    'Mongor\\Model'    => path('bundle').'mongor/model.php',
    'Mongor\\MongoDB'  => path('bundle').'mongor/mongodb.php',
    'Mongor\\Hydrator' => path('bundle').'mongor/hydrator.php',
    'Mongor\\MongoAuth'=> path('bundle').'mongor/mongoauth.php',
));

Auth::extend('mongo', function() {
    return new Mongor\MongoAuth(Config::get('auth.model'));
});