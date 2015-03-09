<?php
namespace Dcs;

use Doctrine\DBAL\Connection;

//-----------------------------------------------------------------------------
// DB周りの操作を書くのがメンドイのでユーティリティ
//-----------------------------------------------------------------------------
class DBUtil {
  //-----------------------------------------------------------------------------
  // select
  //-----------------------------------------------------------------------------
  // $paramsは配列
  // $params = array('column' => array('uid', 'name', …), //指定されいなければ*扱い
  //                 'table' => 'テーブル名', //省略不可
  //                 'where' => array('where句', array(パラメータ列)))
  public static function select(Connection $con, $params, $is_all = false) {
    if(isset($params['column'])) {
      $column = $params['column'];
    } else {
      $column = '*';
    }
    $table = $params['table'];
    if(isset($params['where'])) {
      $where = $params['where'][0];
    } else {
      $where = '1';
    }
    if(isset($params['orderby'])) {
      $orderby = $params['orderby'];
    } else {
      $orderby = '';
    }
    if(isset($params['limit'])) {
      $limit = 'limit '.$params['limit'];
    } else {
      $limit = '';
    }
    if(isset($params['offset'])) {
      $offset = 'offset '.$params['offset'];
    } else {
      $offset = '';
    }
    $sql = 'select '.$column.' from `'.$table.'` where '.$where.' '.$orderby.' '.$limit.' '.$offset;
    //var_dump($sql);
    try {
      $ptmt = $con->prepare($sql);
      if(isset($params['where'])) {
        $ptmt->execute($params['where'][1]);
      } else {
        $ptmt->execute();
      }
      if($is_all) {
        $data = $ptmt->fetchAll();
      } else {
        $data = $ptmt->fetch();
      }
    } catch(\Exception $e) {
      // 上へ伝搬
      throw $e;
      return false;
    }
    //var_dump($data);
    return $data;
  }
  //-----------------------------------------------------------------------------
  // insert
  //-----------------------------------------------------------------------------
  // $params = array('values' => array('uid' => $uid, 'name' => $name, …), //省略不可
  //                 'table' => 'テーブル名', //省略不可
  public static function insert(Connection $con, $params) {
    $values = $params['values'];
    $vstr = 'values(';
    $sql = 'insert into '.$params['table'].'(';
    $i = 0;
    $tmp = array();
    foreach($values as $key => $elm) {
      if($i != 0) {
        $sql .= ',';
        $vstr .= ',';
      }
      $sql .= '`'.$key.'`';
      $vstr .= '?';
      ++$i;
      $tmp[] = $elm;
    }
    $sql .= ') ';
    $vstr .= ')';
    $sql .= $vstr;
    try {
      $ptmt = $con->prepare($sql);
      $ptmt->execute($tmp);
      $ptmt->closeCursor();
    } catch(\Exception $e) {
      // 上へ伝搬
      throw $e;
      return false;
    }  
    //var_dump($sql);
    return true;
  }
  //-----------------------------------------------------------------------------
  // update
  //-----------------------------------------------------------------------------
  // $paramsは配列
  // $params = array('set' => array('uid' => $uid, 'name' => $name, …),
  //                 'table' => 'テーブル名', //省略不可
  //                 'where' => array('where句', array(パラメータ列)))
  public static function update(Connection $con, $params) {
    $sql = 'update '.$params['table'].' set ';
    $set = $params['set'];
    $where = $params['where'][0];
    $i = 0;
    $values = array();
    foreach($set as $key => $elm) {
      if($i != 0) {
        $sql .= ',';
      }
      $sql .= '`'.$key.'` = ?';
      ++$i;
      $values[] = $elm;
    }
    $sql .= ' where '.$where;
    $values = array_merge($values, $params['where']['1']);
//    var_dump($sql);
//    var_dump($values);
    try {
      $ptmt = $con->prepare($sql);
      $ptmt->execute($values);
    } catch(\Exception $e) {
      // 上へ伝搬
      throw $e;
      return false;
    }
    return true;
  }
  
  
  //-----------------------------------------------------------------------------
  // その他
  //-----------------------------------------------------------------------------
  
  // WHERE IN句用の (?,?,…,?) を組み立てる
  // $paramsは配列（要素の数だけ ? をつなげる）
  public static function createInStatement($params) {
    return '('.implode(',', array_fill(0, count($params), '?')).')';
  }
  //-----------------------------------------------------------------------------
  // テーブル取得
  //-----------------------------------------------------------------------------
  public static function showTables(Connection $con, $params) {
    $sql = "show tables from `gaia`";
    if(isset($params['like'])) {
      $sql .= " like '".$params['like']."'";
    }
    try {
      $ptmt = $con->prepare($sql);
      $ptmt->execute();
      $data = $ptmt->fetchAll();
    } catch(\Exception $e) {
      // 上へ伝搬
      throw $e;
      return false;
    }
    return $data;
  }
}
//-----------------------------------------------------------------------------
// End Of File
//-----------------------------------------------------------------------------
