<?php
session_start();
include("../../drop-files/lib/common.php");
include "../../drop-files/config/db.php";

$id = 0;
$franchise_data = [];

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

$GLOBALS['admin_template']['page_title'] = "<i class='fa fa-briefcase'></i> Edit Franchise"; //Set the title of the page on the admin interface
$GLOBALS['admin_template']['active_menu'] = "franch"; //Set the appropriate menu item active
$GLOBALS['admin_template']['active_sub_menu'] = "franch-all"; //Set the appropriate menu item active


if(!empty($_POST['franch-id'])){
    $id = (int) $_POST['franch-id'];
 }
elseif(!empty($_GET['id'])) {
        $id = (int) $_GET['id'] ;
 }




$query = sprintf('SELECT * FROM %stbl_franchise WHERE id = "%d"', DB_TBL_PREFIX, $id); //Get required user information from DB


if($result = mysqli_query($GLOBALS['DB'], $query)){
    if(mysqli_num_rows($result)){
        $franchise_data = mysqli_fetch_assoc($result);
                
    }else{
        $_SESSION['action_error'][]    = "Invalid franchise record.";
    }
    
}
else{ //No record matching the USER ID was found in DB. Show view to notify user

    $_SESSION['action_error'][]    = "Database error!";
}




if(!empty($_SESSION['action_error'])){
   
    ob_start();
    include('../../drop-files/templates/admin/editfranchisetpl.php'); 
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
        header("location: ".SITE_URL."admin/all-franchise.php"); //Yes? then redirect 
        exit;
    }

    if($id == 1){
        $_SESSION['action_error'][] = "You cannot delete the default franchise. Edit the franchise instead.";
        header("location: ".SITE_URL."admin/all-tariffs.php"); //Yes? then redirect 
        exit;
    }
    exit; //avoid deleting of franchise
    //Ensure that course exists on DB
    $query = sprintf('SELECT id FROM %stbl_franchise WHERE id = "%d"',DB_TBL_PREFIX, $id );
           if($result = mysqli_query($GLOBALS['DB'], $query)){
       
                if(!mysqli_num_rows($result)){

                    $_SESSION['action_error'][] = "Could not delete the requested record. The record was not found in the database";
                    header("location: ".SITE_URL."admin/all-franchise.php"); //Yes? then redirect
                    exit;           

                }   
           mysqli_free_result($result);
       }  

  
   //then delete record
       $query = sprintf('DELETE FROM %stbl_franchise WHERE id = "%d"', DB_TBL_PREFIX, $id); 
       if(!$result = mysqli_query($GLOBALS['DB'], $query)){ 
            
            $_SESSION['action_error'][] = "An error occured while trying to delete franchise record from the database.";
            header("location: ".SITE_URL."admin/all-franchise.php"); //Yes? then redirect
            exit; 

       }

       //move all former franchise drivers to the default company franchise
       $query = sprintf('UPDATE %stbl_drivers SET franchise_id = 1 WHERE franchise_id = %d', DB_TBL_PREFIX, $id);
       $result = mysqli_query($GLOBALS['DB'], $query);
       

        

        $_SESSION['action_success'][] = "The franchise record was successfully deleted.";
        header("location: ".SITE_URL."admin/all-franchise.php"); //Yes? then redirect
        exit;



    }


if(empty($_POST)){ //let's render the editfranchise page UI'
    
    ob_start();
    include('../../drop-files/templates/admin/editfranchisetpl.php');
    $pageContent = ob_get_clean();
    $GLOBALS['admin_template']['page_content'] = $pageContent;
    include "../../drop-files/templates/admin/admin-interface.php";
    exit;

}


if(DEMO){
    $_SESSION['action_error'][] = "You are running in Demo mode. Action cannot be completed";
    header("location: ".SITE_URL."admin/all-franchise.php"); //Yes? then redirect 
    exit;
}


//var_dump($_POST);

if(empty($_POST['franch-name'])){

    $_SESSION['action_error'][]    = "Please enter a fanchise name";
}


if(empty($_POST['franch-desc'])){

    $_SESSION['action_error'][]    = "Please enter a franchise description";
}


if($franchise_data['id'] = 1){
    
}


if(!filter_var($_POST['franch-email'], FILTER_VALIDATE_EMAIL)){
    $_SESSION['action_error'][] = "Email is not a valid email format";
}

