<?php
namespace Sega\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class MaintenanceManagementReciveController extends Controller {


  public function startAction() {


    $service = $this->get('Dcs.MaintenanceSchedule');
    try{
      $service->start($this);
    }
    catch(\Exception $e){
      error_log('Maintenance Start Error!!!!');
      return new Response('FAILD');
    }
    return new Response('SUCCESS');
  }

  public function finishAction(){

    $service = $this->get('Dcs.MaintenanceSchedule');
    try{
      $service->finish($this);
    }
    catch(\Exception $e){
      error_log('Maintenance Finish Error!!!!');
      return new Response('FAILD');
    }
    return new Response('SUCCESS');
  }
  
  
  public function cleanApcAction(){
  	$service = $this->get('Dcs.MaintenanceSchedule');
  	if(!$service->checkAdress($this)){
      return new Response('FAILD');
  	}

  	if(extension_loaded('apcu')){
  		apc_clear_cache();
  		apc_clear_cache('user');
  		return new Response('SUCCESS');
  	}
    return new Response('FAILD');
  }
}

?>