<?php
session_start();
include("../drop-files/lib/common.php");
include "../drop-files/config/db.php";





/*

This Script handles ajax calls from the client end. Useful for tasks that don't require client page refresh or reload'

return values
--------------

X99 = User not logged in.
X100 = no action set; invalid function


*/

/* var_dump($_POST);
var_dump($_SERVER);
exit; */



if(isset($_POST['action'])){

    if(function_exists($_POST['action'])){

        call_user_func($_POST['action']);
        exit;
    
    
    }
    
}elseif(isset($_GET['action_get'])){

    if(function_exists($_GET['action_get'])){

        call_user_func($_GET['action_get']);
        exit;
    
    
    }

}else{

    echo "X100"; //no action set; invalid function
    exit;
}


echo "invalid function call";
exit;


function checkSMTP(){

    $to = $_POST['to'];
    $subject = "Droptaxi SMTP Test";
    $message = "If you can read this message it means that your SMTP setting is good and working.";

    $smtp_test_options = array(
        'host'=> $_POST['hostname'],
        'username' => $_POST['username'],
        'password' => $_POST['password']
    );

    

    $res = sendMail($to,$subject,$message,$smtp_test_options);

    if(is_array($res) && isset($res['response'])){
        if($res['response'] == 1){
            $data = array(
                'success'=>1, 'message'=>"Message has been sent."
            );
            echo json_encode($data);
            exit;
        }else{
            $data = array(
                'error'=>1, 'message'=>$res['message']
            );
            echo json_encode($data);
            exit;
        }

    }

    $data = array(
        'error'=>1, 'message'=>"Error sending SMTP test message"
    );
    echo json_encode($data);

}



function calctariff(){

    
    $tariff_data = [];


    if(!(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == 1)){ //if user is not logged in run this code
        $error = array("notloggedin"=>"Please Sign-in to cotinue.",);
        echo json_encode($error); //database error
        exit;
    }


    $route_id = (int) $_POST['route_id'];
    $ride_id = (int) $_POST['ride_id'];


    $query = sprintf('SELECT * FROM %stbl_rides_tariffs WHERE routes_id = "%d" AND ride_id = "%d"', DB_TBL_PREFIX, $route_id,$ride_id); //Get required user information from DB

    if($result = mysqli_query($GLOBALS['DB'], $query)){
        if(mysqli_num_rows($result)){
            $tariff_data = mysqli_fetch_assoc($result);                    
        }else{
            $error = array("error"=>"Error computing tariff. Please retry.");
            echo json_encode($error); //database error
            exit;
        }
        
    }
    else{ //No record matching the USER ID was found in DB. Show view to notify user

        $error = array("error"=>"Error computing tariff. Please retry.");
        echo json_encode($error); //database error
        exit;
    }

    
    //Get distance data from google maps
    $url = "https://maps.googleapis.com/maps/api/directions/json?origin={$_POST['a_lat']},{$_POST['a_lng']}&destination={$_POST['b_lat']},{$_POST['b_lng']}&key=" . GMAP_API_KEY;
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPGET, true);
    curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, false);
    //curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
    $json_response = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    $response = json_decode($json_response, true);
    if(json_last_error()){
        $error = array("error"=>"Error computing tariff. Please retry.");
        echo json_encode($error); //database error
        exit;
    }

    $pickup_cost = $tariff_data['pickup_cost'];
    $drop_off_cost = $tariff_data['drop_off_cost'];
    $cost_per_km = $tariff_data['cost_per_km'];
    $cost_per_minute = $tariff_data['cost_per_minute'];


    $distance = $response['routes'][0]['legs'][0]['distance']['text'];
    $duration = $response['routes'][0]['legs'][0]['duration']['text'];
    $price = ($cost_per_km * $distance) + $drop_off_cost + $pickup_cost;

    //save this information in a session just in case the user decides to book this ride

    $token = crypto_string("nozero",5); //generate token

    unset($_SESSION['booking']);

    $_SESSION['booking'][$token]['a_lat'] = $_POST['a_lat'];
    $_SESSION['booking'][$token]['a_lng'] = $_POST['a_lng'];
    $_SESSION['booking'][$token]['b_lat'] = $_POST['b_lat'];
    $_SESSION['booking'][$token]['b_lng'] = $_POST['b_lng'];
    $_SESSION['booking'][$token]['p_addr'] = $_POST['p_addr'];
    $_SESSION['booking'][$token]['d_addr'] = $_POST['d_addr'];
    $_SESSION['booking'][$token]['route_id'] = $_POST['route_id'];
    $_SESSION['booking'][$token]['distance'] = $distance;
    $_SESSION['booking'][$token]['duration'] = $duration;
    $_SESSION['booking'][$token]['ride_id'] = $_POST['ride_id'];
    $_SESSION['booking'][$token]['token'] = $token;
    $_SESSION['booking'][$token]['cost'] = $price;
    



    
    $route_price_data = array('distance'=>$distance,'duration'=>$duration,'price'=>$price,'token'=>$token);
    echo json_encode($route_price_data); 
    exit;
    




}



function driverLogin(){
    $email = $_POST['phone'];
    $password = $_POST['password'];
    $token = "";
    $driver_account_details = [];


    //Let's check our local DB for driver record'

    $query = sprintf('SELECT driver_id,firstname,lastname,email,phone,drv_address,is_activated,account_active,last_login_date,account_create_date,car_plate_num,car_reg_num,car_model,car_color,available FROM %stbl_drivers WHERE phone = "%d" AND pwd_raw = "%s"', DB_TBL_PREFIX, mysqli_real_escape_string($GLOBALS['DB'], $_POST['phone']), mysqli_real_escape_string($GLOBALS['DB'],  $_POST['password'])); //Get required user information from DB

    if($result = mysqli_query($GLOBALS['DB'], $query)){
        if(mysqli_num_rows($result)){
            $driver_account_details = mysqli_fetch_assoc($result);
        }
        else{
            $error = array("error"=>"Invalid account");
            echo json_encode($error); //invalid account
            exit;

        }
        
    }
    else{ //No record matching the USER ID was found in DB. Show view to notify user

        $error = array("error"=>"Database Error");
        echo json_encode($error); //database error
        exit;
    }



    //valid account. Generate token 
    $token = crypto_string("alnum",20); //generate token

    //Delete any previous session entry
    $query = sprintf('DELETE FROM %stbl_sessions WHERE user_id = "%d" AND user_type=1', DB_TBL_PREFIX, $driver_account_details['driver_id']); //delete already inserted record 
    $result = mysqli_query($GLOBALS['DB'], $query);

    //store new session token
    $query = sprintf('INSERT INTO %stbl_sessions (token, user_id, user_type, date_created) VALUES'.
        '("%s","%d","%d","%s")', 
        DB_TBL_PREFIX, 
        $token,
        $driver_account_details['driver_id'],
        1, //for driver
        gmdate('Y-m-d H:i:s', time()) 
    );


    if(! $result = mysqli_query($GLOBALS['DB'], $query)){
        
        $error = array("error"=>"Database Error");
        echo json_encode($error); //database error
        exit; 
        
    }
    
    $_SESSION['firstname'] = $driver_account_details['firstname'];
    $_SESSION['lastname'] = $driver_account_details['lastname'];
    $_SESSION['uid'] = $driver_account_details['driver_id'];
    $_SESSION['email'] = $driver_account_details['email'];
    $_SESSION['phone'] = $driver_account_details['phone'];
    $_SESSION['address'] = $driver_account_details['address'];
    $_SESSION['is_activated'] = $driver_account_details['is_activated'];
    $_SESSION['loggedin'] = 1;
   
    
    //check if user is activated
    $data = array("token"=>$token,"driver_id"=>$driver_account_details['driver_id'],"email"=>$driver_account_details['email'],"is_activated"=>$driver_account_details['is_activated'],"account_active"=>$driver_account_details['account_active']);
    echo json_encode($data);
    exit;
            
    




    /* //verify token
    $query = sprintf('SELECT * FROM %stbl_sessions WHERE token = "%s" AND user_type = 1', DB_TBL_PREFIX, mysqli_real_escape_string($GLOBALS['DB'], $_POST['token'])); //Get required user information from DB


    if($result = mysqli_query($GLOBALS['DB'], $query)){
        if(mysqli_num_rows($result)){
        $driver_session_token = mysqli_fetch_assoc($result);
        }
        else{
            echo "error1"; //invalid session
            exit;

        }
        
    }
    else{ 

        echo "error2"; //error accessing database
        exit;
    } */


}





function userLogin(){
    
    $email = $_POST['email'];
    $password = $_POST['password'];
    $token = "";
    $user_account_details = [];

    
    //Let's check our local DB for driver record'

    $query = sprintf('SELECT `address`,account_type,user_id,firstname, lastname,email,phone,is_activated,account_active,last_login_date,account_create_date,referal_code,wallet_amount FROM %stbl_users WHERE email = "%s" AND pwd_raw = "%s"', DB_TBL_PREFIX, mysqli_real_escape_string($GLOBALS['DB'], $_POST['email']), mysqli_real_escape_string($GLOBALS['DB'],  $_POST['password'])); //Get required user information from DB


    if($result = mysqli_query($GLOBALS['DB'], $query)){
        if(mysqli_num_rows($result)){
            $user_account_details = mysqli_fetch_assoc($result);
        }
        else{
            $error = array("error"=>"Invalid account");
            echo json_encode($error); //invalid account
            exit;

        }
        
    }
    else{ //No record matching the USER ID was found in DB. Show view to notify user

        $error = array("error"=>"Database Error");
        echo json_encode($error); //database error
        exit;
    }

    
    //valid account. Generate token 
    $token = crypto_string("alnum",20); //generate token

    //Delete any previous session entry
    $query = sprintf('DELETE FROM %stbl_sessions WHERE user_id = "%d" AND user_type=0', DB_TBL_PREFIX, $user_account_details['user_id']); //delete already inserted record 
    $result = mysqli_query($GLOBALS['DB'], $query);

    //store new session token
    $query = sprintf('INSERT INTO %stbl_sessions (token, user_id, user_type, date_created) VALUES'.
        '("%s","%d","%d","%s")', 
        DB_TBL_PREFIX, 
        $token,
        $user_account_details['user_id'],
        0, //for user
        gmdate('Y-m-d H:i:s', time()) 
    );


    if(! $result = mysqli_query($GLOBALS['DB'], $query)){
        
        $error = array("error"=>"Database Error");
        echo json_encode($error); //database error
        exit; 
        
    }

    
    $_SESSION['firstname'] = $user_account_details['firstname'];
    $_SESSION['lastname'] = $user_account_details['lastname'];
    $_SESSION['uid'] = $user_account_details['user_id'];
    $_SESSION['email'] = $user_account_details['email'];
    $_SESSION['phone'] = $user_account_details['phone'];
    $_SESSION['address'] = $user_account_details['address'];
    $_SESSION['referal_code'] = $user_account_details['referal_code'];
    $_SESSION['account_type'] = $user_account_details['account_type'];
    $_SESSION['lastseen'] = $user_account_details['last_login_date'];
    $_SESSION['joined'] = $user_account_details['account_create_date'];
    $_SESSION['loggedin'] = 1;
    $_SESSION['is_activated'] = $user_account_details['is_activated'];
    $_SESSION['wallet_amt'] = $user_account_details['wallet_amount'];
    
    $profiledata = array(
        'success' => 1,
        'firstname'=> $_SESSION['firstname'],
        'lastname'=> $_SESSION['lastname'],
        'email'=> $_SESSION['email'],
        'phone'=> $_SESSION['phone'],
        'address'=> $_SESSION['address'],
        'userid' => $_SESSION['uid']

    );


    //get tariff data
    $tariff_data = getroutetariffs();



    //get wallet data 
    $template = '';
    $transaction_data = [];


    $query = sprintf('SELECT *,DATE(`date`) AS transaction_date FROM %stbl_vogue_pay WHERE transaction_ref = "%d" ORDER BY `date` DESC LIMIT 0,100 ', DB_TBL_PREFIX,$_SESSION['uid']); //Get required user information from DB


    if($result = mysqli_query($GLOBALS['DB'], $query)){
        if(mysqli_num_rows($result)){
            while($row = mysqli_fetch_assoc($result)){
                $transaction_data[] = $row;
            }
                            
        }    
    }


    //sort data by date
    $transaction_data_sort = [];

    foreach($transaction_data as $transactiondata){

        $transaction_data_sort[$transactiondata['transaction_date']]['date'] = $transactiondata['transaction_date'];
        $transaction_data_sort[$transactiondata['transaction_date']]['data'][] = $transactiondata;
    }

    //format for display on app
    foreach($transaction_data_sort as $transactiondatasort){

    if(!empty($transactiondatasort['data'])){ 
            $template .= "<ons-list-header>{$transactiondatasort['date']}</ons-list-header>";

            foreach($transactiondatasort['data'] as $transaction_d){
                $transaction_time = date('g:i A',strtotime($transaction_d['date'] . ' UTC'));
                $status = '';
                switch($transaction_d['status']){
                    case 'Approved':
                    $status = "<span style='color:lightgreen'>Success</span>";
                    break;

                    case 'Pending':
                    $status = "<span style='color:purple'>Pending</span>";
                    break;

                    default:
                    $status = "<span style='color:red'>Failed</span>";
                    break;


                }

                $template .= "<ons-list-item modifier='longdivider'>
                    
                                <div class='center'>
                                    <div style='width:100%'><span class='list-item__title'>{$transaction_time}</span> </div>
                                    <span class='list-item__subtitle'><span style='color:yellow'>Transaction ID:</span> {$transaction_d['v_transaction_id']} | {$status} </span>
                                    <span class='list-item__subtitle'><span style='color:lightgreen'>Description:</span> {$transaction_d['memo']}</span>
                                    <span class='list-item__subtitle'><span style='color:orange'>Amount paid:</span> {$transaction_d['total_credited_to_merchant']}</span>
                                    
                                </div>
                            
                            </ons-list-item>";
            }

    }

    }
    //Get online payment gateway data

    $online_payment_data = array(

        'merchantid'=> MERCHANT_ID,
        'storeid'=> STORE_ID,
        'devid'=> DEV_ID,
        'notifyurl'=> NOTIFY_URL
        
    );


        
    //return data
    $data = array("loggedin"=>1,"is_activated"=>$user_account_details['is_activated'],'wallet_amt' => $_SESSION['wallet_amt'],'wallet_history'=>$template,'cc_num'=>CALL_CENTER_NUMBER,'profileinfo' => $profiledata,'tariff_data'=>$tariff_data,'profileinfo' => $profiledata,'online_pay'=>$online_payment_data);
    echo json_encode($data);
    exit;
            
    
  


}

function userLogout(){

    session_regenerate_id();
    $_SESSION = array();  //clear session data
    session_destroy();
    $data = array("loggedout"=>1);
    echo json_encode($data);
    exit;

}



