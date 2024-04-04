<?php
session_start();
include("../../drop-files/lib/common.php");
include "../../drop-files/config/db.php";
define('ITEMS_PER_PAGE', 20); //define constant for number of items to display per page

$active_tab = 0;
$id = 0;
$franchise_data = [];
$total_amount_earned_franchise = 0;
$number_of_franchise_drivers = 0;

$banks_details = getdefaultbanks();

/* $banks_details = array(
    "044"=>"Access Bank",
    "023"=>"Citibank",
    "063"=>"Diamond Bank",
    "050"=>"Ecobank",
    "040"=>"Equitorial Trust Bank",
    "011"=>"First Bank",
    "214"=>"FCMB",
    "070"=>"Fidelity Bank",
    "085"=>"Finbank",
    "058"=>"Guaranty Trust Bank",
    "030"=>"Heritage Bank",
    "082"=>"Keystone Bank",
    "014"=>"Mainstreet Bank",
    "076"=>"Skye Bank",
    "221"=>"Stanbic IBTC Bank",
    "032"=>"Union Bank of Nigeria",
    "033"=>"United Bank of Africa (UBA)",
    "215"=>"Unity Bank",
    "035"=>"Wema Bank",
    "057"=>"Zenith Bank",
    "xxx"=> "Other..."
); */


if(isset($_SESSION['expired_session'])){
    header("location: ".SITE_URL."login.php?timeout=1");
    exit;
}

if(!(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == 1)){ //if user is not logged in run this code
  header("location: ".SITE_URL."login.php"); //Yes? then redirect user to the login page
  exit;
}

if($_SESSION['account_type'] != 2 && $_SESSION['account_type'] != 3 && $_SESSION['account_type'] != 4){ ////if user is an admin or dispatcher
    $_SESSION['action_error'][] = "Access Denied!";
    header("location: ".SITE_URL."admin/index.php"); //Yes? then redirect user to the login page
    exit;
}



$GLOBALS['admin_template']['page_title'] = "<i class='fa fa-briefcase'></i> View Franchise"; //Set the title of the page on the admin interface
$GLOBALS['admin_template']['active_menu'] = "franch"; //Set the appropriate menu item active
$GLOBALS['admin_template']['active_sub_menu'] = "franch-all"; //Set the appropriate menu item active


$id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['id']) ? (int) $_POST['id'] : 0);

if($_SESSION['account_type'] == 4){
    $id = $_SESSION['uid'];
}

//get franchise data
$query = sprintf('SELECT * FROM %stbl_franchise WHERE id = "%d"', DB_TBL_PREFIX, $id); //Get required user information from DB


if($result = mysqli_query($GLOBALS['DB'], $query)){
    if(mysqli_num_rows($result)){
        $franchise_data = mysqli_fetch_assoc($result);
                
    }else{
        $_SESSION['action_error'][]    = "Invalid franchise record.";
        header('location: all-franchise.php');
        exit;
    }
    
}
else{ //No record matching the USER ID was found in DB. Show view to notify user

    $_SESSION['action_error'][]    = "Database error!";
    header('location: all-franchise.php');
    exit;
}


//get total amount earned by drivers of this franchise
/* if($id == 1){ //for default franchise (owner)
    $query = sprintf('SELECT SUM(IF(%1$stbl_bookings.franchise_id = 1,((%1$stbl_bookings.paid_amount / %1$stbl_bookings.cur_exchng_rate) - ((%1$stbl_bookings.paid_amount / %1$stbl_bookings.cur_exchng_rate) * %1$stbl_bookings.driver_commision / 100)), ((%1$stbl_bookings.paid_amount / %1$stbl_bookings.cur_exchng_rate) - ((%1$stbl_bookings.paid_amount / %1$stbl_bookings.cur_exchng_rate) * %1$stbl_bookings.driver_commision / 100)) - (((%1$stbl_bookings.paid_amount / %1$stbl_bookings.cur_exchng_rate) - ((%1$stbl_bookings.paid_amount / %1$stbl_bookings.cur_exchng_rate) * %1$stbl_bookings.driver_commision / 100)) * %1$stbl_bookings.franchise_commision / 100) )) AS amount_currency FROM %1$stbl_bookings WHERE %1$stbl_bookings.status = 3',DB_TBL_PREFIX);
}else{
    $query = sprintf('SELECT %1$stbl_bookings.franchise_id, SUM((((%1$stbl_bookings.paid_amount / %1$stbl_bookings.cur_exchng_rate) - ((%1$stbl_bookings.paid_amount / %1$stbl_bookings.cur_exchng_rate) * %1$stbl_bookings.driver_commision / 100)) * %1$stbl_bookings.franchise_commision / 100) ) AS amount_currency FROM %1$stbl_bookings WHERE %1$stbl_bookings.franchise_id = %2$d AND %1$stbl_bookings.status = 3 GROUP BY %1$stbl_bookings.franchise_id',DB_TBL_PREFIX, $id);
}
if($result = mysqli_query($GLOBALS['DB'], $query)){
    if(mysqli_num_rows($result)){

        $row = mysqli_fetch_assoc($result);   
        $total_amount_earned_franchise = floor($row['amount_currency'] * 100) / 100;    

         
     }
    mysqli_free_result($result);
} */


//echo mysqli_error($GLOBALS['DB']);

//Get number of drivers for this franchise
$query = sprintf('SELECT COUNT(*) AS drivers_count FROM %1$stbl_drivers WHERE franchise_id = %2$d', DB_TBL_PREFIX, $id);  //Get and count all data

