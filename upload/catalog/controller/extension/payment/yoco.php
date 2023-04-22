<?php
use \Yoco\YocoClient;
use \Yoco\Exceptions;
class ControllerExtensionPaymentYoco extends Controller
{
    protected $testmode;
	protected $secret_key;
	protected $private_key;

	const CHECKOUT_MODEL = "checkout/order";
	const INFORMATION_CONTACT = "information/contact";

	
	protected $data_to_send = array();
	

	public function getCurrency(){
		if ( $this->config->get( 'config_currency' ) != '' ) {
			$currency = filter_var( $this->config->get( 'config_currency' ), FILTER_SANITIZE_STRING );
		} else {
			$currency = filter_var( $this->currency->getCode(), FILTER_SANITIZE_STRING );
		}

		return $currency;
	}


	
    public function index()
    {
      //  unset( $this->session->data['REFERENCE'] );
		$this->load->language( 'extension/payment/yoco' );

		$dateTime  = new DateTime();
		$time = $dateTime->format( 'YmdHis' );

        $data['text_loading']   = $this->language->get( 'text_loading' );
        $data['button_confirm'] = $this->language->get( 'button_confirm' );
        $data['continue']       = $this->language->get( 'payment_url' );

		$yoco_data = array();
		$yoco_data['text_checkout'] = "checkout";
		//$pay_method_data = array();

       $this->load->model( self::CHECKOUT_MODEL );
		$order_id = $this->session->data['order_id'] ;
	   $order_info = $this->model_checkout_order->getOrder( $order_id);

	   if ( empty( $_POST ) && $order_info['payment_code'] === 'yoco' ) {
	
	
		$testmode = $this->config->get( 'payment_yoco_testmode' ) === 'test';
		$this->secret_key	= $this->config->get( 'payment_yoco_live_secret_key' );
		$this->private_key = $this->config->get( 'payment_yoco_live_public_key' );
		if($testmode){
		$this->secret_key		= $this->config->get( 'payment_yoco_test_secret_key' );
		$this->private_key		= $this->config->get( 'payment_yoco_test_public_key' );
		}
		$yoco_data['publickey'] = $this->private_key;
		$yoco_data['secret_key'] = $this->secret_key;
		$preAmount =  $order_info['total'];
		$amount    = substr(''.$preAmount, 0, -2);
		$yoco_data['amountinCents'] = $amount;
		 $currency = $this->getCurrency();
		 $yoco_data['currency'] =$currency;
		 $yoco_data['order_id'] =$order_id;
		 $yoco_data['bill_note'] ='ORD-'.$order_id;
		 $yoco_data['customer_name'] = $order_info['payeeCategory2'];
		 $yoco_data['customer_email'] =$order_info['email'];
		 $yoco_data['modal_title'] =$order_info['payeeOrderItemDescription'];
		 $yoco_data['order_summary'] =$order_info['payeeOrderItemName'];
		 
		 
	   } 
	
        if ( empty( $_POST ) && $order_info['payment_code'] === 'yoco' ) {
			return $this->load->view('extension/payment/yoco', $yoco_data);
		//	die();
		}
		else{
			if (isset($order_info)  ) {
				if ($order_info['payment_code'] === 'yoco' )
				
				$order_id = $this->session->data['order_id'] ;
		$this->change();
			}
		
		}

		header( 'HTTP/1.0 200 OK' );
		flush(); 
		
		
    }

public function change(){

$token = $_POST['token'];
$amountInCents = $_POST['amountInCents'];
$currency = $_POST['currency'];
$metadata = $_POST['metadata'] ?? [];

$client = new YocoClient($this->secret_key, $this->private_key);

// note the keys in use
$env = $client->keyEnvironment();
error_log("Using $env keys for payment");
$result = array();
// process the payment
try {
  $result =  print(json_encode($client->charge($token, $amountInCents, $currency, $metadata)));
} catch (Exceptions\ApiKeyException $e) {
    error_log("Failed to charge card with token $token, amount $currency $amountInCents : " . $e->getMessage());
    Header("HTTP/1.1 400 Bad Request");
 echo   print(json_encode(['charge_error' => $e]));
    exit;    
} catch (Exceptions\DeclinedException $e) {
    error_log("Failed to charge card with token $token, amount $currency $amountInCents : " . $e->getMessage());
    Header("HTTP/1.1 400 Bad Request");
  echo  print(json_encode(['charge_error' => $e]));
    exit;
} catch (Exceptions\InternalException $e) {
    error_log("Failed to charge card with token $token, amount $currency $amountInCents : " . $e->getMessage());
    Header("HTTP/1.1 400 Bad Request");
  echo  print(json_encode(['charge_error' => $e]));
    exit;
}

if($result){
	$this->model_extension_payment_yoco->log_to_file( $result );
}

	header( 'HTTP/1.0 200 OK' );
	flush();	
}
	
