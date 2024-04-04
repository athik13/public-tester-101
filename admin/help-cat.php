<?php
session_start();
include("../../drop-files/lib/common.php");
include ("../../drop-files/config/db.php");


define('ITEMS_PER_PAGE', 20); //define constant for number of items to display per page


$help_categories_page_items = [];
$number_of_help_categories = 0;
$help_categories_help_topics_count = [];

if(isset($_SESSION['expired_session'])){
    header("location: ".SITE_URL."login.php?timeout=1");
    exit;
}

if(!(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == 1)){ //if user is not logged in run this code
  header("location: ".SITE_URL."login.php"); //Yes? then redirect user to the login page
  exit;
}

if($_SESSION['account_type'] != 3){ ////if user is not an admin
    $_SESSION['action_error'][] = "Access Denied!";
    header("location: ".SITE_URL."admin/index.php"); //Yes? then redirect user to the login page
    exit;
}


$GLOBALS['admin_template']['page_title'] = "<i class='fa fa-life-buoy'></i> Help Categories"; //Set the title of the page on the admin interface
$GLOBALS['admin_template']['active_menu'] = "app-info"; //Set the appropriate menu item active
$GLOBALS['admin_template']['active_sub_menu'] = "app-help-cat"; //Set the appropriate menu item active



if(isset($_GET['action']) && $_GET['action'] == 'del'){
    if(DEMO){
        $_SESSION['action_error'][] = "You are running in Demo mode. Action cannot be completed";
        header("location: ".SITE_URL."admin/help-cat.php"); //Yes? then redirect 
        exit;
    }
    $item_id = !empty($_GET['id']) ? (int) $_GET['id'] : 0;

    $query = sprintf('DELETE FROM %stbl_help_cat WHERE id = %d AND id != 1', DB_TBL_PREFIX,$item_id);

    if(!$result = mysqli_query($GLOBALS['DB'], $query)){
        $_SESSION['action_error'][] = "An error has occured. Could not delete help category from database. Ensure database connection is working";
    }else{
        $_SESSION['action_success'][] = "Help category was successfully deleted.";

        $query = sprintf('UPDATE %stbl_appinfo_pages SET cat_id = %d WHERE cat_id = %d AND `type` = 1',
            DB_TBL_PREFIX,
            1,
            $item_id
        );
        $result = mysqli_query($GLOBALS['DB'], $query);
    }

    

}


if(isset($_POST['savecat'])){
    if(DEMO){
        $_SESSION['action_error'][] = "You are running in Demo mode. Action cannot be completed";
        header("location: ".SITE_URL."admin/help-cat.php"); //Yes? then redirect 
        exit;
    }

    $show_web = isset($_POST['show-web']) ? 1 : 0;
    $show_rider = isset($_POST['show-rider']) ? 1 : 0;
    $show_driver = isset($_POST['show-driver']) ? 1 : 0;
    
    
    $query = sprintf('INSERT INTO %stbl_help_cat (title,`desc`,show_web,show_rider,show_driver) VALUES 
    ("%s","%s","%d","%d","%d")',
        DB_TBL_PREFIX,
        mysqli_real_escape_string($GLOBALS['DB'], $_POST['helpcat-title']),
        mysqli_real_escape_string($GLOBALS['DB'], $_POST['helpcat-desc']),
        $show_web,
        $show_rider,
        $show_driver,
        1
    );

    

    if(! $result = mysqli_query($GLOBALS['DB'], $query)){
        //echo mysqli_error($GLOBALS['DB']);
        
        $_SESSION['action_error'][] = "An error has occured. Could not save new help category to database. Ensure database connection is working";
    
            
    }else{

        $_SESSION['action_success'][] = "Help category added successfully.";
    }

}



if(isset($_POST['editcat'])){
    if(DEMO){
        $_SESSION['action_error'][] = "You are running in Demo mode. Action cannot be completed";
        header("location: ".SITE_URL."admin/help-cat.php"); //Yes? then redirect 
        exit;
    }
    $id = (int) $_POST['ehelpcat-id'];
    $show_web = isset($_POST['eshow-web']) ? 1 : 0;
    $show_rider = isset($_POST['eshow-rider']) ? 1 : 0;
    $show_driver = isset($_POST['eshow-driver']) ? 1 : 0;
    
    
    $query = sprintf('UPDATE %stbl_help_cat SET title = "%s",`desc` = "%s",show_web = %d, show_rider = %d,show_driver = %d, date_modified = "%s" WHERE id = %d',
        DB_TBL_PREFIX,
        mysqli_real_escape_string($GLOBALS['DB'], $_POST['ehelpcat-title']),
        mysqli_real_escape_string($GLOBALS['DB'], $_POST['ehelpcat-desc']),
        $show_web,
        $show_rider,
        $show_driver,
        gmdate('Y-m-d H:i:s', time()),
        $id
    );

    if($result = mysqli_query($GLOBALS['DB'], $query)){
        $_SESSION['action_success'][] = "Help category updated successfully.";
    }else{
        $_SESSION['action_error'][]    = "Error updating help category";
    } 
    
    
}


//get the number of help categories

$query = sprintf('SELECT COUNT(*) FROM %stbl_help_cat', DB_TBL_PREFIX);  //Get and count all data

//echo mysqli_error($GLOBALS['DB']);

if($result = mysqli_query($GLOBALS['DB'], $query)){
    if(mysqli_num_rows($result)){

       $row = mysqli_fetch_assoc($result);
          
      $number_of_help_categories = $row['COUNT(*)'];
         
     }
    mysqli_free_result($result);
}


if(isset($_GET['page'])){
    $page_number = (int) $_GET['page'];
}else{
    $page_number = 1;
}
    
$pages = ceil($number_of_help_categories / ITEMS_PER_PAGE) ;
if($page_number > $pages)$page_number = 1; 
if($page_number < 0)$page_number = 1; 
$offset = ($page_number - 1) * ITEMS_PER_PAGE;



//Get the number of help topics in each category

$query = sprintf('SELECT COUNT(%1$stbl_appinfo_pages.cat_id) AS help_topics_count, %1$stbl_help_cat.id FROM %1$stbl_help_cat 
INNER JOIN %1$stbl_appinfo_pages ON %1$stbl_appinfo_pages.cat_id = %1$stbl_help_cat.id
GROUP BY %1$stbl_appinfo_pages.cat_id', DB_TBL_PREFIX);


if($result = mysqli_query($GLOBALS['DB'], $query)){
  
    if(mysqli_num_rows($result)){
        while($row = mysqli_fetch_assoc($result)){
            $help_categories_help_topics_count[$row['id']] = $row;
        }
    
     }
    mysqli_free_result($result);
} 



//Get all help topics categories

$query = sprintf('SELECT * FROM %1$stbl_help_cat ORDER BY %1$stbl_help_cat.title ASC', DB_TBL_PREFIX);


if($result = mysqli_query($GLOBALS['DB'], $query)){
  
    if(mysqli_num_rows($result)){
        while($row = mysqli_fetch_assoc($result)){
            $help_categories_page_items[] = $row;
        }
    
     }
    mysqli_free_result($result);
} 



ob_start();
include "../../drop-files/templates/admin/helpcattpl.php";

if(!empty($_SESSION['action_success'])){
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

}elseif(!empty($_SESSION['action_error'])){
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
    
}


$GLOBALS['admin_template']['page_content'] = ob_get_clean();
include "../../drop-files/templates/admin/admin-interface.php";
exit;


























?>