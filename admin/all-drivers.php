<?php
session_start();
include("../../drop-files/lib/common.php");
include ("../../drop-files/config/db.php");
define('ITEMS_PER_PAGE', 50); //define constant for number of items to display per page

$number_of_drivers = 0;
$number_of_drivers_available = 0;
$drivers_page_items = array();
$query_modifier = '';

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


$GLOBALS['admin_template']['page_title'] = "<i class='fa fa-drivers-license'></i> All Drivers"; //Set the title of the page on the admin interface
$GLOBALS['admin_template']['active_menu'] = "drivers"; //Set the appropriate menu item active
$GLOBALS['admin_template']['active_sub_menu'] = "driver-all"; //Set the appropriate menu item active


if(isset($_GET['search-term'])){

    if(strlen($_GET['search-term']) > 15){
        $_SESSION['action_error'][] = "Search word is too long";
        header("location: ".SITE_URL."admin/all-drivers.php"); //Yes? then redirect
        exit;
    }

    $search_string = mysqli_real_escape_string($GLOBALS['DB'], $_GET['search-term']);

    if(is_numeric($_GET['search-term'])){      
      
      $query_modifier = ' > 0 AND '. DB_TBL_PREFIX .'tbl_drivers.phone LIKE "%' . $search_string . '%" ' ;

    }else{
     
      $query_modifier = ' > 0 AND ' . DB_TBL_PREFIX . 'tbl_drivers.firstname LIKE "%' . $search_string . '%" OR ' . DB_TBL_PREFIX .'tbl_drivers.lastname LIKE "%' . $search_string . '%" ' ;

    }

    
    $GLOBALS['admin_template']['page_title'] = "<i class='fa fa-drivers-license'></i> Driver Search - " . $search_string;
}else{

    $query_modifier = ' > 0  '; //display all pending user qualifications
    

}


$sort_by = DB_TBL_PREFIX . "tbl_drivers.account_create_date DESC";

if(isset($_GET['sort'])){
  if($_GET['sort'] == 1){
    $sort_by = DB_TBL_PREFIX . "tbl_drivers.firstname ASC";
  }elseif($_GET['sort'] == 2){
    $sort_by = DB_TBL_PREFIX . "tbl_drivers.account_create_date DESC";
  }elseif($_GET['sort'] == 3){
    $sort_by = DB_TBL_PREFIX . "tbl_drivers.is_activated ASC, " . DB_TBL_PREFIX . "tbl_drivers.account_create_date DESC";
    
  }elseif($_GET['sort'] == 4){
    $sort_by = DB_TBL_PREFIX . "tbl_drivers.available DESC";    

  }elseif($_GET['sort'] == 5){
    $sort_by = DB_TBL_PREFIX . "tbl_routes.r_title ASC";    

  }else{
    $sort_by = DB_TBL_PREFIX . "tbl_users.firstname ASC";
  }

}