	public function askForRequestedArguments(){
		$getArray = ($tmp = filter_input_array(INPUT_GET)) ? $tmp : Array();
		$postArray = ($tmp = filter_input_array(INPUT_POST)) ? $tmp : Array();
		$allRequests = array_merge($getArray, $postArray);
		return $allRequests;
	}
 
	// public function callback_success() {

	// 	$this->load->model('extension/payment/yoco');
	// 	//error_log("complete:::var_dump".PHP_EOL);
	// 		$doc_data = $this->askForRequestedArguments();
	// 	$this->model_extension_payment_instapaywebpay->logger($doc_data);
	// 	$this->load->model( self::CHECKOUT_MODEL );

		
		
		
	// 	//$order_id = $doc_data[2];
	// 	$action = $doc_data['action'];
	// 	$order_id = (int)$doc_data['order_id'];
		
	// 	$order_info = $this->model_checkout_order->getOrder($order_id);
	// 	//	$order_info = $this->model_checkout_order->getOrder( $order_id);
	// 	$resultsComment ='';
	// 		if($action === 'success'){
	// 			$orderStatusId = $this->config->get( 'payment_instapaywebpay_success_order_status_id' );
		
	// 			$resultsComment = "Notify response from InstaPay WebPay with a status of COMPLETED";
	// 			$nData = array();
	// 	$nData['orderStatusID'] = $orderStatusId;
	// 	$nData['orderID'] = $order_id;
	// 	$nData['action'] = $action;
	// 	$nData['orderDetail'] = 'Order for '.$order_info['firstname'].' order no:'.$order_id;
	// 	$this->model_extension_payment_instapaywebpay->logger($nData);
		
	// 			$this->model_checkout_order->addOrderHistory($order_id,$orderStatusId, $resultsComment, false , true);
	// 			$data['success'] = $this->url->link('checkout/success', '', true);

	// 	//$this->response->redirect($this->url->link('checkout/success'));
	// 	$this->response->addHeader('Content-Type: application/json');
	// 		$this->response->setOutput(json_encode($data));	
	// 		}

			
		
	// }

	public function postToken(){
		$n_data  =  $this->askForRequestedArguments();
		$this->load->language('extension/payment/yoco');
		$testmode = $this->config->get( 'payment_yoco_testmode' ) === 'test';
		$this->secret_key				= $this->config->get( 'payment_yoco_live_secret_key' );
		$this->private_key		= $this->config->get( 'payment_yoco_live_public_key' );

		if($testmode){
			$this->secret_key				= $this->config->get( 'payment_yoco_test_secret_key' );
		$this->private_key		= $this->config->get( 'payment_yoco_test_public_key' );
		}
		$this->load->model('extension/payment/yoco');

		$token = $n_data['token'];
		$order_id = $n_data['order_id'];
		$this->model_extension_payment_yoco->log_to_file( $n_data );
		$this->load->model( self::CHECKOUT_MODEL );

        $order_info = $this->model_checkout_order->getOrder( $order_id);
	$preAmount =  $order_info['total'];
	$currency = $this->getCurrency();
	$meta = array();
	$meta['order_id'] = $order_id;
	$r = array();
			$amount_in_cents    = substr(''.$preAmount, 0, -2);
	$r = $this->model_extension_payment_yoco->PostPayment($secret_key,$private_key, $token,($amount_in_cents * 100),$currency,$meta);
	$this->model_extension_payment_yoco->log_to_file( $r );
	}


	public function getOrderIdFromSession(){
		// Get order Id from query string as backup if session fails
        $m = [];
		$orderId = 0;
        preg_match( '/^.*\/(\d+)$/', $_GET['route'], $m );
        if ( count( $m ) > 1 ) {
            $orderId = (int) $m[1];
        } elseif ( isset( $this->session->data['order_id'] ) ) {
            $orderId = (int) $this->session->data['order_id'];
        }

		return $orderId;
	}

	
	public function setActivityData($order,$orderId){
	//	$this->load->model('customer/order');
		if ( $this->customer->isLogged() ) {
			$activity_data = array(
				'customer_id' => $this->customer->getId(),
				'name'        => $this->customer->getFirstName() . ' ' . $this->customer->getLastName(),
				'order_id'    => $orderId,
			);
			$this->model_account_activity->addActivity( 'order_account', $activity_data );
		} else {
			$activity_data = array(
				'name'     => $order['firstname'] . ' ' . $order['lastname'],
				'order_id' => $orderId,
			);
			$this->model_account_activity->addActivity( 'order_guest', $activity_data );
		}
	}


	

  

	
	
	public static function get_order_prop( $order, $prop ) {
		switch ( $prop ) {
			case 'order_total':
				$getter = array( $order, 'get_total' );
				break;
			default:
				$getter = array( $order, 'get_' . $prop );
				break;
		}

		return is_callable( $getter ) ? call_user_func( $getter ) : $order->{ $prop };
	}



	

}