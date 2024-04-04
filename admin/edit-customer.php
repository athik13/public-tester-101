<?php
session_start();
include("../../drop-files/lib/common.php");
include "../../drop-files/config/db.php";


$customer_data = array();
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

$GLOBALS['admin_template']['page_title'] = "<i class='fa fa-user'></i> Edit Customer"; //Set the title of the page on the admin interface
$GLOBALS['admin_template']['active_menu'] = "customers"; //Set the appropriate menu item active
$GLOBALS['admin_template']['active_sub_menu'] = "customer-all"; //Set the appropriate menu item active


if(!empty($_POST['customer-id'])){
    $id = (int) $_POST['customer-id'];
 }
elseif(!empty($_GET['id'])) {
    $id = (int) $_GET['id'] ;
 }elseif(!empty($_SESSION['reload_id'])){
    $id = (int) $_SESSION['reload_id'];
    unset($_SESSION['reload_id']);
 }


 if(isset($_GET['action'])){
    
    if($_GET['action'] == "act"){
            
            $query = sprintf('UPDATE %stbl_users SET is_activated = 1 WHERE user_id = "%d"', DB_TBL_PREFIX, $id );

            if(!$result = mysqli_query($GLOBALS['DB'], $query)){
                
                //echo mysqli_error($GLOBALS['DB']);
                $_SESSION['reload_id'] = $id;
                $_SESSION['action_error'][] = "An error has occured. Could not activate customer account";
                header("location: ".SITE_URL."admin/edit-customer.php"); //Yes? then redirect
                exit;
            
            }

            $_SESSION['reload_id'] = $id;
            $_SESSION['action_success'][] = "The customer account was activated successfully.";
            header("location: ".SITE_URL."admin/all-customers.php"); //Yes? then redirect
            exit;


    }elseif($_GET['action'] == "deact"){
            
        $query = sprintf('UPDATE %stbl_users SET is_activated = 0 WHERE user_id = "%d"', DB_TBL_PREFIX, $id );

        if(!$result = mysqli_query($GLOBALS['DB'], $query)){
            
            //echo mysqli_error($GLOBALS['DB']);
            $_SESSION['reload_id'] = $id;
            $_SESSION['action_error'][] = "An error has occured. Could not deactivate customer account";
            header("location: ".SITE_URL."admin/edit-customer.php"); //Yes? then redirect
            exit;
        
        }

        //delete old activation code
        $query = sprintf('DELETE FROM %stbl_account_codes WHERE user_id = "%d" AND user_type = 0 AND context = 0', DB_TBL_PREFIX, $id); //delete already inserted record 
        $result = mysqli_query($GLOBALS['DB'], $query);

        //Generate new code
        $act_code = crypto_string("nozero",5);

        //store to database
        $query = sprintf('INSERT INTO %stbl_account_codes (user_id, code,user_type) VALUES ("%d","%s",0)',DB_TBL_PREFIX, $id, $act_code); 
        $result = mysqli_query($GLOBALS['DB'], $query);

        $_SESSION['reload_id'] = $id;
        $_SESSION['action_success'][] = "The customer account was deactivated successfully.";
        header("location: ".SITE_URL."admin/all-customers.php"); //Yes? then redirect
        exit;

    }elseif($_GET['action'] == "del"){

        if(DEMO){
            $_SESSION['action_error'][] = "You are running in Demo mode. Action cannot be completed";
            header("location: ".SITE_URL."admin/all-customers.php"); //Yes? then redirect 
            exit;
        }

        //Ensure that data exists on DB
        $staff_data_item = [];
        $query = sprintf('SELECT * FROM %stbl_users WHERE user_id = "%d"',DB_TBL_PREFIX, $id );
            if($result = mysqli_query($GLOBALS['DB'], $query)){
                    
                if(!mysqli_num_rows($result)){

                    $_SESSION['action_error'][] = "Could not delete the requested record. The record was not found in the database";
                    header("location: ".SITE_URL."admin/all-customers.php"); //Yes? then redirect
                    exit;
                    
                }

                $staff_data_item = mysqli_fetch_assoc($result);
                mysqli_free_result($result);
            } else{
                $_SESSION['action_error'][] = "An error occured while trying to delete customer record from the database.";
                header("location: ".SITE_URL."admin/all-customers.php"); //Yes? then redirect
                exit;
            } 

        //then delete record
        $query = sprintf('DELETE FROM %stbl_users WHERE user_id = "%d"', DB_TBL_PREFIX, $id); 
        if(!$result = mysqli_query($GLOBALS['DB'], $query)){ 
            $_SESSION['action_error'][] = "An error occured while trying to delete customer record from the database.";
            header("location: ".SITE_URL."admin/all-customers.php"); //Yes? then redirect
            exit;
            
        }


        //@unlink($staff_data_item['photo_file']); //delete the old user uploaded file

        
          
                    
        $_SESSION['action_success'][] = "The customer record was successfully deleted.";
        header("location: ".SITE_URL."admin/all-customers.php"); //Yes? then redirect
        exit;


    }

 }


