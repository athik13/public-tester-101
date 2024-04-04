<?php
session_start();
include("../drop-files/lib/common.php");
include "../drop-files/config/db.php";

header("location: ".SITE_URL."index.php"); //remove to enable registration through web
exit;

$message = "";
$user_id = 0;
$GLOBALS['admin_template']['active_menu'] = "main-signup";
$GLOBALS['template']['page-heading'] = "Sign-Up";
$GLOBALS['template']['breadcrumbs'] = array("Home"=>"index.php","Sign-Up" => "register.php");


if(isset($_SESSION['loggedin'])){  //check if user is already signed in and trying to register
    if($_SESSION['loggedin'] == 1){
      
      $GLOBALS['template']['page-heading'] = "Sign-Up Error"; //Load up header title global variable to display a custom value
      ob_start(); ?>  
      
      
      <div class="col-sm-offset-2 col-sm-8" style = "height:auto; padding-top:10%; padding-bottom:10%;">
      
          <img src="img/info_.gif" class="gifanim" width="200px"  style="margin-left:auto; margin-right:auto; display:block;"/>
      <div class="spacer-1"></div>
      <div class="spacer-1"></div>
          <h1 style="text-align:center;"> Authenticated User Currently Signed In </h1>
          <div class="spacer-1"></div>
          <div class="spacer-1"></div>
          <p>We have authenticated your login credentials and have already signed you in. You cannot sign-up while you are signed in. Please Sign out to sign-up a new member</p>
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
  }


if(empty($_POST)){
    include "../drop-files/templates/headertpl.php";
    include "../drop-files/templates/pageheadingtpl.php";
    include "../drop-files/templates/registertpl.php";
    include "../drop-files/templates/footertpl.php";
    exit;
}


$_POST['firstname'] = str_replace(" ","",$_POST['firstname']); //remove spaces
$_POST['lastname'] = str_replace(" ","",$_POST['lastname']); //remove spaces



if(empty($_POST['firstname'])) {
    $error[] = "Please enter your first name";
    
} 

if(strlen($_POST['firstname']) < 2){
    $error[] = "Your firstname is too short";            
}

if(preg_match('/[^a-z]/i', $_POST['firstname'])){
    $error[] = "Your first name must contain only alphabetical characters";            
}

       
if(empty($_POST['lastname'])) {
   $error[] = "Please enter your lastname";
} 
 if(preg_match('/[^a-z]/i', $_POST['lastname'])){
    $error[] = "Your lastname must contain only alphabetical characters";            
}
 if(strlen($_POST['lastname']) < 2){
    $error[] = "Your lastname is too short";            
}


if(empty($_POST['email'])) {
    $error[] = "Please enter a valid email";
} 

if(!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)){
    $error[] = "Your email is not a valid email format";
}
if(strlen($_POST['email'])>64){
    $error[] = "Your email is too long. Email must be lower than 64 characters";
}
if(strlen($_POST['phone']) > 20) {
    $error[] = "Your phone number is too long";
} 
if(strlen($_POST['phone']) < 5) {
$error[] = "Your phone number is too short";
} 

/* if(empty($_POST['username'])) {
    $error[] = "Please enter a username";
} 
if(preg_match('/[^a-z0-9@._-]/i', $_POST['username'])){
    $error[] = "Your Username should contain only lowercase characters  and can contain numbers or any of these characters _-.@;";            
}
if((strlen($_POST['username'])) > 15 || (strlen($_POST['username'])) < 2 ){
     $error[] = "Your username should not be more that 15 or lower than 2 characters";
} */

if((strlen($_POST['password']) < 8 )){
   $error[] = "Password must not be less than eight characters";
}
if((strlen($_POST['password']) > 60 )){
   $error[] = "Password is too long. Password must not be more than 60 characters";
}

if($_POST['password'] !== $_POST['rpassword']) {
     $error[] = "Password and password repeat are not the same ";
     
}
if(empty($_POST['password'])|| empty($_POST['rpassword']) ) {
    $error[] = "Please enter a password";
} 



