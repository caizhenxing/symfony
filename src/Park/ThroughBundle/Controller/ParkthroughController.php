<?php
namespace Park\ThroughBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class ParkthroughController extends Controller
{
    
    public function indexAction()
    {

        return $this->render('ParkThroughBundle:Walkthrough:index.html.twig');
    }
}
