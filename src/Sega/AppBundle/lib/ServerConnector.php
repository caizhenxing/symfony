<?php
namespace Dcs;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\DBAL\Connection;

class ServerConnector {

  const SUCCESS=0;	///< 成功
  const FAILED=1;	///< 失敗
  const DBERROR=2;	///< データベースエラー

  const PLATFORM_ANDROID 	= 0;
  const PLATFORM_IPHONE 	= 1;
  const PLATFORM_WEB		= 2;
  const PLATFORM_PC		= 3;

  private $url;

  public function getReturnUrl(){
    return $this->url;
  }
  
  public function connectorToGameVersion(Controller &$controller , Request $request , $game_version , $platform){

    $this->url = $request->getScheme().'://'.$request->getHttpHost().$request->getBasePath().'/';
    //error_log($this->url);
    try {
      $con = $controller->get('doctrine')->getConnection();
      $mst_data = $this->getConnectionMstData($con , $game_version , $platform);

      if($mst_data != false && isset($mst_data) && array_key_exists('url' , $mst_data)) {
        if( isset($mst_data['url']) ){
          $this->url = $mst_data['url'];
        }
      }
    }catch(\Exception $e) {
      throw new \Exception('データベースエラー');
    }
    return ServerConnector::SUCCESS;
  }

  function getConnectionMstData($con , $game_version , $platform) {

    $time = time();
    
    $ptmt = $con->prepare('select * from `DCS_MST_SERVER_CONNECTOR` where `enable_flag` = 1 and `game_version` = ?');
    $ptmt->execute(array($game_version));
    $datas = $ptmt->fetchAll();
    $ptmt->closeCursor();
    $target = false;
    if($datas != false && isset($datas) != false) {
      //error_log(print_r($datas , true));
      foreach($datas as $element){
        if(isset($element['effective_to']) && isset($element['effective_from'])) {
          $effective_from_time = strtotime($element['effective_from']);
          $effective_to_time = strtotime($element['effective_to']);
          if($effective_from_time < $time && $effective_to_time >= $time) {
            if(isset($element['platform'])){
              if($element['platform'] == $platform) {
                $target = $element;
                //error_log(print_r($target , true));
                break;
              }
            }
            else{
              $target = $element;
              //error_log(print_r($target , true));
              break;
            }
          }
        }
        else{
          if(isset($element['platform'])) {
            if($element['platform'] == $platform) {
              $target = $element;
              //error_log(print_r($target , true));
              break;
            }
          }
          else{
            $target = $element;
            //error_log(print_r($target , true));
            break;
          }
        }
      }
    }
    //error_log(print_r($target , true));
    return $target;
  }

  

}
?>