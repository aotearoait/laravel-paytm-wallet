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
			'user' => NULL,
			'amount' => NULL,
            'callback_url' => NULL,
            'email' => NULL,
            'mobile_number' => NULL,
            'subscription_grace_days' => NULL,
            'subscription_start_date' => date("Y-m-d")
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

        if($this->parameters['request_type'] == 'SUBSCRIBE' || $this->parameters['request_type'] == 'RENEW_SUBSCRIPTION'){
            $subscription_params = [
                'SUBS_SERVICE_ID' => $this->parameters['subscription_id'], /// Subscription ID ALPHANUMERIC
				'SUBS_AMOUNT_TYPE' => $this->parameters['subscription_type'], /// FIXED or VARIABLE,
				'SUBS_FREQUENCY' => $this->parameters['subscription_frequency'],
                'SUBS_FREQUENCY_UNIT' => $this->parameters['subscription_frequency_unit'], /// Subscription frequecy DAY / MONTH / YEAR
                'SUBS_ENABLE_RETRY' => 1, /// Auto retry failed transaction
                'SUBS_EXPIRY_DATE' => $this->parameters['subscription_expiry'], /// YYYY-MM-DD
                'SUBS_MAX_AMOUNT' => $this->parameters['subscription_maximum_payment'], /// Maximum amount of a single payment
                'SUBS_START_DATE' => $this->parameters['subscription_start_date'], /// YYYY-MM-DD used to specify first payment, can be in future for trial scenarios
                'SUBS_GRACE_DAYS' => $this->parameters['subscription_grace_days'],
            ];
			$params = array_merge($params,$subscription_params);
			
			if(isset($this->parameters['subscription_payment_mode']) && $this->parameters['subscription_payment_mode'] == 'CC'){

				$cc_params = [
					'SUBS_PPI_ONLY' => 'N',
					'SUBS_MAYMENT_MODE' => 'CC',
					'AUTH_MODE' => '3D'
				];
			}
			else{
				$cc_params = [
					'SUBS_PPI_ONLY' => 'Y'
				];
			}
			$params = array_merge($params,$cc_params);
        }
        if($this->parameters['request_type'] == 'RENEW_SUBSCRIPTION'){

			$params = [
				'REQUEST_TYPE' => $this->parameters['request_type'],
				'MID' => $this->merchant_id,
				'ORDER_ID' => $this->parameters['order'],
				'SUBS_ID' => $this->$parameters['subscription_id'],
				'TXN_AMOUNT' => $this->parameters['amount']
				

			];

		}
		
		if($this->parameters['request_type']=='CANCEL' || $this->parameters['request_type']=='REFUND'){

			$params = [
				'TXNTYPE' => $this->parameters['request_type'],
				'MID' => $this->merchant_id,
				'ORDER_ID' => $this->parameters['order'],
				'SUBS_ID' => $this->$paramaters['subscription_id'],
				'REFUNDAMOUNT' => $parameters['refund_amount'],
				'REFID' => $parameters['refund_id']
				

			];
		}

		return view('paytmwallet::form')->with('view', $this->view)->with('params', $params)->with('txn_url', $this->paytm_txn_url)->with('checkSum', getChecksumFromArray($params, $this->merchant_key));
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