if(! empty($error)){ //If any error was found on the form; notify the user about the error. display the form 

   
    $GLOBALS['template']['page-heading'] = "Sign-Up";
    include "../drop-files/templates/headertpl.php";
    include "../drop-files/templates/pageheadingtpl.php";
    include "../drop-files/templates/registertpl.php";
    include "../drop-files/templates/footertpl.php";
    exit; 
    
}
    
//Checck if email or phone number already exists

$query = sprintf('SELECT user_id,email,phone FROM %stbl_users WHERE email = "%s" OR phone="%s"', DB_TBL_PREFIX, mysqli_real_escape_string($GLOBALS['DB'], $_POST['email']),mysqli_real_escape_string($GLOBALS['DB'], $_POST['phone']));




if($result = mysqli_query($GLOBALS['DB'], $query)){
    if(mysqli_num_rows($result)){

        $row = mysqli_fetch_assoc($result);
        if($row['email'] == mysqli_real_escape_string($GLOBALS['DB'], $_POST['email'])){
            $error[] = "The email address already exists. Please use a different email address";
        }elseif($row['phone'] == mysqli_real_escape_string($GLOBALS['DB'], $_POST['phone'])){
            $error[] = "The phone number already exists. Please use a different phone number";
        }else{
            $error[] = "The email address or phone number already exists. Please use a different email address or phone number";
        }      
        
        
        $GLOBALS['template']['page-heading'] = "Sign-Up Error";
        include "../drop-files/templates/headertpl.php";
        include "../drop-files/templates/pageheadingtpl.php";
        include "../drop-files/templates/registertpl.php";
        include "../drop-files/templates/footertpl.php";
        exit; 
    }
}else{

    $error[] = "Error connecting to database. Please contact the administrator.";
    $GLOBALS['template']['page-heading'] = "Sign-Up Error";
    include "../drop-files/templates/headertpl.php";
    include "../drop-files/templates/pageheadingtpl.php";
    include "../drop-files/templates/registertpl.php";
    include "../drop-files/templates/footertpl.php";
    exit; 


}


$refcode = crypto_string("alnum",15);

//OK, all good. lets store the registrant form data in the database
$verify_reg  = 0;
$query = sprintf('INSERT INTO %stbl_users (firstname, lastname, email, phone, pwd_raw, password_hash, account_create_date,referal_code) VALUES'.
'("%s","%s","%s","%s","%s","%s","%s","%s")', 
DB_TBL_PREFIX, 
mysqli_real_escape_string($GLOBALS['DB'], ucfirst(strtolower(strip_tags($_POST['firstname'])))),
mysqli_real_escape_string($GLOBALS['DB'], ucfirst(strtolower(strip_tags($_POST['lastname'])))),
mysqli_real_escape_string($GLOBALS['DB'], strip_tags($_POST['email'])),
mysqli_real_escape_string($GLOBALS['DB'], strip_tags($_POST['phone'])),
mysqli_real_escape_string($GLOBALS['DB'], $_POST['password']),
password_hash(mysqli_real_escape_string($GLOBALS['DB'], $_POST['password']), PASSWORD_DEFAULT),
gmdate('Y-m-d H:i:s', time()),
$refcode 
);


if(! $result = mysqli_query($GLOBALS['DB'], $query)){
    
    $error[] = "An error has occured. Could not create new user. Please contact the Administrator";
    $GLOBALS['template']['page-heading'] = "Sign-Up Error";
    include "../drop-files/templates/headertpl.php";
    include "../drop-files/templates/pageheadingtpl.php";
    include "../drop-files/templates/registertpl.php";
    include "../drop-files/templates/footertpl.php";
    exit; 
    
}
else{
        $user_id = mysqli_insert_id ( $GLOBALS['DB'] );
        
    }

    

    $token = crypto_string("nozero",5); //generate token

if(!$user_id){
    $error[] = "An error has occured. Could not create new user account";    
    $GLOBALS['template']['page-heading'] = "Sign-Up Error";
    include "../drop-files/templates/headertpl.php";
    include "../drop-files/templates/pageheadingtpl.php";
    include "../drop-files/templates/registertpl.php";
    include "../drop-files/templates/footertpl.php";
    exit; 

}


//Store activation token information and user ID  in database 
$query = sprintf('INSERT INTO %stbl_account_codes (user_id, code) VALUES ("%d","%s")',DB_TBL_PREFIX, $user_id, $token); 


