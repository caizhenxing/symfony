<?php
namespace Sega\AppBundle;

use Gaia\Bundle\ExternalInterfaceBundle\Noah\Security\AuthNoahFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SegaAppBundle extends Bundle{
	public function build(ContainerBuilder $container){
		parent::build($container);
		$extension = $container->getExtension('security');
		$extension->addSecurityListenerFactory(new AuthNoahFactory());
	}
}
