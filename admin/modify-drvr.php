<?php
session_start();
include("../../drop-files/lib/common.php");
include ("../../drop-files/config/db.php");
define('ITEMS_PER_PAGE', 10); //define constant for number of items to display per page

$driver_page_items = array();
$franchise_data = [];
$ride_data = [];
$op_result_success = 0; //keep track of result of operation for user feeback 
$op_result_fail = 0; //keep track of result of operation for user feeback
$op_result_msg = ''; 
$id = 0;

$banks_details = getdefaultbanks();

/* $banks_details = array(
    "044"=>"Access Bank",
    "023"=>"Citibank",
    "063"=>"Diamond Bank",
    "050"=>"Ecobank",
    "040"=>"Equitorial Trust Bank",
    "011"=>"First Bank",
    "214"=>"FCMB",
    "070"=>"Fidelity Bank",
    "085"=>"Finbank",
    "058"=>"Guaranty Trust Bank",
    "030"=>"Heritage Bank",
    "082"=>"Keystone Bank",
    "014"=>"Mainstreet Bank",
    "076"=>"Skye Bank",
    "221"=>"Stanbic IBTC Bank",
    "032"=>"Union Bank of Nigeria",
    "033"=>"United Bank of Africa (UBA)",
    "215"=>"Unity Bank",
    "035"=>"Wema Bank",
    "057"=>"Zenith Bank",
    "xxx"=> "Other..."
); */

if(isset($_SESSION['expired_session'])){
    echo "<h1 style='text-align:center;'>Your session has expired. Please logout and login to continue.</h1>";
    exit;
}

if(!(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == 1)){ //if user is not logged in run this code
    echo "<h1 style='text-align:center;'>Please login to continue.</h1>";
    exit;
}

if($_SESSION['account_type'] != 3 && $_SESSION['account_type'] != 2){ ////if user is not an admin or dispatcher
    $_SESSION['action_error'][] = "Access Denied!";
    header("location: ".SITE_URL."admin/index.php"); //Yes? then redirect user to the login page
    exit;
}

$GLOBALS['admin_template']['page_title'] = "<i class='fa fa-drivers-license'></i> Modify Driver"; //Set the title of the page on the admin interface
$GLOBALS['admin_template']['active_menu'] = "drivers"; //Set the appropriate menu item active
$GLOBALS['admin_template']['active_sub_menu'] = "driver-all"; //Set the appropriate menu item active



if(!empty($_POST['driver-id'])){
    $id = (int) $_POST['driver-id'];
 }
elseif(!empty($_GET['id'])) {
        $id = (int) $_GET['id'] ;
 }elseif($_SESSION['reload_id']){
    $id = (int) $_SESSION['reload_id'];
    unset($_SESSION['reload_id']);
 }


