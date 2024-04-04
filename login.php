<?php
session_start();
include("../drop-files/lib/common.php");
include "../drop-files/config/db.php";
$GLOBALS['admin_template']['active_menu'] = "main-signin";
$GLOBALS['template']['page-heading'] = "Login";
$GLOBALS['template']['breadcrumbs'] = array("Home"=>"index.php","Login" => "login.php");
$user_acc_details = array();
$acc_type = isset($_POST['type']) ? (int) $_POST['type'] : 0;

if(isset($_GET['logout']) && $_GET['logout'] == 1){ //Lets check if user has requested to log out.    
    session_regenerate_id();
    $_SESSION = array();  //clear session data
    session_destroy();
    header("location: ".SITE_URL."index.php"); //Yes? then redirect user to the home page
    exit;
  }
  



if(isset($_SESSION['loggedin'])){ 
    if($_SESSION['loggedin'] == 1){ //run  this code if user is logged in already.
      header("location: ".SITE_URL."admin/index.php"); //Yes? then redirect user to the home page
      exit;
    }
  }



if(!(isset($_POST['login']))){ //Lets check if user has submitted the login form. No ? ok show login form.
    
    include "../drop-files/templates/headertpl.php";
    //include "../drop-files/templates/pageheadingtpl.php";
    include "../drop-files/templates/logintpl.php";
    include "../drop-files/templates/footertpl.php";
    exit;

}

//Ok user has submitted login form; lets get to work

if (empty($_POST['email'])){
    $error[] = "Please enter your email";
  } 
if(empty($_POST['password'])){
  $error[] = "Please enter a password";
} 

if(empty($_POST['type'])){
  $error[] = "Please select an account type";
} 


if((int) $_POST['type'] == 1){
  $error[] = "Login prohibitted for this account type";
}

/* if(!empty(DEMO) && $acc_type != 3){
  $error[] = "Demo Mode: You can only sign in as an Admin.";
} */


if(!empty($error)){ //If any error was found on the form; notify the user about the error. display the form 
    
    $GLOBALS['template']['page-heading'] = "Login Error";
    include "../drop-files/templates/headertpl.php";
    //include "../drop-files/templates/pageheadingtpl.php";
    include "../drop-files/templates/logintpl.php";
    include "../drop-files/templates/footertpl.php";
    exit;
    
}




