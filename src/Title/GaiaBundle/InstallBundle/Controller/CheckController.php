<?php

namespace Title\GaiaBundle\InstallBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class CheckController extends Controller
{
    public function dbaccessAction()
    {
        $dbCheckModel = $this->get('title.gaia.install.model.databasecheck');
        $result = $dbCheckModel->getCheckedInfo();
        return $this->render('GaiaInstallBundle:Check:install_info.html.twig', array('errors' => $result));
    }
}
