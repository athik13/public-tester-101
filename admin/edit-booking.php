<?php
session_start();
include("../../drop-files/lib/common.php");
include "../../drop-files/config/db.php";
$route_data = [];
$booking_data = [];
$id = 0;

if(isset($_SESSION['expired_session'])){
    header("location: ".SITE_URL."login.php?timeout=1");
    exit;
}

if(!(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == 1)){ //if user is not logged in run this code
  header("location: ".SITE_URL."login.php"); //Yes? then redirect user to the login page
  exit;
}

if($_SESSION['account_type'] != 2 && $_SESSION['account_type'] != 3){ ////if user is an admin or dispatcher
    $_SESSION['action_error'][] = "Access Denied!";
    header("location: ".SITE_URL."admin/index.php"); //Yes? then redirect user to the login page
    exit;
}

$GLOBALS['admin_template']['page_title'] = "<i class='fa fa-bookmark'></i>Edit Booking"; //Set the title of the page on the admin interface
$GLOBALS['admin_template']['active_menu'] = "bookings"; //Set the appropriate menu item active
$GLOBALS['admin_template']['active_sub_menu'] = "booking-all"; //Set the appropriate menu item active


if(!empty($_POST['booking-id'])){
    $id = (int) $_POST['booking-id'];
 }
elseif(!empty($_GET['id'])) {
    $id = (int) $_GET['id'] ;
 }elseif(!empty($_SESSION['reload_id'])){
    $id = (int) $_SESSION['reload_id'];
    unset($_SESSION['reload_id']);
 }


if(!empty($_GET['action']) && $_GET['action'] == "delete"){
    if(DEMO){
        $_SESSION['action_error'][] = "You are running in Demo mode. Action cannot be completed";
        header("location: ".SITE_URL."admin/all-bookings.php"); //Yes? then redirect 
        exit;
    }
    //Ensure that data exists on DB
    $bookings_data = [];
    $query = sprintf('SELECT * FROM %stbl_bookings WHERE id = "%d"',DB_TBL_PREFIX, $id );
        if($result = mysqli_query($GLOBALS['DB'], $query)){
                
            if(!mysqli_num_rows($result)){

                $_SESSION['action_error'][] = "Could not delete the requested record. The record was not found in the database";
                header("location: ".SITE_URL."admin/all-bookings.php"); //Yes? then redirect
                exit;
                
            }

            $bookings_data = mysqli_fetch_assoc($result);
            if($bookings_data['status'] == 1 || $bookings_data['status'] == 3){
                $_SESSION['action_error'][] = "Could not delete the requested record.";
                header("location: ".SITE_URL."admin/all-bookings.php"); //Yes? then redirect
                exit;
            }
            mysqli_free_result($result);
        }  

    //then delete record

    $query = sprintf('DELETE FROM %stbl_bookings WHERE id = "%d"', DB_TBL_PREFIX, $id); 

    if(!$result = mysqli_query($GLOBALS['DB'], $query)){ 
        $_SESSION['action_error'][] = "An error occured while trying to delete driver record from the database.";
        header("location: ".SITE_URL."admin/all-bookings.php"); //Yes? then redirect
        exit;
        
    }

    //delete driver allocations for this booking
    $query = sprintf('DELETE FROM %stbl_driver_allocate WHERE booking_id = "%d"', DB_TBL_PREFIX, $id);
    $result = mysqli_query($GLOBALS['DB'], $query);
                    
    $_SESSION['action_success'][] = "The booking record was successfully deleted.";
    if($bookings_data['scheduled'] == 1){
        header("location: ".SITE_URL."admin/all-sbookings.php"); //Yes? then redirect
    }else{
        if(isset($_GET['rdir'])){
            header("location: ".SITE_URL."admin/{$_GET['rdir']}"); //Yes? then redirect
        }else{
            header("location: ".SITE_URL."admin/all-bookings.php"); //Yes? then redirect
        }       
    }
    exit;



}

//Get all route tariff
$query = sprintf('SELECT * FROM %1$stbl_routes', DB_TBL_PREFIX); //Get required user information from DB


if($result = mysqli_query($GLOBALS['DB'], $query)){
    if(mysqli_num_rows($result)){
        while($row = mysqli_fetch_assoc($result)){
            $route_data[] = $row;
        }
                         
    }    
}


