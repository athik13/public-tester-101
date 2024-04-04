<?php
session_start();
include("../../drop-files/lib/common.php");
include "../../drop-files/config/db.php";
define('ITEMS_PER_PAGE', 10); //define constant for number of items to display per page


$transaction_data = [];
$num_of_transactions = 0;

if(isset($_SESSION['expired_session'])){
    echo "<h1 style='text-align:center;'>Your session has expired. Please logout and login to continue.</h1>";
    exit;
}

if(!(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == 1)){ //if user is not logged in run this code
    echo "<h1 style='text-align:center;'>Please login to continue.</h1>";
    exit;
}

if($_SESSION['account_type'] != 2 && $_SESSION['account_type'] != 3){ ////if user is an admin or dispatcher
    echo "<h1 style='text-align:center;'>You are not authorized to access this page!.</h1>";
    exit;
}

if(!isset($_GET['user_type']) || !isset($_GET['id']) ){
    echo "<h1 style='text-align:center;'>Invalid parameter passed!.</h1>";
    exit;
}

$GLOBALS['admin_template']['page_title'] = "<i class='fa fa-money'></i> Transactions"; //Set the title of the page on the admin interface




$user_type = (int) !empty($_GET['user_type']) ? $_GET['user_type'] : 0;

$user_id = (int) !empty($_GET['id']) ? $_GET['id'] : 0;


$query = sprintf('SELECT COUNT(*) FROM %stbl_vogue_pay WHERE user_type = "%d" AND user_id = "%d" ', DB_TBL_PREFIX,$user_type,$user_id);


if($result = mysqli_query($GLOBALS['DB'], $query)){
  
    if(mysqli_num_rows($result)){
        while($row = mysqli_fetch_assoc($result)){
            $num_of_transactions = $row['COUNT(*)'];
        }
    
     }
    mysqli_free_result($result);
} 



if(isset($_GET['page'])){
    $page_number = (int) $_GET['page'];
  }else{
      $page_number = 1;
    }
    
$pages = ceil($num_of_transactions / ITEMS_PER_PAGE) ;
if($page_number > $pages)$page_number = 1; 
if($page_number < 0)$page_number = 1; 
$offset = ($page_number - 1) * ITEMS_PER_PAGE;
  
  


$query = sprintf('SELECT * FROM %stbl_vogue_pay WHERE user_type = "%d" AND user_id = "%d" ORDER BY `date` DESC', DB_TBL_PREFIX,$user_type,$user_id);


if($result = mysqli_query($GLOBALS['DB'], $query)){
  
    if(mysqli_num_rows($result)){
        while($row = mysqli_fetch_assoc($result)){
            $transaction_data[] = $row;
        }
    
     }
    mysqli_free_result($result);
}   

if(empty($_POST)){ 
    
    ob_start();
    include('../../drop-files/templates/admin/viewtransactionstpl.php');
    $pageContent = ob_get_clean();
    $GLOBALS['admin_template']['page_content'] = $pageContent;
    include "../../drop-files/templates/admin/admin-interface.php";
    exit;


}


?>
