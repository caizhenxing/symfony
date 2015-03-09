<?php

namespace Sega\AppBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class SegaAppExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources'));
        $loader->load('config/services.yml');
        

        $loader->load('config/present.yml');
        
        $path = __DIR__.'/../Resources/service/';
        $d = dir($path);
        while(false !== ($entry = $d->read())){
        	if(strcmp(strtolower(pathinfo($entry, PATHINFO_EXTENSION)),'yml') != 0) continue;
        	
        	$loader->load('service/'.$entry);
        }
        $d->close();
    }
}
