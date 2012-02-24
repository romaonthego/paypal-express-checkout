<?php
//  PaypalExpressCheckout.php
//  PaypalExpressCheckout
//
// Copyright 2011 Roman Efimov <romefimov@gmail.com>
//
// Licensed under the Apache License, Version 2.0 (the "License");
// you may not use this file except in compliance with the License.
// You may obtain a copy of the License at
//
//    http://www.apache.org/licenses/LICENSE-2.0
//
// Unless required by applicable law or agreed to in writing, software
// distributed under the License is distributed on an "AS IS" BASIS,
// WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
// See the License for the specific language governing permissions and
// limitations under the License.
//

require_once "common/PaypalBase.php";

class PaypalExpressCheckout extends PaypalBase {
    
    /**
     * Redirect user to PayPal to request payment permissions
     * 
     * If OK, the customer is redirected to PayPal gateway
     * If error, returns false
     * 
     * @param float $amount Amount (2 numbers after decimal point)
     * @param string $desc Item description
     * @param string $invoice (Optional) Your own invoice or tracking number.
     * @param string $currency 3-letter currency code (USD, GBP, CZK etc.)
     * @param array $resultData PayPal response
     * 
     * @return bool
     */
    public function doExpressCheckout($amount, $description, $invoice = '', $currency = 'USD', $shipping = false, &$resultData = array()){
        $data = array('PAYMENTACTION' =>'Sale',
                      'AMT' => $amount,
                      'DESC' => $description,
                      'ALLOWNOTE' => "0",
                      'CURRENCYCODE' => $currency,
                      'METHOD' => 'SetExpressCheckout');
                      
        if($shipping === false) {
        	$data['NOSHIPPING'] = "1";
				}
				else if($shipping === true) {
					// let the merchant decide the address
				}
				else {
					// address specified by the customer
					$data['ADDROVERRIDE'] = 1;
					$data['PAYMENTREQUEST_0_SHIPTONAME'] = $shipping['name'];
					$data['PAYMENTREQUEST_0_SHIPTOSTREET'] = $shipping['street_address_1'];
					$data['PAYMENTREQUEST_0_SHIPTOSTREET2'] = $shipping['street_address_2'];
					$data['PAYMENTREQUEST_0_SHIPTOCITY'] = $shipping['city'];
					$data['PAYMENTREQUEST_0_SHIPTOSTATE'] = $shipping['state_code'];
					$data['PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE'] = $shipping['country_code'];
					$data['PAYMENTREQUEST_0_SHIPTOZIP'] = $shipping['zip'];
					$data['PAYMENTREQUEST_0_SHIPTOPHONENUM'] = $shipping['phone_number'];
				}
				
        $data['CUSTOM'] = $amount.'|'.$currency.'|'.$invoice;
        if ($invoice) $data['INVNUM'] = $invoice;

        if (!$resultData = $this->runQueryWithParams($data)) return false;
        if ($resultData['ACK'] == 'FAILURE') return false;

        if ($resultData['ACK'] == 'SUCCESS') {
            header('Location: '.$this->gateway->getGate().'cmd=_express-checkout&useraction=commit&token='.$resultData['TOKEN']);
            exit();
        }
        return false;
    }
    
    /**
     * Gets checkout information from PayPal
     * 
     * @param string $token PayPal token
     * 
     * @return array $resultData PayPal response
     */
    public function getCheckoutDetails($token) {
        $data = array('TOKEN' => $token,
                      'METHOD' =>'GetExpressCheckoutDetails');
        
        if (!$resultData = $this->runQueryWithParams($data)) return false;
        return $resultData;
    }
    
    /**
     * Perform payment based on token and Payer ID
     * 
     * If OK, returns true
     * If error, returns false
     * 
     * @param string $token PayPal token returned with GET response
     * @param string $payerId PayPal Payer ID returned with GET response
     * @param array $resultData PayPal response
     * 
     * @return bool
     */
    public function doPayment($token, $payerId, $total_amount = null, &$resultData = array()) {
        $details = $this->getCheckoutDetails($token);
        if (!$details) return false;
        list($amount, $currency, $invoice) = explode('|', $details['CUSTOM']);
        $data = array('PAYMENTACTION' => 'Sale',
                      'PAYERID' => $payerId,
                      'TOKEN' => $token,
                      'AMT' => $total_amount ? $total_amount : $amount,
                      'CURRENCYCODE' => $currency,
                      'METHOD' =>'DoExpressCheckoutPayment');
        
        if (!$resultData = $this->runQueryWithParams($data)) return false;
        if ($resultData['ACK'] == 'SUCCESS') return $resultData;
        return false;
    }
	
	/**
     * Perform refund base on transaction ID
     * 
     * If OK, returns true
     * If error, returns false
     * 
     * @param string $transactionId Unique identifier of a transaction
     * @param string $invoice (Optional) Your own invoice or tracking number.
     * @param bool $isPartial Partial or Full refund
     * @param float $amount PayPal (Optional) Refund amount
     * @param string $currencyCode A three-character currency code. This field is required for partial refunds. Do not use this field for full refunds.
     * @param string $note (Optional) Custom memo about the refund.
     * @param array $resultData PayPal response
     * 
     * @return bool
     */
	public function doRefund($transactionId, $invoice = '', $isPartial = false,
							 $amount = 0, $currencyCode = 'USD', $note = '', &$resultData) {
		$data = array('METHOD' => 'RefundTransaction',
					  'TRANSACTIONID' => $transactionId,
					  'INVOICEID' => $invoice,
					  'REFUNDTYPE' => $isPartial ? 'Partial' : 'Full',
					  'NOTE' => $note);
		if ($isPartial) {
			$data['AMT'] = $amount;
			$data['CURRENCYCODE'] = $currencyCode;
		}
		
		if (!$resultData = $this->runQueryWithParams($data)) return false;
        if ($resultData['ACK'] == 'SUCCESS') return true;
		return false;
	}
    
}


?>