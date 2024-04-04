<?php
session_start();
include("../../drop-files/lib/common.php");
include ("../../drop-files/config/db.php");
 

$month_names = array("January","February","March","April","May","June","July","August","September","October","November","December");
$oldest_reg_year = "";
$year_selected = 2018;
$month_selected = 01;
$num_of_users_data = [];


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


$GLOBALS['admin_template']['page_title'] = "<i class='fa fa-line-chart'></i> Drivers Report"; //Set the title of the page on the admin interface
$GLOBALS['admin_template']['active_menu'] = "reports"; //Set the appropriate menu item active
$GLOBALS['admin_template']['active_sub_menu'] = "reports_drv"; //Set the appropriate menu item active

//get all available cities
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


//Get oldest registration date from DB

$query = sprintf('SELECT YEAR(MIN(account_create_date)) AS oldest_reg_year FROM %stbl_users', DB_TBL_PREFIX);
        $row = array();
        if($result = mysqli_query($GLOBALS['DB'], $query)){
            if(mysqli_num_rows($result)){
                $row = mysqli_fetch_assoc($result);
                $oldest_reg_year = $row['oldest_reg_year'];            
            }
            mysqli_free_result($result);
        }  



$year_selected = !empty($_POST['reg-period-year']) ? (int) $_POST['reg-period-year'] : date('Y');
$month_selected = !empty($_POST['reg-period-month']) ? (int) $_POST['reg-period-month'] : date('m');
$city_selected = !empty($_POST['reg-city']) ? (int) $_POST['reg-city'] : (!empty($inter_city_routes) ? $inter_city_routes[0]['id'] : 0);

$month_trend_data = getRegTrendMonth($month_selected,$year_selected,$city_selected);

if(empty($_POST)){ //let's render the default users data analytics page UI'
    
    
    
    $total_num_of_users = getNumVerUsers();
    
    ob_start(); 
    include('../../drop-files/templates/admin/driversreporttpl.php');
    $pageContent = ob_get_clean();
    $GLOBALS['admin_template']['page_content'] = $pageContent;
    include "../../drop-files/templates/admin/admin-interface.php";
    exit;



}











$total_num_of_users = getNumVerUsers();

ob_start();
include('../../drop-files/templates/admin/driversreporttpl.php');
$pageContent = ob_get_clean();
$GLOBALS['admin_template']['page_content'] = $pageContent;
include "../../drop-files/templates/admin/admin-interface.php";



function getRegTrendMonth($month,$year,$city){ //Function returns number of registrations for a month on a selected year
        $number_of_users_reg_trend = array();
        $month_formated = str_pad($month,2,"0",STR_PAD_LEFT);
        $num_of_month_days = date("t",strtotime($year."-".$month_formated));
        $start_date = $year."-".$month_formated."-01";
        $end_date = $year."-".$month_formated."-".$num_of_month_days;
        

     
     
    //Get data from database
    $query = sprintf('SELECT COUNT(*), DATE(%1$stbl_drivers.account_create_date) AS date_string FROM %1$stbl_drivers
    WHERE %1$stbl_drivers.route_id = %4$d AND (%1$stbl_drivers.account_create_date BETWEEN "%3$s" AND "%2$s") GROUP BY DAY(%1$stbl_drivers.account_create_date)', DB_TBL_PREFIX,$end_date,$start_date,$city);

    
    

        if($result = mysqli_query($GLOBALS['DB'], $query)){
            if(mysqli_num_rows($result)){
                while($row = mysqli_fetch_assoc($result)){
                    $number_of_users_reg_trend[] = $row;
                }
            
            }
            mysqli_free_result($result);
        }
	

    $reg_trend_dates_array = array();
    $reg_trend_dates_num_array = array();

    
     for($i = 0; $i < $num_of_month_days; $i++){ //Create array indexes using the dates to display records of
            $day_val = $num_of_month_days - $i;
            $day_formated = $day_val < 10 ? "0" . $day_val : $day_val;
            $date_formated = $year."-".$month_formated."-".$day_formated;
           $reg_trend_dates_array[$date_formated]['date'] = '"'.$date_formated.'"';     
           $reg_trend_dates_array[$date_formated]['num_of_users'] = 0;
           
     }

    $month_total_regs = 0;
     foreach($number_of_users_reg_trend as $numberofusersregtrend){ //Load database record values into appropriate array indices

       if(isset($reg_trend_dates_array[$numberofusersregtrend['date_string']])){
          $reg_trend_dates_array[$numberofusersregtrend['date_string']]['num_of_users'] += (int) $numberofusersregtrend['COUNT(*)'];
          $month_total_regs += (int) $numberofusersregtrend['COUNT(*)'];
       }

       
    }

    $reg_trend_dates_array['month_total'] = $month_total_regs;
    
    return $reg_trend_dates_array;


}













function getNumVerUsers(){ //Function returns number of registered users on platform
    $number_of_users = 0;
    //Get data from database
    $query = sprintf('SELECT COUNT(*) FROM %1$stbl_drivers', DB_TBL_PREFIX);

        if($result = mysqli_query($GLOBALS['DB'], $query)){
            if(mysqli_num_rows($result)){
                while($row = mysqli_fetch_assoc($result)){
                    $number_of_users = $row['COUNT(*)'];
                }
            
            }
            mysqli_free_result($result);
        }  

    
    
    return $number_of_users;


}














?>