//Get all route tariff
$query = sprintf('SELECT *,%1$stbl_bookings.id AS booking_id,%1$stbl_bookings.route_id AS b_route_id,%1$stbl_users.firstname AS user_firstname, %1$stbl_users.lastname AS user_lastname, %1$stbl_users.phone AS user_phone, %1$stbl_bookings.ride_id AS bookride_id FROM %1$stbl_bookings
INNER JOIN %1$stbl_users ON %1$stbl_users.user_id = %1$stbl_bookings.user_id
LEFT JOIN %1$stbl_drivers ON %1$stbl_drivers.driver_id = %1$stbl_bookings.driver_id
LEFT JOIN %1$stbl_routes ON %1$stbl_routes.id = %1$stbl_bookings.route_id
WHERE %1$stbl_bookings.id = "%2$d"  ', DB_TBL_PREFIX,$id); //Get required user information from DB


if($result = mysqli_query($GLOBALS['DB'], $query)){
    if(mysqli_num_rows($result)){
        
            $booking_data = mysqli_fetch_assoc($result);
        
                         
    }    
}


/* var_dump($booking_data);
exit; */


//get all drivers

$query = sprintf('SELECT * FROM %1$stbl_drivers ORDER BY firstname ASC', DB_TBL_PREFIX);


if($result = mysqli_query($GLOBALS['DB'], $query)){
  
    if(mysqli_num_rows($result)){
        while($row = mysqli_fetch_assoc($result)){
            $drivers_data[] = $row;
        }
    
     }
    mysqli_free_result($result);
}   





if(empty($_POST)){ //let's render the add-new-ride page UI'
    
    ob_start();
    include('../../drop-files/templates/admin/editbookingtpl.php');
    $GLOBALS['admin_template']['page_content'] = ob_get_clean();
    include "../../drop-files/templates/admin/admin-interface.php";
    exit;


}

/* var_dump($_POST);
exit; */
if(DEMO){
    $_SESSION['action_error'][] = "You are running in Demo mode. Action cannot be completed";
    header("location: ".SITE_URL."admin/all-bookings.php"); //Yes? then redirect 
    exit;
}


if($_POST['zonetypeoption'] == "city" && (empty($_POST['pcity-zone-long']) || empty($_POST['pcity-zone-lat']) || empty($_POST['dcity-zone-lat']) || empty($_POST['dcity-zone-lat']))){

    $_SESSION['action_error'][]    = "Coordinates of the selected route was not found. Please use google map suggestions";
    
}


if($_POST['zonetypeoption'] == "state" && (empty($_POST['pcz-long']) || empty($_POST['pcz-lat']) || empty($_POST['dcz-long']) || empty($_POST['dcz-lat']))){

    $_SESSION['action_error'][]    = "Coordinates of the selected route was not found. Please use google map suggestions";
    
}

if($_POST['zonetypeoption'] == "city"){

    if(empty($_POST['cbooking-price'])){
        $_SESSION['action_error'][]    = "Please enter a price for this booking";
    
    }

    if(empty($_POST['pcity-zone']) || empty($_POST['dcity-zone'])){
        $_SESSION['action_error'][]    = "Pick-up and Drop-off locations must be set";
    
    }


}else{

    if(empty($_POST['booking-price'])){
        $_SESSION['action_error'][]    = "Please enter a price for this booking";
    
    }
    
    
    if(empty($_POST['booking-customerid']) || empty($_POST['booking-customer'])){
        $_SESSION['action_error'][]    = "Please select a customer from the autocomplete popup menu";
    
    }
    
    
    
    



}




if(!empty($_SESSION['action_error'])){
   
    ob_start();
            include('../../drop-files/templates/admin/editbookingtpl.php'); 
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







$paddr = '';
$daddr = '';
$plng = '';
$plat = '';
$dlng = '';
$dlat = '';
$bprice = '';
$route_id = 0;
$ride_id = 0;
$customer_id = 0;
$driver_id = 0;
$datetime = '';
$payment_type = 0;


if($_POST['zonetypeoption'] == "city"){
    $paddr = $_POST['pcity-zone'];
    $daddr = $_POST['dcity-zone'];
    $plng = $_POST['pcity-zone-long'];
    $plat = $_POST['pcity-zone-lat'];
    $dlng = $_POST['dcity-zone-long'];
    $dlat = $_POST['pcity-zone-lat'];
    $bprice = $_POST['cbooking-price'];
    $route_id = $_POST['route-city'];
    $ride_id = $_POST['ride-type'];
    $customer_id = $_POST['booking-customerid'];
    $driver_id = !empty($_POST['booking-driverid']) ? $_POST['booking-driverid'] : 0 ;
    $datetime = date('Y-m-d H:i:s',strtotime($_POST['date'] . " " . $_POST['time']));
    $payment_type = $_POST['booking-paymethod'];

}else{
    $paddr = $_POST['pcz'];
    $daddr = $_POST['dcz'];
    $plng = $_POST['pcz-long'];
    $plat = $_POST['pcz-lat'];
    $dlng = $_POST['dcz-long'];
    $dlat = $_POST['dcz-lat'];
    $bprice = $_POST['booking-price'];
    $route_id = $_POST['route-state'];
    $ride_id = $_POST['sride-type'];
    $customer_id = $_POST['booking-customerid'];
    $driver_id = !empty($_POST['booking-driverid']) ? $_POST['booking-driverid'] : 0 ;
    $datetime = date('Y-m-d H:i:s',strtotime($_POST['date'] . " " . $_POST['time']));
    $payment_type = $_POST['booking-paymethod'];
}



$query = sprintf('UPDATE %stbl_bookings SET user_id = "%d", pickup_datetime = "%s", pickup_address = "%s", pickup_long  = "%s", pickup_lat = "%s", dropoff_address = "%s", dropoff_long = "%s",dropoff_lat = "%s",estimated_cost = "%s",route_id = "%s",ride_id = "%s",payment_type = "%d",date_created = "%s",driver_id = "%d" WHERE id = "%d"', 
DB_TBL_PREFIX, 
$customer_id,
$datetime,
$paddr,
$plng,
$plat,
$daddr,
$dlng,
$dlat,
$bprice,
$route_id,
$ride_id,
$payment_type,
gmdate('Y-m-d H:i:s', time()),
$driver_id,
$id
);


if(! $result = mysqli_query($GLOBALS['DB'], $query)){
    
    $_SESSION['action_error'][]    = "Error modifying new booking in database";
    
}





/* echo mysqli_error($GLOBALS['DB']);
exit; */

if(!empty($_SESSION['action_error'])){
   
    ob_start();
            include('../../drop-files/templates/admin/editbookingtpl.php'); 
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


        $_SESSION['reload_id'] = $id;
        $_SESSION['action_success'][] = "The booking was updated successfully.";
        if($booking_data['scheduled'] == 1){
            header("location: ".SITE_URL."admin/all-sbookings.php"); //Yes? then redirect
        }else{
            header("location: ".SITE_URL."admin/all-bookings.php"); //Yes? then redirect
        }
        exit;






?>