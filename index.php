<?php
require_once "lib/PaypalExpressCheckout.php";

$gateway = new PaypalGateway();
$gateway->apiUsername = "YOUR API USERNAME HERE";
$gateway->apiPassword = "YOUR API PASSWORD HERE";
$gateway->apiSignature = "YOUR API SIGNATURE HERE";
$gateway->testMode = true;

// Return (success) and cancel url setup
$gateway->returnUrl = "http://test.site/?action=success";
$gateway->cancelUrl = "http://test.site/?action=cancel";

$paypal = new PaypalExpressCheckout($gateway);

$shipping = false;

if (!isset($resultData)) {
    $resultData = Array();
}

if (isset($_GET['action'])) {
    $action = $_GET['action'];
}

switch ($_GET['action']) {
    case "": // Index page, here you should be redirected to Paypal
        $paypal->doExpressCheckout(123.45, 'Test service', 'inv123', 'USD', $shipping, $resultData);
        break;
    
    case "success": // Paypal says everything's fine, do the charge (user redirected to $gateway->returnUrl)
        if ($transaction = $paypal->doPayment($_GET['token'], $_GET['PayerID'], $resultData)) {
			echo "Success! Transaction ID: ".$transaction['TRANSACTIONID'];
		} else {
				echo "Debugging what went wrong: ";
				print_r($resultData);
			}
		break;

    case "refund":
        $transactionId = '9SU82364E9556505C';
        if ($paypal->doRefund($transactionId, 'inv123', false, 0, 'USD', '', $resultData))
            echo 'Refunded: '.$resultData['GROSSREFUNDAMT']; else {
                echo "Debugging what went wrong: ";
                print_r($resultData);
            }
        break;
    
    case "cancel": // User canceled and returned to your store (to $gateway->cancelUrl)
        echo "User canceled";
        break;
}

?>