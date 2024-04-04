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

if($_SESSION['account_type'] /* != 2 && $_SESSION['account_type'] */ != 3){ ////if user is not an admin
    $_SESSION['action_error'][] = "Access Denied!";
    header("location: ".SITE_URL."admin/index.php"); //Yes? then redirect user to the login page
    exit;
}

$GLOBALS['admin_template']['page_title'] = "<i class='fa fa-gears'></i> Settings"; //Set the title of the page on the admin interface
$GLOBALS['admin_template']['active_menu'] = "settings"; //Set the appropriate menu item active
//$GLOBALS['admin_template']['active_sub_menu'] = "franch-new"; //Set the appropriate menu item active


/* $settings_data = [];
$settings_data2 = [];
$settings_data3 = [];
require(realpath("../../drop-files/lib/") . "/" . "settingsdata.php");
require(realpath("../../drop-files/lib/") . "/" . "settingsdata2.php");
require(realpath("../../drop-files/lib/") . "/" . "settingsdata3.php"); */

$number_of_currencies = 0;
$currency_page_items = array();
$currency_list_items = array();
$active_tab = 0;


//Get number of currencies in datatbase
$query = sprintf('SELECT COUNT(*) FROM %stbl_currencies', DB_TBL_PREFIX);  //Get and count all data

//echo mysqli_error($GLOBALS['DB']);

if($result = mysqli_query($GLOBALS['DB'], $query)){
    if(mysqli_num_rows($result)){

        $row = mysqli_fetch_assoc($result);          
        $number_of_currencies = $row['COUNT(*)'];
         
     }
    mysqli_free_result($result);
}  

//Get currency data from DB
$query = sprintf('SELECT * FROM %stbl_currencies ORDER BY `name` DESC', DB_TBL_PREFIX);


if($result = mysqli_query($GLOBALS['DB'], $query)){
  
    if(mysqli_num_rows($result)){
        while($row = mysqli_fetch_assoc($result)){
            $currency_page_items[] = $row;
        }
    
     }
    mysqli_free_result($result);
}

$selected_currency_array =[];

$selected_currency_array = array_column($currency_page_items,'iso_code');

$selected_currency_string = "";


if(!empty($selected_currency_array)){
    $selected_currency_array = array_map(function($value){return "'" . $value . "'";},$selected_currency_array);
    $selected_currency_string = implode(",",$selected_currency_array);
    $selected_currency_string = 'NOT IN ('.$selected_currency_string.')' ;
}else{
    $selected_currency_string = "IS NOT NULL";
}






//Get currency list items
$query = sprintf('SELECT * FROM %stbl_currency_list WHERE code %s ORDER BY `name` ASC', DB_TBL_PREFIX,$selected_currency_string);


if($result = mysqli_query($GLOBALS['DB'], $query)){
  
    if(mysqli_num_rows($result)){
        while($row = mysqli_fetch_assoc($result)){
            $currency_list_items[] = $row;
        }    
    }

    mysqli_free_result($result);
}  




