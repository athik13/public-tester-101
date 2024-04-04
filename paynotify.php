<?php
include("../drop-files/lib/common.php");
include ("../drop-files/config/db.php");



if(isset($_GET['callback']) && $_GET['callback'] == "true"){

    switch(DEFAULT_PAYMENT_GATEWAY){

        case "voguepay":
        require "../drop-files/lib/pgateways/voguepay/voguepay-callback.php";
        break;
    
        case "paystack":
        require "../drop-files/lib/pgateways/paystack/paystack-callback.php";
        break;
    
        case "pesapal":
        require "../drop-files/lib/pgateways/pesapal/pesapal-callback.php";
        break;

        case "paytr":
        require "../drop-files/lib/pgateways/paytr/paytr-callback.php";
        break;

        case "stripe":
        require "../drop-files/lib/pgateways/stripe/stripe-callback.php";
        break;
    
    	case "flutterwave":
        require "../drop-files/lib/pgateways/flutterwave/flutterwave-callback.php";
        break;

        case "payku":
        require "../drop-files/lib/pgateways/payku/payku-callback.php";
        break;

        case "midtrans":
        require "../drop-files/lib/pgateways/midtrans/midtrans-callback.php";
        break;

        case "paymob":
        require "../drop-files/lib/pgateways/paymob/paymob-callback.php";
        break;
    
            
    }

    exit;
}

switch(DEFAULT_PAYMENT_GATEWAY){

    case "voguepay":
    require "../drop-files/lib/pgateways/voguepay/voguepay-gateway.php";
    break;

    case "paystack":
    require "../drop-files/lib/pgateways/paystack/paystack-gateway.php";
    break;

    case "pesapal":
    require "../drop-files/lib/pgateways/pesapal/pesapal-gateway.php";
    break;

    case "paytr":
    require "../drop-files/lib/pgateways/paytr/paytr-gateway.php";
    break;

    case "stripe":
    require "../drop-files/lib/pgateways/stripe/stripe-gateway.php";
    break;

	case "flutterwave":
    require "../drop-files/lib/pgateways/flutterwave/flutterwave-gateway.php";
    break;

    case "payku":
    require "../drop-files/lib/pgateways/payku/payku-gateway.php";
    break;

    case "midtrans":
    require "../drop-files/lib/pgateways/midtrans/midtrans-gateway.php";
    break;

    case "paymob":
    require "../drop-files/lib/pgateways/paymob/paymob-gateway.php";
    break;

    default:
    require "../drop-files/lib/pgateways/paystack/paystack-gateway.php";

}




?>

















