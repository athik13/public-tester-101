<?php
session_start();
include("../../drop-files/lib/common.php");
include "../../drop-files/config/db.php";

$referal_code = "";
$photo_type = "";
$user_photo = "";

if(isset($_SESSION['expired_session'])){
    header("location: ".SITE_URL."login.php?timeout=1");
    exit;
}

if(!(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == 1)){ //if user is not logged in run this code
  header("location: ".SITE_URL."login.php"); //Yes? then redirect user to the login page
  exit;
}

if($_SESSION['account_type'] != 3){ ////if user is not an admin or dispatcher
    $_SESSION['action_error'][] = "Access Denied!";
    header("location: ".SITE_URL."admin/index.php"); //Yes? then redirect user to the login page
    exit;
}

$GLOBALS['admin_template']['page_title'] = "<i class='fa fa-users'></i> Add Customer"; //Set the title of the page on the admin interface
$GLOBALS['admin_template']['active_menu'] = "customers"; //Set the appropriate menu item active
$GLOBALS['admin_template']['active_sub_menu'] = "customer-new"; //Set the appropriate menu item active





for ($x = 0;$x < 10;$x++){ //try to generate a unique code for a referal code
       
         
    if(!empty($_POST['refcode']) && strlen($_POST['refcode']) == 8){
        $referal_code = $_POST['refcode'];
    }else{
        $referal_code =  crypto_string("ABCDEFGHIJKLMNOPQRSTUVWXYZ",4);
        $referal_code .=  crypto_string("123456789",4);
    }
    

    //check database to see if generated code already exists
    $query = sprintf('SELECT * FROM %stbl_users WHERE referal_code = "%s"',DB_TBL_PREFIX,$referal_code);

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


if(empty($_POST)){ //let's render the add-new-franchise page UI'

    
    
    ob_start();
    include('../../drop-files/templates/admin/addcustomertpl.php');
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


      

if(empty($_POST['image-data'])){
    $_SESSION['action_error'][] = "Please upload a passport photo for this customer";
}       
else{
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
   
}





/* var_dump($_POST);
exit; */

$_POST['firstname'] = str_replace(" ","",$_POST['firstname']); //remove spaces
$_POST['lastname'] = str_replace(" ","",$_POST['lastname']); //remove spaces



if(empty($_POST['firstname'])) {
    $_SESSION['action_error'][] = "Please enter your first name";
    
} 

if(strlen($_POST['firstname']) < 2){
    $_SESSION['action_error'][] = "Your firstname is too short";            
}

if(preg_match('/[^a-z]/i', $_POST['firstname'])){
    //$_SESSION['action_error'][] = "Your first name must contain only alphabetical characters";            
}

       
if(empty($_POST['lastname'])) {
   $_SESSION['action_error'][] = "Please enter your lastname";
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

if(!empty($_SESSION['action_error'])){
    @unlink($user_photo); //delete the old user uploaded file
   
    ob_start();
    include('../../drop-files/templates/admin/addcustomertpl.php'); 
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

$query = sprintf('SELECT user_id,email, phone FROM %stbl_users WHERE email = "%s" OR phone="%s"', DB_TBL_PREFIX, mysqli_real_escape_string($GLOBALS['DB'], $_POST['email']),mysqli_real_escape_string($GLOBALS['DB'], $_POST['phone']));



if($result = mysqli_query($GLOBALS['DB'], $query)){
    if(mysqli_num_rows($result)){

        $row = mysqli_fetch_assoc($result);
        if($row['email'] == mysqli_real_escape_string($GLOBALS['DB'], $_POST['email'])){
            $_SESSION['action_error'][] = "The email address already exists. Please use a different email address";
        }elseif($row['country_dial_code'].$row['phone'] == "+". mysqli_real_escape_string($GLOBALS['DB'], $_POST['country_dial_code']) . mysqli_real_escape_string($GLOBALS['DB'], $_POST['phone'])){
            $_SESSION['action_error'][] = "The phone number already exists. Please use a different phone number";
        }else{
            $_SESSION['action_error'][] = "The email address or phone number already exists. Please use a different email address or phone number";
        }      
        
       
    }
}else{

    $_SESSION['action_error'][] = "Error connecting to database. Please contact the administrator.";
    
}

$customer_country = codeToCountryName(strtoupper($_POST['country-code']));

if(!$customer_country){
    $_SESSION['action_error'][]    = "Invalid country selected!";
}



if(!empty($_SESSION['action_error'])){
    @unlink($user_photo); //delete the old user uploaded file
   
    ob_start();
    include('../../drop-files/templates/admin/addcustomertpl.php'); 
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


$activateacc = !empty($_POST['activateacc']) ? 1 : 0;

//OK, all good. lets store the registrant form data in the database
$verify_reg  = 0;
$query = sprintf('INSERT INTO %stbl_users (firstname, lastname, email, phone, pwd_raw, password_hash, account_create_date,referal_code,is_activated,photo_file,country_code,country_dial_code,country) VALUES'.
'("%s","%s","%s","%s","%s","%s","%s","%s","%d","%s","%s","%s","%s")', 
    DB_TBL_PREFIX, 
    mysqli_real_escape_string($GLOBALS['DB'], ucfirst(strtolower(strip_tags($_POST['firstname'])))),
    mysqli_real_escape_string($GLOBALS['DB'], ucfirst(strtolower(strip_tags($_POST['lastname'])))),
    mysqli_real_escape_string($GLOBALS['DB'], strip_tags($_POST['email'])),
    mysqli_real_escape_string($GLOBALS['DB'], strip_tags($_POST['phone'])),
    mysqli_real_escape_string($GLOBALS['DB'], $_POST['password']),
    password_hash(mysqli_real_escape_string($GLOBALS['DB'], $_POST['password']), PASSWORD_DEFAULT),
    gmdate('Y-m-d H:i:s', time()),
    $referal_code,
    $activateacc,
    $user_photo,
    mysqli_real_escape_string($GLOBALS['DB'], $_POST['country-code']),
    "+" . mysqli_real_escape_string($GLOBALS['DB'], $_POST['country-dial-code']),
    $customer_country
);


if(! $result = mysqli_query($GLOBALS['DB'], $query)){
    echo mysqli_error($GLOBALS['DB']);
    
    $_SESSION['action_error'][] = "An error has occured. Could not save new customer form data to database. Ensure database connection is working";
    @unlink($user_photo); //delete the old user uploaded file
    ob_start();
    include('../../drop-files/templates/admin/addcustomertpl.php'); 
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


    $query = sprintf('INSERT INTO %stbl_account_codes (user_id, code,user_type) VALUES ("%d","%s",0)',DB_TBL_PREFIX, $id, $_POST['act-pin']); 
    $result = mysqli_query($GLOBALS['DB'], $query);


   
    $_SESSION['action_success'][] = "The customer record was created successfully.";
    header("location: ".SITE_URL."admin/all-customers.php"); //Yes? then redirect
    exit;



?>