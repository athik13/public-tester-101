<?php
session_start();
include("../../drop-files/lib/common.php");
include "../../drop-files/config/db.php";

$id = 0;
$tariff_data = [];
$rides_tariff_data = [];
$rides_data = [];
$rides_array = [];
$currency_page_items=[];


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

$GLOBALS['admin_template']['page_title'] = "<i class='fa fa-tags'></i> Edit Tariff"; //Set the title of the page on the admin interface
$GLOBALS['admin_template']['active_menu'] = "tariffs"; //Set the appropriate menu item active
$GLOBALS['admin_template']['active_sub_menu'] = "tariffs-all"; //Set the appropriate menu item active


if(!empty($_POST['route-id'])){
    $id = (int) $_POST['route-id'];
 }
elseif(!empty($_GET['id'])) {
        $id = (int) $_GET['id'] ;
 }




$query = sprintf('SELECT * FROM %stbl_routes WHERE id = "%d"', DB_TBL_PREFIX, $id); //Get required user information from DB


if($result = mysqli_query($GLOBALS['DB'], $query)){
    if(mysqli_num_rows($result)){
        $tariff_data = mysqli_fetch_assoc($result);
                
    }else{
        $_SESSION['action_error'][]    = "Invalid Tariff record.";
    }
    
}
else{ //No record matching the USER ID was found in DB. Show view to notify user

    $_SESSION['action_error'][]    = "Database error!";
}




//Get all ride types

$query = sprintf("SELECT * FROM %stbl_rides ORDER BY id ASC", DB_TBL_PREFIX);


if($result = mysqli_query($GLOBALS['DB'], $query)){
    if(mysqli_num_rows($result)){
        while($row = mysqli_fetch_assoc($result)){
            $rides_array[] = $row;
        }
    
     }
    mysqli_free_result($result);

}


//get all rides tariffs
$query = sprintf('SELECT * FROM %1$stbl_rides_tariffs WHERE %1$stbl_rides_tariffs.routes_id = %2$d',DB_TBL_PREFIX, $id);


if($result = mysqli_query($GLOBALS['DB'], $query)){
  
    if(mysqli_num_rows($result)){
        while($row = mysqli_fetch_assoc($result)){
            $rides_tariff_data[] = $row;
        }
    
     }
    mysqli_free_result($result);
}  




