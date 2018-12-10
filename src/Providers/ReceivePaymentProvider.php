<?php

namespace Anand\LaravelPaytmWallet\Providers;
use Anand\LaravelPaytmWallet\Facades\PaytmWallet;
use Anand\LaravelPaytmWallet\Traits\HasTransactionStatus;
use Illuminate\Http\Request;

class ReceivePaymentProvider extends PaytmWalletProvider{
	use HasTransactionStatus;
	
	private $parameters = null;
	private $view = 'paytmwallet::transact';

    public function prepare($params = array()){
		$defaults = [
			'request_type' => 'DEFAULT',
			'order' => NULL,
			'user' => NULL,
			'amount' => NULL,
            'callback_url' => NULL,
            'email' => NULL,
			'mobile_number' => NULL,
			'subscription_id' => NULL,
			'subscription_type' => NULL,
			'subscription_frequency' => NULL,
			'subscription_expiry' => NULL,
			'subscription_maximum_payment' => NULL,
			'subscription_start_date' => NULL,
			'subscription_grace_days' => NULL
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
			'CUST_ID' => $this->parameters['user'],
			'INDUSTRY_TYPE_ID' => $this->industry_type,
			'CHANNEL_ID' => $this->channel,
			'TXN_AMOUNT' => $this->parameters['amount'],
			'WEBSITE' => $this->merchant_website,
            'CALLBACK_URL' => $this->parameters['callback_url'],
            'MOBILE_NO' => $this->parameters['mobile_number'],
            'EMAIL' => $this->parameters['email'],
		];
		return view('paytmwallet::form')->with('view', $this->view)->with('params', $params)->with('txn_url', $this->paytm_txn_url)->with('checkSum', getChecksumFromArray($params, $this->merchant_key));
	}

    public function getOrderId(){
        return $this->response()->ORDERID;
    }
    public function getTransactionId(){
        return $this->response()->TXNID;
    }

}