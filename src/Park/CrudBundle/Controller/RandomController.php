<?php

namespace Park\CrudBundle\Controller;

use Symfony\Component\HttpFoundation\Response;

class RandomController
{
    public function indexAction($limit)
    {
       // return $this->render('ParkCrudBundle:Random:index.html.twig', array('name' => $name));
    	return new Response(
    			'<html><body>Number: '.rand(1, $limit).'</body></html>'
    	);
    }
}
