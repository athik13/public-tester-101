<?php
session_start();
include("../../drop-files/lib/common.php");
include "../../drop-files/config/db.php";
define('ITEMS_PER_PAGE', 100); //define constant for number of items to display per page


if(isset($_SESSION['expired_session'])){
    header("location: ".SITE_URL."login.php?timeout=1");
    exit;
}

if(!(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == 1)){ //if user is not logged in run this code
  header("location: ".SITE_URL."login.php"); //Yes? then redirect user to the login page
  exit;
}

if($_SESSION['account_type'] != 5 && $_SESSION['account_type'] != 3){ ////if user is an admin or dispatcher
    $_SESSION['action_error'][] = "Access Denied!";
    header("location: ".SITE_URL."admin/index.php"); //Yes? then redirect user to the login page
    exit;
}

$GLOBALS['admin_template']['page_title'] = "<i class='fa fa-handshake-o'></i> Payouts"; //Set the title of the page on the admin interface
$GLOBALS['admin_template']['active_menu'] = "payout"; //Set the appropriate menu item active



$withdrawal_requests_data = [];
$query_modifier  = '1 ';
$number_of_withdrawal_data = 0;
$gateway_payment_status = 0;



if(isset($_GET['view_user_type']) && isset($_GET['filter'])){
    $view_user_type = (int) $_GET['view_user_type'] != 0 ? (int) $_GET['view_user_type'] : 1;
    $filter = (int) $_GET['filter'] != 0 ? (int) $_GET['filter'] : 1;

    if($view_user_type == 2 && $filter == 1){ //franchise and pending   
        $query_modifier  .= 'AND '. DB_TBL_PREFIX . 'tbl_wallet_withdrawal.user_type = 1';
    }elseif($view_user_type == 2 && $filter == 2){
        $query_modifier  .= 'AND '. DB_TBL_PREFIX . 'tbl_wallet_withdrawal.user_type = 1 AND ' . DB_TBL_PREFIX . 'tbl_wallet_withdrawal.request_status = 0';
    }elseif($view_user_type == 2 && $filter == 3){ //franchise and settled 
        $query_modifier  .= 'AND '. DB_TBL_PREFIX . 'tbl_wallet_withdrawal.user_type = 1 AND ' . DB_TBL_PREFIX . 'tbl_wallet_withdrawal.request_status = 2';
    }elseif($view_user_type == 2 && $filter == 4){ //franchise and declined 
        $query_modifier  .= 'AND '. DB_TBL_PREFIX . 'tbl_wallet_withdrawal.user_type = 1 AND ' . DB_TBL_PREFIX . 'tbl_wallet_withdrawal.request_status = 1';
    }elseif($view_user_type == 3 && $filter == 1){
        $query_modifier  .= 'AND '. DB_TBL_PREFIX . 'tbl_wallet_withdrawal.user_type = 0';
    }elseif($view_user_type == 3 && $filter == 2){ //driver and pending   
        $query_modifier  .= 'AND '. DB_TBL_PREFIX . 'tbl_wallet_withdrawal.user_type = 0 AND ' . DB_TBL_PREFIX . 'tbl_wallet_withdrawal.request_status = 0';
    }elseif($view_user_type == 3 && $filter == 3){ //driver and settled 
        $query_modifier  .= 'AND '. DB_TBL_PREFIX . 'tbl_wallet_withdrawal.user_type = 0 AND ' . DB_TBL_PREFIX . 'tbl_wallet_withdrawal.request_status = 2';
    }elseif($view_user_type == 3 && $filter == 4){ //driver and declined 
        $query_modifier  .= 'AND '. DB_TBL_PREFIX . 'tbl_wallet_withdrawal.user_type = 0 AND ' . DB_TBL_PREFIX . 'tbl_wallet_withdrawal.request_status = 1';
    }

    
}



//get number of payouts
$query = sprintf('SELECT COUNT(*) FROM %1$stbl_wallet_withdrawal WHERE %2$s', DB_TBL_PREFIX, $query_modifier);

if($result = mysqli_query($GLOBALS['DB'], $query)){
    if(mysqli_num_rows($result)){
        $row = mysqli_fetch_assoc($result);        
        $number_of_withdrawal_data = $row['COUNT(*)'];
    }
}

//calculate pages
if(isset($_GET['page'])){
    $page_number = (int) $_GET['page'];
}else{
    $page_number = 1;
}
    
