<?php
session_start();
include("../../drop-files/lib/common.php");
include "../../drop-files/config/db.php";

$currency_page_items = [];
$rides_array = array();
$id = 0;

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

$GLOBALS['admin_template']['page_title'] = "<i class='fa fa-tags'></i> New Tariff"; //Set the title of the page on the admin interface
$GLOBALS['admin_template']['active_menu'] = "tariffs"; //Set the appropriate menu item active
$GLOBALS['admin_template']['active_sub_menu'] = "tariffs-new"; //Set the appropriate menu item active





//Get all ride types

$query = sprintf("SELECT * FROM %stbl_rides WHERE avail = 1 ORDER BY id ASC", DB_TBL_PREFIX);


if($result = mysqli_query($GLOBALS['DB'], $query)){
    if(mysqli_num_rows($result)){
        while($row = mysqli_fetch_assoc($result)){
            $rides_array[] = $row;
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
        }
    
     }
    mysqli_free_result($result);
}



if(empty($_POST)){ //let's render the add-new-ride page UI'
    
    ob_start();
    include('../../drop-files/templates/admin/addtarifftpl.php');
    $GLOBALS['admin_template']['page_content'] = ob_get_clean();
    include "../../drop-files/templates/admin/admin-interface.php";
    exit;


}


if(DEMO){
    $_SESSION['action_error'][] = "You are running in Demo mode. Action cannot be completed";
    header("location: ".SITE_URL."admin/all-tariffs.php"); //Yes? then redirect 
    exit;
}





if($_POST['zonetypeoption'] == "city" && (empty($_POST['city-zone-long']) || empty($_POST['city-zone-lat']))){

    $_SESSION['action_error'][]    = "Coordinates of the selected route was not found. Please use google map suggestions";
    
}


if($_POST['zonetypeoption'] == "state" && (empty($_POST['pcz-long']) || empty($_POST['pcz-lat']) || empty($_POST['dcz-long']) || empty($_POST['dcz-lat']))){

    $_SESSION['action_error'][]    = "Coordinates of the selected route was not found. Please use google map suggestions";
    
}


if(empty($_POST['route-title'])){

    $_SESSION['action_error'][]    = "Please enter a title for this tariff";
}


if(empty($_POST['car_type'])){

    $_SESSION['action_error'][]    = "Please select atleast one ride type";

}






//check if tariff name already exists

$query = sprintf('SELECT r_title FROM %stbl_routes WHERE r_title = "%s"', DB_TBL_PREFIX, mysqli_real_escape_string($GLOBALS['DB'], $_POST['route-title'])); //Get required user information from DB


if($result = mysqli_query($GLOBALS['DB'], $query)){
    if(mysqli_num_rows($result)){
        $_SESSION['action_error'][]    = "Tariff title already exists. Please use another title";

    }
    
}
else{ //No record matching the USER ID was found in DB. Show view to notify user

    $_SESSION['action_error'][]    = "Database error!";
}



/* echo mysqli_error($GLOBALS['DB']);
exit; */