if(isset($_GET['action'])){
    if($_GET['action'] == "act"){
            
            $query = sprintf('UPDATE %stbl_drivers SET is_activated = 1 WHERE driver_id = "%d"', DB_TBL_PREFIX, $id );

            if(!$result = mysqli_query($GLOBALS['DB'], $query)){
                
                //echo mysqli_error($GLOBALS['DB']);
                $_SESSION['reload_id'] = $id;
                $_SESSION['action_error'][] = "An error has occured. Could not activate driver account";
                header("location: ".SITE_URL."admin/modify-drvr.php"); //Yes? then redirect
                exit;
            
            }

            $_SESSION['reload_id'] = $id;
            $_SESSION['action_success'][] = "The driver account was activated successfully.";
            header("location: ".SITE_URL."admin/modify-drvr.php"); //Yes? then redirect
            exit;


    }elseif($_GET['action'] == "deact"){
            
        $query = sprintf('UPDATE %stbl_drivers SET is_activated = 0 WHERE driver_id = "%d"', DB_TBL_PREFIX, $id );

        if(!$result = mysqli_query($GLOBALS['DB'], $query)){
            
            //echo mysqli_error($GLOBALS['DB']);
            $_SESSION['reload_id'] = $id;
            $_SESSION['action_error'][] = "An error has occured. Could not deactivate driver account";
            header("location: ".SITE_URL."admin/modify-drvr.php"); //Yes? then redirect
            exit;
        
        }

        //delete old activation code
        $query = sprintf('DELETE FROM %stbl_account_codes WHERE user_id = "%d" AND user_type = 1', DB_TBL_PREFIX, $id); //delete already inserted record 
        $result = mysqli_query($GLOBALS['DB'], $query);

        //Generate new code
        $act_code = crypto_string("nozero",5);

        //store to database
        $query = sprintf('INSERT INTO %stbl_account_codes (user_id, code,user_type) VALUES ("%d","%s",1)',DB_TBL_PREFIX, $id, $act_code); 
        $result = mysqli_query($GLOBALS['DB'], $query);

        $_SESSION['reload_id'] = $id;
        $_SESSION['action_success'][] = "The driver account was deactivated successfully.";
        header("location: ".SITE_URL."admin/modify-drvr.php"); //Yes? then redirect
        exit;

    }elseif($_GET['action'] == "del"){

        if(DEMO){
            $_SESSION['action_error'][] = "You are running in Demo mode. Action cannot be completed";
            header("location: ".SITE_URL."admin/all-drivers.php"); //Yes? then redirect 
            exit;
        }

        //Ensure that data exists on DB
        $driver_data = [];
        $query = sprintf('SELECT * FROM %stbl_drivers WHERE id = "%d"',DB_TBL_PREFIX, $id );
            if($result = mysqli_query($GLOBALS['DB'], $query)){
                    
                if(!mysqli_num_rows($result)){

                    $_SESSION['action_error'][] = "Could not delete the requested record. The record was not found in the database";
                    header("location: ".SITE_URL."admin/all-drivers.php"); //Yes? then redirect
                    exit;
                    
                }

                $driver_data = mysqli_fetch_assoc($result);
                mysqli_free_result($result);
            }  

        //then delete record
        $query = sprintf('DELETE FROM %stbl_drivers WHERE driver_id = "%d"', DB_TBL_PREFIX, $id); 
        if(!$result = mysqli_query($GLOBALS['DB'], $query)){ 
            $_SESSION['action_error'][] = "An error occured while trying to delete driver record from the database.";
            header("location: ".SITE_URL."admin/all-drivers.php"); //Yes? then redirect
            exit;
            
        }

        //delete driver photo
        //@unlink($driver_data['photo_file']); //delete the old user uploaded file


        //update all record driver entry in bookings table to unallocated
        /* $query = sprintf('UPDATE %stbl_bookings SET driver_id = 0 WHERE driver_id = "%d"', DB_TBL_PREFIX, $id); 
        $result = mysqli_query($GLOBALS['DB'], $query); */
        
                    
        $_SESSION['action_success'][] = "The driver record was successfully deleted.";
        header("location: ".SITE_URL."admin/all-drivers.php"); //Yes? then redirect
        exit;



    }





}





