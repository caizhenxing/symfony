<?php

namespace Title\GaiaBundle\InstallBundle;

use Title\GaiaBundle\InstallBundle\Exception\ErrorMessages;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class GaiaInstallBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        ErrorMessages::setMessages(
            $this->container->getParameter('title.gaia.install.error_messages')
        );
    }
}
