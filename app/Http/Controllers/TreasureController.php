<?php namespace App\Http\Controllers;

use Illuminate\Support\Facades\Request;

class TreasureController extends Controller {

	public $location_url = "http://testapi.reapal.com/mobile/portal" ;
	//public $location_url = "api.reapal.com/mobile/portal"; 
	public $merchant_id = "100000000011015" ;
	public $seller_email = "820061154@qq.com" ;
	public $notify_url = "http://120.55.92.123/recharge_notify" ;
	public $return_url = "http://120.55.92.123/recharge_return" ;
	public $currency = "156" ;
	public $use_key = "e977ade964836408243b5g2444848f7b39d09fb41c77ae2e327ffb16f905e117" ;
	public $public_key = "/paykey/treasure_rb/itrus001.pem" ;
	public $private_key = "/paykey/treasure_rb/itrus001_pri.pem" ;
	
	public function __construct()
	{
		$this->middleware('guest');
	}

	public function recharge(Request $request){
		$data['merchant_id'] = $this->merchant_id ;
		$data['order_no'] = time().rand(1000,9999);
		$data['transtime'] = date("Y-m-d h:i:s") ;
		$data['currency'] = $this->currency;
		$data['total_fee'] = $request::input("money") ;
		$data['title'] = "treasure_recharge" ;
		$data['body'] = "treasure_recharge_body" ;
		$data['member_id'] = $request::get("user_id") ;
		$data['terminal_type'] = "mobile" ;
		$data['terminal_info'] = "terminal_info" ;
		$data['member_ip'] = $request::server('REMOTE_ADDR');
		$data['seller_email'] = $this->seller_email ;
		$data['notify_url'] = $this->notify_url ;
		$data['return_url'] = $this->return_url ;
		$data['payment_type'] = "2" ;
		$data['pay_method'] = "bankPay" ;
		$data['sign_type'] = "MD5" ;
		ksort($data); reset($data);
		$data['sign'] = $this->getSign($data) ;
		$ending_key = $this->generateAESKey();
		$ending_data['merchant_id'] = $this->merchant_id;
		$ending_data['data'] = $this->build_rb_data(json_encode($data),$ending_key) ;
		$ending_data['encryptkey'] = $this->build_rb_key($ending_key);
 		$form_str = $this->build_rb_form($ending_data);
		return view("treasure_recharge",array(
			"treasure_submit"=>$ending_data,
			"treasure_url"=>$this->location_url,
		));
	}
	
	public function recharge_notify(Request $request){
		
	}
	
	public function recharge_return(Request $request){
		
	}
	
	public function build_rb_form($data){
		$form_str = "<form id='treasure_form' name='treasure_form' action='{$this->location_url}' method='post'>" ;
		foreach($data as $k=>$v){
			$form_str .= "<input type='hidden' name='{$k}' value='{$v}' />";
		}
		$form_str .= "</form>";
		$form_str .= "<script>document.treasure_form.submit();</script>" ;
		return $form_str;
	}
	
	public function build_rb_key($treasure_key){
		$public_key = file_get_contents(resource_path().$this->public_key);
		$public_key_yes = openssl_pkey_get_public($public_key);
		openssl_public_encrypt($treasure_key,$return_keys,$public_key_yes);
		return base64_encode($return_keys); 
	}
	
	public function build_rb_data($treasure_data,$ending_key){
		$size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
		$treasure_data = $this->pkcs5_pad($treasure_data, $size);
		$td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');
		$iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
		mcrypt_generic_init($td, $ending_key, $iv);
		$data = mcrypt_generic($td, $treasure_data);
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		$data = base64_encode($data);
		return $data;
	}
	
	private function pkcs5_pad ($text, $blocksize) {
		$pad = $blocksize - (strlen($text) % $blocksize);
		return $text . str_repeat(chr($pad), $pad);
	}
	
	public function getSign($data){
		$signText = "" ;
		foreach($data as $k=>$v){
			if($v!="" && $k!="sign" && $k!="sign_type"){
				$signText.=$k . "=" . trim($v) . "&";
			}
		}
		$signText = substr($signText,0,count($signText)-2);
		return md5($signText.$this->use_key);
	}
	
	private function generateAESKey($length=16){
		$baseString = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$treasure_key = '';
		$_len = strlen($baseString);
		for($i=1;$i<=$length;$i++){
			$treasure_key .= $baseString[rand(0, $_len-1)];
		}
		return $treasure_key;
	}
	
}
