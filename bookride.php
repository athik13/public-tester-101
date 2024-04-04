<?php
session_start();
include("../drop-files/lib/common.php");
include "../drop-files/config/db.php";
$GLOBALS['admin_template']['active_menu'] = "main-book";
$GLOBALS['template']['page-heading'] = "Book A Ride";
$GLOBALS['template']['breadcrumbs'] = array("Home"=>"index.php","bookride" => "bookride.php");
$user_acc_details = array();
$tariff_data = [];
$route_data = [];
$rides_tariff_data = [];


if(empty($_SESSION['loggedin'])){ 
    
    header("location: ".SITE_URL."index.php"); //Yes? then redirect user to the home page
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



$query = sprintf('SELECT *,%1$stbl_routes.id AS route_id  FROM %1$stbl_routes
INNER JOIN %1$stbl_rides_tariffs ON %1$stbl_rides_tariffs.routes_id = %1$stbl_routes.id
INNER JOIN %1$stbl_rides ON %1$stbl_rides_tariffs.ride_id = %1$stbl_rides.id
WHERE %1$stbl_rides.avail = 1', DB_TBL_PREFIX);

if($result = mysqli_query($GLOBALS['DB'], $query)){
    if(mysqli_num_rows($result)){
        while($row = mysqli_fetch_assoc($result)){
            $tariff_data[] = $row;
        }
                         
    }    
}
 





//sort rides tarif data
foreach($tariff_data as $tariffdata){

    $rides_data[$tariffdata['route_id']]['r_id'] = $tariffdata['route_id'];
    $rides_data[$tariffdata['route_id']]['cars'][] = $tariffdata;
}



$bookride_token = !empty($_POST['b-token']) ? $_POST['b-token'] : 0;

if(!empty($_SESSION['booking'][$bookride_token])){

        
        include "../drop-files/templates/headertpl.php";
        include "../drop-files/templates/pageheadingtpl.php";
        include "../drop-files/templates/bookridesummarytpl.php";
        include "../drop-files/templates/footertpl.php";
        exit;




}
    



if(!(isset($_POST['bookride']))){ //Lets check if user has submitted the login form. No ? ok show login form.
    
    include "../drop-files/templates/headertpl.php";
    include "../drop-files/templates/pageheadingtpl.php";
    include "../drop-files/templates/bookridetpl.php";
    include "../drop-files/templates/footertpl.php";
    exit;

}








?>