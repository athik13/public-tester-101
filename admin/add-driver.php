<?php
session_start();
include("../../drop-files/lib/common.php");
include "../../drop-files/config/db.php";
$photo_type = "";
$referal_code = "";
$franchise_data = [];
$ride_data = [];
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
    header("location: ".SITE_URL."login.php?timeout=1");
    exit;
}

if(!(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == 1)){ //if user is not logged in run this code
  header("location: ".SITE_URL."login.php"); //Yes? then redirect user to the login page
  exit;
}

if($_SESSION['account_type'] != 3 && $_SESSION['account_type'] != 2){ ////if user is not an admin or dispatcher
    $_SESSION['action_error'][] = "Access Denied!";
    header("location: ".SITE_URL."admin/index.php"); //Yes? then redirect user to the login page
    exit;
}

$GLOBALS['admin_template']['page_title'] = "<i class='fa fa-drivers-license'></i> Add a Driver"; //Set the title of the page on the admin interface
$GLOBALS['admin_template']['active_menu'] = "drivers"; //Set the appropriate menu item active
$GLOBALS['admin_template']['active_sub_menu'] = "driver-new"; //Set the appropriate menu item active





for ($x = 0;$x < 10;$x++){ //try to generate a unique code for a referal code
       
         
    if(!empty($_POST['refcode']) && strlen($_POST['refcode']) == 8){
        $referal_code = $_POST['refcode'];
    }else{
        $referal_code =  crypto_string("ABCDEFGHIJKLMNOPQRSTUVWXYZ",4);
        $referal_code .=  crypto_string("123456789",4);

    }
    

    //check database to see if generated code already exists
    $query = sprintf('SELECT * FROM %stbl_drivers WHERE referal_code = "%s"',DB_TBL_PREFIX,$referal_code);

    if($result = mysqli_query($GLOBALS['DB'], $query)){
        if(mysqli_num_rows($result)){
    
            continue; //found in db? iterate loop; try again.      
        
        }else{
            break; //found? ok stop loop
        }
    }else{
        break;
    }

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


/* var_dump($franchise_data);
var_dump($ride_data);
exit; */

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


if(empty($_POST)){ //let's render the add-new-ride page UI'
    
    ob_start();
    include('../../drop-files/templates/admin/adddrivertpl.php');
    $GLOBALS['admin_template']['page_content'] = ob_get_clean();
    include "../../drop-files/templates/admin/admin-interface.php";
    exit;


}

if(DEMO){
    $_SESSION['action_error'][] = "You are running in Demo mode. Action cannot be completed";
    header("location: ".SITE_URL."admin/all-drivers.php"); //Yes? then redirect 
    exit;
}

//var_dump($_POST);
//exit;

if(empty($_POST['image-data'])){
    $_SESSION['action_error'][] = "Please upload a passport photo for this driver";
}       
else{
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

if(empty($_POST['carcity'])) {
    $_SESSION['action_error'][] = "Please select a city where this car will operate.";
} 

if(!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)){
    $_SESSION['action_error'][] = "Email is not a valid email format";
}
if(strlen($_POST['email'])>64){
    $_SESSION['action_error'][] = "Email is too long. Email must be lower than 64 characters";
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
$query = sprintf('SELECT driver_id,email,phone FROM %stbl_drivers WHERE email = "%s" OR phone="%s"', DB_TBL_PREFIX, mysqli_real_escape_string($GLOBALS['DB'], $_POST['email']),mysqli_real_escape_string($GLOBALS['DB'], $_POST['phone']));


if($result = mysqli_query($GLOBALS['DB'], $query)){
    if(mysqli_num_rows($result)){
        $_SESSION['action_error'][]    = "Email or Phone already exists.";

    }
    
}
else{ //No record matching the USER ID was found in DB. Show view to notify user

    $_SESSION['action_error'][]    = "Database error!";
}


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




$driver_country = codeToCountryName(strtoupper($_POST['country-code']));

if(!$driver_country){
    $_SESSION['action_error'][]    = "Invalid country selected!";
}

/* echo mysqli_error($GLOBALS['DB']);
exit; */

if(!empty($_SESSION['action_error'])){

    @unlink($driver_passport_photo_path); //delete the old user uploaded file
    @unlink($driving_license_photo_path); //delete the old user uploaded file
    @unlink($road_worthiness_cert_path); //delete the old user uploaded file
   
    ob_start();
            include('../../drop-files/templates/admin/adddrivertpl.php'); 
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



$bank_code =  mysqli_real_escape_string($GLOBALS['DB'], $_POST['other-bank-code']);
$bank_name =  mysqli_real_escape_string($GLOBALS['DB'], $_POST['other-bank-name']);


//Store driver data to database
$query = sprintf('INSERT INTO %stbl_drivers(route_id,pwd_raw,password_hash,drv_address,email,firstname,lastname,phone,`state`,drv_country,car_plate_num,car_model,car_color,ride_id,referal_code,franchise_id,account_create_date,photo_file,bank_name,bank_acc_holder_name,bank_acc_num,bank_code,driver_commision,bank_swift_code,driving_license_file,road_worthiness_file,country_code,country_dial_code) VALUES'.
'("%d","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s")', 
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
    $referal_code,
    (int) $_POST['franchise'],
    gmdate('Y-m-d H:i:s', time()),
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
    "+" . mysqli_real_escape_string($GLOBALS['DB'], $_POST['country-dial-code'])

);

/* echo $query;
exit; */

    if(!$result = mysqli_query($GLOBALS['DB'], $query)){
        
        //echo mysqli_error($GLOBALS['DB']);
        
        $_SESSION['action_error'][] = "An error has occured. Could not save new driver form data to database. Ensure database connection is working";
    
        ob_start(); 
            include('../../drop-files/templates/admin/adddrivertpl.php');
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


    //Store activation code 

    //Store activation token information and driver ID  in database 
$query = sprintf('INSERT INTO %stbl_account_codes (user_id, code,user_type) VALUES ("%d","%s",1)',DB_TBL_PREFIX, $id, $_POST['act-pin']); 


if (!$result = mysqli_query($GLOBALS['DB'], $query)){
    
    $query = sprintf('DELETE FROM %stbl_drivers WHERE driver_id = "%d"', DB_TBL_PREFIX, $id); //delete already inserted record 
    $result = mysqli_query($GLOBALS['DB'], $query);

    @unlink($driver_passport_photo_path); //delete the old user uploaded file
    @unlink($driving_license_photo_path); //delete the old user uploaded file
    @unlink($road_worthiness_cert_path); //delete the old user uploaded file

    $_SESSION['action_error'][] = "An error has occured. Could not save new driver form data to database. Ensure database connection is working";
    
        ob_start(); 
            include('../../drop-files/templates/admin/adddrivertpl.php');
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

   
$_SESSION['action_success'][] = "The driver record was added successfully.";
header("location: ".SITE_URL."admin/all-drivers.php"); //Yes? then redirect
exit;






?>