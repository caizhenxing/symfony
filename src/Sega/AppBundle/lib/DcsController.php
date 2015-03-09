<?php
namespace Dcs;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * プロジェクトごとの拡張は、DcsControllerFilterで！！
 */
class DcsController extends Controller{
	use \Dcs\Base, \Logic\DcsControllerFilter;
}
?>