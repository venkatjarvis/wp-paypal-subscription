<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit143910334bf407bb5e6bacfad2ed9703
{
    public static $prefixesPsr0 = array (
        'P' => 
        array (
            'Psr\\Log\\' => 
            array (
                0 => __DIR__ . '/..' . '/psr/log',
            ),
            'PayPal' => 
            array (
                0 => __DIR__ . '/..' . '/paypal/rest-api-sdk-php/lib',
            ),
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixesPsr0 = ComposerStaticInit143910334bf407bb5e6bacfad2ed9703::$prefixesPsr0;

        }, null, ClassLoader::class);
    }
}