$query = sprintf('SELECT * FROM %1$stbl_drivers
LEFT JOIN %1$stbl_account_codes ON %1$stbl_account_codes.user_id = %1$stbl_drivers.driver_id AND %1$stbl_account_codes.user_type = 1 
WHERE driver_id = "%2$d"', DB_TBL_PREFIX, $id);

if($result = mysqli_query($GLOBALS['DB'], $query)){
    if(mysqli_num_rows($result)){
       while($row = mysqli_fetch_assoc($result)){
            $driver_page_items = $row;
       }
    
    }else{
         die('No Valid ID passed');
     }
    mysqli_free_result($result);
}else{
        echo mysqli_error($GLOBALS['DB']);
        die('Error executing database query');
}


//Get all franchises
$query = sprintf('SELECT * FROM %stbl_franchise ORDER BY id ASC', DB_TBL_PREFIX);


if($result = mysqli_query($GLOBALS['DB'], $query)){
  
    if(mysqli_num_rows($result)){
        while($row = mysqli_fetch_assoc($result)){
            $franchise_data[] = $row;
        }
    
     }
    mysqli_free_result($result);
}


//get all intra-city routes
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




//get all rides

$query = sprintf('SELECT * FROM %stbl_rides WHERE avail = 1 ORDER BY id ASC', DB_TBL_PREFIX);


if($result = mysqli_query($GLOBALS['DB'], $query)){
  
    if(mysqli_num_rows($result)){
        while($row = mysqli_fetch_assoc($result)){
            $ride_data[] = $row;
        }
    
     }
    mysqli_free_result($result);
}


/* $photo = explode('/',$driver_page_items['photo_file']);
$photo_file = isset($photo[4]) ? $photo[4] : "0"; */



if(empty($_POST)){
    ob_start();
    include('../../drop-files/templates/admin/modifydrvrtpl.php');  
    $GLOBALS['admin_template']['page_content'] = ob_get_clean();
    include "../../drop-files/templates/admin/admin-interface.php";
    exit;    
    
}



if(DEMO){
    $_SESSION['action_error'][] = "You are running in Demo mode. Action cannot be completed";
    header("location: ".SITE_URL."admin/all-drivers.php"); //Yes? then redirect 
    exit;
}




if(!empty($_POST['image-data'])){
    
    //driver uploaded photo file

    $uploaded_photo_encoded = $_POST['image-data']; //Get Base64 encoded image data. Encoded by our cropit jQuery plugin
    $uploaded_photo_encoded_array = explode(',', $uploaded_photo_encoded);
    $image_data = array_pop($uploaded_photo_encoded_array);
    $uploaded_photo_decoded = base64_decode($image_data); //Decode the data

    
    if(!$uploaded_photo_decoded){ //Verify that data is valid base64 data
        $_SESSION['action_error'][] = "Invalid photo. Please upload a passport photo in JPEG or PNG format";
    }else{

        //prepare filename and save the file. Cropit plugin has been configured to export base64 image data in JPEG format. We should be expecting a JPEG image data then.
        $filename =  crypto_string('distinct',20);

        @mkdir(realpath("../".USER_PHOTO_PATH) . "/". $filename[0] . "/" . $filename[1] . "/" . $filename[2], 0777, true);


        $image_path = realpath("../".USER_PHOTO_PATH) .  "/". $filename[0] . "/" . $filename[1] . "/" . $filename[2] . "/";
        $file = $image_path . $filename . ".jpg";


        
        file_put_contents($file, $uploaded_photo_decoded); //store the photo to disk.     

        $driver_passport_photo = $filename . ".jpg";
        $driver_passport_photo_path = $image_path . $filename . ".jpg";

         

    }      
                
   
}else{
    $driver_passport_photo = $driver_page_items['photo_file'];
}

 

$_POST['firstname'] = str_replace(" ","",$_POST['firstname']); //remove spaces
$_POST['lastname'] = str_replace(" ","",$_POST['lastname']); //remove spaces


if(empty($_POST['firstname'])) {
    $_SESSION['action_error'][] = "Please enter a firstname";
    
} 
 if(strlen($_POST['firstname']) < 2){
    $_SESSION['action_error'][] = "Firstname is too short";            
}
if(preg_match('/[^a-z]/i', $_POST['firstname'])){
    //$_SESSION['action_error'][] = "Firstname must contain only alphabetical characters";            
}

    
if(empty($_POST['lastname'])) {
   $_SESSION['action_error'][] = "Please enter a lastname";
}

 if(preg_match('/[^a-z]/i', $_POST['lastname'])){
    //$_SESSION['action_error'][] = "Lastname must contain only alphabetical characters";            
}

 if(strlen($_POST['lastname']) < 2){
    $_SESSION['action_error'][] = "Lastname is too short";            
}

if(empty($_POST['email'])) {
    $_SESSION['action_error'][] = "Please enter a valid email";
} 

if(!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)){
    $_SESSION['action_error'][] = "Email is not a valid email format";
}
if(strlen($_POST['email'])>64){
    $_SESSION['action_error'][] = "Email is too long. Email must be lower than 64 characters";
}

if(empty($_POST['carcity'])) {
    $_SESSION['action_error'][] = "Please select a city where this car will operate.";
}

if(strlen($_POST['phone']) > 20) {
    $_SESSION['action_error'][] = "Phone number is too long";
} 
if(strlen($_POST['phone']) < 5) {
$_SESSION['action_error'][] = "Phone number is too short";
} 
if(empty($_POST['state'])) {
    $_SESSION['action_error'][] = "Please enter a state";
}

