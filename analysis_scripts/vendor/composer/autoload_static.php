<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit281dd9b6393ad8002c63da3328351315
{
    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'Symfony\\Component\\Filesystem\\' => 29,
            'Seld\\JsonLint\\' => 14,
        ),
        'P' => 
        array (
            'Psr\\Log\\' => 8,
        ),
        'M' => 
        array (
            'Monolog\\' => 8,
        ),
        'G' => 
        array (
            'Goetas\\XML\\XSDReader\\' => 21,
        ),
        'F' => 
        array (
            'FiveFortyCo\\Json\\' => 17,
            'FiveFortyCo\\' => 12,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Symfony\\Component\\Filesystem\\' => 
        array (
            0 => __DIR__ . '/..' . '/symfony/filesystem',
        ),
        'Seld\\JsonLint\\' => 
        array (
            0 => __DIR__ . '/..' . '/seld/jsonlint/src/Seld/JsonLint',
        ),
        'Psr\\Log\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/log/Psr/Log',
        ),
        'Monolog\\' => 
        array (
            0 => __DIR__ . '/..' . '/monolog/monolog/src/Monolog',
        ),
        'Goetas\\XML\\XSDReader\\' => 
        array (
            0 => __DIR__ . '/..' . '/goetas/xsd-reader/src',
        ),
        'FiveFortyCo\\Json\\' => 
        array (
            0 => __DIR__ . '/..' . '/540co/json-parser/src/FiveFortyCo/Json',
        ),
        'FiveFortyCo\\' => 
        array (
            0 => __DIR__ . '/..' . '/540co/xml-tools/src',
        ),
    );

    public static $prefixesPsr0 = array (
        'K' => 
        array (
            'Keboola\\Utils' => 
            array (
                0 => __DIR__ . '/..' . '/keboola/php-utils/src',
            ),
            'Keboola\\Temp' => 
            array (
                0 => __DIR__ . '/..' . '/keboola/php-temp/src',
            ),
            'Keboola\\CsvTable' => 
            array (
                0 => __DIR__ . '/..' . '/keboola/php-csvtable/src',
            ),
            'Keboola\\Csv' => 
            array (
                0 => __DIR__ . '/..' . '/keboola/csv/src',
            ),
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit281dd9b6393ad8002c63da3328351315::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit281dd9b6393ad8002c63da3328351315::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInit281dd9b6393ad8002c63da3328351315::$prefixesPsr0;

        }, null, ClassLoader::class);
    }
}