if(!empty($_SESSION['action_error'])){
   
    ob_start();
            include('../../drop-files/templates/admin/addtarifftpl.php'); 
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




//for good. let's process data



$route_title = mysqli_real_escape_string($GLOBALS['DB'], $_POST['route-title']);
$city_name = $_POST['zonetypeoption'] == "city" ? mysqli_real_escape_string($GLOBALS['DB'], $_POST['city-zone']) : NULL;
$pick_name = $_POST['zonetypeoption'] == "state" ? mysqli_real_escape_string($GLOBALS['DB'], $_POST['pcz']) : NULL;
$drop_name = $_POST['zonetypeoption'] == "state" ? mysqli_real_escape_string($GLOBALS['DB'], $_POST['dcz']) : NULL;
$city_long = mysqli_real_escape_string($GLOBALS['DB'], $_POST['city-zone-long']);
$city_lat = mysqli_real_escape_string($GLOBALS['DB'], $_POST['city-zone-lat']);
$pick_long = mysqli_real_escape_string($GLOBALS['DB'], $_POST['pcz-long']);
$drop_long = mysqli_real_escape_string($GLOBALS['DB'], $_POST['dcz-long']);
$pick_lat = mysqli_real_escape_string($GLOBALS['DB'], $_POST['pcz-lat']);
$drop_lat = mysqli_real_escape_string($GLOBALS['DB'], $_POST['dcz-lat']);
$r_scope = $_POST['zonetypeoption'] == "city" ? 0 : 1;
$city_radius = isset($_POST['city-radius']) ? (float) $_POST['city-radius'] : 0;
$dist_unit = (int) $_POST['route-dist-unit'];
$currency = (int) $_POST['route-currency'];



//Store ride data to database
$query = sprintf('INSERT INTO %stbl_routes(r_title,c_name,pick_name,drop_name,r_scope,lng,lat,pick_lng,pick_lat,drop_lng,drop_lat,dist_unit,city_radius,city_currency_id) VALUES'.
'("%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%d","%s","%d")', 
DB_TBL_PREFIX,
$route_title,
$city_name,
$pick_name,
$drop_name,
$r_scope,
$city_long,
$city_lat, 
$pick_long,
$pick_lat,
$drop_long, 
$drop_lat,
$dist_unit,
$city_radius,
$currency  

);


    if(! $result = mysqli_query($GLOBALS['DB'], $query)){
        //echo mysqli_error($GLOBALS['DB']);
        
        $_SESSION['action_error'][] = "An error has occured. Could not save new tariff data to database. Ensure database connection is working";
    
        ob_start(); 
            include('../../drop-files/templates/admin/addtarifftpl.php');
            if(!empty($_SESSION['action_error'])){
                $msgs = '';
                foreach($_SESSION['action_error'] as $action_error){
                    $msgs .= $action_error . "<br><br>";
                }

                $cache_prevent = RAND();
                echo"<script>
            setTimeout(function(){ 
                    jQuery( function(){
                    swal({
                        title: '<h1>Error</h1>',
            text: '{$msgs}',".
            "imageUrl: '../img/info_.gif?a=" . $cache_prevent . "',
            html:true,
                    });
                    });
                    },500); 
                    
                    </script>";
            
                    unset($_SESSION['action_error']);
            
            } 


        $pageContent = ob_get_clean();
        $GLOBALS['admin_template']['page_content'] = $pageContent;
        include "../../drop-files/templates/admin/admin-interface.php";
        exit;

       
    }
    else{
            $id = mysqli_insert_id ( $GLOBALS['DB'] );
            
        }


        //successfully saved route data. lets save ride and tariff
        $DB_values_string = "";
        foreach($_POST['car_type'] as $cartype_id){
            $fare_compute = isset($_POST['faretype-' . $cartype_id]) ? 1 : 0;
            $ride_share = isset($_POST['rideshare-' . $cartype_id]) ? 1 : 0;
            $pcr = (float) $_POST['pcr-' . $cartype_id];
            $dcr = (float) $_POST['dcr-' . $cartype_id];
            $cpkr = (float) $_POST['cpkr-' . $cartype_id];
            $cpmr = (float) $_POST['cpmr-' . $cartype_id];
            $cc = (float) $_POST['cc-' . $cartype_id];

            $npcr = (float) $_POST['npcr-' . $cartype_id];
            $ndcr = (float) $_POST['ndcr-' . $cartype_id];
            $ncpkr = (float) $_POST['ncpkr-' . $cartype_id];
            $ncpmr = (float) $_POST['ncpmr-' . $cartype_id];
            $ncc = (float) $_POST['ncc-' . $cartype_id];

            $pp_enabled = isset($_POST['pp-enable-' . $cartype_id]) ? 1 : 0;
            $pp_start_time = isset($_POST['ppst-' . $cartype_id]) ? (int) $_POST['ppst-' . $cartype_id] : 12;
            $pp_end_time = isset($_POST['ppet-' . $cartype_id]) ? (int) $_POST['ppet-' . $cartype_id] : 17;
            $pp_active_days = !empty($_POST['ppad-' . $cartype_id]) && $pp_enabled ? json_encode($_POST['ppad-' . $cartype_id]) : " ";
            $pp_active_days = !empty($pp_active_days) ? "'" . mysqli_real_escape_string($GLOBALS['DB'], $pp_active_days) . "'" : " ";

            $pp_charge_type = !empty($_POST['ppct-' . $cartype_id]) && $pp_enabled  ? 1 : 0;
            $pp_charge = isset($_POST['ppchrge-' . $cartype_id]) && $pp_enabled ? (float) $_POST['ppchrge-' . $cartype_id] : 0;


            $DB_values_string .= "({$cartype_id},%2\$d,{$cc},{$cpkr},{$cpmr},{$pcr},{$dcr},{$ncc},{$ncpkr},{$ncpmr},{$npcr},{$ndcr},{$pp_enabled},{$pp_start_time},{$pp_end_time},{$pp_active_days},{$pp_charge_type},{$pp_charge},{$fare_compute},{$ride_share}),";


        }


        $len = strlen($DB_values_string); 
        if(substr($DB_values_string,$len - 1, 1) == ','){
            $DB_values_string = substr($DB_values_string,0, $len - 1); //remove trailing comma if present                
        }  
                

        //Store generated keys in DB
        $query = sprintf('INSERT INTO %1$stbl_rides_tariffs (ride_id,routes_id,cancel_cost,cost_per_km,cost_per_minute,pickup_cost,drop_off_cost,ncancel_cost,ncost_per_km,ncost_per_minute,npickup_cost,ndrop_off_cost,pp_enabled,pp_start,pp_end,pp_active_days,pp_charge_type,pp_charge_value,cfare_enabled,rshare_enabled) VALUES '.
        $DB_values_string, 
        DB_TBL_PREFIX, 
        $id   
        );

        

        
        if(!$result = mysqli_query($GLOBALS['DB'], $query)){

            echo mysqli_error($GLOBALS['DB']);
            
            $_SESSION['action_error'][]    = "Error creating new tariff";
            ob_start(); 
            include('../../drop-files/templates/admin/addtarifftpl.php');
            if(!empty($_SESSION['action_error'])){
                $msgs = '';
                foreach($_SESSION['action_error'] as $action_error){
                    $msgs .= $action_error . "<br><br>";
                }

                $cache_prevent = RAND();
                echo"<script>
            setTimeout(function(){ 
                    jQuery( function(){
                    swal({
                        title: '<h1>Error</h1>',
            text: '{$msgs}',".
            "imageUrl: '../img/info_.gif?a=" . $cache_prevent . "',
            html:true,
                    });
                    });
                    },500); 
                    
                    </script>";
            
                    unset($_SESSION['action_error']);
            
            } 


        $pageContent = ob_get_clean();
        $GLOBALS['admin_template']['page_content'] = $pageContent;
        include "../../drop-files/templates/admin/admin-interface.php";
        exit;
            
            
        }
        
        
        
        
   
        $_SESSION['action_success'][] = "The tariff was created successfully.";
        header("location: ".SITE_URL."admin/all-tariffs.php"); //Yes? then redirect
        exit;






?>