//sort rides tarif data
foreach($rides_tariff_data as $ridestariffdata){
    $rides_data[$ridestariffdata['ride_id']] = $ridestariffdata;
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

/* var_dump($rides_data);
exit; */




if(!empty($_SESSION['action_error'])){
   
    ob_start();
    include('../../drop-files/templates/admin/edittarifftpl.php'); 
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


//Process DELETE record command
if(isset($_GET['action']) && $_GET['action']== "delete"){

    if(DEMO){
        $_SESSION['action_error'][] = "You are running in Demo mode. Action cannot be completed";
        header("location: ".SITE_URL."admin/all-tariffs.php"); //Yes? then redirect 
        exit;
    }

    if($id == 1){
        $_SESSION['action_error'][] = "You cannot delete the default route. Edit the route instead.";
        header("location: ".SITE_URL."admin/all-tariffs.php"); //Yes? then redirect 
        exit;
    }

    //Ensure that data exists on DB
    $query = sprintf('SELECT id FROM %stbl_routes WHERE id = "%d"',DB_TBL_PREFIX, $id );
           if($result = mysqli_query($GLOBALS['DB'], $query)){
       
                if(!mysqli_num_rows($result)){
                        $_SESSION['action_error'][] = "Could not delete the requested record. The record was not found in the database";
                        header("location: ".SITE_URL."admin/all-tariffs.php"); //Yes? then redirect
                        exit;
                    
                }   
           mysqli_free_result($result);
       }  

       
   //then delete record
       $query = sprintf('DELETE FROM %stbl_routes WHERE id = "%d"', DB_TBL_PREFIX, $id); 
       if(!$result = mysqli_query($GLOBALS['DB'], $query)){ 
        $_SESSION['action_error'][] = "An error occured while trying to delete tariff record from the database.";
        header("location: ".SITE_URL."admin/all-tariffs.php"); //Yes? then redirect
        exit;
          
       }

       //remove route tariff vehicles
       $query = sprintf('DELETE FROM %stbl_rides_tariffs WHERE routes_id = "%d"', DB_TBL_PREFIX, $id);
       $result = mysqli_query($GLOBALS['DB'], $query);

       //remove route coupons
       $query = sprintf('DELETE FROM %stbl_coupon_codes WHERE city = "%d"', DB_TBL_PREFIX, $id);
       $result = mysqli_query($GLOBALS['DB'], $query);

       

       //switch drivers on this route to the default route
       $query = sprintf('UPDATE %stbl_drivers SET route_id = 1 WHERE route_id = %d', DB_TBL_PREFIX, $id);
       $query = sprintf('DELETE FROM %stbl_rides_tariffs WHERE routes_id = "%d"', DB_TBL_PREFIX, $id); 
       $result = mysqli_query($GLOBALS['DB'], $query);
       
       
        
        $_SESSION['action_success'][] = "The tariff record was successfully deleted.";
        header("location: ".SITE_URL."admin/all-tariffs.php"); //Yes? then redirect
        exit;



}


/* var_dump($tariff_data);
exit; */



if(empty($_POST)){ //let's render the edit tariff page UI'

ob_start();
include('../../drop-files/templates/admin/edittarifftpl.php');
$pageContent = ob_get_clean();
$GLOBALS['admin_template']['page_content'] = $pageContent;
include "../../drop-files/templates/admin/admin-interface.php";
exit;


}

/* var_dump($_POST);
exit; */

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


if($_POST['zonetypeoption'] == "state" && $id == 1){

    $_SESSION['action_error'][]    = "This is the default city route and cannot be set as an inter-state route";
    
}


if(empty($_POST['route-title'])){

    $_SESSION['action_error'][]    = "Please enter a title for this tariff";
}


if(empty($_POST['car_type'])){

    $_SESSION['action_error'][]    = "Please select atleast one ride type";

}





/* echo mysqli_error($GLOBALS['DB']);
exit; */

if(!empty($_SESSION['action_error'])){
   
    ob_start();
            include('../../drop-files/templates/admin/edittarifftpl.php'); 
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
$query = sprintf('UPDATE %stbl_routes SET r_title = "%s",c_name = "%s",pick_name = "%s",drop_name = "%s",r_scope = "%s",lng = "%s",lat = "%s",pick_lng = "%s",pick_lat = "%s",drop_lng = "%s",drop_lat = "%s",dist_unit = "%d",city_radius = "%s",city_currency_id = "%d" WHERE id="%d"', 
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
$currency,  
$id  

);

/* echo $query;
exit; */


    if(! $result = mysqli_query($GLOBALS['DB'], $query)){
        //echo mysqli_error($GLOBALS['DB']);
        
        $_SESSION['action_error'][] = "An error has occured. Could not save update tariff data on database. Ensure database connection is working";
    
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
                        title: '<h1>Success</h1>',
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
        
        
        //delete all previous ride record for this route
        $query = sprintf('DELETE FROM %stbl_rides_tariffs WHERE routes_id = "%d"', DB_TBL_PREFIX, $id); 
        $result = mysqli_query($GLOBALS['DB'], $query);

        //Store generated keys in DB
        $query = sprintf('INSERT INTO %1$stbl_rides_tariffs (ride_id,routes_id,cancel_cost,cost_per_km,cost_per_minute,pickup_cost,drop_off_cost,ncancel_cost,ncost_per_km,ncost_per_minute,npickup_cost,ndrop_off_cost,pp_enabled,pp_start,pp_end,pp_active_days,pp_charge_type,pp_charge_value,cfare_enabled,rshare_enabled) VALUES '.
        $DB_values_string, 
        DB_TBL_PREFIX, 
        $id   
        );

        
        
        if(!$result = mysqli_query($GLOBALS['DB'], $query)){
            //echo $query . "<br>";
            //echo mysqli_error($GLOBALS['DB']);
            $_SESSION['action_error'][]    = "Error updating tariff";
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
        
        
               
        
   
        $_SESSION['action_success'][] = "The tariff was updated successfully.";
        header("location: ".SITE_URL."admin/all-tariffs.php"); //Yes? then redirect
        exit;





?>