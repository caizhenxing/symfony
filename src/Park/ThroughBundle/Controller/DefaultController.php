<?php

namespace Park\ThroughBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class DefaultController extends Controller
{
    
    public function indexAction()
    {
        return $this->render('ParkThroughBundle:Default:index.html.twig');
    }
}