$query = sprintf('SELECT *,%1$stbl_users.user_id AS customer_id FROM %1$stbl_users
LEFT JOIN %1$stbl_account_codes ON %1$stbl_account_codes.user_id = %1$stbl_users.user_id AND %1$stbl_account_codes.user_type = 0 AND %1$stbl_account_codes.context = 0 
WHERE %1$stbl_users.user_id = "%2$d"', DB_TBL_PREFIX, $id);

if($result = mysqli_query($GLOBALS['DB'], $query)){
    if(mysqli_num_rows($result)){

       $customer_data = mysqli_fetch_assoc($result);

    }else{
         
        $_SESSION['action_error'][] = "No Valid ID passed";
        header("location: ".SITE_URL."admin/all-customers.php"); //Yes? then redirect
        exit;
     }
    mysqli_free_result($result);
}else{
        
        $_SESSION['action_error'][] = "Error executing database query";
        header("location: ".SITE_URL."admin/all-customers.php"); //Yes? then redirect
        exit;
}




if(empty($_POST)){ //let's render the add-new-franchise page UI'
    
    ob_start();
    include('../../drop-files/templates/admin/editcustomertpl.php');
    $pageContent = ob_get_clean();
    $GLOBALS['admin_template']['page_content'] = $pageContent;
    include "../../drop-files/templates/admin/admin-interface.php";
    exit;


}


if(DEMO){
    $_SESSION['action_error'][] = "You are running in Demo mode. Action cannot be completed";
    header("location: ".SITE_URL."admin/all-customers.php"); //Yes? then redirect 
    exit;
}


if(!empty($_POST['image-data'])){

        //customer uploaded photo file

        $uploaded_photo_encoded = $_POST['image-data']; //Get Base64 encoded image data. Encoded by our cropit jQuery plugin
        $uploaded_photo_encoded_array = explode(',', $uploaded_photo_encoded);
        $image_data = array_pop($uploaded_photo_encoded_array);
        $uploaded_photo_decoded = base64_decode($image_data); //Decode the data

        
        if(!$uploaded_photo_decoded){ //Verify that data is valid base64 data
            $_SESSION['action_error'][] = "Invalid photo. Please upload a passport photo in JPEG or PNG format";
        }else{

            //prepare filename and save the file. Cropit plugin has been configured to export base64 image data in JPEG format. We should be expecting a JPEG image data then.
            $filename =  crypto_string('distinct',20);

            @mkdir(realpath("../".CUSTOMER_PHOTO_PATH) . "/". $filename[0] . "/" . $filename[1] . "/" . $filename[2], 0777, true);


            $image_path = realpath("../".CUSTOMER_PHOTO_PATH) .  "/". $filename[0] . "/" . $filename[1] . "/" . $filename[2] . "/";
            $file = $image_path . $filename . ".jpg";


            
            file_put_contents($file, $uploaded_photo_decoded); //store the photo to disk.     

            $user_photo = $filename . ".jpg";
            $user_photo_path = $image_path . $filename . ".jpg";

            

        }  
    
             
   
}else{
    $user_photo = $customer_data['photo_file'];
}




$_POST['firstname'] = str_replace(" ","",$_POST['firstname']); //remove spaces
$_POST['lastname'] = str_replace(" ","",$_POST['lastname']); //remove spaces



if(empty($_POST['firstname'])) {
    $_SESSION['action_error'][] = "Please enter a first name";
    
} 

if(strlen($_POST['firstname']) < 2){
    $_SESSION['action_error'][] = "Your firstname is too short";            
}

if(preg_match('/[^a-z]/i', $_POST['firstname'])){
    //$_SESSION['action_error'][] = "Your first name must contain only alphabetical characters";            
}

       
if(empty($_POST['lastname'])) {
   $_SESSION['action_error'][] = "Please enter a lastname";
} 
 if(preg_match('/[^a-z]/i', $_POST['lastname'])){
    //$_SESSION['action_error'][] = "Your lastname must contain only alphabetical characters";            
}
 if(strlen($_POST['lastname']) < 2){
    $_SESSION['action_error'][] = "Your lastname is too short";            
}


if(empty($_POST['email'])) {
    $_SESSION['action_error'][] = "Please enter a valid email";
} 

if(!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)){
    $_SESSION['action_error'][] = "Your email is not a valid email format";
}