if(empty($_POST['address'])) {
    $_SESSION['action_error'][] = "Please enter an address";
}



if((strlen($_POST['password']) < 8 )){
   $_SESSION['action_error'][] = "Password must not be less than eight characters";
}
if((strlen($_POST['password']) > 15 )){
   $_SESSION['action_error'][] = "Password is too long. Password must not be more than 15 characters";
}


//check if email or phone number already exists
$query = sprintf('SELECT driver_id,email,phone FROM %stbl_drivers WHERE (email = "%s" OR phone="%s") AND driver_id != %d', DB_TBL_PREFIX, mysqli_real_escape_string($GLOBALS['DB'], $_POST['email']),mysqli_real_escape_string($GLOBALS['DB'], $_POST['phone']),$id);


if($result = mysqli_query($GLOBALS['DB'], $query)){
    if(mysqli_num_rows($result)){
        $_SESSION['action_error'][]    = "Email or Phone already exists.";

    }
    
}
else{ //No record matching the USER ID was found in DB. Show view to notify user

    $_SESSION['action_error'][]    = "Database error!";
}

if(!empty($_POST['driver-license-data'])){

    //save driving license
    $uploaded_photo_encoded = $_POST['driver-license-data']; //Get Base64 encoded image data. Encoded by our cropit jQuery plugin
    $uploaded_photo_encoded_array = explode(',', $uploaded_photo_encoded);
    $image_data = array_pop($uploaded_photo_encoded_array);
    $uploaded_photo_decoded = base64_decode($image_data); //Decode the data


    if(!$uploaded_photo_decoded){ //Verify that data is valid base64 data
        $_SESSION['action_error'][] = "Invalid photo. Please upload drivers license in JPEG format";
        
    }else{
        //prepare filename and save the file. Cropit plugin has been configured to export base64 image data in JPEG format. We should be expecting a JPEG image data then.
        $filename =  crypto_string('distinct',20);

        @mkdir(realpath("../".USER_PHOTO_PATH) . "/". $filename[0] . "/" . $filename[1] . "/" . $filename[2], 0777, true);


        $image_path = realpath("../".USER_PHOTO_PATH) .  "/". $filename[0] . "/" . $filename[1] . "/" . $filename[2] . "/";
        $file = $image_path . $filename . ".jpg";



        file_put_contents($file, $uploaded_photo_decoded); //store the photo to disk.     
        $driving_license_photo = $filename . ".jpg";
        $driving_license_photo_path = $image_path . $filename . ".jpg";
    } 

}else{
    $driving_license_photo = $driver_page_items['driving_license_file'];
}

if(!empty($_POST['road-worthiness-data'])){
    //save road worthiness certificate
    $uploaded_photo_encoded = $_POST['road-worthiness-data']; //Get Base64 encoded image data. Encoded by our cropit jQuery plugin
    $uploaded_photo_encoded_array = explode(',', $uploaded_photo_encoded);
    $image_data = array_pop($uploaded_photo_encoded_array);
    $uploaded_photo_decoded = base64_decode($image_data); //Decode the data


    if(!$uploaded_photo_decoded){ //Verify that data is valid base64 data
        $_SESSION['action_error'][] = "Invalid photo. Please upload your road worthiness certificate in JPEG format";    
    }else{

        //prepare filename and save the file. Cropit plugin has been configured to export base64 image data in JPEG format. We should be expecting a JPEG image data then.
        $filename =  crypto_string('distinct',20);

        @mkdir(realpath("../".USER_PHOTO_PATH) . "/". $filename[0] . "/" . $filename[1] . "/" . $filename[2], 0777, true);


        $image_path = realpath("../".USER_PHOTO_PATH) .  "/". $filename[0] . "/" . $filename[1] . "/" . $filename[2] . "/";
        $file = $image_path . $filename . ".jpg";



        file_put_contents($file, $uploaded_photo_decoded); //store the photo to disk.     
        $road_worthiness_cert = $filename . ".jpg";
        $road_worthiness_cert_path = $image_path . $filename . ".jpg";
    } 

}else{
    $road_worthiness_cert = $driver_page_items['road_worthiness_file'];
}




