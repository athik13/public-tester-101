<?php
session_start();
include("../../drop-files/lib/common.php");
include "../../drop-files/config/db.php";

if(isset($_SESSION['expired_session'])){
    header("location: ".SITE_URL."login.php?timeout=1");
    exit;
}

if(!(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == 1)){ //if user is not logged in run this code
  header("location: ".SITE_URL."login.php"); //Yes? then redirect user to the login page
  exit;
}

if($_SESSION['account_type'] /* != 2 && $_SESSION['account_type'] */ != 3){ ////if user is an admin or dispatcher
    $_SESSION['action_error'][] = "Access Denied!";
    header("location: ".SITE_URL."admin/index.php"); //Yes? then redirect user to the login page
    exit;
}

$GLOBALS['admin_template']['page_title'] = "<i class='fa fa-bullhorn'></i> Message Broadcast"; //Set the title of the page on the admin interface
$GLOBALS['admin_template']['active_menu'] = "broadcast"; //Set the appropriate menu item active




//get all available cities
$inter_city_routes = [];

$query = sprintf('SELECT * FROM %1$stbl_routes WHERE r_scope = 0 ORDER BY r_title ASC', DB_TBL_PREFIX);


if($result = mysqli_query($GLOBALS['DB'], $query)){
  
    if(mysqli_num_rows($result)){
        while($row = mysqli_fetch_assoc($result)){
            $inter_city_routes[] = $row;
        }
    
     }
    mysqli_free_result($result);
} 




if(empty($_POST)){ //let's render the page UI'
    
    ob_start();
    include('../../drop-files/templates/admin/broadcasttpl.php');
    $pageContent = ob_get_clean();
    $GLOBALS['admin_template']['page_content'] = $pageContent;
    include "../../drop-files/templates/admin/admin-interface.php";
    exit;


}





//var_dump($_POST);

$msg_title = mysqli_real_escape_string($GLOBALS['DB'], $_POST['heading']);
$msg_content = mysqli_real_escape_string($GLOBALS['DB'], $_POST['msg']);

$msg_title = !empty($msg_title) ? $msg_title : "Attention!";
$broadcast_type = !empty($_POST['push-scope']) ? (int) $_POST['push-scope'] : 0;
$customer_id = !empty($_POST['booking-customerid']) ? (int) $_POST['booking-customerid'] : 0;
$driver_id = !empty($_POST['booking-driverid']) ? (int) $_POST['booking-driverid'] : 0;
/* $staff_id = !empty($_POST['booking-staffid']) ? (int) $_POST['booking-staffid'] : 0; */

$city_route = !empty($_POST['city-route']) ? (int) $_POST['city-route'] : 0;


if(empty($broadcast_type)){
    $_SESSION['action_error'][]    = "Please select the category of users or user you want to send a broadcast message to.";
}else{

    if($broadcast_type == 4 && empty($customer_id)){
        $_SESSION['action_error'][]    = "No customer selected! Please select a customer from the autocomplete dropdown list while entering the customer name.";
    }elseif($broadcast_type == 5 && empty($driver_id)){
        $_SESSION['action_error'][]    = "No driver selected! Please select a driver from the autocomplete dropdown list while entering the driver name.";
    }/* elseif($broadcast_type == 6 && empty($staff_id)){
        $_SESSION['action_error'][]    = "No staff selected! Please select a staff from the autocomplete dropdown list while entering the staff name.";
    } */elseif($broadcast_type == 1 || $broadcast_type == 2 /* || $broadcast_type == 3 */){
        if(empty($city_route)){
            $_SESSION['action_error'][]    = "Please select a city to send broadcast message to it's users.";
        }
    }

}





if(!empty($_SESSION['action_error'])){
   
    ob_start();
    include('../../drop-files/templates/admin/broadcasttpl.php'); 
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



if($broadcast_type == 1){ //broadcast to all customers (riders)
    sendPushNotification($msg_title,$msg_content,"/topics/rider-route-{$city_route}",null,1);

    $query = sprintf('INSERT INTO %stbl_notifications(person_id,user_type,content,n_type,date_created,route_id) VALUES 
        ("%d",0,"%s",5,"%s","%d")', 
        DB_TBL_PREFIX,
        0,
        $msg_content,
        gmdate('Y-m-d H:i:s', time()),
        $city_route 
    );

    $result = mysqli_query($GLOBALS['DB'], $query);

}elseif($broadcast_type == 2){ //broadcast to all drivers
    sendPushNotification($msg_title,$msg_content,"/topics/driver-route-{$city_route}",null,1);
    $query = sprintf('INSERT INTO %stbl_notifications(person_id,user_type,content,n_type,date_created,route_id) VALUES 
        ("%d",1,"%s",5,"%s","%d")', 
        DB_TBL_PREFIX,
        0,
        $msg_content,
        gmdate('Y-m-d H:i:s', time()),
        $city_route 
    );

    $result = mysqli_query($GLOBALS['DB'], $query);

}elseif($broadcast_type == 4){ //broadcast to a specific customer (rider)
    //get push notification token for this user and send message through push messaging
    $query = sprintf('SELECT push_notification_token FROM %stbl_users WHERE user_id = %d', DB_TBL_PREFIX, $customer_id);
    if($result = mysqli_query($GLOBALS['DB'], $query)){ 
        if(mysqli_num_rows(($result))){
            $row = mysqli_fetch_assoc($result);
            sendPushNotification($msg_title,$msg_content,$row['push_notification_token'],null,1);
        }
    }

    $query = sprintf('INSERT INTO %stbl_notifications(person_id,user_type,content,n_type,date_created,route_id) VALUES 
        ("%d",0,"%s",1,"%s","%d")', 
        DB_TBL_PREFIX,
        $customer_id,
        $msg_content,
        gmdate('Y-m-d H:i:s', time()),
        0 
    );

    $result = mysqli_query($GLOBALS['DB'], $query);
    
}elseif($broadcast_type == 5){ //broadcast to a specific driver
    //get push notification token for this driver and send message through push messaging
    $query = sprintf('SELECT push_notification_token FROM %stbl_drivers WHERE driver_id = %d', DB_TBL_PREFIX, $driver_id);
    if($result = mysqli_query($GLOBALS['DB'], $query)){ 
        if(mysqli_num_rows(($result))){
            $row = mysqli_fetch_assoc($result);
            sendPushNotification($msg_title,$msg_content,$row['push_notification_token'],null,1);
        }
    }

    $query = sprintf('INSERT INTO %stbl_notifications(person_id,user_type,content,n_type,date_created,route_id) VALUES 
        ("%d",1,"%s",1,"%s","%d")', 
        DB_TBL_PREFIX,
        $driver_id,
        $msg_content,
        gmdate('Y-m-d H:i:s', time()),
        0 
    );

    $result = mysqli_query($GLOBALS['DB'], $query);
    
}




$_SESSION['action_success'][] = "Message broadcast has been sent!";
ob_start();
    include('../../drop-files/templates/admin/broadcasttpl.php'); 
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