if(strlen($_POST['franch-email'])>64){
    $_SESSION['action_error'][] = "Email is too long. Email must be lower than 64 characters";
}
if(strlen($_POST['franch-phone']) > 20) {
    $_SESSION['action_error'][] = "Phone number is too long";
} 
if(strlen($_POST['franch-phone']) < 5) {
$_SESSION['action_error'][] = "Phone number is too short";
} 



if((strlen($_POST['franch-pwd']) < 8 )){
   $_SESSION['action_error'][] = "Password must not be less than eight characters";
}
if((strlen($_POST['franch-pwd']) > 15 )){
   $_SESSION['action_error'][] = "Password is too long. Password must not be more than 15 characters";
}


//check if franchise name already exists

$query = sprintf('SELECT franchise_name, franchise_email, franchise_phone FROM %stbl_franchise WHERE id != %d AND (franchise_name = "%s" OR franchise_phone = "%s" OR franchise_email = "%s") LIMIT 1', DB_TBL_PREFIX, $id,mysqli_real_escape_string($GLOBALS['DB'], $_POST['franch-name']), mysqli_real_escape_string($GLOBALS['DB'], $_POST['franch-phone']), mysqli_real_escape_string($GLOBALS['DB'], $_POST['franch-email'])); //Get required user information from DB


if($result = mysqli_query($GLOBALS['DB'], $query)){
    if(mysqli_num_rows($result)){
        $row = mysqli_fetch_assoc($result);
        if($_POST['franch-name'] == $row['franchise_name']){
            $_SESSION['action_error'][]    = "Franchise name already exists. Please use another name";
        }elseif($_POST['franch-phone'] == $row['franchise_phone']){
            $_SESSION['action_error'][]    = "Franchise phone number already exists. Please use another name";
        }elseif($_POST['franch-email'] == $row['franchise_email']){
            $_SESSION['action_error'][]    = "Franchise email already exists. Please use another name";
        }else{
            $_SESSION['action_error'][]    = "Franchise name,email or phone number already exists. Please use another name";
        }        


    }
    
}
else{ //No record matching the USER ID was found in DB. Show view to notify user

    $_SESSION['action_error'][]    = "Database error!";
}


if(!empty($_SESSION['action_error'])){
   
    ob_start();
    include('../../drop-files/templates/admin/editfranchisetpl.php'); 
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

//Update franchise data
$query = sprintf('UPDATE %stbl_franchise SET franchise_email = "%s",franchise_phone = "%s",pwd_raw = "%s",password_hash = "%s",franchise_name = "%s" ,franchise_desc = "%s",bank_name = "%s",bank_acc_holder_name = "%s",bank_acc_num = "%s",bank_code = "%s",franchise_commision = "%s", bank_swift_code = "%s" WHERE id = "%d"', 
                    DB_TBL_PREFIX, 
                    mysqli_real_escape_string($GLOBALS['DB'], $_POST['franch-email']),
                    mysqli_real_escape_string($GLOBALS['DB'], $_POST['franch-phone']),
                    mysqli_real_escape_string($GLOBALS['DB'], $_POST['franch-pwd']),
                    password_hash(mysqli_real_escape_string($GLOBALS['DB'], $_POST['franch-pwd']), PASSWORD_DEFAULT),
                    mysqli_real_escape_string($GLOBALS['DB'], $_POST['franch-name']),
                    mysqli_real_escape_string($GLOBALS['DB'], $_POST['franch-desc']),
                    $bank_name,
                    mysqli_real_escape_string($GLOBALS['DB'], $_POST['bank-acc-holders-name']),
                    mysqli_real_escape_string($GLOBALS['DB'], $_POST['bank-details-acc-num']),
                    $bank_code,
                    mysqli_real_escape_string($GLOBALS['DB'], $_POST['commission']),
                    mysqli_real_escape_string($GLOBALS['DB'], $_POST['bank-details-swift']),
                    $id
                );


              

if(! $result = mysqli_query($GLOBALS['DB'], $query)){
    //echo mysqli_error($GLOBALS['DB']);
    
    $_SESSION['action_error'][] = "An error has occured. Could not update franchise record on database. Ensure database connection is working";

    ob_start();
        include('../../drop-files/templates/admin/editfranchisetpl.php'); 
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
    


   
$_SESSION['action_success'][] = "The franchise record was successfully updated.";
header("location: ".SITE_URL."admin/all-franchise.php"); //Yes? then redirect
exit;





?>