if(strlen($_POST['email'])>64){
    $_SESSION['action_error'][] = "Your email is too long. Email must be lower than 64 characters";
}

if(strlen($_POST['phone']) > 20) {
    $_SESSION['action_error'][] = "Your phone number is too long";
} 
if(strlen($_POST['phone']) < 5) {
    $_SESSION['action_error'][] = "Your phone number is too short";
} 


if((strlen($_POST['password']) < 8 )){
   $_SESSION['action_error'][] = "Password must not be less than eight characters";
}

if((strlen($_POST['password']) > 60 )){
   $_SESSION['action_error'][] = "Password is too long. Password must not be more than 60 characters";
}

$customer_country = codeToCountryName(strtoupper($_POST['country-code']));

if(!$customer_country){
    $_SESSION['action_error'][]    = "Invalid country selected!";
}

if(!empty($_SESSION['action_error'])){

    //@unlink($user_photo); //delete the old user uploaded file
   
    ob_start();
    include('../../drop-files/templates/admin/editcustomertpl.php'); 
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



//Checck if email or phone number already exists

$query = sprintf('SELECT user_id,email, phone FROM %stbl_users WHERE (email = "%s" OR phone="%s") AND user_id != "%d"', DB_TBL_PREFIX,mysqli_real_escape_string($GLOBALS['DB'], $_POST['email']),mysqli_real_escape_string($GLOBALS['DB'], $_POST['phone']),$id);


if($result = mysqli_query($GLOBALS['DB'], $query)){
    if(mysqli_num_rows($result)){

        $row = mysqli_fetch_assoc($result);
        if($row['email'] == mysqli_real_escape_string($GLOBALS['DB'], $_POST['email'])){
            $_SESSION['action_error'][] = "The email address already exists. Please use a different email address";
        }elseif($row['phone'] == mysqli_real_escape_string($GLOBALS['DB'], $_POST['phone'])){
            $_SESSION['action_error'][] = "The phone number already exists. Please use a different phone number";
        }else{
            $_SESSION['action_error'][] = "The email address or phone number already exists. Please use a different email address or phone number";
        }      
        
       
    }
}else{

    $_SESSION['action_error'][] = "Error connecting to database. Please contact the administrator.";
    
}



if(!empty($_SESSION['action_error'])){
    //@unlink($user_photo); //delete the old user uploaded file
    ob_start();
    include('../../drop-files/templates/admin/editcustomertpl.php'); 
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


if(empty($_POST['image-data'])){
    $user_photo = $customer_data['photo_file'];
}

$activateacc = !empty($_POST['activateacc']) ? 1 : 0;

//OK, all good. lets store the registrant form data in the database
$verify_reg  = 0;
$query = sprintf('UPDATE %stbl_users SET firstname = "%s", lastname = "%s", email = "%s", phone = "%s", pwd_raw = "%s", password_hash = "%s", referal_code = "%s",photo_file = "%s",country_code = "%s",country_dial_code = "%s",country = "%s" WHERE user_id = "%d"', 
    DB_TBL_PREFIX, 
    mysqli_real_escape_string($GLOBALS['DB'], ucfirst(strtolower(strip_tags($_POST['firstname'])))),
    mysqli_real_escape_string($GLOBALS['DB'], ucfirst(strtolower(strip_tags($_POST['lastname'])))),
    mysqli_real_escape_string($GLOBALS['DB'], strip_tags($_POST['email'])),
    mysqli_real_escape_string($GLOBALS['DB'], strip_tags($_POST['phone'])),
    mysqli_real_escape_string($GLOBALS['DB'], $_POST['password']),
    password_hash(mysqli_real_escape_string($GLOBALS['DB'], $_POST['password']), PASSWORD_DEFAULT),
    mysqli_real_escape_string($GLOBALS['DB'], strip_tags($_POST['refcode'])),
    $user_photo,
    mysqli_real_escape_string($GLOBALS['DB'], $_POST['country-code']),
    "+" . mysqli_real_escape_string($GLOBALS['DB'], $_POST['country-dial-code']),
    $customer_country,
    $id
);


if(! $result = mysqli_query($GLOBALS['DB'], $query)){
    //echo mysqli_error($GLOBALS['DB']);
    
    $_SESSION['action_error'][] = "An error has occured. Could not modify staff record. Ensure database connection is working";
    //@unlink($user_photo); //delete the old user uploaded file
    ob_start();
    include('../../drop-files/templates/admin/editcustomertpl.php'); 
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
        $id = mysqli_insert_id ( $GLOBALS['DB'] );
        
    }


    
   
    $_SESSION['action_success'][] = "The customer record was modified successfully.";
    header("location: ".SITE_URL."admin/all-customers.php"); //Yes? then redirect
    exit;



?>