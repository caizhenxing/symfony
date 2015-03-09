<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Symfony\Bundle\AsseticBundle\AsseticBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),

            // Gaia dependency
            new Lsw\ApiCallerBundle\LswApiCallerBundle(),
            new Lsw\MemcacheBundle\LswMemcacheBundle(),
            new JMS\AopBundle\JMSAopBundle(),

            // Gaia bundles
            new Gaia\Bundle\CommonBundle\GaiaCommonBundle(),
            new Gaia\Bundle\HandlerSocketBundle\GaiaHandlerSocketBundle(),
            new Gaia\Bundle\DatabaseBundle\GaiaDatabaseBundle(),
            new Gaia\Bundle\MemcacheBundle\GaiaMemcacheBundle(),
            new Gaia\Bundle\ResponseFormatBundle\GaiaResponseFormatBundle(),
            new Gaia\Bundle\UserBundle\GaiaUserBundle(),
            new Gaia\Bundle\GachaBundle\GaiaGachaBundle(),
            new Gaia\Bundle\FriendBundle\GaiaFriendBundle(),
            new Gaia\Bundle\PresentBundle\GaiaPresentBundle(),
            new Gaia\Bundle\PurchaseBundle\GaiaPurchaseBundle(),
            new Gaia\Bundle\AtomCampaignBundle\GaiaAtomCampaignBundle(),
            new Gaia\Bundle\ExternalInterfaceBundle\GaiaExternalInterfaceBundle(),
            new Gaia\Bundle\SecurityBundle\GaiaSecurityBundle(),
            new Gaia\Bundle\BbsBundle\GaiaBbsBundle(),
            new Gaia\Bundle\AnnotationsBundle\GaiaAnnotationsBundle(),
            new Gaia\Bundle\MaintenanceBundle\GaiaMaintenanceBundle(),
            new Gaia\Bundle\ManagementToolBundle\GaiaManagementToolBundle(),
            new Title\GaiaBundle\ManagementToolBundle\TitleManagementToolBundle(),
            new Title\GaiaBundle\InstallBundle\GaiaInstallBundle(),
            new Sega\AppBundle\SegaAppBundle(),
            new Park\CrudBundle\ParkCrudBundle(),
        	new Park\ThroughBundle\ParkThroughBundle()
        );

        if (in_array($this->getEnvironment(), array('dev', 'test'))) {
            //$bundles[] = new Acme\DemoBundle\AcmeDemoBundle();
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
        }

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/config_'.$this->getEnvironment().'.yml');
    }
}