if(isset($_POST['savesettings'])){
    $active_tab = 0;
    //var_dump($_POST);

    if(DEMO){
    
        $_SESSION['action_error'][] = "You are running in Demo mode. Action cannot be completed";
        
        ob_start();
        include('../../drop-files/templates/admin/settingstpl.php'); 
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

    if(empty($_POST['max-driver-distance'])){

        $_SESSION['action_error'][]    = "Please enter a value for maximum driver distance";
    }


    if(empty($_POST['call-center'])){

        $_SESSION['action_error'][]    = "Please enter a a call center number";
    }


    if(empty($_POST['night-start'])){

        $_SESSION['action_error'][]    = "Please enter a a call center number";
    }



    if(empty($_POST['night-end'])){

        $_SESSION['action_error'][]    = "Please enter a a call center number";
    }



    if((int) $_POST['night-start'] < (int) $_POST['night-end'] ){
        $_SESSION['action_error'][]    = "Night start time must not be earlier than Night end time";
    }



    if((int) $_POST['night-start'] > 24 || (int) $_POST['night-end'] > 24){
        $_SESSION['action_error'][]    = "Night start and end time must not be greater than 24hours";
    }


    if(empty($_POST['min-book-int'])){
        $_SESSION['action_error'][]    = "Minimum booking interval must not be zero or empty";
    }


    if(empty($_POST['max-pend-book'])){
        $_SESSION['action_error'][]    = "Maximum pending booking must not be zero or empty";
    }

    if(empty($_POST['driver-location-update-interval'])){
        $_SESSION['action_error'][]    = "Driver location update interval must not be zero or empty";
    }


    if(empty($_POST['driver-inactivity-timeout'])){
        $_SESSION['action_error'][]    = "Driver inactivity timeout period must not be zero or empty";
    }



    


    if(!empty($_SESSION['action_error'])){
    
        ob_start();
        include('../../drop-files/templates/admin/settingstpl.php'); 
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

    //save settings
    file_put_contents(realpath("../../drop-files/lib/"). "/" . "settingsdata.php",'<?php $settings_data = ' . var_export($_POST, true) . " ?>");
    
    //require(realpath("../../drop-files/lib/") . "/". "settingsdata.php");

    $settings_data = $_POST;
   
    $_SESSION['action_success'][] = "Settings updated successfully.";
    
    ob_start();
    include('../../drop-files/templates/admin/settingstpl.php');

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

    //restart cron
    file_put_contents(realpath(".."). "/" . "crondata.txt",'2');

    $pageContent = ob_get_clean();
    $GLOBALS['admin_template']['page_content'] = $pageContent;
    include "../../drop-files/templates/admin/admin-interface.php";
    exit;

}

if(isset($_POST['savesettings2'])){
    $active_tab = 1;
    //var_dump($_POST);

    if(DEMO){
    
        $_SESSION['action_error'][] = "You are running in Demo mode. Action cannot be completed";
        
        ob_start();
        include('../../drop-files/templates/admin/settingstpl.php'); 
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

    if(empty($_POST['google-maps-api-key'])){

        $_SESSION['action_error'][]    = "Google maps API key is required";
    }




    if(!empty($_SESSION['action_error'])){
    
        ob_start();
        include('../../drop-files/templates/admin/settingstpl.php'); 
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


    //save settings
    file_put_contents(realpath("../../drop-files/lib/"). "/" . "settingsdata2.php",'<?php $settings_data2 = ' . var_export($_POST, true) . " ?>");
    
    //require(realpath("../../drop-files/lib/") . "/". "settingsdata2.php");

    $settings_data2 = $_POST;
   
    $_SESSION['action_success'][] = "Settings updated successfully.";
    
    ob_start();
    include('../../drop-files/templates/admin/settingstpl.php');

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

    //restart cron
    file_put_contents(realpath(".."). "/" . "crondata.txt",'2');

    $pageContent = ob_get_clean();
    $GLOBALS['admin_template']['page_content'] = $pageContent;
    include "../../drop-files/templates/admin/admin-interface.php";
    exit;

}



if(isset($_POST['savesettings3'])){
    
    $active_tab = 2;
    //var_dump($_POST);

    if(DEMO){
    
        $_SESSION['action_error'][] = "You are running in Demo mode. Action cannot be completed";
        
        ob_start();
        include('../../drop-files/templates/admin/settingstpl.php'); 
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

    if(!empty($_SESSION['action_error'])){
    
        ob_start();
        include('../../drop-files/templates/admin/settingstpl.php'); 
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


    //save settings
    file_put_contents(realpath("../../drop-files/lib/"). "/" . "settingsdata3.php",'<?php $settings_data3 = ' . var_export($_POST, true) . " ?>");
    
    //require(realpath("../../drop-files/lib/") . "/". "settingsdata3.php");

    $settings_data3 = $_POST;
   
    $_SESSION['action_success'][] = "Settings updated successfully.";
    
    ob_start();
    include('../../drop-files/templates/admin/settingstpl.php');

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

    //restart cron
    //file_put_contents(realpath(".."). "/" . "crondata.txt",'2');

    $pageContent = ob_get_clean();
    $GLOBALS['admin_template']['page_content'] = $pageContent;
    include "../../drop-files/templates/admin/admin-interface.php";
    exit;

}



if(isset($_POST['newcurrency'])){
    $active_tab = 3;

    if(DEMO && isset($_POST['defaut-currency'])){

        $_SESSION['action_error'][] = "You are running in Demo mode. Default currency cannot be changed.";
        
        ob_start();
        include('../../drop-files/templates/admin/settingstpl.php'); 
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

    $currency_item_id = isset($_POST['currency-list']) ? (int) $_POST['currency-list'] : 0;
    $exchange_rate = isset($_POST['exchange-rate']) ? (float) $_POST['exchange-rate'] : 0;
    $selected_currency_to_add_details = [];

    if(empty($exchange_rate)){
        $_SESSION['action_error'][]    = "Please enter a value for the exchange rate.";
    }

    //get the selected currency from the currency list DB
    $query = sprintf('SELECT * FROM %stbl_currency_list WHERE id = %d', DB_TBL_PREFIX, $currency_item_id);

    //echo mysqli_error($GLOBALS['DB']);

    if($result = mysqli_query($GLOBALS['DB'], $query)){
        if(mysqli_num_rows($result)){

            $row = mysqli_fetch_assoc($result);          
            $selected_currency_to_add_details = $row;
            
        }else{
            $_SESSION['action_error'][]    = "Invalid currency ID. Please select a currency from the list";
        }
        mysqli_free_result($result);
    }else{
        $_SESSION['action_error'][]    = "Error communicating with database";
    }  

    if(!empty($_SESSION['action_error'])){
    
        ob_start();
        include('../../drop-files/templates/admin/settingstpl.php'); 
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

    
    //all good. Add to user currencies
    
    $query = sprintf('INSERT INTO %stbl_currencies (`name`,iso_code,symbol,exchng_rate) VALUES'.
    '("%s","%s","%s","%s")', 
    DB_TBL_PREFIX,
    $selected_currency_to_add_details['name'],
    $selected_currency_to_add_details['code'],
    $selected_currency_to_add_details['symbol'],
    $exchange_rate
    );


    if(! $result = mysqli_query($GLOBALS['DB'], $query)){
        //echo mysqli_error($GLOBALS['DB']);
        
        $_SESSION['action_error'][] = "Error! Currency already added.";
    
        ob_start();
        include('../../drop-files/templates/admin/settingstpl.php'); 
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
    else{
            
        $added_currency_id = mysqli_insert_id ( $GLOBALS['DB'] );
            
    }

    //currency added to list. lets process default currency

    if(isset($_POST['defaut-currency'])){ //default currency set

        //clear previously set default currency

        $query = sprintf('UPDATE %stbl_currencies SET `default` = 0 WHERE `default` = 1',DB_TBL_PREFIX );
        $result = mysqli_query($GLOBALS['DB'], $query);

        
        //set the new added currency as default
        $query = sprintf('UPDATE %stbl_currencies SET `default` = 1, exchng_rate = 1 WHERE id = %d',DB_TBL_PREFIX,$added_currency_id);
        $result = mysqli_query($GLOBALS['DB'], $query);
        


                  

    }


    //update added currency table data

    $number_of_currencies = 0;
    $currency_page_items = [];
    //Get number of currencies in datatbase
    $query = sprintf('SELECT COUNT(*) FROM %stbl_currencies', DB_TBL_PREFIX);  //Get and count all data

    //echo mysqli_error($GLOBALS['DB']);

    if($result = mysqli_query($GLOBALS['DB'], $query)){
        if(mysqli_num_rows($result)){

            $row = mysqli_fetch_assoc($result);          
            $number_of_currencies = $row['COUNT(*)'];
            
        }
        mysqli_free_result($result);
    }  

    //Get currency data from DB
    $query = sprintf('SELECT * FROM %stbl_currencies ORDER BY `name` DESC', DB_TBL_PREFIX);


    if($result = mysqli_query($GLOBALS['DB'], $query)){
    
        if(mysqli_num_rows($result)){
            while($row = mysqli_fetch_assoc($result)){
                $currency_page_items[] = $row;
            }
        
        }
        mysqli_free_result($result);
    }

    
    $_SESSION['action_success'][] = "Currency successfully added.";
    
    ob_start();
    include('../../drop-files/templates/admin/settingstpl.php');

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


if(isset($_POST['editcurrency'])){
    $active_tab = 3;

    if(DEMO && isset($_POST['edit-defaut-currency'])){

        $_SESSION['action_error'][] = "You are running in Demo mode. Default currency cannot be changed.";
        
        ob_start();
        include('../../drop-files/templates/admin/settingstpl.php'); 
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

    $currency_item_id = isset($_POST['edit-currency-list']) ? (int) $_POST['edit-currency-list'] : 0;
    $exchange_rate = isset($_POST['edit-exchange-rate']) ? (float) $_POST['edit-exchange-rate'] : 0;
    $currency_id = isset($_POST['cur-id']) ? (int) $_POST['cur-id'] : 0;
    $selected_currency_to_add_details = [];

    if(empty($exchange_rate)){
        $_SESSION['action_error'][]    = "Error: No value entered for the exchange rate.";
    }

    //verify that modification is not being done on the current default currency
    
    $query = sprintf('SELECT * FROM %stbl_currencies WHERE id = %d', DB_TBL_PREFIX, $currency_id);

    //echo mysqli_error($GLOBALS['DB']);
    if($result = mysqli_query($GLOBALS['DB'], $query)){
        if(mysqli_num_rows($result)){ //trying to modify default currency. throw an error
            $row = mysqli_fetch_assoc($result);
            if($row['default']){
                $_SESSION['action_error'][]    = "Default currency cannot be modified.";           
            }
        }else{
            $_SESSION['action_error'][]    = "Invalid currency."; 
        }
        mysqli_free_result($result);
    }else{
        $_SESSION['action_error'][]    = "Error communicating with database";
    }

    if(!empty($_SESSION['action_error'])){
    
        ob_start();
        include('../../drop-files/templates/admin/settingstpl.php'); 
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

    
    //update the currency
    if(isset($_POST['edit-defaut-currency'])){ //default currency set

        //clear previously set default currency
        $query = sprintf('UPDATE %stbl_currencies SET `default` = 0 WHERE `default` = 1',DB_TBL_PREFIX );
        $result = mysqli_query($GLOBALS['DB'], $query);

        
        //update currency
        $query = sprintf('UPDATE %stbl_currencies SET `default` = 1, exchng_rate = 1 WHERE id = %d',DB_TBL_PREFIX,$currency_id);
        $result = mysqli_query($GLOBALS['DB'], $query);
        
    }else{

        //update currency
        $query = sprintf('UPDATE %stbl_currencies SET `default` = 0, exchng_rate = "%s" WHERE id = %d',DB_TBL_PREFIX,$exchange_rate,$currency_id);
        $result = mysqli_query($GLOBALS['DB'], $query);

    }

    //update added currency table data

    $number_of_currencies = 0;
    $currency_page_items = [];
    //Get number of currencies in datatbase
    $query = sprintf('SELECT COUNT(*) FROM %stbl_currencies', DB_TBL_PREFIX);  //Get and count all data

    //echo mysqli_error($GLOBALS['DB']);

    if($result = mysqli_query($GLOBALS['DB'], $query)){
        if(mysqli_num_rows($result)){

            $row = mysqli_fetch_assoc($result);          
            $number_of_currencies = $row['COUNT(*)'];
            
        }
        mysqli_free_result($result);
    }  

    //Get currency data from DB
    $query = sprintf('SELECT * FROM %stbl_currencies ORDER BY `name` DESC', DB_TBL_PREFIX);


    if($result = mysqli_query($GLOBALS['DB'], $query)){
    
        if(mysqli_num_rows($result)){
            while($row = mysqli_fetch_assoc($result)){
                $currency_page_items[] = $row;
            }
        
        }
        mysqli_free_result($result);
    }

    $_SESSION['action_success'][] = "Currency successfully updated.";
    
    ob_start();
    include('../../drop-files/templates/admin/settingstpl.php');

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


if(isset($_POST['savesettings4'])){
    $active_tab = 4;
    /* var_dump($_POST);
    exit; */

    if(DEMO){
    
        $_SESSION['action_error'][] = "You are running in Demo mode. Action cannot be completed";
        
        ob_start();
        include('../../drop-files/templates/admin/settingstpl.php'); 
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

    
    if(!empty($_POST['email-transport']) && $_POST['email-transport'] == "1"){ //if email transport is system, do not modify previous smtp values
        $_POST['smtp-hostname'] = isset($settings_data4['smtp-hostname']) ? $settings_data4['smtp-hostname'] : "";
        $_POST['smtp-username'] = isset($settings_data4['smtp-username']) ? $settings_data4['smtp-username'] : "";
        $_POST['smtp-password'] = isset($settings_data4['smtp-password']) ? $settings_data4['smtp-password'] : "";
    }

    
    if(empty($_POST['sender-email'])){
        $_SESSION['action_error'][]    = "Please enter a sender's Email address";
    }

    if(empty($_POST['new-riders-reg-email-subj'])){
        $_SESSION['action_error'][]    = "Please enter a subject for new riders registration email";
    }


    if(empty($_POST['new-riders-reg-email-msg'])){
        $_SESSION['action_error'][]    = "Please enter an email message for new riders registration";
    }


    if(empty($_POST['new-drivers-reg-email-subj'])){
        $_SESSION['action_error'][]    = "Please enter a subject for new drivers registration email";
    }


    if(empty($_POST['new-drivers-reg-email-msg'])){
        $_SESSION['action_error'][]    = "Please enter an email message for new drivers registration";
    }


    if(empty($_POST['password-reset-email-subj'])){
        $_SESSION['action_error'][]    = "Please enter a subject for password reset email";
    }


    if(empty($_POST['password-reset-email-msg'])){
        $_SESSION['action_error'][]    = "Please enter an email message for password reset";
    }




    if(!empty($_SESSION['action_error'])){
    
        ob_start();
        include('../../drop-files/templates/admin/settingstpl.php'); 
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

    //save settings
    file_put_contents(realpath("../../drop-files/lib/"). "/" . "settingsdata4.php",'<?php $settings_data4 = ' . var_export($_POST, true) . " ?>");
    
    
    $settings_data4 = $_POST;
   
    $_SESSION['action_success'][] = "Settings updated successfully.";
    
    ob_start();
    include('../../drop-files/templates/admin/settingstpl.php');

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

    //restart cron
    //file_put_contents(realpath(".."). "/" . "crondata.txt",'2');

    $pageContent = ob_get_clean();
    $GLOBALS['admin_template']['page_content'] = $pageContent;
    include "../../drop-files/templates/admin/admin-interface.php";
    exit;

}



    
ob_start();
header('Content-Type: text/html; charset=utf-8');
include('../../drop-files/templates/admin/settingstpl.php');
$pageContent = ob_get_clean();
$GLOBALS['admin_template']['page_content'] = $pageContent;
include "../../drop-files/templates/admin/admin-interface.php";
exit;











    
    

    
        
        



?>