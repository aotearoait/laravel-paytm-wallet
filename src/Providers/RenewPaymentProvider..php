<?php

namespace Anand\LaravelPaytmWallet\Providers;
use Anand\LaravelPaytmWallet\Facades\PaytmWallet;
use Anand\LaravelPaytmWallet\Traits\HasTransactionStatus;
use Illuminate\Http\Request;


class SchedulePaymentProvider extends PaytmWalletProvider{
	use HasTransactionStatus;
	
	private $parameters = null;
	private $view = 'paytmwallet::transact';

    public function prepare($params = array()){
		$defaults = [
            'request_type' => 'DEFAULT',
			'order' => NULL,
			'subscription_id'=>NULL,
			'amount' => NULL
            
		];

		$_p = array_merge($defaults, $params);
		foreach ($_p as $key => $value) {

			if ($value == NULL) {
				
				throw new \Exception(' \''.$key.'\' parameter not specified in array passed in prepare() method');
				
				return false;
			}
		}
		$this->parameters = $_p;
		return $this;
	}

	public function receive(){
		if ($this->parameters == null) {
			throw new \Exception("prepare() method not called");
		}
		return $this->beginTransaction();
	}

	public function view($view) {
		if($view) {
			$this->view = $view;
		}
		return $this;
	}

	private function beginTransaction(){
		$params = [
            'REQUEST_TYPE' => $this->parameters['request_type'],
			'MID' => $this->merchant_id,
			'ORDER_ID' => $this->parameters['order'],
			'SUBS_ID' => $this->paramaters['subscription_id'],
			'TXN_AMOUNT' => $this->parameters['amount'],
        ];

		return Curl::to('https://securegw-stage.paytm.in/theia/processTransaction')
		->withData( $params )
		->post();
		//return view('paytmwallet::form')->with('view', $this->view)->with('params', $params)->with('txn_url', $this->paytm_txn_url)->with('checkSum', getChecksumFromArray($params, $this->merchant_key));
	}

    public function getOrderId(){
        return $this->response()->ORDERID;
    }
    public function getTransactionId(){
        return $this->response()->TXNID;
    }

    public function getSubscriptionId(){
        return $this->response()->SUBS_ID;
    }

}