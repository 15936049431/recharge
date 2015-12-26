<?php namespace App\Http\Controllers;

use Illuminate\Support\Facades\Request;
use \DB;

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
	public $ending_key = "6js27LZ9165CTNHV" ;
	
	public function __construct()
	{
		$this->middleware('guest');
	}
	
	public function test(){
		//$result = DB::select("select * from dql_account");
		//dd($result);
	}

	public function recharge(Request $request){
		$data['merchant_id'] = $this->merchant_id ;
		$data['order_no'] = time().rand(1000,9999);
		$data['transtime'] = date("Y-m-d h:i:s") ;
		$data['currency'] = $this->currency;
		$data['total_fee'] = $request::input("money") * 100 ;
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
		$check_resutl = $this->check_and_insert($data);
		$ending_data['merchant_id'] = $this->merchant_id;
		$ending_data['data'] = $this->build_rb_data(json_encode($data),$this->ending_key) ;
		$ending_data['encryptkey'] = $this->build_rb_key($this->ending_key);
 		$form_str = $this->build_rb_form($ending_data);
		return view("treasure_recharge",array(
			"treasure_submit"=>$ending_data,
			"treasure_url"=>$this->location_url,
		));
	}
	
	public function check_and_insert($data){
		$user_result = DB::select("select * from dql_account where user_id = ? ", [$data['member_id']]);
		$is_have = DB::select("select * from dql_account_recharge where user_id=? and trade_no=?",[$data['member_id'],$data['order_no']]);
		if(!empty($user_result) && empty($is_have) && strpos(Request::server("HTTP_REFERER"),"www.jinzhangguinb.com") > 0 ){
			$log_add['trade_no'] = $data['order_no'];
			$log_add['user_id'] = $data['member_id'];
			$log_add['type'] = "1" ;
			$log_add['status'] = "0" ;
			$log_add['payment'] = "67";
			$log_add['money'] = $data['total_fee'];
			$log_add['fee'] = "0";
			$log_add['remark'] = "treasure_recharge_".$data['total_fee'] ;
			$log_add['addtime'] = time();
			$log_add['addip'] = $data['member_ip'];
			$insert_log = DB::insert("insert into dql_account_recharge(trade_no,user_id,type,status,payment,money,fee,remark,addtime,addip) values(?,?,?,?,?,?,?,?,?,?)",array_values($log_add));
			if($insert_log){
				return true;
			}
		}
		dd(" he he da ! ! ! ");
	}
	
	public function return_and_addbill($order_id){
		$recharge_result = DB::select("select * from dql_account_recharge where trade_no = ? and status = ? ",[$order_id , 0]);
		if(!empty($recharge_result)){
			$up_result = DB::update("update dql_account_recharge set status = 1 ,amount = money , verify_time = ? where trade_no = ? " , [time() , $order_id]);
			$account_result = DB::select("select * from dql_account where user_id = ? " ,[$recharge_result[0]->user_id]);
			if($up_result && !empty($account_result)){
				$log_add['user_id'] = $recharge_result[0]->user_id;
				$log_add['type'] = "recharge" ;
				$log_add['flow'] = "in" ;
				$log_add['total'] = $account_result[0]->total + $recharge_result[0]->money ; 
				$log_add['money'] = $recharge_result[0]->money ; 
				$log_add['use_money'] = $account_result[0]->use_money + $recharge_result[0]->money ;
				$log_add['no_use_money'] = $account_result[0]->no_use_money ;
				$log_add['collection'] = $account_result[0]->collection ; 
				$log_add['remark'] = "充值{$log_add['money']}元成功" ; 
				$log_add['addtime'] = time();
				$log_add['addip'] = Request::server("REMOTE_ADDR");
				$insert_log = DB::insert("insert into dql_account_log(user_id,type,flow,total,money,use_money,no_use_money,collection,remark,addtime,addip) values(?,?,?,?,?,?,?,?,?,?,?)" , array_values($log_add)) ;
				if($insert_log){
					$up_account = DB::update("update dql_account set total = ?, use_money = ? where user_id = ? ",[$log_add['total'],$log_add['use_money'],$log_add['user_id']]);
					if($up_account){
						return true;
					}
				}
			}
		}
		return false;
	}
	
	public function recharge_notify(Request $request){
		dd($request);
	}
	
	public function recharge_return(Request $request){
		$return_result = $this->rebuild_rb_data($request::get("data"),$this->ending_key);
		//$return_result = $this->rebuild_rb_key($request::get("data"));
		dd($return_result);
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
	
	public function rebuild_rb_data($treasure_data,$ending_key){
		$td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');
		$iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
		//$decrypted= mcrypt_decrypt(MCRYPT_RIJNDAEL_128,$ending_key,$treasure_data,MCRYPT_MODE_ECB,$iv);
		//$dec_s = strlen($decrypted);
		//$padding = ord($decrypted[$dec_s-1]);
		//$decrypted = substr($decrypted, 0, -$padding);
		mcrypt_generic_init($td,$ending_key,$iv);
		$treasure_data = mdecrypt_generic($td,$treasure_data);
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		dd($treasure_data);
		return $treasure_data;
	}
	
	public function rebuild_rb_key($treasure_key){
		$private_key= file_get_contents(resource_path().$this->private_key);
		$private_key_yes =  openssl_pkey_get_private($private_key);
		openssl_private_decrypt($treasure_key,$return_keys,$private_key_yes);
		return $return_keys;
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
