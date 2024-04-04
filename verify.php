<?php
session_start();
include("../drop-files/lib/common.php");
include "../drop-files/config/db.php";
$GLOBALS['template']['page-heading'] = "Account Activation";
$GLOBALS['template']['breadcrumbs'] = array("Home"=>"index.php","Activate Account" => "#");
$user_id = 0;
$code = 0;



isset($_SESSION['uid']) ? $user_id = $_SESSION['uid'] : 0;

if(!$user_id){ //Lets check if userid was passed.
    header("location: ".SITE_URL."index.php"); //Yes? then redirect user to the home page
    exit;
}


if(isset($_GET['resend']) && $_GET['resend']){

    $code = crypto_string("nozero",5); //generate token

    $query = sprintf('DELETE FROM %stbl_account_codes WHERE user_id = "%d" AND context=0', DB_TBL_PREFIX, $user_id); //delete already inserted record 
    $result = mysqli_query($GLOBALS['DB'], $query);

    $query = sprintf('INSERT INTO %stbl_account_codes (user_id, code) VALUES ("%d","%s")',DB_TBL_PREFIX, $user_id, $code); 
    

    if(!$result = mysqli_query($GLOBALS['DB'], $query)){ //An error has occured while trying to update KSmart user ID on SIS user database record?
        $error[] = "An error has occured. Please try again later.";
        $GLOBALS['template']['page-heading'] = "Account Activation Error";
        include "../drop-files/templates/headertpl.php";
        include "../drop-files/templates/pageheadingtpl.php";
        include "../drop-files/templates/verifytpl.php";
        include "../drop-files/templates/footertpl.php";
        exit;
    }

        
    $message = "";

    $message .= '<html>';
    $message .= '<div style = "width:500px;"><img src="'."http://". $_SERVER['HTTP_HOST'].'/img/logo-mid.png" width="180px" style="margin-left:auto; margin-right:auto; display:block;"/><br/>';
    $message .='<h2 style="text-align:center;">Thank you for registering with CabMan</h2><br /><br />';
    $message .='<p>Your account has been created but is currently not activated. To complete your registration, enter the activation code as requested.</p>';
    $message .="<h2><b style='text-align:center;'>{$code}</b></h2>";
    $message .="<br /><br /><br /><br /><br /><br />";
    $message .="<p>You have received this email because a user created an account on CabMan Website."; 
    $message .="Simply ignore the message if it is not you</p></div>";
    $message .="</html >";

    $mail_sender_address = 'From: '.MAIL_SENDER;
    $headers = array($mail_sender_address,'MIME-Version: 1.0', 'Content-Type: text/html; charset="iso-8859-1"'); //Required for a HTML formatted E-mail ;)

    //echo $message;

    //mail($_POST['email'], 'Account Activation - UNN e-Learning Platform', $message, join("\r\n", $headers)); //send the email
    if(!mail($_SESSION['email'], WEBSITE_NAME." - Activation Code", $message, join("\r\n", $headers))){

        $error[] = "An error has occured. Could not send activation email to user email address";
              
        $GLOBALS['template']['page-heading'] = "Sign-Up Error";
        include "../drop-files/templates/headertpl.php";
        include "../drop-files/templates/pageheadingtpl.php";
        include "../drop-files/templates/verifytpl.php";
        include "../drop-files/templates/footertpl.php";
        exit; 

    } //send the email


    $messages[] = "Activation code sent.";
    $GLOBALS['template']['page-heading'] = "Account Activation Error";
    include "../drop-files/templates/headertpl.php";
    include "../drop-files/templates/pageheadingtpl.php";
    include "../drop-files/templates/verifytpl.php";
    include "../drop-files/templates/footertpl.php";
    exit;



}




if(!(isset($_POST['activate']))){ //Lets check if user has submitted the login form. No ? ok show login form.
    
    include "../drop-files/templates/headertpl.php";
    include "../drop-files/templates/pageheadingtpl.php";
    include "../drop-files/templates/verifytpl.php";
    include "../drop-files/templates/footertpl.php";
    exit;

}



//Ok user has submitted activation code; lets get to work

if (empty($_POST['code'])){
    $errors[] = "Please enter an activation code";
} 



if(! empty($error)){ //If any error was found on the form; notify the user about the error. display the form 
    
    $GLOBALS['template']['page-heading'] = "Account Login Error";
    include "../drop-files/templates/headertpl.php";
    include "../drop-files/templates/pageheadingtpl.php";
    include "../drop-files/templates/verifytpl.php";
    include "../drop-files/templates/footertpl.php";
    exit;
    
}

$code = !empty($_POST['code']) ? $_POST['code'] : 0;


//Let's check our local DB for user record'

$query = sprintf('SELECT code FROM %stbl_account_codes WHERE code = "%d" AND user_id = "%d" AND context = 0', DB_TBL_PREFIX, $code,$user_id); //Get required user information from DB


if($result = mysqli_query($GLOBALS['DB'], $query)){
    if(mysqli_num_rows($result)){
      $row = mysqli_fetch_assoc($result);
    }
    else{
        $error[] = "Wrong activation code entered.";
        $GLOBALS['template']['page-heading'] = "Account Activation Error";
        include "../drop-files/templates/headertpl.php";
        include "../drop-files/templates/pageheadingtpl.php";
        include "../drop-files/templates/verifytpl.php";
        include "../drop-files/templates/footertpl.php";
        exit;

    }
    
}
else{ //No record matching the USER ID was found in DB. Show view to notify user

    $error[] = "Wrong activation code entered.";
    $GLOBALS['template']['page-heading'] = "Account Activation Error";
    include "../drop-files/templates/headertpl.php";
    include "../drop-files/templates/pageheadingtpl.php";
    include "../drop-files/templates/verifytpl.php";
    include "../drop-files/templates/footertpl.php";
    exit;
}


$query = sprintf('UPDATE %stbl_users SET is_activated = 1 WHERE user_id = "%d"', DB_TBL_PREFIX,$user_id );

if(!$result = mysqli_query($GLOBALS['DB'], $query)){ //An error has occured while trying to update KSmart user ID on SIS user database record?
    $error[] = "An error has occured. Please try again later.";
    $GLOBALS['template']['page-heading'] = "Account Activation Error";
    include "../drop-files/templates/headertpl.php";
    include "../drop-files/templates/pageheadingtpl.php";
    include "../drop-files/templates/verifytpl.php";
    include "../drop-files/templates/footertpl.php";
    exit;
}

$query = sprintf('DELETE FROM %stbl_account_codes WHERE user_id = "%d" AND code="%d"', DB_TBL_PREFIX, $user_id,$code); //delete already inserted record 
$result = mysqli_query($GLOBALS['DB'], $query);


$GLOBALS['template']['page-heading'] = "Registration Success"; //Load up header title global variable to display a custom value
ob_start(); ?>  
    
    
    <div class="col-sm-offset-2 col-sm-8" style = "height:auto; padding-top:10%; padding-bottom:10%;">
    
        <img src="img/success_.gif" class="gifanim" width="200px" style="margin-left:auto; margin-right:auto; display:block;"/>
    <div class="spacer-1"></div>
    <div class="spacer-1"></div>

        <h1 style="text-align:center;"> Thank You! </h1>
        <div class="spacer-1"></div>
        <div class="spacer-1"></div>
        <p style="text-align:center;">Your Account has been Successfully Activated. Please login to contine.</p>
        <br>
        <div style="text-align:center;"><a href="login.php" class="btn btn-lg btn-yellow">Login</a></div>
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
