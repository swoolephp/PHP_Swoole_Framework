<?php

// autoload_real.php @generated by Composer

class ComposerAutoloaderInit61e19b39ad3b3dd4244316bc2c21dc43
{
    private static $loader;

    public static function loadClassLoader($class)
    {
        if ('Composer\Autoload\ClassLoader' === $class) {
            require __DIR__ . '/ClassLoader.php';
        }
    }

    /**
     * @return \Composer\Autoload\ClassLoader
     */
    public static function getLoader()
    {
        if (null !== self::$loader) {
            return self::$loader;
        }

        spl_autoload_register(array('ComposerAutoloaderInit61e19b39ad3b3dd4244316bc2c21dc43', 'loadClassLoader'), true, true);
        self::$loader = $loader = new \Composer\Autoload\ClassLoader(\dirname(__DIR__));
        spl_autoload_unregister(array('ComposerAutoloaderInit61e19b39ad3b3dd4244316bc2c21dc43', 'loadClassLoader'));

        require __DIR__ . '/autoload_static.php';
        call_user_func(\Composer\Autoload\ComposerStaticInit61e19b39ad3b3dd4244316bc2c21dc43::getInitializer($loader));

        $loader->register(true);

        return $loader;
    }
}
