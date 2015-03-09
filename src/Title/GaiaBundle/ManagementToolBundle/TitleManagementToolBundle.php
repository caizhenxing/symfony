<?php

namespace Title\GaiaBundle\ManagementToolBundle;

use Title\GaiaBundle\ManagementToolBundle\Exception\TitleErrorMessages;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class TitleManagementToolBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        TitleErrorMessages::setMessages(
            $this->container->getParameter('title.mng_tool.error_messages')
        );
    }
}