//echo mysqli_error($GLOBALS['DB']);

if($result = mysqli_query($GLOBALS['DB'], $query)){
    if(mysqli_num_rows($result)){

        $row = mysqli_fetch_assoc($result);      
        $number_of_franchise_drivers = $row['drivers_count'];      
         
     }
    mysqli_free_result($result);
}


if(isset($_GET['tab'])){
    if($_GET['tab'] == 'ftransactions'){
        $active_tab = 0;
    }elseif($_GET['tab'] == 'fpayouts'){
        $active_tab = 1;
    }elseif($_GET['tab'] == 'fdrivers'){
        $active_tab = 2;
    }

}



    
ob_start();
include('../../drop-files/templates/admin/viewfranchisetpl.php');
$pageContent = ob_get_clean();
$GLOBALS['admin_template']['page_content'] = $pageContent;
include "../../drop-files/templates/admin/admin-interface.php";
exit;


function franchiseRequestPayout($amount, $franchise_id){

    //check if franchise has a pending request
    $query = sprintf('SELECT * FROM %stbl_wallet_withdrawal WHERE person_id = "%d" AND user_type = 1 AND request_status = 0', DB_TBL_PREFIX, $franchise_id); //Get required user information from DB


    if($result = mysqli_query($GLOBALS['DB'], $query)){
        if(mysqli_num_rows($result)){
            $row = mysqli_fetch_assoc($result);
            $withdrawal_date = date('l, M j, Y H:i:s',strtotime($row['date_requested'].' UTC'));
            $error = array("error"=>"You already have a pending withdrawal request of {$row['cur_symbol']}{$row['withdrawal_amount']} requested on - {$withdrawal_date}");
            return ($error);
        }
                
    }
    else{ //No record matching the USER ID was found in DB. Show view to notify user

        $error = array("error"=>"Database Error");
        return ($error);
    }




    //Verify if franchise has enough funds in wallet to make this request
    
    $query = sprintf('SELECT fwallet_amount FROM %stbl_franchise WHERE id = "%d"', DB_TBL_PREFIX, $franchise_id); //Get required user information from DB


    if($result = mysqli_query($GLOBALS['DB'], $query)){
        if(mysqli_num_rows($result)){
            $franchise_wallet_details = mysqli_fetch_assoc($result);
        }
        else{
            $error = array("error"=>"Cannot get franchise wallet information");
            return ($error);

        }
        
    }
    else{ //No record matching the USER ID was found in DB. Show view to notify user

        $error = array("error"=>"Database Error");
        return ($error);
    }
    
    
    
    if(isset($franchise_wallet_details['fwallet_amount'])){
        $balance = (float) $franchise_wallet_details['fwallet_amount'] - FRANCHISE_WITHDRAL_MIN_BALANCE - $amount;

        if(empty($balance) || $balance < 0){

            $error = array("error"=>"Insufficient wallet amount! You cannot withraw the requested amount.");
            //echo json_encode($error);
            return ($error);

        }
    }else{
        
        $error = array("error"=>"Insufficient wallet amount!");
        return ($error);
    }



    //All good. Deduct the requested amount from the franchise wallet
    
    $query = sprintf('UPDATE %stbl_franchise SET fwallet_amount = fwallet_amount - %f WHERE id = "%d"', DB_TBL_PREFIX, $amount,$franchise_id);
    $result = mysqli_query($GLOBALS['DB'], $query);

    $default_currency_symbol = !empty($_SESSION['default_currency']) ? $_SESSION['default_currency']['symbol'] : "₦";
    $default_currency_code = !empty($_SESSION['default_currency']) ? $_SESSION['default_currency']['iso_code'] : "NGN";
    $default_currency_exchng = !empty($_SESSION['default_currency']) ? $_SESSION['default_currency']['exchng_rate'] : 1;

    //save this withdrawal request
    $query = sprintf('INSERT INTO %stbl_wallet_withdrawal(cur_symbol,cur_exchng_rate,cur_code,person_id,wallet_amount,withdrawal_amount,wallet_balance,date_requested,user_type) VALUES 
    ("%s","%d","%s","%d","%f","%f","%f","%s","%d")', 
    DB_TBL_PREFIX,
    $default_currency_symbol,
    $default_currency_exchng,
    $default_currency_code,
    $franchise_id,
    $franchise_wallet_details['fwallet_amount'],
    $amount,
    $franchise_wallet_details['fwallet_amount'] - $amount,
    gmdate('Y-m-d H:i:s', time()),
    1 
    ); 
    $result = mysqli_query($GLOBALS['DB'], $query);

    //create an entry in wallet transaction table
    $transaction_id = crypto_string();
    $query = sprintf('INSERT INTO %stbl_wallet_transactions (transaction_id,amount,cur_symbol,cur_exchng_rate,cur_code,wallet_balance,`user_id`,user_type,`desc`,`type`,transaction_date) VALUES 
    ("%s","%f","%s","%s","%s","%f","%d","%d","%s","%d","%s")',
    DB_TBL_PREFIX,
    $transaction_id,
    $amount,
    $default_currency_symbol,
    $default_currency_exchng,
    $default_currency_code,
    $franchise_wallet_details['fwallet_amount'] - $amount,
    $franchise_id,
    2,
    'Withdrawal request amount debit',
    3,
    gmdate('Y-m-d H:i:s', time()) 
    );
    $result = mysqli_query($GLOBALS['DB'], $query);

    //echo mysqli_error($GLOBALS['DB']);
    $data_array = array("success"=>1);    
    return ($data_array);
}




?>