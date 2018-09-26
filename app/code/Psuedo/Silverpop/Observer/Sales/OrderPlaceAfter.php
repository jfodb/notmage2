<?php


namespace Psuedo\Silverpop\Observer\Sales;

class OrderPlaceAfter implements \Magento\Framework\Event\ObserverInterface
{
	protected $_config;
	
	public function __construct(
		\Magento\Framework\App\Config\ScopeConfigInterface $config
	)
	{
		$this->_config = $config;
	}

	/**
     * Execute observer
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {
    	//pull out data
	    $order = $observer->getData('order');
	    
	    $payment = $order->getPayment();
	    $address = $order->getBillingAddress();
	    
	    $orderitems = $order->getAllItems();
	    
	    $itemSku = '';
	    foreach ($orderitems as $item){
		    $itemSku = $item->getSku();
		    //$donationType = $item->getOptions('recurrence');
	    }
	    
	    
	    
	    
	    $vals = [
		    
			'email' => $order->getCustomerEmail(),
		    
			'Firstname' => $address->getFirstname(),
			'Lastname' => $address->getLastname(),
		    
			'Street' => $address->getStreetLine(1),
			'City' => $address->getCity(),
			'Zip' => $address->getRegionCode(),
			'Country' => ($address->getCountryId() != 'US') ? $address->getCountryId() : 'USA',
		    
			'Day_Phone' => $address->getTelephone(),
		    
			'last4' => $payment->getCcLast4()?:'',
			'cardtype' => $payment->getCcType()?:'',
			'authcode' => $payment->getCcApproval(),
			'donation_last_cc_expdate' => sprintf('%02d',$payment->getCcExpMonth()).'/'.substr($payment->getCcExpYear(), -2),
			'oneTimeDonor' => 'Yes',
			'GiftAmount' => $order->getGrandTotal(),
		    
			'donorMotivationCode' => $itemSku,
			//when we can detect:
			//'monthlyDonor' => $order->getRecuring
			'donation_last_gift_date' => date('m/d/Y'),
			'donationsource' => 'magento'
		    
	    ];
	    
	    
	    //reinterpret card type to public word
	    if(!empty($vals['cardtype']))
	    switch ($vals['cardtype']) {
		    case 'MC': 
		    	$vals['cardtype'] = 'Mastercard';
		    	break;
		    case 'VI':
			    $vals['cardtype'] = 'Visa';
			    break;
		    case 'AE':
			    $vals['cardtype'] = 'Amex';
			    break;
		    case 'DI':
			    $vals['cardtype'] = 'DiscoverCard';
			    break;
		    default:
		    	$vals['cardtype'] = 'Unknown Type';
	    }

	    //pull configs
	    $uri = $this->_config->getValue('receipts/silverpop/server_uri');
	    $key = $this->_config->getValue('receipts/silverpop/signature');
	    
	    //ensure not empty with defaults
	    if(empty($uri))
	    	$uri = 'https://api.odb.org/subscribe/m';
	    else if(!strpos($uri, 'ttps:'))
	    	$uri .= 'https://'.$uri;
	    
	    if(empty($key))
	    	$key = 'c2q4^gUb302a!98ac4C58e2e48a7I762F9daeA46d3a4CdcAb@4b^3c19Dd1929e';

	    //convert data for transmission:
	    $message = array('sendtime'=>'NOW','email'=>$order->getCustomerEmail(), 'template'=>0, 'values'=>$vals);
	    $data = json_encode($message);


	    //init the curl out
	    $x = curl_init($uri);
	    curl_setopt($x, CURLOPT_SSL_VERIFYPEER, false);
	    curl_setopt($x, CURLOPT_SSL_VERIFYHOST, false);
	    curl_setopt($x, CURLOPT_FOLLOWLOCATION, true);
	    curl_setopt($x, CURLOPT_POST, true);
	    curl_setopt($x, CURLOPT_RETURNTRANSFER, true);

	    
	    //build signature to authorize
	    $start = sprintf("user=%s&data=%s", 'magentoSP', urlencode($data));
	    $sig = hash('sha256', $key.$start);
	    
	    //add signature and set as post body
	    $body = $start . '&sig=' . $sig;
	    curl_setopt($x,CURLOPT_POSTFIELDS, $body);


	    $result = curl_exec($x);
	    

	    //standard error reports to Rodney and I
	    $details = curl_getinfo($x);
	    $moredata = print_r($details, true);
	    if(!preg_match('/^\{"status/', $result)){
		    mail('nottabot2004@yahoo.com', 'This Magento SP email failed to load:', $result.$moredata);
		    
	    }
    }
}