if (!$result = mysqli_query($GLOBALS['DB'], $query)){
    $error[] = "An error has occured. Could not store user token. Please retry registration";

    $query = sprintf('DELETE FROM %stbl_users WHERE user_id = "%d"', DB_TBL_PREFIX, $user_id); //delete already inserted record 
    $result = mysqli_query($GLOBALS['DB'], $query);

    $GLOBALS['template']['page-heading'] = "Registration Error";
    include "../drop-files/templates/headertpl.php";
    include "../drop-files/templates/pageheadingtpl.php";
    include "../drop-files/templates/registertpl.php";
    include "../drop-files/templates/footertpl.php";
    exit; 

    
}


//Send an email with activation link bearing the token to the user

//composing the email body.
$message .= '<html>';
$message .= '<div style = "width:500px;"><img src="'."http://". $_SERVER['HTTP_HOST'].'/img/logo-mid.png" width="180px" style="margin-left:auto; margin-right:auto; display:block;"/><br/>';
$message .='<h2 style="text-align:center;">Thank you for registering with CabMan</h2><br /><br />';
$message .='<p>Your account has been created but is currently not activated. To complete your registration, enter the activation code as requested.</p>';
$message .="<h2><b style='text-align:center;'>{$token}</b></h2>";
$message .="<br /><br /><br /><br /><br /><br />";
$message .="<p>You have received this email because a user created an account on CabMan Website."; 
$message .="Simply ignore the message if it is not you</p></div>";
$message .="</html >";

$mail_sender_address = 'From: '.MAIL_SENDER;
$headers = array($mail_sender_address,'MIME-Version: 1.0', 'Content-Type: text/html; charset="iso-8859-1"'); //Required for a HTML formatted E-mail ;)

//echo $message;

//mail($_POST['email'], 'Account Activation - UNN e-Learning Platform', $message, join("\r\n", $headers)); //send the email
if(!mail($_POST['email'], WEBSITE_NAME." - Activation Code", $message, join("\r\n", $headers))){

    $error[] = "An error has occured. Could not send activation email to user email address";

    
    $query = sprintf('DELETE FROM %stbl_users WHERE user_id = "%d"', DB_TBL_PREFIX, $user_id); //delete already inserted record 
    $result = mysqli_query($GLOBALS['DB'], $query);

    print_r(error_get_last());
    $GLOBALS['template']['page-heading'] = "Sign-Up Error";
    include "../drop-files/templates/headertpl.php";
    include "../drop-files/templates/pageheadingtpl.php";
    include "../drop-files/templates/registertpl.php";
    include "../drop-files/templates/footertpl.php";
    exit; 

} //send the email

$_SESSION['uid'] = $user_id;
$_SESSION['email'] = $_POST['email'];
header("location: ".SITE_URL."verify.php"); //Yes? then redirect user to the home page
exit;

/* //Create and display a view to notify user of successful account creation

$GLOBALS['template']['page-heading'] = "Registration Success"; //Load up header title global variable to display a custom value
ob_start(); ?>  
    
    
    <div class="col-sm-offset-2 col-sm-8" style = "height:auto; padding-top:10%; padding-bottom:10%;">
    
        <img src="img/success_.gif" class="gifanim" width="200px" style="margin-left:auto; margin-right:auto; display:block;"/>
    <div class="spacer-1"></div>
    <div class="spacer-1"></div>

        <h1 style="text-align:center;"> Thank You! </h1>
        <div class="spacer-1"></div>
        <div class="spacer-1"></div>
        <p>Your Account has been Successfully Created. Please Activate your account by Clicking on the Activation Link Sent to your Email.
        If you don't find the Email in your inbox, check in your SPAM folder - if still not found, be patient. Network conditions usually influence email delivery time.</p>
        
    </div>
    


<?php

    $response = ob_get_clean(); //grab html string content
    $GLOBALS['template']['page-content'] = $response; //display html string content

    //finish up by loading up the appropriate view files
    include "../drop-files/templates/headertpl.php";
    include "../drop-files/templates/pageheadingtpl.php";
    include "../drop-files/templates/pagebodytpl.php";
    include "../drop-files/templates/footertpl.php";
    exit; //done! let's get out */



?>
