<?php
session_start();
include("../../drop-files/lib/common.php");
include "../../drop-files/config/db.php";
define('ITEMS_PER_PAGE', 100); //define constant for number of items to display per page

$number_of_payouts = 0;
$payouts_data = [];
$franchise_data = [];
$driver_details = [];
$user_details = [];
$wallet_amount = 0;
$wallet_balance = 0;
$account_type = 0;

if(isset($_SESSION['expired_session'])){
    header("location: ".SITE_URL."login.php?timeout=1");
    exit;
}

if(!(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == 1)){ //if user is not logged in run this code
  header("location: ".SITE_URL."login.php"); //Yes? then redirect user to the login page
  exit;
}

if($_SESSION['account_type'] /* != 2 && $_SESSION['account_type'] */ != 3){ ////if user is an admin or dispatcher
    //header("location: ".SITE_URL."access-denied.php"); //Yes? then warn the user for trying to access an unauthorized page
    echo "<h1>Access Denied!</h1>";
    exit;
}

$GLOBALS['admin_template']['page_title'] = "<i class='fa fa-handshake-o'></i> Payout"; //Set the title of the page on the admin interface
$GLOBALS['admin_template']['active_menu'] = "payout"; //Set the appropriate menu item active


//Get all franchises

$query = sprintf('SELECT * FROM %1$stbl_franchise ORDER BY franchise_name ASC', DB_TBL_PREFIX);


if($result = mysqli_query($GLOBALS['DB'], $query)){
  
    if(mysqli_num_rows($result)){
        while($row = mysqli_fetch_assoc($result)){
            $franchise_data[] = $row;
        }
    
     }
    mysqli_free_result($result);
}   

//Get number of payout records
$query = sprintf('SELECT COUNT(*) FROM %stbl_payouts', DB_TBL_PREFIX);  //Get and count all data

//echo mysqli_error($GLOBALS['DB']);

if($result = mysqli_query($GLOBALS['DB'], $query)){
    if(mysqli_num_rows($result)){

       $row = mysqli_fetch_assoc($result);
          
      $number_of_payouts = $row['COUNT(*)'];
         
     }
    mysqli_free_result($result);
}   



if(isset($_GET['page'])){
    $page_number = (int) $_GET['page'];
  }else{
      $page_number = 1;
    }
    
    $pages = ceil($number_of_payouts / ITEMS_PER_PAGE) ;
    if($page_number > $pages)$page_number = 1; 
    if($page_number < 0)$page_number = 1; 
    $offset = ($page_number - 1) * ITEMS_PER_PAGE;
  
  
  
  $query = sprintf('SELECT * FROM %stbl_payouts ORDER BY date_payout DESC LIMIT %d,%d', DB_TBL_PREFIX, $offset, ITEMS_PER_PAGE);
  
  
  if($result = mysqli_query($GLOBALS['DB'], $query)){
    
      if(mysqli_num_rows($result)){
          while($row = mysqli_fetch_assoc($result)){
              $payouts_data[] = $row;
          }
      
       }
      mysqli_free_result($result);
  }  





if(empty($_POST)){ //let's render the add-new-ride page UI'
    
    ob_start();
    include('../../drop-files/templates/admin/payouttpl.php');
    $GLOBALS['admin_template']['page_content'] = ob_get_clean();
    include "../../drop-files/templates/admin/admin-interface.php";
    exit;


}





/* var_dump($_POST);
exit; */




$account_type = (int) $_POST['scope'];

