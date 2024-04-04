<?php
session_start();
include("../../drop-files/lib/common.php");
include "../../drop-files/config/db.php";
$photo_type = "";

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

$GLOBALS['admin_template']['page_title'] = "<i class='fa fa-car'></i> Add Ride"; //Set the title of the page on the admin interface
$GLOBALS['admin_template']['active_menu'] = "rides"; //Set the appropriate menu item active
$GLOBALS['admin_template']['active_sub_menu'] = "rides-new"; //Set the appropriate menu item active



if(empty($_POST)){ //let's render the add-new-ride page UI'
    
    ob_start();
    include('../../drop-files/templates/admin/addridetpl.php');
    $GLOBALS['admin_template']['page_content'] = ob_get_clean();
    include "../../drop-files/templates/admin/admin-interface.php";
    exit;


}

if(DEMO){
    $_SESSION['action_error'][] = "You are running in Demo mode. Action cannot be completed";
    header("location: ".SITE_URL."admin/all-rides.php"); //Yes? then redirect 
    exit;
}



/* var_dump($_POST);
exit; */


if ($_FILES['rideimage']['error'] != 0){ 
    $_SESSION['action_error'][] = "Please upload an image for this ride type";
 }
 else{
         //user uploaded a file
     $fileType = exif_imagetype($_FILES['rideimage']['tmp_name']); //check the type of file uploaded
     if(! ($fileType == IMAGETYPE_JPEG || $fileType == IMAGETYPE_PNG)){
        $_SESSION['action_error'][] = "The image uploaded is not of valid type. Please upload a JPG or PNG image";
     }
     else{
             $photo_type = $fileType === IMAGETYPE_JPEG ? ".jpg" : ".png";
             list($width, $height) = getimagesize($_FILES['rideimage']['tmp_name']);

             if($width > 400 || $height >  400) //ensure image size is within limit.
             $_SESSION['action_error'][] = "The resolution of the image is " . $width . " X " . $height .". image must be less than 400 x 400";

                                             
         }
   
                  
    
 } 



if(empty($_POST['ride-type'])){

    $_SESSION['action_error'][]    = "Please enter a fanchise name";
}


if(empty($_POST['ride-type'])){

    $_SESSION['action_error'][]    = "Please enter a ride description";
}


//check if ride name already exists

$query = sprintf('SELECT ride_type FROM %stbl_rides WHERE ride_type = "%s"', DB_TBL_PREFIX, mysqli_real_escape_string($GLOBALS['DB'], $_POST['ride-type'])); //Get required user information from DB


if($result = mysqli_query($GLOBALS['DB'], $query)){
    if(mysqli_num_rows($result)){
        $_SESSION['action_error'][]    = "Ride type already exists. Please use another name";

    }
    
}
else{ //No record matching the USER ID was found in DB. Show view to notify user

    $_SESSION['action_error'][]    = "Database error!";
}



/* echo mysqli_error($GLOBALS['DB']);
exit; */

if(!empty($_SESSION['action_error'])){
   
    ob_start();
            include('../../drop-files/templates/admin/addridetpl.php'); 
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


$uploaddir = '../img/ride_imgs/';
$uploadfile =  md5(random_text(20));
$ride_image = $uploaddir.$uploadfile.$photo_type;

$ride_avail = isset($_POST['ride-avail']) ? 1 : 0;

if (!move_uploaded_file($_FILES['rideimage']['tmp_name'], $ride_image)) {
    $_SESSION['action_error'][]    = "Error uploading ride image!";                   
}


if(!empty($_SESSION['action_error'])){
   
    ob_start();
            include('../../drop-files/templates/admin/addridetpl.php'); 
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




//Store ride data to database
$query = sprintf('INSERT INTO %stbl_rides(ride_type,ride_desc,ride_img,avail) VALUES'.
'("%s","%s","%s","%d")', 
    DB_TBL_PREFIX, 
    mysqli_real_escape_string($GLOBALS['DB'], $_POST['ride-type']),
    htmlspecialchars(mysqli_real_escape_string($GLOBALS['DB'], $_POST['ride-desc'])),
    $ride_image,
    $ride_avail

);


    if(!$result = mysqli_query($GLOBALS['DB'], $query)){
        //echo mysqli_error($GLOBALS['DB']);
        
        $_SESSION['action_error'][] = "An error has occured. Could not save new ride form data to database. Ensure database connection is working";
    
        ob_start(); 
            include('../../drop-files/templates/admin/addridetpl.php');
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




   
        $_SESSION['action_success'][] = "The ride record was added successfully.";
        header("location: ".SITE_URL."admin/all-rides.php"); //Yes? then redirect
        exit;






?>