//Get number of drivers online
$query = sprintf('SELECT COUNT(IF(%1$stbl_drivers.available AND %1$stbl_drivers.is_activated AND (UNIX_TIMESTAMP(%1$stbl_driver_location.location_date) > UNIX_TIMESTAMP() - %3$d),1,NULL)) AS drivers_available FROM %1$stbl_drivers 
LEFT JOIN %1$stbl_driver_location ON %1$stbl_driver_location.driver_id = %1$stbl_drivers.driver_id
WHERE %1$stbl_drivers.driver_id %2$s', DB_TBL_PREFIX,$query_modifier, LOCATION_INFO_VALID_AGE);  //Get and count all data


/* echo mysqli_error($GLOBALS['DB']); */

if($result = mysqli_query($GLOBALS['DB'], $query)){
    if(mysqli_num_rows($result)){

        $row = mysqli_fetch_assoc($result);
          
        
        $number_of_drivers_available = $row['drivers_available'];
         
     }
    mysqli_free_result($result);
}



//Get number of drivers in datatbase
$query = sprintf('SELECT COUNT(*) FROM %1$stbl_drivers 
LEFT JOIN %1$stbl_driver_location ON %1$stbl_driver_location.driver_id = %1$stbl_drivers.driver_id
WHERE %1$stbl_drivers.driver_id %2$s', DB_TBL_PREFIX,$query_modifier, LOCATION_INFO_VALID_AGE);  //Get and count all data


/* echo mysqli_error($GLOBALS['DB']); */

if($result = mysqli_query($GLOBALS['DB'], $query)){
    if(mysqli_num_rows($result)){

        $row = mysqli_fetch_assoc($result);
          
        $number_of_drivers = $row['COUNT(*)'];
        
         
     }
    mysqli_free_result($result);
}   




if(isset($_GET['page'])){
  $page_number = (int) $_GET['page'];
}else{
    $page_number = 1;
  }
  
  $pages = ceil($number_of_drivers / ITEMS_PER_PAGE) ;
  if($page_number > $pages)$page_number = 1; 
  if($page_number < 0)$page_number = 1; 
  $offset = ($page_number - 1) * ITEMS_PER_PAGE;


$booked_drivers_data = [];

//get drivers who are currently onride or allocated to bookings
$query = sprintf('SELECT %1$stbl_bookings.id AS booking_id,%1$stbl_bookings.driver_id AS booking_driver, %1$stbl_driver_allocate.driver_id AS booking_driver_alloc, %1$stbl_driver_allocate.status AS booking_driver_alloc_status  FROM %1$stbl_bookings
INNER JOIN %1$stbl_drivers ON %1$stbl_drivers.driver_id = %1$stbl_bookings.driver_id
LEFT JOIN %1$stbl_driver_allocate ON %1$stbl_driver_allocate.booking_id = %1$stbl_bookings.id
WHERE (%1$stbl_bookings.status = 0 OR %1$stbl_bookings.status = 1)', DB_TBL_PREFIX);

if($result = mysqli_query($GLOBALS['DB'], $query)){
  
    if(mysqli_num_rows($result)){
        while($row = mysqli_fetch_assoc($result)){
            if(!empty($row['booking_driver'])){
                $booked_drivers_data[$row['booking_driver']] = array('driver_id' => $row['booking_driver'], 'status' => "Servicing booking <a href='view-booking.php?bkid={$row['booking_id']}'>#{$row['booking_id']}</a>");
            }
            if(!empty($row['booking_driver_alloc'] && $row['booking_driver_alloc_status'] == 0 )){
                $booked_drivers_data[$row['booking_driver_alloc']] = array('driver_id' => $row['booking_driver_alloc'], 'status' => "Allocated to booking <a href='view-booking.php?bkid={$row['booking_id']}'>#{$row['booking_id']}</a>");
            }
            
        }
    
      }
    mysqli_free_result($result);
}



$query = sprintf('SELECT *, %1$stbl_driver_location.location_date, %1$stbl_driver_location.long AS drvlong, %1$stbl_driver_location.lat AS drvlat, %1$stbl_drivers.driver_id AS driver_ids FROM %1$stbl_drivers 
LEFT JOIN %1$stbl_account_codes ON %1$stbl_account_codes.user_id = %1$stbl_drivers.driver_id AND %1$stbl_account_codes.user_type = 1 AND %1$stbl_account_codes.context = 0
LEFT JOIN %1$stbl_driver_location ON %1$stbl_driver_location.driver_id = %1$stbl_drivers.driver_id
LEFT JOIN %1$stbl_routes ON %1$stbl_routes.id = %1$stbl_drivers.route_id
WHERE %1$stbl_drivers.driver_id %4$s ORDER BY %5$s LIMIT %2$d,%3$d', DB_TBL_PREFIX, $offset, ITEMS_PER_PAGE, $query_modifier, $sort_by);


if($result = mysqli_query($GLOBALS['DB'], $query)){
  
    if(mysqli_num_rows($result)){
        while($row = mysqli_fetch_assoc($result)){
            $drivers_page_items[] = $row;
        }
    
     }
    mysqli_free_result($result);
}






/* var_dump($drivers_page_items);
exit; */

ob_start();
include "../../drop-files/templates/admin/alldriverstpl.php";

if(!empty($_SESSION['action_success'])){
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

}elseif(!empty($_SESSION['action_error'])){
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
    
}


$GLOBALS['admin_template']['page_content'] = ob_get_clean();
include "../../drop-files/templates/admin/admin-interface.php";
exit;





























?>