<?php
namespace Sega\AppBundle\Controller;

use Symfony\Component\HttpFoundation\Response;

class JmeterController extends \Dcs\DcsController
{
	public function indexAction(){
		if(!\Dcs\config::JmeterMode){
				throw new \Symfony\Component\HttpKernel\Exception\HttpException(500);
		}
		return new Response('Start Jmeter test');
	}
}
