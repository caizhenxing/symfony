<?php
namespace Sega\AppBundle\Controller;


use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use Gaia\Bundle\HandlerSocketBundle\Util\HandlerSocketUtil;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use \Dcs\Arpg\StopWatch as StopWatch;
use \Dcs\Arpg\Time as Time;
use \Dcs\Security as sec;

class PageController extends \Dcs\DcsController
{
	public function gachaRateAction()
	{		
		
		return new Response($this->renderView(
				'SegaAppBundle:arpg:gacha_rate.html.twig',
				[
					'outer_css' => ['bundles/segaapp/css/gacha_rate.css'],
				
					'top_mes' => 'ガチャ確率',
					'rates'=>[
						[
							'name' => 'レアガチャ',
							'single' => true,
							'list1' => [
								'SR:1%',
								'HR:9%',
								'R:80%',
							]
						],
						[
							'name' => '期間限定',
							'single' => false,
							'list1' => [
								'SR:1%',
								'HR:9%',
								'R:80%',
							],
							'left_hr' => '期間中確率',
							'list2' => [
								'SR:2%',
								'HR:9%',
								'R:89%',
							]
						]
					]
				]
		));
	}
}
