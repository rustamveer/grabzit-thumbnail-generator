<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitea252ab9a2ac1c2d078aaa1927cc29db
{
    public static $prefixLengthsPsr4 = array (
        'G' => 
        array (
            'GrabzIt\\' => 8,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'GrabzIt\\' => 
        array (
            0 => __DIR__ . '/..' . '/grabzit/grabzit/lib',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitea252ab9a2ac1c2d078aaa1927cc29db::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitea252ab9a2ac1c2d078aaa1927cc29db::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitea252ab9a2ac1c2d078aaa1927cc29db::$classMap;

        }, null, ClassLoader::class);
    }
}