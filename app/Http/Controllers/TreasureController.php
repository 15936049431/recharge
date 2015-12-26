<?php namespace App\Http\Controllers;

class TreasureController extends Controller {


	public function __construct()
	{
		$this->middleware('guest');
	}

	public function recharge(){
		var_dump(Request);
	}
}
