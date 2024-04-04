<?php
session_start();
include("../../drop-files/lib/common.php");
include ("../../drop-files/config/db.php");
 

$month_names = array("January","February","March","April","May","June","July","August","September","October","November","December");
$oldest_reg_year = "2017";
$year_selected = 2018;
$month_selected = 01;

$franchise_data = [];
$num_of_users_data = [];
$sum_all_completed_booking_cost = 0;
$sum_all_completed_transaction_cost = 0;
$sum_all_wallet_funding = 0;
$sum_all_payouts = 0;

$sum_all_completed_booking_cost_month = 0;
$sum_all_completed_booking_earning_month = 0;
$sum_all_completed_transaction_cost_month = 0;
$sum_all_wallet_funding_month = 0;
$sum_all_payouts_month = 0;

$dates = [];
$dates_label = [];
$payment_data_bookings_completed_sort = [];
$payment_data_bookings_completed_earnings_sort = [];
$payment_data_online_sort = [];
$payment_data_wallet_fund_sort = [];
$payment_data_payouts_sort = [];
$default_currency_symbol = !empty($_SESSION['default_currency']) ? $_SESSION['default_currency']['symbol'] : "₦";



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


$GLOBALS['admin_template']['page_title'] = "<i class='fa fa-line-chart'></i> Payments Report"; //Set the title of the page on the admin interface
$GLOBALS['admin_template']['active_menu'] = "reports"; //Set the appropriate menu item active
$GLOBALS['admin_template']['active_sub_menu'] = "reports_payments"; //Set the appropriate menu item active





$year_selected = !empty($_POST['reg-period-year']) ? (int) $_POST['reg-period-year'] : date('Y');
$month_selected = !empty($_POST['reg-period-month']) ? (int) $_POST['reg-period-month'] : date('m');

//Get all franchises

$query = sprintf('SELECT * FROM %1$stbl_franchise ORDER BY franchise_name ASC', DB_TBL_PREFIX);


if($result = mysqli_query($GLOBALS['DB'], $query)){
  
    if(mysqli_num_rows($result)){
        while($row = mysqli_fetch_assoc($result)){
            $franchise_data[] = $row;
        }
    
     }
    mysqli_free_result($result);
}   


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



//Get total of all completed bookings