function checkLoginStatus(){

    if(!empty($_SESSION['loggedin'])){

        //get tariff data
        $tariff_data = getroutetariffs();



        //get wallet data 
        $template = '';
        $transaction_data = [];


        $query = sprintf('SELECT *,DATE(`date`) AS transaction_date FROM %stbl_vogue_pay WHERE transaction_ref = "%d" ORDER BY `date` DESC LIMIT 0,100 ', DB_TBL_PREFIX,$_SESSION['uid']); //Get required user information from DB


        if($result = mysqli_query($GLOBALS['DB'], $query)){
            if(mysqli_num_rows($result)){
                while($row = mysqli_fetch_assoc($result)){
                    $transaction_data[] = $row;
                }
                                
            }    
        }


        //sort data by date
        $transaction_data_sort = [];

        foreach($transaction_data as $transactiondata){

            $transaction_data_sort[$transactiondata['transaction_date']]['date'] = $transactiondata['transaction_date'];
            $transaction_data_sort[$transactiondata['transaction_date']]['data'][] = $transactiondata;
        }

        //format for display on app
        foreach($transaction_data_sort as $transactiondatasort){

        if(!empty($transactiondatasort['data'])){ 
                $template .= "<ons-list-header>{$transactiondatasort['date']}</ons-list-header>";

                foreach($transactiondatasort['data'] as $transaction_d){
                    $transaction_time = date('g:i A',strtotime($transaction_d['date'] . ' UTC'));
                    $status = '';
                    switch($transaction_d['status']){
                        case 'Approved':
                        $status = "<span style='color:lightgreen'>Success</span>";
                        break;

                        case 'Pending':
                        $status = "<span style='color:purple'>Pending</span>";
                        break;

                        default:
                        $status = "<span style='color:red'>Failed</span>";
                        break;


                    }

                    $template .= "<ons-list-item modifier='longdivider'>
                        
                                    <div class='center'>
                                        <div style='width:100%'><span class='list-item__title'>{$transaction_time}</span> </div>
                                        <span class='list-item__subtitle'><span style='color:yellow'>Transaction ID:</span> {$transaction_d['v_transaction_id']} | {$status} </span>
                                        <span class='list-item__subtitle'><span style='color:lightgreen'>Description:</span> {$transaction_d['memo']}</span>
                                        <span class='list-item__subtitle'><span style='color:orange'>Amount paid:</span> {$transaction_d['total_credited_to_merchant']}</span>
                                        
                                    </div>
                                
                                </ons-list-item>";
                }

        }

        }


        //profile information
        $profiledata = array(
            'success' => 1,
            'firstname'=> $_SESSION['firstname'],
            'lastname'=> $_SESSION['lastname'],
            'email'=> $_SESSION['email'],
            'phone'=> $_SESSION['phone'],
            'address'=> $_SESSION['address'],
            'userid' => $_SESSION['uid']
    
        );

        $online_payment_data = array(

            'merchantid'=> MERCHANT_ID,
            'storeid'=> STORE_ID,
            'devid'=> DEV_ID,
            'notifyurl'=> NOTIFY_URL
            
        );



        $data = array("loggedin"=>1,"is_activated"=>$_SESSION['is_activated'],'wallet_amt' => $_SESSION['wallet_amt'],'wallet_history'=>$template,'cc_num'=>CALL_CENTER_NUMBER,'profileinfo' => $profiledata,'tariff_data'=>$tariff_data,'online_pay'=>$online_payment_data);
        echo json_encode($data);
        exit;
    }else{
        $data = array("loggedin"=>0);
        echo json_encode($data);
        exit;
    }

}




function userRegister(){
    
    $_POST['firstname'] = str_replace(" ","",$_POST['firstname']); //remove spaces
    $_POST['lastname'] = str_replace(" ","",$_POST['lastname']); //remove spaces



    if(empty($_POST['firstname'])) {
        
        $error = array("error"=>"Please enter your first name");
        echo json_encode($error); 
        exit;
        
    } 

    if(strlen($_POST['firstname']) < 2){
        
        $error = array("error"=>"Your firstname is too short");
        echo json_encode($error); 
        exit;            
    }

    if(preg_match('/[^a-z]/i', $_POST['firstname'])){
        
        $error = array("error"=>"Your first name must contain only alphabetical characters");
        echo json_encode($error); 
        exit;  
                    
    }

        
    if(empty($_POST['lastname'])) {
        
        $error = array("error"=>"Please enter your lastname");
        echo json_encode($error); 
        exit;
    } 
    if(preg_match('/[^a-z]/i', $_POST['lastname'])){
        
        $error = array("error"=>"Your lastname must contain only alphabetical characters");
        echo json_encode($error); 
        exit;            
    }

    if(strlen($_POST['lastname']) < 2){
        
        $error = array("error"=>"Your lastname is too short");
        echo json_encode($error); 
        exit;             
    }


    if(empty($_POST['email'])) {
        
        $error = array("error"=>"Please enter a valid email");
        echo json_encode($error); 
        exit;
        
    } 

    if(!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)){
        
        $error = array("error"=>"Your email is not a valid email format");
        echo json_encode($error); 
        exit;
    }

    if(strlen($_POST['email'])>64){
        
        $error = array("error"=>"Your email is too long. Email must be lower than 64 characters");
        echo json_encode($error); 
        exit;
    }

    if(strlen($_POST['phone']) > 20) {
        
        $error = array("error"=>"Your phone number is too long");
        echo json_encode($error); 
        exit;
    } 

    if(strlen($_POST['phone']) < 5) {
        
        $error = array("error"=>"Your phone number is too short");
        echo json_encode($error); 
        exit;
    } 

    /* if(empty($_POST['username'])) {
        $error[] = "Please enter a username";
    } 
    if(preg_match('/[^a-z0-9@._-]/i', $_POST['username'])){
        $error[] = "Your Username should contain only lowercase characters  and can contain numbers or any of these characters _-.@;";            
    }
    if((strlen($_POST['username'])) > 15 || (strlen($_POST['username'])) < 2 ){
        $error[] = "Your username should not be more that 15 or lower than 2 characters";
    } */

    if((strlen($_POST['password']) < 8 )){
        
        $error = array("error"=>"Password must not be less than eight characters");
        echo json_encode($error); 
        exit;
    }

    if((strlen($_POST['password']) > 60 )){
        
        $error = array("error"=>"Password is too long. Password must not be more than 60 characters");
        echo json_encode($error); 
        exit;
    }

    if($_POST['password'] !== $_POST['rpassword']) {
        
        $error = array("error"=>"Password and password repeat are not the same");
        echo json_encode($error); 
        exit;
        
    }
    if(empty($_POST['password'])|| empty($_POST['rpassword']) ) {
        
        $error = array("error"=>"Please enter a password");
        echo json_encode($error); 
        exit;
    } 

    $query = sprintf('SELECT user_id,email,phone FROM %stbl_users WHERE email = "%s" OR phone = "%s"', DB_TBL_PREFIX, mysqli_real_escape_string($GLOBALS['DB'], $_POST['email']),mysqli_real_escape_string($GLOBALS['DB'], $_POST['phone']));


    if($result = mysqli_query($GLOBALS['DB'], $query)){
        if(mysqli_num_rows($result)){

            $row = mysqli_fetch_assoc($result);
            
            if($row['email'] == mysqli_real_escape_string($GLOBALS['DB'], $_POST['email'])){
                $error = array("error"=>"Email already Exists");
                echo json_encode($error);
                exit;
            }elseif($row['phone'] == mysqli_real_escape_string($GLOBALS['DB'], $_POST['phone'])){
                $error = array("error"=>"Phone number already Exists");
                echo json_encode($error);
                exit;
            }else{
                $error = array("error"=>"Email or phone number already Exists");
                echo json_encode($error);
                exit;
            }  
            
            
            
        }
    }else{

        $error = array("error"=>"Database Error");
        echo json_encode($error); //database error
        exit;


    }

    

    //OK, all good. lets store the registrant form data in the database
    $refcode = crypto_string("alnum",15);
    $query = sprintf('INSERT INTO %stbl_users (firstname, lastname, email, phone, pwd_raw, password_hash, account_create_date,referal_code) VALUES'.
    '("%s","%s","%s","%s","%s","%s","%s","%s")', 
    DB_TBL_PREFIX, 
    mysqli_real_escape_string($GLOBALS['DB'], ucfirst(strtolower(strip_tags($_POST['firstname'])))),
    mysqli_real_escape_string($GLOBALS['DB'], ucfirst(strtolower(strip_tags($_POST['lastname'])))),
    mysqli_real_escape_string($GLOBALS['DB'], strip_tags($_POST['email'])),
    mysqli_real_escape_string($GLOBALS['DB'], strip_tags($_POST['phone'])),
    mysqli_real_escape_string($GLOBALS['DB'], $_POST['password']),
    password_hash(mysqli_real_escape_string($GLOBALS['DB'], $_POST['password']), PASSWORD_DEFAULT),
    gmdate('Y-m-d H:i:s', time()),
    $refcode 
    );


    if(! $result = mysqli_query($GLOBALS['DB'], $query)){

               
        $error = array("error"=>"Dabase Error");
        echo json_encode($error); 
        exit;
        
    }
    else{
            $user_id = mysqli_insert_id ( $GLOBALS['DB'] );
            
        }

           

        $token = crypto_string("nozero",5); //generate token

    if(!$user_id){
        $error = array("error"=>"Error creating your account");
        echo json_encode($error); 
        exit; 

    }


    //Store activation token information and user ID  in database 
    $query = sprintf('INSERT INTO %stbl_account_codes (user_id, code) VALUES ("%d","%s")',DB_TBL_PREFIX, $user_id, $token); 


    if (!$result = mysqli_query($GLOBALS['DB'], $query)){
        
        $query = sprintf('DELETE FROM %stbl_users WHERE user_id = "%d"', DB_TBL_PREFIX, $user_id); //delete already inserted record 
        $result = mysqli_query($GLOBALS['DB'], $query);

        $error = array("error"=>"Error creating your account");
        echo json_encode($error); 
        exit;

                
    }


    //Send an email with activation link bearing the token to the user
    $message ="";
    //composing the email body.
    $message .= '<html>';
    $message .= '<div style = "width:500px;"><img src="'."http://". $_SERVER['HTTP_HOST'].'/img/logo-mid.png" width="180px" style="margin-left:auto; margin-right:auto; display:block;"/><br/>';
    $message .='<h2 style="text-align:center;">Thank you for registering with CabMan</h2><br /><br />';
    $message .='<p>Your account has been created but is currently not activated. To complete your registration, enter the activation code as requested.</p>';
    $message .="<h2><b style='text-align:center;'>{$token}</b></h2>";
    $message .="<br /><br /><br /><br /><br /><br />";
    $message .="<p>You have received this email because a user created an account on CabMan Website."; 
    $message .="Simply ignore the message if it is not you</p></div>";
    $message .="</html >";

    $mail_sender_address = 'From: '.MAIL_SENDER;
    $headers = array($mail_sender_address,'MIME-Version: 1.0', 'Content-Type: text/html; charset="iso-8859-1"'); //Required for a HTML formatted E-mail ;)

    //echo $message;

    //mail($_POST['email'], 'Account Activation - UNN e-Learning Platform', $message, join("\r\n", $headers)); //send the email
    if(!mail($_POST['email'], WEBSITE_NAME." - Activation Code", $message, join("\r\n", $headers))){

                
        $query = sprintf('DELETE FROM %stbl_users WHERE user_id = "%d"', DB_TBL_PREFIX, $user_id); //delete already inserted record 
        $result = mysqli_query($GLOBALS['DB'], $query);

        $error = array("error"=>"Error creating your account");
        echo json_encode($error); 
        exit;

        
    } //send the email

       
    
    $data = array("success"=>1);
    echo json_encode($data); 
    exit;



}



function userResendCode(){

    if(empty($_SESSION['loggedin'])){
        $error = array("error"=>"Please re-login and retry.");
        echo json_encode($error); 
        exit; 
    }

    $code = crypto_string("nozero",5); //generate token

    $user_id = (int) $_POST['user_id'];
    $email = $_POST['email'];
    

    //delete any previous activation code for this user
    $query = sprintf('DELETE FROM %stbl_account_codes WHERE user_id = "%d" AND context=0', DB_TBL_PREFIX, $_SESSION['uid']); //delete already inserted record 
    $result = mysqli_query($GLOBALS['DB'], $query);

    $query = sprintf('INSERT INTO %stbl_account_codes (user_id, code) VALUES ("%d","%s")',DB_TBL_PREFIX, $user_id, $code); 
    
    if(!$result = mysqli_query($GLOBALS['DB'], $query)){ //An error has occured while trying to update KSmart user ID on SIS user database record?
        $error = array("error"=>"Error resending activation code");
        echo json_encode($error); 
        exit;
    }

        
    $message = "";

    $message .= '<html>';
    $message .= '<div style = "width:500px;"><img src="'."http://". $_SERVER['HTTP_HOST'].'/img/logo-mid.png" width="180px" style="margin-left:auto; margin-right:auto; display:block;"/><br/>';
    $message .='<h2 style="text-align:center;">Thank you for registering with CabMan</h2><br /><br />';
    $message .='<p>Your account has been created but is currently not activated. To complete your registration, enter the activation code as requested.</p>';
    $message .="<h2><b style='text-align:center;'>{$code}</b></h2>";
    $message .="<br /><br /><br /><br /><br /><br />";
    $message .="<p>You have received this email because a user created an account on CabMan Website."; 
    $message .="Simply ignore the message if it is not you</p></div>";
    $message .="</html >";

    $mail_sender_address = 'From: '.MAIL_SENDER;
    $headers = array($mail_sender_address,'MIME-Version: 1.0', 'Content-Type: text/html; charset="iso-8859-1"'); //Required for a HTML formatted E-mail ;)

    //echo $message;

    //mail($_POST['email'], 'Account Activation - UNN e-Learning Platform', $message, join("\r\n", $headers)); //send the email
    if(!mail($email, WEBSITE_NAME." - Activation Code", $message, join("\r\n", $headers))){

        $error = array("error"=>"Error resending activation code");
        echo json_encode($error); 
        exit;

    } //send the email


    $success = array("success"=>"Activation code sent");
    echo json_encode($success); 
    exit;



}


function userActivateCode(){
    $code = (int) $_POST['code'];
    if(empty($code)){
        $error = array("error"=>"Please enter an activation code");
        echo json_encode($error); 
        exit; 
    }

    $query = sprintf('SELECT code FROM %stbl_account_codes WHERE code = "%d" AND user_id = "%d" AND context = 0', DB_TBL_PREFIX, $code,$_SESSION['uid']); //Get required user information from DB


    if($result = mysqli_query($GLOBALS['DB'], $query)){
        if(mysqli_num_rows($result)){
        $row = mysqli_fetch_assoc($result);
        }
        else{
            $error = array("error"=>"Wrong activation code entered.");
            echo json_encode($error); 
            exit;
        }
        
    }
    else{ //No record matching the USER ID was found in DB. Show view to notify user

        $error = array("error"=>"Database Error");
        echo json_encode($error); 
        exit;
    }


    $query = sprintf('UPDATE %stbl_users SET is_activated = 1 WHERE user_id = "%d"', DB_TBL_PREFIX,$_SESSION['uid'] );

    if(!$result = mysqli_query($GLOBALS['DB'], $query)){ //An error has occured while trying to update KSmart user ID on SIS user database record?
        $error = array("error"=>"Database error.");
        echo json_encode($error); 
        exit;
    }

    $query = sprintf('DELETE FROM %stbl_account_codes WHERE user_id = "%d" AND code="%d"', DB_TBL_PREFIX, $_SESSION['uid'],$code); //delete already inserted record 
    $result = mysqli_query($GLOBALS['DB'], $query);
    $_SESSION['is_activated'] = 1;
    $response = array("success"=>1);
    echo json_encode($response); 
    exit;




}



