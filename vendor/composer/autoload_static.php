<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit6bb980a95f63e4b85b9074b721b909e5
{
    public static $files = array (
        '91472710a804a13d98482825773b2fd4' => __DIR__ . '/../..' . '/src/Common.php',
    );

    public static $prefixLengthsPsr4 = array (
        'W' => 
        array (
            'WPChina\\WPChinaAPI\\' => 19,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'WPChina\\WPChinaAPI\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit6bb980a95f63e4b85b9074b721b909e5::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit6bb980a95f63e4b85b9074b721b909e5::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}