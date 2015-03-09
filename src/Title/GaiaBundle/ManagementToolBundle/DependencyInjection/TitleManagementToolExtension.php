<?php

namespace Title\GaiaBundle\ManagementToolBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class TitleManagementToolExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $loader->load('gaia_view_parameters.yml');
        $loader->load('gaia_model.yml');
        $loader->load('gaia_services.yml');
        $loader->load('title_view_parameters.yml');
        $loader->load('title_model.yml');
        $loader->load('title_services.yml');
        $loader->load('title_controllers.yml');
        $loader->load('title_error_messages.yml');
        # TODO ManagementTool タイトル側で実装したYamlを呼び出すように追加してください
        //$loader->load('XXXX.yml');
    }
}
