<?php
/**
 * Created by PhpStorm.
 * User: ppostma
 * Date: 5/21/18
 * Time: 2:21 PM
 */


namespace Psuedo\Mpxdownload\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
	protected $db, $db_resource, $ips, $domain, $timeoffset, $store, $motivation, $company, $jobtype, $_err_code, $object_manager;
	protected $ip, $err, $err_message, $connection_good;
	protected $productModel;
	public $START_STATUS, $PROCESS_STATUS, $END_STATUS;

	static $ENCODE_BYTE_MAP, $NIBBLE_GOOD_CHARS;



	public function set_source(\Magento\Framework\App\ResourceConnection $dbresource,
	                      \Magento\Framework\DB\Adapter\Pdo\Mysql $dbconnection,
	                      \Magento\Catalog\Model\Product $productModel)
	{
		$this->db_resource = $dbresource;
		$this->db = $dbconnection;
		$this->productModel = $productModel;


		$this->connection_good = false;


		//$this->object_manager = \Magento\Framework\App\ObjectManager::getInstance();

		// !-- all connection can now be done through a single domain (any of them)
		// !-- the passed company and job type will determine what store is being pulled.
		//$this->domain = $_SERVER['HTTP_HOST'];
		$this->company = strtolower((!empty($_GET['company']))?$_GET['company']:'');
		$this->jobtype = strtolower(@$_GET['jobtype']);

		//load company and job type to a domain to load configs from.  If this fails, output the error AFTER the connection check by setting err and err_message (not _err and err_message)
		if(! (preg_match('/^[a-z0-9]+$/', $this->company) && preg_match('/^[a-z0-9]+$/', $this->jobtype) )){
			$this->err = true;
			$this->err_message = 'Company or Jobtype not set to valid value';
		} else {
			$xml_key = "psuedo_mpxdownload/runtime/domain/{$this->jobtype}/{$this->company}";
			$this->domain = $this->scopeConfig->getValue($xml_key, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

			if(empty($this->domain)){
				$this->err = true;
				$this->err_message = "company and jobtype did not distinguish a domain ".$xml_key;
			} else {
				$this->err = false;
			}
		}


		//read-configs
		$ipstring = $this->scopeConfig->getValue("psuedo_mpxdownload/runtime/trustedips", \Magento\Store\Model\ScopeInterface::SCOPE_STORE);


		//pull the cut off time from admin configs
		$tmpcutoff = $this->scopeConfig->getValue('mpxdownloads/general/cutoff_time', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		if(!empty($tmpcutoff) && is_string($tmpcutoff) && preg_match('/^[0-2][0-9]:[0-5][0-9]:[0-5][0-9]$/', $tmpcutoff)){
			$this->timeoffset = $tmpcutoff;
		}

		//fallback to xml configs
		if(empty($this->timeoffset))
			$this->timeoffset = $this->scopeConfig->getValue("psuedo_mpxdownload/runtime/timezone", \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		//fallback to hard number
		if (empty($this->timeoffset))
			$this->timeoffset = "07:00:05";

		$this->store = $this->scopeConfig->getValue("psuedo_mpxdownload/runtime/store_id/{$this->domain}", \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

		$this->motivation = $this->scopeConfig->getValue("psuedo_mpxdownload/runtime/motivation_code/{$this->domain}", \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

		$this->ips = preg_split('/[;,]\s*/', $ipstring);
		//print_r($this->ips);

		$this->START_STATUS = 'unprocessed';
		
		$this->PROCESS_STATUS = 'processing';
		
		$this->END_STATUS = 'processed';
		

		$this->productModel->setStoreId($this->store);

		/*if($this->jobtype == 'ccstore' || $this->jobtype == 'dhpstore') {
			//prep ODB-CCU orders for processing:

			$storeid = intval($this->store);

			//revert download-only orders that have not been processed.
			$this->db->query('UPDATE `sales_order` set `status`="paid", `state`="processing" WHERE store_id='.$storeid.' AND `state`="complete" AND `status`="complete" AND ext_order_id IS NULL AND base_total_paid>0.01;');

			//advance shipping-product orders that have been paid.
			$this->db->query('UPDATE `sales_order` set `status`="paid", `state`="processing" WHERE store_id='.$storeid.' AND `state`="processing" AND `status`="processing" AND ext_order_id IS NULL AND base_total_paid>0.01;');

		}
		else if($this->jobtype == 'pilotstore' || $this->jobtype == 'odbstore') {
			//now revert the new free-orders from "left hanging" to paid, if they are $0, were sent a confirmation email, and have a shipping address
			$this->db->query('UPDATE `sales_order` set `status`="paid", `state`="processing" WHERE store_id=8 AND `state`="new" AND `status`="pending" AND `base_grand_total`=0.0 AND `email_sent`=1 AND `quote_id` IS NOT NULL AND `shipping_address_id` IS NOT NULL');
		}
		*/
	}

	function get_ip(){
		//static $this_ip;

		if(!empty($this->ip))
			return $this->ip;

		//grab obvious IP address
		$gateway = $_SERVER['REMOTE_ADDR'];
		//grab forwards
		$iplist = @$_SERVER['HTTP_X_FORWARDED_FOR'];

		if(!empty($iplist)) {
			//split forward into individual IPs
			$ips = preg_split('/, ?/', $iplist);

			$first = $ips[0];
			//loop through and remove all "local subnet" ips
			foreach($ips as $key=>$addr) {
				if(preg_match('/^192\.168/', $addr) || preg_match('/^10\./', $addr))
					unset($ips[$key]);
			}
			//if all are local, toss and use remote address
			if(empty($ips))
				$ip_address = $gateway;
			else{
				//re-index the array to get the internet routed IPs
				$ips = array_values($ips);
				//check to see if the first in the row was a good IP
				if($first == $ips[0])
					$ip_address = $ips[0];
				else
					$ip_address = $ips[count($ips)-1];  //last IP should be the source
				//forwarded ips work like this:
				// forwarded: 1
				// forwarded: 1, 2
				// forwarded: 1, 3, 2
				// forwarded: 1, 4, 3, 2
				// if 1 is not a local subnet, its the IP
				// but 1 is usually a local subnet, the actual IP is the last non-subnet
			}
		} else {
			$ip_address = $gateway;
		}

		$this->ip = $ip_address;

		return $this->ip;
	}

	function api_clear_reset()
	{
		//remove all traces we have been here.
		if (!empty($this->db))
		{
			try{
				$sql = sprintf("UPDATE `%s` SET `ext_order_id`=NULL, `mpx_status`='%s' WHERE `ext_order_id` IS NOT NULL;", $this->db_resource->getTableName('sales_order'), $this->START_STATUS);
				$this->db->query($sql);
			} catch(\Exception $e) {
				$this->api_return_error(503, "Could not reset Job");
				return false;
			}

			return true;
		}
		return false;
	}

	function api_rollback_job()
	{

		if (empty($this->db))
		{
			$this->api_return_error(506, "Database failure in rollback");
			return false;
		}

		//job id
		$jobid = (!empty($_GET["job_id"]))?$_GET["job_id"]:'';
		if (empty($_GET["job_id"]) || !is_numeric($_GET["job_id"]))
		{
			$this->api_return_error(417, "Job ID was not valid int type.");
			return false;
		}

		$sql = sprintf("SELECT `entity_id` FROM `%s` WHERE `store_id`=%d AND `ext_order_id` = %d;", $this->db_resource->getTableName('sales_order'),  $this->store, $jobid);
		$result_set = $this->db->fetchAll($sql);
		if(count($result_set)>0){
			$ids = array();
			foreach ($result_set as $r)
				$ids[] = $r['entity_id'];
		} else
			$ids = false;

		$sql = sprintf("UPDATE `%s` SET `ext_order_id`=NULL, `mpx_status`='%s' WHERE `store_id`=%d AND `ext_order_id` = %d;", $this->db_resource->getTableName('sales_order'), $this->START_STATUS, $this->store, $jobid);
		try {
			$this->db->query($sql);
			return true;
		} catch (\Exception $e) {
			$this->api_return_error(503, "Could not reset Job");
			return false;
		}

		return false; //backstop
	}


	function api_return_error($code, $message)
	{

		//HttpContext.Current.Response.Headers.Add("Msxhdr", message);
		$this->_err_code = $code;
		$this->err_message = $message;
		header('Content-type: text/html; charset=utf-8', true, $code);
		header('Xmessage: '.$code);
		if($code == 204) {
			header('Content-length: 0');
		}
		else
		{
			echo "<html><head><title>", $code, "</title></head>\r\n<body><h1>";
			echo $code, "</h1><p>", $message, "</p></body></html>";
		}

	}


	function api_check_connection()
	{
 		if( !($_SERVER['SERVER_NAME'] == 'dev.mage2.org' || $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' || !empty($_SERVER['HTTPS'])) ){
			$this->api_return_error(412, 'Must be done through a secure socket.');
			return false;
		}

		if(empty($this->db)) {
			$this->api_return_error(506, "No database connection found");
			return false;
		}


		$remote = $this->get_ip();
		$found = false;
		foreach ($this->ips  as $ip)
		{
			if(empty($ip))
				continue;

			if ($pos=strpos($ip, "*") > 3)
			{
				$tmpsafe = substr($ip, 0, $pos);
				//$tmpremote = remote.Substring(0, tmpsafe.Length);
				$tmpsafe = str_replace('.', '\.', $tmpsafe);
				if (preg_match("/^$tmpsafe/", $remote))
				{
					$found = true;
					break;
				}
			}
			else
				if ($ip == $remote)
				{
					$found = true;
					break;
				}
		}
		if (!$found)
		{
			$this->_logger->notice("Refused connection from: $remote\n");
			$this->api_return_error(403, "Not Authorized");
			return false;
		}

		//string service = System.Configuration.ConfigurationManager.AppSettings["MpxServiceRunning"];
		//if (string.IsNullOrEmpty(service) || !service.ToLower().Equals("on"))
		//{
		//    api_return_error(503, "The service is currently off");
		//    return false;
		//}

		$this->connection_good = true;

		if($this->err){
			$this->_logger->notice("unable to load config values: {$this->err_message}\n");
			$this->api_return_error(405, $this->err_message);
			return false;
		}

		return true;
	}


	function api_validate_login_command()
	{
		if (!($this->connection_good || $this->api_check_connection()))
		{
			//$this->api_return_error(506, "No database connection found.");
			return false;
		}

		$query = $_SERVER["QUERY_STRING"];
		$sig = (!empty($_GET["sig"]))?$_GET["sig"]:'';
		if (empty($sig))
		{
			$this->api_return_error(405, "Signature was not found");
			return false;
		}


		//pull the password of user
		$username = (!empty($_GET["user"]))?$_GET["user"]:'';
		if (empty($username))
		{
			$this->api_return_error(401, "No user information was sent");
			return false;
		}


		$sql = sprintf("SELECT * FROM `%s` WHERE username=%s", $this->db_resource->getTableName('admin_user'), $this->db->quote($username));
		try{
			$result = $this->db->fetchAll($sql);
		} catch(\Exception $e) {
			$this->api_return_error(506, "Unable to connect to database or query in login.");
			return false;
		}

		if (!$result || count($result) == 0 || $result[0]["username"] != $username || $result[0]['is_active'] == 0)
		{
			$this->api_return_error(401, "Security Check Failed.");
			return false;
		}

		//grab the command string

		$secret = $result[0]["password"];
		if (empty($query) || strlen($query) < 9)
		{
			$this->api_return_error(405, "No query string found");
			return false;
		}
		if(strlen($secret)>32)
			$secret = substr($secret, 0, 32);
		$sigposition = strpos($query, "&sig", 9);
		if ($sigposition <= 0)
		{
			$this->api_return_error(405, "Missing call or signature in request.");
			return false;
		}
		$query = substr($query, 0, $sigposition);

		//validate the command from sig
		$signature = md5($secret . $query);

		if ( strtolower($sig) != strtolower($signature))
		{
			$this->api_return_error(401, "Signature did not validate.");
			return false;
		}

		return true;
	}

	function api_login_command()
	{
		$return_val = false;


		// done in validate_login...
		//$result = $this->api_check_connection();
		//if (!$result)
		//    return false;

		$result = $this->api_validate_login_command();


		if (!$result)
			return false;

		$operation = (!empty($_GET["action"]))?$_GET["action"]:'';
		if (empty($operation))
		{
			$this->api_return_error(404, "Action not recognized");
			return false;
		}

		if ("export" == $operation)
			$return_val = $this->api_run_export();

		else if ("checkback" == $operation)
			$return_val = $this->api_run_checkback();

		else if ("rollback" == $operation || ("purge" == $operation && !empty($_GET['job_id'])))
			$return_val = $this->api_rollback_job();

		else if ("purge" == $operation ){
			//$this->api_clear_reset();
			//$this->api_return_error(205, "complete");
			$this->api_return_error(202, "Accepted");
		}

		else
			$this->api_return_error(404, "Action not recognized.");


		return $return_val;
	}


	function api_run_export()
	{
		$job_id = intval( (!empty($_GET["job_id"]))?$_GET["job_id"]:0  );

		if (empty($job_id) || $job_id == 0)
		{
			$this->api_return_error(417, "Job ID was not valid int type.");
			return false;
		}

		$startdate = (!empty($_GET["start_date"]))?$_GET["start_date"]:'' ;
		$start_date = '';
		if (empty($startdate) || ! $start_date = strtotime($startdate))
		{
			$this->api_return_error(471, "Start Date did not parse to time");
			return false;
		}
		$end_date = strtotime("+1 day", $start_date);

		
		//check for caching.
		$file_cache_key =  sprintf( "mpx-%s-%s.json", pathinfo($startdate, PATHINFO_BASENAME), $this->store);
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$directories = $objectManager->get('\Magento\Framework\Filesystem\DirectoryList');
		$directory = $directories->getPath('log');
		
		$file_cache = $directory . '/' . $file_cache_key;
		
		if(file_exists($file_cache)){
			if(filesize($file_cache)>100){
				$output = file_get_contents($file_cache);

				header('Content-type: text/plain; charset=utf-8', true, 200);
				$tmpdata = json_decode($output, true);
				if($tmpdata) {
					$tmpdata['served_cached'] = 'true';
					$output = json_encode($tmpdata);
				}
				header('Content-length: '.strlen($output));

				if(ob_get_level())
					ob_clean();
				echo $output;
				if(ob_get_level())
					ob_flush();
				
				return;
			}
		}

		
		

		//see if job must be reset
		$sql = sprintf("SELECT * from `%s` WHERE `store_id`=%d AND `ext_order_id`=%d;", $this->db_resource->getTableName('sales_order'), $this->store, $job_id);

		try{
			$row = $this->db->fetchRow($sql);
		} catch(\Exception $e) {
			//nothing
		}

		if (!empty($row) && count($row) > 0)
		{

			//: call the function instead:
			$this->api_rollback_job();
			usleep(400);   /* database has to update before we continue... */


			//} catch(Exception $e) {

			//}

		}



		//updated_at
		$sql  = sprintf("SELECT orders.*, ocache.* FROM `%s` orders LEFT JOIN `%s` ocache ON ocache.order_id=orders.entity_id WHERE store_id=%d AND mpx_status='%s' AND created_at>='%s' AND created_at<'%s' AND ext_order_id IS NULL;",
			$this->db_resource->getTableName('sales_order'),
			$this->db_resource->getTableName('mpx_flat_orders'),
			$this->store,
			$this->START_STATUS,
			date('Y-m-d ', $start_date).$this->timeoffset,
			date('Y-m-d ', $end_date).$this->timeoffset
		);

		try
		{
			$orderRows = $this->db->fetchAll($sql);
		}
		catch (\Exception $sqle)
		{
			$this->api_return_error(503, "Database error filtering rows");
			$this->_logger->addNotice($sql);
			return false;
			//throw($sqle);  //pitch it and move on.
		}


		if (is_null($orderRows) )
		{
			$this->api_return_error(506, "Failed to load orders");
			return false;
		}


		try{
			$affilresults = $this->db->fetchRow("SHOW TABLES LIKE 'affiliateplus_transaction';");
			if(!$affilresults || count($affilresults) == 0)
				$affilTableExists = false;
			else
				$affilTableExists = true;
		} catch (\Exception $sqle) {
			$affilTableExists = false;
		}


		if (empty($orderRows) || count($orderRows) == 0)
		{
			$this->api_return_error(204, "No orders were found");
			return true;
		}
  //TODO create 1 update from entity_ids and change query to be entity_id in
		for ($i = 0; $i < count($orderRows); $i++)
		{
			$this->db->update(
				$this->db_resource->getTableName('sales_order'),
				array('ext_order_id'=>$job_id, 'mpx_status'=>$this->PROCESS_STATUS),
				" `entity_id`=".$orderRows[$i]['entity_id']
			);
		}


		$JsonBuild = array();

		$JsonBuild["form_id"] = "1";
		$JsonBuild["Company"] = $this->company;
		$JsonBuild["JobType"] = $this->jobtype;

		$JsonBuild["total_rows"] =  count($orderRows);
		$PROCESSED_ROWS = 0;


		$Mem_rows = array();
		$order_id_list = array();

		for ($i = 0; $i < count($orderRows); $i++)
		{
			$OrderNumber = $orderRows[$i]["entity_id"]; //increment_id
			try
			{

				$order = $orderRows[$i];


				$OrderRow = array();

				$OrderRow["SourceTransactionId"] = $order["increment_id"]; //intval($OrderNumber);
				if(empty($order['customer_id']))
					$OrderRow["SourceAccountNumber"] = 0;
				else
					$OrderRow["SourceAccountNumber"] = $order['customer_id'];



				//check for affiliates
				if($affilTableExists) {
					$affilresults = $this->db->fetchRow(sprintf("SELECT `account_name` FROM `affiliateplus_transaction` WHERE `order_number`=%d;", $order["increment_id"]) );
					if($affilresults && count($affilresults) > 0 && !empty($affilresults['account_name'])) {
						$tmp = preg_split('/\s+/', $affilresults['account_name'], 2);
						$OrderRow["MotivationCode"] = $tmp[0];
					}
				}

				//OrderRow.Add("MotivationCode", "");
				//$OrderRow["MinistryEffort"] = $this->ministry;



				//decimal payments, refunds, recvd;
				//payments = refunds = 0;
				//for (int j = 0; j < order.Payments.Count; j++)
				//{
				//    payments += order.Payments[j].AmountCharged;
				//    refunds += order.Payments[j].AmountRefunded;
				//}
				//recvd = payments - refunds;

				//'base_total_paid' remains null, base_total_invoiced column has the paid amount
				if(is_null($order['base_total_invoiced']))
					$OrderRow["ReceivedAmount"] = floatval("0.00");
				else
					$OrderRow["ReceivedAmount"] = round($order['base_total_invoiced'], 2);


				/*This doesn't work for cart*/
				$OrderRow["OrderTotalAmount"] = round($order["base_subtotal"], 2);
				$OrderRow["GiftAmount"] = floatval("0.00");
				$OrderRow["OrderAmount"] = round($order["base_subtotal"], 2);


				$OrderRow["ShippingAmount"] = round($order['base_shipping_amount'], 2);

				$OrderRow["PrimaryTaxAmount"] = round($order['base_tax_amount'], 2);
				$OrderRow["SecondaryTaxAmount"] = 0.00;

				//make discount positive, add after accounting for row level discounts
				$order['base_discount_amount'] = abs($order['base_discount_amount']);
				//$OrderRow["OrderDiscountAmount"] = round($order['base_discount_amount'], 2);

				//OrderRow.Add("SourceShipMethod", order.ShippingMethodDisplayName);
				$shipmethod = $order['shipping_method'];
				if(empty($shipmethod)){
					$shipmethod = "None";
				}
				else if (strpos($shipmethod,"(") > 3)
				{
				    $shipmethod = trim( substr($shipmethod, 0, strpos($shipmethod,"(")) );
				}
				else if ($shipmethod == "No Shipping Required")
				{
				    $shipmethod = "None";
				}
				$OrderRow["SourceShipMethod"] = $shipmethod;
				$OrderRow["ShippingPrimaryTaxAmount"] = round($order['base_shipping_tax_amount'], 2);
				$OrderRow["ShippingSecondaryTaxAmount"] = 0.0;
				if(!is_null($order['customer_note']))
					$OrderRow["OrderComment"] = self::utf8_safe_encode( $order['customer_note'] );
				else
					$OrderRow["OrderComment"] = '';
				//$OrderRow["MediaOutletCode"] = "";
				//$OrderRow["MediaCode"] = "";
				//$OrderRow["MediaProgram"] = "";

				
				
				if(!empty($order['payment'])){
					$payment = json_decode($order['payment'], true);
				} else {
					$sql = sprintf("SELECT * FROM `%s` WHERE `parent_id`=%d", $this->db_resource->getTableName('sales_order_payment'), $OrderNumber);
					$tmp = $this->db->fetchAssoc($sql);

					//$this->_logger->notice($OrderNumber);
					//$this->_logger->notice(print_r($tmp, true));
					foreach ($tmp as $idontcare => $payment) break;
					//$payment = $tmp[$key];
				}
				if(!empty($order['order_grid'])) {
					$order_grid = json_decode($order['order_grid'], true);
				} else {
					$sql = sprintf("SELECT * FROM `%s` WHERE `entity_id`=%d", $this->db_resource->getTableName('sales_order_grid'), $OrderNumber);
					$tmp = $this->db->fetchAssoc($sql);
					foreach ($tmp as $idontcare => $order_grid) break;
					//$order_grid = $tmp[$key];
				}

				if(!empty($payment)) {
					if(is_null($payment['method']))
						$OrderRow["SourcePaymentType"] = "NONE";
					else if($payment['method'] == 'backoffice')
						$OrderRow["SourcePaymentType"] = 'Paperless';
					else
						$OrderRow["SourcePaymentType"] = $payment['method'];

					//'base_amount_paid' remains null, base_amount_authorized contains the transacted amount
					if(is_null($payment['base_amount_authorized'])) {
						if(!empty($payment['base_amount_paid']))
							$OrderRow["PaymentAmount"] = round($payment['base_amount_paid'], 2);
						else
							$OrderRow["PaymentAmount"] = floatval("0.00");
					}
					else
						$OrderRow["PaymentAmount"] = round($payment['base_amount_authorized'], 2);

					//if(is_null($payment['cc_last_4']))
					$OrderRow["CreditCardNumber"] = "";
					//else
					//  $OrderRow["CreditCardNumber"] = $payment['cc_last_4'];
					if(!empty($payment['ExpirationDate']))
						$OrderRow['ExpirationDate'] = $payment['ExpirationDate'];
					else
					if(is_null($payment['cc_exp_year']) || $payment['cc_exp_year'] == 0 || is_null($payment['cc_exp_month']) || $payment['cc_exp_month'] == 0)
						$OrderRow['ExpirationDate'] = "";
					else
						$OrderRow['ExpirationDate'] = sprintf("%02d", $payment['cc_exp_month']).substr($payment['cc_exp_year'], -2);



					if(is_null($payment['cc_last_4']))
						$OrderRow["CreditCardLastFour"] = "";
					else
						$OrderRow["CreditCardLastFour"] = $payment['cc_last_4'];

					//if ("Credit Card" == order.Payments[0].PaymentMethodName)
					$OrderRow["PaymentProcessor"] = "Magento";
					//else
					//    OrderRow.Add("PaymentProcessor", order.Payments[0].PaymentMethodName);

					if(!is_null($payment['cc_trans_id'])) {
						$OrderRow['TransactionId'] = $payment['cc_trans_id'];
					} else if(!is_null($payment['last_trans_id'])) {
						$OrderRow['TransactionId'] = $payment['last_trans_id'];
					} else {
						$OrderRow['TransactionId'] = '';
					}

					if($payment['method'] == 'paypal_express' && empty($payment['cc_approval'])) {
						//special paypal method handling

						//no updated/cached auth code in the payment record,
						//first decode from the extra payment data;
						//$paypal_record = unserialize($payment['additional_information']);
						
						//from db record is encoded data, from cache it is already decoded
						if(is_string($payment['additional_information']))
							$paypal_record = json_decode($payment['additional_information'], true);
						else if(is_array($payment['additional_information']))
							$paypal_record = $payment['additional_information'];
						else
							$paypal_record = false;
						
						if(empty($paypal_record) || !is_array($paypal_record) || empty($paypal_record['paypal_correlation_id'])) {
							//mail('peter.postma@odb.org', 'paypal record error', "Payment results were: " . $payment['additional_information']);
							$OrderRow['AuthorizationCode'] = '';
						} else {
							$OrderRow['AuthorizationCode'] = $paypal_record['paypal_correlation_id'];
							//that finishes retrieving the record

							//but if we can store that value for the Magento admin interface it would be better
							if(!empty($paypal_record['paypal_correlation_id'])) {

								if(!empty($payment['entity_id']))
								$this->db->update(
									$this->db_resource->getTableName('sales_order_payment'),
									array('cc_approval'=>$paypal_record['paypal_correlation_id']),
									" `entity_id` = ".$payment['entity_id']
								);

							}
						}
					}
					else if(!is_null($payment['cc_approval'])) {
						$OrderRow['AuthorizationCode'] = $payment['cc_approval'];
					} else {
						$OrderRow['AuthorizationCode'] = '';
					}

					$OrderRow["CardholderName"] = $order_grid['billing_name'];

					if(!empty($payment['cc_status_description']))
						$OrderRow['cardprofile'] = $payment['cc_status_description'];





				}
				else
				{
					$OrderRow['notice'] = "No Payment Information logged.";
					$OrderRow['CreditCardLastFour'] = "    ";
					$OrderRow['PaymentProcessor'] = "";

					$OrderRow['TransactionId'] = $OrderNumber;
					$OrderRow['AuthorizationCode'] = "";
					$OrderRow['CardholderName'] = "";
				}



				$Addresses = array();
				$mainemail = array();
				$mainphone = array();

				if(!empty($order['addresses'])){
					$addr = json_decode($order['addresses'], true);
				} else {
					$sql = sprintf("SELECT address.*, region.code FROM `%s` address LEFT JOIN `%s` region ON address.region_id=region.region_id WHERE `entity_id` IN (%d, %d)",
						$this->db_resource->getTableName('sales_order_address'),
						$this->db_resource->getTableName('directory_country_region'),
						$order['billing_address_id'],
						$order['shipping_address_id']
					);
					$addr_row = $this->db->query($sql);
					$addr = array();
					while ($row = $addr_row->fetch())
						$addr[] = $row;
				}
				foreach ($addr as $row) {
					$addrline = array();

					if($row['address_type'] == 'shipping')
						$type = "Ship";
					else if($row['address_type'] == 'billing')
						$type = "Bill";
					else
						$type = "Other";

					$addrline['AddressTypeCode'] = $type;


					if (!(is_null($row['company']) || empty($row['company'])) || !is_null(@$row['tax_id']))
						$addrline["OrgFlag"] = 'O';
					else
						$addrline["OrgFlag"] = 'I';

					$addrline["FirstName"] = trim($row['firstname'] . ' ' . $row['middlename']);
					$addrline["LastName"] = trim($row['lastname']);
					if(is_array($row['street'])){
						for($z=0; $z<count($row['street']); $z++) {
							$addrline["Address" . ($z+1) ] = $row['street'][$z];
						}
					} else
					if(!strpos($row['street'], "\n"))
						$addrline["Address1"] = trim($row['street']);
					else {
						$adlines = preg_split('/[\r\n]+/s', trim($row['street']));
						for($z=0; $z<count($adlines); $z++)
							$addrline["Address" . ($z+1) ] = $adlines[$z];
					}
					//$addrline["Address2"] = $row.ShippingAddress.Line2);
					//$addrline["Address3"] = $row.ShippingAddress.Line3);
					$addrline["City"] = trim($row['city']);
					$addrline["State"] = $row['code'];
					$addrline["PostalCode"] = trim($row['postcode']);
					$country = $row['country_id'];
					if($country == 'US')
						$addrline["CountryCode"] = 'USA';
					else if($country == 'CA')
						$addrline["CountryCode"] = 'Canada';
					else
						$addrline["CountryCode"] = $country;

					$tmp = strtolower($addrline["LastName"]);
					//special condition!!
					if(   $addrline["State"] == "CT"
						&& strtolower($addrline["City"]) == "bridgeport"
						&& strpos(strtolower($addrline["Address1"]), "brooks st")
						&&($tmp =="berrios" || $tmp == "berios")
					)
					{
						if (!empty($OrderRow['notice']))
						{
							$OrderRow['notice'] = $OrderRow['notice'] . ', -AND- address is known to be used for fraudulent orders.';
						}
						else
							$OrderRow['notice'] = 'Address is known to be used for fraudulent orders!';
					}

					//convert NULL to empty string
					foreach($addrline as $k=>$v)
						if(is_null($v))
							$addrline[$k] = '';

					$Addresses[] = $addrline;


					//email
					if(!(is_null($row['email']) || empty($row['email']))) {
						$email = array();
						$email["SourceEmailType"] = $type;
						$email["SourceEmailAddress"] = $row['email'];
						$mainemail[] = $email;
					}


					//phone
					if (!(is_null($row['telephone']) || empty($row['telephone'])))
					{
						$phone = array();
						$phone['SourcePhoneType'] = $type;
						$phone['SourcePhoneNumber'] = $row['telephone'];
						$mainphone[] = $phone;
					}

				}
				//duplication elimination
				//address
				if ( count($Addresses) > 1
					&& (
						$Addresses[0]["FirstName"] == $Addresses[1]["FirstName"]
						&& $Addresses[0]["LastName"] == $Addresses[1]["LastName"]
						// May be needed for when MOSS comes over
            //&& $Addresses[0]["OrganizationName"] == $Addresses[1]["OrganizationName"]
						&& $Addresses[0]["Address1"] == $Addresses[1]["Address1"]
						//&& order.ShippingAddress.Line2.Trim() == order.BillingAddress.Line2.Trim()
						//&& order.ShippingAddress.Line3.Trim() == order.BillingAddress.Line3.Trim()
						&& $Addresses[0]["City"] == $Addresses[1]["City"]
						&& $Addresses[0]["State"] == $Addresses[1]["State"]
						&& $Addresses[0]["PostalCode"] == $Addresses[1]["PostalCode"]
						&& $Addresses[0]["CountryCode"] == $Addresses[1]["CountryCode"]
					)
				)
				{
					unset($Addresses[1]);
					$Addresses[0]['AddressTypeCode'] = "Bill";
				}

				//email
				if ( count($mainemail) > 1
					&& ($mainemail[0]["SourceEmailAddress"] == $mainemail[1]["SourceEmailAddress"])
				)
				{
					unset($mainemail[1]);
					$mainemail[0]["SourceEmailType"] = "Bill";
				}

				// phone
				if ( count($mainphone) > 1
					&& ($mainphone[0]["SourcePhoneNumber"] == $mainphone[1]["SourcePhoneNumber"])
				)
				{
					unset($mainphone[1]);
					$mainphone[0]["SourcePhoneType"] = "Bill";
				}


				$OrderRow['JobDetailAddresses'] = $Addresses;

				if (count($mainemail) > 0)
					$OrderRow['JobDetailEmails'] = $mainemail;   //JobDetailEmails:[]

				if (count($mainphone) > 0)
					$OrderRow['JobDetailPhoneNumbers'] =  $mainphone;



				if(!empty($order['items'])){
					$items = json_decode($order['items'], true);
				} else {
					$sql = sprintf("SELECT * FROM `%s` WHERE `order_id`=%d", $this->db_resource->getTableName('sales_order_item'), $OrderNumber);
					$items = $this->db->fetchAll($sql);
				}

				$shiptax = 0.0;
				$TotalTax = $order['base_tax_amount'];
				$TaxSum = 0.0;


				$OrderRow["ShippingPrimaryTaxAmount"] = $order['base_shipping_tax_amount'];


				if(!(is_null($order['coupon_code']) || empty($order['coupon_code']))) {
					$coupons = preg_split('/[,;]\s*/', $order['coupon_code']);
					$CouponCodes =  array();

					foreach ($coupons as $coupon)
					{
						$couponline = array();
						$couponline["SourceCouponCode"] = $coupon;
						$CouponCodes[] = $couponline;
					}

					$OrderRow["JobDetailCouponCode"] = $CouponCodes;

				}




				//rehash by ID as index
				$products = array();
				$item_hash = array();
				foreach($items as $lineitem) {
					if(!empty($lineitem['product_id']))
						$products[$lineitem['product_id']] = $lineitem;
					if(!empty($lineitem['item_id']))
						$products[$lineitem['item_id']] = $lineitem;
				}

				//iterate through, evaluate items and determine if they need to be discarded as duplicate or child
				$itemtracking = [];
				foreach($items as $index => $lineitem) {
					if(!empty($lineitem['parent_item_id']) ) {
						//we have a child based variation?

						//do we have the parent?
						if(!empty($products[$lineitem['parent_item_id']])) {

							if (!empty($lineitem['parent_item_id']) && !empty($item_hash[$lineitem['parent_item_id']]) && $lineitem['sku'] === $item_hash[$lineitem['parent_item_id']]['sku']) {
								unset($items[$index]);
								continue;     //this element contains no relevant additional information
							}

							if (empty($lineitem['base_original_price'])) {
								unset($items[$index]);
								continue;   //this child is not fully configured to be inlcuded.
							}
						}
					}

					//product customization, results in a duplicate, less informational product. drop it.
					if(!empty($lineitem['item_id'])) {
						$itemid = $lineitem['item_id'];
						$itemtracking[$itemid] = $itemid;

						$parentid = $lineitem['parent_item_id'];
						if(!empty($itemtracking[$parentid])){

							$parent = $itemtracking[$parentid];

							//has_options in parent, not child, required options parent, not child, sku's are not the same (in product),
							//parent has parent_item_id=null, child does not, product_id !=, parent product_type=configurable child is simple,
							//sku == and product_id !=, name != but similar, qty_ordered ==,
							//this may be over specific, but we want to make sure that there are no consequential errors.
							if(
								$lineitem['sku'] === $parent['sku']
								&& $lineitem['name'] != $parent['name']
								&& $lineitem['qty_ordered'] === $parent['qty_ordered']
								&& $parent['product_type'] === 'configurable' && $lineitem['product_type'] == 'simple'
							) {
								//remove unnecessary child product
								unset($items[$index]);
							}
						}


					}
				}


				$OrderLines = array();


				foreach ($items as $lineitem)
				{
					$original = false;
					$motivation = $this->motivation;
					$productisrecurring = false;
					$recurMotivationCode = false;
					$gift_amount = false;

					$quant = intval($lineitem['qty_ordered']);
					if ($quant <= 0)
						continue;  //BV Commerce throwback... they could order 0 of something
					$li = array();

					$li["ProductCode"] = $lineitem['sku'];


					$li["Quantity"] = $quant;
					$li["Price"] = round($lineitem['price'], 2);
					$original_price = round($lineitem['base_original_price'], 2);
					//Mage ::log("Base_Original price: ".$li["Price"]);

					$li["PrimaryTaxAmount"] = round($lineitem['base_tax_amount'], 2);
					$li["SecondaryTaxAmount"] = 0.0;
					$li["SourcePriceCode"] = "Internet";



					//all discounts remain positive!
					if($lineitem['base_discount_amount'] != 0)
						$lineitem['base_discount_amount'] = abs($lineitem['base_discount_amount']);
					//$original = $this->object_manager->create('\Magento\Catalog\Model\Product')->setStoreId($this->store)->load($lineitem['product_id']);


					//look up attributes of the product, we need ProductOfferType. try it a few different ways.
					if(!empty($lineitem['attr'])) {
						$attr = $lineitem['attr'];
					} else {
						$original = $this->productModel->load($lineitem['product_id']);
						if ($original->getResource()->getAttribute('productoffertype'))
							$attr = $original->getAttributeText('productoffertype');
						else
							$attr = false;
					}


					//is product recurring???
					if(!empty($lineitem['recurring'])){
						//cached item record
						$productisrecurring = true;

						if(!empty($lineitem['recurmotivation']))
							$recurMotivationCode = $lineitem['recurmotivation'];
					} else if(!empty($product_options['info_buyRequest'])){
						//database item record
						$product_options = $lineitem['product_options'];
						if(is_string($product_options))
							$product_options = json_decode($product_options, true);

						if(!empty($product_options['info_buyRequest']['_recurring']) && $product_options['info_buyRequest']['_recurring'] !== false)
							$productisrecurring = true;

						if(!empty($product_options['info_buyRequest']['_recurmotivation']))
							$recurMotivationCode = $product_options['info_buyRequest']['_recurmotivation'];
					}




					// check for various donation methods
					//do the price/original price not match?
					if(isset($lineitem['price']) && !empty($lineitem['original_price']) && $lineitem['price'] != $lineitem['original_price']) {

						// was this product already pre-determined to be a GOAA or NCOO?
						if($attr == 'GOAA' || $attr == 'NCOO') {

							$li["DiscountAmount"] = '0.00';
							$gift_amount = $li['Price'];
						}
						else {
							// check with original price to see if this was a sale, or
							$diff = round($original_price - $lineitem['price'], 2);

							if($diff > 0 ) {
								//if this is a discount, then assign the original price and indicate discount amount
								$li["DiscountAmount"] = round($diff, 2);
								$li["Price"] = round($lineitem['original_price'], 2);
							}
							else
								//anything above the original amount is a donation
								$gift_amount = abs($diff);
						}

					} else

						//ok... so there is "no discount" on this line item, but the prices isn't the same as in the inventory...
						//so, is there a discount that is not being recorded on the record?  (look for hidden discounts)
						if($lineitem['base_discount_amount'] == 0) {
							//if 0, check for a hidden discount just in case.


							//if the product is marked NCOO or GOAA do not process for discounts
							if($attr == 'GOAA' || $attr == 'NCOO') {
								$li["Price"] = round($lineitem['price'], 2);
								$li["DiscountAmount"] = '0.00';
							}
							else {
								if(empty($original))
									$original = $this->productModel->load($lineitem['product_id']);
								$price = $original->getPrice();

								//Mage ::log("Ordering $quant of {$lineitem['sku']} with a discount");
								//Mage ::log("Original price: ".$price);

								$originalTotal = $price * $quant;
								//Mage ::log("Original total: ".$originalTotal);

								$statedTotal = $li["Price"] * $quant;
								//Mage ::log("stated total: ".$statedTotal);

								if ($originalTotal > ($statedTotal + .01)) {
									$li["Price"] = round($price, 2);
									//Mage ::log("price: ".$li["Price"]);

									$lineitem['base_discount_amount'] = $originalTotal - $statedTotal;
									//Mage ::log("discount: ".$lineitem['base_discount_amount']);

								}
							}
						} else if($lineitem['base_discount_amount'] > 0.00499 && $lineitem['base_original_price'] != $lineitem['original_price']) { //must be able to round up to penny

							//if the line item was marked as discounted, Magento added the line item discount to the order discount
							//we don't want that, remove the line item discount from the order level.

							if($order['base_discount_amount'] >= $lineitem['base_discount_amount'])
								$order['base_discount_amount'] -= $lineitem['base_discount_amount'];
						}

					if(empty($li["DiscountAmount"]))
						$li["DiscountAmount"] = round($lineitem['base_discount_amount'], 2);

					//negative discounts are probably donations or goaa. move to GiftAmount
					//if($li['DiscountAmount'] < 0.00) {
					//    //$OrderRow["GiftAmount"] = $OrderRow["GiftAmount"] - $li['DiscountAmount'];
					//    $order['base_discount_amount'] + $li['DiscountAmount'];  //move to order discount and make negative.
					//    $li['DiscountAmount'] = 0.00;
					//}

					//check to see if product was already loaded in object form from DB
					//if(empty($original)) {
					//	$original = Mage ::getModel('catalog/product')->setStoreId($this->store)->load();



					//fetch set product type if available
					if(empty($attr)) {
						if(empty($lineitem['attr'])) {
							if(empty($original))
								$original = $this->productModel->load($lineitem['product_id']);
							if ($original->getResource()->getAttribute('productoffertype'))
								$attr = $original->getAttributeText('productoffertype');
							else $attr = false;
						} else 
							$attr = $lineitem['attr'];
					}

					if(!empty($attr))
						$li['SourceProductType'] = ucfirst(strtolower($attr)); //camelcase
					else
						$li['SourceProductType'] = 'Sale';


					//are these donations? let me count the ways...
					if($li['SourceProductType'] === 'Donation'){
						//this is purely a donation, the item amount is $0 and the products motivation over rides the order motivation
						$motivation = $lineitem['sku'];

						/*This doesn't work for cart*/
						//move this amount from Order Total and total amount to Gift Amount
						$amount = $li["Price"];
						$li["Price"] = '0.00';
						if($li['Quantity'] > 1)
							$amount *= $li['Quantity'];
						$OrderRow["OrderTotalAmount"] = round($OrderRow["OrderTotalAmount"] - $amount, 2);
						$OrderRow["OrderAmount"] = round($OrderRow["OrderAmount"] - $amount, 2);
						$OrderRow["GiftAmount"] = round($OrderRow["GiftAmount"] + $amount, 2);
					}

					else if($gift_amount) {
						//a gift from the ODB Store.
						//the gift amount is above and beyond the price, price may not be $0
						//remove from product price and apply to order level gift.

						$li["Price"] = round($li["Price"] - $gift_amount, 2);

						if($lineitem['qty_ordered'] > 1)
							$gift_amount *= $li["Quantity"];

						$OrderRow["OrderTotalAmount"] = round($OrderRow["OrderTotalAmount"] - $gift_amount, 2);
						$OrderRow["GiftAmount"] = round($OrderRow["GiftAmount"] + $gift_amount, 2);
						$OrderRow["OrderAmount"] = round($OrderRow["OrderAmount"] - $gift_amount, 2);
					}


					//back to payment data:
					if(!empty($payment)) {


						if(empty($recurMotivationCode))
							$recurMotivationCode = 'INR1';  //default motivation code

						if(empty($payment['cc_type']))
							$card_type = ''; //don't let no-found exception take us down
						else {
							switch ($payment['cc_type']){
								case 'MC': $card_type = 'CARD_MASTER';
								break;
								case 'VI': $card_type = 'CARD_VISA';
								break;
								case 'AE': $card_type = 'CARD_AMEX';
								break;
								case 'DI': $card_type = 'CARD_DISCOVER';
								break;
								default:
									$card_type = 'CARD_UNKNOWN';
							}
						}

						if ($productisrecurring) {
							$OrderRow['JobDetailRecurringGifts'] = [
								'MotivationCode' => $recurMotivationCode,
								'SourcePaymentType' => $card_type,
								'GiftAmount' => $OrderRow["GiftAmount"],
								'ProfileNumber' => $OrderRow['cardprofile'],
								'CreditCardLastFour' => $OrderRow["CreditCardLastFour"],
								'ExpirationDate' => $OrderRow['ExpirationDate'],
								'CardholderName' => $OrderRow["CardholderName"],
								'RecurrenceType' => 'monthly'  //presently fixed at monthly
							];
						}

						$productisrecurring = false;  //wipe it for next iteration
					}


					$OrderLines[] = $li;
				}
				$OrderRow["JobDetailOrderLines"] = $OrderLines;


				//order level discount is the remainder of discount
				$OrderRow["OrderDiscountAmount"] = round($order['base_discount_amount'], 2);

				if($OrderRow["OrderDiscountAmount"] > 0.00) {
					$OrderRow["OrderAmount"] -= $OrderRow["OrderDiscountAmount"];
				}


				$OrderRow["MotivationCode"] = $motivation;


				$Mem_rows[] = $OrderRow;
				$PROCESSED_ROWS++;

				$order_id_list[] = $OrderNumber;
			}
			catch (\Exception $e)
			{

				$sql = sprintf("UPDATE `%s` SET `ext_order_id`=-1 WHERE `entity_id` = %d;", $this->db_resource->getTableName('sales_order'), $OrderNumber);
				try{
					$this->db->query($sql);
				} catch(\Exception $oops) {
					$this->_logger->error("MPX mysql-exceptions on exceptions!");
				}

				//maybe I should log this too
				$this->_logger->error("MPX Order Exception");
				$this->_logger->error($e->getMessage());
				$this->_logger->error($e->getFile()." ".$e->getLine());
				//note how far the process got and any details we have captured
				$this->_logger->error( json_encode(@$OrderRow));
				

				$OrderRow = array();

				$OrderRow['SourceTransactionId'] = $OrderNumber;
				$OrderRow['exception'] = "Record Failed on exception: " . $e->getMessage();


				// empty/0 fill the required fields
				$OrderRow["SourceAccountNumber"] = "";
				$OrderRow["MotivationCode"] = "";
				$OrderRow["ReceivedAmount"] = "0.00";
				$OrderRow["GiftAmount"] = "0.00";
				$OrderRow["OrderAmount"] = "0.00";
				$OrderRow["ShippingAmount"] = "0.00";
				$OrderRow["PrimaryTaxAmount"] = "0.00";
				$OrderRow["SecondaryTaxAmount"] = "0.00";
				$OrderRow["OrderDiscountAmount"] = "0.00";
				$OrderRow["OrderTotalAmount"] = "0.00";
				//OrderRow.Add("SourceShipMethod", "");
				$OrderRow["ShippingPrimaryTaxAmount"] = "0.00";
				$OrderRow["ShippingSecondaryTaxAmount"] = "0.00";

				$OrderRow["OrderComment"] = "";
				//$OrderRow["MediaOutletCode"] = "";
				//$OrderRow["MediaProgram"] = "";


				//and yet, its not in the json file! :(
				$Mem_rows[] = $OrderRow;
			}
		}



		$JsonBuild["rows"] = $Mem_rows;
		$JsonBuild["processed_rows"] = $PROCESSED_ROWS;
		$JsonBuild["row_count"] = count($Mem_rows);


		$json = json_encode($JsonBuild);

		//do not cache minor pulls. We may have some stragglers.
		if($JsonBuild["row_count"] > 20)
			@file_put_contents($file_cache, $json);
		
		if ($PROCESSED_ROWS == count($orderRows))
			$status = 200;
		else
			$status = 206;
		//HttpContext.Current.Response.BufferOutput = true;
		//header('Content-type: text/x-json; charset=utf-8', true, $status);
		header('Content-type: text/plain; charset=utf-8', true, $status);
		header('Content-length: '.strlen($json));

		if(ob_get_level())
			ob_clean();
		echo $json;
		if(ob_get_level())
			ob_flush();

		ob_start();
		try{
			$order_set = '(' . implode(',', $order_id_list) . ')';
			$this->_logger->notice('updating final status');
			$this->_logger->notice($order_set);
			$this->db->update(
				$this->db_resource->getTableName('sales_order'),
				array('mpx_status'=>$this->END_STATUS),
				" `entity_id` IN ".$order_set
			);
			
		} catch(\Exception $e) {
			$this->_logger->error("Could not update processed orders' status");
		}
		ob_clean();

		return true;
	}

	function api_run_checkback()
	{
		$startdate = (!empty($_GET["start_date"]))?$_GET["start_date"]:'';
		$start_date = '';

		if(!empty($_GET['short']))
			$short = true;
		else
			$short = false;

		if (empty($startdate) || ! $start_date = strtotime($startdate))
		{
			$this->api_return_error(471, "Start Date did not parse to time ");
			return false;
		}


		if (!$this->db) {
			$this->api_return_error(506, "Database failure in export");
			return false;
		}

		// `created_at` = time order started; `updated_at` = time order paid/finished
		$hourshift = intval($this->timeoffset);
		$sql  = sprintf("SELECT DATE(`created_at` - INTERVAL %d HOUR) as order_date, COUNT(`entity_id`) as order_count FROM `%s` orders WHERE store_id=%d AND mpx_status='%s' AND created_at<'%s' AND ext_order_id IS NULL GROUP BY order_date ;",
			$hourshift,
			$this->db_resource->getTableName('sales_order'),
			$this->store,
			$this->START_STATUS,
			date('Y-m-d ', $start_date).$this->timeoffset
		);
		try
		{
			$set = $this->db->fetchAll($sql);
		}
		catch (\Exception $sqle)
		{
			$this->api_return_error(503, "Database error filtering rows");
			$this->_logger->notice($sql);
			$this->_logger->notice($sqle->getMessage());
			//$this->_logger->notice($sqle);
			return false;
		}

		if (empty($set) || count($set) == 0)
		{
			$this->api_return_error(204, "");
			return true;
		}

		ob_start();

		if(!$short)
			echo "There were ";
		foreach ($set as $orderRows)
		{
			if(!$short)
				echo $orderRows["order_count"], " orders unprocessed on day ", $orderRows["order_date"], ", ";
			else
				echo $orderRows["order_date"], ',';
		}

		$message = ob_get_clean();

		if(!$short)
			$message = substr($message, 0, -2);
		else
			$message = substr($message, 0, -1);

		$len = strlen($message);

		header('Content-type: text/plain; charset=utf-8', true, 200);
		header('Content-length: '.$len);

		echo $message;

		return true;
	}

	public static function utf8_safe_encode($instr) {
		if(mb_check_encoding($instr,'UTF-8'))return $instr; // no need for the rest if it's all valid UTF-8 already
		//global $NIBBLE_GOOD_CHARS,$ENeCODE_BYTE_MAP;
		if( empty(self::$ENCODE_BYTE_MAP)) {
			self::$ENCODE_BYTE_MAP=array();    //byte-map-array, empty
			$cp1252_map=array(         //windows-1252 char re-map information
				"\x80"=>"\xE2\x82\xAC",    // EURO SIGN
				"\x82" => "\xE2\x80\x9A",  // SINGLE LOW-9 QUOTATION MARK
				"\x83" => "\xC6\x92",      // LATIN SMALL LETTER F WITH HOOK
				"\x84" => "\xE2\x80\x9E",  // DOUBLE LOW-9 QUOTATION MARK
				"\x85" => "\xE2\x80\xA6",  // HORIZONTAL ELLIPSIS
				"\x86" => "\xE2\x80\xA0",  // DAGGER
				"\x87" => "\xE2\x80\xA1",  // DOUBLE DAGGER
				"\x88" => "\xCB\x86",      // MODIFIER LETTER CIRCUMFLEX ACCENT
				"\x89" => "\xE2\x80\xB0",  // PER MILLE SIGN
				"\x8A" => "\xC5\xA0",      // LATIN CAPITAL LETTER S WITH CARON
				"\x8B" => "\xE2\x80\xB9",  // SINGLE LEFT-POINTING ANGLE QUOTATION MARK
				"\x8C" => "\xC5\x92",      // LATIN CAPITAL LIGATURE OE
				"\x8E" => "\xC5\xBD",      // LATIN CAPITAL LETTER Z WITH CARON
				"\x91" => "\xE2\x80\x98",  // LEFT SINGLE QUOTATION MARK
				"\x92" => "\xE2\x80\x99",  // RIGHT SINGLE QUOTATION MARK
				"\x93" => "\xE2\x80\x9C",  // LEFT DOUBLE QUOTATION MARK
				"\x94" => "\xE2\x80\x9D",  // RIGHT DOUBLE QUOTATION MARK
				"\x95" => "\xE2\x80\xA2",  // BULLET
				"\x96" => "\xE2\x80\x93",  // EN DASH
				"\x97" => "\xE2\x80\x94",  // EM DASH
				"\x98" => "\xCB\x9C",      // SMALL TILDE
				"\x99" => "\xE2\x84\xA2",  // TRADE MARK SIGN
				"\x9A" => "\xC5\xA1",      // LATIN SMALL LETTER S WITH CARON
				"\x9B" => "\xE2\x80\xBA",  // SINGLE RIGHT-POINTING ANGLE QUOTATION MARK
				"\x9C" => "\xC5\x93",      // LATIN SMALL LIGATURE OE
				"\x9E" => "\xC5\xBE",      // LATIN SMALL LETTER Z WITH CARON
				"\x9F" => "\xC5\xB8"       // LATIN CAPITAL LETTER Y WITH DIAERESIS
			);
			//build the encode_byte_map array keeping the index in incrementing order
			for($x=128;$x<256;++$x){
				$ch = chr($x);   //get the character
				if(isset($cp1252_map[$ch]))  //look up if its Windows-1252
					self::$ENCODE_BYTE_MAP[$ch] = $cp1252_map[$ch];
				else
					self::$ENCODE_BYTE_MAP[$ch]=utf8_encode($ch);  //simple conversion
			}
		}
		if(empty(self::$NIBBLE_GOOD_CHARS)) {
			$ascii_char='[\x00-\x7F]';
			$cont_byte='[\x80-\xBF]';
			$utf8_2='[\xC0-\xDF]'.$cont_byte;
			$utf8_3='[\xE0-\xEF]'.$cont_byte.'{2}';
			$utf8_4='[\xF0-\xF7]'.$cont_byte.'{3}';
			$utf8_5='[\xF8-\xFB]'.$cont_byte.'{4}';
			//set it took look for any good character combinations repeatedly
			self::$NIBBLE_GOOD_CHARS = "@^(($ascii_char|$utf8_2|$utf8_3|$utf8_4|$utf8_5)+)(.*)$@s";
		}

		ob_start();
		$char='';
		$rest='';
		while((strlen($instr))>0){
			//match all good characters
			if(1==preg_match(self::$NIBBLE_GOOD_CHARS,$instr,$match)){
				$char=$match[1];
				$instr=$match[3];

				echo $char;
				unset($match);
			}
			//we can continue with the operations if the string is not empty
			if(!empty($instr)) {
				//'byting' the string is 30% more efficient than preg('/(.)(.*)/s'
				$char=$instr[0];
				if(strlen($instr)>1)
					$instr=substr($instr,1);  //set to remainder of string
				else
					$instr='';  //set to empty if its done and let the loop terminate

				echo self::$ENCODE_BYTE_MAP[$char];
			}

		}

		$outstr = ob_get_clean();
		return $outstr;
	}
}
