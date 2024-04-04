<?php
session_start();
include("../../drop-files/lib/common.php");
include "../../drop-files/config/db.php";
$photo_type = "";
$img_uploaded = 0;

$id = 0;
$ride_data = [];

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

$GLOBALS['admin_template']['page_title'] = "<i class='fa fa-car'></i> Edit Car"; //Set the title of the page on the admin interface
$GLOBALS['admin_template']['active_menu'] = "rides"; //Set the appropriate menu item active
$GLOBALS['admin_template']['active_sub_menu'] = "rides-all"; //Set the appropriate menu item active


if(!empty($_POST['ride-id'])){
    $id = (int) $_POST['ride-id'];
 }
elseif(!empty($_GET['id'])) {
        $id = (int) $_GET['id'] ;
 }




$query = sprintf('SELECT * FROM %stbl_rides WHERE id = "%d"', DB_TBL_PREFIX, $id); //Get required user information from DB


if($result = mysqli_query($GLOBALS['DB'], $query)){
    if(mysqli_num_rows($result)){
        $ride_data = mysqli_fetch_assoc($result);
                
    }else{
        $_SESSION['action_error'][]    = "Invalid car record.";
    }
    
}
else{ //No record matching the USER ID was found in DB. Show view to notify user

    $_SESSION['action_error'][]    = "Database error!";
}




if(!empty($_SESSION['action_error'])){
   
    ob_start();
    include('../../drop-files/templates/admin/editridetpl.php'); 
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
    exit; //avoid deleting
    //Ensure that data exists on DB
    $ride_data = [];
    $query = sprintf('SELECT * FROM %stbl_rides WHERE id = "%d"',DB_TBL_PREFIX, $id );
           if($result = mysqli_query($GLOBALS['DB'], $query)){
                
                if(!mysqli_num_rows($result)){
                        $_SESSION['action_error'][] = "Could not delete the requested record. The record was not found in the database";
                        header("location: ".SITE_URL."admin/all-rides.php"); //Yes? then redirect
                        exit;
                    
                }

                $ride_data = mysqli_fetch_assoc($result);
           mysqli_free_result($result);
       }  

       

   //then delete record
       $query = sprintf('DELETE FROM %stbl_rides WHERE id != 1 AND id = "%d"', DB_TBL_PREFIX, $id); 
       if(!$result = mysqli_query($GLOBALS['DB'], $query)){ 
        $_SESSION['action_error'][] = "An error occured while trying to delete car record from the database.";
        header("location: ".SITE_URL."admin/all-rides.php"); //Yes? then redirect
        exit;
          
       }



       //delete record ride entry in ride-tariff table
       $query = sprintf('DELETE FROM %stbl_rides_tariffs WHERE ride_id = "%d"', DB_TBL_PREFIX, $id); 
       $result = mysqli_query($GLOBALS['DB'], $query);
       
       
       @unlink($ride_data['ride_img']); //delete ride image file
        
        $_SESSION['action_success'][] = "The car record was successfully deleted.";
        header("location: ".SITE_URL."admin/all-rides.php"); //Yes? then redirect
        exit;


        



    }


if(empty($_POST)){ //let's render the edit rides page UI'
    
    ob_start();
    include('../../drop-files/templates/admin/editridetpl.php');
    $pageContent = ob_get_clean();
    $GLOBALS['admin_template']['page_content'] = $pageContent;
    include "../../drop-files/templates/admin/admin-interface.php";
    exit;


}


if(DEMO){
    $_SESSION['action_error'][] = "You are running in Demo mode. Action cannot be completed";
    header("location: ".SITE_URL."admin/all-rides.php"); //Yes? then redirect 
    exit;
}


if ($_FILES['rideimage']['error'] != 0){
    $img_uploaded = 0;  
    //$_SESSION['action_error'][] = "Please upload an image for this ride type";
 }
 else{
    $img_uploaded = 1; 
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






//var_dump($_POST);

if(empty($_POST['ride-name'])){

    $_SESSION['action_error'][]    = "Please enter a car name";
}


if(empty($_POST['ride-desc'])){

    $_SESSION['action_error'][]    = "Please enter a car description";
}




if(!empty($_SESSION['action_error'])){
   
        ob_start();
        include('../../drop-files/templates/admin/editridetpl.php'); 
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



if($img_uploaded){
    
    $uploaddir = '../img/ride_imgs/';
    $uploadfile =  md5(random_text(20));
    $ride_image = $uploaddir.$uploadfile.$photo_type;
    if (!move_uploaded_file($_FILES['rideimage']['tmp_name'], $ride_image)) {
        $_SESSION['action_error'][]    = "Error changing car image!";                   
    }
}else{
    $ride_image = $ride_data['ride_img'];
}

$ride_avail = isset($_POST['ride-avail']) ? 1 : 0;




if(!empty($_SESSION['action_error'])){
   
    ob_start();
            include('../../drop-files/templates/admin/editridetpl.php'); 
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

//Update ride data
$query = sprintf('UPDATE %stbl_rides SET ride_type = "%s" ,ride_desc = "%s",ride_img = "%s", avail = "%d" WHERE id = "%d"', 
                    DB_TBL_PREFIX, 
                    mysqli_real_escape_string($GLOBALS['DB'], $_POST['ride-name']),
                    mysqli_real_escape_string($GLOBALS['DB'], $_POST['ride-desc']),
                    $ride_image,
                    $ride_avail,
                    $id
                );


              

    if(! $result = mysqli_query($GLOBALS['DB'], $query)){
        //echo mysqli_error($GLOBALS['DB']);
        
        $_SESSION['action_error'][] = "An error has occured. Could not update car record on database. Ensure database connection is working";
    
        ob_start();
            include('../../drop-files/templates/admin/editridetpl.php'); 
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
    
    if($img_uploaded){
        @unlink($ride_data['ride_img']); //delete old image file
    }
    
   
    $_SESSION['action_success'][] = "The car record was successfully updated.";
    header("location: ".SITE_URL."admin/all-rides.php"); //Yes? then redirect
    exit;





?>