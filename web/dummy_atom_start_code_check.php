<?php

$cid = $_GET['cid'];
$start_code = $_GET['start_code'];
$ret = array('result'=>'OK', 'cid'=>$cid,'start_code'=>$start_code);
echo json_encode($ret);

