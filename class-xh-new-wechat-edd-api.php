<?php
if (! defined ( 'ABSPATH' )) exit ();
require_once dirname(__FILE__).'/views/api.php';
class XH_New_Wechat_Payment_EDD_Api{
    public $id = 'xh_new_wechat_payment_edd';
		private static $_instance;
	/**
	 * @return XHWepayezAlipayWC
	 */
	public static function instance(){
	    if(!self::$_instance){
	        self::$_instance = new self();
	    }
	    
	    return self::$_instance;
	}
    public function __construct(){
        add_filter( 'edd_accepted_payment_icons', array( $this, 'register_payment_icon' ), 10, 1 );
    }

    public function init(){
        if(!function_exists('edd_get_option')){
            add_action ( 'admin_notices',function(){
                ?>
                <div class="notice notice-error is-dismissible"><b> Wechat:</b><p>请启用EDD插件!</p></div>
                <?php
            });
            return;
        }

        //notify
        $request=json_decode(file_get_contents('php://input'));
        $out_trade_no = isset($request->out_trade_no)?$request->out_trade_no:null;
		$order_id=isset($request->order_id)?$request->order_id:null;
		if(!$out_trade_no||!$order_id){
			return;
		}

      if($request->status=='complete'){
		if(!edd_is_payment_complete($out_trade_no)){
			update_post_meta($out_trade_no, '_edd_payment_transaction_id', $order_id);	
			edd_update_payment_status($out_trade_no, 'complete');
			}
		}else{
			return;	
		}
		ob_clean();
        print 'success';
        exit;
    }

    public function register_activation_hook(){
        $val =edd_get_option('xh_new_wechat_payment_edd_title');
        if(empty($val)){
            edd_update_option('xh_new_wechat_payment_edd_title',__('Wechat Payment',XH_NEW_WECHAT_PAYMENT_EDD));
        }

        $val =edd_get_option('xh_wechat_payment_edd_mchid');
        if(empty($val)){
            edd_update_option('xh_wechat_payment_edd_mchid','2ddfa6b4325542979d55f90ffe0216bd');
        }

        $val =edd_get_option('xh_wechat_payment_edd_private_key');
        if(empty($val)){
            edd_update_option('xh_wechat_payment_edd_private_key','ceb557e114554c56ad665b52f1cb3d8b');
        }

        $val =edd_get_option('xh_new_wechat_payment_edd_transaction_url');
        if(empty($val)){
            edd_update_option('xh_new_wechat_payment_edd_transaction_url','https://admin.xunhuweb.com');
        }

        $val =edd_get_option('xh_new_wechat_payment_edd_exchange_rate');
        if(empty($val)){
            edd_update_option('xh_new_wechat_payment_edd_exchange_rate','1');
        }
    }

    public function edd_settings_gateways($settings){
        $options=array(
            'xh_wechat_payment_edd_settings'=>array(
                'id'   => 'xh_wechat_payment_edd_header',
                'name' => '<h3>' . __( 'Wechat Payment Settings', XH_NEW_WECHAT_PAYMENT_EDD ) . '</h3>',
                'desc' => '',
                'type' => 'header',
            ),
            'xh_new_wechat_payment_edd_title'=>array(
                'id' => 'xh_new_wechat_payment_edd_title',
                'name' =>  __( 'Title', XH_NEW_WECHAT_PAYMENT_EDD ),
                'type' => 'text'
            ),
			'xh_wechat_payment_edd_mchid' => array(
			        'id'=>'xh_wechat_payment_edd_mchid',
					'name'       => __( 'MCHID', XH_NEW_WECHAT_PAYMENT_EDD ),
					'type'        => 'text',
			         'default'=>'2ddfa6b4325542979d55f90ffe0216bd',
                     'desc'=>'帮助文档：https://admin.xunhuweb.com'
			),
			'xh_wechat_payment_edd_private_key' => array(
			        'id'=>'xh_wechat_payment_edd_private_key',
					'name'       => __( 'Private Key', XH_NEW_WECHAT_PAYMENT_EDD ),
					'type'        => 'text',
			    'default'=>'ceb557e114554c56ad665b52f1cb3d8b',

			),
			'xh_new_wechat_payment_edd_transaction_url' => array(
			    'id'=>'xh_new_wechat_payment_edd_transaction_url',
					'name'       => __( 'Transaction Url', XH_NEW_WECHAT_PAYMENT_EDD ),
					'type'        => 'text',
			        'default'=>'https://admin.xunhuweb.com',
			    'desc' =>''
			),
            'xh_new_wechat_payment_edd_exchange_rate'=>array(
                'id' => 'xh_new_wechat_payment_edd_exchange_rate',
                'name' => __( 'exchange rate', XH_NEW_WECHAT_PAYMENT_EDD ),
                'placeholder'=>'1',
                'desc'=>__( 'Please set current currency against Chinese Yuan exchange rate,default 1.', XH_NEW_WECHAT_PAYMENT_EDD ),
                'type' => 'text'
            )
        );

        $settings[$this->id]=$options;
        return $settings;
    }


