<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit521193aead2e94eec78908ca0ced7ccf
{
    public static $prefixLengthsPsr4 = array (
        'A' => 
        array (
            'App\\' => 4,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'App\\' => 
        array (
            0 => __DIR__ . '/../..' . '/app',
        ),
    );

    public static $classMap = array (
        'App\\ExampleController' => __DIR__ . '/../..' . '/app/UserController.php',
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit521193aead2e94eec78908ca0ced7ccf::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit521193aead2e94eec78908ca0ced7ccf::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit521193aead2e94eec78908ca0ced7ccf::$classMap;

        }, null, ClassLoader::class);
    }
}