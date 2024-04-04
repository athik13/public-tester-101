<?php
session_start();
include("../../drop-files/lib/common.php");
include "../../drop-files/config/db.php";
define('ITEMS_PER_PAGE', 100); //define constant for number of items to display per page

$number_of_fundings = 0;
$wallet_funding_data = [];
$driver_details = [];
$user_details = [];
$wallet_amount = 0;
$wallet_balance = 0;
$account_type = 0;
$query_modifier = "> 0";
$currency_page_items = [];
$currency_page_items_sorted = [];

if(isset($_SESSION['expired_session'])){
    header("location: ".SITE_URL."login.php?timeout=1");
    exit;
}

if(!(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == 1)){ //if user is not logged in run this code
  header("location: ".SITE_URL."login.php"); //Yes? then redirect user to the login page
  exit;
}

if($_SESSION['account_type'] != 5 && $_SESSION['account_type'] != 3){ ////if user is not an admin or biller
    $_SESSION['action_error'][] = "Access Denied!";
    header("location: ".SITE_URL."admin/index.php"); //Yes? then redirect user to the login page
    exit;
}

$GLOBALS['admin_template']['page_title'] = "<i class='fa fa-google-wallet'></i> Fund Wallet"; //Set the title of the page on the admin interface
$GLOBALS['admin_template']['active_menu'] = "wallet"; //Set the appropriate menu item active


if(!empty($_GET['search-fund'])){


    if(isset($_GET['customer-scope-id2']) && (int) $_GET['search-context'] == 2 && $_GET['customer-scope-id2'] != NULL){
        $query_modifier .= " AND " . DB_TBL_PREFIX . "tbl_wallet_fund.fund_type = 2 AND " . DB_TBL_PREFIX . "tbl_wallet_fund.customer_id = " . (int) $_GET['customer-scope-id2'];
    }

    if(isset($_GET['driver-scope-id2']) && (int) $_GET['search-context'] == 1 && $_GET['driver-scope-id2'] != NULL){
        $query_modifier .= " AND " . DB_TBL_PREFIX . "tbl_wallet_fund.fund_type = 1 AND " . DB_TBL_PREFIX . "tbl_wallet_fund.driver_id = " . (int) $_GET['driver-scope-id2'];
    }


    if(isset($_GET['staff-scope-id2']) && (int) $_GET['search-context'] == 3 && $_GET['staff-scope-id2'] != NULL){
        $query_modifier .= " AND " . DB_TBL_PREFIX . "tbl_wallet_fund.fund_type = 3 AND " . DB_TBL_PREFIX . "tbl_wallet_fund.staff_id = " . (int) $_GET['staff-scope-id2'];
    }


    
    if(isset($_GET['date-fund']) && $_GET['date-fund'] != NULL){
        $end_date = date('Y-m-d', strtotime($_GET['date-fund'] . " +1day"));
        $query_modifier .= " AND " . DB_TBL_PREFIX . "tbl_wallet_fund.date_fund BETWEEN '" . $_GET['date-fund'] . "' AND '" . $end_date ."'" ;
    }



}





//Get number of wallet funding records
$query = sprintf('SELECT COUNT(*) FROM %stbl_wallet_fund WHERE id %s', DB_TBL_PREFIX, $query_modifier);  //Get and count all data


//echo mysqli_error($GLOBALS['DB']);

if($result = mysqli_query($GLOBALS['DB'], $query)){
    if(mysqli_num_rows($result)){

       $row = mysqli_fetch_assoc($result);
          
      $number_of_fundings = $row['COUNT(*)'];
         
     }
    mysqli_free_result($result);
}   



if(isset($_GET['page'])){
    $page_number = (int) $_GET['page'];
  }else{
      $page_number = 1;
    }
    
    $pages = ceil($number_of_fundings / ITEMS_PER_PAGE) ;
    if($page_number > $pages)$page_number = 1; 
    if($page_number < 0)$page_number = 1; 
    $offset = ($page_number - 1) * ITEMS_PER_PAGE;
  
  
  
  $query = sprintf('SELECT * FROM %stbl_wallet_fund WHERE id %s ORDER BY date_fund DESC LIMIT %d,%d', DB_TBL_PREFIX,$query_modifier ,$offset, ITEMS_PER_PAGE);
  
  
  if($result = mysqli_query($GLOBALS['DB'], $query)){
    
      if(mysqli_num_rows($result)){
          while($row = mysqli_fetch_assoc($result)){
              $wallet_funding_data[] = $row;
          }
      
       }
      mysqli_free_result($result);
  }  


  

