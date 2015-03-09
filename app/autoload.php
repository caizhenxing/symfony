<?php

use Doctrine\Common\Annotations\AnnotationRegistry;
use Composer\Autoload\ClassLoader;

/**
 * @var ClassLoader $loader
 */
$loader = require __DIR__.'/../vendor/autoload.php';

AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

$loader->add('Dcs',__DIR__.'/../src/Sega/AppBundle/lib/');

// ‚â‚¯‚­‚»ŽÀ‘• by sega takeday
spl_autoload_register(function($class){
	if(strpos($class,'Dcs') === 0){
		require_once str_replace('\\','/',preg_replace('/Dcs/',__DIR__.'/../src/Sega/AppBundle/lib',$class,1)).".php";
	}
	if(strpos($class,'Logic') === 0){
		require_once str_replace('\\','/',preg_replace('/Logic/',__DIR__.'/../src/Sega/AppBundle/logic',$class,1)).".php";
	}
});
return $loader;