$pages = ceil($number_of_withdrawal_data / ITEMS_PER_PAGE) ;
if($page_number > $pages)$page_number = 1; 
if($page_number < 0)$page_number = 1; 
$offset = ($page_number - 1) * ITEMS_PER_PAGE;

//get withdrawal data
$query = sprintf('SELECT %1$stbl_wallet_withdrawal.*,%1$stbl_wallet_withdrawal.id AS wid,%1$stbl_drivers.firstname,%1$stbl_drivers.lastname,%1$stbl_drivers.country_dial_code,%1$stbl_drivers.phone,%1$stbl_franchise.franchise_name,%1$stbl_franchise.franchise_phone FROM %1$stbl_wallet_withdrawal 
LEFT JOIN %1$stbl_franchise ON %1$stbl_wallet_withdrawal.person_id = %1$stbl_franchise.id AND %1$stbl_wallet_withdrawal.user_type = 1
LEFT JOIN %1$stbl_drivers ON %1$stbl_drivers.driver_id = %1$stbl_wallet_withdrawal.person_id AND %1$stbl_wallet_withdrawal.user_type = 0
WHERE %2$s ORDER BY %1$stbl_wallet_withdrawal.date_requested DESC LIMIT %3$d, %4$d', DB_TBL_PREFIX, $query_modifier, $offset, ITEMS_PER_PAGE);

if($result = mysqli_query($GLOBALS['DB'], $query)){
    if(mysqli_num_rows($result)){
        while($row = mysqli_fetch_assoc($result)){
            $withdrawal_requests_data[$row['id']] = $row;
        }
        
    }
}



if(isset($_GET['action'])){
    if($_GET['action'] == 'approve'){
        $withdrawal_id = isset($_GET['wid']) ? (int) $_GET['wid'] : 0;
        if(!empty($withdrawal_id) && isset($withdrawal_requests_data[$withdrawal_id])){//verify this withdrawal status on db first
            if($withdrawal_requests_data[$withdrawal_id]['request_status'] != 0){
                $cache_prevent = RAND();
                $msgs = "<p style='text-align:left;'><i style='color:red;' class='fa fa-circle-o'></i> Processing withdrawal request failed as request has already been processed.</p>";
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
            }else{

                $gateway_payment_status = 1; //returned value from function that pays the user through the gateway
        
                if($gateway_payment_status){ //if gateway payment of user was successful
                    
                    //update withdrawal table
                    $query = sprintf('UPDATE %stbl_wallet_withdrawal SET request_status = %d, date_settled = "%s" WHERE id = %d',DB_TBL_PREFIX,2,gmdate('Y-m-d H:i:s', time()) ,$withdrawal_id);
                    $result = mysqli_query($GLOBALS['DB'], $query);

                    $withdrawal_requests_data[$withdrawal_id]['request_status'] = 2; //update already ready db data record to reflect success

                    $cache_prevent = RAND();
                    $msgs = "<p style='text-align:left;'><i style='color:green;' class='fa fa-circle-o'></i> Withdrawal request has been approved and money transfered to driver account.</p>";
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
                }else{
                    $cache_prevent = RAND();
                    $msgs = "<p style='text-align:left;'><i style='color:red;' class='fa fa-circle-o'></i> Processing withdrawal request failed as payment gateway returned error.</p>";
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
                }
            }
                

        }else{

            $cache_prevent = RAND();
            $msgs = "<p style='text-align:left;'><i style='color:red;' class='fa fa-circle-o'></i> Processing withdrawal request failed. Invalid record</p>";
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
            
        }        
        

        
    }elseif($_GET['action'] == 'reject'){
        $withdrawal_id = isset($_GET['wid']) ? (int) $_GET['wid'] : 0;
        if(!empty($withdrawal_id) && isset($withdrawal_requests_data[$withdrawal_id])){//verify this withdrawal status on db first
            if($withdrawal_requests_data[$withdrawal_id]['request_status'] != 0){
                $cache_prevent = RAND();
                $msgs = "<p style='text-align:left;'><i style='color:red;' class='fa fa-circle-o'></i> Processing withdrawal request failed as request has already been processed.</p>";
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
            }else{

                
                    
                    //update withdrawal table
                    $query = sprintf('UPDATE %stbl_wallet_withdrawal SET request_status = %d,date_settled = "%s" WHERE id = %d',DB_TBL_PREFIX,1,gmdate('Y-m-d H:i:s', time()),$withdrawal_id);
                    $result = mysqli_query($GLOBALS['DB'], $query);

                    //convert amount to be withdrawn to default local currency
                    $withdrawal_amount_converted = $withdrawal_requests_data[$withdrawal_id]['withdrawal_amount'] / $withdrawal_requests_data[$withdrawal_id]['cur_exchng_rate'];

                    if($withdrawal_requests_data[$withdrawal_id]['user_type']){ //franchise

                        //update franchise wallet by reversing initial debit on driver wthrawal request action
                        $query = sprintf('UPDATE %stbl_franchise SET wallet_amount = wallet_amount + "%s" WHERE id = %d', DB_TBL_PREFIX, $withdrawal_amount_converted, $withdrawal_requests_data[$withdrawal_id]['person_id']);
                        $result = mysqli_query($GLOBALS['DB'], $query);

                        //add record to transaction table as wallet funding from admin
                        $transaction_id = crypto_string();
                        $query = sprintf('INSERT INTO %stbl_wallet_transactions (transaction_id,amount,cur_symbol,cur_exchng_rate,cur_code,wallet_balance,`user_id`,user_type,`desc`,`type`,transaction_date) VALUES 
                        ("%s","%f","%s","%s","%s","%f","%d","%d","%s","%d","%s")',
                        DB_TBL_PREFIX,
                        $transaction_id,
                        $withdrawal_requests_data[$withdrawal_id]['withdrawal_amount'],
                        $withdrawal_requests_data[$withdrawal_id]['cur_symbol'],
                        $withdrawal_requests_data[$withdrawal_id]['cur_exchng_rate'],
                        $withdrawal_requests_data[$withdrawal_id]['cur_code'],
                        $withdrawal_requests_data[$withdrawal_id]['fwallet_amount'] + $withdrawal_amount_converted,
                        $withdrawal_requests_data[$withdrawal_id]['person_id'],
                        2,
                        'Withdrawal request reversal',
                        2,
                        gmdate('Y-m-d H:i:s', time()) 
                        );
                        $result = mysqli_query($GLOBALS['DB'], $query);

                    }else{//driver


                        //update driver wallet by reversing initial debit on driver wthrawal request action
                        $query = sprintf('UPDATE %stbl_drivers SET wallet_amount = wallet_amount + "%s" WHERE driver_id = %d', DB_TBL_PREFIX, $withdrawal_amount_converted, $withdrawal_requests_data[$withdrawal_id]['person_id']);
                        $result = mysqli_query($GLOBALS['DB'], $query);

                        //add record to transaction table as wallet funding from admin
                        $transaction_id = crypto_string();
                        $query = sprintf('INSERT INTO %stbl_wallet_transactions (transaction_id,amount,cur_symbol,cur_exchng_rate,cur_code,wallet_balance,`user_id`,user_type,`desc`,`type`,transaction_date) VALUES 
                        ("%s","%f","%s","%s","%s","%f","%d","%d","%s","%d","%s")',
                        DB_TBL_PREFIX,
                        $transaction_id,
                        $withdrawal_requests_data[$withdrawal_id]['withdrawal_amount'],
                        $withdrawal_requests_data[$withdrawal_id]['cur_symbol'],
                        $withdrawal_requests_data[$withdrawal_id]['cur_exchng_rate'],
                        $withdrawal_requests_data[$withdrawal_id]['cur_code'],
                        $withdrawal_requests_data[$withdrawal_id]['wallet_amount'] + $withdrawal_amount_converted,
                        $withdrawal_requests_data[$withdrawal_id]['person_id'],
                        1,
                        'Withdrawal request reversal',
                        2,
                        gmdate('Y-m-d H:i:s', time()) 
                        );
                        $result = mysqli_query($GLOBALS['DB'], $query);

                    }
                        


                    
                    


                    $withdrawal_requests_data[$withdrawal_id]['request_status'] = 1; //update already ready db data record to reflect success

                    $cache_prevent = RAND();
                    $msgs = "<p style='text-align:left;'><i style='color:green;' class='fa fa-circle-o'></i> Withdrawal request rejected with initial transactions reversed.</p>";
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
                
            }
                

        }else{

            $cache_prevent = RAND();
            $msgs = "<p style='text-align:left;'><i style='color:red;' class='fa fa-circle-o'></i> Processing withdrawal request failed. Invalid record</p>";
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
            
        }        
        

        
    }

}





ob_start();
include('../../drop-files/templates/admin/payouttpl.php');
$pageContent = ob_get_clean();
$GLOBALS['admin_template']['page_content'] = $pageContent;
include "../../drop-files/templates/admin/admin-interface.php";
exit;


?>