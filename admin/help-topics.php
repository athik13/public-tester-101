<?php
session_start();
include("../../drop-files/lib/common.php");
include ("../../drop-files/config/db.php");


define('ITEMS_PER_PAGE', 20); //define constant for number of items to display per page


$help_topics_page_items = [];
$help_topics_categories = [];
$number_of_help_topics = 0;


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


$GLOBALS['admin_template']['page_title'] = "<i class='fa fa-life-buoy'></i> Help Topics"; //Set the title of the page on the admin interface
$GLOBALS['admin_template']['active_menu'] = "app-info"; //Set the appropriate menu item active
$GLOBALS['admin_template']['active_sub_menu'] = "app-help-topics"; //Set the appropriate menu item active



if(isset($_GET['action']) && $_GET['action'] == 'del'){
    if(DEMO){
        $_SESSION['action_error'][] = "You are running in Demo mode. Action cannot be completed";
        header("location: ".SITE_URL."admin/help-topics.php"); //Yes? then redirect 
        exit;
    }
    $item_id = !empty($_GET['id']) ? (int) $_GET['id'] : 0;
    $query = sprintf('DELETE FROM %stbl_appinfo_pages WHERE id = %d AND `type` = 1', DB_TBL_PREFIX,$item_id);

    if(!$result = mysqli_query($GLOBALS['DB'], $query)){
        $_SESSION['action_error'][] = "An error has occured. Could not delete help topic from database. Ensure database connection is working";
    }else{
        $_SESSION['action_success'][] = "Help topic was successfully deleted.";
    }    

}


if(isset($_POST['savetopic'])){

    if(DEMO){
        $_SESSION['action_error'][] = "You are running in Demo mode. Action cannot be completed";
        header("location: ".SITE_URL."admin/help-topics.php"); //Yes? then redirect 
        exit;
    }

    $show_web = isset($_POST['show-web']) ? 1 : 0;
    $show_rider = isset($_POST['show-rider']) ? 1 : 0;
    $show_driver = isset($_POST['show-driver']) ? 1 : 0;
    
    $cat_id =  !empty((int) $_POST['helptopic-cat']) ? (int) $_POST['helptopic-cat']: 1;

    $query = sprintf('INSERT INTO %stbl_appinfo_pages (title,excerpt,content,cat_id,show_web,show_rider,show_driver,`type`) VALUES 
    ("%s","%s","%s","%d","%d","%d","%d","%d")',
        DB_TBL_PREFIX,
        mysqli_real_escape_string($GLOBALS['DB'], $_POST['helptopic-title']),
        mysqli_real_escape_string($GLOBALS['DB'], $_POST['helptopic-excerpt']),
        mysqli_real_escape_string($GLOBALS['DB'], $_POST['helptopic-content']),
        $cat_id,
        $show_web,
        $show_rider,
        $show_driver,
        1
    );

    

    if(! $result = mysqli_query($GLOBALS['DB'], $query)){
        //echo mysqli_error($GLOBALS['DB']);
        
        $_SESSION['action_error'][] = "An error has occured. Could not save new help topic to database. Ensure database connection is working";
    
            
    }else{

        $_SESSION['action_success'][] = "Help topic added successfully.";
    }

}



if(isset($_POST['edittopic'])){

    if(DEMO){
        $_SESSION['action_error'][] = "You are running in Demo mode. Action cannot be completed";
        header("location: ".SITE_URL."admin/help-topics.php"); //Yes? then redirect 
        exit;
    }

    $id = (int) $_POST['ehelptopic-id'];
    $show_web = isset($_POST['eshow-web']) ? 1 : 0;
    $show_rider = isset($_POST['eshow-rider']) ? 1 : 0;
    $show_driver = isset($_POST['eshow-driver']) ? 1 : 0;
    
    $cat_id =  !empty((int) $_POST['ehelptopic-cat']) ? (int) $_POST['ehelptopic-cat']: 1;

    $query = sprintf('UPDATE %stbl_appinfo_pages SET title = "%s",excerpt = "%s",content = "%s",cat_id = %d,`type` = %d,show_web = %d, show_rider = %d,show_driver = %d, date_modified = "%s" WHERE id = %d AND `type` = 1',
        DB_TBL_PREFIX,
        mysqli_real_escape_string($GLOBALS['DB'], $_POST['ehelptopic-title']),
        mysqli_real_escape_string($GLOBALS['DB'], $_POST['ehelptopic-excerpt']),
        mysqli_real_escape_string($GLOBALS['DB'], $_POST['ehelptopic-content']),
        $cat_id,
        1,
        $show_web,
        $show_rider,
        $show_driver,
        gmdate('Y-m-d H:i:s', time()),
        $id
    );

    if($result = mysqli_query($GLOBALS['DB'], $query)){
        $_SESSION['action_success'][] = "Help topic updated successfully.";
    }else{
        $_SESSION['action_error'][]    = "Error updating help topic";
    } 
    
    
}


//get the number of help topics

$query = sprintf('SELECT COUNT(*) FROM %stbl_appinfo_pages WHERE `type` = 1', DB_TBL_PREFIX);  //Get and count all data

//echo mysqli_error($GLOBALS['DB']);

if($result = mysqli_query($GLOBALS['DB'], $query)){
    if(mysqli_num_rows($result)){

       $row = mysqli_fetch_assoc($result);
          
      $number_of_help_topics = $row['COUNT(*)'];
         
     }
    mysqli_free_result($result);
}


if(isset($_GET['page'])){
    $page_number = (int) $_GET['page'];
}else{
    $page_number = 1;
}
    
$pages = ceil($number_of_help_topics / ITEMS_PER_PAGE) ;
if($page_number > $pages)$page_number = 1; 
if($page_number < 0)$page_number = 1; 
$offset = ($page_number - 1) * ITEMS_PER_PAGE;



//Get all help topics

$query = sprintf('SELECT %1$stbl_appinfo_pages.*,%1$stbl_help_cat.title AS cat_title,%1$stbl_appinfo_pages.title AS help_topic_title FROM %1$stbl_appinfo_pages 
LEFT JOIN %1$stbl_help_cat ON  %1$stbl_help_cat.id = %1$stbl_appinfo_pages.cat_id
WHERE %1$stbl_appinfo_pages.type = 1 ORDER BY %1$stbl_help_cat.title ASC LIMIT %2$d,%3$d', DB_TBL_PREFIX, $offset, ITEMS_PER_PAGE);


if($result = mysqli_query($GLOBALS['DB'], $query)){
  
    if(mysqli_num_rows($result)){
        while($row = mysqli_fetch_assoc($result)){
            $help_topics_page_items[] = $row;
        }
    
     }
    mysqli_free_result($result);
} 


//Get all help topics categories

$query = sprintf('SELECT * FROM %1$stbl_help_cat ORDER BY %1$stbl_help_cat.id ASC', DB_TBL_PREFIX);


if($result = mysqli_query($GLOBALS['DB'], $query)){
  
    if(mysqli_num_rows($result)){
        while($row = mysqli_fetch_assoc($result)){
            $help_topics_categories[] = $row;
        }
    
     }
    mysqli_free_result($result);
} 

/* var_dump($franchise_page_items);
exit; */

ob_start();
include "../../drop-files/templates/admin/helptopicstpl.php";

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