    public function register_payment_icon( $payment_icons ) {
        $payment_icons[XH_NEW_WECHAT_PAYMENT_EDD_URL.'/images/icon.png'] =__('Wechat Payment',XH_NEW_WECHAT_PAYMENT_EDD);

        return $payment_icons;
    }

    public function currency_filter_before( $formatted, $currency, $price){
        if($currency=='CNY'){
            $formatted = '&yen;' . ' ' . $price;
        }
        return $formatted;
    }

    public function currency_filter_after( $formatted, $currency, $price){
       if($currency=='CNY'){
            $formatted = $price . ' ' . '&yen;';
       }
       return $formatted;
    }

    public function plugin_action_links($links) {
        return array_merge ( array (
            'settings' => '<a href="' . admin_url ( 'edit.php?post_type=download&page=edd-settings&tab=gateways&section='.$this->id ) . '">'.__('Settings',XH_NEW_WECHAT_PAYMENT_EDD).'</a>'
        ), $links );
    }

    public function edd_currency_symbol($symbol, $currency ){
        if($currency=='CNY'){
            $symbol= '&yen;';
        }

        return $symbol;
    }
    /**
     *
     * @param array $order
     * @return string
     */
    public function edd_gateway($purchase_data){
        if( ! wp_verify_nonce( $purchase_data['gateway_nonce'], 'edd-gateway' ) ) {
    		wp_die( __( 'Nonce verification has failed', XH_NEW_WECHAT_PAYMENT_EDD ), __( 'Error',XH_NEW_WECHAT_PAYMENT_EDD ), array( 'response' => 403 ) );
    		return;
    	}

    	// Collect payment data
    	$payment_data = array(
    		'price'         => $purchase_data['price'],
    		'date'          => $purchase_data['date'],
    		'user_email'    => $purchase_data['user_email'],
    		'purchase_key'  => $purchase_data['purchase_key'],
    		'currency'      => edd_get_currency(),
    		'downloads'     => $purchase_data['downloads'],
    		'user_info'     => $purchase_data['user_info'],
    		'cart_details'  => $purchase_data['cart_details'],
    		'gateway'       => $purchase_data['post_data']['edd-gateway'],
    		'status'        => ! empty( $purchase_data['buy_now'] ) ? 'private' : 'pending'
    	);

	    $payment_id = edd_insert_payment( $payment_data );
        if($payment_id===false){
             edd_set_error(__( 'Payment Error', XH_Wechat_Payment), __('Ops!Something is wrong',XH_Wechat_Payment));
            edd_record_gateway_error('Payment Error',__('Ops!Something is wrong',XH_Wechat_Payment), $payment_id );
            edd_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['edd-gateway'] );
            return;
        }

        $payment_data['order_id']=$payment_id;

        $exchange_rate =floatval(edd_get_option('xh_new_wechat_payment_edd_exchange_rate',1));
        if($exchange_rate<=0){
            $exchange_rate=1;
        }