//Let's check our local DB for user record'
if($acc_type != 4){
      $query = sprintf('SELECT country_dial_code,photo_file,user_id,firstname, lastname,email,phone,referal_code,is_activated,account_active,account_type,last_login_date,account_create_date FROM %stbl_users 
      WHERE email = "%s" AND pwd_raw = "%s" AND account_type = %d', DB_TBL_PREFIX, mysqli_real_escape_string($GLOBALS['DB'], $_POST['email']), mysqli_real_escape_string($GLOBALS['DB'],  $_POST['password']), $acc_type); //Get required user information from DB


      if($result = mysqli_query($GLOBALS['DB'], $query)){
          if(mysqli_num_rows($result)){
            $user_acc_details = mysqli_fetch_assoc($result);      
          }
          else{
              $error[] = "The email and password you entered does not match";
              $GLOBALS['template']['page-heading'] = "Login Error";
              include "../drop-files/templates/headertpl.php";
              //include "../drop-files/templates/pageheadingtpl.php";
              include "../drop-files/templates/logintpl.php";
              include "../drop-files/templates/footertpl.php";
              exit;

          }
          
      }
      else{ //No record matching the USER ID was found in DB. Show view to notify user

          $error[] = "The email and password you entered does not match";
          $GLOBALS['template']['page-heading'] = "Account Login Error";
          include "../drop-files/templates/headertpl.php";
          //include "../drop-files/templates/pageheadingtpl.php";
          include "../drop-files/templates/logintpl.php";
          include "../drop-files/templates/footertpl.php";
          exit;
      }


      if(isset($user_acc_details['is_activated']) && empty($user_acc_details['is_activated'])){
          /* $_SESSION['uid'] = $user_acc_details['user_id'];
          $_SESSION['email'] = $user_acc_details['email'];
          
          header("location: ".SITE_URL."verify.php"); 
          exit; */
          $error[] = "This account has not been activated. Contact Administrator.";
          $GLOBALS['template']['page-heading'] = "Login Error";
          include "../drop-files/templates/headertpl.php";
          //include "../drop-files/templates/pageheadingtpl.php";
          include "../drop-files/templates/logintpl.php";
          include "../drop-files/templates/footertpl.php";
          exit;
      }

      //Get default currency data
      $default_currency = [];
      $query = sprintf('SELECT * FROM %stbl_currencies WHERE `default` = %d', DB_TBL_PREFIX, 1);

      if($result = mysqli_query($GLOBALS['DB'], $query)){
        
          if(mysqli_num_rows($result)){
            $default_currency = mysqli_fetch_assoc($result);            
          }
          mysqli_free_result($result);
      }



      //load up session variables

      $_SESSION['firstname'] = $user_acc_details['firstname'];
      $_SESSION['lastname'] = $user_acc_details['lastname'];
      $_SESSION['uid'] = $user_acc_details['user_id'];
      $_SESSION['email'] = $user_acc_details['email'];
      $_SESSION['phone'] = $user_acc_details['phone'];
      $_SESSION['country_dial_code'] = $user_acc_details['country_dial_code'];
      $_SESSION['referal_code'] = $user_acc_details['referal_code'];
      $_SESSION['account_type'] = $user_acc_details['account_type'];
      $_SESSION['lastseen'] = $user_acc_details['last_login_date'];
      $_SESSION['joined'] = $user_acc_details['account_create_date'];
      //$photo = explode('/',$user_acc_details['photo_file']);
      $_SESSION['photo'] = isset($user_acc_details['photo_file']) ? $user_acc_details['photo_file'] : "0";
      $_SESSION['default_currency'] = $default_currency;
      $_SESSION['loggedin'] = 1;

      //set timezone if available
      if(!empty($_POST['timezone']) && isValidTimezoneId($_POST['timezone'])){
        $_SESSION['timezone'] = $_POST['timezone'];
      }
      

      //let's update the database record with user last time seen data
      $query = sprintf('UPDATE %stbl_users SET last_login_date = "%s", login_count = login_count + 1 WHERE user_id = "%d"', DB_TBL_PREFIX, gmdate('Y-m-d H:i:s', time()),$_SESSION['uid']);
      $result = mysqli_query($GLOBALS['DB'], $query);
      //redirect to appropriate page based on account type

      switch($user_acc_details['account_type']){
            
          case 2: //dispatcher
          header("location: ".SITE_URL."admin/index.php"); //Yes? then redirect user to dashboard
          exit;
          break;
        
          case 3://admin account
          header("location: ".SITE_URL."admin/index.php"); //Yes? then redirect user to dashboard
          exit;
          break;

          case 4://franchise account
          header("location: ".SITE_URL."admin/index.php"); //Yes? then redirect user to dashboard
          exit;
          break;

          case 5://biller account
          header("location: ".SITE_URL."admin/index.php"); //Yes? then redirect user to dashboard
          exit;
          break;
        
            
          default: //others
          header("location: ".SITE_URL."index.php"); //Yes? then redirect user to the home page
          exit;
          break;
        
        
        
        }

    }



    $query = sprintf('SELECT * FROM %stbl_franchise
    WHERE franchise_email = "%s" AND pwd_raw = "%s"', DB_TBL_PREFIX, mysqli_real_escape_string($GLOBALS['DB'], $_POST['email']), mysqli_real_escape_string($GLOBALS['DB'],  $_POST['password'])); //Get required user information from DB


      if($result = mysqli_query($GLOBALS['DB'], $query)){
          if(mysqli_num_rows($result)){
            $user_acc_details = mysqli_fetch_assoc($result);      
          }
          else{
              $error[] = "The email and password you entered does not match";
              $GLOBALS['template']['page-heading'] = "Login Error";
              include "../drop-files/templates/headertpl.php";
              //include "../drop-files/templates/pageheadingtpl.php";
              include "../drop-files/templates/logintpl.php";
              include "../drop-files/templates/footertpl.php";
              exit;

          }
          
      }
      else{ //No record matching the USER ID was found in DB. Show view to notify user

          $error[] = "The email and password you entered does not match";
          $GLOBALS['template']['page-heading'] = "Account Login Error";
          include "../drop-files/templates/headertpl.php";
          //include "../drop-files/templates/pageheadingtpl.php";
          include "../drop-files/templates/logintpl.php";
          include "../drop-files/templates/footertpl.php";
          exit;
      }


      
      //Get default currency data
      $default_currency = [];
      $query = sprintf('SELECT * FROM %stbl_currencies WHERE `default` = %d', DB_TBL_PREFIX, 1);

      if($result = mysqli_query($GLOBALS['DB'], $query)){
        
          if(mysqli_num_rows($result)){
            $default_currency = mysqli_fetch_assoc($result);            
          }
          mysqli_free_result($result);
      }



      //load up session variables

      $_SESSION['firstname'] = $user_acc_details['franchise_name'];
      $_SESSION['lastname'] = " ";
      $_SESSION['uid'] = $user_acc_details['id'];
      $_SESSION['email'] = $user_acc_details['franchise_email'];
      $_SESSION['phone'] = $user_acc_details['franchise_phone'];
      $_SESSION['country_dial_code'] = " ";
      $_SESSION['referal_code'] = " ";
      $_SESSION['account_type'] = 4;
      $_SESSION['lastseen'] = gmdate('Y-m-d H:i:s', time());
      $_SESSION['joined'] = $user_acc_details['date_created'];
      //$photo = explode('/',$user_acc_details['photo_file']);
      $_SESSION['photo'] = "0";
      $_SESSION['default_currency'] = $default_currency;
      $_SESSION['loggedin'] = 1;

      
      //redirect to appropriate page based on account type
      header("location: ".SITE_URL."admin/index.php"); //Yes? then redirect user to the home page      
      exit;




?>