function bookingassigndriver(){

    $booking_id = (int) $_POST['booking_id'];
    $driver_id = (int) $_POST['driver_id'];
    $driver_data = [];

       
    if(empty($_SESSION['loggedin'])){
        $error = array("error"=>"Please re-login and retry.");
        echo json_encode($error); 
        exit; 
    }


    
    //check if driver is already assigned to the booking

    $booking_data = [];    
    
    $query = sprintf('SELECT *, %1$stbl_bookings.id AS booking_id FROM %1$stbl_bookings
    INNER JOIN %1$stbl_users ON %1$stbl_users.user_id = %1$stbl_bookings.user_id
    WHERE %1$stbl_bookings.id = %2$d', DB_TBL_PREFIX, $booking_id);

    if($result = mysqli_query($GLOBALS['DB'], $query)){
        if(mysqli_num_rows($result)){

            $booking_data = mysqli_fetch_assoc($result);
            
            if(!empty($booking_data['driver_id'])){
                $error = array("error"=>"Driver has been assigned to the booking and is being serviced.");
                echo json_encode($error); 
                exit;
            }          

        }else{
            $error = array("error"=>"Invalid booking record.");
            echo json_encode($error); 
            exit;
        }
    }else{
        $error = array("error"=>"Error occured trying to read booking data from database.");
        echo json_encode($error); 
        exit;
    }


    //get driver details

    $query = sprintf('SELECT * FROM %1$stbl_drivers 
    INNER JOIN %1$stbl_driver_location ON %1$stbl_driver_location.driver_id = %1$stbl_drivers.driver_id
    WHERE %1$stbl_drivers.driver_id = "%2$d"', DB_TBL_PREFIX, $driver_id);

    if($result = mysqli_query($GLOBALS['DB'], $query)){
        if(mysqli_num_rows($result)){
            $driver_data = mysqli_fetch_assoc($result);
            if($driver_data['available'] && strtotime($driver_data['location_date'] . ' UTC') < (time() - LOCATION_INFO_VALID_AGE)){
                $error = array("error"=>"Driver is offline.");
                echo json_encode($error); 
                exit;
            }elseif(empty($driver_data['available'])){
                $error = array("error"=>"Driver is offline.");
                echo json_encode($error); 
                exit;
            }
        }else{

            $error = array("error"=>"Invalid driver record.");
            echo json_encode($error); 
            exit;
        }
    }else{
        $error = array("error"=>"Error occured trying to read driver data from database.");
        echo json_encode($error); 
        exit;
    }


    //check for pending driver allocations for this booking
    $query = sprintf('SELECT * FROM %1$stbl_driver_allocate
    WHERE %1$stbl_driver_allocate.booking_id = %2$d', DB_TBL_PREFIX, $booking_id);

    if($result = mysqli_query($GLOBALS['DB'], $query)){
        if(mysqli_num_rows($result)){
            while($row = mysqli_fetch_assoc($result)){
                
                if(!empty($row['driver_id']) && $row['status'] == 1){
                    $error = array("error"=>"Driver has been assigned to the booking and is being serviced.");
                    echo json_encode($error); 
                    exit;
                }elseif(!empty($row['driver_id']) && $row['status'] == 0){
                    if($row['driver_id'] == $driver_id){ //same driver previously allocated? then resend notification
                        //send push notification to driver
    
                        $booking_title = str_pad($booking_data['booking_id'] , 5, '0', STR_PAD_LEFT);
                        $title = WEBSITE_NAME . " - New Booking";
                        $body = "You have a new booking with ID({$booking_title}). Please respond to this booking immediately as your customer is waiting.";
                        $device_tokens = !empty($driver_data['push_notification_token']) ? $driver_data['push_notification_token'] : 0;
                        if(!empty($device_tokens)){
                            sendPushNotification($title,$body,$device_tokens,NULL,0);
                        }

                        //silent notification
                        $title = "";
                        $body = "";
                        //$photo = explode('/',$bookingsdata['user_photo']);
                        $photo_file = isset($booking_data['photo_file']) ? $booking_data['photo_file'] : "0";
                        $data = array(
                                        "action"=>"driver-allocate",
                                        "booking_id" => $booking_data['booking_id'],
                                        "p_address" => $booking_data['pickup_address'],
                                        "p_lat" => $booking_data['pickup_lat'],
                                        "p_lng" => $booking_data['pickup_long'],
                                        "d_address" => $booking_data['dropoff_address'],
                                        "d_lat" => $booking_data['dropoff_lat'],
                                        "d_lng" => $booking_data['dropoff_long'],
                                        "rider_image"=> SITE_URL . "ajaxuserphotofile.php?file=". $photo_file,
                                        "rider_name"=>$booking_data['user_firstname'],
                                        "rider_phone"=>$booking_data['user_phone'],
                                        'rider_rating'=>$booking_data['user_rating'],
                                        "completion_code"=>$booking_data['completion_code'],
                                        "driver_accept_duration" => DRIVER_ALLOCATE_ACCEPT_DURATION,
                                        "sent_time"=>time(),
                                        "fare"=>$booking_data['estimated_cost'],
                                        "payment_type" => $booking_data['payment_type'],
                                        "coupon_code"=> $booking_data['coupon_code'],
                                        "coupon_discount_type"=> $booking_data['coupon_discount_type'],
                                        "coupon_discount_value"=> $booking_data['coupon_discount_value'],
                                        "referral_discount_value"=>$booking_data['referral_discount_value'],
                                        "referral_used"=>$booking_data['referral_used'],
                                        "waypoint1_address" => "",
                                        "waypoint1_long" => "",
                                        "waypoint1_lat" => "",
                                        "waypoint2_address" => "",
                                        "waypoint2_long" => "",
                                        "waypoint2_lat" => "",
                                        "repeatable" => 1
                                    );
                        $device_tokens = !empty($driver_data['push_notification_token']) ? $driver_data['push_notification_token'] : 0;
                        if(!empty($device_tokens)){
                            //sendPushNotification($title,$body,$device_tokens,$data,0);        
                        }

                        //send realtime notification
                        sendRealTimeNotification('drvr-' . $driver_id, $data);

                        $error = array("success"=>"The Driver has been previously allocated to this booking and is pending acceptance by the driver. A repeat notification has been sent.");
                        echo json_encode($error); 
                        exit;
                    }

                    $error = array("error"=>"Driver has been allocated to the booking and is pending acceptance by the driver.");
                    echo json_encode($error); 
                    exit;
                }
            }

        }
    }else{
        $error = array("error"=>"Error occured trying to read booking data from database.");
        echo json_encode($error); 
        exit;
    }    

    

    //allocate driver to booking
    $query = sprintf('INSERT INTO %stbl_driver_allocate(booking_id,driver_id,`status`,date_allocated) VALUES 
    ("%d","%d","%d","%s")', 
    DB_TBL_PREFIX,
    $booking_id,
    $driver_id,
    0,
    gmdate('Y-m-d H:i:s', time())
    ); 
    $result = mysqli_query($GLOBALS['DB'], $query);


    //send push notification to driver
    
    $booking_title = str_pad($booking_data['booking_id'] , 5, '0', STR_PAD_LEFT);
    $title = WEBSITE_NAME . " - New Booking";
    $body = "You have a new booking with ID({$booking_title}). Please respond to this booking immediately as your customer is waiting.";
    $device_tokens = !empty($driver_data['push_notification_token']) ? $driver_data['push_notification_token'] : 0;
    if(!empty($device_tokens)){
        sendPushNotification($title,$body,$device_tokens,NULL,0);
    }

    //silent notification
    $title = "";
    $body = "";
    //$photo = explode('/',$bookingsdata['user_photo']);
    $photo_file = isset($booking_data['photo_file']) ? $booking_data['photo_file'] : "0";
    $data = array(
                    "action"=>"driver-allocate",
                    "booking_id" => $booking_data['booking_id'],
                    "p_address" => $booking_data['pickup_address'],
                    "p_lat" => $booking_data['pickup_lat'],
                    "p_lng" => $booking_data['pickup_long'],
                    "d_address" => $booking_data['dropoff_address'],
                    "d_lat" => $booking_data['dropoff_lat'],
                    "d_lng" => $booking_data['dropoff_long'],
                    "rider_image"=> SITE_URL . "ajaxuserphotofile.php?file=". $photo_file,
                    "rider_name"=>$booking_data['user_firstname'],
                    "rider_phone"=>$booking_data['user_phone'],
                    'rider_rating'=>$booking_data['user_rating'],
                    "completion_code"=>$booking_data['completion_code'],
                    "driver_accept_duration" => DRIVER_ALLOCATE_ACCEPT_DURATION,
                    "sent_time"=>time(),
                    "fare"=>$booking_data['estimated_cost'],
                    "payment_type" => $booking_data['payment_type'],
                    "coupon_code"=> $booking_data['coupon_code'],
                    "coupon_discount_type"=> $booking_data['coupon_discount_type'],
                    "coupon_discount_value"=> $booking_data['coupon_discount_value'],
                    "referral_discount_value"=>$booking_data['referral_discount_value'],
                    "referral_used"=>$booking_data['referral_used'],
                    "waypoint1_address" => "",
                    "waypoint1_long" => "",
                    "waypoint1_lat" => "",
                    "waypoint2_address" => "",
                    "waypoint2_long" => "",
                    "waypoint2_lat" => "",
                    "repeatable" => 1
                );
    $device_tokens = !empty($driver_data['push_notification_token']) ? $driver_data['push_notification_token'] : 0;
    if(!empty($device_tokens)){
        //sendPushNotification($title,$body,$device_tokens,$data,0);        
    }

    //send realtime notification
    sendRealTimeNotification('drvr-' . $driver_id, $data);

    
        


    $success = array("success"=>"Driver allocated to booking successfully");
    echo json_encode($success); 
    exit;



}




function updateDashboard(){

    if(empty($_SESSION['loggedin'])){
        $error = array("error"=>"Please re-login and retry.");
        echo json_encode($error); 
        exit; 
    }

    $number_of_bookings = 0;
    $number_of_customers = 0;
    $number_of_available_drivers = 0;
    $todays_earnings = 0.00;
    
    //Get the number of bookings for today
    $start_date = gmdate('Y-m-d', time());
    $end_date = date('Y-m-d', strtotime($start_date . " +1day"));
    $query = sprintf('SELECT COUNT(*) FROM %1$stbl_bookings WHERE DATE(%1$stbl_bookings.date_created) = "%2$s"', DB_TBL_PREFIX,$start_date);

    
    if($result = mysqli_query($GLOBALS['DB'], $query)){
        if(mysqli_num_rows($result)){

        $row = mysqli_fetch_assoc($result);            
        $number_of_bookings = $row['COUNT(*)'];
            
        }
        mysqli_free_result($result);
    } 




    //Get the number of customer registrations for today
    $start_date = gmdate('Y-m-d', time());
    //$end_date = date('Y-m-d', strtotime($start_date . " +1day"));
    $query = sprintf('SELECT COUNT(*) FROM %1$stbl_users WHERE DATE(%1$stbl_users.account_create_date) = "%2$s"', DB_TBL_PREFIX,$start_date);

    //echo mysqli_error($GLOBALS['DB']);

    if($result = mysqli_query($GLOBALS['DB'], $query)){
        if(mysqli_num_rows($result)){

        $row = mysqli_fetch_assoc($result);            
        $number_of_customers = $row['COUNT(*)'];
            
        }
        mysqli_free_result($result);
    }


    //Get the number of online drivers for today
    $query = sprintf('SELECT COUNT(*) FROM %1$stbl_drivers 
    LEFT JOIN %1$stbl_driver_location ON %1$stbl_driver_location.driver_id = %1$stbl_drivers.driver_id
    WHERE %1$stbl_drivers.available = 1 AND %1$stbl_drivers.is_activated = 1 AND (UNIX_TIMESTAMP(%1$stbl_driver_location.location_date) > (UNIX_TIMESTAMP() - %2$d))', DB_TBL_PREFIX, LOCATION_INFO_VALID_AGE);

    //echo mysqli_error($GLOBALS['DB']);

    if($result = mysqli_query($GLOBALS['DB'], $query)){
        if(mysqli_num_rows($result)){

        $row = mysqli_fetch_assoc($result);            
        $number_of_available_drivers = $row['COUNT(*)'];
            
        }
        mysqli_free_result($result);
    }


    //Get earnings for today. convert amount to base currency and sum all
    $query = sprintf('SELECT SUM(%1$stbl_bookings.paid_amount / %1$stbl_bookings.cur_exchng_rate) AS today_earnings FROM %1$stbl_bookings WHERE %1$stbl_bookings.status = 3 AND DATE(%1$stbl_bookings.date_completed) = "%2$s"', DB_TBL_PREFIX, $start_date);

    //echo mysqli_error($GLOBALS['DB']);

    if($result = mysqli_query($GLOBALS['DB'], $query)){
        if(mysqli_num_rows($result)){

            $row = mysqli_fetch_assoc($result);            
            $todays_earnings = $row['today_earnings'];
            $todays_earnings = round($todays_earnings, 2);
            
        }
        mysqli_free_result($result);
    }



    $default_currency_symbol = !empty($_SESSION['default_currency']) ? $_SESSION['default_currency']['symbol'] : "₦";


    $dashdata = array(
        'success' => 1,
        'num_of_bookings'=> $number_of_bookings,
        'num_of_customers'=> $number_of_customers,
        'num_of_available_drivers'=> $number_of_available_drivers,
        'todays_earnings' => !empty($todays_earnings) ? $default_currency_symbol.$todays_earnings : $default_currency_symbol . "0.00"
    );

    
    //return data
    echo json_encode($dashdata);
    exit;  



}

/* function sendPushNotification($title,$body,$device_tokens){

    
    $content_object = array("to"=>$device_tokens,
                    "notification"=>array(
                        "body"=>$body,
                        "title"=>$title,
                        "sound"=>"default"
                    ),
                    "data" => array(
                        "page"=>"login"
                    ) 


    );

    $content_json = json_encode($content_object);

    $curl = curl_init('https://fcm.googleapis.com/fcm/send');
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: application/json","Authorization: key=".PUSH_AUTH_KEY));
    //curl_setopt($curl, CURLOPT_HTTPGET, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $content_json);
    $json_response = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    return;

} */


function getRouteDrivers(){
    if(empty($_SESSION['loggedin'])){
        $error = array("error"=>"Please re-login and retry.");
        echo json_encode($error); 
        exit; 
    }

    $route_id = (int) $_GET['route_id'];
    $drivers_list = "<option value='all'>All Drivers</option>";

    //Get all available drivers and their location
    $drivers_location_data = [];
    $query = sprintf('SELECT %1$stbl_driver_location.location_date,%1$stbl_drivers.country_dial_code,%1$stbl_drivers.driver_id,%1$stbl_drivers.available,%1$stbl_drivers.firstname,%1$stbl_drivers.lastname,%1$stbl_drivers.phone FROM %1$stbl_drivers 
    LEFT JOIN %1$stbl_driver_location ON %1$stbl_driver_location.driver_id = %1$stbl_drivers.driver_id
    WHERE %1$stbl_drivers.route_id = %2$d AND %1$stbl_drivers.is_activated = 1', DB_TBL_PREFIX, $route_id);

    if($result = mysqli_query($GLOBALS['DB'], $query)){ 
        if(mysqli_num_rows($result)){

            while($row = mysqli_fetch_assoc($result)){
                
                $availability = !empty($row['available']) && (strtotime($row['location_date'] . ' UTC') > (time() - LOCATION_INFO_VALID_AGE)) ? "---Available" : "---Unavailable";                               
                $driver_details = $row['firstname'] . " " . $row['lastname'] . "(" . $row['country_dial_code'] . " ". (!empty(DEMO) ? mask_string($row['phone']) : $row['phone']) . ")" . " " . $availability;
                $drivers_list .= "<option data-driverid='{$row['driver_id']}'>{$driver_details}</option>";
            }
                    
        }
        mysqli_free_result($result);
    }


    $dashdata = array(
        'success' => 1,
        'drivers_list'=> $drivers_list        
    );

    
    //return data
    echo json_encode($dashdata);
    exit;

    

}



function messagedriver(){

    $driver_id = (int) $_POST['driver_id'];
    $content = $_POST['content'];

   
    
    if(empty($_SESSION['loggedin'])){
        $error = array("error"=>"Please re-login and retry.");
        echo json_encode($error); 
        exit; 
    }


    $query = sprintf('INSERT INTO %stbl_notifications(person_id,user_type,content,n_type,date_created) VALUES 
        ("%d",1,"%s",1,"%s")', 
        DB_TBL_PREFIX,
        $driver_id,
        mysqli_real_escape_string($GLOBALS['DB'],$content),
        gmdate('Y-m-d H:i:s', time()) 
    );

    if(!$result = mysqli_query($GLOBALS['DB'], $query)){ 
        $error = array("error"=>"Failed to send message.");
        echo json_encode($error); 
        exit;
    }

    //get push notification token for this user and send message through push messaging
    $query = sprintf('SELECT push_notification_token FROM %stbl_drivers WHERE driver_id = %d', DB_TBL_PREFIX, $driver_id);
    if($result = mysqli_query($GLOBALS['DB'], $query)){ 
        if(mysqli_num_rows(($result))){
            $row = mysqli_fetch_assoc($result);
            $title = "Attention!";//WEBSITE_NAME ." Admin";
            sendPushNotification($title,$content,$row['push_notification_token'],NULL,1);
        }
    }


    $success = array("success"=>"Message sent successfully");
    echo json_encode($success); 
    exit;



}


function submitcontactform(){
    

        if(empty($_POST['customername'])){
            
            $response = array('error'=>"Please enter your name");
            echo json_encode($response);
            exit;

        }
        
        if(empty($_POST['email'])){
            
            $response = array('error'=>'Please enter your email');
            echo json_encode($response);
            exit;
        }

        if(!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)){
        
            $response = array("error"=>"Your email is not a valid email format");
            echo json_encode($response); 
            exit;
        }
        
        if(empty($_POST['phone'])){
            
            $response = array("error"=>"Please enter your phone number");
            echo json_encode($response); 
            exit;
        }
        
        
        
        if(empty($_POST['message'])){
            
            $response = array("error"=>"Please enter your message");
            echo json_encode($response); 
            exit;

        }
        
        if(empty($_SESSION['captcha'])){
            
            $response = array("error"=>"Captcha error");
            echo json_encode($response); 
            exit;
        }
        
        if(isset($_SESSION['captcha']) && $_SESSION['captcha'] != $_POST['captcha'] ){
            
            $response = array("error"=>"Captcha does not match");
            echo json_encode($response); 
            exit;
        }

        $customer_name = ucfirst(strtolower($_POST['customername']));
        $subject = !empty($_POST['subject']) ? "[". WEBSITE_NAME . "] Re: " .$_POST['subject'] : WEBSITE_NAME . "Team";

        //Send an email to the user confirming receipt of his mail
        $message ="";
        //composing the email body.
        $message .= '<html>';
        $message .= '<div><img src="'.SITE_URL.'img/logo-mid.png" width="200px" /><br><br>';
        $message .="<p>Hi {$customer_name},<br>

        Thanks for contacting us! Your request has been received and we'll get back to you as soon as we can (typically within 24 hours).<br><br>
        
         
        Regards,<br>" . WEBSITE_NAME ." Team</p>";
        $message .="<br><br>";
        $message .="</div>";
        $message .="</html >";

        $mail_sender_address = 'From:' . WEBSITE_NAME . "<" . CONTACT_EMAIL . ">";
        $headers = array($mail_sender_address,'MIME-Version: 1.0', 'Content-Type: text/html; charset="iso-8859-1"'); //Required for a HTML formatted E-mail ;)

        
        mail($_POST['email'], $subject, $message, join("\r\n", $headers));


        //send message to admin
        $message = "";

        $message .= 'This message was sent to you from the contact form on ' . WEBSITE_NAME . "\r\n" . "\r\n";
        $message .= "Sender's name: " . $_POST['customername']."\r\n";
        $message .= "Sender's email: " . $_POST['email']."\r\n";
        $message .= "Sender's pnumber: " . $_POST['phone']."\r\n";
        $message .= "Sender's subject: " . $_POST['subject']."\r\n";
        $message .= "Sender's message: " . $_POST['message']."\r\n";

        $reply_to = 'Reply-To: ' . $_POST['email'];


        $headers = array('From:' . WEBSITE_NAME . "<" . CONTACT_EMAIL . ">",$reply_to,'MIME-Version: 1.0'); //Required for a HTML formatted E-mail ;)

        //echo $message;
        $mail_sender_address = 'From: '.CONTACT_EMAIL;
        mail(CONTACT_EMAIL, 'Contact Form Message  - '.$subject, $message, join("\r\n", $headers));


        $response = array("success"=>1);
        echo json_encode($response); 
        exit;
        


}


function messagecustomer(){

    $user_id = (int) $_POST['user_id'];
    $content = $_POST['content'];

   
    
    if(empty($_SESSION['loggedin'])){
        $error = array("error"=>"Please re-login and retry.");
        echo json_encode($error); 
        exit; 
    }

    


    $query = sprintf('INSERT INTO %stbl_notifications(person_id,user_type,content,n_type,date_created) VALUES 
        ("%d",0,"%s",1,"%s")', 
        DB_TBL_PREFIX,
        $user_id,
        mysqli_real_escape_string($GLOBALS['DB'],$content),
        gmdate('Y-m-d H:i:s', time()) 
    );

    if(!$result = mysqli_query($GLOBALS['DB'], $query)){ 
        $error = array("error"=>"Failed to send message.");
        echo json_encode($error); 
        exit;
    }

    //get push notification token for this user and send message through push messaging
    $query = sprintf('SELECT push_notification_token FROM %stbl_users WHERE user_id = %d', DB_TBL_PREFIX, $user_id);
    if($result = mysqli_query($GLOBALS['DB'], $query)){ 
        if(mysqli_num_rows(($result))){
            $row = mysqli_fetch_assoc($result);
            $title = "Attention!";//WEBSITE_NAME ." Admin";
            sendPushNotification($title,$content,$row['push_notification_token'],NULL,1);
        }
    }

    


    $success = array("success"=>"Message sent successfully");
    echo json_encode($success); 
    exit;



}


function getrouterides(){
    
    $tariff_data = [];
    $rides_data = [];


    $route_id = (int) $_POST['route_id'];
    

   
    
    if(empty($_SESSION['loggedin'])){
        $error = array("error"=>"Please re-login and retry.");
        echo json_encode($error); 
        exit; 
    }


    $query = sprintf('SELECT *,%1$stbl_routes.id AS route_id  FROM %1$stbl_routes
    INNER JOIN %1$stbl_rides_tariffs ON %1$stbl_rides_tariffs.routes_id = %1$stbl_routes.id
    INNER JOIN %1$stbl_rides ON %1$stbl_rides_tariffs.ride_id = %1$stbl_rides.id
    WHERE %1$stbl_rides.avail = 1', DB_TBL_PREFIX);

    if($result = mysqli_query($GLOBALS['DB'], $query)){
        if(mysqli_num_rows($result)){
            while($row = mysqli_fetch_assoc($result)){
                $tariff_data[] = $row;
            }
                            
        }    
    }
    



    
    $data_array = [];

    //sort rides tarif data
    foreach($tariff_data as $tariffdata){

        $rides_data[$tariffdata['route_id']]['r_id'] = $tariffdata['route_id'];
        $rides_data[$tariffdata['route_id']]['cars'][] = $tariffdata;
        $select_options = '';
        foreach($rides_data[$tariffdata['route_id']]['cars'] as $ridesdata){

            $select_options .= "<option data-cpk = {$ridesdata['cost_per_km']} data-cpm = {$ridesdata['cost_per_minute']} data-puc = {$ridesdata['pickup_cost']} data-doc={$ridesdata['drop_off_cost']} data-cc={$ridesdata['cancel_cost']} data-ncpk = {$ridesdata['ncost_per_km']} data-ncpm = {$ridesdata['ncost_per_minute']} data-npuc = {$ridesdata['npickup_cost']} data-ndoc={$ridesdata['ndrop_off_cost']} data-ncc={$ridesdata['ncancel_cost']} value={$ridesdata['ride_id']} data-rideid={$ridesdata['ride_id']} data-ridedesc={$ridesdata['ride_desc']}>{$ridesdata['ride_type']}</option>";
            
        }

        $rides_data[$tariffdata['route_id']]['cars_html'] = $select_options;

    }

    

    $data_array = array("success"=>1,'result'=>$rides_data);


    
    echo json_encode($data_array); 
    exit;



}




function customersautocomp(){
    
    $term = $_POST['term'];
    $auto_complete2 = [];
    $auto_complete1 = [];

    if(empty($_SESSION['loggedin'])){        
        echo ""; 
        exit; 
    }

    $query_modifier = "= 1 AND (" .DB_TBL_PREFIX . "tbl_users.firstname LIKE '" . $term . "%'" . " OR " . DB_TBL_PREFIX . "tbl_users.lastname LIKE '" . $term . "%' ) LIMIT 0,10"; 
    $query = sprintf('SELECT %1$stbl_users.account_type, %1$stbl_users.country_dial_code,%1$stbl_users.user_id,%1$stbl_users.phone, %1$stbl_users.firstname, %1$stbl_users.lastname FROM %1$stbl_users WHERE %1$stbl_users.account_type %2$s', DB_TBL_PREFIX, $query_modifier);  //Get and count all data


    /* echo mysqli_error($GLOBALS['DB']); */

    if($result = mysqli_query($GLOBALS['DB'], $query)){
        if(mysqli_num_rows($result)){
            while($row = mysqli_fetch_assoc($result)){
                $auto_complete1['label'] = $row['firstname'] ." ".$row['lastname'] . " (" . $row['country_dial_code'] . " " . (!empty(DEMO) ? mask_string($row['phone']) : $row['phone']) . ")";
                $auto_complete1['value'] = $row['user_id'];
                $auto_complete2[] = $auto_complete1; 
            }    
        }
        mysqli_free_result($result);
    } 

    


    echo json_encode($auto_complete2);
    
    //echo $query;
    exit;



}


function staffautocomp(){

    $term = $_POST['term'];
    $auto_complete2 = [];
    $auto_complete1 = [];

    if(empty($_SESSION['loggedin'])){        
        echo ""; 
        exit; 
    }

    $query_modifier = "!= 1 AND (" .DB_TBL_PREFIX . "tbl_users.firstname LIKE '" . $term . "%'" . " OR " . DB_TBL_PREFIX . "tbl_users.lastname LIKE '" . $term . "%' ) LIMIT 0,10"; 
    $query = sprintf('SELECT %1$stbl_users.account_type, %1$stbl_users.country_dial_code,%1$stbl_users.user_id,%1$stbl_users.phone, %1$stbl_users.firstname, %1$stbl_users.lastname FROM %1$stbl_users WHERE %1$stbl_users.account_type %2$s', DB_TBL_PREFIX, $query_modifier);  //Get and count all data


    /* echo mysqli_error($GLOBALS['DB']); */

    if($result = mysqli_query($GLOBALS['DB'], $query)){
        if(mysqli_num_rows($result)){
            while($row = mysqli_fetch_assoc($result)){
                $auto_complete1['label'] = $row['firstname'] ." ".$row['lastname'] . " (" . $row['country_dial_code'] . " " . (!empty(DEMO) ? mask_string($row['phone']) : $row['phone']) . ")";
                $auto_complete1['value'] = $row['user_id'];
                $auto_complete2[] = $auto_complete1; 
            }    
        }
        mysqli_free_result($result);
    } 

    
    

    

    echo json_encode($auto_complete2);
    
    //echo $query;
    exit;



}


function getDriverLocation(){

    if(empty($_SESSION['loggedin'])){        
        echo ""; 
        exit; 
    }

    $driver_id = mysqli_real_escape_string($GLOBALS['DB'], $_POST['driver_id']);
    //$driver_id = !empty($_POST['driver_id']) ? (int) $_POST['driver_id'] : 0;
    $drivers_location_items = [];
    $location_info_age = gmdate('Y-m-d H:i:s', time() - LOCATION_INFO_VALID_AGE);

    if($driver_id == 'all_d'){
        $query = sprintf('SELECT * FROM %1$stbl_driver_location 
        INNER JOIN %1$stbl_drivers ON %1$stbl_drivers.driver_id = %1$stbl_driver_location.driver_id
        WHERE %1$stbl_drivers.available = 1 /* AND %1$stbl_driver_location.location_date > "%2$s" */ LIMIT 500', DB_TBL_PREFIX, $location_info_age);
    }elseif($driver_id == 'all_d_c'){

        $route_id = (int) $_POST['route_id'];
        $query = sprintf('SELECT * FROM %1$stbl_driver_location 
        INNER JOIN %1$stbl_drivers ON %1$stbl_drivers.driver_id = %1$stbl_driver_location.driver_id
        WHERE %1$stbl_drivers.route_id = %2$d AND %1$stbl_drivers.available = 1 /* AND %1$stbl_driver_location.location_date > "%3$s" */', DB_TBL_PREFIX, $route_id,$location_info_age);

    }else{
        $query = sprintf('SELECT * FROM %1$stbl_driver_location 
        INNER JOIN %1$stbl_drivers ON %1$stbl_drivers.driver_id = %1$stbl_driver_location.driver_id
        WHERE %1$stbl_driver_location.driver_id = %2$d ', DB_TBL_PREFIX, $driver_id);
    }

    

    if($result = mysqli_query($GLOBALS['DB'], $query)){
    
        if(mysqli_num_rows($result)){
            while($row = mysqli_fetch_assoc($result)){
                $drivers_location_items[$row['driver_id']]['driver_id'] = $row['driver_id'];
                $drivers_location_items[$row['driver_id']]['lat'] = $row['lat'];
                $drivers_location_items[$row['driver_id']]['lng'] = $row['long'];
                $drivers_location_items[$row['driver_id']]['name'] = $row['firstname'] . ' ' . $row['lastname'];
                $drivers_location_items[$row['driver_id']]['view_link'] = "<a href='view-driver.php?id={$row['driver_id']}' >View</a>"; 
                $drivers_location_items[$row['driver_id']]['location_date'] = date('d/m/Y g:i A',strtotime($row['location_date'] . ' UTC'));
            }
        
        }else{
            echo ""; 
            exit; 
        }
        mysqli_free_result($result);
    }else{
        echo ""; 
        exit; 
    }

    $data = array(
        'success' => 1,
        'data' => $drivers_location_items
    );

    
    //return data
    echo json_encode($data);
    exit;    
    


}


function driversautocomp(){

    $term = $_POST['term'];
    $bookrecsearch = !empty($_POST['bookrecsearch']) ? 1 : 0;
    $auto_complete2 = [];
    $auto_complete1 = [];

    if(empty($_SESSION['loggedin'])){        
        echo ""; 
        exit; 
    }

    if($bookrecsearch){
        $query_modifier = DB_TBL_PREFIX . "tbl_bookings.driver_firstname LIKE '" . $term . "%'" . " OR " . DB_TBL_PREFIX . "tbl_bookings.driver_lastname LIKE '" . $term . "%' LIMIT 0,10"; 
        $query = sprintf('SELECT %1$stbl_bookings.driver_id,%1$stbl_bookings.driver_phone AS phone, %1$stbl_bookings.driver_firstname AS firstname, %1$stbl_bookings.driver_lastname AS lastname FROM %1$stbl_bookings WHERE %2$s', DB_TBL_PREFIX, $query_modifier);  //Get and count all data

    }else{
        $query_modifier = DB_TBL_PREFIX . "tbl_drivers.firstname LIKE '" . $term . "%'" . " OR " . DB_TBL_PREFIX . "tbl_drivers.lastname LIKE '" . $term . "%' LIMIT 0,10"; 
        $query = sprintf('SELECT %1$stbl_drivers.driver_id,%1$stbl_drivers.country_dial_code,%1$stbl_drivers.phone, %1$stbl_drivers.firstname, %1$stbl_drivers.lastname FROM %1$stbl_drivers WHERE %2$s', DB_TBL_PREFIX, $query_modifier);  //Get and count all data
    }

    /* echo mysqli_error($GLOBALS['DB']); */

    if($result = mysqli_query($GLOBALS['DB'], $query)){
        if(mysqli_num_rows($result)){
            while($row = mysqli_fetch_assoc($result)){
                $auto_complete1['label'] = $row['firstname'] ." ".$row['lastname'] . " (" . $row['country_dial_code'] . " " . (!empty(DEMO) ? mask_string($row['phone']) : $row['phone']) . ")";
                $auto_complete1['value'] = $row['driver_id'];
                $auto_complete2[] = $auto_complete1; 
                
            }    
        }
        mysqli_free_result($result);
    } 

    

    echo json_encode($auto_complete2);
    
    //echo $query;
    exit;



}



function getuserprofileinfo(){

    if(empty($_SESSION['loggedin'])){
        $error = array("error"=>"Please re-login and retry.");
        echo json_encode($error); 
        exit; 
    }

    $profiledata = array(
        'success' => 1,
        'firstname'=> $_SESSION['firstname'],
        'lastname'=> $_SESSION['lastname'],
        'email'=> $_SESSION['email'],
        'phone'=> $_SESSION['phone'],
        'address'=> $_SESSION['address']

    );

    
    //return data
    echo json_encode($profiledata);
    exit;


}


function getroutetariffs(){

    $tariff_data = [];
    $rides_data = [];


       
    
    

    $query = sprintf('SELECT *,%1$stbl_routes.id AS route_id  FROM %1$stbl_routes
    INNER JOIN %1$stbl_rides_tariffs ON %1$stbl_rides_tariffs.routes_id = %1$stbl_routes.id
    INNER JOIN %1$stbl_rides ON %1$stbl_rides_tariffs.ride_id = %1$stbl_rides.id
    WHERE %1$stbl_rides.avail = 1', DB_TBL_PREFIX);

    if($result = mysqli_query($GLOBALS['DB'], $query)){
        if(mysqli_num_rows($result)){
            while($row = mysqli_fetch_assoc($result)){
                $tariff_data[] = $row;
            }
                            
        }    
    }
    



    
    $data_array = [];
    $city_select_options = '';
    $state_select_options = '';
    $tariff_ids = [];
    $sel_route_id = !empty($_POST['sel_route_id']) ? $_POST['sel_route_id'] : 0;
    $sel_route_name = !empty($_POST['sel_route_name']) ? $_POST['sel_route_name'] : 0;
    $count = 0;
    $route_selected = '';
    $rides_ids = [];
    $rides_url = '';
    
    //sort rides tarif data
    foreach($tariff_data as $tariffdata){
        $count ++;
        $rides_data[$tariffdata['route_id']]['r_id'] = $tariffdata['route_id'];
        
        if(empty($sel_route_id)){ 
            if($count == 1){
               //    $route_selected = "checked";
            }else{
                $route_selected = "";
            }    
        }else{
            if($sel_route_id == $tariffdata['route_id'] && $sel_route_name == $tariffdata['r_title']){
                //$route_selected = "checked";
                $rides_data['route-exists'] = 1;
            }else{
                $route_selected = "";
            }
            
        }

        if(array_search($tariffdata['route_id'],$tariff_ids) === false){
            if($tariffdata['r_scope'] == 0){
                $tariff_ids[] = $tariffdata['route_id'];
                $rides_data['city_name'][] = $tariffdata['r_title'];
                $rides_data['city_id'][] = $tariffdata['route_id'];
                $route_name_variable = "'" . $tariffdata['r_title'] . "'"; 
                $city_select_options .= "<ons-list-item tappable class='city-route-list' onclick = routecityitemselected({$tariffdata['route_id']}) data-routename='{$tariffdata['r_title']}' id=route-sel-{$tariffdata['route_id']} ><label class='left'><ons-radio {$route_selected} name='city-route' id='radio-sel-{$tariffdata['route_id']}' input-id='radio-{$tariffdata['route_id']}'></ons-radio></label><label for='radio-{$tariffdata['route_id']}' class='center'>{$tariffdata['r_title']}</label></ons-list-item>";
            }else{
                $tariff_ids[] = $tariffdata['route_id'];
                $rides_data['state_name'][] = $tariffdata['r_title'];
                $rides_data['state_id'][] = $tariffdata['route_id'];
                $route_name_variable = "'" . $tariffdata['r_title'] . "'"; 
                $state_select_options .= "<ons-list-item data-plng='{$tariffdata['pick_lng']}' data-plat='{$tariffdata['pick_lat']}' data-dlng='{$tariffdata['drop_lng']}' data-dlat='{$tariffdata['drop_lat']}' data-pus='{$tariffdata['pick_name']}' data-dos='{$tariffdata['drop_name']}' tappable class='state-route-list' onclick = routestateitemselected({$tariffdata['route_id']}) data-routename='{$tariffdata['r_title']}' id=route-sel-{$tariffdata['route_id']} ><label for='radio-{$tariffdata['route_id']}' class='center'>{$tariffdata['r_title']}</label></ons-list-item>";
            }
        }     

        $rides_data[$tariffdata['route_id']]['cars'][] = $tariffdata;
        $select_options = '';
        foreach($rides_data[$tariffdata['route_id']]['cars'] as $ridesdata){
            
           
            $ride_filename = explode('/',$ridesdata['ride_img']);
            $ride_image = SITE_URL . 'img/ride_imgs/' . array_pop($ride_filename);
            if(array_search($ridesdata['ride_id'],$rides_ids) === false){
                $rides_ids[] = $ridesdata['ride_id'];
                $rides_url .= "<img src={$ride_image} >";
            }
             
            $select_options .= "<option data-img='{$ride_image}' data-cpk = '{$ridesdata['cost_per_km']}' data-cpm = '{$ridesdata['cost_per_minute']}' data-puc = '{$ridesdata['pickup_cost']}' data-doc='{$ridesdata['drop_off_cost']}' data-cc='{$ridesdata['cancel_cost']}' data-ncpk = '{$ridesdata['ncost_per_km']}' data-ncpm = '{$ridesdata['ncost_per_minute']}' data-npuc = '{$ridesdata['npickup_cost']}' data-ndoc='{$ridesdata['ndrop_off_cost']}' data-ncc='{$ridesdata['ncancel_cost']}' value='{$ridesdata['ride_id']}' data-rideid='{$ridesdata['ride_id']}' data-ridedesc='{$ridesdata['ride_desc']}'>{$ridesdata['ride_type']}</option>";
            
        }

        $rides_data[$tariffdata['route_id']]['cars_html'] = $select_options;

    }

        
    $rides_data['city'] = $city_select_options;
    $rides_data['state'] = $state_select_options;
    $rides_data['preloadrides'] = $rides_url;
    $rides_data['payment_options'] = "<option value='1'>Cash / POS</option><option value='2'>Wallet</option>";
    $rides_data['nighttime'] = array('start_hour'=>NIGHT_START, 'end_hour'=>NIGHT_END);
    $data_array = array("success"=>1,'result'=>$rides_data);


    
    return $data_array; 
    exit;




}



function getgooglemapapikey(){

    $data_array = array("success"=>1,'api_key'=>GMAP_API_KEY);    
    echo json_encode($data_array); 
    exit;



}

function getcallcenternum(){

    $data_array = array("success"=>1,'cc_num'=>CALL_CENTER_NUMBER);    
    echo json_encode($data_array); 
    exit;

}


function getwalletinfo(){

    $user_wallet_details = [];

    if(empty($_SESSION['loggedin'])){
        $error = array("error"=>"Please re-login and retry.");
        echo json_encode($error); 
        exit; 
    }

    $template = '';

    for($i = 0; $i < 100; $i++){

        $template .= "<ons-list-item>Item {$i} </ons-list-item>";

    }

    //Get wallet amount

    $query = sprintf('SELECT wallet_amount FROM %stbl_users WHERE user_id = "%d"', DB_TBL_PREFIX, $_SESSION['uid']); //Get required user information from DB


    if($result = mysqli_query($GLOBALS['DB'], $query)){
        if(mysqli_num_rows($result)){
            $user_wallet_details = mysqli_fetch_assoc($result);
        }
        else{
            $error = array("error"=>"Cannot get user wallet information");
            echo json_encode($error); //invalid account
            exit;

        }
        
    }
    else{ //No record matching the USER ID was found in DB. Show view to notify user

        $error = array("error"=>"Database Error");
        echo json_encode($error); //database error
        exit;
    }

    $_SESSION['wallet_amt'] = $user_wallet_details['wallet_amount'];

    $data_array = array("success"=>1,'wallet_amt'=>$_SESSION['wallet_amt'], 'wallet_history' => $template);    
    echo json_encode($data_array); 
    exit;

}


function newbooking(){

    if(empty($_SESSION['loggedin'])){
        $error = array("error"=>"Please re-login and retry.");
        echo json_encode($error); 
        exit; 
    }

    $tariff_data = [];

    $paddress = mysqli_real_escape_string($GLOBALS['DB'], $_POST['paddress']);
    $daddress = mysqli_real_escape_string($GLOBALS['DB'], $_POST['daddress']);
    $plng = mysqli_real_escape_string($GLOBALS['DB'], $_POST['plng']);
    $plat = mysqli_real_escape_string($GLOBALS['DB'], $_POST['plat']);
    $dlng = mysqli_real_escape_string($GLOBALS['DB'], $_POST['dlng']);
    $dlat = mysqli_real_escape_string($GLOBALS['DB'], $_POST['dlat']);
    $payment_type = (int) $_POST['p_type'];
    $pdatetime = mysqli_real_escape_string($GLOBALS['DB'], $_POST['pdatetime']);
    $ride_id = (int) $_POST['ride_id'];
    $route_id = (int) $_POST['route_id'];

    //format date time
    $pdatetime = gmdate('Y-m-d H:i:s',strtotime($pdatetime));


    $query = sprintf('SELECT * FROM %stbl_rides_tariffs WHERE routes_id = "%d" AND ride_id = "%d"', DB_TBL_PREFIX, $route_id,$ride_id); //Get required user information from DB

    if($result = mysqli_query($GLOBALS['DB'], $query)){
        if(mysqli_num_rows($result)){
            $tariff_data = mysqli_fetch_assoc($result);                    
        }else{
            $error = array("error"=>"Error booking your ride. Please retry.");
            echo json_encode($error); //database error
            exit;
        }
        
    }
    else{ //No record matching the USER ID was found in DB. Show view to notify user

        $error = array("error"=>"Error booking your ride. Please retry.");
        echo json_encode($error); //database error
        exit;
    }

    
    //Get distance data from google maps
    $url = "https://maps.googleapis.com/maps/api/directions/json?origin={$plat},{$plng}&destination={$dlat},{$dlng}&key=" . GMAP_API_KEY;
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPGET, true);
    curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, false);
    //curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
    $json_response = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    $response = json_decode($json_response, true);
    if(json_last_error()){
        $error = array("error"=>"Error booking your ride. Please retry.");
        echo json_encode($error); //database error
        exit;
    }

    $pickup_cost = $tariff_data['pickup_cost'];
    $drop_off_cost = $tariff_data['drop_off_cost'];
    $cost_per_km = $tariff_data['cost_per_km'];
    $cost_per_minute = $tariff_data['cost_per_minute'];


    $distance = $response['routes'][0]['legs'][0]['distance']['value'];
    $duration = $response['routes'][0]['legs'][0]['duration']['value'];
    $price = round(($cost_per_km * $distance/1000) + ($cost_per_minute * $duration/60) + $drop_off_cost + $pickup_cost,2);
    
    $query = sprintf('INSERT INTO %stbl_bookings (user_firstname,user_lastname,user_phone,user_id,pickup_datetime, pickup_address, pickup_long, pickup_lat, dropoff_address, dropoff_long,dropoff_lat,estimated_cost,route_id,ride_id,payment_type,date_created) VALUES'.
    '("%s","%s","%s","%d","%s","%s","%s","%s","%s","%s","%s","%s","%d","%d","%d","%s")', 
    DB_TBL_PREFIX, 
    $_SESSION['firstname'],
    $_SESSION['lastname'],
    $_SESSION['phone'],
    $_SESSION['uid'],
    $pdatetime,
    $paddress,
    $plng,
    $plat,
    $daddress,
    $dlng,
    $dlat,
    $price,
    $route_id,
    $ride_id,
    $payment_type,
    gmdate('Y-m-d H:i:s', time()) 
    );


    if(! $result = mysqli_query($GLOBALS['DB'], $query)){
        $error = array("error"=>"Error booking your ride. Please retry.");
        echo json_encode($error); //database error
        exit;
        
    }


    $data_array = array("success"=>1);    
    echo json_encode($data_array); 
    exit;

}

function getbookings(){

    if(empty($_SESSION['loggedin'])){
        $error = array("error"=>"Please re-login and retry.");
        echo json_encode($error); 
        exit; 
    }

    $booking_data = [];
    $booking_pend_onride = '';
    $booking_completed = '';
    $booking_cancelled = '';


    $query = sprintf('SELECT *, DATE(%1$stbl_bookings.date_created) AS created_date,%1$stbl_bookings.id AS booking_id FROM %1$stbl_bookings 
    LEFT JOIN %1$stbl_drivers ON %1$stbl_drivers.driver_id = %1$stbl_bookings.driver_id
    LEFT JOIN %1$stbl_rides ON %1$stbl_rides.id = %1$stbl_bookings.ride_id
    LEFT JOIN %1$stbl_users ON %1$stbl_users.user_id = %1$stbl_bookings.user_id
    WHERE %1$stbl_bookings.user_id = %2$s ORDER BY %1$stbl_bookings.date_created DESC LIMIT 0,200 ', DB_TBL_PREFIX,$_SESSION['uid']);


    if($result = mysqli_query($GLOBALS['DB'], $query)){

        if(mysqli_num_rows($result)){
            while($row = mysqli_fetch_assoc($result)){
                $booking_data[] = $row;
            }
            
            mysqli_free_result($result);
        }else{

            $error = array("error"=>"You do not have any booking records.");
            echo json_encode($error); 
            exit; 

        }
    }else{

        $error = array("error"=>"Error retrieving booking records.");
        echo json_encode($error); 
        exit; 

    }

    //sort booking data
    $booking_data_sort = [];

    foreach($booking_data as $bookingdata){
        if($bookingdata['status'] == 0 || $bookingdata['status'] == 1){ //pending or onride
            $booking_data_sort[$bookingdata['created_date']]['date'] = $bookingdata['created_date'];
            $booking_data_sort[$bookingdata['created_date']]['pend_onride'][] = $bookingdata;
        }elseif($bookingdata['status'] == 3){ //completed
            $booking_data_sort[$bookingdata['created_date']]['date'] = $bookingdata['created_date'];
            $booking_data_sort[$bookingdata['created_date']]['completed'][] = $bookingdata;
        }elseif($bookingdata['status'] == 2){ //cancelled
            $booking_data_sort[$bookingdata['created_date']]['date'] = $bookingdata['created_date'];
            $booking_data_sort[$bookingdata['created_date']]['cancelled'][] = $bookingdata;
        }        
        

    }


    //format for display on app


    foreach($booking_data_sort as $bookingdatasort){

        if(!empty($bookingdatasort['pend_onride'])){
            //save date
            $booking_pend_onride .= "<ons-list-header>{$bookingdatasort['date']}</ons-list-header>";


            //format pending onride rides for this date
            foreach($bookingdatasort['pend_onride'] as $bookingdatasort_po){
                $booking_time = date('g:i A',strtotime($bookingdatasort_po['date_created'] . ' UTC'));

                
                $booking_ptime = date('g:i A',strtotime($bookingdatasort_po['pickup_datetime'] . ' UTC'));
                $booking_driver = isset($bookingdatasort_po['driver_id']) ? $bookingdatasort_po['driver_firstname'] ." " . $bookingdatasort_po['driver_lastname'] : "N/A";
                $booking_driver_assigned = isset($bookingdatasort_po['driver_id']) ? 1 : 0;
                $status = '';
                $close_btn = '';
                if($bookingdatasort_po['status'] == 0){
                    $status = "<span style='color:#e541e5'>[Pending]</span>";
                    $close_btn = "<span style='display:inline-block;float:right'><ons-icon onclick = 'bookingcancel({$bookingdatasort_po['booking_id']},{$booking_driver_assigned})' icon='fa-times' size='18px' style='color:red'></ons-icon></span>";
                }else{
                    $status = "<span style='color:lightgreen'>[On-ride]</span>";
                    $close_btn = '';
                }
                $booking_pdate_time = date('d/m/Y g:i A',strtotime($bookingdatasort_po['pickup_datetime'] . ' UTC'));
                $drvr_photo = explode('/',$bookingdatasort_po['photo_file']);
                $drvr_photo_file = isset($drvr_photo[5]) ? SITE_URL . "photofile.php?file=".$drvr_photo[5] : "0";
                $booking_payment_type = '';
                if(!empty($bookingdatasort_po['payment_type'])){
                    if($bookingdatasort_po['payment_type'] == 2){
                        $booking_payment_type = "Wallet";
                    }else{
                        $booking_payment_type = "Cash / POS";
                    }
                }
                $ride_filename = explode('/',$bookingdatasort_po['ride_img']);
                $ride_image = SITE_URL . 'img/ride_imgs/' . array_pop($ride_filename);
                $booking_title = str_pad($bookingdatasort_po['booking_id'] , 5, '0', STR_PAD_LEFT);
                $booking_pend_onride .= "<ons-list-item data-ridedesc='{$bookingdatasort_po['ride_desc']}'  data-driverphone='{$bookingdatasort_po['driver_phone']}' data-ptype='{$booking_payment_type}' data-put='{$booking_pdate_time}' data-driverimg='{$drvr_photo_file}' data-rideimg='{$ride_image}' data-drivername='{$booking_driver}' data-cost='{$bookingdatasort_po['estimated_cost']}' data-ride='{$bookingdatasort_po['ride_type']}' data-pul='{$bookingdatasort_po['pickup_address']}' data-dol='{$bookingdatasort_po['dropoff_address']}' data-btitle='{$booking_title}' id='booking-list-item-{$bookingdatasort_po['booking_id']}' modifier='longdivider'>
                
                                            <div class='center'>
                                                <div style='width:100%'><span class='list-item__title'>{$booking_time} {$status} </span> | <span onclick='showbookingdetails({$bookingdatasort_po['booking_id']})' style='color:skyblue'>View details</span> {$close_btn}</div>
                                                <span class='list-item__subtitle'><span style='color:yellow'>Booking ID:</span> {$booking_title}</span>
                                                <span class='list-item__subtitle'><span style='color:lightgreen'>Pick-up:</span> {$bookingdatasort_po['pickup_address']}</span>
                                                <span class='list-item__subtitle'><span style='color:orange'>Drop-off:</span> {$bookingdatasort_po['dropoff_address']}</span>
                                                <span class='list-item__subtitle'><span style='color:cyan'>Driver:</span> {$booking_driver}</span>
                                                
                                            </div>
                                        
                                        </ons-list-item>";

            }


        }
        
        if(!empty($bookingdatasort['completed'])){
            //save date
            $booking_completed .= "<ons-list-header>{$bookingdatasort['date']}</ons-list-header>";


            //format pending onride rides for this date
            foreach($bookingdatasort['completed'] as $bookingdatasort_comp){
                $booking_time = date('g:i A',strtotime($bookingdatasort_comp['date_created'] . ' UTC'));
                $booking_ptime = date('g:i A',strtotime($bookingdatasort_comp['pickup_datetime'] . ' UTC'));
                $booking_dtime = isset($bookingdatasort_comp['dropoff_datetime']) ? date('g:i A',strtotime($bookingdatasort_comp['dropoff_datetime'] . ' UTC')) : "N/A";
                $booking_paid_amt = isset($bookingdatasort_comp['paid_amount']) ? $bookingdatasort_comp['paid_amount'] : "N/A";
                $booking_driver = isset($bookingdatasort_comp['driver_id']) ? $bookingdatasort_comp['driver_firstname'] ." " . $bookingdatasort_comp['driver_lastname'] : "N/A";

                $booking_pdate_time = date('d/m/Y g:i A',strtotime($bookingdatasort_comp['pickup_datetime'] . ' UTC'));
                $drvr_photo = explode('/',$bookingdatasort_comp['photo_file']);
                $drvr_photo_file = isset($drvr_photo[5]) ? SITE_URL . "photofile.php?file=".$drvr_photo[5] : "0";
                $booking_payment_type = '';
                if(!empty($bookingdatasort_comp['payment_type'])){
                    if($bookingdatasort_comp['payment_type'] == 2){
                        $booking_payment_type = "Wallet";
                    }else{
                        $booking_payment_type = "Cash / POS";
                    }
                }
                $ride_filename = explode('/',$bookingdatasort_comp['ride_img']);
                $ride_image = SITE_URL . 'img/ride_imgs/' . array_pop($ride_filename);
                $booking_title = str_pad($bookingdatasort_comp['booking_id'] , 5, '0', STR_PAD_LEFT);

                $booking_completed .= "<ons-list-item data-ridedesc='{$bookingdatasort_comp['ride_desc']}'  data-driverphone='{$bookingdatasort_comp['driver_phone']}' data-ptype='{$booking_payment_type}' data-put='{$booking_pdate_time}' data-driverimg='{$drvr_photo_file}' data-rideimg='{$ride_image}' data-drivername='{$booking_driver}' data-cost='{$bookingdatasort_comp['estimated_cost']}' data-ride='{$bookingdatasort_comp['ride_type']}' data-pul='{$bookingdatasort_comp['pickup_address']}' data-dol='{$bookingdatasort_comp['dropoff_address']}' data-btitle='{$booking_title}' id='booking-list-item-{$bookingdatasort_comp['booking_id']}' modifier='longdivider'>
                                            <div class='center'>
                                                <span class='list-item__title'>{$booking_time} | <span onclick='showbookingdetails({$bookingdatasort_comp['booking_id']})' style='color:skyblue'>View details</span> </span>
                                                <span class='list-item__subtitle'><span style='color:yellow'>Booking ID:</span> {$booking_title}</span>
                                                <span class='list-item__subtitle'><span style='color:lightgreen'>Pick-up:</span> {$bookingdatasort_comp['pickup_address']}</span>
                                                <span class='list-item__subtitle'><span style='color:orange'>Drop-off:</span> {$bookingdatasort_comp['dropoff_address']}</span>
                                                <span class='list-item__subtitle'><span style='color:cyan'>Driver: </span>{$booking_driver}</span>
                                            </div>
                                        
                                        </ons-list-item>";

            }


        }


        if(!empty($bookingdatasort['cancelled'])){
            //save date
            $booking_cancelled .= "<ons-list-header>{$bookingdatasort['date']}</ons-list-header>";


            //format pending onride rides for this date
            foreach($bookingdatasort['cancelled'] as $bookingdatasort_canc){
                $booking_time = date('g:i A',strtotime($bookingdatasort_canc['date_created'] . ' UTC'));
                $booking_ptime = date('g:i A',strtotime($bookingdatasort_canc['pickup_datetime'] . ' UTC'));
                $booking_driver = isset($bookingdatasort_canc['driver_id']) ? $bookingdatasort_canc['driver_firstname'] ." " . $bookingdatasort_canc['driver_lastname'] : "N/A";
                

                $booking_pdate_time = date('d/m/Y g:i A',strtotime($bookingdatasort_canc['pickup_datetime'] . ' UTC'));
                $drvr_photo = explode('/',$bookingdatasort_canc['photo_file']);
                $drvr_photo_file = isset($drvr_photo[5]) ? SITE_URL . "photofile.php?file=".$drvr_photo[5] : "0";
                $booking_payment_type = '';
                if(!empty($bookingdatasort_canc['payment_type'])){
                    if($bookingdatasort_canc['payment_type'] == 2){
                        $booking_payment_type = "Wallet";
                    }else{
                        $booking_payment_type = "Cash / POS";
                    }
                }
                $ride_filename = explode('/',$bookingdatasort_canc['ride_img']);
                $ride_image = SITE_URL . 'img/ride_imgs/' . array_pop($ride_filename);
                $booking_title = str_pad($bookingdatasort_canc['booking_id'] , 5, '0', STR_PAD_LEFT);
                
                $booking_cancelled .= "<ons-list-item data-ridedesc='{$bookingdatasort_canc['ride_desc']}'  data-driverphone='{$bookingdatasort_canc['driver_phone']}' data-ptype='{$booking_payment_type}' data-put='{$booking_pdate_time}' data-driverimg='{$drvr_photo_file}' data-rideimg='{$ride_image}' data-drivername='{$booking_driver}' data-cost='{$bookingdatasort_canc['estimated_cost']}' data-ride='{$bookingdatasort_canc['ride_type']}' data-pul='{$bookingdatasort_canc['pickup_address']}' data-dol='{$bookingdatasort_canc['dropoff_address']}' data-btitle='{$booking_title}' id='booking-list-item-{$bookingdatasort_canc['booking_id']}' modifier='longdivider'>
                
                                            <div class='center'>
                                                <div style='width:100%'><span class='list-item__title'>{$booking_time}</span> | <span onclick='showbookingdetails({$bookingdatasort_canc['booking_id']})' style='color:skyblue'>View details</span></div>
                                                <span class='list-item__subtitle'><span style='color:yellow'>Booking ID:</span> {$booking_title}</span>
                                                <span class='list-item__subtitle'><span style='color:lightgreen'>Pick-up:</span> {$bookingdatasort_canc['pickup_address']}</span>
                                                <span class='list-item__subtitle'><span style='color:orange'>Drop-off:</span> {$bookingdatasort_canc['dropoff_address']}</span>
                                                <span class='list-item__subtitle'><span style='color:cyan'>Driver: </span>{$booking_driver}</span>
                                            </div>
                                        
                                        </ons-list-item>";

            }


        }







    }

    
    $data_array = array("success"=>1,'pend_onride' => $booking_pend_onride,'booking_comp'=>$booking_completed,'booking_canc'=>$booking_cancelled);    
    echo json_encode($data_array); 
    exit;






}


function bookingcancel(){

    if(empty($_SESSION['loggedin'])){
        $error = array("error"=>"Please re-login and retry.");
        echo json_encode($error); 
        exit; 
    }

    $booking_id = (int) $_POST['bookingid'];

    $query = sprintf('UPDATE %stbl_bookings SET `status` = 2 WHERE id = "%d"', DB_TBL_PREFIX,$booking_id );

    if(!$result = mysqli_query($GLOBALS['DB'], $query)){ 
        $error = array("error"=>"Failed to cancel booking");
        echo json_encode($error); 
        exit;
    }



    $data_array = array("success"=>1);    
    echo json_encode($data_array); 
    exit;

}


function getusernotifications(){


    if(empty($_SESSION['loggedin'])){
        $error = array("error"=>"Please re-login and retry.");
        echo json_encode($error); 
        exit; 
    }

    $notification_data = [];
    $notification_data_date_sort = [];
    $formatted_notifications = '';
    $num_of_notifications = 0;

    $query = sprintf('SELECT COUNT(*) FROM %stbl_notifications WHERE person_id = "%d" AND user_type = 0', DB_TBL_PREFIX, $_SESSION['uid']); //Get required user information from DB
    if($result = mysqli_query($GLOBALS['DB'], $query)){
        if(mysqli_num_rows($result)){
    
           $row = mysqli_fetch_assoc($result);
              
          $num_of_notifications = $row['COUNT(*)'];
             
         }
        mysqli_free_result($result);
    }   


    
    
    $query = sprintf('SELECT *, DATE(date_created) AS created_date FROM %stbl_notifications WHERE person_id = "%d" AND user_type = 0 ORDER BY date_created DESC LIMIT 0,100', DB_TBL_PREFIX, $_SESSION['uid']); //Get required user information from DB

    if($result = mysqli_query($GLOBALS['DB'], $query)){
        if(mysqli_num_rows($result)){

            while($row = mysqli_fetch_assoc($result)){
                $notification_data[] = $row;
            }
            
            mysqli_free_result($result);

        }else{
            $error = array("error"=>"You do not have any notifications.");
            echo json_encode($error); 
            exit; 
        }
        
    }
    else{ 

        $error = array("error"=>"Error retrieving notifications.");
        echo json_encode($error); 
        exit;
        
    }

    

    foreach($notification_data as $notificationdata){
        $notification_data_date_sort[$notificationdata['created_date']]['date'] = $notificationdata['created_date'];
        $notification_data_date_sort[$notificationdata['created_date']]['notifications'][] = $notificationdata;
    }

    //format data for display on app
    foreach($notification_data_date_sort as $notificationdatadatesort){
        if(!empty($notificationdatadatesort['notifications'])){
            $formatted_notifications .= "<ons-list-header>{$notificationdatadatesort['date']}</ons-list-header>";
            
            foreach($notificationdatadatesort['notifications'] as $date_notifications){
                $close_btn = "<span style='display:inline-block;float:right'><ons-icon onclick = 'notifydelete({$date_notifications['id']})' icon='fa-times' size='18px' style='color:red'></ons-icon></span>";
                $notification_time = date('g:i A',strtotime($date_notifications['date_created'] . ' UTC'));
                $type_color = 'white';
                switch($date_notifications['n_type']){
                    
                    case 1:
                    $type_color = 'lightblue';
                    break;

                    case 2:
                    $type_color = 'lightgreen';
                    break;

                    case 3:
                    $type_color = 'orange';
                    break;

                    case 4:
                    $type_color = 'yellow';
                    break;

                    default:
                    $type_color = 'white';
                    break;
                    



                }
                $formatted_notifications .= "<ons-list-item id='notification-list-item-{$date_notifications['id']}' modifier='longdivider'>
                
                                                <div class='center'>
                                                    <div style='width:100%;color:{$type_color}'><span class='list-item__title'>{$notification_time} </span> {$close_btn}</div>
                                                    <span class='list-item__subtitle'>{$date_notifications['content']}</span>                                                    
                                                </div>
                                            
                                            </ons-list-item>";
            }
        }
        
    }



    $data_array = array("success"=>1,"notifications"=>$formatted_notifications,'n_count'=>$num_of_notifications);    
    echo json_encode($data_array); 
    exit;



}



function deletenotification(){

    if(empty($_SESSION['loggedin'])){
        $error = array("error"=>"Please re-login and retry.");
        echo json_encode($error); 
        exit; 
    }


    $notification_id = (int) $_POST['n_id'];


    $query = sprintf('DELETE FROM %stbl_notifications WHERE person_id = "%d" AND user_type = 0 AND id = "%d"', DB_TBL_PREFIX, $_SESSION['uid'],$notification_id); 
    
    if(!$result = mysqli_query($GLOBALS['DB'], $query)){ 
        $error = array("error"=>"Failed to delete notifications");
        echo json_encode($error); 
        exit;

                
    }


    $data_array = array("success"=>1);    
    echo json_encode($data_array); 
    exit;




}

//************************************Data Export***************************/

function exportBookings(){

    if(empty($_SESSION['loggedin'])){
        $error = array("error"=>"Please re-login and retry.");
        echo json_encode($error); 
        exit; 
    }


    if(!isValidDate($_POST['date'])){
        $error = array("error"=>"The selected data is not valid");
        echo json_encode($error); 
        exit; 
    }

    $type = isset($_POST['type']) ? (int) $_POST['type'] : 0;
    $bookings_page_items = [];
    $data_items = [];
    $head_titles = array("Booking ID","Customer","Car Type","Pick-up","Drop-off","Booking Time","Est.Fare","Amount Paid","Payment Method","Assigned Driver","Status");

    $query = sprintf('SELECT *,%1$stbl_bookings.id AS booking_id,%1$stbl_bookings.route_id AS booking_route_id, %1$stbl_bookings.ride_id AS booking_ride,%1$stbl_drivers.firstname AS drvr_firstname, %1$stbl_drivers.lastname AS drvr_lastname  FROM %1$stbl_bookings 
    LEFT JOIN %1$stbl_routes ON %1$stbl_routes.id = %1$stbl_bookings.route_id
    LEFT JOIN %1$stbl_drivers ON %1$stbl_drivers.driver_id = %1$stbl_bookings.driver_id
    LEFT JOIN %1$stbl_rides ON %1$stbl_rides.id = %1$stbl_bookings.ride_id
    LEFT JOIN %1$stbl_driver_location ON %1$stbl_driver_location.driver_id = %1$stbl_bookings.driver_id
    INNER JOIN %1$stbl_users ON %1$stbl_users.user_id = %1$stbl_bookings.user_id
    WHERE DATE(%1$stbl_bookings.date_created) = "%2$s" ORDER BY %1$stbl_bookings.date_created DESC', DB_TBL_PREFIX, $_POST['date']);

    if($result = mysqli_query($GLOBALS['DB'], $query)){
    
        if(mysqli_num_rows($result)){
            while($row = mysqli_fetch_assoc($result)){
                $bookings_page_items[] = $row;                
            }
        
        }else{
            $error = array("error"=>"No data available for the selected date");
            echo json_encode($error); 
            exit; 
        }
        
    }else{
        $error = array("error"=>"Database not responding");
        echo json_encode($error); 
        exit;
    }

    $count = 1;
    $default_currency_symbol = !empty($_SESSION['default_currency']) ? $_SESSION['default_currency']['symbol'] : "₦";
    foreach($bookings_page_items as $bookingspageitems){
        if($count == 1){
            $data_items[] = $head_titles;
        }
        $booking_status = '';
        switch($bookingspageitems['status']){
            case 0:
            $booking_status = "Pending";
            break;

            case 1:
            $booking_status = "On Ride";
            break;

            case 2:
            $booking_status = "Cancelled (Rider)";
            break;

            case 3:
            $booking_status = "Completed";
            break;

            case 4:
            $booking_status = "Cancelled (Driver)";
            break;

            case 5:
            $booking_status = "Cancelled (System)";
            break;

            default:
            $booking_status = "Pending";
            break;
            
        }

        $paymethod = '';
        switch($bookingspageitems['payment_type']){
            case 1:
            $paymethod = "CASH";
            break;

            case 2:
            $paymethod = "WALLET";
            break;

            case 3:
            $paymethod = "CARD";
            break;

            case 4:
            $paymethod = "POS";
            break;

        }
        
    

        
                                                
        $customer_details = $bookingspageitems['user_firstname'] . " " . $bookingspageitems['user_lastname'] . "(" . $bookingspageitems['user_phone'] . ")";
        $driver_assigned = empty($bookingspageitems['driver_id']) ? "Not Assigned" : $bookingspageitems['driver_firstname'] . " " . $bookingspageitems['driver_lastname']; 
        
        

        $estimated_cost = $bookingspageitems['cur_symbol'] . ($bookingspageitems['estimated_cost']);
        $estimated_cost_local = (int) ($bookingspageitems['estimated_cost'] / $bookingspageitems['cur_exchng_rate'] * 100);
        $estimated_cost_local = $default_currency_symbol . ($estimated_cost_local / 100);
        $amount_paid = !empty($bookingspageitems['paid_amount']) ? $bookingspageitems['cur_symbol'] . $bookingspageitems['paid_amount'] : "N/A";
        $amount_paid_local = (int) ($bookingspageitems['paid_amount'] / $bookingspageitems['cur_exchng_rate'] * 100);
        $amount_paid_local = !empty($bookingspageitems['paid_amount']) ? $default_currency_symbol . ($amount_paid_local / 100) : "0.00";
        $booking_id_formatted = str_pad($bookingspageitems['booking_id'] , 5, '0', STR_PAD_LEFT);
        
        $data_items[] = array($booking_id_formatted,$customer_details,$bookingspageitems['ride_type'],$bookingspageitems['pickup_address'],$bookingspageitems['dropoff_address'],date('l, M j, Y H:i:s',strtotime($bookingspageitems['date_created'].' UTC')),$estimated_cost,$amount_paid,$paymethod,$driver_assigned,$booking_status);
        $count++;
    }

    
    $filename = "DT-Booking-data-{$_POST['date']}-" . crypto_string('nozero',4);
    if($type){
        $res = exportToGoogleSheet($filename,$data_items);
    }else{
        $res = exportCSV($filename,$data_items);
    }

    if(!empty($res)){

        if(isset($res['error'])){
            $error = array("error"=>"An error has occured");
            echo json_encode($error); 
            exit;
        }elseif(isset($res['success'])){
            if($type){
                $success = array("success"=>"Data was exported successfully - filename: {$filename}");
            }else{
                $success = array("success"=>"Data was exported successfully - filename: {$filename}.csv", "download" => SITE_URL . "downloadex.php?file={$filename}.csv");
            }
            
            echo json_encode($success); 
            exit;
        }else{
            $error = array("error"=>"An error has occured");
            echo json_encode($error); 
            exit;        }

    }else{

        $error = array("error"=>"An error has occured");
        echo json_encode($error); 
        exit;

    }
    
}




function exportsBookings(){

    if(empty($_SESSION['loggedin'])){
        $error = array("error"=>"Please re-login and retry.");
        echo json_encode($error); 
        exit; 
    }


    if(!isValidDate($_POST['date'])){
        $error = array("error"=>"The selected data is not valid");
        echo json_encode($error); 
        exit; 
    }

    $type = isset($_POST['type']) ? (int) $_POST['type'] : 0;
    $bookings_page_items = [];
    $data_items = [];
    $head_titles = array("Booking ID","Customer","Car Type","Pick-up","Drop-off","Booking Time","Est.Fare","Amount Paid","Payment Method","Assigned Driver","Status");

    $query = sprintf('SELECT *,%1$stbl_bookings.id AS booking_id,%1$stbl_bookings.route_id AS booking_route_id, %1$stbl_bookings.ride_id AS booking_ride,%1$stbl_drivers.firstname AS drvr_firstname, %1$stbl_drivers.lastname AS drvr_lastname  FROM %1$stbl_bookings 
    LEFT JOIN %1$stbl_routes ON %1$stbl_routes.id = %1$stbl_bookings.route_id
    LEFT JOIN %1$stbl_drivers ON %1$stbl_drivers.driver_id = %1$stbl_bookings.driver_id
    LEFT JOIN %1$stbl_rides ON %1$stbl_rides.id = %1$stbl_bookings.ride_id
    LEFT JOIN %1$stbl_driver_location ON %1$stbl_driver_location.driver_id = %1$stbl_bookings.driver_id
    INNER JOIN %1$stbl_users ON %1$stbl_users.user_id = %1$stbl_bookings.user_id
    WHERE DATE(%1$stbl_bookings.date_created) = "%2$s" AND %1$stbl_bookings.scheduled = 1 ORDER BY %1$stbl_bookings.date_created DESC', DB_TBL_PREFIX, $_POST['date']);

    if($result = mysqli_query($GLOBALS['DB'], $query)){
    
        if(mysqli_num_rows($result)){
            while($row = mysqli_fetch_assoc($result)){
                $bookings_page_items[] = $row;                
            }
        
        }else{
            $error = array("error"=>"No data available for the selected date");
            echo json_encode($error); 
            exit; 
        }
        
    }else{
        $error = array("error"=>"Database not responding");
        echo json_encode($error); 
        exit;
    }

    $count = 1;
    $default_currency_symbol = !empty($_SESSION['default_currency']) ? $_SESSION['default_currency']['symbol'] : "₦";
    foreach($bookings_page_items as $bookingspageitems){
        if($count == 1){
            $data_items[] = $head_titles;
        }
        $booking_status = '';
        switch($bookingspageitems['status']){
            case 0:
            $booking_status = "Pending";
            break;

            case 1:
            $booking_status = "On Ride";
            break;

            case 2:
            $booking_status = "Cancelled (Rider)";
            break;

            case 3:
            $booking_status = "Completed";
            break;

            case 4:
            $booking_status = "Cancelled (Driver)";
            break;

            case 5:
            $booking_status = "Cancelled (System)";
            break;

            default:
            $booking_status = "Pending";
            break;
            
        }

        $paymethod = '';
        switch($bookingspageitems['payment_type']){
            case 1:
            $paymethod = "CASH";
            break;

            case 2:
            $paymethod = "WALLET";
            break;

            case 3:
            $paymethod = "CARD";
            break;

            case 4:
            $paymethod = "POS";
            break;

        }
        
    

        
                                                
        $customer_details = $bookingspageitems['user_firstname'] . " " . $bookingspageitems['user_lastname'] . "(" . $bookingspageitems['user_phone'] . ")";
        $driver_assigned = empty($bookingspageitems['driver_id']) ? "Not Assigned" : $bookingspageitems['driver_firstname'] . " " . $bookingspageitems['driver_lastname']; 
        
        

        $estimated_cost = $bookingspageitems['cur_symbol'] . ($bookingspageitems['estimated_cost']);
        $estimated_cost_local = (int) ($bookingspageitems['estimated_cost'] / $bookingspageitems['cur_exchng_rate'] * 100);
        $estimated_cost_local = $default_currency_symbol . ($estimated_cost_local / 100);
        $amount_paid = !empty($bookingspageitems['paid_amount']) ? $bookingspageitems['cur_symbol'] . $bookingspageitems['paid_amount'] : "N/A";
        $amount_paid_local = (int) ($bookingspageitems['paid_amount'] / $bookingspageitems['cur_exchng_rate'] * 100);
        $amount_paid_local = !empty($bookingspageitems['paid_amount']) ? $default_currency_symbol . ($amount_paid_local / 100) : "0.00";
        $booking_id_formatted = str_pad($bookingspageitems['booking_id'] , 5, '0', STR_PAD_LEFT);
        
        $data_items[] = array($booking_id_formatted,$customer_details,$bookingspageitems['ride_type'],$bookingspageitems['pickup_address'],$bookingspageitems['dropoff_address'],date('l, M j, Y H:i:s',strtotime($bookingspageitems['date_created'].' UTC')),$estimated_cost,$amount_paid,$paymethod,$driver_assigned,$booking_status);
        $count++;
    }

    
    $filename = "DT-Scheduled-Booking-data-{$_POST['date']}-" . crypto_string('nozero',4);
    if($type){
        $res = exportToGoogleSheet($filename,$data_items);
    }else{
        $res = exportCSV($filename,$data_items);
    }

    if(!empty($res)){

        if(isset($res['error'])){
            $error = array("error"=>"An error has occured");
            echo json_encode($error); 
            exit;
        }elseif(isset($res['success'])){
            if($type){
                $success = array("success"=>"Data was exported successfully - filename: {$filename}");
            }else{
                $success = array("success"=>"Data was exported successfully - filename: {$filename}.csv", "download" => SITE_URL . "downloadex.php?file={$filename}.csv");
            }
            echo json_encode($success); 
            exit;
        }else{
            $error = array("error"=>"An error has occured");
            echo json_encode($error); 
            exit;        }

    }else{

        $error = array("error"=>"An error has occured");
        echo json_encode($error); 
        exit;

    }
    
}




function exportCustomerReg(){

    if(empty($_SESSION['loggedin'])){
        $error = array("error"=>"Please re-login and retry.");
        echo json_encode($error); 
        exit; 
    }


    if(!isValidDate($_POST['date'])){
        $error = array("error"=>"The selected data is not valid");
        echo json_encode($error); 
        exit; 
    }

    $type = isset($_POST['type']) ? (int) $_POST['type'] : 0;
    $data_page_items = [];
    $export_data_items = [];
    $data_items = [];
    $head_titles = array("Name","Email","Phone","Wallet Balance","Account Created");

    $query = sprintf('SELECT * FROM %1$stbl_users
    WHERE DATE(%1$stbl_users.account_create_date) = "%2$s" AND %1$stbl_users.account_type != 2 AND %1$stbl_users.account_type != 3 AND %1$stbl_users.account_type != 5 ORDER BY %1$stbl_users.account_create_date DESC', DB_TBL_PREFIX, $_POST['date']);
    

    if($result = mysqli_query($GLOBALS['DB'], $query)){
    
        if(mysqli_num_rows($result)){
            while($row = mysqli_fetch_assoc($result)){
                $data_page_items[] = $row;                
            }
        
        }else{
            $error = array("error"=>"No data available for the selected date");
            echo json_encode($error); 
            exit; 
        }
        
    }else{
        $error = array("error"=>"Database not responding");
        echo json_encode($error); 
        exit;
    }

    $count = 1;
    $default_currency_symbol = !empty($_SESSION['default_currency']) ? $_SESSION['default_currency']['symbol'] : "₦";
    foreach($data_page_items as $customerspageitems){   
        if($count == 1){
            $data_items[] = $head_titles;
        } 
        
        $email = (!empty(DEMO) ? mask_email($customerspageitems['email']) : $customerspageitems['email']);
        $phone = (!empty(DEMO) ? mask_string($customerspageitems['phone']) : $customerspageitems['phone']);
        $name =  $customerspageitems['firstname'] . " " . $customerspageitems['lastname'];                              
        
        
        $data_items[] = array($name,$email,$customerspageitems['country_dial_code']." ".$phone,$default_currency_symbol.$customerspageitems['wallet_amount'],date('l, M j, Y H:i:s',strtotime($customerspageitems['account_create_date'].' UTC')));
        $count++;
        
    }

    
    $filename = "DT-Customer-Reg-data-{$_POST['date']}-" . crypto_string('nozero',4);

    if($type){
        $res = exportToGoogleSheet($filename,$data_items);
    }else{
        $res = exportCSV($filename,$data_items);
    }

    if(!empty($res)){

        if(isset($res['error'])){
            $error = array("error"=>"An error has occured");
            echo json_encode($error); 
            exit;
        }elseif(isset($res['success'])){
            if($type){
                $success = array("success"=>"Data was exported successfully - filename: {$filename}");
            }else{
                $success = array("success"=>"Data was exported successfully - filename: {$filename}.csv", "download" => SITE_URL . "downloadex.php?file={$filename}.csv");
            }
            echo json_encode($success); 
            exit;
        }else{
            $error = array("error"=>"An error has occured");
            echo json_encode($error); 
            exit;        }

    }else{

        $error = array("error"=>"An error has occured");
        echo json_encode($error); 
        exit;

    }
    
}



function exportDriverReg(){

    if(empty($_SESSION['loggedin'])){
        $error = array("error"=>"Please re-login and retry.");
        echo json_encode($error); 
        exit; 
    }


    if(!isValidDate($_POST['date'])){
        $error = array("error"=>"The selected data is not valid");
        echo json_encode($error); 
        exit; 
    }

    $type = isset($_POST['type']) ? (int) $_POST['type'] : 0;
    $data_page_items = [];
    $export_data_items = [];
    $data_items = [];
    $head_titles = array("Driver ID","Driver Name","Phone","City","Wallet Amount","Car Model","Car Plate Number");

    $query = sprintf('SELECT *, %1$stbl_driver_location.location_date, %1$stbl_driver_location.long AS drvlong, %1$stbl_driver_location.lat AS drvlat, %1$stbl_drivers.driver_id AS driver_ids FROM %1$stbl_drivers 
    LEFT JOIN %1$stbl_account_codes ON %1$stbl_account_codes.user_id = %1$stbl_drivers.driver_id AND %1$stbl_account_codes.user_type = 1 AND %1$stbl_account_codes.context = 0
    LEFT JOIN %1$stbl_driver_location ON %1$stbl_driver_location.driver_id = %1$stbl_drivers.driver_id
    LEFT JOIN %1$stbl_routes ON %1$stbl_routes.id = %1$stbl_drivers.route_id
    WHERE DATE(%1$stbl_drivers.account_create_date) = "%2$s" ORDER BY %1$stbl_drivers.account_create_date', DB_TBL_PREFIX, $_POST['date']);


    if($result = mysqli_query($GLOBALS['DB'], $query)){
    
        if(mysqli_num_rows($result)){
            while($row = mysqli_fetch_assoc($result)){
                $data_page_items[] = $row;                
            }
        
        }else{
            $error = array("error"=>"No data available for the selected date");
            echo json_encode($error); 
            exit; 
        }
        
    }else{
        $error = array("error"=>"Database not responding");
        echo json_encode($error); 
        exit;
    }

    $count = 1;
    $default_currency_symbol = !empty($_SESSION['default_currency']) ? $_SESSION['default_currency']['symbol'] : "₦";
    foreach($data_page_items as $driverspageitems){    
        if($count == 1){
            $data_items[] = $head_titles;
        }
        $drvr_id = str_pad($driverspageitems['driver_ids'] , 5, '0', STR_PAD_LEFT);
        
        $driver_name = $driverspageitems['firstname'] . " " .  $driverspageitems['lastname'];
                
        $phone = (!empty(DEMO) ? mask_string($driverspageitems['phone']) : $driverspageitems['phone']);
              
        
        $data_items[] = array($drvr_id,$driver_name,$driverspageitems['country_dial_code']." ".$phone,$driverspageitems['r_title'],$default_currency_symbol.$driverspageitems['wallet_amount'],$driverspageitems['car_model'],$driverspageitems['car_plate_num']);
        $count++;
        
    }

    
    $filename = "DT-Driver-Reg-data-{$_POST['date']}-" . crypto_string('nozero',4);

    if($type){
        $res = exportToGoogleSheet($filename,$data_items);
    }else{
        $res = exportCSV($filename,$data_items);
    }

    if(!empty($res)){

        if(isset($res['error'])){
            $error = array("error"=>"An error has occured");
            echo json_encode($error); 
            exit;
        }elseif(isset($res['success'])){
            if($type){
                $success = array("success"=>"Data was exported successfully - filename: {$filename}");
            }else{
                $success = array("success"=>"Data was exported successfully - filename: {$filename}.csv", "download" => SITE_URL . "downloadex.php?file={$filename}.csv");
            }
            echo json_encode($success); 
            exit;
        }else{
            $error = array("error"=>"An error has occured");
            echo json_encode($error); 
            exit;        
        }

    }else{

        $error = array("error"=>"An error has occured");
        echo json_encode($error); 
        exit;

    }
    
}



function exportWalletFundData(){

    if(empty($_SESSION['loggedin'])){
        $error = array("error"=>"Please re-login and retry.");
        echo json_encode($error); 
        exit; 
    }


    if(!isValidDate($_POST['date'])){
        $error = array("error"=>"The selected data is not valid");
        echo json_encode($error); 
        exit; 
    }

    
    $type = isset($_POST['type']) ? (int) $_POST['type'] : 0;
    $data_page_items = [];
    $export_data_items = [];
    $data_items = [];
    $head_titles = array("Type","Details","Amount funded","Wallet balance","Date","Comment");

    $query = sprintf('SELECT * FROM %stbl_wallet_fund WHERE DATE(date_fund) = "%2$s" ORDER BY date_fund DESC', DB_TBL_PREFIX,$_POST['date']);

    

    if($result = mysqli_query($GLOBALS['DB'], $query)){
    
        if(mysqli_num_rows($result)){
            while($row = mysqli_fetch_assoc($result)){
                $data_page_items[] = $row;                
            }
        
        }else{
            $error = array("error"=>"No data available for the selected date");
            echo json_encode($error); 
            exit; 
        }
        
    }else{
        $error = array("error"=>"Database not responding");
        echo json_encode($error); 
        exit;
    }

    $count = 1;
    foreach($data_page_items as $walletfundingdata){    
        if($count == 1){
            $data_items[] = $head_titles;
        }
        $user_type = '';
        $details ="";
        if($walletfundingdata['fund_type'] == 1){
            $user_type = "Driver";
            $details = $walletfundingdata['driver_firstname'] . " " . $walletfundingdata['driver_lastname'] . "(" . (!empty(DEMO) ? mask_string($walletfundingdata['driver_phone']) : $walletfundingdata['driver_phone']) .") wallet was funded by " . $walletfundingdata['staff_firstname'] . " " .  $walletfundingdata['staff_lastname'] ;
        }elseif($walletfundingdata['fund_type'] == 2){
            $user_type = "Customer";
            $details = $walletfundingdata['customer_firstname'] . " " . $walletfundingdata['customer_lastname'] . "(" . (!empty(DEMO) ? mask_string($walletfundingdata['customer_phone']) : $walletfundingdata['customer_phone']) .") wallet was funded by " . $walletfundingdata['staff_firstname'] . " " .  $walletfundingdata['staff_lastname'] ;
        }else{
            $user_type = "Staff";
            $details = $walletfundingdata['customer_firstname'] . " " . $walletfundingdata['customer_lastname'] . "(" . (!empty(DEMO) ? mask_string($walletfundingdata['customer_phone']) : $walletfundingdata['customer_phone']) .") wallet was funded by " . $walletfundingdata['staff_firstname'] . " " .  $walletfundingdata['staff_lastname'] ;
        }

        $date_funded = date('l, M j, Y H:i:s',strtotime($walletfundingdata['date_fund'] . ' UTC'));
        $wallet_fund_amount = $walletfundingdata['cur_symbol'] . $walletfundingdata['fund_amount'];
        
        
        
        $data_items[] = array($user_type,$details,$wallet_fund_amount,$walletfundingdata['wallet_balance'],$date_funded,$walletfundingdata['fund_comment']);
        $count++;
        
    }

    
    $filename = "DT-Wallet-Funding-data-{$_POST['date']}-" . crypto_string('nozero',4);

    if($type){
    	 
        $res = exportToGoogleSheet($filename,$data_items);
    }else{
    	
        $res = exportCSV($filename,$data_items);
    }

    if(!empty($res)){

        if(isset($res['error'])){
            $error = array("error"=>"An error has occured");
            echo json_encode($error); 
            exit;
        }elseif(isset($res['success'])){
            if($type){
                $success = array("success"=>"Data was exported successfully - filename: {$filename}");
            }else{
                $success = array("success"=>"Data was exported successfully - filename: {$filename}.csv", "download" => SITE_URL . "downloadex.php?file={$filename}.csv");
            }
            echo json_encode($success); 
            exit;
        }else{
            $error = array("error"=>"An error has occured");
            echo json_encode($error); 
            exit;        }

    }else{

        $error = array("error"=>"An error has occured");
        echo json_encode($error); 
        exit;

    }
    
}



function exportPayoutsData(){

    if(empty($_SESSION['loggedin'])){
        $error = array("error"=>"Please re-login and retry.");
        echo json_encode($error); 
        exit; 
    }


    if(!isValidDate($_POST['date'])){
        $error = array("error"=>"The selected data is not valid");
        echo json_encode($error); 
        exit; 
    }

    $type = isset($_POST['type']) ? (int) $_POST['type'] : 0;
    $data_page_items = [];
    $export_data_items = [];
    $data_items = [];
    $head_titles = array("User","Account type","Amount","Wallet Amount (Old)","Wallet Balance (New)","Status","Date Requested","Date Processed");

    $query = sprintf('SELECT %1$stbl_wallet_withdrawal.*,%1$stbl_wallet_withdrawal.id AS wid,%1$stbl_drivers.firstname,%1$stbl_drivers.lastname,%1$stbl_drivers.country_dial_code,%1$stbl_drivers.phone,%1$stbl_franchise.franchise_name,%1$stbl_franchise.franchise_phone FROM %1$stbl_wallet_withdrawal 
    LEFT JOIN %1$stbl_franchise ON %1$stbl_wallet_withdrawal.person_id = %1$stbl_franchise.id AND %1$stbl_wallet_withdrawal.user_type = 1
    LEFT JOIN %1$stbl_drivers ON %1$stbl_drivers.driver_id = %1$stbl_wallet_withdrawal.person_id AND %1$stbl_wallet_withdrawal.user_type = 0
    WHERE DATE(%1$stbl_wallet_withdrawal.date_requested) = "%2$s" ORDER BY %1$stbl_wallet_withdrawal.date_requested DESC', DB_TBL_PREFIX, $_POST['date']);

    

    

    if($result = mysqli_query($GLOBALS['DB'], $query)){
    
        if(mysqli_num_rows($result)){
            while($row = mysqli_fetch_assoc($result)){
                $data_page_items[] = $row;                
            }
        
        }else{
            $error = array("error"=>"No data available for the selected dates");
            echo json_encode($error); 
            exit; 
        }
        
    }else{
        $error = array("error"=>"Database not responding");
        echo json_encode($error); 
        exit;
    }

    $count = 1;
    $default_currency_symbol = !empty($_SESSION['default_currency']) ? $_SESSION['default_currency']['symbol'] : "₦";
    foreach($data_page_items as $withdrawalrequestsdata){
        if($count == 1){
            $data_items[] = $head_titles;
        }
        $user_type_name = "";
        if($withdrawalrequestsdata['user_type']){
            $user_type_name = "Franchise";
            $user = $withdrawalrequestsdata['franchise_name'] . " (" . $withdrawalrequestsdata['franchise_phone'] . ")";
        }else{
            $user_type_name = "Driver";
            $user = $withdrawalrequestsdata['firstname'] . " " . $withdrawalrequestsdata['lastname'] . " (" . $withdrawalrequestsdata['country_dial_code'].$withdrawalrequestsdata['phone'] . ")";
        }

        $date_settled = !empty($withdrawalrequestsdata['date_settled']) ? date('l, M j, Y H:i:s',strtotime($withdrawalrequestsdata['date_settled'].' UTC')) : "---";
        
        

        switch($withdrawalrequestsdata['request_status']){
            case 0:
            $payout_status = "Pending";
            break;

            case 1:
            $payout_status = "Declined";
            break;

            case 2:
            $payout_status = "Settled";
            break;

        }        
        
        $data_items[] = array($user,$user_type_name,$withdrawalrequestsdata['cur_symbol']. $withdrawalrequestsdata['withdrawal_amount'],$default_currency_symbol. $withdrawalrequestsdata['wallet_amount'],$default_currency_symbol . $withdrawalrequestsdata['wallet_balance'],$payout_status,date('l, M j, Y H:i:s',strtotime($withdrawalrequestsdata['date_requested'].' UTC')));
        $count++;
        
    }

    
    $filename = "DT-Payouts-data-{$_POST['date']}-" . crypto_string('nozero',4);

    if($type){
        $res = exportToGoogleSheet($filename,$data_items);
    }else{
        $res = exportCSV($filename,$data_items);
    }

    if(!empty($res)){

        if(isset($res['error'])){
            $error = array("error"=>"An error has occured");
            echo json_encode($error); 
            exit;
        }elseif(isset($res['success'])){
            if($type){
                $success = array("success"=>"Data was exported successfully - filename: {$filename}");
            }else{
                $success = array("success"=>"Data was exported successfully - filename: {$filename}.csv", "download" => SITE_URL . "downloadex.php?file={$filename}.csv");
            }
            echo json_encode($success); 
            exit;
        }else{
            $error = array("error"=>"An error has occured");
            echo json_encode($error); 
            exit;        }

    }else{

        $error = array("error"=>"An error has occured");
        echo json_encode($error); 
        exit;

    }
    
}


function exportCSV($exportfilename,$data){

    if(empty($data)){
        return array('error'=>"Invalid data passed"); 
    }

    if(!is_dir(FILES_FOLDER . "/uploads/exports")){
        $t = mkdir(FILES_FOLDER . "/uploads/exports", 0777);
        if(!$t){
            return array('error'=>"Cannot create exports folder. Permission denied."); 
        }
    }

    

    $f = fopen(FILES_FOLDER . "/uploads/exports/{$exportfilename}.csv", "w");

    if(empty($f)){
        return array('error'=>"Cannot create exports file. Permission denied."); 
    }    


    foreach($data as $dt){
        fputcsv($f, $dt);
    }

    fclose($f);

    return array('success'=>1);



}



function exportToGoogleSheet($exportfilename,$data){



    $spreadsheet_id = '';

    try{
    //First Enable Google Sheets and Google Drive API in Google cloud console

    require_once(dirname(__DIR__) . "/drop-files/google-client/vendor/autoload.php"); //load google api client 
        

    $credential_file = dirname(__DIR__) . "/drop-files/google-client/credentials.json";


    $client = new Google_Client();
    $client->setApplicationName('Google Sheets API');
    $client->setScopes([Google_Service_Sheets::SPREADSHEETS]);
    $client->setAccessType('offline');
    // credentials.json is the key file we downloaded while setting up our Google Sheets API
    $client->setAuthConfig($credential_file);

    // configure the Sheets Service
    $service = new Google_Service_Sheets($client);

    $spreadsheet = new Google_Service_Sheets_Spreadsheet([
        'properties' => [
            'title' => $exportfilename
        ]
    ]);

    $spreadsheet = $service->spreadsheets->create($spreadsheet, [
        'fields' => 'spreadsheetId'
    ]);


    //printf("Spreadsheet ID: %s\n", $spreadsheet->spreadsheetId);

    $spreadsheet_id = $spreadsheet->spreadsheetId;

    }catch(Exception $e) {
        //error creating spreadsheet        
        return array('error'=>$e->getMessage());

    }

    try{

    //Grant permissions

    $client->addScope(['https://www.googleapis.com/auth/drive', 'https://www.googleapis.com/auth/spreadsheets']);

    $Drive = new Google_Service_Drive($client);
    // Object - permission drive
    $DrivePermisson = new Google_Service_Drive_Permission();
    // Type permission
    //$DrivePermisson->setType('anyone');
    $DrivePermisson->setType('user');

    // Email
    $DrivePermisson->setEmailAddress('myemail@gmail.com');

    // Role
    //$DrivePermisson->setRole('owner');
    $DrivePermisson->setRole('reader');

    // Send request with you spreadsheetId
    $response = $Drive->permissions->create($spreadsheet_id, $DrivePermisson);

    }catch(Exception $e) {
        //error granting permissions
        return array('error'=>$e->getMessage());
    }


    try{
    // wwrite data to spread sheet
    
    // Object - range of values
    $ValueRange = new Google_Service_Sheets_ValueRange();
    // Setting our data
    $ValueRange->setValues($data);
    // We specify in the options to process user data
    $options = ['valueInputOption' => 'RAW'];
    // We make a request indicating in the second parameter the name of the sheet and the starting cell to fill
    $service->spreadsheets_values->update($spreadsheet_id, 'Sheet1!A1', $ValueRange, $options);
    }catch(Exception $e) {
    //error writting data to spreadsheet
        return array('error'=>$e->getMessage());
    }

    return array('success'=>1);

}

?>