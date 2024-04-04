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

$GLOBALS['admin_template']['page_title'] = "<i class='fa fa-recycle'></i> Manage Drivers Referral"; //Set the title of the page on the admin interface
$GLOBALS['admin_template']['active_menu'] = "referrals"; //Set the appropriate menu item active
$GLOBALS['admin_template']['active_sub_menu'] = "referral-drivers"; //Set the appropriate menu item active



//get referral data items from DB
$referral_drivers_data = [];
$route_ids = [];

$query = sprintf('SELECT *,%1$stbl_referral_drivers.id AS ref_id FROM %1$stbl_referral_drivers
INNER JOIN %1$stbl_routes ON %1$stbl_routes.id = %1$stbl_referral_drivers.route_id
INNER JOIN %1$stbl_currencies ON %1$stbl_currencies.id = %1$stbl_routes.city_currency_id
WHERE %1$stbl_routes.r_scope = 0 ORDER BY %1$stbl_referral_drivers.id ASC', DB_TBL_PREFIX);


if($result = mysqli_query($GLOBALS['DB'], $query)){
  
    if(mysqli_num_rows($result)){
        while($row = mysqli_fetch_assoc($result)){
            $referral_drivers_data[] = $row;
            $route_ids[$row['route_id']] = 1;
        }  
       
     }
    mysqli_free_result($result);
}


//get all intra-city routes
$city_currency_data = [];
$city_currency_data_all = [];
$query = sprintf('SELECT *,%1$stbl_routes.id AS city_id FROM %1$stbl_routes
INNER JOIN %1$stbl_currencies ON %1$stbl_currencies.id = %1$stbl_routes.city_currency_id
WHERE %1$stbl_routes.r_scope = 0 ORDER BY %1$stbl_routes.r_title ASC', DB_TBL_PREFIX);


if($result = mysqli_query($GLOBALS['DB'], $query)){
  
    if(mysqli_num_rows($result)){
        while($row = mysqli_fetch_assoc($result)){

            $city_currency_data[] = $row;
            $city_currency_data_all[] = $row;
        }
    
     }
    mysqli_free_result($result);
}




//remove cities with already set referrals
foreach($city_currency_data as $key=>$value){
    if(!empty($route_ids[$value['city_id']]))unset($city_currency_data[$key]);
}



if(isset($_GET['action']) && $_GET['action'] == "del"){ //delete referral

    if(DEMO){
    
        $_SESSION['action_error'][] = "You are running in Demo mode. Action cannot be completed";
        
        ob_start();
        include('../../drop-files/templates/admin/referraldriverstpl.php'); 
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

    $rid = empty($_GET['id']) ? 0 : (int) $_GET['id'];
    
    //delete referral database record
    $query = sprintf('DELETE FROM %stbl_referral_drivers WHERE id=%d', 
        DB_TBL_PREFIX, 
        $rid
    );

    

    if(! $result = mysqli_query($GLOBALS['DB'], $query)){
        //echo mysqli_error($GLOBALS['DB']);
        
        $_SESSION['action_error'][] = "An error has occured. Could not delete referral record. Ensure database connection is working";
    
        ob_start();
        include('../../drop-files/templates/admin/referraldriverstpl.php'); 
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



    //refresh referral data
        
    $referral_drivers_data = [];
    $route_ids = [];

    $query = sprintf('SELECT *,%1$stbl_referral_drivers.id AS ref_id FROM %1$stbl_referral_drivers
    INNER JOIN %1$stbl_routes ON %1$stbl_routes.id = %1$stbl_referral_drivers.route_id
    INNER JOIN %1$stbl_currencies ON %1$stbl_currencies.id = %1$stbl_routes.city_currency_id
    WHERE %1$stbl_routes.r_scope = 0 ORDER BY %1$stbl_referral_drivers.id ASC', DB_TBL_PREFIX);


    if($result = mysqli_query($GLOBALS['DB'], $query)){
    
        if(mysqli_num_rows($result)){
            while($row = mysqli_fetch_assoc($result)){
                $referral_drivers_data[] = $row;
                $route_ids[$row['route_id']] = 1;
            }  
        
        }
        mysqli_free_result($result);
    }


    //get all intra-city routes
    $city_currency_data = [];
    $city_currency_data_all = [];
    $query = sprintf('SELECT *,%1$stbl_routes.id AS city_id FROM %1$stbl_routes
    INNER JOIN %1$stbl_currencies ON %1$stbl_currencies.id = %1$stbl_routes.city_currency_id
    WHERE %1$stbl_routes.r_scope = 0 ORDER BY %1$stbl_routes.r_title ASC', DB_TBL_PREFIX);


    if($result = mysqli_query($GLOBALS['DB'], $query)){
    
        if(mysqli_num_rows($result)){
            while($row = mysqli_fetch_assoc($result)){

                $city_currency_data[] = $row;
                $city_currency_data_all[] = $row;
            }
        
        }
        mysqli_free_result($result);
    }




    //remove cities with already set referrals
    foreach($city_currency_data as $key=>$value){
        if(!empty($route_ids[$value['city_id']]))unset($city_currency_data[$key]);
    }       





    $_SESSION['action_success'][] = "Referral info was deleted successfully.";
    ob_start();
    include('../../drop-files/templates/admin/referraldriverstpl.php'); 
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

        


}


if(empty($_POST)){ 
    
    ob_start();
    include('../../drop-files/templates/admin/referraldriverstpl.php');
    $pageContent = ob_get_clean();
    $GLOBALS['admin_template']['page_content'] = $pageContent;
    include "../../drop-files/templates/admin/admin-interface.php";
    exit;


}


if(DEMO){
    
    $_SESSION['action_error'][] = "You are running in Demo mode. Action cannot be completed";
    
    ob_start();
    include('../../drop-files/templates/admin/referraldriverstpl.php'); 
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



if(isset($_POST['edit-ref'])){

    $id = !empty($_POST['e-referral-id']) ? (int) $_POST['e-referral-id'] : 0;
    $beneficiary = !empty($_POST['eref-benef']) ? (int) $_POST['eref-benef'] : 0;
    $route_id = !empty($_POST['ecity-list']) ? (int) $_POST['ecity-list'] : 0;
    $target_rides = !empty($_POST['etarget-rides']) ? (int) $_POST['etarget-rides'] : 0;
    $target_days = !empty($_POST['etarget-days']) ? (int) $_POST['etarget-days'] : 0;
    $inv_incentive = !empty($_POST['einvitee-incentive']) ? (float) $_POST['einvitee-incentive'] : 0.00;
    $drv_incentive = !empty($_POST['edriver-commission']) ? (float) $_POST['edriver-commission'] : 0.00;

    $status = isset($_POST['eref-status']) && $_POST['eref-status'] == '1'? 1 : 0;
    //$desc =  !empty($_POST['eref-desc']) ? htmlspecialchars(mysqli_real_escape_string($GLOBALS['DB'], $_POST['eref-desc'])) :"" ;

    
    $query = sprintf('UPDATE %stbl_referral_drivers SET beneficiary = %d,number_of_rides = %d,number_of_days = %d,invitee_incentive = "%s",driver_incentive = "%s",`status` = %d WHERE id = %d',
        DB_TBL_PREFIX,
        $beneficiary,
        $target_rides,
        $target_days,
        $inv_incentive,
        $drv_incentive,
        $status,
        $id    
    );

    

    if(! $result = mysqli_query($GLOBALS['DB'], $query)){
        //echo mysqli_error($GLOBALS['DB']);
        
        $_SESSION['action_error'][] = "An error has occured. Could not add new refferal info on database. Ensure database connection is working";
    
        ob_start();
        include('../../drop-files/templates/admin/referraldriverstpl.php'); 
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
    
        //refresh referral data
        
        $referral_drivers_data = [];
        $route_ids = [];

        $query = sprintf('SELECT *,%1$stbl_referral_drivers.id AS ref_id FROM %1$stbl_referral_drivers
        INNER JOIN %1$stbl_routes ON %1$stbl_routes.id = %1$stbl_referral_drivers.route_id
        INNER JOIN %1$stbl_currencies ON %1$stbl_currencies.id = %1$stbl_routes.city_currency_id
        WHERE %1$stbl_routes.r_scope = 0 ORDER BY %1$stbl_referral_drivers.id ASC', DB_TBL_PREFIX);


        if($result = mysqli_query($GLOBALS['DB'], $query)){
        
            if(mysqli_num_rows($result)){
                while($row = mysqli_fetch_assoc($result)){
                    $referral_drivers_data[] = $row;
                    $route_ids[$row['route_id']] = 1;
                }  
            
            }
            mysqli_free_result($result);
        }


        //get all intra-city routes
        $city_currency_data = [];
        $city_currency_data_all = [];
        $query = sprintf('SELECT *,%1$stbl_routes.id AS city_id FROM %1$stbl_routes
        INNER JOIN %1$stbl_currencies ON %1$stbl_currencies.id = %1$stbl_routes.city_currency_id
        WHERE %1$stbl_routes.r_scope = 0 ORDER BY %1$stbl_routes.r_title ASC', DB_TBL_PREFIX);


        if($result = mysqli_query($GLOBALS['DB'], $query)){
        
            if(mysqli_num_rows($result)){
                while($row = mysqli_fetch_assoc($result)){

                    $city_currency_data[] = $row;
                    $city_currency_data_all[] = $row;
                }
            
            }
            mysqli_free_result($result);
        }




        //remove cities with already set referrals
        foreach($city_currency_data as $key=>$value){
            if(!empty($route_ids[$value['city_id']]))unset($city_currency_data[$key]);
        }       
    
    
    
    
    
        $_SESSION['action_success'][] = "Referral info was updated successfully.";
        ob_start();
        include('../../drop-files/templates/admin/referraldriverstpl.php'); 
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






}





/* var_dump($_POST);
exit; */

$beneficiary = !empty($_POST['ref-benef']) ? (int) $_POST['ref-benef'] : 0;
$route_id = !empty($_POST['city-list']) ? (int) $_POST['city-list'] : 0;
$target_rides = !empty($_POST['target-rides']) ? (int) $_POST['target-rides'] : 0;
$target_days = !empty($_POST['target-days']) ? (int) $_POST['target-days'] : 0;
$inv_incentive = !empty($_POST['invitee-incentive']) ? (float) $_POST['invitee-incentive'] : 0.00;
$drv_incentive = !empty($_POST['driver-commission']) ? (float) $_POST['driver-commission'] : 0.00;

$status = isset($_POST['ref-status']) && $_POST['ref-status'] == '1'? 1 : 0;
//$desc =  !empty($_POST['ref-desc']) ? htmlspecialchars(mysqli_real_escape_string($GLOBALS['DB'], $_POST['ref-desc'])) :"" ;







$query = sprintf('INSERT INTO %stbl_referral_drivers (beneficiary,route_id,number_of_rides,number_of_days,invitee_incentive,driver_incentive,`status`) VALUES (%d,%d,%d,%d,"%s","%s",%d)',
    DB_TBL_PREFIX,
    $beneficiary,
    $route_id,
    $target_rides,
    $target_days,
    $inv_incentive,
    $drv_incentive,
    $status  

);




if(! $result = mysqli_query($GLOBALS['DB'], $query)){
    //echo mysqli_error($GLOBALS['DB']);
    
    $_SESSION['action_error'][] = "An error has occured. Could not add new refferal info on database. Ensure database connection is working";

    ob_start();
    include('../../drop-files/templates/admin/referraldriverstpl.php'); 
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

    //refresh referral data
            
    $referral_drivers_data = [];
    $route_ids = [];

    $query = sprintf('SELECT *,%1$stbl_referral_drivers.id AS ref_id FROM %1$stbl_referral_drivers
    INNER JOIN %1$stbl_routes ON %1$stbl_routes.id = %1$stbl_referral_drivers.route_id
    INNER JOIN %1$stbl_currencies ON %1$stbl_currencies.id = %1$stbl_routes.city_currency_id
    WHERE %1$stbl_routes.r_scope = 0 ORDER BY %1$stbl_referral_drivers.id ASC', DB_TBL_PREFIX);


    if($result = mysqli_query($GLOBALS['DB'], $query)){
    
        if(mysqli_num_rows($result)){
            while($row = mysqli_fetch_assoc($result)){
                $referral_drivers_data[] = $row;
                $route_ids[$row['route_id']] = 1;
            }  
        
        }
        mysqli_free_result($result);
    }


    //get all intra-city routes
    $city_currency_data = [];
    $city_currency_data_all = [];
    $query = sprintf('SELECT *,%1$stbl_routes.id AS city_id FROM %1$stbl_routes
    INNER JOIN %1$stbl_currencies ON %1$stbl_currencies.id = %1$stbl_routes.city_currency_id
    WHERE %1$stbl_routes.r_scope = 0 ORDER BY %1$stbl_routes.r_title ASC', DB_TBL_PREFIX);


    if($result = mysqli_query($GLOBALS['DB'], $query)){
    
        if(mysqli_num_rows($result)){
            while($row = mysqli_fetch_assoc($result)){

                $city_currency_data[] = $row;
                $city_currency_data_all[] = $row;
            }
        
        }
        mysqli_free_result($result);
    }




    //remove cities with already set referrals
    foreach($city_currency_data as $key=>$value){
        if(!empty($route_ids[$value['city_id']]))unset($city_currency_data[$key]);
    }





    $_SESSION['action_success'][] = "Referral info was added successfully.";
    ob_start();
    include('../../drop-files/templates/admin/referraldriverstpl.php'); 
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