$query = sprintf('SELECT SUM(%1$stbl_bookings.paid_amount / %1$stbl_bookings.cur_exchng_rate) AS price FROM %1$stbl_bookings 
WHERE %1$stbl_bookings.status = 3', DB_TBL_PREFIX);


if($result = mysqli_query($GLOBALS['DB'], $query)){

    if(mysqli_num_rows($result)){
        $row = mysqli_fetch_assoc($result);
        if(!empty($row['price'])){
            $sum_all_completed_booking_cost = $row['price'];        
        }
        mysqli_free_result($result);
    }
} 


//Get total of all succesful wallet funding

$query = sprintf('SELECT SUM(%1$stbl_wallet_transactions.amount / %1$stbl_wallet_transactions.cur_exchng_rate) AS price FROM %1$stbl_wallet_transactions 
WHERE %1$stbl_wallet_transactions.type = 0', DB_TBL_PREFIX);


if($result = mysqli_query($GLOBALS['DB'], $query)){

    if(mysqli_num_rows($result)){
        $row = mysqli_fetch_assoc($result);
        if(!empty($row['price'])){
            $sum_all_completed_transaction_cost = $row['price'];        
        }
        mysqli_free_result($result);
    }
} 



//Get total of all deposits for driver / customer wallet funding

$query = sprintf('SELECT SUM(%1$stbl_wallet_fund.fund_amount / %1$stbl_wallet_fund.cur_exchng_rate) AS price FROM %1$stbl_wallet_fund', DB_TBL_PREFIX);


if($result = mysqli_query($GLOBALS['DB'], $query)){

    if(mysqli_num_rows($result)){
        $row = mysqli_fetch_assoc($result);
        if(!empty($row['price'])){
            $sum_all_wallet_funding = $row['price'];        
        }
        mysqli_free_result($result);
    }
} 


//Get total of all payouts

$query = sprintf('SELECT SUM(%1$stbl_wallet_withdrawal.withdrawal_amount / %1$stbl_wallet_withdrawal.cur_exchng_rate) AS price FROM %1$stbl_wallet_withdrawal WHERE %1$stbl_wallet_withdrawal.request_status = 2', DB_TBL_PREFIX);


if($result = mysqli_query($GLOBALS['DB'], $query)){

    if(mysqli_num_rows($result)){
        $row = mysqli_fetch_assoc($result);
        if(!empty($row['price'])){
            $sum_all_payouts = $row['price'];        
        }
        mysqli_free_result($result);
    }
}







if(empty($_POST)){ //let's render the default users data analytics page UI'
    
    getPaymentTrendMonthCity($month_selected,$year_selected);   
    ob_start(); 
    include('../../drop-files/templates/admin/paymentsreporttpl.php');
    $pageContent = ob_get_clean();
    $GLOBALS['admin_template']['page_content'] = $pageContent;
    include "../../drop-files/templates/admin/admin-interface.php";
    exit;



}



$scope = (int) $_POST['scope'];

switch ($scope){

    case 1:
    getPaymentTrendMonthCity($month_selected,$year_selected);
    break;

    case 2:
    getPaymentTrendMonthCust($month_selected,$year_selected);
    break;


    case 3:
    getPaymentTrendMonthDrvr($month_selected,$year_selected);
    break;

    case 4:
    getPaymentTrendMonthFranch($month_selected,$year_selected);
    break;


    default:
    getPaymentTrendMonthCity($month_selected,$year_selected);
    break;


}





ob_start();
include('../../drop-files/templates/admin/paymentsreporttpl.php');
$pageContent = ob_get_clean();
$GLOBALS['admin_template']['page_content'] = $pageContent;
include "../../drop-files/templates/admin/admin-interface.php";
exit;



















function getPaymentTrendMonthCity($month,$year){ //Function returns number of registrations for a month on a selected year and city
    
    $payment_data_online = [];
    $payment_data_wallet_fund = [];
    $payment_data_payouts = [];
    
    global $dates;
    global $dates_label;
    global $payment_data_bookings_completed_sort;
    global $payment_data_online_sort;
    global $payment_data_wallet_fund_sort;
    global $payment_data_payouts_sort;

    global $sum_all_completed_booking_cost_month;
    global $sum_all_completed_transaction_cost_month;
    global $sum_all_wallet_funding_month;
    global $sum_all_payouts_month;
    
    $city_id = !empty($_POST['report-city']) ? (int) $_POST['report-city'] : (!empty($inter_city_routes) ? $inter_city_routes[0]['id'] : 0);
    
    $month_formated = str_pad($month,2,"0",STR_PAD_LEFT);
    $num_of_month_days = date("t",strtotime($year."-".$month_formated));
    $start_date = $year."-".$month_formated."-01";
    $end_date = $year."-".$month_formated."-".$num_of_month_days;

    
    for($i = 0; $i < $num_of_month_days; $i++){ //Create array indexes using the dates to display records of
        $day_val = $num_of_month_days - $i;
        $day_formated = $day_val < 10 ? "0" . $day_val : $day_val;
        $date_formated = $year."-".$month_formated."-".$day_formated;
        $dates[] = $date_formated;
        $dates_label[] = '"' . $date_formated . '"';     
        $payment_data_bookings_completed_sort[$date_formated] = "0";
        $payment_data_online_sort[$date_formated] = "0";
        $payment_data_wallet_fund_sort[$date_formated] = "0";
        $payment_data_payouts_sort[$date_formated] = "0";       
        
    }
    
    
    //Get total of all completed bookings for the period

    $query = sprintf('SELECT (%1$stbl_bookings.paid_amount / %1$stbl_bookings.cur_exchng_rate) AS price, DATE(%1$stbl_bookings.date_completed) AS date_string FROM %1$stbl_bookings 
    WHERE %1$stbl_bookings.status = 3 AND %1$stbl_bookings.route_id = %2$d AND %1$stbl_bookings.date_completed BETWEEN "%3$s" AND "%4$s"', DB_TBL_PREFIX, $city_id, $start_date, $end_date);
    
    
    if($result = mysqli_query($GLOBALS['DB'], $query)){
        if(mysqli_num_rows($result)){
            while($row = mysqli_fetch_assoc($result)){
                if(empty($row['date_string']))continue;
                $payment_data_bookings_completed_sort[$row['date_string']] += floattocurrency($row['price']);
                $sum_all_completed_booking_cost_month += $row['price'];
            }
        
        }
        mysqli_free_result($result);
    }

    

    
    
    
    //Get total of all succesful online wallet funding for the period

    $query = sprintf('SELECT %1$stbl_wallet_transactions.user_type,(%1$stbl_wallet_transactions.amount / %1$stbl_wallet_transactions.cur_exchng_rate) AS price, DATE(%1$stbl_wallet_transactions.transaction_date) AS date_string FROM %1$stbl_wallet_transactions 
    LEFT JOIN %1$stbl_users ON %1$stbl_users.user_id = %1$stbl_wallet_transactions.user_id AND %1$stbl_wallet_transactions.user_type = 0
    LEFT JOIN %1$stbl_drivers ON %1$stbl_drivers.driver_id = %1$stbl_wallet_transactions.user_id AND %1$stbl_wallet_transactions.user_type = 1
    WHERE %1$stbl_wallet_transactions.type = 0 AND (%1$stbl_users.route_id = %2$d OR %1$stbl_drivers.route_id = %2$d) AND %1$stbl_wallet_transactions.transaction_date BETWEEN "%3$s" AND "%4$s"', DB_TBL_PREFIX, $city_id,$start_date,$end_date);

    
 
    if($result = mysqli_query($GLOBALS['DB'], $query)){
        if(mysqli_num_rows($result)){
            while($row = mysqli_fetch_assoc($result)){
                if(empty($row['date_string']))continue;
                $payment_data_online_sort[$row['date_string']] += floattocurrency($row['price']);
                $sum_all_completed_transaction_cost_month += $row['price'];
            }
        
        }
        mysqli_free_result($result);
    }

    
    
    


    //Get total of all deposits for driver / customer wallet funding for the period

    $query = sprintf('SELECT (%1$stbl_wallet_fund.fund_amount / %1$stbl_wallet_fund.cur_exchng_rate) AS price, DATE(%1$stbl_wallet_fund.date_fund) AS date_string FROM %1$stbl_wallet_fund    
    LEFT JOIN %1$stbl_users ON %1$stbl_users.user_id = %1$stbl_wallet_fund.customer_id AND (%1$stbl_wallet_fund.fund_type = 2 OR %1$stbl_wallet_fund.fund_type = 3)
    LEFT JOIN %1$stbl_drivers ON %1$stbl_drivers.driver_id = %1$stbl_wallet_fund.driver_id AND %1$stbl_wallet_fund.fund_type = 1
    WHERE (%1$stbl_users.route_id = %2$d OR %1$stbl_drivers.route_id = %2$d) AND %1$stbl_wallet_fund.date_fund BETWEEN "%3$s" AND "%4$s" ', DB_TBL_PREFIX,$city_id,$start_date,$end_date);

    
    if($result = mysqli_query($GLOBALS['DB'], $query)){
        
        if(mysqli_num_rows($result)){
            while($row = mysqli_fetch_assoc($result)){
                if(empty($row['date_string']))continue;
                $payment_data_wallet_fund_sort[$row['date_string']] += floattocurrency($row['price']);
                $sum_all_wallet_funding_month += $row['price'];
            }
        
        }
        mysqli_free_result($result);
    }

    
    

    //Get total of all deposits for driver / customer wallet funding for the period

    $query = sprintf('SELECT (%1$stbl_wallet_withdrawal.withdrawal_amount / %1$stbl_wallet_withdrawal.cur_exchng_rate) AS price, DATE(%1$stbl_wallet_withdrawal.date_settled) AS date_string FROM %1$stbl_wallet_withdrawal 
    INNER JOIN %1$stbl_drivers ON %1$stbl_drivers.driver_id = %1$stbl_wallet_withdrawal.person_id AND %1$stbl_wallet_withdrawal.user_type = 0
    WHERE %1$stbl_drivers.route_id = %2$d AND %1$stbl_wallet_withdrawal.request_status = 2 AND %1$stbl_wallet_withdrawal.date_settled BETWEEN "%3$s" AND "%4$s"', DB_TBL_PREFIX, $city_id,$start_date,$end_date);

     
    if($result = mysqli_query($GLOBALS['DB'], $query)){
        if(mysqli_num_rows($result)){
            while($row = mysqli_fetch_assoc($result)){
                if(empty($row['date_string']))continue;
                $payment_data_payouts_sort[$row['date_string']] += floattocurrency($row['price']);
                $sum_all_payouts_month += $row['price'];
            }
        
        }
        mysqli_free_result($result);
    }
    
    


}



function getPaymentTrendMonthCust($month,$year){ //Function returns number of registrations for a month on a selected year

    $customer_id = (int) $_POST['customer-scope-id'];

    $payment_data_bookings_completed = [];
    $payment_data_online = [];
    $payment_data_wallet_fund = [];
    $payment_data_payouts = [];
    
    global $dates;
    global $dates_label;
    global $payment_data_bookings_completed_sort;
    global $payment_data_online_sort;
    global $payment_data_wallet_fund_sort;
    global $payment_data_payouts_sort;

    global $sum_all_completed_booking_cost_month;
    global $sum_all_completed_transaction_cost_month;
    global $sum_all_wallet_funding_month;
    global $sum_all_payouts_month;
  
    
    $month_formated = str_pad($month,2,"0",STR_PAD_LEFT);
    $num_of_month_days = date("t",strtotime($year."-".$month_formated));
    $start_date = $year."-".$month_formated."-01";
    $end_date = gmdate('Y-m-d', strtotime($start_date."+" . $num_of_month_days . " day" ));

    
    for($i = 0; $i < $num_of_month_days; $i++){ //Create array indexes using the dates to display records of
        $day_val = $num_of_month_days - $i;
        $day_formated = $day_val < 10 ? "0" . $day_val : $day_val;
        $date_formated = $year."-".$month_formated."-".$day_formated;
        $dates[] = $date_formated;
        $dates_label[] = '"' . $date_formated . '"';     
        $payment_data_bookings_completed_sort[$date_formated] = "0";
        $payment_data_online_sort[$date_formated] = "0";
        $payment_data_wallet_fund_sort[$date_formated] = "0";
        $payment_data_payouts_sort[$date_formated] = "---";       
        
    }

    $sum_all_payouts_month = "---";
    //Get total of all completed bookings for the period

    $query = sprintf('SELECT (%1$stbl_bookings.paid_amount / %1$stbl_bookings.cur_exchng_rate) AS price, DATE(%1$stbl_bookings.date_completed) AS date_string FROM %1$stbl_bookings 
    WHERE %1$stbl_bookings.user_id = %2$d AND %1$stbl_bookings.status = 3 AND %1$stbl_bookings.date_completed BETWEEN "%3$s" AND "%4$s"', DB_TBL_PREFIX, $customer_id, $start_date, $end_date);

     
    if($result = mysqli_query($GLOBALS['DB'], $query)){
        if(mysqli_num_rows($result)){
            while($row = mysqli_fetch_assoc($result)){
                if(empty($row['date_string']))continue;
                $payment_data_bookings_completed_sort[$row['date_string']] += floattocurrency($row['price']);
                $sum_all_completed_booking_cost_month += $row['price'];
            }
        
        }
        mysqli_free_result($result);
    }
    

    
    
    //Get total of all succesful online wallet funding for the period

    $query = sprintf('SELECT (%1$stbl_wallet_transactions.amount / %1$stbl_wallet_transactions.cur_exchng_rate) AS price, DATE(%1$stbl_wallet_transactions.transaction_date) AS date_string FROM %1$stbl_wallet_transactions 
    WHERE %1$stbl_wallet_transactions.user_id = %2$d AND %1$stbl_wallet_transactions.user_type = 0 AND %1$stbl_wallet_transactions.type = 0 AND %1$stbl_wallet_transactions.transaction_date BETWEEN "%3$s" AND "%4$s"', DB_TBL_PREFIX, $customer_id,$start_date,$end_date);

    
 
    if($result = mysqli_query($GLOBALS['DB'], $query)){
        if(mysqli_num_rows($result)){
            while($row = mysqli_fetch_assoc($result)){
                if(empty($row['date_string']))continue;
                $payment_data_online_sort[$row['date_string']] += floattocurrency($row['price']);
                $sum_all_completed_transaction_cost_month += $row['price'];
            }
        
        }
        mysqli_free_result($result);
    }

    


    //Get total of all deposits for driver / customer wallet funding for the period

    $query = sprintf('SELECT (%1$stbl_wallet_fund.fund_amount / %1$stbl_wallet_fund.cur_exchng_rate) AS price, DATE(%1$stbl_wallet_fund.date_fund) AS date_string FROM %1$stbl_wallet_fund    
    WHERE %1$stbl_wallet_fund.customer_id = %2$d AND %1$stbl_wallet_fund.fund_type = 2 AND %1$stbl_wallet_fund.date_fund BETWEEN "%3$s" AND "%4$s" ', DB_TBL_PREFIX,$customer_id,$start_date,$end_date);

    
    if($result = mysqli_query($GLOBALS['DB'], $query)){
        
        if(mysqli_num_rows($result)){
            while($row = mysqli_fetch_assoc($result)){
                if(empty($row['date_string']))continue;
                $payment_data_wallet_fund_sort[$row['date_string']] += floattocurrency($row['price']);
                $sum_all_wallet_funding_month += $row['price'];
            }
        
        }
        mysqli_free_result($result);
    }

    

    
    


}


function getPaymentTrendMonthDrvr($month,$year){ //Function returns number of registrations for a month on a selected year

    $driver_id = (int) $_POST['driver-scope-id'];

    $payment_data_bookings_completed = [];
    $payment_data_online = [];
    $payment_data_wallet_fund = [];
    $payment_data_payouts = [];
    
    global $dates;
    global $dates_label;
    global $payment_data_bookings_completed_sort;
    global $payment_data_bookings_completed_earnings_sort;
    global $payment_data_online_sort;
    global $payment_data_wallet_fund_sort;
    global $payment_data_payouts_sort;

    global $sum_all_completed_booking_cost_month;
    global $sum_all_completed_booking_earning_month;
    global $sum_all_completed_transaction_cost_month;
    global $sum_all_wallet_funding_month;
    global $sum_all_payouts_month;
  
    
    $month_formated = str_pad($month,2,"0",STR_PAD_LEFT);
    $num_of_month_days = date("t",strtotime($year."-".$month_formated));
    $start_date = $year."-".$month_formated."-01";
    $end_date = gmdate('Y-m-d', strtotime($start_date."+" . $num_of_month_days . " day" ));

    
    for($i = 0; $i < $num_of_month_days; $i++){ //Create array indexes using the dates to display records of
        $day_val = $num_of_month_days - $i;
        $day_formated = $day_val < 10 ? "0" . $day_val : $day_val;
        $date_formated = $year."-".$month_formated."-".$day_formated;
        $day_val = $num_of_month_days - $i;
        $day_formated = $day_val < 10 ? "0" . $day_val : $day_val;
        $date_formated = $year."-".$month_formated."-".$day_formated;
        $dates[] = $date_formated;
        $dates_label[] = '"' . $date_formated . '"';     
        $payment_data_bookings_completed_sort[$date_formated] = "0";
        $payment_data_bookings_completed_earnings_sort[$date_formated]  = "0";
        $payment_data_online_sort[$date_formated] = "0";
        $payment_data_wallet_fund_sort[$date_formated] = "0";
        $payment_data_payouts_sort[$date_formated] = "0";       
        
    }

    $sum_all_payouts_month = "---";
    //Get total of all completed bookings for the period

    
    $query = sprintf('SELECT (%1$stbl_bookings.paid_amount / %1$stbl_bookings.cur_exchng_rate) AS price, DATE(%1$stbl_bookings.date_completed) AS date_string FROM %1$stbl_bookings 
    WHERE %1$stbl_bookings.driver_id = %2$d AND %1$stbl_bookings.status = 3 AND %1$stbl_bookings.date_completed BETWEEN "%3$s" AND "%4$s"', DB_TBL_PREFIX, $driver_id, $start_date, $end_date);

     
    if($result = mysqli_query($GLOBALS['DB'], $query)){
        if(mysqli_num_rows($result)){
            while($row = mysqli_fetch_assoc($result)){
                if(empty($row['date_string']))continue;
                $payment_data_bookings_completed_sort[$row['date_string']] += floattocurrency($row['price']);
                $sum_all_completed_booking_cost_month += $row['price'];
            }
        
        }
        mysqli_free_result($result);
    }

    

    
    //Get total of all succesful online wallet funding for the period

    $query = sprintf('SELECT (%1$stbl_wallet_transactions.amount / %1$stbl_wallet_transactions.cur_exchng_rate) AS price, DATE(%1$stbl_wallet_transactions.transaction_date) AS date_string FROM %1$stbl_wallet_transactions 
    WHERE %1$stbl_wallet_transactions.user_id = %2$d AND %1$stbl_wallet_transactions.user_type = 1 AND %1$stbl_wallet_transactions.type = 0 AND %1$stbl_wallet_transactions.transaction_date BETWEEN "%3$s" AND "%4$s"', DB_TBL_PREFIX, $driver_id,$start_date,$end_date);

    
 
    if($result = mysqli_query($GLOBALS['DB'], $query)){
        if(mysqli_num_rows($result)){
            while($row = mysqli_fetch_assoc($result)){
                if(empty($row['date_string']))continue;
                $payment_data_online_sort[$row['date_string']] += floattocurrency($row['price']);
                $sum_all_completed_transaction_cost_month += $row['price'];
            }
        
        }
        mysqli_free_result($result);
    }

    


    //Get total of all deposits for driver / customer wallet funding for the period

    $query = sprintf('SELECT (%1$stbl_wallet_fund.fund_amount / %1$stbl_wallet_fund.cur_exchng_rate) AS price, DATE(%1$stbl_wallet_fund.date_fund) AS date_string FROM %1$stbl_wallet_fund    
    WHERE %1$stbl_wallet_fund.driver_id = %2$d AND %1$stbl_wallet_fund.fund_type = 1 AND %1$stbl_wallet_fund.date_fund BETWEEN "%3$s" AND "%4$s" ', DB_TBL_PREFIX,$driver_id,$start_date,$end_date);

    
    if($result = mysqli_query($GLOBALS['DB'], $query)){
        
        if(mysqli_num_rows($result)){
            while($row = mysqli_fetch_assoc($result)){
                if(empty($row['date_string']))continue;
                $payment_data_wallet_fund_sort[$row['date_string']] += floattocurrency($row['price']);
                $sum_all_wallet_funding_month += $row['price'];
            }
        
        }
        mysqli_free_result($result);
    }



    //Get total of all payouts for driver

    $query = sprintf('SELECT (%1$stbl_wallet_withdrawal.withdrawal_amount / %1$stbl_wallet_withdrawal.cur_exchng_rate) AS price, DATE(%1$stbl_wallet_withdrawal.date_settled) AS date_string FROM %1$stbl_wallet_withdrawal 
    WHERE %1$stbl_wallet_withdrawal.person_id = %2$d AND %1$stbl_wallet_withdrawal.user_type = 0 AND %1$stbl_wallet_withdrawal.request_status = 2 AND %1$stbl_wallet_withdrawal.date_settled BETWEEN "%3$s" AND "%4$s"', DB_TBL_PREFIX, $driver_id,$start_date,$end_date);

     
    if($result = mysqli_query($GLOBALS['DB'], $query)){
        if(mysqli_num_rows($result)){
            while($row = mysqli_fetch_assoc($result)){
                if(empty($row['date_string']))continue;
                $payment_data_payouts_sort[$row['date_string']] += floattocurrency($row['price']);
                $sum_all_payouts_month += $row['price'];
            }
        
        }
        mysqli_free_result($result);
    }


    //get all earnings of driver
    $query = sprintf('SELECT (%1$stbl_bookings.actual_cost / %1$stbl_bookings.cur_exchng_rate * (%1$stbl_bookings.driver_commision / 100)) AS price, DATE(%1$stbl_bookings.date_completed) AS date_string FROM %1$stbl_bookings 
    WHERE %1$stbl_bookings.driver_id = %2$d AND %1$stbl_bookings.status = 3 AND %1$stbl_bookings.date_completed BETWEEN "%3$s" AND "%4$s"', DB_TBL_PREFIX, $driver_id, $start_date, $end_date);

     
    if($result = mysqli_query($GLOBALS['DB'], $query)){
        if(mysqli_num_rows($result)){
            while($row = mysqli_fetch_assoc($result)){
                if(empty($row['date_string']))continue;
                $payment_data_bookings_completed_earnings_sort[$row['date_string']] += floattocurrency($row['price']);
                $sum_all_completed_booking_earning_month += $row['price'];
            }
        
        }
        mysqli_free_result($result);
    }


    
    


}


function getPaymentTrendMonthFranch($month,$year){ //Function returns number of registrations for a month on a selected year

    $franchise_id = (int) $_POST['franchise-scope-name'];

    $payment_data_bookings_completed = [];
    $payment_data_online = [];
    $payment_data_wallet_fund = [];
    $payment_data_payouts = [];
    
    global $dates;
    global $dates_label;
    global $payment_data_bookings_completed_sort;
    global $payment_data_bookings_completed_earnings_sort;
    global $payment_data_online_sort;
    global $payment_data_wallet_fund_sort;
    global $payment_data_payouts_sort;

    global $sum_all_completed_booking_cost_month;
    global $sum_all_completed_booking_earning_month;
    global $sum_all_completed_transaction_cost_month;
    global $sum_all_wallet_funding_month;
    global $sum_all_payouts_month;
  
    
    $month_formated = str_pad($month,2,"0",STR_PAD_LEFT);
    $num_of_month_days = date("t",strtotime($year."-".$month_formated));
    $start_date = $year."-".$month_formated."-01";
    $end_date = gmdate('Y-m-d', strtotime($start_date."+" . $num_of_month_days . " day" ));

    
    for($i = 0; $i < $num_of_month_days; $i++){ //Create array indexes using the dates to display records of
        $day_val = $num_of_month_days - $i;
        $day_formated = $day_val < 10 ? "0" . $day_val : $day_val;
        $date_formated = $year."-".$month_formated."-".$day_formated;
        $day_val = $num_of_month_days - $i;
        $day_formated = $day_val < 10 ? "0" . $day_val : $day_val;
        $date_formated = $year."-".$month_formated."-".$day_formated;
        $dates[] = $date_formated;
        $dates_label[] = '"' . $date_formated . '"';     
        $payment_data_bookings_completed_sort[$date_formated] = "0";
        $payment_data_bookings_completed_earnings_sort[$date_formated]  = "0";
        $payment_data_online_sort[$date_formated] = "0";
        $payment_data_wallet_fund_sort[$date_formated] = "0";
        $payment_data_payouts_sort[$date_formated] = "0";       
        
    }

    $sum_all_payouts_month = "---";
    //Get total of all completed bookings for the period

    
    $query = sprintf('SELECT (%1$stbl_bookings.paid_amount / %1$stbl_bookings.cur_exchng_rate) AS price, DATE(%1$stbl_bookings.date_completed) AS date_string FROM %1$stbl_bookings 
    WHERE %1$stbl_bookings.franchise_id = %2$d AND %1$stbl_bookings.status = 3 AND %1$stbl_bookings.date_completed BETWEEN "%3$s" AND "%4$s"', DB_TBL_PREFIX, $franchise_id, $start_date, $end_date);

     
    if($result = mysqli_query($GLOBALS['DB'], $query)){
        if(mysqli_num_rows($result)){
            while($row = mysqli_fetch_assoc($result)){
                if(empty($row['date_string']))continue;
                $payment_data_bookings_completed_sort[$row['date_string']] += floattocurrency($row['price']);
                $sum_all_completed_booking_cost_month += $row['price'];
            }
        
        }
        mysqli_free_result($result);
    }

    

    
    //Get total of all succesful online wallet funding for the period

    $query = sprintf('SELECT (%1$stbl_wallet_transactions.amount / %1$stbl_wallet_transactions.cur_exchng_rate) AS price, DATE(%1$stbl_wallet_transactions.transaction_date) AS date_string FROM %1$stbl_wallet_transactions 
    WHERE %1$stbl_wallet_transactions.user_id = %2$d AND %1$stbl_wallet_transactions.user_type = 2 AND %1$stbl_wallet_transactions.type = 0 AND %1$stbl_wallet_transactions.transaction_date BETWEEN "%3$s" AND "%4$s"', DB_TBL_PREFIX, $franchise_id,$start_date,$end_date);

    
 
    if($result = mysqli_query($GLOBALS['DB'], $query)){
        if(mysqli_num_rows($result)){
            while($row = mysqli_fetch_assoc($result)){
                if(empty($row['date_string']))continue;
                $payment_data_online_sort[$row['date_string']] += floattocurrency($row['price']);
                $sum_all_completed_transaction_cost_month += $row['price'];
            }
        
        }
        mysqli_free_result($result);
    }

    


    //Get total of all deposits for driver / customer wallet funding for the period

    /* $query = sprintf('SELECT (%1$stbl_wallet_fund.fund_amount / %1$stbl_wallet_fund.cur_exchng_rate) AS price, DATE(%1$stbl_wallet_fund.date_fund) AS date_string FROM %1$stbl_wallet_fund    
    WHERE %1$stbl_wallet_fund.driver_id = %2$d AND %1$stbl_wallet_fund.fund_type = 1 AND %1$stbl_wallet_fund.date_fund BETWEEN "%3$s" AND "%4$s" ', DB_TBL_PREFIX,$driver_id,$start_date,$end_date);

    
    if($result = mysqli_query($GLOBALS['DB'], $query)){
        
        if(mysqli_num_rows($result)){
            while($row = mysqli_fetch_assoc($result)){
                if(empty($row['date_string']))continue;
                $payment_data_wallet_fund_sort[$row['date_string']] += floattocurrency($row['price']);
                $sum_all_wallet_funding_month += $row['price'];
            }
        
        }
        mysqli_free_result($result);
    } */



    //Get total of all payouts

    $query = sprintf('SELECT (%1$stbl_wallet_withdrawal.withdrawal_amount / %1$stbl_wallet_withdrawal.cur_exchng_rate) AS price, DATE(%1$stbl_wallet_withdrawal.date_settled) AS date_string FROM %1$stbl_wallet_withdrawal 
    WHERE %1$stbl_wallet_withdrawal.person_id = %2$d AND %1$stbl_wallet_withdrawal.user_type = 1 AND %1$stbl_wallet_withdrawal.request_status = 2 AND %1$stbl_wallet_withdrawal.date_settled BETWEEN "%3$s" AND "%4$s"', DB_TBL_PREFIX, $franchise_id,$start_date,$end_date);

     
    if($result = mysqli_query($GLOBALS['DB'], $query)){
        if(mysqli_num_rows($result)){
            while($row = mysqli_fetch_assoc($result)){
                if(empty($row['date_string']))continue;
                $payment_data_payouts_sort[$row['date_string']] += floattocurrency($row['price']);
                $sum_all_payouts_month += $row['price'];
            }
        
        }
        mysqli_free_result($result);
    }


    //get all earnings
    if($franchise_id == 1){
        $query = sprintf('SELECT (%1$stbl_bookings.paid_amount - %1$stbl_bookings.actual_cost) / %1$stbl_bookings.cur_exchng_rate AS deficit,%1$stbl_bookings.franchise_id,%1$stbl_bookings.franchise_commision,(%1$stbl_bookings.actual_cost - (%1$stbl_bookings.actual_cost * (%1$stbl_bookings.driver_commision / 100))) / %1$stbl_bookings.cur_exchng_rate AS price, DATE(%1$stbl_bookings.date_completed) AS date_string FROM %1$stbl_bookings 
        WHERE %1$stbl_bookings.status = 3 AND %1$stbl_bookings.date_completed BETWEEN "%2$s" AND "%3$s"', DB_TBL_PREFIX, $start_date, $end_date);
    }else{
        $query = sprintf('SELECT (%1$stbl_bookings.paid_amount - %1$stbl_bookings.actual_cost) / %1$stbl_bookings.cur_exchng_rate AS deficit,%1$stbl_bookings.franchise_id,%1$stbl_bookings.franchise_commision,(%1$stbl_bookings.actual_cost - (%1$stbl_bookings.actual_cost * (%1$stbl_bookings.driver_commision / 100))) / %1$stbl_bookings.cur_exchng_rate AS price, DATE(%1$stbl_bookings.date_completed) AS date_string FROM %1$stbl_bookings 
        WHERE %1$stbl_bookings.franchise_id = %2$d AND %1$stbl_bookings.status = 3 AND %1$stbl_bookings.date_completed BETWEEN "%3$s" AND "%4$s"', DB_TBL_PREFIX, $franchise_id, $start_date, $end_date);
    }
        

    $data_raw = []; 
    if($result = mysqli_query($GLOBALS['DB'], $query)){
        if(mysqli_num_rows($result)){
            while($row = mysqli_fetch_assoc($result)){
                $data_raw[] = $row;
                if(empty($row['date_string']))continue;
                if($franchise_id == 1){
                    if($row['franchise_id'] == 1){ //company
                        $row['price'] = $row['price'] + $row['deficit'];                    
                    }else{ //franchise
                        $other_franchise_commission = $row['price'] * $row['franchise_commision'] / 100;
                        $company_commission = $row['price'] - $other_franchise_commission;
                        $row['price'] = $company_commission + $row['deficit'];
                    }
                }else{
                    $row['price'] = $row['price'] * $row['franchise_commision'] / 100;
                }
                    
                $payment_data_bookings_completed_earnings_sort[$row['date_string']] += floattocurrency($row['price']);
                $sum_all_completed_booking_earning_month += $row['price'];
            }
        
        }
        mysqli_free_result($result);
    }


    
    


}


























?>