        $total_fee = round(floatval($payment_data['price'])*$exchange_rate, 2);
        $hashkey          = edd_get_option('xh_wechat_payment_edd_private_key');
        $params = array(
                'payment_id'=>$payment_id
        );
		$params['hash']=XH_Payment_Api::generate_xh_hash($params, $hashkey);
        $siteurl = rtrim(home_url(),'/');
        $posi =strripos($siteurl, '/');
        //若是二级目录域名，需要以“/”结尾，否则会出现403跳转
        if($posi!==false&&$posi>7){
            $siteurl.='/';
        }
        $data=array(
			'mchid'     	=> edd_get_option('xh_wechat_payment_edd_mchid'),
            'out_trade_no'	=> $payment_data['order_id'],
            'type'  		=> 'wechat',
            'total_fee' 	=> $total_fee*100,
            'body'  		=> $this->get_order_title($payment_data),
            'notify_url'	=> $siteurl,
            'redirect_url'  => edd_get_success_page_uri(),
            'nonce_str' 	=> str_shuffle(time())
        );
        $data['sign']     = XH_Payment_Api::generate_xh_hash($data,$hashkey);
    	if(!XH_Payment_Api::is_wechat_app()){
        	 $pay_url     = XH_Payment_Api::data_link('https://admin.xunhuweb.com/pay/cashier', $data);
        	 header("Location:". htmlspecialchars_decode($pay_url,ENT_NOQUOTES));
	    	 exit;
        }
        $url              = edd_get_option('xh_new_wechat_payment_edd_transaction_url').'/pay/payment';
		$data['hashkey']  = $hashkey;
        try {
            $response     = XH_Payment_Api::http_post($url, json_encode($data));
            $result       = $response?json_decode($response,true):null;
            if(!$result){
                throw new Exception('Internal server error',500);
            }

            // $hash         = $this->generate_xh_hash($result,$hashkey);
            // if(!isset( $result['hash'])|| $hash!=$result['hash']){
            //     throw new Exception(__('Invalid sign!',XH_Wechat_Payment),40029);
            // }

            // if($result['errcode']!=0){
            //     throw new Exception($result['errmsg'],$result['errcode']);
            // }
            //edd_empty_cart();
            $url =$result['code_url'];
           ?>
			<!DOCTYPE html>
			<html>
			<head>
		    <meta charset="utf-8">
		    <meta http-equiv="X-UA-Compatible" content="IE=edge">
		    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
		    <meta name="keywords" content="">
		    <meta name="description" content="">   
		    <title>微信支付收银台</title>
		    <style>
		         *{margin:0;padding:0;}
		          body{background: #f2f2f4;}
		         .clearfix:after { content: "."; display: block; height: 0; clear: both; visibility: hidden; }
		        .clearfix { display: inline-block; }
		        * html .clearfix { height: 1%; }
		        .clearfix { display: block; }
		          .xh-title{height:75px;line-height:75px;text-align:center;font-size:30px;font-weight:300;border-bottom:2px solid #eee;background: #fff;}
		          .qrbox{max-width: 900px;margin: 0 auto;padding:85px 20px 20px 50px;}
		          
		          .qrbox .left{width: 40%;
		            float: left;    
		             display: block;
		            margin: 0px auto;}
		          .qrbox .left .qrcon{
		            border-radius: 10px;
		            background: #fff;
		            overflow: visible;
		            text-align: center;
		            padding-top:25px;
		            color: #555;
		            box-shadow: 0 3px 3px 0 rgba(0, 0, 0, .05);
		            vertical-align: top;
		            -webkit-transition: all .2s linear;
		            transition: all .2s linear;
		          }
		            .qrbox .left .qrcon .logo{width: 100%;}
		            .qrbox .left .qrcon .title{font-size: 16px;margin: 10px auto;width: 100%;}
		            .qrbox .left .qrcon .price{font-size: 22px;margin: 0px auto;width: 100%;}
		            .qrbox .left .qrcon .bottom{border-radius: 0 0 10px 10px;
		            width: 100%;
		            background: #32343d;
		            color: #f2f2f2;padding:15px 0px;text-align: center;font-size: 14px;}
		           .qrbox .sys{width: 60%;float: right;text-align: center;padding-top:20px;font-size: 12px;color: #ccc}
		           .qrbox img{max-width: 100%;}
		           @media (max-width : 767px){
		        .qrbox{padding:20px;}
		            .qrbox .left{width: 90%;float: none;}   
		            .qrbox .sys{display: none;}
		           }
		           
		           @media (max-width : 320px){
		
		          }
		          @media ( min-width: 321px) and ( max-width:375px ){
		
		          }
		    </style>
		    </head>
		    
		    <body>
		     <div class="xh-title">微信支付收银台</div>
		      <div class="qrbox clearfix">
		      <div class="left">
		         <div class="qrcon">
		           <h5><img src="<?php echo plugin_dir_url(__FILE__ ) ?>images/wechat/logo.png" alt=""></h5>
		             <div class="title"><?php echo $this->get_order_title($payment_data) ?></div>
		             <div class="price"><?php echo $total_fee ?></div>
		             <div align="center"><div id="wechat_qrcode" style="width: 250px;height: 250px;"></div></div>
		             <div class="bottom">
		             <?php 
		             if(XH_Payment_Api::is_app_client()){
		                 ?>步骤1：长按二维码保存到手机相册<br/>步骤2：微信扫一扫选择相册(右上角)完成扫描
		                 <?php 
		             }else{
		                 ?>
		                 	请使用微信扫一扫<br/>
		    				扫描二维码支付
		                 <?php 
		             }
		             ?>
		             	
		             </div>
		         </div>
		         
		  </div>
		     <div class="sys"><img src="<?php echo plugin_dir_url(__FILE__ ) ?>images/wechat/wechat-sys.png" alt=""></div>
		  </div>
			  <script src="<?php echo $siteurl ?>/wp-includes/js/jquery/jquery.js"></script>
		      <script src="<?php echo plugin_dir_url(__FILE__ ) ?>js/qrcode.js"></script>
		     <script type="text/javascript">
		     (function($){
		    		window.view={
						query:function () {
							var data={data:'<?php echo json_encode($data);?>'};
					        $.ajax({
					            type: "POST",
					            url: "<?php echo plugin_dir_url(__FILE__ ).'views/query.php' ?>",
					            data:data,
					            timeout:6000,
					            cache:false,
					            dataType:'text',
					            success:function(e){
					            		if (e && e.indexOf('complete')!==-1) {
		    			                // $('#weixin-notice').css('color','green').text('已支付成功，跳转中...');
		    		                    window.location.href = "<?php echo edd_get_success_page_uri() ?>";
		    		                    return;
		    		                }
					                setTimeout(function(){window.view.query();}, 2000);
					            },
					            error:function(){
					            	console.log(11);
					            	 setTimeout(function(){window.view.query();}, 2000);
					            }
					        });
					    }
		    		};
		    		var qrcode = new QRCode(document.getElementById("wechat_qrcode"), {
		              width : 220,
		              height : 220
		            });
		            
		            <?php if(!empty($url)){
		              ?>
		              qrcode.makeCode("<?php print $url?>");
		              window.view.query();
		            <?php 
		            }?>
		    		
		    	})(jQuery);
		    	</script>
			</body>
		</html>
			<?php  
            exit;
        } catch (Exception $e) {
             edd_set_error(__( 'Payment Error', XH_Wechat_Payment), "errcode:{$e->getCode()},errmsg:{$e->getMessage()}");
            edd_record_gateway_error(__( 'Payment Error', XH_Wechat_Payment),"errcode:{$e->getCode()},errmsg:{$e->getMessage()}", $payment_id );
            edd_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['edd-gateway'] );
            exit;
        }
    }

    public  function edd_settings_sections_gateways( $gateway_sections ) {
        $gateway_sections[$this->id] =  __('Wechat Payment',XH_NEW_WECHAT_PAYMENT_EDD) ;

        return $gateway_sections;
    }

   public function edd_currencies( $currencies ) {
        $currencies['CNY'] = __('Chinese Yuan(&yen;)', XH_NEW_WECHAT_PAYMENT_EDD);

        return $currencies;
    }

    public function edd_payment_gateways($gateways){
        $gateways[$this->id] = array(
            'admin_label' => __('Wechat Payment',XH_NEW_WECHAT_PAYMENT_EDD),
            'checkout_label' =>edd_get_option('xh_new_wechat_payment_edd_title', __( 'Wechat Payment', XH_NEW_WECHAT_PAYMENT_EDD ))
        );
        return $gateways;
    }

    public function get_order_title($order, $limit = 98) {
        $subject = "#{$order['order_id']}";

        if($order['cart_details']&&count($order['cart_details'])>0){
            $index=0;
            foreach ($order['cart_details'] as $item){
                $subject.= "|{$item['name']}";
                if($index++>0){
                    $subject.='...';
                    break;
                }
            }
        }

        $title = mb_strimwidth($subject, 0, $limit,'utf-8');
        return apply_filters('xh-payment-get-order-title', $title,$order);
    }

    public function get_order_desc($order) {
        $descs=array();

        if( $order['cart_details']){
            foreach ( $order['cart_details'] as $order_item){
                $result =array(
                    'order_item_id'=>$order_item['id'],
                    'qty'=>$order_item['quantity'],
                    'product_id'=>$order_item['id']
                );

                if(isset( $result['product_id'])){
                    $post = get_post($result['product_id']);
                    if($post){
                        //获取图片
                        $full_image_url = wp_get_attachment_image_src( get_post_thumbnail_id($post->ID), 'full');

                        $desc=array(
                            'id'=>$result['product_id'],
                            'order_qty'=>$order_item['quantity'],
                            'order_item_id'=>$order_item['id'],
                            'url'=>get_permalink($post),
                            'sale_price'=>$order_item['subtotal'],
                            'image'=>count($full_image_url)>0?$full_image_url[0]:'',
                            'title'=>$post->post_title,
                            'sku'=>$post->ID,
                            'summary'=>$post->post_excerpt,
                            'content'=>$post->post_content
                        );
                    }
                }

                $descs[]=$desc;
            }
        }

        return apply_filters('xh-payment-get-order-desc', json_encode($descs),$order);
    }
}
?>