//Get currency data from DB
$query = sprintf('SELECT * FROM %stbl_currencies ORDER BY `default` DESC', DB_TBL_PREFIX);


if($result = mysqli_query($GLOBALS['DB'], $query)){
  
    if(mysqli_num_rows($result)){
        while($row = mysqli_fetch_assoc($result)){
            $currency_page_items[] = $row;
            $currency_page_items_sorted[$row['id']] = $row;
        }
    
     }
    mysqli_free_result($result);
}




if(empty($_POST)){ //let's render the add-new-ride page UI'
    
    ob_start();
    include('../../drop-files/templates/admin/walletfundtpl.php');
    $GLOBALS['admin_template']['page_content'] = ob_get_clean();
    include "../../drop-files/templates/admin/admin-interface.php";
    exit;


}





/* var_dump($_POST);
exit; */

/* if(DEMO){
    
    $_SESSION['action_error'][] = "You are running in Demo mode. Action cannot be completed";
    
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
    
    
    
    
} */




$account_type = (int) $_POST['scope'];
$currency_id = (int) $_POST['fund-currency'];
$currency_id = !empty($currency_id) ? $currency_id : 1;
$user_type = 0;

if($account_type == 1){ //driver

    
    $user_type = 1;
    $driver_id = (int) $_POST['driver-scope-id'];
    $person_id = $driver_id;
    $fund_amount = (float) $_POST['fund-amount'];
    $fund_currency_symbol = $currency_page_items_sorted[$currency_id]['symbol'];
    $fund_currency_exchng = $currency_page_items_sorted[$currency_id]['exchng_rate'];
    $fund_currency_code = $currency_page_items_sorted[$currency_id]['iso_code'];
    $fund_amount_converted = $fund_amount / $fund_currency_exchng; //convert to default currency. wallet value is always in default currency
    

    $query = sprintf('SELECT * FROM %1$stbl_drivers 
    LEFT JOIN %1$stbl_franchise ON %1$stbl_franchise.id = %1$stbl_drivers.franchise_id 
    WHERE %1$stbl_drivers.driver_id = %2$d', DB_TBL_PREFIX, $driver_id);


    if($result = mysqli_query($GLOBALS['DB'], $query)){
        
        if(mysqli_num_rows($result)){
            
            $driver_details = mysqli_fetch_assoc($result);
            $wallet_amount = $driver_details['wallet_amount'];
            
            //update driver wallet with amount
            $query = sprintf('UPDATE %stbl_drivers SET wallet_amount = wallet_amount + %f WHERE driver_id = "%d"', DB_TBL_PREFIX, $fund_amount_converted,$driver_id);
            $result = mysqli_query($GLOBALS['DB'], $query);

            $wallet_balance = $wallet_amount + $fund_amount_converted;


            //Add this transaction to wallet transactions database table
            $transaction_id = crypto_string();
            $query = sprintf('INSERT INTO %stbl_wallet_transactions (cur_symbol,cur_exchng_rate,cur_code,transaction_id,amount,wallet_balance,user_id,user_type,`desc`,`type`,transaction_date) VALUES'.
            '("%s","%s","%s","%s","%s","%s","%d","%d","%s","%d","%s")', 
            DB_TBL_PREFIX,
            $fund_currency_symbol,
            $fund_currency_exchng,
            $fund_currency_code,
            $transaction_id,
            $fund_amount,
            $wallet_balance,
            $driver_id,
            1,
            strip_tags(mysqli_real_escape_string($GLOBALS['DB'], $_POST['fund-comment'])), 
            1,
            gmdate('Y-m-d H:i:s', time())

            );

            $result = mysqli_query($GLOBALS['DB'], $query);

        }else{
            $_SESSION['action_error'][]    = "Driver not found";
        }
        
    }else{
        $_SESSION['action_error'][]    = "Driver not found";
    }



    
    
}elseif($account_type == 2 || $account_type == 3){
    $user_type = 0;
    $user_id = (int) $account_type == 2 ? $_POST['customer-scope-id'] : $_POST['staff-scope-id'];
    $person_id = $user_id;
    $fund_amount = (float) $_POST['fund-amount'];
    $fund_currency_symbol = $currency_page_items_sorted[$currency_id]['symbol'];
    $fund_currency_exchng = $currency_page_items_sorted[$currency_id]['exchng_rate'];
    $fund_currency_code = $currency_page_items_sorted[$currency_id]['iso_code'];
    $fund_amount_converted = $fund_amount / $fund_currency_exchng; //convert to default currency. wallet value is always in default currency


    $query = sprintf('SELECT * FROM %1$stbl_users 
    WHERE %1$stbl_users.user_id = %2$d', DB_TBL_PREFIX, $user_id);


    if($result = mysqli_query($GLOBALS['DB'], $query)){
        
        if(mysqli_num_rows($result)){
            
            $user_details = mysqli_fetch_assoc($result);
            $wallet_amount = $user_details['wallet_amount'];
            //update customer / staff wallet with amount
            $query = sprintf('UPDATE %stbl_users SET wallet_amount = wallet_amount + %f WHERE user_id = "%d"', DB_TBL_PREFIX, $fund_amount_converted,$user_id);
            $result = mysqli_query($GLOBALS['DB'], $query);

            $wallet_balance = $wallet_amount + $fund_amount_converted;

            //Add this transaction to wallet transactions database table
            $transaction_id = crypto_string();
            $query = sprintf('INSERT INTO %stbl_wallet_transactions (cur_symbol,cur_exchng_rate,cur_code,transaction_id,amount,wallet_balance,user_id,user_type,`desc`,`type`,transaction_date) VALUES'.
            '("%s","%s","%s","%s","%s","%s","%d","%d","%s","%d","%s")', 
            DB_TBL_PREFIX,
            $fund_currency_symbol,
            $fund_currency_exchng,
            $fund_currency_code,
            $transaction_id,
            $fund_amount,
            $wallet_balance,
            $person_id,
            0,
            strip_tags(mysqli_real_escape_string($GLOBALS['DB'], $_POST['fund-comment'])), 
            1,
            gmdate('Y-m-d H:i:s', time())

            );

            $result = mysqli_query($GLOBALS['DB'], $query);
            
        
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
$query = sprintf('INSERT INTO %stbl_wallet_fund(cur_symbol,cur_exchng_rate,cur_code,staff_id,staff_firstname,staff_lastname,fund_type,driver_id,driver_firstname,driver_lastname,driver_phone,franchise_name,customer_id,customer_firstname,customer_lastname,customer_phone,fund_comment,date_fund,fund_amount,wallet_balance) VALUES'.
'("%s","%s","%s","%d","%s","%s","%d","%d","%s","%s","%s","%s","%d","%s","%s","%s","%s","%s","%f","%f")', 
    DB_TBL_PREFIX,
    $fund_currency_symbol,
    $fund_currency_exchng,
    $fund_currency_code,
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
          
      $number_of_fundings = $row['COUNT(*)'];
         
     }
    mysqli_free_result($result);
}   



if(isset($_GET['page'])){
    $page_number = (int) $_GET['page'];
  }else{
      $page_number = 1;
    }
    
    $pages = ceil($number_of_fundings / ITEMS_PER_PAGE) ;
    if($page_number > $pages)$page_number = 1; 
    if($page_number < 0)$page_number = 1; 
    $offset = ($page_number - 1) * ITEMS_PER_PAGE;
  
  
$wallet_funding_data = [];
$query = sprintf('SELECT * FROM %stbl_wallet_fund ORDER BY date_fund DESC LIMIT %d,%d', DB_TBL_PREFIX, $offset, ITEMS_PER_PAGE);
  
  
  if($result = mysqli_query($GLOBALS['DB'], $query)){
    
      if(mysqli_num_rows($result)){
          while($row = mysqli_fetch_assoc($result)){
              $wallet_funding_data[] = $row;
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