$driver_country = codeToCountryName(strtoupper($_POST['country-code']));

if(!$driver_country){
    $_SESSION['action_error'][]    = "Invalid country selected!";
}


/* echo mysqli_error($GLOBALS['DB']);
exit; */

if(!empty($_SESSION['action_error'])){

    ob_start();
    include('../../drop-files/templates/admin/modifydrvrtpl.php');  
    $GLOBALS['admin_template']['page_content'] = ob_get_clean();
    include "../../drop-files/templates/admin/admin-interface.php";
    exit;  



}




$bank_code =  mysqli_real_escape_string($GLOBALS['DB'], $_POST['other-bank-code']);
$bank_name =  mysqli_real_escape_string($GLOBALS['DB'], $_POST['other-bank-name']);


//Store driver data to database
$query = sprintf('UPDATE %stbl_drivers SET route_id = "%d",pwd_raw = "%s",password_hash = "%s",drv_address = "%s",email = "%s",firstname = "%s",lastname = "%s",phone = "%s",`state` = "%s",drv_country = "%s",car_plate_num = "%s",car_model = "%s",car_color = "%s",ride_id = "%s",referal_code = "%s",franchise_id = "%s",photo_file = "%s",bank_name = "%s",bank_acc_holder_name = "%s",bank_acc_num = "%s",bank_code = "%s",driver_commision = "%s",bank_swift_code = "%s",driving_license_file = "%s",road_worthiness_file = "%s",country_code = "%s",country_dial_code = "%s" WHERE driver_id = "%d"', 
    DB_TBL_PREFIX,
    (int) $_POST['carcity'], 
    mysqli_real_escape_string($GLOBALS['DB'], $_POST['password']),
    password_hash(mysqli_real_escape_string($GLOBALS['DB'], $_POST['password']), PASSWORD_DEFAULT),
    mysqli_real_escape_string($GLOBALS['DB'], $_POST['address']),
    mysqli_real_escape_string($GLOBALS['DB'], $_POST['email']),
    mysqli_real_escape_string($GLOBALS['DB'], $_POST['firstname']),
    mysqli_real_escape_string($GLOBALS['DB'], $_POST['lastname']),
    mysqli_real_escape_string($GLOBALS['DB'], $_POST['phone']),
    mysqli_real_escape_string($GLOBALS['DB'], $_POST['state']),
    $driver_country,
    mysqli_real_escape_string($GLOBALS['DB'], $_POST['cpnumber']),
    mysqli_real_escape_string($GLOBALS['DB'], $_POST['carmake']),
    mysqli_real_escape_string($GLOBALS['DB'], $_POST['carcolor']),
    mysqli_real_escape_string($GLOBALS['DB'], $_POST['ridetype']),
    mysqli_real_escape_string($GLOBALS['DB'], $_POST['refcode']),
    (int) $_POST['franchise'],
    $driver_passport_photo,
    $bank_name,
    mysqli_real_escape_string($GLOBALS['DB'], $_POST['bank-acc-holders-name']),
    mysqli_real_escape_string($GLOBALS['DB'], $_POST['bank-details-acc-num']),
    $bank_code,
    mysqli_real_escape_string($GLOBALS['DB'], $_POST['commission']),
    mysqli_real_escape_string($GLOBALS['DB'], $_POST['bank-details-swift']),
    $driving_license_photo,
    $road_worthiness_cert,
    mysqli_real_escape_string($GLOBALS['DB'], $_POST['country-code']),
    "+" . mysqli_real_escape_string($GLOBALS['DB'], $_POST['country-dial-code']),
    $id    

);


if(!$result = mysqli_query($GLOBALS['DB'], $query)){
    
    //echo mysqli_error($GLOBALS['DB']);
    $_SESSION['reload_id'] = $id;
    $_SESSION['action_error'][] = "An error has occured. Could not save new driver form data to database. Ensure database connection is working";
    header("location: ".SITE_URL."admin/modify-drvr.php"); //Yes? then redirect
    exit;

    
}


$_SESSION['reload_id'] = $id;
$_SESSION['action_success'][] = "The driver record was modified successfully.";
header("location: ".SITE_URL."admin/all-drivers.php"); //Yes? then redirect
exit;
































?>