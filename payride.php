<?php
session_start();
include("../drop-files/lib/common.php");
include "../drop-files/config/db.php";
$GLOBALS['admin_template']['active_menu'] = "main-book";
$GLOBALS['template']['page-heading'] = "Payment";
$GLOBALS['template']['breadcrumbs'] = array("Home"=>"index.php","payment" => "payride.php");
$user_acc_details = array();
$tariff_data = [];
$route_data = [];
$rides_tariff_data = [];


if(empty($_SESSION['loggedin'])){ 
    
    header("location: ".SITE_URL."index.php"); //Yes? then redirect user to the home page
    exit;
    
  }

$bookride_token = !empty($_POST['b-token']) ? $_POST['b-token'] : 0;

if(empty($_POST) || empty($_SESSION['booking'][$bookride_token])){

    ob_start(); ?>  
    
    
        <div class="col-sm-offset-2 col-sm-8" style = "height:auto; padding-top:10%; padding-bottom:10%;">
        
            <img src="img/info_.gif" class="gifanim" width="200px" style="margin-left:auto; margin-right:auto; display:block;"/>
            <div class="spacer-1"></div>
            <div class="spacer-1"></div>

            <h1 style="text-align:center;"> Invalid Parameter! </h1>
            <div class="spacer-1"></div>
            <div class="spacer-1"></div>
            <p style="text-align:center;">There was an error processing your payment information.</p>
            <br>
            <div style="text-align:center;"><a href="index.php" class="btn btn-lg btn-yellow">Home</a></div>
        </div>
        


    <?php

        $response = ob_get_clean(); //grab html string content
        $GLOBALS['template']['page-content'] = $response; //display html string content

        //finish up by loading up the appropriate view files
        include "../drop-files/templates/headertpl.php";
        include "../drop-files/templates/pageheadingtpl.php";
        include "../drop-files/templates/pagebodytpl.php";
        include "../drop-files/templates/footertpl.php";
        exit; //done! let's get out


}


$error_data = 0;

//saanitize
$_POST['date'] = isset($_POST['date']) ? trim($_POST['date']) : '';
$_POST['time'] = isset($_POST['time']) ? trim($_POST['time']) : '';


if(empty($_POST['date']) || strlen($_POST['date']) > 11){
    $error_data = 1;
    echo "date";
}



if(empty($_POST['time']) || strlen($_POST['time']) > 11){
    $error_data = 1;
    echo "time";
}



if(empty($_POST['payoption'])){
    $error_data = 1;
    echo "pay";
}

$payment_type = 1;
switch($_POST['payoption']){

    case "wallet":
    $payment_type = 2;
    break;

    case "card":
    $payment_type = 3;
    break;

    case "pos":
    $payment_type = 4;
    break;

    default:
    $error_data = 1;
    break;
}


if($error_data){
    ob_start(); ?>  
    
    
        <div class="col-sm-offset-2 col-sm-8" style = "height:auto; padding-top:10%; padding-bottom:10%;">
        
            <img src="img/info_.gif" class="gifanim" width="200px" style="margin-left:auto; margin-right:auto; display:block;"/>
            <div class="spacer-1"></div>
            <div class="spacer-1"></div>

            <h1 style="text-align:center;"> Invalid Parameter! </h1>
            <div class="spacer-1"></div>
            <div class="spacer-1"></div>
            <p style="text-align:center;">There was an error processing your payment information.</p>
            <br>
            <div style="text-align:center;"><a href="index.php" class="btn btn-lg btn-yellow">Home</a></div>
        </div>
        


    <?php

        $response = ob_get_clean(); //grab html string content
        $GLOBALS['template']['page-content'] = $response; //display html string content

        //finish up by loading up the appropriate view files
        include "../drop-files/templates/headertpl.php";
        include "../drop-files/templates/pageheadingtpl.php";
        include "../drop-files/templates/pagebodytpl.php";
        include "../drop-files/templates/footertpl.php";
        exit; //done! let's get out


}


$date = mysqli_real_escape_string($GLOBALS['DB'], $_POST['date']);
$time = mysqli_real_escape_string($GLOBALS['DB'], $_POST['time']);


//store booking information on database

$pickup_datetime = date('Y-m-d H:i:s',strtotime($date . " " . $time));


