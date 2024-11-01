<?php 
	require_once 'api.php';
	$param=json_decode($_POST['data']);
	 $data=array(
	        'mchid'     	=> $param->mchid,
	        'out_trade_no'	=> $param->out_trade_no,
	        'nonce_str' 	=> str_shuffle(time()),
		);
	$url = 'https://admin.xunhuweb.com/pay/query';
	$private_key=$param->hashkey;
	$data['sign']	  = XH_Payment_Api::generate_xh_hash($data,$private_key);
	$response   	  = XH_Payment_Api::http_post($url, json_encode($data));
	$result     	  = $response?json_decode($response,true):null;
	var_dump($result['status']);
	if(isset($result['status'])&&$result['status']=='complete'){
	    	return $result;
		}else{
			return $order_id;
		}
		
			
?>