if($account_type == 1){ //driver

    $driver_id = (int) $_POST['driver-scope-id'];
    $fund_amount = (float) $_POST['fund-amount'];

    $query = sprintf('SELECT * FROM %1$stbl_drivers 
    LEFT JOIN %1$stbl_franchise ON %1$stbl_franchise.id = %1$stbl_drivers.franchise_id 
    WHERE %1$stbl_drivers.driver_id = %2$d', DB_TBL_PREFIX, $driver_id);


    if($result = mysqli_query($GLOBALS['DB'], $query)){
        
        if(mysqli_num_rows($result)){
            
            $driver_details = mysqli_fetch_assoc($result);
            $wallet_amount = $driver_details['wallet_amount'];

            //update driver wallet with amount
            $query = sprintf('UPDATE %stbl_drivers SET wallet_amount = wallet_amount + %f WHERE driver_id = "%d"', DB_TBL_PREFIX, $fund_amount,$driver_id);
            $result = mysqli_query($GLOBALS['DB'], $query);

            $wallet_balance = $wallet_amount + $fund_amount;
        }else{
            $_SESSION['action_error'][]    = "Driver not found";
        }
        
    }else{
        $_SESSION['action_error'][]    = "Driver not found";
    }



    
    
}elseif($account_type == 2 || $account_type == 3){
    
    $user_id = (int) $account_type == 2 ? $_POST['customer-scope-id'] : $_POST['staff-scope-id'];
    $fund_amount = (float) $_POST['fund-amount'];
    $query = sprintf('SELECT * FROM %1$stbl_users 
    WHERE %1$stbl_users.user_id = %2$d', DB_TBL_PREFIX, $user_id);


    if($result = mysqli_query($GLOBALS['DB'], $query)){
        
        if(mysqli_num_rows($result)){
            
            $user_details = mysqli_fetch_assoc($result);
            $wallet_amount = $user_details['wallet_amount'];
            //update customer / staff wallet with amount
            $query = sprintf('UPDATE %stbl_users SET wallet_amount = wallet_amount + %f WHERE user_id = "%d"', DB_TBL_PREFIX, $fund_amount,$user_id);
            $result = mysqli_query($GLOBALS['DB'], $query);

            $wallet_balance = $wallet_amount + $fund_amount;
        
        }else{
            $_SESSION['action_error'][]    = "Customer / Staff not found";
        }
        
    }else{
        $_SESSION['action_error'][]    = "Customer / Staff not found";
    }

    
    
}else{
    $_SESSION['action_error'][]    = "Account type not selected";
}




/* echo mysqli_error($GLOBALS['DB']);
exit; */

if(!empty($_SESSION['action_error'])){
   
    ob_start();
            include('../../drop-files/templates/admin/walletfundtpl.php'); 
                $msgs = '';
                foreach($_SESSION['action_error'] as $action_error){
                    $msgs .= "<p style='text-align:left;'><i style='color:red;' class='fa fa-circle-o'></i> ".$action_error . "</p>";
                }

                $cache_prevent = RAND();
                echo"<script>
            setTimeout(function(){ 
                    jQuery( function(){
                    swal({
                        title: '<h1>Error</h1>'".',
            text:"'.$msgs .'",'.
            "imageUrl: '../img/info_.gif?a=" . $cache_prevent . "',
            html:true,
                    });
                    });
                    },500); 
                    
                    </script>";
            
                    unset($_SESSION['action_error']);
            
        


        $pageContent = ob_get_clean();
        $GLOBALS['admin_template']['page_content'] = $pageContent;
        include "../../drop-files/templates/admin/admin-interface.php";
        exit;




}

$driver_id = !empty($driver_details) ? $driver_details['driver_id'] : 0;
$driver_firstname = !empty($driver_details) ? $driver_details['firstname'] : '';
$driver_lastname = !empty($driver_details) ? $driver_details['lastname'] : '';
$driver_phone = !empty($driver_details) ? $driver_details['phone'] : '';
$driver_franchise = !empty($driver_details) ? $driver_details['franchise_name'] : '';


$user_id = (int) !empty($user_details) ? $user_details['user_id'] : 0;
$user_firstname = !empty($user_details) ? $user_details['firstname'] : '';
$user_lastname = !empty($user_details) ? $user_details['lastname'] : '';
$user_phone = !empty($user_details) ? $user_details['phone'] : '';





//Store data to database
$query = sprintf('INSERT INTO %stbl_wallet_fund(staff_id,staff_firstname,staff_lastname,fund_type,driver_id,driver_firstname,driver_lastname,driver_phone,franchise_name,customer_id,customer_firstname,customer_lastname,customer_phone,fund_comment,date_fund,fund_amount,wallet_balance) VALUES'.
'("%d","%s","%s","%d","%d","%s","%s","%s","%s","%d","%s","%s","%s","%s","%s","%f","%f")', 
    DB_TBL_PREFIX,
    $_SESSION['uid'],
    $_SESSION['firstname'],
    $_SESSION['lastname'],
    $account_type,
    $driver_id,
    $driver_firstname,
    $driver_lastname,
    $driver_phone,
    $driver_franchise,
    $user_id,
    $user_firstname,
    $user_lastname,
    $user_phone,
    strip_tags(mysqli_real_escape_string($GLOBALS['DB'], $_POST['fund-comment'])),
    gmdate('Y-m-d H:i:s', time()),
    $fund_amount,
    $wallet_balance
);

$result = mysqli_query($GLOBALS['DB'], $query);


//refresh wallet fund history view 

//Get number of wallet funding records
$query = sprintf('SELECT COUNT(*) FROM %stbl_wallet_fund', DB_TBL_PREFIX);  //Get and count all data

//echo mysqli_error($GLOBALS['DB']);

if($result = mysqli_query($GLOBALS['DB'], $query)){
    if(mysqli_num_rows($result)){

       $row = mysqli_fetch_assoc($result);
          
      $number_of_payouts = $row['COUNT(*)'];
         
     }
    mysqli_free_result($result);
}   



if(isset($_GET['page'])){
    $page_number = (int) $_GET['page'];
  }else{
      $page_number = 1;
    }
    
    $pages = ceil($number_of_payouts / ITEMS_PER_PAGE) ;
    if($page_number > $pages)$page_number = 1; 
    if($page_number < 0)$page_number = 1; 
    $offset = ($page_number - 1) * ITEMS_PER_PAGE;
  
  
$payouts_data = [];
$query = sprintf('SELECT * FROM %stbl_wallet_fund ORDER BY date_fund DESC LIMIT %d,%d', DB_TBL_PREFIX, $offset, ITEMS_PER_PAGE);
  
  
  if($result = mysqli_query($GLOBALS['DB'], $query)){
    
      if(mysqli_num_rows($result)){
          while($row = mysqli_fetch_assoc($result)){
              $payouts_data[] = $row;
          }
      
       }
      mysqli_free_result($result);
  }  



  $_SESSION['action_success'][] = "The wallet was funded successfully.";

  ob_start();
  include('../../drop-files/templates/admin/walletfundtpl.php'); 
      $msgs = '';
      foreach($_SESSION['action_success'] as $action_success){
        $msgs .= "<p style='text-align:left;'><i style='color:green;' class='fa fa-circle-o'></i> ".$action_success . "</p>";
      }

      $cache_prevent = RAND();
      echo"<script>
  setTimeout(function(){ 
          jQuery( function(){
          swal({
              title: '<h1>Success</h1>'".',
  text:"'.$msgs .'",'.
  "imageUrl: '../img/success_.gif?a=" . $cache_prevent . "',
  html:true,
          });
          });
          },500); 
          
          </script>";
  
          unset($_SESSION['action_success']);
  



$pageContent = ob_get_clean();
$GLOBALS['admin_template']['page_content'] = $pageContent;
include "../../drop-files/templates/admin/admin-interface.php";
exit;






?>