$query = sprintf('INSERT INTO %stbl_bookings (user_firstname,user_lastname,user_phone,user_id,pickup_datetime, pickup_address, pickup_long, pickup_lat, dropoff_address, dropoff_long,dropoff_lat,estimated_cost,route_id,ride_id,payment_type,date_created) VALUES'.
'("%s","%s","%s","%d","%s","%s","%s","%s","%s","%s","%s","%s","%d","%d","%d","%s")', 
DB_TBL_PREFIX, 
$_SESSION['firstname'],
$_SESSION['lastname'],
$_SESSION['phone'],
$_SESSION['uid'],
$pickup_datetime,
$_SESSION['booking'][$bookride_token]['p_addr'],
$_SESSION['booking'][$bookride_token]['a_lng'],
$_SESSION['booking'][$bookride_token]['a_lat'],
$_SESSION['booking'][$bookride_token]['d_addr'],
$_SESSION['booking'][$bookride_token]['b_lng'],
$_SESSION['booking'][$bookride_token]['b_lat'],
$_SESSION['booking'][$bookride_token]['cost'],
$_SESSION['booking'][$bookride_token]['route_id'],
$_SESSION['booking'][$bookride_token]['ride_id'],
$payment_type,
gmdate('Y-m-d H:i:s', time()) 
);


if(! $result = mysqli_query($GLOBALS['DB'], $query)){
    
    ob_start(); ?>  
    
    
        <div class="col-sm-offset-2 col-sm-8" style = "height:auto; padding-top:10%; padding-bottom:10%;">
        
            <img src="img/info_.gif" class="gifanim" width="200px" style="margin-left:auto; margin-right:auto; display:block;"/>
            <div class="spacer-1"></div>
            <div class="spacer-1"></div>

            <h1 style="text-align:center;"> Invalid Parameter! </h1>
            <div class="spacer-1"></div>
            <div class="spacer-1"></div>
            <p style="text-align:center;">There was an error processing your payment information.</p>
            <br>
            <div style="text-align:center;"><a href="index.php" class="btn btn-lg btn-yellow">Home</a></div>
        </div>
        


    <?php

        $response = ob_get_clean(); //grab html string content
        $GLOBALS['template']['page-content'] = $response; //display html string content

        //finish up by loading up the appropriate view files
        include "../drop-files/templates/headertpl.php";
        include "../drop-files/templates/pageheadingtpl.php";
        include "../drop-files/templates/pagebodytpl.php";
        include "../drop-files/templates/footertpl.php";
        exit; //done! let's get out
    
}


//send email
$message = '';
//composing the email body.
$message .= '<html>';
$message .= '<div style = "width:500px;"><img src="'."http://". $_SERVER['HTTP_HOST'].'/img/logo-mid.png" width="180px" style="margin-left:auto; margin-right:auto; display:block;"/><br/>';
$message .='<h3 style="text-align:center;">Thank you for booking a ride with CabMan</h3><br /><br />';
$message .='<p>Your ride booking with the following details was successful:</p>';
$message .="<p><b> Pick-Up Address: </b> {$_SESSION['booking'][$bookride_token]['p_addr']}</p>";
$message .="<p><b> Drop off Address: </b> {$_SESSION['booking'][$bookride_token]['d_addr']}</p>";
$message .="<p><b> Ride Cost: </b> ₦{$_SESSION['booking'][$bookride_token]['cost']}</p>";
$message .="<br />";
$message .="<p>Make payments, top-up your wallet,get instant notifications and track your bookings in realtime by downloading our App from Play store or iTunes.</p>"; 
$message .="<p style='text-align:center;'><a href='#'><img src='http://{$_SERVER['HTTP_HOST']}/img/_app-google.png' ></a> &nbsp;  &nbsp; <a href='#'><img src='http://{$_SERVER['HTTP_HOST']}/img/_app-apple.png' ></a></p>"; 
$message .="</html >";

$mail_sender_address = 'From: '.MAIL_SENDER;
$headers = array($mail_sender_address,'MIME-Version: 1.0', 'Content-Type: text/html; charset="iso-8859-1"'); //Required for a HTML formatted E-mail ;)

mail($_SESSION['email'], WEBSITE_NAME." - New Booking", $message, join("\r\n", $headers));




//succes view render

ob_start(); ?>  
    
    
    <div class="col-sm-offset-2 col-sm-8" style = "height:auto; padding-top:10%; padding-bottom:10%;">
    
        <img src="img/success_.gif" class="gifanim" width="200px" style="margin-left:auto; margin-right:auto; display:block;"/>
    <div class="spacer-1"></div>
    <div class="spacer-1"></div>

        <h1 style="text-align:center;"> Booking Success! </h1>
        <div class="spacer-1"></div>
        <div class="spacer-1"></div>
        <p style="text-align:center;">Your ride has been booked. You can track your booking by downloading our App from Play Store and iTunes.</p>
        <div style="text-align:center"><a href="#"><img src="img/_app-google.png" ></a> &nbsp;  &nbsp; <a href="#"><img src="img/_app-apple.png" ></a> </div>
        <br>
        
    </div>
    


<?php

    $response = ob_get_clean(); //grab html string content
    $GLOBALS['template']['page-content'] = $response; //display html string content

    //finish up by loading up the appropriate view files
    include "../drop-files/templates/headertpl.php";
    include "../drop-files/templates/pageheadingtpl.php";
    include "../drop-files/templates/pagebodytpl.php";
    include "../drop-files/templates/footertpl.php";
    exit; //done! let's get out












?>