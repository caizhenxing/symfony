<?php

header("Content-Type: application/json; charset=utf-8");
$ret = array('result'=>'OK','response_data'=>array(), 'status'=>0, 'receipt'=>array('product_id'=>'gaia.ios.purchase.sample1', 'transaction_id'=>2, 'purchase_date_ms'=>13600000));
echo json_encode($ret);

