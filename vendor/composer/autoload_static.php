<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit7d552f71dc727087f01015ac5dd4ed0b
{
    public static $prefixLengthsPsr4 = array (
        'C' => 
        array (
            'Combodo\\iTop\\Extension\\' => 23,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Combodo\\iTop\\Extension\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Combodo\\iTop\\Extension\\Helper\\ImapOptionsHelper' => __DIR__ . '/../..' . '/src/Helper/ImapOptionsHelper.php',
        'Combodo\\iTop\\Extension\\Helper\\ProviderHelper' => __DIR__ . '/../..' . '/src/Helper/ProviderHelper.php',
        'Combodo\\iTop\\Extension\\Service\\IMAPOAuthEmailSource' => __DIR__ . '/../..' . '/src/Service/IMAPOAuthEmailSource.php',
        'Combodo\\iTop\\Extension\\Service\\IMAPOAuthLogin' => __DIR__ . '/../..' . '/src/Service/IMAPOAuthLogin.php',
        'Combodo\\iTop\\Extension\\Service\\IMAPOAuthStorage' => __DIR__ . '/../..' . '/src/Service/IMAPOAuthStorage.php',
        'Combodo\\iTop\\Extension\\Service\\POP3OAuthEmailSource' => __DIR__ . '/../..' . '/src/Service/POP3OAuthEmailSource.php',
        'Combodo\\iTop\\Extension\\Service\\POP3OAuthLogin' => __DIR__ . '/../..' . '/src/Service/POP3OAuthLogin.php',
        'Combodo\\iTop\\Extension\\Service\\POP3OAuthStorage' => __DIR__ . '/../..' . '/src/Service/POP3OAuthStorage.php',
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit7d552f71dc727087f01015ac5dd4ed0b::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit7d552f71dc727087f01015ac5dd4ed0b::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit7d552f71dc727087f01015ac5dd4ed0b::$classMap;

        }, null, ClassLoader::class);
    }
}
