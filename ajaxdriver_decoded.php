<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2 & 7.3
 * @ Decoder version: 1.1.6
 * @ Release: 10/08/2022
 */

// Decoded file for php version 72.
include "../drop-files/lib/session_start_timeout.php";
session_start_timeout(25920002, 1);
include "../drop-files/lib/common.php";
include "../drop-files/config/db.php";
$wildcard = true;
$credentials = true;
$allowedOrigins = ["http://localhost"];
if(empty($_SERVER["HTTP_ORIGIN"])) {
    $origin = $wildcard && !$credentials ? "*" : "file://";
} else {
    $origin = $wildcard && !$credentials ? "*" : $_SERVER["HTTP_ORIGIN"];
}
header("Access-Control-Allow-Origin: " . $origin);
if($credentials) {
    header("Access-Control-Allow-Credentials: true");
}
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Origin");
header("P3P: CP=\"CAO PSA OUR\"");
if(!empty($_SESSION["lang"])) {
    if(file_exists(FILES_FOLDER . "/lang/driver/" . $_SESSION["lang"] . ".php")) {
        include FILES_FOLDER . "/lang/driver/" . $_SESSION["lang"] . ".php";
    } else {
        include FILES_FOLDER . "/lang/driver/en.php";
    }
} else {
    include FILES_FOLDER . "/lang/driver/en.php";
}
if(isset($_POST["action"])) {
    if(function_exists($_POST["action"])) {
        call_user_func($_POST["action"]);
        exit;
    }
} elseif(isset($_GET["action_get"])) {
    if(function_exists($_GET["action_get"])) {
        call_user_func($_GET["action_get"]);
        exit;
    }
} else {
    echo "X100";
    exit;
}
echo "invalid function call";
exit;
function calctariff()
{
    $tariff_data = [];
    if(!(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] == 1)) {
        $error = ["notloggedin" => "Please Sign-in to cotinue."];
        echo json_encode($error);
        exit;
    }
    $route_id = (int) $_POST["route_id"];
    $ride_id = (int) $_POST["ride_id"];
    $query = sprintf("SELECT * FROM %stbl_rides_tariffs WHERE routes_id = \"%d\" AND ride_id = \"%d\"", DB_TBL_PREFIX, $route_id, $ride_id);
    if($result = mysqli_query($GLOBALS["DB"], $query)) {
        if(mysqli_num_rows($result)) {
            $tariff_data = mysqli_fetch_assoc($result);
            $url = "https://maps.googleapis.com/maps/api/directions/json?origin=" . $_POST["a_lat"] . "," . $_POST["a_lng"] . "&destination=" . $_POST["b_lat"] . "," . $_POST["b_lng"] . "&key=" . GMAP_API_KEY;
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HTTPGET, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            $json_response = curl_exec($curl);
            $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            $response = json_decode($json_response, true);
            if(json_last_error()) {
                $error = ["error" => "Error computing tariff. Please retry."];
                echo json_encode($error);
                exit;
            }
            $pickup_cost = $tariff_data["pickup_cost"];
            $drop_off_cost = $tariff_data["drop_off_cost"];
            $cost_per_km = $tariff_data["cost_per_km"];
            $cost_per_minute = $tariff_data["cost_per_minute"];
            $distance = $response["routes"][0]["legs"][0]["distance"]["text"];
            $duration = $response["routes"][0]["legs"][0]["duration"]["text"];
            $price = $cost_per_km * $distance + $drop_off_cost + $pickup_cost;
            $token = crypto_string("nozero", 5);
            unset($_SESSION["booking"]);
            $_SESSION["booking"][$token]["a_lat"] = $_POST["a_lat"];
            $_SESSION["booking"][$token]["a_lng"] = $_POST["a_lng"];
            $_SESSION["booking"][$token]["b_lat"] = $_POST["b_lat"];
            $_SESSION["booking"][$token]["b_lng"] = $_POST["b_lng"];
            $_SESSION["booking"][$token]["p_addr"] = $_POST["p_addr"];
            $_SESSION["booking"][$token]["d_addr"] = $_POST["d_addr"];
            $_SESSION["booking"][$token]["route_id"] = $_POST["route_id"];
            $_SESSION["booking"][$token]["distance"] = $distance;
            $_SESSION["booking"][$token]["duration"] = $duration;
            $_SESSION["booking"][$token]["ride_id"] = $_POST["ride_id"];
            $_SESSION["booking"][$token]["token"] = $token;
            $_SESSION["booking"][$token]["cost"] = $price;
            $route_price_data = ["distance" => $distance, "duration" => $duration, "price" => $price, "token" => $token];
            echo json_encode($route_price_data);
            exit;
        }
        $error = ["error" => "Error computing tariff. Please retry."];
        echo json_encode($error);
        exit;
    }
    $error = ["error" => "Error computing tariff. Please retry."];
    echo json_encode($error);
    exit;
}
function updateUserProfile()
{
    if(empty($_SESSION["loggedin"])) {
        $error = ["error" => __("Please login to continue")];
        echo json_encode($error);
        exit;
    }
    $driver_country = codeToCountryName(strtoupper($_POST["country_code"]));
    if(!$driver_country) {
        $error = ["error" => __("Invalid country selected")];
        echo json_encode($error);
        exit;
    }
    $msg = "";
    $enable_city_ride_change = 1;
    if(!filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
        $error = ["error" => __("Your email is not a valid email format")];
        echo json_encode($error);
        exit;
    }
    $query = sprintf("SELECT driver_id,email, phone FROM %stbl_drivers WHERE driver_id != \"%d\" AND (email = \"%s\" OR phone=\"%s\")", DB_TBL_PREFIX, $_SESSION["uid"], mysqli_real_escape_string($GLOBALS["DB"], $_POST["email"]), mysqli_real_escape_string($GLOBALS["DB"], $_POST["phone"]));
    if($result = mysqli_query($GLOBALS["DB"], $query)) {
        if(mysqli_num_rows($result)) {
            $row = mysqli_fetch_assoc($result);
            if($row["email"] == mysqli_real_escape_string($GLOBALS["DB"], $_POST["email"])) {
                $error = ["error" => __("The email already exists. Please use a different email")];
                echo json_encode($error);
                exit;
            }
            if($row["phone"] == mysqli_real_escape_string($GLOBALS["DB"], $_POST["phone"])) {
                $error = ["error" => __("The phone number already exists. Please use a different phone number")];
                echo json_encode($error);
                exit;
            }
            $error = ["error" => __("The email or phone number already exists. Please use a different email or phone number")];
            echo json_encode($error);
            exit;
        }
        if($enable_city_ride_change && (int) $_POST["op_city"] && (int) $_POST["car_category"]) {
            $operational_city = (int) $_POST["op_city"];
            $car_category = (int) $_POST["car_category"];
            $query = sprintf("UPDATE %stbl_drivers SET `route_id` = \"%d\", ride_id = %d WHERE driver_id = \"%d\"", DB_TBL_PREFIX, $operational_city, $car_category, $_SESSION["uid"]);
            $result = mysqli_query($GLOBALS["DB"], $query);
        }
        if(!empty($_POST["oldpassword"]) && empty($_POST["newpassword"])) {
            $error = ["error" => __("Your new password cannot be empty")];
            echo json_encode($error);
            exit;
        }
        if(!empty($_POST["oldpassword"])) {
            $query = sprintf("SELECT driver_id FROM %stbl_drivers WHERE driver_id = \"%d\" AND pwd_raw = \"%s\"", DB_TBL_PREFIX, $_SESSION["uid"], mysqli_real_escape_string($GLOBALS["DB"], $_POST["oldpassword"]));
            if($result = mysqli_query($GLOBALS["DB"], $query)) {
                if(mysqli_num_rows($result)) {
                    $query = sprintf("UPDATE %stbl_drivers SET `pwd_raw` = \"%s\" WHERE driver_id = \"%d\"", DB_TBL_PREFIX, mysqli_real_escape_string($GLOBALS["DB"], $_POST["newpassword"]), $_SESSION["uid"]);
                    $result = mysqli_query($GLOBALS["DB"], $query);
                    $msg = "Password was changed successfully.";
                } else {
                    $error = ["error" => __("An error has occured")];
                    echo json_encode($error);
                    exit;
                }
            } else {
                $error = ["error" => __("An error has occured")];
                echo json_encode($error);
                exit;
            }
        }
        $query = sprintf("UPDATE %stbl_drivers SET `phone` = \"%s\", `email` = \"%s\", country_code = \"%s\", country_dial_code = \"%s\", drv_country = \"%s\" WHERE driver_id = \"%d\"", DB_TBL_PREFIX, mysqli_real_escape_string($GLOBALS["DB"], $_POST["phone"]), mysqli_real_escape_string($GLOBALS["DB"], $_POST["email"]), mysqli_real_escape_string($GLOBALS["DB"], $_POST["country_code"]), "+" . mysqli_real_escape_string($GLOBALS["DB"], $_POST["country_dial_code"]), $driver_country, $_SESSION["uid"]);
        $result = mysqli_query($GLOBALS["DB"], $query);
        $_SESSION["email"] = mysqli_real_escape_string($GLOBALS["DB"], $_POST["email"]);
        $_SESSION["phone"] = mysqli_real_escape_string($GLOBALS["DB"], $_POST["phone"]);
        $msg .= __("Profile updated successfully");
        $error = ["success" => $msg];
        echo json_encode($error);
        exit;
    }
    $error = ["error" => __("An error has occured")];
    echo json_encode($error);
    exit;
}
function updatePushNotificationToken()
{
    if(empty($_SESSION["loggedin"])) {
        exit;
    }
    $push_notification_token = !empty($_POST["token"]) ? mysqli_real_escape_string($GLOBALS["DB"], $_POST["token"]) : "";
    if(!empty($push_notification_token) && $push_notification_token != $_SESSION["push_token"]) {
        $query = sprintf("UPDATE %stbl_drivers SET `push_notification_token` = NULL WHERE push_notification_token = \"%s\"", DB_TBL_PREFIX, $push_notification_token);
        $result = mysqli_query($GLOBALS["DB"], $query);
        $query = sprintf("UPDATE %stbl_drivers SET `push_notification_token` = \"%s\" WHERE driver_id = \"%d\"", DB_TBL_PREFIX, $push_notification_token, $_SESSION["uid"]);
        $result = mysqli_query($GLOBALS["DB"], $query);
        $_SESSION["push_token"] = $push_notification_token;
    }
}
function driverRegister()
{
    $driver_reg_data = $_POST["driver_reg_data"];
    if(empty($driver_reg_data["firstname"])) {
        $error = ["error" => __("Please enter your first name")];
        echo json_encode($error);
        exit;
    }
    if(empty($driver_reg_data["lastname"])) {
        $error = ["error" => __("Please enter your last name")];
        echo json_encode($error);
        exit;
    }
    $driver_reg_data["email"] = trim($driver_reg_data["email"]);
    if(!filter_var($driver_reg_data["email"], FILTER_VALIDATE_EMAIL)) {
        $error = ["error" => __("Your email is not a valid email format")];
        echo json_encode($error);
        exit;
    }
    if(64 < strlen($driver_reg_data["email"])) {
        $error = ["error" => __("Your email is too long")];
        echo json_encode($error);
        exit;
    }
    if(20 < strlen($driver_reg_data["phone"])) {
        $error = ["error" => __("Your phone number is too long")];
        echo json_encode($error);
        exit;
    }
    if(strlen($driver_reg_data["phone"]) < 5) {
        $error = ["error" => "Phone number is too short"];
        echo json_encode($error);
        exit;
    }
    if(empty($driver_reg_data["state"])) {
        $error = ["error" => __("Your phone number is too short")];
        echo json_encode($error);
        exit;
    }
    if(empty($driver_reg_data["address"])) {
        $error = ["error" => __("Please enter an address")];
        echo json_encode($error);
        exit;
    }
    if(empty($driver_reg_data["driver_photo"])) {
        $error = ["error" => __("Please upload a passport photo")];
        echo json_encode($error);
        exit;
    }
    if(empty($driver_reg_data["drivers_license_photo"])) {
        $error = ["error" => __("Missing registration document image")];
        echo json_encode($error);
        exit;
    }
    if(empty($driver_reg_data["road_worthiness_cert"])) {
        $error = ["error" => __("Missing registration document image")];
        echo json_encode($error);
        exit;
    }
    if(strlen($driver_reg_data["password"]) < 8) {
        $error = ["error" => __("Password is too short")];
        echo json_encode($error);
        exit;
    }
    if(15 < strlen($driver_reg_data["password"])) {
        $error = ["error" => __("Password is too long")];
        echo json_encode($error);
        exit;
    }
    $driver_country = codeToCountryName(strtoupper($driver_reg_data["country_2c_code"]));
    if(!$driver_country) {
        $error = ["error" => __("Invalid country selected")];
        echo json_encode($error);
        exit;
    }
    $query = sprintf("SELECT driver_id,email,phone FROM %stbl_drivers WHERE email = \"%s\" OR phone=\"%s\"", DB_TBL_PREFIX, mysqli_real_escape_string($GLOBALS["DB"], $driver_reg_data["email"]), mysqli_real_escape_string($GLOBALS["DB"], $driver_reg_data["phone"]));
    if($result = mysqli_query($GLOBALS["DB"], $query)) {
        if(mysqli_num_rows($result)) {
            $error = ["error" => __("The email or phone number already exists. Please use a different email or phone number")];
            echo json_encode($error);
            exit;
        }
        if($_POST["validate_only"] == 1) {
            $data = ["success" => 1];
            echo json_encode($data);
            exit;
        }
        $uploaded_photo_encoded = $driver_reg_data["driver_photo"];
        $uploaded_photo_encoded_array = explode(",", $uploaded_photo_encoded);
        $image_data = array_pop($uploaded_photo_encoded_array);
        $uploaded_photo_decoded = base64_decode($image_data);
        if(!$uploaded_photo_decoded) {
            $error = ["error" => __("Please upload a passport photo in JPEG format")];
            echo json_encode($error);
            exit;
        }
        $filename = crypto_string("distinct", 20);
        @mkdir(@realpath(USER_PHOTO_PATH) . "/" . $filename[0] . "/" . $filename[1] . "/" . $filename[2], 511, true);
        $image_path = realpath(USER_PHOTO_PATH) . "/" . $filename[0] . "/" . $filename[1] . "/" . $filename[2] . "/";
        $file = $image_path . $filename . ".jpg";
        file_put_contents($file, $uploaded_photo_decoded);
        $driver_passport_photo = $filename . ".jpg";
        $uploaded_photo_encoded = $driver_reg_data["drivers_license_photo"];
        $uploaded_photo_encoded_array = explode(",", $uploaded_photo_encoded);
        $image_data = array_pop($uploaded_photo_encoded_array);
        $uploaded_photo_decoded = base64_decode($image_data);
        if(!$uploaded_photo_decoded) {
            $error = ["error" => __("Please upload registration document image in JPEG format")];
            echo json_encode($error);
            exit;
        }
        $filename = crypto_string("distinct", 20);
        @mkdir(@realpath(USER_PHOTO_PATH) . "/" . $filename[0] . "/" . $filename[1] . "/" . $filename[2], 511, true);
        $image_path = realpath(USER_PHOTO_PATH) . "/" . $filename[0] . "/" . $filename[1] . "/" . $filename[2] . "/";
        $file = $image_path . $filename . ".jpg";
        file_put_contents($file, $uploaded_photo_decoded);
        $driving_license_photo = $filename . ".jpg";
        $uploaded_photo_encoded = $driver_reg_data["road_worthiness_cert"];
        $uploaded_photo_encoded_array = explode(",", $uploaded_photo_encoded);
        $image_data = array_pop($uploaded_photo_encoded_array);
        $uploaded_photo_decoded = base64_decode($image_data);
        if(!$uploaded_photo_decoded) {
            $error = ["error" => __("Please upload registration document image in JPEG format")];
            echo json_encode($error);
            exit;
        }
        $filename = crypto_string("distinct", 20);
        @mkdir(@realpath(USER_PHOTO_PATH) . "/" . $filename[0] . "/" . $filename[1] . "/" . $filename[2], 511, true);
        $image_path = realpath(USER_PHOTO_PATH) . "/" . $filename[0] . "/" . $filename[1] . "/" . $filename[2] . "/";
        $file = $image_path . $filename . ".jpg";
        file_put_contents($file, $uploaded_photo_decoded);
        $road_worthiness_cert = $filename . ".jpg";
        $x = 0;
        while ($x < 10) {
            $referal_code = crypto_string("ABCDEFGHIJKLMNOPQRSTUVWXYZ", 4);
            $referal_code .= crypto_string("123456789", 4);
            $query = sprintf("SELECT * FROM %stbl_drivers WHERE referal_code = \"%s\"", DB_TBL_PREFIX, $referal_code);
            if($result = mysqli_query($GLOBALS["DB"], $query)) {
                if(mysqli_num_rows($result)) {
                    $x++;
                }
                break;
            }
        }
        $reg_with_referral_code = "";
        $ref_code_result_msg = __("Thank you for a patnering with us. We hope you will find success driving and earning with us. You are welcome");
        $ref_user_data = [];
        $referal_settings_data = [];
        if(!empty($driver_reg_data["referal_code"]) && strlen($driver_reg_data["referal_code"]) < 10) {
            $ref_code = mysqli_real_escape_string($GLOBALS["DB"], $driver_reg_data["referal_code"]);
            $query = sprintf("SELECT %1\$stbl_referral_drivers.beneficiary,%1\$stbl_referral_drivers.route_id AS ref_route_id, %1\$stbl_referral_drivers.driver_incentive,%1\$stbl_referral_drivers.invitee_incentive, %1\$stbl_referral_drivers.number_of_rides, %1\$stbl_referral_drivers.number_of_days, %1\$stbl_currencies.symbol FROM %1\$stbl_referral_drivers \r\n        INNER JOIN %1\$stbl_routes ON %1\$stbl_routes.id = %1\$stbl_referral_drivers.route_id\r\n        INNER JOIN %1\$stbl_currencies ON %1\$stbl_currencies.id = %1\$stbl_routes.city_currency_id\r\n        WHERE %1\$stbl_referral_drivers.route_id = %2\$d AND %1\$stbl_referral_drivers.status = 1", DB_TBL_PREFIX, intval($driver_reg_data["operation_city"]));
            if(($result = mysqli_query($GLOBALS["DB"], $query)) && mysqli_num_rows($result)) {
                $referal_settings_data = mysqli_fetch_assoc($result);
                $query = sprintf("SELECT driver_id, disp_lang FROM %stbl_drivers WHERE referal_code = \"%s\"", DB_TBL_PREFIX, $ref_code);
                if(($result = mysqli_query($GLOBALS["DB"], $query)) && mysqli_num_rows($result)) {
                    $reg_with_referral_code = $ref_code;
                    $ref_user_data = mysqli_fetch_assoc($result);
                    $driver_notification_msg = "";
                    if($referal_settings_data["beneficiary"] == 0 || $referal_settings_data["beneficiary"] == 2) {
                        $driver_notification_msg = __("A new driver has signed-up with your referral code. You will earn {---1} when the driver completes {---2} trips in {---3} days", [$referal_settings_data["symbol"] . $referal_settings_data["driver_incentive"], $referal_settings_data["number_of_rides"], $referal_settings_data["number_of_days"]], "d|" . $ref_user_data["disp_lang"]);
                        $query = sprintf("INSERT INTO %stbl_notifications(person_id,user_type,content,n_type,date_created) VALUES \r\n                                (\"%d\",1,\"%s\",0,\"%s\")", DB_TBL_PREFIX, $ref_user_data["driver_id"], mysqli_real_escape_string($GLOBALS["DB"], $driver_notification_msg), gmdate("Y-m-d H:i:s", time()));
                        $result = mysqli_query($GLOBALS["DB"], $query);
                    } elseif($referal_settings_data["beneficiary"] == 1) {
                        $driver_notification_msg = __("A new driver has signed-up with your referral code. Thank you for growing our great and awesome service", NULL, "d|" . $ref_user_data["disp_lang"]);
                        $query = sprintf("INSERT INTO %stbl_notifications(person_id,user_type,content,n_type,date_created) VALUES \r\n                                (\"%d\",1,\"%s\",0,\"%s\")", DB_TBL_PREFIX, $ref_user_data["driver_id"], mysqli_real_escape_string($GLOBALS["DB"], $driver_notification_msg), gmdate("Y-m-d H:i:s", time()));
                        $result = mysqli_query($GLOBALS["DB"], $query);
                    }
                    if($referal_settings_data["beneficiary"] == 1 || $referal_settings_data["beneficiary"] == 2) {
                        $ref_code_result_msg = __("Thank you for patnering with us. Your referral has qualified you to earn {---1} when you complete {---2} trips in {---3} days", [$referal_settings_data["symbol"] . $referal_settings_data["invitee_incentive"], $referal_settings_data["number_of_rides"], $referal_settings_data["number_of_days"]]);
                    }
                }
            }
        }
        $query = sprintf("INSERT INTO %stbl_drivers(reg_route_id,reg_with_referal_code,is_activated,route_id,pwd_raw,password_hash,drv_address,email,firstname,lastname,phone,`state`,drv_country,car_plate_num,car_reg_num,car_model,car_color,ride_id,referal_code,franchise_id,account_create_date,photo_file,bank_name,bank_acc_holder_name,bank_acc_num,bank_code,driver_commision,bank_swift_code,driving_license_file,road_worthiness_file,country_code,country_dial_code) VALUES(\"%d\",\"%s\",\"%d\",\"%d\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\")", DB_TBL_PREFIX, (int) $driver_reg_data["operation_city"], $reg_with_referral_code, DRIVER_REG_ACT_MODE == 2 ? 0 : 1, (int) $driver_reg_data["operation_city"], mysqli_real_escape_string($GLOBALS["DB"], $driver_reg_data["password"]), password_hash(mysqli_real_escape_string($GLOBALS["DB"], $driver_reg_data["password"]), PASSWORD_DEFAULT), mysqli_real_escape_string($GLOBALS["DB"], $driver_reg_data["address"]), mysqli_real_escape_string($GLOBALS["DB"], $driver_reg_data["email"]), mysqli_real_escape_string($GLOBALS["DB"], $driver_reg_data["firstname"]), mysqli_real_escape_string($GLOBALS["DB"], $driver_reg_data["lastname"]), mysqli_real_escape_string($GLOBALS["DB"], $driver_reg_data["phone"]), mysqli_real_escape_string($GLOBALS["DB"], $driver_reg_data["state"]), mysqli_real_escape_string($GLOBALS["DB"], $driver_country), mysqli_real_escape_string($GLOBALS["DB"], $driver_reg_data["car_plate_num"]), mysqli_real_escape_string($GLOBALS["DB"], $driver_reg_data["car_reg_num"]), mysqli_real_escape_string($GLOBALS["DB"], $driver_reg_data["car_model"]), mysqli_real_escape_string($GLOBALS["DB"], $driver_reg_data["car_color"]), mysqli_real_escape_string($GLOBALS["DB"], $driver_reg_data["car_type"]), $referal_code, 1, gmdate("Y-m-d H:i:s", time()), $driver_passport_photo, mysqli_real_escape_string($GLOBALS["DB"], $driver_reg_data["bank_name"]), mysqli_real_escape_string($GLOBALS["DB"], $driver_reg_data["account_holders_name"]), mysqli_real_escape_string($GLOBALS["DB"], $driver_reg_data["account_number"]), mysqli_real_escape_string($GLOBALS["DB"], $driver_reg_data["bank_code"]), DRIVER_DEFAULT_COMMISSION, mysqli_real_escape_string($GLOBALS["DB"], $driver_reg_data["bank_swift_code"]), $driving_license_photo, $road_worthiness_cert, mysqli_real_escape_string($GLOBALS["DB"], $driver_reg_data["country_2c_code"]), "+" . mysqli_real_escape_string($GLOBALS["DB"], $driver_reg_data["country_call_code"]));
        if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
            $error = ["error" => __("An error has occured")];
            echo json_encode($error);
            exit;
        }
        $id = mysqli_insert_id($GLOBALS["DB"]);
        $query = sprintf("INSERT INTO %stbl_notifications(person_id,user_type,content,n_type,date_created) VALUES \r\n        (\"%d\",1,\"%s\",0,\"%s\")", DB_TBL_PREFIX, $id, mysqli_real_escape_string($GLOBALS["DB"], $ref_code_result_msg), gmdate("Y-m-d H:i:s", time()));
        $result = mysqli_query($GLOBALS["DB"], $query);
        $activation_code = crypto_string("nozero", 5);
        $query = sprintf("INSERT INTO %stbl_account_codes (user_id, code,user_type) VALUES (\"%d\",\"%s\",1)", DB_TBL_PREFIX, $id, $activation_code);
        if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
            $query = sprintf("DELETE FROM %stbl_drivers WHERE driver_id = \"%d\"", DB_TBL_PREFIX, $id);
            $result = mysqli_query($GLOBALS["DB"], $query);
            $error = ["error" => __("An error has occured")];
            echo json_encode($error);
            exit;
        }
        $message = "";
        $mail_sender_address = "From: " . MAIL_SENDER;
        $headers = [$mail_sender_address, "MIME-Version: 1.0", "Content-Type: text/html; charset=\"iso-8859-1\""];
        if(EMAIL_TRANSPORT == 1) {
            mail($driver_reg_data["email"], DRIVERS_REG_EMAIL_SUBJ, "<html>" . DRIVERS_REG_EMAIL_MSG . "</html>", join("\r\n", $headers));
        } else {
            sendMail($driver_reg_data["email"], DRIVERS_REG_EMAIL_SUBJ, DRIVERS_REG_EMAIL_MSG);
        }
        $_SESSION["new_reg"]["uid"] = $id;
        $_SESSION["new_reg"]["email"] = $driver_reg_data["email"];
        $_SESSION["new_reg"]["phone"] = $driver_reg_data["phone"];
        $success = ["success" => "1"];
        echo json_encode($success);
        exit;
    }
    $error = ["error" => __("An error has occured")];
    echo json_encode($error);
    exit;
}
function driverLogin()
{
    $phone = $_POST["phone"];
    $password = $_POST["password"];
    $country_dial_code = $_POST["country_dial_code"];
    $token = "";
    $driver_account_details = [];
    $display_language = mysqli_real_escape_string($GLOBALS["DB"], $_POST["display_lang"]);
    if(isset($_POST["timezone"]) && isValidTimezoneId($_POST["timezone"])) {
        $_SESSION["timezone"] = $_POST["timezone"];
        date_default_timezone_set($_SESSION["timezone"]);
    } else {
        date_default_timezone_set("Africa/Lagos");
    }
    $query = sprintf("SELECT *,%1\$stbl_drivers.route_id AS route_id FROM %1\$stbl_drivers \r\n    LEFT JOIN %1\$stbl_rides ON %1\$stbl_rides.id = %1\$stbl_drivers.ride_id\r\n    LEFT JOIN %1\$stbl_routes ON %1\$stbl_routes.id = %1\$stbl_drivers.route_id\r\n    LEFT JOIN %1\$stbl_currencies ON %1\$stbl_currencies.id = %1\$stbl_routes.city_currency_id\r\n    LEFT JOIN %1\$stbl_referral_drivers ON %1\$stbl_referral_drivers.route_id = %1\$stbl_drivers.route_id\r\n    WHERE %1\$stbl_drivers.phone = \"%2\$s\" AND %1\$stbl_drivers.pwd_raw = \"%3\$s\" AND %1\$stbl_drivers.country_dial_code = \"%4\$s\"", DB_TBL_PREFIX, $phone, $password, "+" . $country_dial_code);
    if($result = mysqli_query($GLOBALS["DB"], $query)) {
        if(mysqli_num_rows($result)) {
            $driver_account_details = mysqli_fetch_assoc($result);
            $referral_url = "";
            $refcode_copy = isset($driver_account_details["status"]) && $driver_account_details["status"] == 1 && !empty($driver_account_details["referal_code"]) ? __("Hi, you can now earn good money with your car driving with Droptaxi. Sign-up now with my referral code {---1} to start earning while you drive", [$driver_account_details["referal_code"]], "d|" . $display_language) : "";
            $ref_code_desc = __("Earn {---1} with your referral code when you invite a driver to Droptaxi and the driver completes {---2} trips in {---3} days", [$driver_account_details["symbol"] . $driver_account_details["driver_incentive"], $driver_account_details["number_of_rides"], $driver_account_details["number_of_days"]], "d|" . $display_language);
            $ref_code = isset($driver_account_details["status"]) && $driver_account_details["status"] == 1 && !empty($driver_account_details["referal_code"]) ? "<input id='user-refcode-text' type='text' hidden='hidden' value='" . $refcode_copy . "'><span style='display:block;color:#42a5f5;font-family: Roboto,Noto,sans-serif;font-size:13px;font-weight:400;'>" . __("Referral code") . "</span><p style='margin-top:5px;padding: 15px 5px;border: thin dashed;'><b id='user-refcode'>" . $driver_account_details["referal_code"] . "</b><b style='color: blue;float: right;' onclick=share_message('',\$('#user-refcode-text').val()),'" . $referral_url . "'>" . __("Share") . "</b></p><p>" . $ref_code_desc . "</p>" : "";
            $photo_file = isset($driver_account_details["photo_file"]) ? $driver_account_details["photo_file"] : "0";
            $_SESSION["firstname"] = $driver_account_details["firstname"];
            $_SESSION["lastname"] = $driver_account_details["lastname"];
            $_SESSION["uid"] = $driver_account_details["driver_id"];
            $_SESSION["email"] = $driver_account_details["email"];
            $_SESSION["phone"] = $driver_account_details["phone"];
            $_SESSION["country_dial_code"] = $driver_account_details["country_dial_code"];
            $_SESSION["address"] = $driver_account_details["drv_address"];
            $_SESSION["is_activated"] = $driver_account_details["is_activated"];
            $_SESSION["loggedin"] = 1;
            $_SESSION["ref_code"] = $driver_account_details["referal_code"];
            $_SESSION["city_id"] = !empty($driver_account_details["route_id"]) ? $driver_account_details["route_id"] : 0;
            $_SESSION["driver_rating"] = $driver_account_details["driver_rating"];
            $_SESSION["wallet_amt"] = $driver_account_details["wallet_amount"];
            $_SESSION["push_token"] = $driver_account_details["push_notification_token"];
            $_SESSION["photo"] = SITE_URL . "ajaxphotofile.php?file=" . $photo_file;
            $_SESSION["availability"] = $driver_account_details["available"];
            $_SESSION["driver_city"] = !empty($driver_account_details["r_title"]) ? $driver_account_details["r_title"] : "Unknown";
            $_SESSION["driver_city_dist_unit"] = !empty($driver_account_details["dist_unit"]) ? 1 : 0;
            $profiledata = ["success" => 1, "firstname" => $_SESSION["firstname"], "lastname" => $_SESSION["lastname"], "email" => $_SESSION["email"], "phone" => $_SESSION["phone"], "address" => $_SESSION["address"], "driverid" => $_SESSION["uid"], "ref_code" => $ref_code, "photo" => $_SESSION["photo"], "city_id" => !empty($driver_account_details["route_id"]) ? $driver_account_details["route_id"] : 0, "city" => !empty($driver_account_details["r_title"]) ? $driver_account_details["r_title"] : "Unknown", "city_lat" => !empty($driver_account_details["lat"]) ? $driver_account_details["lat"] : "", "city_lng" => !empty($driver_account_details["lng"]) ? $driver_account_details["lng"] : "", "night_start" => NIGHT_START, "night_end" => NIGHT_END, "carcat" => $driver_account_details["ride_type"], "driver_rating" => $_SESSION["driver_rating"], "driver_ride_id" => $driver_account_details["ride_id"], "country_code" => $driver_account_details["country_code"], "country_dial_code" => $driver_account_details["country_dial_code"]];
            $uncompleted_booking = 0;
            $query = sprintf("SELECT id, `status` FROM %stbl_bookings WHERE driver_id = %d AND (`status` =  0 OR `status` =  1)", DB_TBL_PREFIX, $_SESSION["uid"]);
            if(($result = mysqli_query($GLOBALS["DB"], $query)) && mysqli_num_rows($result)) {
                $uncompleted_booking = mysqli_num_rows($result);
            }
            $default_currency_data = [];
            $query = sprintf("SELECT * FROM %stbl_currencies WHERE `default` = 1", DB_TBL_PREFIX);
            if(($result = mysqli_query($GLOBALS["DB"], $query)) && mysqli_num_rows($result)) {
                $default_currency_data = mysqli_fetch_assoc($result);
            }
            $online_payment_data = ["merchantid" => P_G_PK, "storeid" => STORE_ID, "devid" => DEV_ID, "notifyurl" => NOTIFY_URL];
            $driver_city_currency = ["cur_symbol" => $driver_account_details["symbol"], "cur_code" => $driver_account_details["iso_code"], "cur_exchng" => $driver_account_details["exchng_rate"], "cur_name" => $driver_account_details["name"]];
            $app_settings = ["payment_type" => PAYMENT_TYPE, "ride_otp" => RIDE_OTP, "default_payment_gateway" => DEFAULT_PAYMENT_GATEWAY, "driver_location_update_interval" => DRIVER_LOCATION_UPDATE_INTERVAL, "wallet_topup_presets" => WALLET_TOPUP_PRESETS, "default_banks_and_codes" => DEFAULT_BANKS_AND_CODES];
            $firebase_rtdb_conf = ["databaseURL" => FB_RTDB_URL, "apiKey" => FB_WEB_API_KEY, "storageBucket" => FB_STORAGE_BCKT];
            $query = sprintf("UPDATE %stbl_drivers SET last_login_date = \"%s\", disp_lang = \"%s\" WHERE driver_id = %d", DB_TBL_PREFIX, gmdate("Y-m-d H:i:s", time()), $display_language, $_SESSION["uid"]);
            $result = mysqli_query($GLOBALS["DB"], $query);
            $_SESSION["lang"] = $display_language;
            session_regenerate_id();
            $data = ["availability" => $_SESSION["availability"], "fb_conf" => $firebase_rtdb_conf, "online_pay" => $online_payment_data, "profileinfo" => $profiledata, "cc_num" => CALL_CENTER_NUMBER, "wallet_amt" => $_SESSION["wallet_amt"], "driver_min_wallet_balance" => DRIVER_MIN_WALLET_BALANCE, "driver_id" => $driver_account_details["driver_id"], "email" => $driver_account_details["email"], "is_activated" => $driver_account_details["is_activated"], "account_active" => $driver_account_details["account_active"], "app_version_ios" => APP_VERSION_DRIVER_IOS, "app_version_android" => APP_VERSION_DRIVER_ANDROID, "driver_app_update_url_ios" => DRIVER_APP_UPDATE_URL_IOS, "driver_app_update_url_android" => DRIVER_APP_UPDATE_URL_ANDROID, "currency_data" => $driver_city_currency, "uncompleted_bk" => $uncompleted_booking, "default_currency" => $default_currency_data, "app_settings" => $app_settings, "sess_id" => base64_encode(session_id())];
            echo json_encode($data);
            exit;
        }
        $error = ["error" => __("Invalid account")];
        echo json_encode($error);
        exit;
    }
    $error = ["error" => __("An error has occured")];
    echo json_encode($error);
    exit;
}
function checkDriverLoginStatus()
{
    if(isset($_POST["timezone"]) && isValidTimezoneId($_POST["timezone"])) {
        $_SESSION["timezone"] = $_POST["timezone"];
        date_default_timezone_set($_SESSION["timezone"]);
    } else {
        date_default_timezone_set("Africa/Lagos");
    }
    $display_language = mysqli_real_escape_string($GLOBALS["DB"], $_POST["display_lang"]);
    if(!empty($_SESSION["loggedin"])) {
        $driver_account_details = [];
        $query = sprintf("SELECT *,%1\$stbl_drivers.route_id AS route_id FROM %1\$stbl_drivers \r\n        LEFT JOIN %1\$stbl_rides ON %1\$stbl_rides.id = %1\$stbl_drivers.ride_id\r\n        LEFT JOIN %1\$stbl_routes ON %1\$stbl_routes.id = %1\$stbl_drivers.route_id\r\n        LEFT JOIN %1\$stbl_currencies ON %1\$stbl_currencies.id = %1\$stbl_routes.city_currency_id\r\n        LEFT JOIN %1\$stbl_referral_drivers ON %1\$stbl_referral_drivers.route_id = %1\$stbl_drivers.route_id\r\n        WHERE %1\$stbl_drivers.driver_id = \"%2\$d\"", DB_TBL_PREFIX, $_SESSION["uid"]);
        if($result = mysqli_query($GLOBALS["DB"], $query)) {
            if(mysqli_num_rows($result)) {
                $driver_account_details = mysqli_fetch_assoc($result);
                $referral_url = SITE_URL;
                $refcode_copy = isset($driver_account_details["status"]) && $driver_account_details["status"] == 1 && !empty($driver_account_details["referal_code"]) ? __("Hi, you can now earn good money with your car driving with Droptaxi. Sign-up now with my referral code {---1} to start earning while you drive", [$driver_account_details["referal_code"]], "d|" . $display_language) : "";
                $ref_code_desc = __("Earn {---1} with your referral code when you invite a driver to Droptaxi and the driver completes {---2} trips in {---3} days", [$driver_account_details["symbol"] . $driver_account_details["driver_incentive"], $driver_account_details["number_of_rides"], $driver_account_details["number_of_days"]], "d|" . $display_language);
                $ref_code = isset($driver_account_details["status"]) && $driver_account_details["status"] == 1 && !empty($driver_account_details["referal_code"]) ? "<input id='user-refcode-text' type='text' hidden='hidden' value='" . $refcode_copy . "'><span style='display:block;color:#42a5f5;font-family: Roboto,Noto,sans-serif;font-size:13px;font-weight:400;'>Referral code</span><p style='margin-top:5px;padding: 15px 5px;border: thin dashed;'><b id='user-refcode'>" . $driver_account_details["referal_code"] . "</b><b style='color: blue;float: right;' onclick=share_message('',\$('#user-refcode-text').val(),'" . $referral_url . "') >" . __("Share") . "</b></p><p>" . $ref_code_desc . "</p>" : "";
                if(isset($driver_account_details["is_activated"])) {
                    $_SESSION["is_activated"] = $driver_account_details["is_activated"];
                }
                if(isset($driver_account_details["available"])) {
                    $_SESSION["availability"] = $driver_account_details["available"];
                }
                if(isset($driver_account_details["push_notification_token"])) {
                    $_SESSION["push_token"] = $driver_account_details["push_notification_token"];
                }
                $photo_file = isset($driver_account_details["photo_file"]) ? $driver_account_details["photo_file"] : "0";
                $_SESSION["firstname"] = $driver_account_details["firstname"];
                $_SESSION["lastname"] = $driver_account_details["lastname"];
                $_SESSION["uid"] = $driver_account_details["driver_id"];
                $_SESSION["email"] = $driver_account_details["email"];
                $_SESSION["phone"] = $driver_account_details["phone"];
                $_SESSION["country_dial_code"] = $driver_account_details["country_dial_code"];
                $_SESSION["address"] = $driver_account_details["drv_address"];
                $_SESSION["lastseen"] = $driver_account_details["last_login_date"];
                $_SESSION["joined"] = $driver_account_details["account_create_date"];
                $_SESSION["loggedin"] = 1;
                $_SESSION["ref_code"] = $driver_account_details["referal_code"];
                $_SESSION["city_id"] = !empty($driver_account_details["route_id"]) ? $driver_account_details["route_id"] : 0;
                $_SESSION["is_activated"] = $driver_account_details["is_activated"];
                $_SESSION["driver_rating"] = $driver_account_details["driver_rating"];
                $_SESSION["wallet_amt"] = $driver_account_details["wallet_amount"];
                $_SESSION["push_token"] = $driver_account_details["push_notification_token"];
                $_SESSION["photo"] = SITE_URL . "ajaxphotofile.php?file=" . $photo_file;
                $_SESSION["driver_city"] = !empty($driver_account_details["r_title"]) ? $driver_account_details["r_title"] : "Unknown";
                $_SESSION["driver_city_dist_unit"] = !empty($driver_account_details["dist_unit"]) ? 1 : 0;
                $tariff_data = getroutetariffs();
                $uncompleted_booking = 0;
                $query = sprintf("SELECT id, `status` FROM %stbl_bookings WHERE driver_id = %d AND (`status` =  0 OR `status` =  1)", DB_TBL_PREFIX, $_SESSION["uid"]);
                if(($result = mysqli_query($GLOBALS["DB"], $query)) && mysqli_num_rows($result)) {
                    $uncompleted_booking = mysqli_num_rows($result);
                }
                $default_currency_data = [];
                $query = sprintf("SELECT * FROM %stbl_currencies WHERE `default` = 1", DB_TBL_PREFIX);
                if(($result = mysqli_query($GLOBALS["DB"], $query)) && mysqli_num_rows($result)) {
                    $default_currency_data = mysqli_fetch_assoc($result);
                }
                $profiledata = ["success" => 1, "firstname" => $_SESSION["firstname"], "lastname" => $_SESSION["lastname"], "email" => $_SESSION["email"], "phone" => $_SESSION["phone"], "address" => $_SESSION["address"], "driverid" => $_SESSION["uid"], "ref_code" => $ref_code, "photo" => $_SESSION["photo"], "city_id" => !empty($driver_account_details["route_id"]) ? $driver_account_details["route_id"] : 0, "city" => !empty($driver_account_details["r_title"]) ? $driver_account_details["r_title"] : "Unknown", "city_lat" => !empty($driver_account_details["lat"]) ? $driver_account_details["lat"] : "", "city_lng" => !empty($driver_account_details["lng"]) ? $driver_account_details["lng"] : "", "night_start" => NIGHT_START, "night_end" => NIGHT_END, "carcat" => !empty($driver_account_details["ride_type"]) ? $driver_account_details["ride_type"] : "N/A", "driver_rating" => $_SESSION["driver_rating"], "driver_ride_id" => $driver_account_details["ride_id"], "country_code" => $driver_account_details["country_code"], "country_dial_code" => $driver_account_details["country_dial_code"]];
                $online_payment_data = ["merchantid" => P_G_PK, "storeid" => STORE_ID, "devid" => DEV_ID, "notifyurl" => NOTIFY_URL];
                $driver_city_currency = ["cur_symbol" => $driver_account_details["symbol"], "cur_code" => $driver_account_details["iso_code"], "cur_exchng" => $driver_account_details["exchng_rate"], "cur_name" => $driver_account_details["name"]];
                $app_settings = ["payment_type" => PAYMENT_TYPE, "ride_otp" => RIDE_OTP, "default_payment_gateway" => DEFAULT_PAYMENT_GATEWAY, "driver_location_update_interval" => DRIVER_LOCATION_UPDATE_INTERVAL, "wallet_topup_presets" => WALLET_TOPUP_PRESETS, "default_banks_and_codes" => DEFAULT_BANKS_AND_CODES];
                $firebase_rtdb_conf = ["databaseURL" => FB_RTDB_URL, "apiKey" => FB_WEB_API_KEY, "storageBucket" => FB_STORAGE_BCKT];
                $query = sprintf("UPDATE %stbl_drivers SET last_login_date = \"%s\", disp_lang = \"%s\" WHERE driver_id = %d", DB_TBL_PREFIX, gmdate("Y-m-d H:i:s", time()), $display_language, $_SESSION["uid"]);
                $result = mysqli_query($GLOBALS["DB"], $query);
                $_SESSION["lang"] = $display_language;
                $time_online = gettodaytimeonline();
                $hours = floor($time_online / 3600);
                $minutes = floor($time_online % 3600 / 60);
                $driver_time_online_formated = "";
                if(!empty($hours)) {
                    $driver_time_online_formated = $hours . "H ";
                }
                if(!empty($minutes)) {
                    $driver_time_online_formated .= $minutes . "M ";
                } else {
                    $driver_time_online_formated .= "0M ";
                }
                $driver_today_earnings = 0;
                $completed_trips_today = 0;
                $query = sprintf("SELECT %1\$stbl_bookings.paid_amount, %1\$stbl_bookings.cur_exchng_rate, %1\$stbl_bookings.driver_commision FROM %1\$stbl_bookings\r\n        WHERE %1\$stbl_bookings.driver_id = %2\$d AND %1\$stbl_bookings.status = 3 AND DATE(%1\$stbl_bookings.date_completed) = \"%3\$s\"", DB_TBL_PREFIX, $_SESSION["uid"], date("Y-m-d", time()));
                if(($result = mysqli_query($GLOBALS["DB"], $query)) && mysqli_num_rows($result)) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $completed_trips_today++;
                        $driver_today_earnings += (double) $row["paid_amount"] / $row["cur_exchng_rate"] * $row["driver_commision"] / 100;
                    }
                }
                $data = ["driver_time_online" => $driver_time_online_formated, "fb_conf" => $firebase_rtdb_conf, "driver_today_earning" => $driver_today_earnings, "completed_trips" => $completed_trips_today, "availability" => $_SESSION["availability"], "tariff_data" => $tariff_data, "loggedin" => 1, "online_pay" => $online_payment_data, "profileinfo" => $profiledata, "cc_num" => CALL_CENTER_NUMBER, "wallet_amt" => $_SESSION["wallet_amt"], "driver_min_wallet_balance" => DRIVER_MIN_WALLET_BALANCE, "driver_id" => $driver_account_details["driver_id"], "email" => $driver_account_details["email"], "is_activated" => $driver_account_details["is_activated"], "account_active" => $driver_account_details["account_active"], "currency_data" => $driver_city_currency, "app_version_ios" => APP_VERSION_DRIVER_IOS, "app_version_android" => APP_VERSION_DRIVER_ANDROID, "driver_app_update_url_ios" => DRIVER_APP_UPDATE_URL_IOS, "driver_app_update_url_android" => DRIVER_APP_UPDATE_URL_ANDROID, "uncompleted_bk" => $uncompleted_booking, "default_currency" => $default_currency_data, "app_settings" => $app_settings, "sess_id" => base64_encode(session_id())];
                echo json_encode($data);
                exit;
            }
            $error = ["error" => __("Invalid account")];
            echo json_encode($error);
            exit;
        }
        $error = ["error" => __("An error has occured")];
        echo json_encode($error);
        exit;
    }
    $app_settings = ["payment_type" => PAYMENT_TYPE, "ride_otp" => RIDE_OTP, "default_payment_gateway" => DEFAULT_PAYMENT_GATEWAY, "driver_location_update_interval" => DRIVER_LOCATION_UPDATE_INTERVAL, "wallet_topup_presets" => WALLET_TOPUP_PRESETS, "default_banks_and_codes" => DEFAULT_BANKS_AND_CODES];
    $referral_data = [];
    $query = sprintf("SELECT %1\$stbl_referral_drivers.route_id AS ref_route_id, %1\$stbl_referral_drivers.invitee_incentive, %1\$stbl_referral_drivers.number_of_rides, %1\$stbl_referral_drivers.number_of_days, %1\$stbl_currencies.symbol FROM %1\$stbl_referral_drivers \r\n        INNER JOIN %1\$stbl_routes ON %1\$stbl_routes.id = %1\$stbl_referral_drivers.route_id\r\n        INNER JOIN %1\$stbl_currencies ON %1\$stbl_currencies.id = %1\$stbl_routes.city_currency_id\r\n        WHERE %1\$stbl_referral_drivers.status = 1", DB_TBL_PREFIX);
    if(($result = mysqli_query($GLOBALS["DB"], $query)) && mysqli_num_rows($result)) {
        while ($row = mysqli_fetch_assoc($result)) {
            $referral_data[$row["ref_route_id"]] = $row;
            $referral_data[$row["ref_route_id"]]["ref_reg_msg"] = __("Enter a referral code and earn {---1} when you complete {---2} trips in {---3} days", [$row["symbol"] . $row["invitee_incentive"], $row["number_of_rides"], $row["number_of_days"]], "d|" . $display_language);
        }
    }
    $tariff_data = getroutetariffs();
    $display_language = mysqli_real_escape_string($GLOBALS["DB"], $_POST["display_lang"]);
    $_SESSION["lang"] = $display_language;
    $data = ["loggedin" => 0, "referral_data" => $referral_data, "tariff_data" => $tariff_data, "cc_num" => CALL_CENTER_NUMBER, "app_version_ios" => APP_VERSION_DRIVER_IOS, "app_version_android" => APP_VERSION_DRIVER_ANDROID, "driver_app_update_url_ios" => DRIVER_APP_UPDATE_URL_IOS, "driver_app_update_url_android" => DRIVER_APP_UPDATE_URL_ANDROID, "app_settings" => $app_settings, "sess_id" => base64_encode(session_id())];
    echo json_encode($data);
    exit;
}
function getuserinfopages()
{
    if(empty($_SESSION["loggedin"])) {
        $error = ["error" => __("Please login to continue")];
        echo json_encode($error);
        exit;
    }
    $user_info_pages = [];
    $query = sprintf("SELECT * FROM %stbl_appinfo_pages WHERE id = 2 OR id = 4", DB_TBL_PREFIX);
    if($result = mysqli_query($GLOBALS["DB"], $query)) {
        if(mysqli_num_rows($result)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $user_info_pages[$row["id"]] = $row;
            }
            $data = ["success" => 1, "about" => $user_info_pages[2]["content"], "terms" => $user_info_pages[4]["content"]];
            echo json_encode($data);
            exit;
        }
        $error = ["error" => __("An error has occured")];
        echo json_encode($error);
        exit;
    }
    $error = ["error" => __("An error has occured")];
    echo json_encode($error);
    exit;
}
function gethelpcontent()
{
    if(empty($_SESSION["loggedin"])) {
        $error = ["error" => __("Please login to continue")];
        echo json_encode($error);
        exit;
    }
    $id = !empty($_GET["id"]) ? (int) $_GET["id"] : 0;
    $help_item_content = [];
    $query = sprintf("SELECT title,content FROM %stbl_appinfo_pages WHERE id = %d AND type = 1", DB_TBL_PREFIX, $id);
    if($result = mysqli_query($GLOBALS["DB"], $query)) {
        if(mysqli_num_rows($result)) {
            $help_item_content = mysqli_fetch_assoc($result);
            $data = ["success" => 1, "help_content" => $help_item_content["content"], "help_title" => $help_item_content["title"]];
            echo json_encode($data);
            exit;
        }
        $error = ["error" => __("An error has occured")];
        echo json_encode($error);
        exit;
    }
    $error = ["error" => __("An error has occured")];
    echo json_encode($error);
    exit;
}
function gethelpdata()
{
    if(empty($_SESSION["loggedin"])) {
        $error = ["error" => __("Please login to continue")];
        echo json_encode($error);
        exit;
    }
    $help_content_data = [];
    $help_categories_data = [];
    $query = sprintf("SELECT * FROM %1\$stbl_help_cat WHERE %1\$stbl_help_cat.show_driver = 1", DB_TBL_PREFIX);
    if($result = mysqli_query($GLOBALS["DB"], $query)) {
        if(mysqli_num_rows($result)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $help_categories_data[$row["id"]] = $row;
            }
            $help_topics_strings = [];
            $query = sprintf("SELECT id,cat_id,title,excerpt FROM %1\$stbl_appinfo_pages WHERE %1\$stbl_appinfo_pages.show_driver = 1 AND %1\$stbl_appinfo_pages.type = 1", DB_TBL_PREFIX);
            if($result = mysqli_query($GLOBALS["DB"], $query)) {
                if(mysqli_num_rows($result)) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $help_content_data[$row["cat_id"]][] = $row;
                        $help_topics_strings[$row["cat_id"]] = "";
                    }
                    $help_categories_string = "";
                    $help_uncategorized_topics_string = "";
                    foreach ($help_categories_data as $helpcategoriesdata) {
                        if($helpcategoriesdata["id"] == 1) {
                        } else {
                            $help_categories_string .= "<ons-list-item onclick='showhelpcattopics(" . $helpcategoriesdata["id"] . ")' modifier='longdivider'>\r\n                    \r\n                                                <div class='center'>\r\n                                                    <div style='width:100%;'><span class='list-item__title' id='cat-title-" . $helpcategoriesdata["id"] . "'>" . $helpcategoriesdata["title"] . "</div>\r\n                                                    <span class='list-item__subtitle'>" . $helpcategoriesdata["desc"] . "</span>\r\n                                                    \r\n                                                </div>\r\n\r\n                                                <div class='right'>\r\n                                                    <ons-icon icon='fa-chevron-right' size='14px' style='color:#42a5f5;'></ons-icon>                                                    \r\n                                                </div>\r\n                                                \r\n                                            \r\n                                            </ons-list-item>";
                        }
                    }
                    foreach ($help_content_data as $key => $helpcontentdata) {
                        if($key == 1) {
                            foreach ($help_content_data[$key] as $helpcontenttopics) {
                                $help_uncategorized_topics_string .= "<ons-list-item onclick='showhelptopic(" . $helpcontenttopics["id"] . ")' modifier='longdivider'>\r\n                    \r\n                                                        <div class='center'>\r\n                                                            <div style='width:100%;'><span class='list-item__title' id='topic-title-" . $helpcontenttopics["id"] . "'>" . $helpcontenttopics["title"] . "</div>\r\n                                                            <span class='list-item__subtitle'>" . $helpcontenttopics["excerpt"] . "</span>\r\n                                                            \r\n                                                        </div>\r\n                                                        \r\n                                                    \r\n                                                    </ons-list-item>";
                            }
                        } else {
                            foreach ($help_content_data[$key] as $helpcontenttopics) {
                                $help_topics_strings[$key] .= "<ons-list-item onclick='showhelptopic(" . $helpcontenttopics["id"] . ")' modifier='longdivider'>\r\n                        \r\n                                                    <div class='center'>\r\n                                                        <div style='width:100%;'><span class='list-item__title' id='topic-title-" . $helpcontenttopics["id"] . "'>" . $helpcontenttopics["title"] . "</div>\r\n                                                        <span class='list-item__subtitle'>" . $helpcontenttopics["excerpt"] . "</span>\r\n                                                        \r\n                                                    </div>\r\n                                                    \r\n                                                \r\n                                                </ons-list-item>";
                            }
                        }
                    }
                    $help_categories_string = $help_uncategorized_topics_string . $help_categories_string;
                    $data = ["success" => 1, "help_cat" => $help_categories_string, "help_cat_topics" => $help_topics_strings];
                    echo json_encode($data);
                    exit;
                } else {
                    $error = ["error" => __("Help information is not available")];
                    echo json_encode($error);
                    exit;
                }
            } else {
                $error = ["error" => __("An error has occured")];
                echo json_encode($error);
                exit;
            }
        } else {
            $error = ["error" => __("Help information is not available")];
            echo json_encode($error);
            exit;
        }
    } else {
        $error = ["error" => __("An error has occured")];
        echo json_encode($error);
        exit;
    }
}
function setAvailability()
{
    if(empty($_SESSION["loggedin"])) {
        $error = ["error" => __("Please login to continue")];
        echo json_encode($error);
        exit;
    }
    $driver_account_details = [];
    $query = sprintf("SELECT available FROM %stbl_drivers WHERE driver_id = %d ", DB_TBL_PREFIX, $_SESSION["uid"]);
    if($result = mysqli_query($GLOBALS["DB"], $query)) {
        if(mysqli_num_rows($result)) {
            $driver_account_details = mysqli_fetch_assoc($result);
            $availability = !empty($driver_account_details["available"]) ? 0 : 1;
            $query = sprintf("UPDATE %stbl_drivers SET `available` = %d WHERE driver_id = %d", DB_TBL_PREFIX, $availability, $_SESSION["uid"]);
            if($result = mysqli_query($GLOBALS["DB"], $query)) {
                $_SESSION["availability"] = $availability;
                $response = ["success" => 1, "status" => $availability];
                echo json_encode($response);
                exit;
            }
            $error = ["error" => __("An error has occured")];
            echo json_encode($error);
            exit;
        }
        $error = ["error" => __("An error has occured")];
        echo json_encode($error);
        exit;
    }
    $error = ["error" => __("An error has occured")];
    echo json_encode($error);
    exit;
}
function driverLogout()
{
    $_SESSION = [];
    session_destroy();
    $data = ["loggedout" => 1];
    echo json_encode($data);
    exit;
}
function del_user_acc()
{
    if(empty($_SESSION["loggedin"])) {
        $error = ["error" => __("Please login to continue")];
        echo json_encode($error);
        exit;
    }
    $password = !empty($_POST["pwd"]) ? mysqli_real_escape_string($GLOBALS["DB"], trim($_POST["pwd"])) : "";
    $query = sprintf("SELECT * FROM %stbl_drivers WHERE `driver_id` = %d AND pwd_raw = \"%s\"", DB_TBL_PREFIX, $_SESSION["uid"], $password);
    if($result = mysqli_query($GLOBALS["DB"], $query)) {
        if(!mysqli_num_rows($result)) {
            $error = ["error" => __("Invalid account")];
            echo json_encode($error);
            exit;
        }
        $query = sprintf("SELECT * FROM %stbl_bookings WHERE `driver_id` = %d AND (`status` = 0 OR `status` = 1 OR `status` = 6)", DB_TBL_PREFIX, $_SESSION["uid"]);
        if($result = mysqli_query($GLOBALS["DB"], $query)) {
            if(mysqli_num_rows($result)) {
                $error = ["error" => __("An error has occured")];
                echo json_encode($error);
                exit;
            }
            $query = sprintf("DELETE FROM %stbl_drivers WHERE `driver_id` = %d AND pwd_raw = \"%s\"", DB_TBL_PREFIX, $_SESSION["uid"], $password);
            $result = mysqli_query($GLOBALS["DB"], $query);
            $query = sprintf("DELETE FROM %stbl_notifications WHERE `person_id` = %d AND user_type = %d", DB_TBL_PREFIX, $_SESSION["uid"], 1);
            $result = mysqli_query($GLOBALS["DB"], $query);
            $_SESSION = [];
            session_destroy();
            $data = ["success" => 1];
            echo json_encode($data);
            exit;
        }
        $error = ["error" => __("An error has occured")];
        echo json_encode($error);
        exit;
    }
    $error = ["error" => __("An error has occured")];
    echo json_encode($error);
    exit;
}
function rateride()
{
    if(empty($_SESSION["loggedin"])) {
        $error = ["error" => __("Please login to continue")];
        echo json_encode($error);
        exit;
    }
    $booking_id = (int) $_GET["bookingid"];
    $rating = (int) $_GET["rating"];
    if($rating < 0 || $rating < 1) {
        $rating = 1;
    } elseif(5 < $rating) {
        $rating = 5;
    }
    $booking_data = [];
    $query = sprintf("SELECT %1\$stbl_bookings.user_id, %1\$stbl_users.user_rating FROM %1\$stbl_bookings \r\n    INNER JOIN %1\$stbl_users ON %1\$stbl_users.user_id = %1\$stbl_bookings.user_id\r\n    WHERE %1\$stbl_bookings.id = %2\$d", DB_TBL_PREFIX, $booking_id);
    if($result = mysqli_query($GLOBALS["DB"], $query)) {
        if(mysqli_num_rows($result)) {
            $booking_data = mysqli_fetch_assoc($result);
            $query = sprintf("INSERT INTO %stbl_ratings_drivers (booking_id,`driver_id`,driver_comment,driver_rating) VALUES (%d,%d,\"%s\",%d)", DB_TBL_PREFIX, $booking_id, $_SESSION["uid"], mysqli_real_escape_string($GLOBALS["DB"], strip_tags($_GET["comment"])), $rating);
            if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
                $error = ["error" => __("An error has occured")];
                echo json_encode($error);
                exit;
            }
            $user_new_rating = floor(($booking_data["user_rating"] + $rating) / 2);
            if(5 < $user_new_rating) {
                $user_new_rating = 5;
            }
            $query = sprintf("UPDATE %stbl_users SET user_rating = %d WHERE user_id = %d", DB_TBL_PREFIX, $user_new_rating, $booking_data["user_id"]);
            $result = mysqli_query($GLOBALS["DB"], $query);
            $data = ["success" => 1];
            echo json_encode($data);
            exit;
        }
        $error = ["error" => __("An error has occured")];
        echo json_encode($error);
        exit;
    }
    $error = ["error" => __("An error has occured")];
    echo json_encode($error);
    exit;
}
function userRegister()
{
    $_POST["firstname"] = str_replace(" ", "", $_POST["firstname"]);
    $_POST["lastname"] = str_replace(" ", "", $_POST["lastname"]);
    if(empty($_POST["firstname"])) {
        $error = ["error" => "Please enter your first name"];
        echo json_encode($error);
        exit;
    }
    if(strlen($_POST["firstname"]) < 2) {
        $error = ["error" => "Your firstname is too short"];
        echo json_encode($error);
        exit;
    }
    if(preg_match("/[^a-z]/i", $_POST["firstname"])) {
        $error = ["error" => "Your first name must contain only alphabetical characters"];
        echo json_encode($error);
        exit;
    }
    if(empty($_POST["lastname"])) {
        $error = ["error" => "Please enter your lastname"];
        echo json_encode($error);
        exit;
    }
    if(preg_match("/[^a-z]/i", $_POST["lastname"])) {
        $error = ["error" => "Your lastname must contain only alphabetical characters"];
        echo json_encode($error);
        exit;
    }
    if(strlen($_POST["lastname"]) < 2) {
        $error = ["error" => "Your lastname is too short"];
        echo json_encode($error);
        exit;
    }
    if(empty($_POST["email"])) {
        $error = ["error" => "Please enter a valid email"];
        echo json_encode($error);
        exit;
    }
    if(!filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
        $error = ["error" => "Your email is not a valid email format"];
        echo json_encode($error);
        exit;
    }
    if(64 < strlen($_POST["email"])) {
        $error = ["error" => "Your email is too long. Email must be lower than 64 characters"];
        echo json_encode($error);
        exit;
    }
    if(20 < strlen($_POST["phone"])) {
        $error = ["error" => "Your phone number is too long"];
        echo json_encode($error);
        exit;
    }
    if(strlen($_POST["phone"]) < 5) {
        $error = ["error" => "Your phone number is too short"];
        echo json_encode($error);
        exit;
    }
    if(strlen($_POST["password"]) < 8) {
        $error = ["error" => "Password must not be less than eight characters"];
        echo json_encode($error);
        exit;
    }
    if(60 < strlen($_POST["password"])) {
        $error = ["error" => "Password is too long. Password must not be more than 60 characters"];
        echo json_encode($error);
        exit;
    }
    if($_POST["password"] !== $_POST["rpassword"]) {
        $error = ["error" => "Password and password repeat are not the same"];
        echo json_encode($error);
        exit;
    }
    if(empty($_POST["password"]) || empty($_POST["rpassword"])) {
        $error = ["error" => "Please enter a password"];
        echo json_encode($error);
        exit;
    }
    $query = sprintf("SELECT user_id,email,phone FROM %stbl_users WHERE email = \"%s\" OR phone = \"%s\"", DB_TBL_PREFIX, mysqli_real_escape_string($GLOBALS["DB"], $_POST["email"]), mysqli_real_escape_string($GLOBALS["DB"], $_POST["phone"]));
    if($result = mysqli_query($GLOBALS["DB"], $query)) {
        if(mysqli_num_rows($result)) {
            $row = mysqli_fetch_assoc($result);
            if($row["email"] == mysqli_real_escape_string($GLOBALS["DB"], $_POST["email"])) {
                $error = ["error" => "Email already Exists"];
                echo json_encode($error);
                exit;
            }
            if($row["phone"] == mysqli_real_escape_string($GLOBALS["DB"], $_POST["phone"])) {
                $error = ["error" => "Phone number already Exists"];
                echo json_encode($error);
                exit;
            }
            $error = ["error" => "Email or phone number already Exists"];
            echo json_encode($error);
            exit;
        }
        $refcode = crypto_string("alnum", 15);
        $query = sprintf("INSERT INTO %stbl_users (firstname, lastname, email, phone, pwd_raw, password_hash, account_create_date,referal_code) VALUES(\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\")", DB_TBL_PREFIX, mysqli_real_escape_string($GLOBALS["DB"], ucfirst(strtolower(strip_tags($_POST["firstname"])))), mysqli_real_escape_string($GLOBALS["DB"], ucfirst(strtolower(strip_tags($_POST["lastname"])))), mysqli_real_escape_string($GLOBALS["DB"], strip_tags($_POST["email"])), mysqli_real_escape_string($GLOBALS["DB"], strip_tags($_POST["phone"])), mysqli_real_escape_string($GLOBALS["DB"], $_POST["password"]), password_hash(mysqli_real_escape_string($GLOBALS["DB"], $_POST["password"]), PASSWORD_DEFAULT), gmdate("Y-m-d H:i:s", time()), $refcode);
        if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
            $error = ["error" => "Dabase Error"];
            echo json_encode($error);
            exit;
        }
        $user_id = mysqli_insert_id($GLOBALS["DB"]);
        $token = crypto_string("nozero", 5);
        if(!$user_id) {
            $error = ["error" => "Error creating your account"];
            echo json_encode($error);
            exit;
        }
        $query = sprintf("INSERT INTO %stbl_account_codes (user_id, code) VALUES (\"%d\",\"%s\")", DB_TBL_PREFIX, $user_id, $token);
        if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
            $query = sprintf("DELETE FROM %stbl_users WHERE user_id = \"%d\"", DB_TBL_PREFIX, $user_id);
            $result = mysqli_query($GLOBALS["DB"], $query);
            $error = ["error" => "Error creating your account"];
            echo json_encode($error);
            exit;
        }
        $message = "";
        $message .= "<html>";
        $message .= "<div style = \"max-width:100%;\"><img src=\"http://" . $_SERVER["HTTP_HOST"] . "/img/logo-mid.png\" width=\"180px\" style=\"margin-left:auto; margin-right:auto; display:block;\"/><br/>";
        $message .= "<h2 style=\"text-align:center;\">Thank you for registering with CabMan</h2><br /><br />";
        $message .= "<p>Your account has been created but is currently not activated. To complete your registration, enter the activation code as requested.</p>";
        $message .= "<h2><b style='text-align:center;'>" . $token . "</b></h2>";
        $message .= "<br /><br /><br /><br /><br /><br />";
        $message .= "<p>You have received this email because a user created an account on CabMan Website.";
        $message .= "Simply ignore the message if it is not you</p></div>";
        $message .= "</html >";
        $mail_sender_address = "From: " . MAIL_SENDER;
        $headers = [$mail_sender_address, "MIME-Version: 1.0", "Content-Type: text/html; charset=\"iso-8859-1\""];
        if(!mail($_POST["email"], WEBSITE_NAME . " - Activation Code", $message, join("\r\n", $headers))) {
            $query = sprintf("DELETE FROM %stbl_users WHERE user_id = \"%d\"", DB_TBL_PREFIX, $user_id);
            $result = mysqli_query($GLOBALS["DB"], $query);
            $error = ["error" => "Error creating your account"];
            echo json_encode($error);
            exit;
        }
        $data = ["success" => 1];
        echo json_encode($data);
        exit;
    }
    $error = ["error" => "Database Error"];
    echo json_encode($error);
    exit;
}
function userResendCode()
{
    if(isset($_SESSION["code_resend"]["time"]) && $_SESSION["code_resend"]["time"] < time() - 30) {
        $error = ["error" => "Please wait a while before resending code."];
        echo json_encode($error);
        exit;
    }
    if(!empty($_SESSION["loggedin"])) {
        $user_id = $_SESSION["uid"];
        $email = $_SESSION["email"];
        $phone = $_SESSION["phone"];
    } elseif(!empty($_SESSION["new_reg"])) {
        $user_id = $_SESSION["new_reg"]["uid"];
        $email = $_SESSION["new_reg"]["email"];
        $phone = $_SESSION["new_reg"]["phone"];
    } else {
        $error = ["error" => "Error resending activation code"];
        echo json_encode($error);
        exit;
    }
    $code = crypto_string("nozero", 5);
    $query = sprintf("DELETE FROM %stbl_account_codes WHERE user_id = \"%d\" AND user_type = 1 AND context=0", DB_TBL_PREFIX, $user_id);
    $result = mysqli_query($GLOBALS["DB"], $query);
    $query = sprintf("INSERT INTO %stbl_account_codes (user_id, code, user_type) VALUES (\"%d\",\"%s\",\"%d\")", DB_TBL_PREFIX, $user_id, $code, 1);
    if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
        $error = ["error" => "Error resending activation code"];
        echo json_encode($error);
        exit;
    }
    $message = "";
    $message .= "<html>";
    $message .= "<div style = \"max-width:100%;\"><img src=\"http://" . $_SERVER["HTTP_HOST"] . "/img/logo-mid.png\" width=\"180px\" style=\"margin-left:auto; margin-right:auto; display:block;\"/><br/>";
    $message .= "<h2 style=\"text-align:center;\">Thank you for registering with " . WEBSITE_NAME . "</h2><br /><br />";
    $message .= "<p>Your account has been created but is currently not activated. To complete your registration, enter the activation code as requested.</p>";
    $message .= "<h2><b style='text-align:center;'>" . $code . "</b></h2>";
    $message .= "<br /><br /><br /><br /><br /><br />";
    $message .= "<p>You have received this email because a user created an account on " . WEBSITE_NAME . " Website.";
    $message .= "Simply ignore the message if it is not you</p></div>";
    $message .= "</html >";
    $mail_sender_address = "From: " . MAIL_SENDER;
    $headers = [$mail_sender_address, "MIME-Version: 1.0", "Content-Type: text/html; charset=\"iso-8859-1\""];
    if(!mail($email, WEBSITE_NAME . " - Activation Code", $message, join("\r\n", $headers))) {
        $error = ["error" => "Error resending activation code"];
        echo json_encode($error);
        exit;
    }
    $_SESSION["code_resend"]["time"] = time();
    $success = ["success" => "Activation code sent"];
    echo json_encode($success);
    exit;
}
function passwordReset()
{
    $user_account_details = [];
    $email = !empty($_POST["email"]) ? mysqli_real_escape_string($GLOBALS["DB"], $_POST["email"]) : "";
    $query = sprintf("SELECT driver_id,email,phone FROM %stbl_drivers WHERE email = \"%s\"", DB_TBL_PREFIX, $email);
    if($result = mysqli_query($GLOBALS["DB"], $query)) {
        if(mysqli_num_rows($result)) {
            $user_account_details = mysqli_fetch_assoc($result);
            $code = crypto_string("nozero", 6);
            $query = sprintf("DELETE FROM %stbl_account_codes WHERE user_id = \"%d\" AND user_type = 1 AND context=1", DB_TBL_PREFIX, $user_account_details["driver_id"]);
            $result = mysqli_query($GLOBALS["DB"], $query);
            $query = sprintf("INSERT INTO %stbl_account_codes (user_id, code,context, user_type) VALUES (\"%d\",\"%s\",1,1)", DB_TBL_PREFIX, $user_account_details["driver_id"], $code);
            if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
                $error = ["error" => __("An error has occured")];
                echo json_encode($error);
                exit;
            }
            $message = "";
            $mail_sender_address = "From: " . MAIL_SENDER;
            $headers = [$mail_sender_address, "MIME-Version: 1.0", "Content-Type: text/html; charset=\"iso-8859-1\""];
            if(EMAIL_TRANSPORT == 1) {
                mail($email, PWD_RST_EMAIL_SUBJ, "<html>" . PWD_RST_EMAIL_MSG . "<br><br><h2><b style='text-align:center;'>" . $code . "</b></h2><br><br></html>", join("\r\n", $headers));
            } else {
                sendMail($email, PWD_RST_EMAIL_SUBJ, PWD_RST_EMAIL_MSG . "<br><br><h2><b style='text-align:center;'>" . $code . "</b></h2><br><br></html>");
            }
            $success = ["success" => __("Password reset code has been sent to your email")];
            echo json_encode($success);
            exit;
        }
        $error = ["error" => __("Invalid account")];
        echo json_encode($error);
        exit;
    }
    $error = ["error" => __("An error has occured")];
    echo json_encode($error);
    exit;
}
function passwordResetVerify()
{
    $user_account_details = [];
    $passcode = !empty($_POST["code"]) ? mysqli_real_escape_string($GLOBALS["DB"], $_POST["code"]) : "";
    $query = sprintf("SELECT user_id FROM %stbl_account_codes WHERE code = \"%s\" AND user_type = 1 AND context = 1", DB_TBL_PREFIX, $passcode);
    if($result = mysqli_query($GLOBALS["DB"], $query)) {
        if(mysqli_num_rows($result)) {
            $user_account_details = mysqli_fetch_assoc($result);
            $newpassword = crypto_string("hexdec", 5);
            $query = sprintf("UPDATE %stbl_drivers SET `pwd_raw` = \"%s\" WHERE driver_id = \"%d\"", DB_TBL_PREFIX, $newpassword, $user_account_details["user_id"]);
            if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
                $error = ["error" => __("An error has occured")];
                echo json_encode($error);
                exit;
            }
            $query = sprintf("DELETE FROM %stbl_account_codes WHERE code = \"%s\" AND user_type = 1 AND context=1", DB_TBL_PREFIX, $passcode);
            $result = mysqli_query($GLOBALS["DB"], $query);
            $success = ["success" => __("Password change was successful. Your new password is {---1}", ["<b>" . $newpassword . "</b>"])];
            echo json_encode($success);
            exit;
        }
        $error = ["error" => __("Invalid password reset code")];
        echo json_encode($error);
        exit;
    }
    $error = ["error" => __("An error has occured")];
    echo json_encode($error);
    exit;
}
function userActivateCode()
{
    $code = (int) $_POST["code"];
    if(empty($code)) {
        $error = ["error" => "Please enter an activation code"];
        echo json_encode($error);
        exit;
    }
    if(!empty($_SESSION["loggedin"])) {
        $user_id = $_SESSION["uid"];
    } elseif(!empty($_SESSION["new_reg"])) {
        $user_id = $_SESSION["new_reg"]["uid"];
    } elseif(!empty($_SESSION["not_activated_user"])) {
        $user_id = $_SESSION["not_activated_user"]["uid"];
    } else {
        $user_id = 0;
    }
    $query = sprintf("SELECT code FROM %stbl_account_codes WHERE code = \"%d\" AND user_id = \"%d\" AND user_type = 1", DB_TBL_PREFIX, $code, $user_id);
    if($result = mysqli_query($GLOBALS["DB"], $query)) {
        if(mysqli_num_rows($result)) {
            $row = mysqli_fetch_assoc($result);
            $query = sprintf("UPDATE %stbl_drivers SET is_activated = 1, account_active = 1 WHERE driver_id = \"%d\"", DB_TBL_PREFIX, $user_id);
            if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
                $error = ["error" => "Database error."];
                echo json_encode($error);
                exit;
            }
            $query = sprintf("DELETE FROM %stbl_account_codes WHERE user_id = \"%d\" AND code=\"%d\" AND user_type = 1", DB_TBL_PREFIX, $user_id, $code);
            $result = mysqli_query($GLOBALS["DB"], $query);
            $_SESSION["is_activated"] = 1;
            $response = ["success" => "Your account has been successfully activated. Restart app."];
            echo json_encode($response);
            exit;
        }
        $error = ["error" => "Wrong activation code entered."];
        echo json_encode($error);
        exit;
    }
    $error = ["error" => "Database Error"];
    echo json_encode($error);
    exit;
}
function bookingassigndriver()
{
    $booking_id = (int) $_POST["booking_id"];
    $driver_id = (int) $_POST["driver_id"];
    $driver_data = [];
    if(empty($_SESSION["loggedin"])) {
        $error = ["error" => "Please re-login and retry."];
        echo json_encode($error);
        exit;
    }
    $query = sprintf("SELECT * FROM %1\$stbl_drivers WHERE %1\$stbl_drivers.driver_id = \"%2\$d\"", DB_TBL_PREFIX, $driver_id);
    if(($result = mysqli_query($GLOBALS["DB"], $query)) && mysqli_num_rows($result)) {
        $driver_data = mysqli_fetch_assoc($result);
    }
    $driver_firstname = !empty($driver_data["firstname"]) ? $driver_data["firstname"] : "";
    $driver_lastname = !empty($driver_data["lastname"]) ? $driver_data["lastname"] : "";
    $driver_phone = !empty($driver_data["phone"]) ? $driver_data["phone"] : "";
    $query = sprintf("UPDATE %stbl_bookings SET driver_id = \"%d\",driver_firstname = \"%s\",driver_lastname = \"%s\",driver_phone = \"%s\" WHERE id = \"%d\"", DB_TBL_PREFIX, $driver_id, $driver_firstname, $driver_lastname, $driver_phone, $booking_id);
    if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
        $error = ["error" => "Failed to assign driver"];
        echo json_encode($error);
        exit;
    }
    $success = ["success" => "Record updated successfully"];
    echo json_encode($success);
    exit;
}
function getrouterides()
{
    $tariff_data = [];
    $rides_data = [];
    $route_id = (int) $_POST["route_id"];
    if(empty($_SESSION["loggedin"])) {
        $error = ["error" => "Please re-login and retry."];
        echo json_encode($error);
        exit;
    }
    $query = sprintf("SELECT *,%1\$stbl_routes.id AS route_id  FROM %1\$stbl_routes\r\n    INNER JOIN %1\$stbl_rides_tariffs ON %1\$stbl_rides_tariffs.routes_id = %1\$stbl_routes.id\r\n    INNER JOIN %1\$stbl_rides ON %1\$stbl_rides_tariffs.ride_id = %1\$stbl_rides.id\r\n    WHERE %1\$stbl_rides.avail = 1", DB_TBL_PREFIX);
    if(($result = mysqli_query($GLOBALS["DB"], $query)) && mysqli_num_rows($result)) {
        while ($row = mysqli_fetch_assoc($result)) {
            $tariff_data[] = $row;
        }
    }
    $data_array = [];
    foreach ($tariff_data as $tariffdata) {
        $rides_data[$tariffdata["route_id"]]["r_id"] = $tariffdata["route_id"];
        $rides_data[$tariffdata["route_id"]]["cars"][] = $tariffdata;
        $select_options = "";
        foreach ($rides_data[$tariffdata["route_id"]]["cars"] as $ridesdata) {
            $select_options .= "<option data-cpk = " . $ridesdata["cost_per_km"] . " data-cpm = " . $ridesdata["cost_per_minute"] . " data-puc = " . $ridesdata["pickup_cost"] . " data-doc=" . $ridesdata["drop_off_cost"] . " data-cc=" . $ridesdata["cancel_cost"] . " data-ncpk = " . $ridesdata["ncost_per_km"] . " data-ncpm = " . $ridesdata["ncost_per_minute"] . " data-npuc = " . $ridesdata["npickup_cost"] . " data-ndoc=" . $ridesdata["ndrop_off_cost"] . " data-ncc=" . $ridesdata["ncancel_cost"] . " value=" . $ridesdata["ride_id"] . " data-rideid=" . $ridesdata["ride_id"] . " data-ridedesc=" . $ridesdata["ride_desc"] . ">" . $ridesdata["ride_type"] . "</option>";
        }
        $rides_data[$tariffdata["route_id"]]["cars_html"] = $select_options;
    }
    $data_array = ["success" => 1, "result" => $rides_data];
    echo json_encode($data_array);
    exit;
}
function getuserprofileinfo()
{
    if(empty($_SESSION["loggedin"])) {
        $error = ["error" => "Please re-login and retry."];
        echo json_encode($error);
        exit;
    }
    $profiledata = ["success" => 1, "firstname" => $_SESSION["firstname"], "lastname" => $_SESSION["lastname"], "email" => $_SESSION["email"], "phone" => $_SESSION["phone"], "address" => $_SESSION["address"]];
    echo json_encode($profiledata);
    exit;
}
function getroutetariffs()
{
    $tariff_data = [];
    $rides_data = [];
    $query = sprintf("SELECT *,%1\$stbl_routes.id AS route_id  FROM %1\$stbl_routes\r\n    INNER JOIN %1\$stbl_currencies ON %1\$stbl_currencies.id = %1\$stbl_routes.city_currency_id\r\n    INNER JOIN %1\$stbl_rides_tariffs ON %1\$stbl_rides_tariffs.routes_id = %1\$stbl_routes.id\r\n    INNER JOIN %1\$stbl_rides ON %1\$stbl_rides_tariffs.ride_id = %1\$stbl_rides.id\r\n    WHERE %1\$stbl_rides.avail = 1 ORDER BY %1\$stbl_routes.r_title", DB_TBL_PREFIX);
    if(($result = mysqli_query($GLOBALS["DB"], $query)) && mysqli_num_rows($result)) {
        while ($row = mysqli_fetch_assoc($result)) {
            $tariff_data[] = $row;
        }
    }
    $data_array = [];
    $city_select_options = "";
    $state_select_options = "";
    $tariff_ids = [];
    $sel_route_id = !empty($_POST["sel_route_id"]) ? $_POST["sel_route_id"] : 0;
    $sel_route_name = !empty($_POST["sel_route_name"]) ? $_POST["sel_route_name"] : 0;
    $count = 0;
    $route_selected = "";
    $rides_ids = [];
    $rides_url = "";
    foreach ($tariff_data as $tariffdata) {
        $count++;
        $rides_data[$tariffdata["route_id"]]["r_id"] = $tariffdata["route_id"];
        if(empty($sel_route_id)) {
            if($count == 1) {
            } else {
                $route_selected = "";
            }
        } elseif($sel_route_id == $tariffdata["route_id"] && $sel_route_name == $tariffdata["r_title"]) {
            $rides_data["route-exists"] = 1;
        } else {
            $route_selected = "";
        }
        if(array_search($tariffdata["route_id"], $tariff_ids) === false) {
            if($tariffdata["r_scope"] == 0) {
                $tariff_ids[] = $tariffdata["route_id"];
                $rides_data["city_name"][] = $tariffdata["r_title"];
                $rides_data["city_id"][] = $tariffdata["route_id"];
                $route_name_variable = "'" . $tariffdata["r_title"] . "'";
                $city_select_options .= "<ons-list-item tappable class='city-route-list' onclick = routecityitemselected(" . $tariffdata["route_id"] . ") data-routename='" . $tariffdata["r_title"] . "' id=route-sel-" . $tariffdata["route_id"] . " ><label class='left'><ons-radio " . $route_selected . " name='city-route' id='radio-sel-" . $tariffdata["route_id"] . "' input-id='radio-" . $tariffdata["route_id"] . "'></ons-radio></label><label for='radio-" . $tariffdata["route_id"] . "' class='center'>" . $tariffdata["r_title"] . "</label></ons-list-item>";
            } else {
                $tariff_ids[] = $tariffdata["route_id"];
                $rides_data["state_name"][] = $tariffdata["r_title"];
                $rides_data["state_id"][] = $tariffdata["route_id"];
                $route_name_variable = "'" . $tariffdata["r_title"] . "'";
                $state_select_options .= "<ons-list-item data-plng='" . $tariffdata["pick_lng"] . "' data-plat='" . $tariffdata["pick_lat"] . "' data-dlng='" . $tariffdata["drop_lng"] . "' data-dlat='" . $tariffdata["drop_lat"] . "' data-pus='" . $tariffdata["pick_name"] . "' data-dos='" . $tariffdata["drop_name"] . "' tappable class='state-route-list' onclick = routestateitemselected(" . $tariffdata["route_id"] . ") data-routename='" . $tariffdata["r_title"] . "' id=route-sel-" . $tariffdata["route_id"] . " ><label for='radio-" . $tariffdata["route_id"] . "' class='center'>" . $tariffdata["r_title"] . "</label></ons-list-item>";
            }
        }
        $rides_data[$tariffdata["route_id"]]["cars"][] = $tariffdata;
    }
    foreach ($rides_data as $key => $route_ridesdata) {
        $select_options = "";
        if(!is_numeric($key)) {
        } else {
            foreach ($route_ridesdata["cars"] as $ridesdata) {
                $ride_filename = explode("/", $ridesdata["ride_img"]);
                $ride_title = htmlentities($ridesdata["ride_type"]);
                $ride_image = SITE_URL . "img/ride_imgs/" . array_pop($ride_filename);
                $ride_desc = htmlentities($ridesdata["ride_desc"]);
                if(array_search($ridesdata["ride_id"], $rides_ids) === false) {
                    $rides_ids[] = $ridesdata["ride_id"];
                    $rides_url .= "<img id='uniq-car-type-id-" . $ridesdata["ride_id"] . "' src='" . $ride_image . "' >";
                }
                $select_options .= "<img data-cfare='" . $ridesdata["cfare_enabled"] . "' data-ppenabled='" . $ridesdata["pp_enabled"] . "' data-ppstart='" . $ridesdata["pp_start"] . "' data-ppend='" . $ridesdata["pp_end"] . "' data-ppdays='" . $ridesdata["pp_active_days"] . "' data-ppchargetype='" . $ridesdata["pp_charge_type"] . "' data-ppchargevalue='" . $ridesdata["pp_charge_value"] . "' data-img='" . $ride_image . "' data-cpk = '" . $ridesdata["cost_per_km"] . "' data-cpm = '" . $ridesdata["cost_per_minute"] . "' data-puc = '" . $ridesdata["pickup_cost"] . "' data-doc='" . $ridesdata["drop_off_cost"] . "' data-cc='" . $ridesdata["cancel_cost"] . "' data-ncpk = '" . $ridesdata["ncost_per_km"] . "' data-ncpm = '" . $ridesdata["ncost_per_minute"] . "' data-npuc = '" . $ridesdata["npickup_cost"] . "' data-ndoc='" . $ridesdata["ndrop_off_cost"] . "' data-ncc='" . $ridesdata["ncancel_cost"] . "' value='" . $ridesdata["ride_id"] . "' data-rideid='" . $ridesdata["ride_id"] . "' data-ridedesc='" . $ride_desc . "' data-title='" . $ride_title . "' style='width:85px;margin-right:auto;margin-left:auto;' src='" . $ride_image . "' />";
            }
            $rides_data[$key]["cars_html"] = $select_options;
        }
    }
    $rides_data["city"] = $city_select_options;
    $rides_data["state"] = $state_select_options;
    $rides_data["preloadrides"] = $rides_url;
    if(PAYMENT_TYPE == 2) {
        $rides_data["payment_options"] = "<option value='1'>Cash</option><option value='2'>Wallet</option>";
    } elseif(PAYMENT_TYPE == 1) {
        $rides_data["payment_options"] = "<option value='2'>Wallet</option>";
    } else {
        $rides_data["payment_options"] = "<option value='1'>Cash</option>";
    }
    $rides_data["nighttime"] = ["start_hour" => NIGHT_START, "end_hour" => NIGHT_END];
    $data_array = ["success" => 1, "result" => $rides_data];
    return $data_array;
}
function getwalletinfo()
{
    $user_wallet_details = [];
    if(empty($_SESSION["loggedin"])) {
        $error = ["error" => __("Please login to continue")];
        echo json_encode($error);
        exit;
    }
    $template = "";
    $template2 = "";
    $transaction_data_sort = [];
    $transaction_data_sort["credit-debit-data"] = [];
    $transaction_data_sort["funding-data"] = [];
    $query = sprintf("SELECT *,%1\$stbl_wallet_transactions.cur_exchng_rate AS exchng_rate,%1\$stbl_bookings.cur_symbol AS b_cur_symbol,%1\$stbl_wallet_transactions.cur_symbol AS t_cur_symbol,%1\$stbl_wallet_transactions.transaction_id AS transaction_id,DATE(%1\$stbl_wallet_transactions.transaction_date) AS transaction_date,%1\$stbl_wallet_transactions.transaction_date AS transaction_dates FROM %1\$stbl_wallet_transactions \r\n    LEFT JOIN %1\$stbl_bookings ON %1\$stbl_bookings.id = %1\$stbl_wallet_transactions.book_id\r\n    LEFT JOIN %1\$stbl_drivers ON %1\$stbl_drivers.driver_id = %1\$stbl_wallet_transactions.user_id\r\n    LEFT JOIN %1\$stbl_routes ON %1\$stbl_routes.id = %1\$stbl_drivers.route_id\r\n    LEFT JOIN %1\$stbl_currencies ON %1\$stbl_currencies.id = %1\$stbl_routes.city_currency_id\r\n    WHERE %1\$stbl_wallet_transactions.user_id = \"%2\$d\" AND %1\$stbl_wallet_transactions.user_type = 1 ORDER BY %1\$stbl_wallet_transactions.transaction_date DESC LIMIT 0,300 ", DB_TBL_PREFIX, $_SESSION["uid"]);
    if(($result = mysqli_query($GLOBALS["DB"], $query)) && mysqli_num_rows($result)) {
        while ($row = mysqli_fetch_assoc($result)) {
            if($row["type"] == 2 || $row["type"] == 3) {
                $transaction_data_sort["credit-debit-data"][$row["transaction_date"]]["date"] = $row["transaction_date"];
                $transaction_data_sort["credit-debit-data"][$row["transaction_date"]]["data"][] = $row;
            } else {
                $transaction_data_sort["funding-data"][$row["transaction_date"]]["date"] = $row["transaction_date"];
                $transaction_data_sort["funding-data"][$row["transaction_date"]]["data"][] = $row;
            }
        }
    }
    foreach ($transaction_data_sort["funding-data"] as $transactiondatasort) {
        if(!empty($transactiondatasort["data"])) {
            $t_date_format = date("l, M j, Y", strtotime($transactiondatasort["date"] . " UTC"));
            $template .= "<ons-list-header style='border-top: thin solid grey;border-bottom: thin solid grey;font-size: 14px;'>" . $t_date_format . "</ons-list-header>";
            foreach ($transactiondatasort["data"] as $transaction_d) {
                $transaction_time = date("g:i A", strtotime($transaction_d["transaction_dates"] . " UTC"));
                $transaction_id_upper = strtoupper($transaction_d["transaction_id"]);
                $wallet_balance_converted = (double) $transaction_d["wallet_balance"] * (double) $transaction_d["exchng_rate"];
                $wallet_balance_converted = round($wallet_balance_converted, 2);
                $template .= "<ons-list-item modifier='longdivider'>\r\n                            \r\n                                        <div class='center'>\r\n                                            <div style='width:100%;margin-bottom:15px;'><ons-icon icon='fa-circle' size='14px' style='color: #24c539; font-size: 14px;'></ons-icon> <span class='list-item__title'>" . $transaction_time . "</span> </div>\r\n                                            <span class='list-item__subtitle' style='margin-bottom:5px;'><span style='color:#000;font-size:20px;'>" . __("Amount") . ": " . $transaction_d["t_cur_symbol"] . $transaction_d["amount"] . "</span></span>\r\n                                            <span class='list-item__subtitle'><span style='color:#777'><ons-icon icon='fa-square' size='8px' style='vertical-align: bottom;color: #1867c2; font-size: 10px;'></ons-icon> " . __("Description") . ": " . $transaction_d["desc"] . "</span></span>\r\n                                            <span class='list-item__subtitle'><span style='color:#777'><ons-icon icon='fa-square' size='8px' style='vertical-align: bottom;color: #1867c2; font-size: 10px;'></ons-icon> " . __("Transaction ID") . ":" . $transaction_id_upper . " </span></span>                                            \r\n                                            <span class='list-item__subtitle'><span style='color:#777'><ons-icon icon='fa-square' size='8px' style='vertical-align: bottom;color: #1867c2; font-size: 10px;'></ons-icon> " . __("Wallet Balance") . ":</span> " . $transaction_d["t_cur_symbol"] . $wallet_balance_converted . "</span>\r\n                                        </div>\r\n                                    \r\n                                    </ons-list-item>";
            }
        }
    }
    foreach ($transaction_data_sort["credit-debit-data"] as $transactiondatasort) {
        if(!empty($transactiondatasort["data"])) {
            $t_date_format = date("l, M j, Y", strtotime($transactiondatasort["date"] . " UTC"));
            $template2 .= "<ons-list-header style='border-top: thin solid grey;border-bottom: thin solid grey;font-size: 14px;'>" . $t_date_format . "</ons-list-header>";
            foreach ($transactiondatasort["data"] as $transaction_d) {
                $transaction_time = date("g:i A", strtotime($transaction_d["transaction_dates"] . " UTC"));
                $transaction_id_upper = strtoupper($transaction_d["transaction_id"]);
                $wallet_balance_converted = (double) $transaction_d["wallet_balance"] * (double) $transaction_d["exchng_rate"];
                $wallet_balance_converted = round($wallet_balance_converted, 2);
                $indicate_credit_debit = $transaction_d["type"] == 2 ? "<ons-icon icon='fa-circle' size='14px' style='color: green; font-size: 14px;'></ons-icon>" : "<ons-icon icon='fa-circle' size='14px' style='color: red; font-size: 14px;'></ons-icon>";
                $booking_fare = "";
                $booking_id = "";
                if($transaction_d["book_id"]) {
                    $booking_id = "#" . str_pad($transaction_d["book_id"], 5, "0", STR_PAD_LEFT);
                    $booking_fare = $transaction_d["b_cur_symbol"] . $transaction_d["paid_amount"];
                } else {
                    $booking_id = "N/A";
                    $booking_fare = "N/A";
                }
                $booking_id = !empty($transaction_d["book_id"]) ? "#" . str_pad($transaction_d["book_id"], 5, "0", STR_PAD_LEFT) : "N/A";
                $transaction_id_upper = strtoupper($transaction_d["transaction_id"]);
                $template2 .= "<ons-list-item modifier='longdivider'>\r\n                            \r\n                                        <div class='center'>\r\n                                            <div style='width:100%;margin-bottom:15px;'>" . $indicate_credit_debit . " <span class='list-item__title'>" . $transaction_time . "</span> </div>\r\n                                            <span class='list-item__subtitle' style='margin-bottom:5px;'><span style='color:#000;font-size:20px;'>" . __("Amount") . ": " . $transaction_d["t_cur_symbol"] . $transaction_d["amount"] . "</span></span>\r\n                                            <span class='list-item__subtitle'><span style='color:#777'><ons-icon icon='fa-square' size='8px' style='vertical-align: bottom;color: #1867c2; font-size: 10px;'></ons-icon> " . __("Description") . ": " . $transaction_d["desc"] . "</span></span>\r\n                                            <span class='list-item__subtitle'><span style='color:#777'><ons-icon icon='fa-square' size='8px' style='vertical-align: bottom;color: #1867c2; font-size: 10px;'></ons-icon> " . __("Transaction ID") . ":" . $transaction_id_upper . " </span></span>                                            \r\n                                            <span class='list-item__subtitle'><span style='color:#777'><ons-icon icon='fa-square' size='8px' style='vertical-align: bottom;color: #1867c2; font-size: 10px;'></ons-icon> " . __("Booking ID") . ":</span> " . $booking_id . " </span>\r\n                                            <span class='list-item__subtitle'><span style='color:#777'><ons-icon icon='fa-square' size='8px' style='vertical-align: bottom;color: #1867c2; font-size: 10px;'></ons-icon> " . __("Booking Fare") . ":</span> " . $booking_fare . " </span>\r\n                                            <span class='list-item__subtitle'><span style='color:#777'><ons-icon icon='fa-square' size='8px' style='vertical-align: bottom;color: #1867c2; font-size: 10px;'></ons-icon> " . __("Wallet Balance") . ":</span> " . $transaction_d["t_cur_symbol"] . $wallet_balance_converted . "</span>\r\n                                        </div>\r\n                                    \r\n                                    </ons-list-item>";
            }
        }
    }
    $query = sprintf("SELECT %1\$stbl_drivers.wallet_amount,%1\$stbl_currencies.symbol,%1\$stbl_currencies.exchng_rate FROM %1\$stbl_drivers \r\n    LEFT JOIN %1\$stbl_routes ON %1\$stbl_routes.id = %1\$stbl_drivers.route_id\r\n    LEFT JOIN %1\$stbl_currencies ON %1\$stbl_currencies.id = %1\$stbl_routes.city_currency_id\r\n    WHERE %1\$stbl_drivers.driver_id = %2\$d", DB_TBL_PREFIX, $_SESSION["uid"]);
    if($result = mysqli_query($GLOBALS["DB"], $query)) {
        if(mysqli_num_rows($result)) {
            $user_wallet_details = mysqli_fetch_assoc($result);
            $_SESSION["wallet_amt"] = $user_wallet_details["wallet_amount"];
            $withdrawal_enabled = 0;
            $withdrawal_message = "";
            if(isset($user_wallet_details["wallet_amount"])) {
                $balance = (double) $user_wallet_details["wallet_amount"] - DRIVER_WITHDRAL_MIN_BALANCE - 1;
                if(empty($balance) || $balance < 0) {
                    $withdrawal_enabled = 0;
                    $withdrawal_message = __("Insufficient wallet amount! Complete more rides to increase wallet balance");
                } else {
                    $withdrawal_enabled = 1;
                    $withdrawal_message = __("Enter the Amount of money you want to cash out.") . __("Maximum amount") . ": <b>" . $user_wallet_details["symbol"] . floattocurrency($balance * $user_wallet_details["exchng_rate"]) . "</b>";
                }
            } else {
                $withdrawal_enabled = 0;
                $withdrawal_message = __("Insufficient wallet amount!");
            }
            $data_array = ["success" => 1, "wallet_amt" => $_SESSION["wallet_amt"], "wallet_history" => $template, "wallet_earning" => $template2, "withdrawenabled" => $withdrawal_enabled, "withdrawmessage" => $withdrawal_message, "driver_min_wallet_balance" => DRIVER_MIN_WALLET_BALANCE];
            echo json_encode($data_array);
            exit;
        }
        $error = ["error" => __("An error has occured")];
        echo json_encode($error);
        exit;
    }
    $error = ["error" => __("An error has occured")];
    echo json_encode($error);
    exit;
}
function getbookings()
{
    if(empty($_SESSION["loggedin"])) {
        $error = ["error" => "Please re-login and retry."];
        echo json_encode($error);
        exit;
    }
    $booking_data = [];
    $booking_pend_onride = "";
    $booking_completed = "";
    $booking_cancelled = "";
    $query = sprintf("SELECT *, DATE(%1\$stbl_bookings.date_created) AS created_date,%1\$stbl_bookings.id AS booking_id FROM %1\$stbl_bookings \r\n    LEFT JOIN %1\$stbl_drivers ON %1\$stbl_drivers.driver_id = %1\$stbl_bookings.driver_id\r\n    LEFT JOIN %1\$stbl_rides ON %1\$stbl_rides.id = %1\$stbl_bookings.ride_id\r\n    LEFT JOIN %1\$stbl_users ON %1\$stbl_users.user_id = %1\$stbl_bookings.user_id\r\n    WHERE %1\$stbl_bookings.user_id = %2\$s ORDER BY %1\$stbl_bookings.date_created DESC LIMIT 0,200 ", DB_TBL_PREFIX, $_SESSION["uid"]);
    if($result = mysqli_query($GLOBALS["DB"], $query)) {
        if(mysqli_num_rows($result)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $booking_data[] = $row;
            }
            mysqli_free_result($result);
            $booking_data_sort = [];
            foreach ($booking_data as $bookingdata) {
                if($bookingdata["status"] == 0 || $bookingdata["status"] == 1) {
                    $booking_data_sort[$bookingdata["created_date"]]["date"] = $bookingdata["created_date"];
                    $booking_data_sort[$bookingdata["created_date"]]["pend_onride"][] = $bookingdata;
                } elseif($bookingdata["status"] == 3) {
                    $booking_data_sort[$bookingdata["created_date"]]["date"] = $bookingdata["created_date"];
                    $booking_data_sort[$bookingdata["created_date"]]["completed"][] = $bookingdata;
                } elseif($bookingdata["status"] == 2) {
                    $booking_data_sort[$bookingdata["created_date"]]["date"] = $bookingdata["created_date"];
                    $booking_data_sort[$bookingdata["created_date"]]["cancelled"][] = $bookingdata;
                }
            }
            foreach ($booking_data_sort as $bookingdatasort) {
                if(!empty($bookingdatasort["pend_onride"])) {
                    $booking_pend_onride .= "<ons-list-header>" . $bookingdatasort["date"] . "</ons-list-header>";
                    foreach ($bookingdatasort["pend_onride"] as $bookingdatasort_po) {
                        $booking_time = date("g:i A", strtotime($bookingdatasort_po["date_created"] . " UTC"));
                        $booking_ptime = date("g:i A", strtotime($bookingdatasort_po["pickup_datetime"] . " UTC"));
                        $booking_driver = isset($bookingdatasort_po["driver_id"]) ? $bookingdatasort_po["driver_firstname"] . " " . $bookingdatasort_po["driver_lastname"] : "N/A";
                        $booking_driver_assigned = isset($bookingdatasort_po["driver_id"]) ? 1 : 0;
                        $status = "";
                        $close_btn = "";
                        if($bookingdatasort_po["status"] == 0) {
                            $status = "<span style='color:#e541e5'>[Pending]</span>";
                            $close_btn = "<span style='display:inline-block;float:right'><ons-icon onclick = 'bookingcancel(" . $bookingdatasort_po["booking_id"] . "," . $booking_driver_assigned . ")' icon='fa-times' size='18px' style='color:red'></ons-icon></span>";
                        } else {
                            $status = "<span style='color:lightgreen'>[On-ride]</span>";
                            $close_btn = "";
                        }
                        $booking_pdate_time = date("d/m/Y g:i A", strtotime($bookingdatasort_po["pickup_datetime"] . " UTC"));
                        $drvr_photo_file = isset($bookingdatasort_po["photo_file"]) ? SITE_URL . "ajaxphotofile.php?file=" . $bookingdatasort_po["photo_file"] : "0";
                        $booking_payment_type = "";
                        if(!empty($bookingdatasort_po["payment_type"])) {
                            if($bookingdatasort_po["payment_type"] == 2) {
                                $booking_payment_type = "Wallet";
                            } else {
                                $booking_payment_type = "Cash / POS";
                            }
                        }
                        $ride_filename = explode("/", $bookingdatasort_po["ride_img"]);
                        $ride_image = SITE_URL . "img/ride_imgs/" . array_pop($ride_filename);
                        $booking_title = str_pad($bookingdatasort_po["booking_id"], 5, "0", STR_PAD_LEFT);
                        $booking_pend_onride .= "<ons-list-item data-ridedesc='" . $bookingdatasort_po["ride_desc"] . "'  data-driverphone='" . $bookingdatasort_po["driver_phone"] . "' data-ptype='" . $booking_payment_type . "' data-put='" . $booking_pdate_time . "' data-driverimg='" . $drvr_photo_file . "' data-rideimg='" . $ride_image . "' data-drivername='" . $booking_driver . "' data-cost='" . $bookingdatasort_po["estimated_cost"] . "' data-ride='" . $bookingdatasort_po["ride_type"] . "' data-pul='" . $bookingdatasort_po["pickup_address"] . "' data-dol='" . $bookingdatasort_po["dropoff_address"] . "' data-btitle='" . $booking_title . "' id='booking-list-item-" . $bookingdatasort_po["booking_id"] . "' modifier='longdivider'>\r\n                \r\n                                            <div class='center'>\r\n                                                <div style='width:100%'><span class='list-item__title'>" . $booking_time . " " . $status . " </span> | <span onclick='showbookingdetails(" . $bookingdatasort_po["booking_id"] . ")' style='color:skyblue'>View details</span> " . $close_btn . "</div>\r\n                                                <span class='list-item__subtitle'><span style='color:yellow'>Booking ID:</span> " . $booking_title . "</span>\r\n                                                <span class='list-item__subtitle'><span style='color:lightgreen'>Pick-up:</span> " . $bookingdatasort_po["pickup_address"] . "</span>\r\n                                                <span class='list-item__subtitle'><span style='color:orange'>Drop-off:</span> " . $bookingdatasort_po["dropoff_address"] . "</span>\r\n                                                <span class='list-item__subtitle'><span style='color:cyan'>Driver:</span> " . $booking_driver . "</span>\r\n                                                \r\n                                            </div>\r\n                                        \r\n                                        </ons-list-item>";
                    }
                }
                if(!empty($bookingdatasort["completed"])) {
                    $booking_completed .= "<ons-list-header>" . $bookingdatasort["date"] . "</ons-list-header>";
                    foreach ($bookingdatasort["completed"] as $bookingdatasort_comp) {
                        $booking_time = date("g:i A", strtotime($bookingdatasort_comp["date_created"] . " UTC"));
                        $booking_ptime = date("g:i A", strtotime($bookingdatasort_comp["pickup_datetime"] . " UTC"));
                        $booking_dtime = isset($bookingdatasort_comp["dropoff_datetime"]) ? date("g:i A", strtotime($bookingdatasort_comp["dropoff_datetime"] . " UTC")) : "N/A";
                        $booking_paid_amt = isset($bookingdatasort_comp["paid_amount"]) ? $bookingdatasort_comp["paid_amount"] : "N/A";
                        $booking_driver = isset($bookingdatasort_comp["driver_id"]) ? $bookingdatasort_comp["driver_firstname"] . " " . $bookingdatasort_comp["driver_lastname"] : "N/A";
                        $booking_pdate_time = date("d/m/Y g:i A", strtotime($bookingdatasort_comp["pickup_datetime"] . " UTC"));
                        $drvr_photo_file = isset($bookingdatasort_comp["photo_file"]) ? SITE_URL . "ajaxphotofile.php?file=" . $bookingdatasort_comp["photo_file"] : "0";
                        $booking_payment_type = "";
                        if(!empty($bookingdatasort_comp["payment_type"])) {
                            if($bookingdatasort_comp["payment_type"] == 2) {
                                $booking_payment_type = "Wallet";
                            } else {
                                $booking_payment_type = "Cash / POS";
                            }
                        }
                        $ride_filename = explode("/", $bookingdatasort_comp["ride_img"]);
                        $ride_image = SITE_URL . "img/ride_imgs/" . array_pop($ride_filename);
                        $booking_title = str_pad($bookingdatasort_comp["booking_id"], 5, "0", STR_PAD_LEFT);
                        $booking_completed .= "<ons-list-item data-ridedesc='" . $bookingdatasort_comp["ride_desc"] . "'  data-driverphone='" . $bookingdatasort_comp["driver_phone"] . "' data-ptype='" . $booking_payment_type . "' data-put='" . $booking_pdate_time . "' data-driverimg='" . $drvr_photo_file . "' data-rideimg='" . $ride_image . "' data-drivername='" . $booking_driver . "' data-cost='" . $bookingdatasort_comp["estimated_cost"] . "' data-ride='" . $bookingdatasort_comp["ride_type"] . "' data-pul='" . $bookingdatasort_comp["pickup_address"] . "' data-dol='" . $bookingdatasort_comp["dropoff_address"] . "' data-btitle='" . $booking_title . "' id='booking-list-item-" . $bookingdatasort_comp["booking_id"] . "' modifier='longdivider'>\r\n                                            <div class='center'>\r\n                                                <span class='list-item__title'>" . $booking_time . " | <span onclick='showbookingdetails(" . $bookingdatasort_comp["booking_id"] . ")' style='color:skyblue'>View details</span> </span>\r\n                                                <span class='list-item__subtitle'><span style='color:yellow'>Booking ID:</span> " . $booking_title . "</span>\r\n                                                <span class='list-item__subtitle'><span style='color:lightgreen'>Pick-up:</span> " . $bookingdatasort_comp["pickup_address"] . "</span>\r\n                                                <span class='list-item__subtitle'><span style='color:orange'>Drop-off:</span> " . $bookingdatasort_comp["dropoff_address"] . "</span>\r\n                                                <span class='list-item__subtitle'><span style='color:cyan'>Driver: </span>" . $booking_driver . "</span>\r\n                                            </div>\r\n                                        \r\n                                        </ons-list-item>";
                    }
                }
                if(!empty($bookingdatasort["cancelled"])) {
                    $booking_cancelled .= "<ons-list-header>" . $bookingdatasort["date"] . "</ons-list-header>";
                    foreach ($bookingdatasort["cancelled"] as $bookingdatasort_canc) {
                        $booking_time = date("g:i A", strtotime($bookingdatasort_canc["date_created"] . " UTC"));
                        $booking_ptime = date("g:i A", strtotime($bookingdatasort_canc["pickup_datetime"] . " UTC"));
                        $booking_driver = isset($bookingdatasort_canc["driver_id"]) ? $bookingdatasort_canc["driver_firstname"] . " " . $bookingdatasort_canc["driver_lastname"] : "N/A";
                        $booking_pdate_time = date("d/m/Y g:i A", strtotime($bookingdatasort_canc["pickup_datetime"] . " UTC"));
                        $drvr_photo_file = isset($bookingdatasort_canc["photo_file"]) ? SITE_URL . "ajaxphotofile.php?file=" . $bookingdatasort_canc["photo_file"] : "0";
                        $booking_payment_type = "";
                        if(!empty($bookingdatasort_canc["payment_type"])) {
                            if($bookingdatasort_canc["payment_type"] == 2) {
                                $booking_payment_type = "Wallet";
                            } else {
                                $booking_payment_type = "Cash / POS";
                            }
                        }
                        $ride_filename = explode("/", $bookingdatasort_canc["ride_img"]);
                        $ride_image = SITE_URL . "img/ride_imgs/" . array_pop($ride_filename);
                        $booking_title = str_pad($bookingdatasort_canc["booking_id"], 5, "0", STR_PAD_LEFT);
                        $booking_cancelled .= "<ons-list-item data-ridedesc='" . $bookingdatasort_canc["ride_desc"] . "'  data-driverphone='" . $bookingdatasort_canc["driver_phone"] . "' data-ptype='" . $booking_payment_type . "' data-put='" . $booking_pdate_time . "' data-driverimg='" . $drvr_photo_file . "' data-rideimg='" . $ride_image . "' data-drivername='" . $booking_driver . "' data-cost='" . $bookingdatasort_canc["estimated_cost"] . "' data-ride='" . $bookingdatasort_canc["ride_type"] . "' data-pul='" . $bookingdatasort_canc["pickup_address"] . "' data-dol='" . $bookingdatasort_canc["dropoff_address"] . "' data-btitle='" . $booking_title . "' id='booking-list-item-" . $bookingdatasort_canc["booking_id"] . "' modifier='longdivider'>\r\n                \r\n                                            <div class='center'>\r\n                                                <div style='width:100%'><span class='list-item__title'>" . $booking_time . "</span> | <span onclick='showbookingdetails(" . $bookingdatasort_canc["booking_id"] . ")' style='color:skyblue'>View details</span></div>\r\n                                                <span class='list-item__subtitle'><span style='color:yellow'>Booking ID:</span> " . $booking_title . "</span>\r\n                                                <span class='list-item__subtitle'><span style='color:lightgreen'>Pick-up:</span> " . $bookingdatasort_canc["pickup_address"] . "</span>\r\n                                                <span class='list-item__subtitle'><span style='color:orange'>Drop-off:</span> " . $bookingdatasort_canc["dropoff_address"] . "</span>\r\n                                                <span class='list-item__subtitle'><span style='color:cyan'>Driver: </span>" . $booking_driver . "</span>\r\n                                            </div>\r\n                                        \r\n                                        </ons-list-item>";
                    }
                }
            }
            $data_array = ["success" => 1, "pend_onride" => $booking_pend_onride, "booking_comp" => $booking_completed, "booking_canc" => $booking_cancelled];
            echo json_encode($data_array);
            exit;
        } else {
            $error = ["error" => "You do not have any booking records."];
            echo json_encode($error);
            exit;
        }
    } else {
        $error = ["error" => "Error retrieving booking records."];
        echo json_encode($error);
        exit;
    }
}
function setDriverLocation()
{
    if(empty($_SESSION["loggedin"])) {
        $error = ["error" => "Please re-login and retry."];
        echo json_encode($error);
        exit;
    }
    $time_online = gettodaytimeonline();
    $hours = floor($time_online / 3600);
    $minutes = floor($time_online % 3600 / 60);
    $driver_time_online_formated = "";
    if(!empty($hours)) {
        $driver_time_online_formated = $hours . "H ";
    }
    if(!empty($minutes)) {
        $driver_time_online_formated .= $minutes . "M ";
    } else {
        $driver_time_online_formated .= "0M ";
    }
    $query = sprintf("SELECT * FROM %stbl_driver_location WHERE driver_id = \"%d\"", DB_TBL_PREFIX, $_SESSION["uid"]);
    $result = mysqli_query($GLOBALS["DB"], $query);
    if(!mysqli_num_rows($result)) {
        $query = sprintf("INSERT INTO %stbl_driver_location (`long`,`lat`,`driver_id`,`location_date`)  VALUES(\"%s\",\"%s\",\"%d\",\"%s\")", DB_TBL_PREFIX, $_POST["long"], $_POST["lat"], $_SESSION["uid"], gmdate("Y-m-d H:i:s", time()));
        if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
            $feedback = ["feedback" => "Insert Database error."];
            echo json_encode($feedback);
            exit;
        }
        $feedback = ["feedback" => "Location update OK."];
        echo json_encode($feedback);
        exit;
    }
    $query = sprintf("UPDATE %stbl_driver_location SET `long` = \"%s\", `lat` = \"%s\", `location_date`= \"%s\" WHERE `driver_id` = \"%d\"", DB_TBL_PREFIX, $_POST["long"], $_POST["lat"], gmdate("Y-m-d H:i:s", time()), $_SESSION["uid"]);
    if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
        $feedback = ["feedback" => "Update Database error."];
        echo json_encode($feedback);
        exit;
    }
    $feedback = ["feedback" => "Location update OK.", "driver_time_online" => $driver_time_online_formated];
    echo json_encode($feedback);
    exit;
}
function syncservertime()
{
    $server_time = round(microtime(true) * 1000);
    $data = ["success" => 1, "server_time" => $server_time];
    echo json_encode($data);
    exit;
}
function getEarnings()
{
    if(empty($_SESSION["loggedin"])) {
        $error = ["error" => __("Please login to continue")];
        echo json_encode($error);
        exit;
    }
    $template = "";
    $earnings_data = [];
    $earnings_date = "";
    $end_date = "";
    $driver_all_day_earning = 0;
    $driver_city_currency_symbol = "";
    if(!empty($_POST["date"])) {
        if(!isValidDate($_POST["date"], $format = "Y-m-d")) {
            $error = ["error" => __("An error has occured")];
            echo json_encode($error);
            exit;
        }
        $e_date = mysqli_real_escape_string($GLOBALS["DB"], $_POST["date"]);
        $earnings_date = $e_date;
    } else {
        $earnings_date = "2019-01-21";
    }
    $query = sprintf("SELECT *,%1\$stbl_users.firstname AS user_firstname,%1\$stbl_users.photo_file AS user_photo_file,%1\$stbl_bookings.id AS book_id,%1\$stbl_bookings.driver_commision AS driver_com,DATE(%1\$stbl_bookings.date_completed) AS completed_date FROM %1\$stbl_bookings\r\n    LEFT JOIN %1\$stbl_rides ON %1\$stbl_rides.id = %1\$stbl_bookings.ride_id\r\n    LEFT JOIN %1\$stbl_users ON %1\$stbl_users.user_id = %1\$stbl_bookings.user_id\r\n    INNER JOIN %1\$stbl_drivers ON %1\$stbl_drivers.driver_id = %1\$stbl_bookings.driver_id\r\n    LEFT JOIN %1\$stbl_routes ON %1\$stbl_routes.id = %1\$stbl_drivers.route_id\r\n    LEFT JOIN %1\$stbl_currencies ON %1\$stbl_currencies.id = %1\$stbl_routes.city_currency_id\r\n    WHERE %1\$stbl_bookings.driver_id = %2\$d AND %1\$stbl_bookings.status = 3 AND DATE(%1\$stbl_bookings.date_completed) = \"%3\$s\" ORDER BY %1\$stbl_bookings.date_completed DESC", DB_TBL_PREFIX, $_SESSION["uid"], $earnings_date);
    if(($result = mysqli_query($GLOBALS["DB"], $query)) && mysqli_num_rows($result)) {
        while ($row = mysqli_fetch_assoc($result)) {
            $earnings_data[] = $row;
        }
    }
    $earnings_data_sort = [];
    foreach ($earnings_data as $earningsdata) {
        $earnings_data_sort[$earningsdata["completed_date"]]["date"] = $earningsdata["date_completed"];
        $earnings_data_sort[$earningsdata["completed_date"]]["data"][] = $earningsdata;
    }
    foreach ($earnings_data_sort as $earningsdatasort) {
        if(!empty($earningsdatasort["data"])) {
            $t_date_format = date("l, M j, Y H:i:s", strtotime($earningsdatasort["date"] . " UTC"));
            foreach ($earningsdatasort["data"] as $transaction_d) {
                $transaction_time = date("g:i A", strtotime($transaction_d["date_completed"] . " UTC"));
                $driver_city_currency_symbol = $transaction_d["symbol"];
                $driver_earning = $transaction_d["actual_cost"] * $transaction_d["driver_com"] / 100;
                $driver_earning = round($driver_earning, 2);
                $deficit = $transaction_d["paid_amount"] - $transaction_d["actual_cost"];
                $company_commision = $transaction_d["actual_cost"] - $driver_earning + $deficit;
                $company_commision = round($company_commision, 2);
                $driver_all_day_earning += $driver_earning * $transaction_d["exchng_rate"];
                $booking_id = !empty($transaction_d["book_id"]) ? "#" . str_pad($transaction_d["book_id"], 5, "0", STR_PAD_LEFT) : "N/A";
                $booking_payment_type = "";
                if(!empty($transaction_d["payment_type"])) {
                    if($transaction_d["payment_type"] == 1) {
                        $booking_payment_type = "Cash";
                    } elseif($transaction_d["payment_type"] == 2) {
                        $booking_payment_type = "Wallet";
                    } else {
                        $booking_payment_type = "Card";
                    }
                }
                $booking_pdate_time = date("l, M j, Y g:i A", strtotime($transaction_d["pickup_datetime"] . " UTC"));
                $booking_type = $transaction_d["scheduled"] == 1 ? "Schedule ride" : "Instant ride";
                $user_photo_file = isset($transaction_d["user_photo_file"]) ? SITE_URL . "ajaxuserphotofile.php?file=" . $transaction_d["user_photo_file"] : SITE_URL . "ajaxuserphotofile.php?file=0";
                $ride_filename = explode("/", $transaction_d["ride_img"]);
                $ride_image = SITE_URL . "img/ride_imgs/" . array_pop($ride_filename);
                $ride_duration = "0 Secs";
                $distance_travelled = !empty($transaction_d["dist_unit"]) ? round($transaction_d["distance_travelled"] * 0, 2) . " mi" : round($transaction_d["distance_travelled"] * 0, 2) . " KM";
                if(!empty($transaction_d["date_started"]) && !empty($transaction_d["date_completed"])) {
                    $ride_start_time = strtotime($transaction_d["date_started"]);
                    $ride_end_time = strtotime($transaction_d["date_completed"]);
                    $ride_duration_secs = $ride_end_time - $ride_start_time;
                    if($ride_duration_secs) {
                        $hours = floor($ride_duration_secs / 3600);
                        $minutes = floor($ride_duration_secs % 3600 / 60);
                        $seconds = $ride_duration_secs % 3600 % 60;
                        $ride_duration = "";
                        if(!empty($hours)) {
                            $ride_duration = $hours . "H ";
                        }
                        if(!empty($minutes)) {
                            $ride_duration .= $minutes . "M ";
                        }
                        if(!empty($seconds)) {
                            $ride_duration .= $seconds . "S";
                        }
                    }
                }
                $item_data = [];
                $item_data = ["car_desc" => $transaction_d["ride_desc"], "user_firstname" => $transaction_d["user_firstname"], "user_rating" => $transaction_d["user_rating"], "payment_type" => $booking_payment_type, "pick_up_time" => $booking_pdate_time, "user_image" => $user_photo_file, "car_image" => $ride_image, "driver_earning" => $transaction_d["cur_symbol"] . $driver_earning, "booking_cost" => $transaction_d["cur_symbol"] . $transaction_d["actual_cost"], "car_type" => $transaction_d["ride_type"], "p_location" => $transaction_d["pickup_address"], "d_location" => $transaction_d["dropoff_address"], "booking_id" => $booking_id, "booking_type" => $booking_type, "booking_status" => $transaction_d["status"], "coupon_code" => $transaction_d["coupon_code"], "distance_travelled" => $distance_travelled, "paid_amount" => $transaction_d["cur_symbol"] . $transaction_d["paid_amount"], "ride_duration" => $ride_duration];
                $item_data_json = json_encode($item_data);
                $template .= "<ons-list-item onclick='showbookingdetails(" . $transaction_d["book_id"] . ")' modifier='longdivider'>\r\n                        \r\n                                    <div class='center'>\r\n                                        <div style='width:100%;margin-bottom:10px;'><span class='list-item__title'>" . $transaction_time . "</span> </div>\r\n                                        <span class='list-item__subtitle'><span style='color: #000;font-size: 20px;margin-bottom:5px;'>" . __("Earning") . ": " . $transaction_d["cur_symbol"] . $driver_earning . "</span></span>\r\n                                        <span class='list-item__subtitle'><ons-icon icon='fa-square' size='8px' style='vertical-align: bottom; color: rgb(24, 103, 194); font-size: 8px;' modifier='material' class='ons-icon fa-square fa'></ons-icon> <span style='color:#777'>" . __("Booking ID") . ":</span> " . $booking_id . " </span>\r\n                                        <span class='list-item__subtitle'><ons-icon icon='fa-square' size='8px' style='vertical-align: bottom; color: rgb(24, 103, 194); font-size: 8px;' modifier='material' class='ons-icon fa-square fa'></ons-icon> <span style='color:#777'>" . __("Booking Fare") . ":</span> " . $transaction_d["paid_amount"] . " </span>\r\n                                        <span class='list-item__subtitle'><ons-icon icon='fa-square' size='8px' style='vertical-align: bottom; color: rgb(24, 103, 194); font-size: 8px;' modifier='material' class='ons-icon fa-square fa'></ons-icon> <span style='color:#777'>" . __("Company commision") . ":</span> " . $transaction_d["cur_symbol"] . $company_commision . "</span>\r\n                                        <span id='booking-list-item-data-" . $transaction_d["book_id"] . "' type='text' style='display:none'>" . $item_data_json . "</span>\r\n                                    </div>\r\n                                \r\n                                </ons-list-item>";
            }
        }
    }
    $formatted_all_day_earning = "";
    if(empty($earnings_data)) {
        $template = "<p style='color:grey;text-align:center;position:absolute;top:50%;width:100%;'>" . __("No record available") . "</p>";
    } else {
        $formatted_all_day_earning = $driver_city_currency_symbol . floattocurrency($driver_all_day_earning);
    }
    $data_array = ["success" => 1, "earnings_data" => $template, "all_day_earning" => $formatted_all_day_earning];
    echo json_encode($data_array);
    exit;
}
function getDriverHistory()
{
    if(empty($_SESSION["loggedin"])) {
        $error = ["error" => __("Please login to continue")];
        echo json_encode($error);
        exit;
    }
    $booking_completed = [];
    $booking_cancelled = [];
    $booking_pending = [];
    $query = sprintf("SELECT *, DATE(%1\$stbl_bookings.date_created) AS created_date,%1\$stbl_bookings.id AS booking_id FROM %1\$stbl_bookings \r\n    LEFT JOIN %1\$stbl_rides ON %1\$stbl_rides.id = %1\$stbl_bookings.ride_id\r\n    LEFT JOIN %1\$stbl_users ON %1\$stbl_users.user_id = %1\$stbl_bookings.user_id\r\n    WHERE %1\$stbl_bookings.driver_id = %2\$s AND (%1\$stbl_bookings.status = 0 OR %1\$stbl_bookings.status = 1 OR %1\$stbl_bookings.status = 2 OR %1\$stbl_bookings.status = 3 OR %1\$stbl_bookings.status = 4 OR %1\$stbl_bookings.status = 5) \r\n    ORDER BY %1\$stbl_bookings.date_created DESC LIMIT 0,500 ", DB_TBL_PREFIX, $_SESSION["uid"]);
    if($result = mysqli_query($GLOBALS["DB"], $query)) {
        if(mysqli_num_rows($result)) {
            while ($row = mysqli_fetch_assoc($result)) {
                if($row["status"] == 0 || $row["status"] == 1) {
                    $booking_pending[$row["created_date"]]["date"] = $row["created_date"];
                    $booking_pending[$row["created_date"]]["pending"][] = $row;
                }
                if($row["status"] == 2 || $row["status"] == 4 || $row["status"] == 5) {
                    $booking_cancelled[$row["created_date"]]["date"] = $row["created_date"];
                    $booking_cancelled[$row["created_date"]]["cancelled"][] = $row;
                }
                if($row["status"] == 3) {
                    $booking_completed[$row["created_date"]]["date"] = $row["created_date"];
                    $booking_completed[$row["created_date"]]["completed"][] = $row;
                }
            }
            mysqli_free_result($result);
            $booking_complete_str = "";
            $booking_cancel_str = "";
            $booking_pending_str = "";
            foreach ($booking_pending as $bookingdatasort) {
                $b_date_format = date("l, M j, Y", strtotime($bookingdatasort["date"] . " UTC"));
                $booking_cancel_str .= "<ons-list-header style='border-top: thin solid grey;border-bottom: thin solid grey;font-size: 14px;font-weight:bold;'>" . $b_date_format . "</ons-list-header>";
                foreach ($bookingdatasort["pending"] as $bookingdatasort_canc) {
                    $booking_time = date("g:i A", strtotime($bookingdatasort_canc["date_created"] . " UTC"));
                    $booking_ptime = date("g:i A", strtotime($bookingdatasort_canc["pickup_datetime"] . " UTC"));
                    $booking_pdate_time = date("l, M j, Y g:i A", strtotime($bookingdatasort_canc["pickup_datetime"] . " UTC"));
                    $booking_payment_type = "";
                    $status = "";
                    $close_btn = "";
                    if($bookingdatasort_canc["status"] == 0) {
                        $status = "<span style='color: #ef6c00;font-weight: bold;border: thin solid #ef6c00;padding: 3px 5px;font-size: 12px;'>" . __("Booking accepted") . "</span>";
                        $close_btn = "<span style='display:inline-block;float:right'><ons-icon onclick = 'event.stopPropagation();drivercancel(" . $bookingdatasort_canc["booking_id"] . ")' icon='fa-times-circle' size='20px' style='color:red'></ons-icon></span>";
                    } else {
                        $status = "<span style='color: #43a047;font-weight: bold;border: thin solid #43a047;padding: 3px 5px;font-size: 12px;'>" . __("Servicing booking") . "</span>";
                        $close_btn = "<span style='display:inline-block;float:right'><ons-icon onclick = 'event.stopPropagation();drivercancel(" . $bookingdatasort_canc["booking_id"] . ")' icon='fa-times-circle' size='20px' style='color:red'></ons-icon></span>";
                    }
                    if(!empty($bookingdatasort_canc["payment_type"])) {
                        if($bookingdatasort_canc["payment_type"] == 1) {
                            $booking_payment_type = __("Cash");
                        } elseif($bookingdatasort_canc["payment_type"] == 2) {
                            $booking_payment_type = __("Wallet");
                        } else {
                            $booking_payment_type = "Card";
                        }
                    }
                    $booking_type = $bookingdatasort_canc["scheduled"] == 1 ? "Schedule ride" : "Instant ride";
                    $user_photo_file = isset($bookingdatasort_canc["photo_file"]) ? SITE_URL . "ajaxuserphotofile.php?file=" . $bookingdatasort_canc["photo_file"] : SITE_URL . "ajaxuserphotofile.php?file=0";
                    $ride_filename = explode("/", $bookingdatasort_canc["ride_img"]);
                    $ride_image = SITE_URL . "img/ride_imgs/" . array_pop($ride_filename);
                    $booking_title = str_pad($bookingdatasort_canc["booking_id"], 5, "0", STR_PAD_LEFT);
                    $item_data = [];
                    $item_data = ["car_desc" => $bookingdatasort_canc["ride_desc"], "user_firstname" => $bookingdatasort_canc["firstname"], "user_rating" => $bookingdatasort_canc["user_rating"], "payment_type" => $booking_payment_type, "pick_up_time" => $booking_pdate_time, "user_image" => $user_photo_file, "car_image" => $ride_image, "booking_cost" => $bookingdatasort_canc["cur_symbol"] . $bookingdatasort_canc["estimated_cost"], "car_type" => $bookingdatasort_canc["ride_type"], "p_location" => $bookingdatasort_canc["pickup_address"], "d_location" => $bookingdatasort_canc["dropoff_address"], "booking_id" => $booking_title, "booking_type" => $booking_type, "booking_status" => $bookingdatasort_canc["status"], "coupon_code" => $bookingdatasort_canc["coupon_code"]];
                    $item_data_json = json_encode($item_data);
                    $booking_pending_str .= "<ons-list-item onclick='showbookingdetails(" . $bookingdatasort_canc["booking_id"] . ")' id='booking-list-item-" . $bookingdatasort_canc["booking_id"] . "' modifier='longdivider'>\r\n            \r\n                                            <div class='center'>\r\n                                                <div style='width:100%;'><span class='list-item__title'>" . $booking_time . " </span> " . $status . " " . $close_btn . "</div>\r\n                                                <span style='text-align: left;margin-bottom: 15px;' class='list-item__subtitle'><span>Booking ID:#" . $booking_title . "</span></span>                               \r\n                                                <span class='list-item__subtitle' style='margin-bottom:5px;'><ons-icon icon='fa-circle' size='14px' style='color: #24c539; font-size: 14px;position: absolute;'></ons-icon> <span style='display:inline-block;margin-left:22px;font-weight:bold;'>" . $bookingdatasort_canc["pickup_address"] . "</span></span>\r\n                                                <span class='list-item__subtitle'><ons-icon icon='fa-map-marker' size='14px' style='color: red; font-size: 14px;position: absolute;'></ons-icon> <span style='display:inline-block;margin-left:22px;font-weight:bold;'>" . $bookingdatasort_canc["dropoff_address"] . "</span></span>\r\n                                                <span id='booking-list-item-data-" . $bookingdatasort_canc["booking_id"] . "' type='text' style='display:none'>" . $item_data_json . "</span>\r\n                                                <span class='list-item__subtitle' id='resume-bk-" . $bookingdatasort_canc["booking_id"] . "' style='display:none;'><ons-button style='width:100%' onclick='event.stopPropagation();resumeBooking(" . $bookingdatasort_canc["booking_id"] . ")'> Resume </ons-button></span>\r\n                                            </div>\r\n                                            \r\n                                                                               \r\n                                    </ons-list-item>";
                }
            }
            foreach ($booking_cancelled as $bookingdatasort) {
                $b_date_format = date("l, M j, Y", strtotime($bookingdatasort["date"] . " UTC"));
                $booking_cancel_str .= "<ons-list-header style='border-top: thin solid grey;border-bottom: thin solid grey;font-size: 14px;font-weight:bold;'>" . $b_date_format . "</ons-list-header>";
                foreach ($bookingdatasort["cancelled"] as $bookingdatasort_canc) {
                    $booking_time = date("g:i A", strtotime($bookingdatasort_canc["date_created"] . " UTC"));
                    $booking_ptime = date("g:i A", strtotime($bookingdatasort_canc["pickup_datetime"] . " UTC"));
                    $booking_pdate_time = date("l, M j, Y g:i A", strtotime($bookingdatasort_canc["pickup_datetime"] . " UTC"));
                    $booking_payment_type = "";
                    $status = "";
                    if($bookingdatasort_canc["status"] == 2) {
                        $status = "<span style='color: #e53935;font-weight: bold;border: thin solid #e53935;padding: 3px 5px;font-size: 12px;'>" . __("Cancelled by rider") . "</span>";
                    } elseif($bookingdatasort_canc["status"] == 4) {
                        $status = "<span style='color: #e53935;font-weight: bold;border: thin solid #e53935;padding: 3px 5px;font-size: 12px;'>" . __("Cancelled by driver") . "</span>";
                    } else {
                        $status = "<span style='color: #e53935;font-weight: bold;border: thin solid #e53935;padding: 3px 5px;font-size: 12px;'>" . __("Cancelled by Admin") . "</span>";
                    }
                    if(!empty($bookingdatasort_canc["payment_type"])) {
                        if($bookingdatasort_canc["payment_type"] == 1) {
                            $booking_payment_type = __("Cash");
                        } elseif($bookingdatasort_canc["payment_type"] == 2) {
                            $booking_payment_type = __("Wallet");
                        } else {
                            $booking_payment_type = "Card";
                        }
                    }
                    $booking_type = $bookingdatasort_canc["scheduled"] == 1 ? "Schedule ride" : "Instant ride";
                    $user_photo_file = isset($bookingdatasort_canc["photo_file"]) ? SITE_URL . "ajaxuserphotofile.php?file=" . $bookingdatasort_canc["photo_file"] : SITE_URL . "ajaxuserphotofile.php?file=0";
                    $ride_filename = explode("/", $bookingdatasort_canc["ride_img"]);
                    $ride_image = SITE_URL . "img/ride_imgs/" . array_pop($ride_filename);
                    $booking_title = str_pad($bookingdatasort_canc["booking_id"], 5, "0", STR_PAD_LEFT);
                    $item_data = [];
                    $item_data = ["car_desc" => $bookingdatasort_canc["ride_desc"], "user_firstname" => $bookingdatasort_canc["firstname"], "user_rating" => $bookingdatasort_canc["user_rating"], "payment_type" => $booking_payment_type, "pick_up_time" => $booking_pdate_time, "user_image" => $user_photo_file, "car_image" => $ride_image, "booking_cost" => $bookingdatasort_canc["cur_symbol"] . $bookingdatasort_canc["estimated_cost"], "car_type" => $bookingdatasort_canc["ride_type"], "p_location" => $bookingdatasort_canc["pickup_address"], "d_location" => $bookingdatasort_canc["dropoff_address"], "booking_id" => $booking_title, "booking_type" => $booking_type, "booking_status" => $bookingdatasort_canc["status"], "coupon_code" => $bookingdatasort_canc["coupon_code"]];
                    $item_data_json = json_encode($item_data);
                    $booking_cancel_str .= "<ons-list-item onclick='showbookingdetails(" . $bookingdatasort_canc["booking_id"] . ")' id='booking-list-item-" . $bookingdatasort_canc["booking_id"] . "' modifier='longdivider'>\r\n            \r\n                                        <div class='center'>\r\n                                            <div style='width:100%;'><span class='list-item__title'>" . $booking_time . " </span> " . $status . "</div>\r\n                                                <span style='text-align: left;margin-bottom: 15px;' class='list-item__subtitle'><span>Booking ID:#" . $booking_title . "</span></span>                               \r\n                                                <span class='list-item__subtitle' style='margin-bottom:5px;'><ons-icon icon='fa-circle' size='14px' style='color: #24c539; font-size: 14px;position: absolute;'></ons-icon> <span style='display:inline-block;margin-left:22px;font-weight:bold;'>" . $bookingdatasort_canc["pickup_address"] . "</span></span>\r\n                                                <span class='list-item__subtitle'><ons-icon icon='fa-map-marker' size='14px' style='color: red; font-size: 14px;position: absolute;'></ons-icon> <span style='display:inline-block;margin-left:22px;font-weight:bold;'>" . $bookingdatasort_canc["dropoff_address"] . "</span></span>\r\n                                                <span id='booking-list-item-data-" . $bookingdatasort_canc["booking_id"] . "' type='text' style='display:none'>" . $item_data_json . "</span>\r\n                                            </div>\r\n                                        </div>\r\n                                    </ons-list-item>";
                }
            }
            foreach ($booking_completed as $bookingdatasort) {
                $b_date_format = date("l, M j, Y", strtotime($bookingdatasort["date"] . " UTC"));
                $booking_complete_str .= "<ons-list-header>" . $b_date_format . "</ons-list-header>";
                foreach ($bookingdatasort["completed"] as $bookingdatasort_canc) {
                    $booking_time = date("g:i A", strtotime($bookingdatasort_canc["date_created"] . " UTC"));
                    $booking_ptime = date("g:i A", strtotime($bookingdatasort_canc["pickup_datetime"] . " UTC"));
                    $booking_pdate_time = date("l, M j, Y g:i A", strtotime($bookingdatasort_canc["pickup_datetime"] . " UTC"));
                    $booking_payment_type = "";
                    if(!empty($bookingdatasort_canc["payment_type"])) {
                        if($bookingdatasort_canc["payment_type"] == 2) {
                            $booking_payment_type = __("Wallet");
                        }
                        if($bookingdatasort_canc["payment_type"] == 1) {
                            $booking_payment_type = __("Cash");
                        }
                        if($bookingdatasort_canc["payment_type"] == 4) {
                            $booking_payment_type = "POS";
                        }
                    }
                    $distance_travelled = !empty($_SESSION["driver_city_dist_unit"]) ? round($bookingdatasort_canc["distance_travelled"] * 0, 2) . " mi" : round($bookingdatasort_canc["distance_travelled"] * 0, 2) . " KM";
                    $booking_type = $bookingdatasort_canc["scheduled"] == 1 ? "Schedule ride" : "Instant ride";
                    $user_photo_file = isset($bookingdatasort_canc["photo_file"]) ? SITE_URL . "ajaxuserphotofile.php?file=" . $bookingdatasort_canc["photo_file"] : SITE_URL . "ajaxuserphotofile.php?file=0";
                    $ride_filename = explode("/", $bookingdatasort_canc["ride_img"]);
                    $ride_image = SITE_URL . "img/ride_imgs/" . array_pop($ride_filename);
                    $booking_title = str_pad($bookingdatasort_canc["booking_id"], 5, "0", STR_PAD_LEFT);
                    $ride_duration = "0 Secs";
                    if(!empty($bookingdatasort_canc["date_started"]) && !empty($bookingdatasort_canc["date_completed"])) {
                        $ride_start_time = strtotime($bookingdatasort_canc["date_started"] . " UTC");
                        $ride_end_time = strtotime($bookingdatasort_canc["date_completed"] . " UTC");
                        $ride_duration_secs = $ride_end_time - $ride_start_time;
                        if($ride_duration_secs) {
                            $hours = floor($ride_duration_secs / 3600);
                            $minutes = floor($ride_duration_secs % 3600 / 60);
                            $seconds = $ride_duration_secs % 3600 % 60;
                            $ride_duration = "";
                            if(!empty($hours)) {
                                $ride_duration = $hours . "H ";
                            }
                            if(!empty($minutes)) {
                                $ride_duration .= $minutes . "M ";
                            }
                            if(!empty($seconds)) {
                                $ride_duration .= $seconds . "S";
                            }
                        }
                    }
                    $item_data = [];
                    $item_data = ["car_desc" => $bookingdatasort_canc["ride_desc"], "user_firstname" => $bookingdatasort_canc["firstname"], "user_rating" => $bookingdatasort_canc["user_rating"], "payment_type" => $booking_payment_type, "pick_up_time" => $booking_pdate_time, "user_image" => $user_photo_file, "car_image" => $ride_image, "booking_cost" => $bookingdatasort_canc["cur_symbol"] . $bookingdatasort_canc["estimated_cost"], "car_type" => $bookingdatasort_canc["ride_type"], "p_location" => $bookingdatasort_canc["pickup_address"], "d_location" => $bookingdatasort_canc["dropoff_address"], "booking_id" => $booking_title, "booking_type" => $booking_type, "booking_status" => $bookingdatasort_canc["status"], "coupon_code" => $bookingdatasort_canc["coupon_code"], "distance_travelled" => $distance_travelled, "paid_amount" => $bookingdatasort_canc["cur_symbol"] . $bookingdatasort_canc["paid_amount"], "ride_duration" => $ride_duration];
                    $item_data_json = json_encode($item_data);
                    $booking_fare = $bookingdatasort_canc["cur_symbol"] . $bookingdatasort_canc["paid_amount"];
                    $booking_title = str_pad($bookingdatasort_canc["booking_id"], 5, "0", STR_PAD_LEFT);
                    $booking_complete_str .= "<ons-list-item onclick='showbookingdetails(" . $bookingdatasort_canc["booking_id"] . ")' id='booking-list-item-" . $bookingdatasort_canc["booking_id"] . "' modifier='longdivider'>\r\n            \r\n                                            <div class='center'>\r\n                                                <div style='width:100%;'><span class='list-item__title'>" . $booking_time . " </span> <span style='color: #1976d2;border-radius:5px;font-weight: bold;border: thin solid #1976d2;padding: 3px 5px;font-size: 12px;'>" . __("Completed") . "</span></div>\r\n                                                    <span style='text-align: left;margin-bottom: 15px;' class='list-item__subtitle'><span>Booking ID:#" . $booking_title . "</span> | <span>Fare:" . $booking_fare . "</span></span>                               \r\n                                                    <span class='list-item__subtitle' style='margin-bottom:5px;'><ons-icon icon='fa-circle' size='14px' style='color: #24c539; font-size: 14px;position: absolute;'></ons-icon> <span style='display:inline-block;margin-left:22px;font-weight:bold;'>" . $bookingdatasort_canc["pickup_address"] . "</span></span>\r\n                                                    <span class='list-item__subtitle'><ons-icon icon='fa-map-marker' size='14px' style='color: red; font-size: 14px;position: absolute;'></ons-icon> <span style='display:inline-block;margin-left:22px;font-weight:bold;'>" . $bookingdatasort_canc["dropoff_address"] . "</span></span>\r\n                                                    <span id='booking-list-item-data-" . $bookingdatasort_canc["booking_id"] . "' type='text' style='display:none'>" . $item_data_json . "</span>\r\n                                                </div>\r\n                                            </div>\r\n                                        </ons-list-item>";
                }
            }
            $data_array = ["success" => 1, "booking_comp" => $booking_complete_str, "booking_canc" => $booking_cancel_str, "pend_onride" => $booking_pending_str];
            echo json_encode($data_array);
            exit;
        } else {
            $error = ["error" => __("You do not have any booking records")];
            echo json_encode($error);
            exit;
        }
    } else {
        $error = ["error" => __("An error has occured")];
        echo json_encode($error);
        exit;
    }
}
function drivercompleted()
{
    if(empty($_SESSION["loggedin"])) {
        $error = ["error" => __("Please login to continue")];
        echo json_encode($error);
        exit;
    }
    $booking_id = (int) $_GET["bookingid"];
    $complete_code = $_GET["complete_code"];
    $distance_travelled = (double) $_GET["ride_distance"];
    $distance_travelled = $distance_travelled * 1000;
    $ride_duration_secs = (int) $_GET["ride_duration_secs"];
    $ride_duration_formated = $_GET["ride_duration_secs_formated"];
    $booking_data = [];
    $query = sprintf("SELECT *,%1\$stbl_bookings.route_id AS booking_route,%1\$stbl_users.referral_discounts_count AS user_referral_discount_count,%1\$stbl_bookings.franchise_commision AS franchise_commision,%1\$stbl_bookings.driver_commision AS driver_commision,%1\$stbl_drivers.wallet_amount AS driver_wallet,%1\$stbl_drivers.firstname AS driver_firstname,%1\$stbl_drivers.photo_file AS driver_photo,%1\$stbl_users.push_notification_token AS push_token,%1\$stbl_users.wallet_amount AS user_wallet,%1\$stbl_users.reward_points_redeemed,%1\$stbl_users.reward_points AS user_reward_points,estimated_cost,payment_type,%1\$stbl_bookings.user_id AS customer_id,%1\$stbl_bookings.completion_code,%1\$stbl_bookings.status,%1\$stbl_users.disp_lang AS u_lang FROM %1\$stbl_bookings \r\n    INNER JOIN %1\$stbl_drivers ON %1\$stbl_drivers.driver_id = %1\$stbl_bookings.driver_id\r\n    INNER JOIN %1\$stbl_users ON %1\$stbl_users.user_id = %1\$stbl_bookings.user_id\r\n    INNER JOIN %1\$stbl_franchise ON %1\$stbl_franchise.id = %1\$stbl_drivers.franchise_id\r\n    WHERE %1\$stbl_bookings.id = \"%2\$d\"", DB_TBL_PREFIX, $booking_id);
    if($result = mysqli_query($GLOBALS["DB"], $query)) {
        if(mysqli_num_rows($result)) {
            $booking_data = mysqli_fetch_assoc($result);
            if($booking_data["status"] != 1) {
                $error = ["error" => __("An error has occured")];
                echo json_encode($error);
                exit;
            }
            if($booking_data["completion_code"] != $complete_code) {
                $error = ["error" => __("Invalid ride completion code")];
                echo json_encode($error);
                exit;
            }
            mysqli_query($GLOBALS["DB"], "START TRANSACTION");
            $ride_fare = !empty($_GET["ride_fare"]) ? (double) $_GET["ride_fare"] : $booking_data["estimated_cost"];
            $city_currency_exchange = (double) $booking_data["cur_exchng_rate"];
            $city_currency_symbol = $booking_data["cur_symbol"];
            $city_currency_code = $booking_data["cur_code"];
            $ride_date_completed = gmdate("Y-m-d H:i:s", strtotime($booking_data["date_started"] . " UTC") + $ride_duration_secs);
            $amount_paid_by_rider = (double) $_GET["amount_paid_by_rider"];
            $rlang = $booking_data["u_lang"];
            $referral_used = !empty($_GET["referral_used"]) ? 1 : 0;
            $referral_discount_value = (double) $_GET["referral_discount_value"];
            $coupon_used = !empty($_GET["coupon_code"]) ? 1 : 0;
            $coupon_discount_type = !empty($_GET["coupon_discount_type"]) ? 1 : 0;
            $coupon_discount_value = (double) $_GET["coupon_discount_value"];
            if($referral_used && 0 < $booking_data["user_referral_discount_count"]) {
                $query = sprintf("UPDATE %stbl_users SET referral_discounts_count = referral_discounts_count - 1 WHERE `user_id` = %d", DB_TBL_PREFIX, $booking_data["customer_id"]);
                if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
                    mysqli_query($GLOBALS["DB"], "ROLLBACK");
                    $error = ["error" => __("An error has occured")];
                    echo json_encode($error);
                    exit;
                }
            }
            $query = sprintf("UPDATE %stbl_bookings SET actual_cost = \"%s\",`status` = 3, date_completed = \"%s\", distance_travelled = \"%s\", paid_amount = \"%s\" WHERE id = \"%d\" AND `status` = 1", DB_TBL_PREFIX, $ride_fare, $ride_date_completed, $distance_travelled, $amount_paid_by_rider, $booking_id);
            if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
                mysqli_query($GLOBALS["DB"], "ROLLBACK");
                $error = ["error" => __("An error has occured")];
                echo json_encode($error);
                exit;
            }
            $query = sprintf("UPDATE %stbl_driver_allocate SET `status` = %d WHERE booking_id = %d AND driver_id = %d", DB_TBL_PREFIX, 4, $booking_id, $_SESSION["uid"]);
            $result = mysqli_query($GLOBALS["DB"], $query);
            $query = sprintf("UPDATE %stbl_drivers SET completed_rides = completed_rides + 1 WHERE driver_id = %d", DB_TBL_PREFIX, $_SESSION["uid"]);
            $result = mysqli_query($GLOBALS["DB"], $query);
            $query = sprintf("UPDATE %stbl_users SET completed_rides = completed_rides + 1 WHERE `user_id` = %d", DB_TBL_PREFIX, $booking_data["customer_id"]);
            $result = mysqli_query($GLOBALS["DB"], $query);
            if($coupon_used) {
                $query = sprintf("SELECT %1\$stbl_coupon_codes.id AS coupon_id,%1\$stbl_coupons_used.times_used FROM %1\$stbl_coupon_codes \r\n        LEFT JOIN %1\$stbl_coupons_used ON %1\$stbl_coupons_used.coupon_id = %1\$stbl_coupon_codes.id AND %1\$stbl_coupons_used.user_id = %4\$d\r\n        WHERE %1\$stbl_coupon_codes.coupon_code = \"%2\$s\" AND %1\$stbl_coupon_codes.city = %3\$d", DB_TBL_PREFIX, $booking_data["coupon_code"], $booking_data["booking_route"], $booking_data["customer_id"]);
                if(($result = mysqli_query($GLOBALS["DB"], $query)) && mysqli_num_rows($result)) {
                    $row = mysqli_fetch_assoc($result);
                    if($row["times_used"] == NULL) {
                        $query = sprintf("INSERT INTO %stbl_coupons_used (coupon_id,`user_id`,times_used) VALUES (\"%d\",\"%d\",\"%d\")", DB_TBL_PREFIX, $row["coupon_id"], $booking_data["customer_id"], 1);
                        $result = mysqli_query($GLOBALS["DB"], $query);
                    } else {
                        $query = sprintf("UPDATE %stbl_coupons_used SET times_used = times_used + 1 WHERE coupon_id = %d AND user_id = %d", DB_TBL_PREFIX, $row["coupon_id"], $booking_data["customer_id"]);
                        $result = mysqli_query($GLOBALS["DB"], $query);
                    }
                }
            }
            $points_earned = 0;
            if(!($coupon_used || $referral_used)) {
                $query = sprintf("SELECT * FROM %stbl_reward_points WHERE id=1", DB_TBL_PREFIX);
                if(($result = mysqli_query($GLOBALS["DB"], $query)) && mysqli_num_rows($result)) {
                    $row = mysqli_fetch_assoc($result);
                    if($row["status"] == 1) {
                        $curtopointsconv = (double) $row["cur_to_points_conv"];
                        $amount_paid_by_rider_converted = (double) $amount_paid_by_rider / $city_currency_exchange;
                        if($booking_data["payment_type"] == 2) {
                            if($booking_data["reward_points_redeemed"] == 0) {
                                $points_earned = round($amount_paid_by_rider_converted / $curtopointsconv, 1);
                                $query = sprintf("UPDATE %1\$stbl_users SET reward_points = reward_points + %2\$f WHERE user_id = %3\$d", DB_TBL_PREFIX, $points_earned, $booking_data["customer_id"]);
                                $result = mysqli_query($GLOBALS["DB"], $query);
                            } else {
                                $last_redeemed_reward_point_balance = $booking_data["reward_points_redeemed"] - $amount_paid_by_rider_converted;
                                if(0 < $last_redeemed_reward_point_balance) {
                                    $query = sprintf("UPDATE %1\$stbl_users SET reward_points_redeemed = %2\$f WHERE user_id = %3\$d", DB_TBL_PREFIX, $last_redeemed_reward_point_balance, $booking_data["customer_id"]);
                                    $result = mysqli_query($GLOBALS["DB"], $query);
                                } elseif($last_redeemed_reward_point_balance < 0) {
                                    $last_redeemed_reward_point_balance_real = abs($last_redeemed_reward_point_balance);
                                    $points_earned = round($last_redeemed_reward_point_balance_real / $curtopointsconv, 1);
                                    $query = sprintf("UPDATE %1\$stbl_users SET reward_points = reward_points + %2\$f, reward_points_redeemed = %4\$f WHERE user_id = %3\$d", DB_TBL_PREFIX, $points_earned, $booking_data["customer_id"], 0);
                                    $result = mysqli_query($GLOBALS["DB"], $query);
                                } else {
                                    $query = sprintf("UPDATE %1\$stbl_users SET reward_points_redeemed = %2\$f WHERE user_id = %3\$d", DB_TBL_PREFIX, 0, $booking_data["customer_id"]);
                                    $result = mysqli_query($GLOBALS["DB"], $query);
                                }
                            }
                        } else {
                            $points_earned = round($amount_paid_by_rider_converted / $curtopointsconv, 1);
                            $query = sprintf("UPDATE %1\$stbl_users SET reward_points = reward_points + %2\$f WHERE user_id = %3\$d", DB_TBL_PREFIX, $points_earned, $booking_data["customer_id"]);
                            $result = mysqli_query($GLOBALS["DB"], $query);
                        }
                    }
                }
            }
            $owner_franchise_wallet_amount = [];
            $query = sprintf("SELECT fwallet_amount FROM %stbl_franchise WHERE id = 1", DB_TBL_PREFIX);
            if($result = mysqli_query($GLOBALS["DB"], $query)) {
                if(mysqli_num_rows($result)) {
                    $owner_franchise_wallet_amount = mysqli_fetch_assoc($result);
                    $owner_wallet_amount = !empty($owner_franchise_wallet_amount["fwallet_amount"]) ? (double) $owner_franchise_wallet_amount["fwallet_amount"] : 0;
                    $driver_wallet_amount = !empty($booking_data["driver_wallet"]) ? (double) $booking_data["driver_wallet"] : 0;
                    $franchise_wallet_amount = !empty($booking_data["fwallet_amount"]) ? (double) $booking_data["fwallet_amount"] : 0;
                    $user_wallet_amount = !empty($booking_data["user_wallet"]) ? (double) $booking_data["user_wallet"] : 0;
                    $booking_cost = $ride_fare;
                    $driver_commision = !empty($booking_data["driver_commision"]) ? (double) $booking_data["driver_commision"] : 0;
                    $franchise_commision = !empty($booking_data["franchise_commision"]) ? (double) $booking_data["franchise_commision"] : 0;
                    if(!empty($booking_data["reg_with_referal_code"]) && $booking_data["referral_target_status"] == 0) {
                        $query = sprintf("SELECT %1\$stbl_referral_drivers.beneficiary,%1\$stbl_referral_drivers.route_id AS ref_route_id, %1\$stbl_referral_drivers.driver_incentive,%1\$stbl_referral_drivers.invitee_incentive, %1\$stbl_referral_drivers.number_of_rides, %1\$stbl_referral_drivers.number_of_days, %1\$stbl_currencies.symbol, %1\$stbl_currencies.exchng_rate, %1\$stbl_currencies.iso_code FROM %1\$stbl_referral_drivers \r\n        INNER JOIN %1\$stbl_routes ON %1\$stbl_routes.id = %1\$stbl_referral_drivers.route_id\r\n        INNER JOIN %1\$stbl_currencies ON %1\$stbl_currencies.id = %1\$stbl_routes.city_currency_id\r\n        WHERE %1\$stbl_referral_drivers.route_id = %2\$d AND %1\$stbl_referral_drivers.status = 1", DB_TBL_PREFIX, $booking_data["reg_route_id"]);
                        if(($result = mysqli_query($GLOBALS["DB"], $query)) && mysqli_num_rows($result)) {
                            $referal_settings_data = mysqli_fetch_assoc($result);
                            $query = sprintf("SELECT id, DATE(date_completed) AS date_comp FROM %stbl_bookings WHERE driver_id = %d AND `status` = %d AND route_id = %d ORDER BY id ASC LIMIT %d", DB_TBL_PREFIX, $_SESSION["uid"], 3, $booking_data["reg_route_id"], $referal_settings_data["number_of_rides"]);
                            if(($result = mysqli_query($GLOBALS["DB"], $query)) && mysqli_num_rows($result)) {
                                $driver_bookings = [];
                                while ($row = mysqli_fetch_assoc($result)) {
                                    if(!empty($row)) {
                                        $driver_bookings[] = $row;
                                    }
                                }
                                $num_of_bookings = count($driver_bookings);
                                $start_date = $driver_bookings[0]["date_comp"];
                                $latest_date = $driver_bookings[$num_of_bookings - 1]["date_comp"];
                                $days_taken = 0;
                                if(isValidDate($start_date) && isValidDate($latest_date)) {
                                    $time_duration_sec = strtotime($latest_date) - strtotime($start_date);
                                    $days_taken = intval($time_duration_sec / 86400);
                                } else {
                                    $days_taken = 1000;
                                }
                                $query = sprintf("UPDATE %stbl_drivers SET referral_task_progress = \"%s\" WHERE driver_id = %d", DB_TBL_PREFIX, $num_of_bookings, $_SESSION["uid"]);
                                $result = mysqli_query($GLOBALS["DB"], $query);
                                if($referal_settings_data["number_of_rides"] <= $num_of_bookings) {
                                    if($days_taken <= $referal_settings_data["number_of_days"]) {
                                        $query = sprintf("UPDATE %stbl_drivers SET referral_target_status = %d WHERE driver_id = %d", DB_TBL_PREFIX, 1, $_SESSION["uid"]);
                                        $result = mysqli_query($GLOBALS["DB"], $query);
                                        $referral_bonus_amount_driver_conv = $referal_settings_data["driver_incentive"] / $referal_settings_data["exchng_rate"];
                                        $referral_bonus_amount_invitee_conv = $referal_settings_data["invitee_incentive"] / $referal_settings_data["exchng_rate"];
                                        if($referal_settings_data["beneficiary"] == 0) {
                                            $query = sprintf("SELECT disp_lang,driver_id, wallet_amount FROM %stbl_drivers WHERE referal_code = \"%s\"", DB_TBL_PREFIX, $booking_data["reg_with_referal_code"]);
                                            if(($result = mysqli_query($GLOBALS["DB"], $query)) && mysqli_num_rows($result)) {
                                                $reg_ref_driver_data = mysqli_fetch_assoc($result);
                                                $query = sprintf("UPDATE %stbl_drivers SET wallet_amount = wallet_amount + %f WHERE driver_id = %d", DB_TBL_PREFIX, $referral_bonus_amount_driver_conv, $reg_ref_driver_data["driver_id"]);
                                                $result = mysqli_query($GLOBALS["DB"], $query);
                                                $query = sprintf("UPDATE %stbl_franchise SET fwallet_amount = fwallet_amount - %f WHERE id = %d", DB_TBL_PREFIX, $referral_bonus_amount_driver_conv, 1);
                                                $result = mysqli_query($GLOBALS["DB"], $query);
                                                $owner_wallet_amount -= $referral_bonus_amount_driver_conv;
                                                $transaction_id = crypto_string();
                                                $query = sprintf("INSERT INTO %stbl_wallet_transactions (cur_symbol,cur_exchng_rate,cur_code,book_id,transaction_id,amount,wallet_balance,user_id,user_type,`desc`,`type`,transaction_date) VALUES(\"%s\",\"%s\",\"%s\",\"%d\",\"%s\",\"%s\",\"%s\",\"%d\",\"%d\",\"%s\",\"%d\",\"%s\")", DB_TBL_PREFIX, $referal_settings_data["symbol"], $referal_settings_data["exchng_rate"], $referal_settings_data["iso_code"], 0, $transaction_id, 0 - $referral_bonus_amount_driver_conv, $owner_wallet_amount, 1, 2, "Driver referral bonus debit", 3, gmdate("Y-m-d H:i:s", time()));
                                                $result = mysqli_query($GLOBALS["DB"], $query);
                                                $transaction_id = crypto_string();
                                                $query = sprintf("INSERT INTO %stbl_wallet_transactions (cur_symbol,cur_exchng_rate,cur_code,book_id,transaction_id,amount,wallet_balance,user_id,user_type,`desc`,`type`,transaction_date) VALUES(\"%s\",\"%s\",\"%s\",\"%d\",\"%s\",\"%s\",\"%s\",\"%d\",\"%d\",\"%s\",\"%d\",\"%s\")", DB_TBL_PREFIX, $referal_settings_data["symbol"], $referal_settings_data["exchng_rate"], $referal_settings_data["iso_code"], 0, $transaction_id, $referal_settings_data["driver_incentive"], $referral_bonus_amount_driver_conv + $reg_ref_driver_data["wallet_amount"], $reg_ref_driver_data["driver_id"], 1, mysqli_real_escape_string($GLOBALS["DB"], __("Referral bonus", NULL, "d|" . $reg_ref_driver_data["disp_lang"])), 2, gmdate("Y-m-d H:i:s", time()));
                                                $result = mysqli_query($GLOBALS["DB"], $query);
                                                $driver_notification_msg = __("The new driver you referred to Droptaxi has completed the required number of trips within the set period of time. You have earned a referral bonus of {---1}", [$referal_settings_data["driver_incentive"]], "d|" . $reg_ref_driver_data["disp_lang"]);
                                                $query = sprintf("INSERT INTO %stbl_notifications(person_id,user_type,content,n_type,date_created) VALUES \r\n                                                (\"%d\",1,\"%s\",0,\"%s\")", DB_TBL_PREFIX, $reg_ref_driver_data["driver_id"], mysqli_real_escape_string($GLOBALS["DB"], $driver_notification_msg), gmdate("Y-m-d H:i:s", time()));
                                                $result = mysqli_query($GLOBALS["DB"], $query);
                                            }
                                        } elseif($referal_settings_data["beneficiary"] == 1) {
                                            $query = sprintf("UPDATE %stbl_drivers SET wallet_amount = wallet_amount + %f WHERE driver_id = %d", DB_TBL_PREFIX, $referral_bonus_amount_invitee_conv, $_SESSION["uid"]);
                                            $result = mysqli_query($GLOBALS["DB"], $query);
                                            $driver_wallet_amount += $referral_bonus_amount_invitee_conv;
                                            $query = sprintf("UPDATE %stbl_franchise SET fwallet_amount = fwallet_amount - %f WHERE id = %d", DB_TBL_PREFIX, $referral_bonus_amount_invitee_conv, 1);
                                            $result = mysqli_query($GLOBALS["DB"], $query);
                                            $owner_wallet_amount -= $referral_bonus_amount_invitee_conv;
                                            $transaction_id = crypto_string();
                                            $query = sprintf("INSERT INTO %stbl_wallet_transactions (cur_symbol,cur_exchng_rate,cur_code,book_id,transaction_id,amount,wallet_balance,user_id,user_type,`desc`,`type`,transaction_date) VALUES(\"%s\",\"%s\",\"%s\",\"%d\",\"%s\",\"%s\",\"%s\",\"%d\",\"%d\",\"%s\",\"%d\",\"%s\")", DB_TBL_PREFIX, $referal_settings_data["symbol"], $referal_settings_data["exchng_rate"], $referal_settings_data["iso_code"], 0, $transaction_id, 0 - $referral_bonus_amount_invitee_conv, $owner_wallet_amount, 1, 2, "Driver referral bonus debit", 3, gmdate("Y-m-d H:i:s", time()));
                                            $result = mysqli_query($GLOBALS["DB"], $query);
                                            $transaction_id = crypto_string();
                                            $query = sprintf("INSERT INTO %stbl_wallet_transactions (cur_symbol,cur_exchng_rate,cur_code,book_id,transaction_id,amount,wallet_balance,user_id,user_type,`desc`,`type`,transaction_date) VALUES(\"%s\",\"%s\",\"%s\",\"%d\",\"%s\",\"%s\",\"%s\",\"%d\",\"%d\",\"%s\",\"%d\",\"%s\")", DB_TBL_PREFIX, $referal_settings_data["symbol"], $referal_settings_data["exchng_rate"], $referal_settings_data["iso_code"], 0, $transaction_id, $referal_settings_data["invitee_incentive"], $driver_wallet_amount, $_SESSION["uid"], 1, mysqli_real_escape_string($GLOBALS["DB"], __("Referral bonus")), 2, gmdate("Y-m-d H:i:s", time()));
                                            $result = mysqli_query($GLOBALS["DB"], $query);
                                            $driver_notification_msg = __("You have completed the required number of trips within the set period of time. You have earned a referral bonus of {---1}", [$referal_settings_data["invitee_incentive"]]);
                                            $query = sprintf("INSERT INTO %stbl_notifications(person_id,user_type,content,n_type,date_created) VALUES \r\n                                        (\"%d\",1,\"%s\",0,\"%s\")", DB_TBL_PREFIX, $_SESSION["uid"], mysqli_real_escape_string($GLOBALS["DB"], $driver_notification_msg), gmdate("Y-m-d H:i:s", time()));
                                            $result = mysqli_query($GLOBALS["DB"], $query);
                                        } else {
                                            $query = sprintf("SELECT disp_lang,driver_id, wallet_amount FROM %stbl_drivers WHERE referal_code = \"%s\"", DB_TBL_PREFIX, $booking_data["reg_with_referal_code"]);
                                            if(($result = mysqli_query($GLOBALS["DB"], $query)) && mysqli_num_rows($result)) {
                                                $reg_ref_driver_data = mysqli_fetch_assoc($result);
                                                $query = sprintf("UPDATE %stbl_drivers SET wallet_amount = wallet_amount + %f WHERE driver_id = %d", DB_TBL_PREFIX, $referral_bonus_amount_driver_conv, $reg_ref_driver_data["driver_id"]);
                                                $result = mysqli_query($GLOBALS["DB"], $query);
                                                $query = sprintf("UPDATE %stbl_franchise SET fwallet_amount = fwallet_amount - %f WHERE id = %d", DB_TBL_PREFIX, $referral_bonus_amount_driver_conv, 1);
                                                $result = mysqli_query($GLOBALS["DB"], $query);
                                                $owner_wallet_amount -= $referral_bonus_amount_driver_conv;
                                                $transaction_id = crypto_string();
                                                $query = sprintf("INSERT INTO %stbl_wallet_transactions (cur_symbol,cur_exchng_rate,cur_code,book_id,transaction_id,amount,wallet_balance,user_id,user_type,`desc`,`type`,transaction_date) VALUES(\"%s\",\"%s\",\"%s\",\"%d\",\"%s\",\"%s\",\"%s\",\"%d\",\"%d\",\"%s\",\"%d\",\"%s\")", DB_TBL_PREFIX, $referal_settings_data["symbol"], $referal_settings_data["exchng_rate"], $referal_settings_data["iso_code"], 0, $transaction_id, 0 - $referral_bonus_amount_driver_conv, $owner_wallet_amount, 1, 2, "Driver referral bonus debit", 3, gmdate("Y-m-d H:i:s", time()));
                                                $result = mysqli_query($GLOBALS["DB"], $query);
                                                $transaction_id = crypto_string();
                                                $query = sprintf("INSERT INTO %stbl_wallet_transactions (cur_symbol,cur_exchng_rate,cur_code,book_id,transaction_id,amount,wallet_balance,user_id,user_type,`desc`,`type`,transaction_date) VALUES(\"%s\",\"%s\",\"%s\",\"%d\",\"%s\",\"%s\",\"%s\",\"%d\",\"%d\",\"%s\",\"%d\",\"%s\")", DB_TBL_PREFIX, $referal_settings_data["symbol"], $referal_settings_data["exchng_rate"], $referal_settings_data["iso_code"], 0, $transaction_id, $referal_settings_data["driver_incentive"], $referral_bonus_amount_driver_conv + $reg_ref_driver_data["wallet_amount"], $reg_ref_driver_data["driver_id"], 1, mysqli_real_escape_string($GLOBALS["DB"], __("Referral bonus", NULL, "d|" . $reg_ref_driver_data["disp_lang"])), 2, gmdate("Y-m-d H:i:s", time()));
                                                $result = mysqli_query($GLOBALS["DB"], $query);
                                                $driver_notification_msg = __("The new driver you referred to Droptaxi has completed the required number of trips within the set period of time. You have earned a referral bonus of {---1}", [$referal_settings_data["symbol"] . $referal_settings_data["driver_incentive"]], "d|" . $reg_ref_driver_data["disp_lang"]);
                                                $query = sprintf("INSERT INTO %stbl_notifications(person_id,user_type,content,n_type,date_created) VALUES \r\n                                                (\"%d\",1,\"%s\",0,\"%s\")", DB_TBL_PREFIX, $reg_ref_driver_data["driver_id"], mysqli_real_escape_string($GLOBALS["DB"], $driver_notification_msg), gmdate("Y-m-d H:i:s", time()));
                                                $result = mysqli_query($GLOBALS["DB"], $query);
                                            }
                                            $query = sprintf("UPDATE %stbl_drivers SET wallet_amount = wallet_amount + %f WHERE driver_id = %d", DB_TBL_PREFIX, $referral_bonus_amount_invitee_conv, $_SESSION["uid"]);
                                            $result = mysqli_query($GLOBALS["DB"], $query);
                                            $driver_wallet_amount += $referral_bonus_amount_invitee_conv;
                                            $query = sprintf("UPDATE %stbl_franchise SET fwallet_amount = fwallet_amount - %f WHERE id = %d", DB_TBL_PREFIX, $referral_bonus_amount_invitee_conv, 1);
                                            $result = mysqli_query($GLOBALS["DB"], $query);
                                            $owner_wallet_amount -= $referral_bonus_amount_invitee_conv;
                                            $transaction_id = crypto_string();
                                            $query = sprintf("INSERT INTO %stbl_wallet_transactions (cur_symbol,cur_exchng_rate,cur_code,book_id,transaction_id,amount,wallet_balance,user_id,user_type,`desc`,`type`,transaction_date) VALUES(\"%s\",\"%s\",\"%s\",\"%d\",\"%s\",\"%s\",\"%s\",\"%d\",\"%d\",\"%s\",\"%d\",\"%s\")", DB_TBL_PREFIX, $referal_settings_data["symbol"], $referal_settings_data["exchng_rate"], $referal_settings_data["iso_code"], 0, $transaction_id, 0 - $referral_bonus_amount_invitee_conv, $owner_wallet_amount, 1, 2, "Driver referral bonus debit", 3, gmdate("Y-m-d H:i:s", time()));
                                            $result = mysqli_query($GLOBALS["DB"], $query);
                                            $transaction_id = crypto_string();
                                            $query = sprintf("INSERT INTO %stbl_wallet_transactions (cur_symbol,cur_exchng_rate,cur_code,book_id,transaction_id,amount,wallet_balance,user_id,user_type,`desc`,`type`,transaction_date) VALUES(\"%s\",\"%s\",\"%s\",\"%d\",\"%s\",\"%s\",\"%s\",\"%d\",\"%d\",\"%s\",\"%d\",\"%s\")", DB_TBL_PREFIX, $referal_settings_data["symbol"], $referal_settings_data["exchng_rate"], $referal_settings_data["iso_code"], 0, $transaction_id, $referal_settings_data["invitee_incentive"], $driver_wallet_amount, $_SESSION["uid"], 1, mysqli_real_escape_string($GLOBALS["DB"], __("Referral bonus")), 2, gmdate("Y-m-d H:i:s", time()));
                                            $result = mysqli_query($GLOBALS["DB"], $query);
                                            $driver_notification_msg = __("You have completed the required number of trips within the set period of time. You have earned a referral bonus of {---1}", [$referal_settings_data["symbol"] . $referal_settings_data["invitee_incentive"]]);
                                            $query = sprintf("INSERT INTO %stbl_notifications(person_id,user_type,content,n_type,date_created) VALUES \r\n                                        (\"%d\",1,\"%s\",0,\"%s\")", DB_TBL_PREFIX, $_SESSION["uid"], mysqli_real_escape_string($GLOBALS["DB"], $driver_notification_msg), gmdate("Y-m-d H:i:s", time()));
                                            $result = mysqli_query($GLOBALS["DB"], $query);
                                        }
                                    } else {
                                        $query = sprintf("UPDATE %stbl_drivers SET referral_target_status = %d WHERE driver_id = %d", DB_TBL_PREFIX, 2, $_SESSION["uid"]);
                                        $result = mysqli_query($GLOBALS["DB"], $query);
                                        if($referal_settings_data["beneficiary"] == 0) {
                                            $query = sprintf("SELECT disp_lang,driver_id FROM %stbl_drivers WHERE referal_code = \"%s\"", DB_TBL_PREFIX, $booking_data["reg_with_referal_code"]);
                                            if(($result = mysqli_query($GLOBALS["DB"], $query)) && mysqli_num_rows($result)) {
                                                $reg_ref_driver_data = mysqli_fetch_assoc($result);
                                                $driver_notification_msg = __("The new driver you referred to Droptaxi failed to complete the required number of trips within the set period of time. No referral bonus was paid to you", NULL, "d|" . $reg_ref_driver_data["disp_lang"]);
                                                $query = sprintf("INSERT INTO %stbl_notifications(person_id,user_type,content,n_type,date_created) VALUES \r\n                                                (\"%d\",1,\"%s\",0,\"%s\")", DB_TBL_PREFIX, $reg_ref_driver_data["driver_id"], mysqli_real_escape_string($GLOBALS["DB"], $driver_notification_msg), gmdate("Y-m-d H:i:s", time()));
                                                $result = mysqli_query($GLOBALS["DB"], $query);
                                            }
                                        } elseif($referal_settings_data["beneficiary"] == 1) {
                                            $driver_notification_msg = __("You failed to complete the required number of trips within the set period of time. No referral bonus was paid to you");
                                            $query = sprintf("INSERT INTO %stbl_notifications(person_id,user_type,content,n_type,date_created) VALUES \r\n                                        (\"%d\",1,\"%s\",0,\"%s\")", DB_TBL_PREFIX, $_SESSION["uid"], mysqli_real_escape_string($GLOBALS["DB"], $driver_notification_msg), gmdate("Y-m-d H:i:s", time()));
                                            $result = mysqli_query($GLOBALS["DB"], $query);
                                        } else {
                                            $query = sprintf("SELECT disp_lang,driver_id FROM %stbl_drivers WHERE referal_code = \"%s\"", DB_TBL_PREFIX, $booking_data["reg_with_referal_code"]);
                                            if(($result = mysqli_query($GLOBALS["DB"], $query)) && mysqli_num_rows($result)) {
                                                $reg_ref_driver_data = mysqli_fetch_assoc($result);
                                                $driver_notification_msg = __("The new driver you referred to Droptaxi failed to complete the required number of trips within the set period of time. No referral bonus was paid to you", NULL, "d|" . $reg_ref_driver_data["disp_lang"]);
                                                $query = sprintf("INSERT INTO %stbl_notifications(person_id,user_type,content,n_type,date_created) VALUES \r\n                                                (\"%d\",1,\"%s\",0,\"%s\")", DB_TBL_PREFIX, $reg_ref_driver_data["driver_id"], mysqli_real_escape_string($GLOBALS["DB"], $driver_notification_msg), gmdate("Y-m-d H:i:s", time()));
                                                $result = mysqli_query($GLOBALS["DB"], $query);
                                            }
                                            $driver_notification_msg = __("You failed to complete the required number of trips within the set period of time. No referral bonus was paid to you");
                                            $query = sprintf("INSERT INTO %stbl_notifications(person_id,user_type,content,n_type,date_created) VALUES \r\n                                        (\"%d\",1,\"%s\",0,\"%s\")", DB_TBL_PREFIX, $_SESSION["uid"], mysqli_real_escape_string($GLOBALS["DB"], $driver_notification_msg), gmdate("Y-m-d H:i:s", time()));
                                            $result = mysqli_query($GLOBALS["DB"], $query);
                                        }
                                    }
                                } elseif($referal_settings_data["number_of_days"] < $days_taken) {
                                    $query = sprintf("UPDATE %stbl_drivers SET referral_target_status = %d WHERE driver_id = %d", DB_TBL_PREFIX, 2, $_SESSION["uid"]);
                                    $result = mysqli_query($GLOBALS["DB"], $query);
                                    if($referal_settings_data["beneficiary"] == 0) {
                                        $query = sprintf("SELECT disp_lang,driver_id FROM %stbl_drivers WHERE referal_code = \"%s\"", DB_TBL_PREFIX, $booking_data["reg_with_referal_code"]);
                                        if(($result = mysqli_query($GLOBALS["DB"], $query)) && mysqli_num_rows($result)) {
                                            $reg_ref_driver_data = mysqli_fetch_assoc($result);
                                            $driver_notification_msg = __("The new driver you referred to Droptaxi failed to complete the required number of trips within the set period of time. No referral bonus was paid to you", NULL, "d|" . $reg_ref_driver_data["disp_lang"]);
                                            $query = sprintf("INSERT INTO %stbl_notifications(person_id,user_type,content,n_type,date_created) VALUES \r\n                                            (\"%d\",1,\"%s\",0,\"%s\")", DB_TBL_PREFIX, $reg_ref_driver_data["driver_id"], mysqli_real_escape_string($GLOBALS["DB"], $driver_notification_msg), gmdate("Y-m-d H:i:s", time()));
                                            $result = mysqli_query($GLOBALS["DB"], $query);
                                        }
                                    } elseif($referal_settings_data["beneficiary"] == 1) {
                                        $driver_notification_msg = __("You failed to complete the required number of trips within the set period of time. No referral bonus was paid to you");
                                        $query = sprintf("INSERT INTO %stbl_notifications(person_id,user_type,content,n_type,date_created) VALUES \r\n                                    (\"%d\",1,\"%s\",0,\"%s\")", DB_TBL_PREFIX, $_SESSION["uid"], mysqli_real_escape_string($GLOBALS["DB"], $driver_notification_msg), gmdate("Y-m-d H:i:s", time()));
                                        $result = mysqli_query($GLOBALS["DB"], $query);
                                    } else {
                                        $query = sprintf("SELECT disp_lang,driver_id FROM %stbl_drivers WHERE referal_code = \"%s\"", DB_TBL_PREFIX, $booking_data["reg_with_referal_code"]);
                                        if(($result = mysqli_query($GLOBALS["DB"], $query)) && mysqli_num_rows($result)) {
                                            $reg_ref_driver_data = mysqli_fetch_assoc($result);
                                            $driver_notification_msg = __("The new driver you referred to Droptaxi failed to complete the required number of trips within the set period of time. No referral bonus was paid to you", NULL, "d|" . $reg_ref_driver_data["disp_lang"]);
                                            $query = sprintf("INSERT INTO %stbl_notifications(person_id,user_type,content,n_type,date_created) VALUES \r\n                                            (\"%d\",1,\"%s\",0,\"%s\")", DB_TBL_PREFIX, $reg_ref_driver_data["driver_id"], mysqli_real_escape_string($GLOBALS["DB"], $driver_notification_msg), gmdate("Y-m-d H:i:s", time()));
                                            $result = mysqli_query($GLOBALS["DB"], $query);
                                        }
                                        $driver_notification_msg = __("You failed to complete the required number of trips within the set period of time. No referral bonus was paid to you");
                                        $query = sprintf("INSERT INTO %stbl_notifications(person_id,user_type,content,n_type,date_created) VALUES \r\n                                    (\"%d\",1,\"%s\",0,\"%s\")", DB_TBL_PREFIX, $_SESSION["uid"], mysqli_real_escape_string($GLOBALS["DB"], $driver_notification_msg), gmdate("Y-m-d H:i:s", time()));
                                        $result = mysqli_query($GLOBALS["DB"], $query);
                                    }
                                }
                            }
                        }
                    }
                    if($booking_data["payment_type"] == 2) {
                        $user_wallet_balance = $user_wallet_amount - (double) $amount_paid_by_rider / $city_currency_exchange;
                        $query = sprintf("UPDATE %stbl_users SET wallet_amount = %f WHERE user_id = \"%d\"", DB_TBL_PREFIX, $user_wallet_balance, $booking_data["customer_id"]);
                        if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
                            mysqli_query($GLOBALS["DB"], "ROLLBACK");
                            $error = ["error" => __("An error has occured")];
                            echo json_encode($error);
                            exit;
                        }
                        $transaction_msg = __("Payment for completed ride with booking ID: {---1}.", ["#" . $booking_id], "d|" . $rlang);
                        if($referral_used && $coupon_used) {
                            if($coupon_discount_type == 0) {
                                $transaction_msg = __("Payment for completed ride with booking ID: {---1}. Referral discount of {---2} followed by a coupon discount of {---3} was applied", ["#" . $booking_id, $referral_discount_value . "%", $coupon_discount_value . "%"], "d|" . $rlang);
                            } else {
                                $transaction_msg = __("Payment for completed ride with booking ID: {---1}. Referral discount and Coupon flat fare price was used", ["#" . $booking_id], "d|" . $rlang);
                            }
                        } elseif($referral_used) {
                            $transaction_msg = __("Payment for completed ride with booking ID: {---1}. Referral discount of {---2} was applied", ["#" . $booking_id, $referral_discount_value . "%"], "d|" . $rlang);
                        } elseif($coupon_used) {
                            if($coupon_discount_type == 0) {
                                $transaction_msg = __("Payment for completed ride with booking ID: {---1}. Coupon discount of {---2} was applied", ["#" . $booking_id, $coupon_discount_value . "%"], "d|" . $rlang);
                            } else {
                                $transaction_msg = __("Payment for completed ride with booking ID: {---1}. Coupon flat fare price was used", ["#" . $booking_id], "d|" . $rlang);
                            }
                        }
                        $transaction_id = crypto_string();
                        $query = sprintf("INSERT INTO %stbl_wallet_transactions (cur_symbol,cur_exchng_rate,cur_code,book_id,transaction_id,amount,wallet_balance,user_id,user_type,`desc`,`type`,transaction_date) VALUES(\"%s\",\"%s\",\"%s\",\"%d\",\"%s\",\"%s\",\"%s\",\"%d\",\"%d\",\"%s\",\"%d\",\"%s\")", DB_TBL_PREFIX, $city_currency_symbol, $city_currency_exchange, $city_currency_code, $booking_id, $transaction_id, $amount_paid_by_rider, $user_wallet_balance, $booking_data["customer_id"], 0, mysqli_real_escape_string($GLOBALS["DB"], $transaction_msg), 3, gmdate("Y-m-d H:i:s", time()));
                        if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
                            mysqli_query($GLOBALS["DB"], "ROLLBACK");
                            $error = ["error" => __("An error has occured")];
                            echo json_encode($error);
                            exit;
                        }
                        $booking_title = str_pad($booking_id, 5, "0", STR_PAD_LEFT);
                        $amount_paid_by_rider_cur = floattocurrency($amount_paid_by_rider);
                        $notification_msg = __("Wallet payment of {---1} for booking {---2} was successful", [$city_currency_symbol . $amount_paid_by_rider_cur, "#" . $booking_title], "d|" . $rlang);
                        if($referral_used && $coupon_used) {
                            if($coupon_discount_type == 0) {
                                $notification_msg = __("Wallet payment of {---1} for booking {---2} was successful. Referral discount of {---3} followed by a coupon discount of {---4} was applied", [$city_currency_symbol . $amount_paid_by_rider_cur, "#" . $booking_title, $referral_discount_value . "%", $coupon_discount_value . "%", $coupon_discount_value . "%"], "d|" . $rlang);
                            } else {
                                $notification_msg = __("Wallet payment of {---1} for booking {---2} was successful. Referral discount and Coupon flat fare price was used", [$city_currency_symbol . $amount_paid_by_rider_cur, "#" . $booking_title], "d|" . $rlang);
                            }
                        } elseif($referral_used) {
                            $notification_msg = __("Wallet payment of {---1} for booking {---2} was successful. Referral discount of {---3} was applied", [$city_currency_symbol . $amount_paid_by_rider_cur, "#" . $booking_title, $referral_discount_value . "%"], "d|" . $rlang);
                        } elseif($coupon_used) {
                            if($coupon_discount_type == 0) {
                                $notification_msg = __("Wallet payment of {---1} for booking {---2} was successful. Coupon discount of {---3} was applied", [$city_currency_symbol . $amount_paid_by_rider_cur, "#" . $booking_title, $coupon_discount_value . "%"], "d|" . $rlang);
                            } else {
                                $notification_msg = __("Wallet payment of {---1} for booking {---2} was successful. Coupon flat fare price was used", [$city_currency_symbol . $amount_paid_by_rider_cur, "#" . $booking_title], "d|" . $rlang);
                            }
                        }
                        $query = sprintf("INSERT INTO %stbl_notifications(person_id,user_type,content,n_type,date_created) VALUES \r\n        (\"%d\",0,\"%s\",3,\"%s\")", DB_TBL_PREFIX, $booking_data["customer_id"], mysqli_real_escape_string($GLOBALS["DB"], $notification_msg), gmdate("Y-m-d H:i:s", time()));
                        if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
                            mysqli_query($GLOBALS["DB"], "ROLLBACK");
                            $error = ["error" => __("An error has occured")];
                            echo json_encode($error);
                            exit;
                        }
                        $driver_earning = $booking_cost * $driver_commision / 100;
                        $driver_earning_converted = (double) $driver_earning / $city_currency_exchange;
                        $owner_franchise_earning = 0;
                        $other_franchise_earning = 0;
                        if($booking_data["franchise_id"] == 1) {
                            $owner_franchise_earning = $booking_cost - $driver_earning;
                            $deficit_due_to_discounts = $amount_paid_by_rider - $ride_fare;
                            $owner_franchise_earning = $deficit_due_to_discounts + $owner_franchise_earning;
                            $owner_franchise_earning_converted = (double) $owner_franchise_earning / $city_currency_exchange;
                            $query = sprintf("UPDATE %stbl_franchise SET fwallet_amount = fwallet_amount + %f WHERE id = \"%d\"", DB_TBL_PREFIX, $owner_franchise_earning_converted, 1);
                            if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
                                mysqli_query($GLOBALS["DB"], "ROLLBACK");
                                $error = ["error" => __("An error has occured")];
                                echo json_encode($error);
                                exit;
                            }
                            if($owner_franchise_earning < 0) {
                                $debit_or_credit = "Debit";
                                $transaction_type = 3;
                            } else {
                                $debit_or_credit = "Credit";
                                $transaction_type = 2;
                            }
                            $transaction_msg = "Ride completion earning (" . $franchise_commision . "% commission) after deducting driver commission, for booking: #" . $booking_id;
                            if($referral_used && $coupon_used) {
                                if($coupon_discount_type == 0) {
                                    $transaction_msg = $debit_or_credit . " for completed ride with booking ID: #" . $booking_id . ". Customer referral discount of " . $referral_discount_value . "% followed by a coupon discount of " . $coupon_discount_value . "% was applied.";
                                } else {
                                    $transaction_msg = $debit_or_credit . " for completed ride with booking ID: #" . $booking_id . ". Customer referral discount and Coupon flat fare price was used.";
                                }
                            } elseif($referral_used) {
                                $transaction_msg = $debit_or_credit . " for completed ride with booking ID: #" . $booking_id . ". Customer referral discount of " . $referral_discount_value . "% was applied.";
                            } elseif($coupon_used) {
                                if($coupon_discount_type == 0) {
                                    $transaction_msg = $debit_or_credit . " for completed ride with booking ID: #" . $booking_id . ". Customer used coupon discount of " . $coupon_discount_value . "%.";
                                } else {
                                    $transaction_msg = $debit_or_credit . " for completed ride with booking ID: #" . $booking_id . ". Customer used coupon flat fare price.";
                                }
                            }
                            $transaction_id = crypto_string();
                            $query = sprintf("INSERT INTO %stbl_wallet_transactions (cur_symbol,cur_exchng_rate,cur_code,book_id,transaction_id,amount,wallet_balance,user_id,user_type,`desc`,`type`,transaction_date) VALUES(\"%s\",\"%s\",\"%s\",\"%d\",\"%s\",\"%s\",\"%s\",\"%d\",\"%d\",\"%s\",\"%d\",\"%s\")", DB_TBL_PREFIX, $city_currency_symbol, $city_currency_exchange, $city_currency_code, $booking_id, $transaction_id, $owner_franchise_earning, $owner_franchise_earning_converted + $owner_wallet_amount, 1, 2, mysqli_real_escape_string($GLOBALS["DB"], $transaction_msg), $transaction_type, gmdate("Y-m-d H:i:s", time()));
                            if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
                                mysqli_query($GLOBALS["DB"], "ROLLBACK");
                                $error = ["error" => __("An error has occured")];
                                echo json_encode($error);
                                exit;
                            }
                        } else {
                            $owner_and_franchise_earning = $booking_cost - $driver_earning;
                            $other_franchise_earning = $owner_and_franchise_earning * $franchise_commision / 100;
                            $other_franchise_earning_converted = (double) $other_franchise_earning / $city_currency_exchange;
                            $owner_franchise_earning = $owner_and_franchise_earning - $other_franchise_earning;
                            $deficit_due_to_discounts = $amount_paid_by_rider - $ride_fare;
                            $owner_franchise_earning = $deficit_due_to_discounts + $owner_franchise_earning;
                            $owner_franchise_earning_converted = (double) $owner_franchise_earning / $city_currency_exchange;
                            $query = sprintf("UPDATE %stbl_franchise SET fwallet_amount = fwallet_amount + %f WHERE id = \"%d\"", DB_TBL_PREFIX, $owner_franchise_earning_converted, 1);
                            if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
                                mysqli_query($GLOBALS["DB"], "ROLLBACK");
                                $error = ["error" => __("An error has occured")];
                                echo json_encode($error);
                                exit;
                            }
                            if($owner_franchise_earning < 0) {
                                $debit_or_credit = "Debit";
                                $transaction_type = 3;
                            } else {
                                $debit_or_credit = "Credit";
                                $transaction_type = 2;
                            }
                            $transaction_msg = $debit_or_credit . " for ride completion after deducting driver and franchise commission, for booking: #" . $booking_id;
                            if($referral_used && $coupon_used) {
                                if($coupon_discount_type == 0) {
                                    $transaction_msg = $debit_or_credit . " for completed ride with booking ID: #" . $booking_id . ". Customer referral discount of " . $referral_discount_value . "% followed by a coupon discount of " . $coupon_discount_value . "% was applied.";
                                } else {
                                    $transaction_msg = $debit_or_credit . " for completed ride with booking ID: #" . $booking_id . ". Customer referral discount and Coupon flat fare price was used.";
                                }
                            } elseif($referral_used) {
                                $transaction_msg = $debit_or_credit . " for completed ride with booking ID: #" . $booking_id . ". Customer referral discount of " . $referral_discount_value . "% was applied.";
                            } elseif($coupon_used) {
                                if($coupon_discount_type == 0) {
                                    $transaction_msg = $debit_or_credit . " for completed ride with booking ID: #" . $booking_id . ". Customer used coupon discount of " . $coupon_discount_value . "%.";
                                } else {
                                    $transaction_msg = $debit_or_credit . " for completed ride with booking ID: #" . $booking_id . ". Customer used coupon flat fare price.";
                                }
                            }
                            $transaction_id = crypto_string();
                            $query = sprintf("INSERT INTO %stbl_wallet_transactions (cur_symbol,cur_exchng_rate,cur_code,book_id,transaction_id,amount,wallet_balance,user_id,user_type,`desc`,`type`,transaction_date) VALUES(\"%s\",\"%s\",\"%s\",\"%d\",\"%s\",\"%s\",\"%s\",\"%d\",\"%d\",\"%s\",\"%d\",\"%s\")", DB_TBL_PREFIX, $city_currency_symbol, $city_currency_exchange, $city_currency_code, $booking_id, $transaction_id, $owner_franchise_earning, $owner_franchise_earning_converted + $owner_wallet_amount, 1, 2, mysqli_real_escape_string($GLOBALS["DB"], $transaction_msg), $transaction_type, gmdate("Y-m-d H:i:s", time()));
                            if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
                                mysqli_query($GLOBALS["DB"], "ROLLBACK");
                                $error = ["error" => __("An error has occured")];
                                echo json_encode($error);
                                exit;
                            }
                            $query = sprintf("UPDATE %stbl_franchise SET fwallet_amount = fwallet_amount + %f WHERE id = \"%d\"", DB_TBL_PREFIX, $other_franchise_earning_converted, $booking_data["franchise_id"]);
                            if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
                                mysqli_query($GLOBALS["DB"], "ROLLBACK");
                                $error = ["error" => __("An error has occured")];
                                echo json_encode($error);
                                exit;
                            }
                            $transaction_id = crypto_string();
                            $query = sprintf("INSERT INTO %stbl_wallet_transactions (cur_symbol,cur_exchng_rate,cur_code,book_id,transaction_id,amount,wallet_balance,user_id,user_type,`desc`,`type`,transaction_date) VALUES(\"%s\",\"%s\",\"%s\",\"%d\",\"%s\",\"%s\",\"%s\",\"%d\",\"%d\",\"%s\",\"%d\",\"%s\")", DB_TBL_PREFIX, $city_currency_symbol, $city_currency_exchange, $city_currency_code, $booking_id, $transaction_id, $other_franchise_earning, $other_franchise_earning_converted + $franchise_wallet_amount, $booking_data["franchise_id"], 2, "Ride completion earning (" . $franchise_commision . "%) after deducting driver commission, for booking: #" . $booking_id, 2, gmdate("Y-m-d H:i:s", time()));
                            if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
                                mysqli_query($GLOBALS["DB"], "ROLLBACK");
                                $error = ["error" => __("An error has occured")];
                                echo json_encode($error);
                                exit;
                            }
                        }
                        $query = sprintf("UPDATE %stbl_drivers SET wallet_amount = wallet_amount + %f WHERE driver_id = \"%d\"", DB_TBL_PREFIX, $driver_earning_converted, $_SESSION["uid"]);
                        if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
                            mysqli_query($GLOBALS["DB"], "ROLLBACK");
                            $error = ["error" => __("An error has occured")];
                            echo json_encode($error);
                            exit;
                        }
                        $transaction_id = crypto_string();
                        $query = sprintf("INSERT INTO %stbl_wallet_transactions (cur_symbol,cur_exchng_rate,cur_code,book_id,transaction_id,amount,wallet_balance,user_id,user_type,`desc`,`type`,transaction_date) VALUES(\"%s\",\"%s\",\"%s\",\"%d\",\"%s\",\"%s\",\"%s\",\"%d\",\"%d\",\"%s\",\"%d\",\"%s\")", DB_TBL_PREFIX, $city_currency_symbol, $city_currency_exchange, $city_currency_code, $booking_id, $transaction_id, $driver_earning, $driver_earning_converted + $driver_wallet_amount, $_SESSION["uid"], 1, __("Ride completion earning for booking: {---1}", ["#" . $booking_id]), 2, gmdate("Y-m-d H:i:s", time()));
                        if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
                            mysqli_query($GLOBALS["DB"], "ROLLBACK");
                            $error = ["error" => __("An error has occured")];
                            echo json_encode($error);
                            exit;
                        }
                    } elseif($booking_data["payment_type"] == 1) {
                        $driver_earning = $booking_cost * $driver_commision / 100;
                        $driver_balance_due_to_discounts = $driver_earning - $amount_paid_by_rider;
                        $driver_balance_due_to_discounts_converted = (double) $driver_balance_due_to_discounts / $city_currency_exchange;
                        $owner_franchise_earning = 0;
                        $other_franchise_earning = 0;
                        if($booking_data["franchise_id"] == 1) {
                            $owner_franchise_earning = $booking_cost - $driver_earning;
                            $owner_franchise_earning_converted = (double) $owner_franchise_earning / $city_currency_exchange;
                            $query = sprintf("UPDATE %stbl_drivers SET wallet_amount = wallet_amount + %f WHERE driver_id = \"%d\"", DB_TBL_PREFIX, $driver_balance_due_to_discounts_converted, $_SESSION["uid"]);
                            if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
                                mysqli_query($GLOBALS["DB"], "ROLLBACK");
                                $error = ["error" => __("An error has occured")];
                                echo json_encode($error);
                                exit;
                            }
                            $driver_wallet_amount = $driver_wallet_amount + $driver_balance_due_to_discounts_converted;
                            if($driver_balance_due_to_discounts_converted < 0) {
                                $transaction_id = crypto_string();
                                $query = sprintf("INSERT INTO %stbl_wallet_transactions (cur_symbol,cur_exchng_rate,cur_code,book_id,transaction_id,amount,wallet_balance,user_id,user_type,`desc`,`type`,transaction_date) VALUES(\"%s\",\"%s\",\"%s\",\"%d\",\"%s\",\"%s\",\"%s\",\"%d\",\"%d\",\"%s\",\"%d\",\"%s\")", DB_TBL_PREFIX, $city_currency_symbol, $city_currency_exchange, $city_currency_code, $booking_id, $transaction_id, $driver_balance_due_to_discounts, $driver_wallet_amount, $_SESSION["uid"], 1, __("Balance deduction for cash payment for booking: {---1}", ["#" . $booking_id]), 3, gmdate("Y-m-d H:i:s", time()));
                                if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
                                    mysqli_query($GLOBALS["DB"], "ROLLBACK");
                                    $error = ["error" => __("An error has occured")];
                                    echo json_encode($error);
                                    exit;
                                }
                            } elseif(0 < $driver_balance_due_to_discounts_converted) {
                                $transaction_id = crypto_string();
                                $query = sprintf("INSERT INTO %stbl_wallet_transactions (cur_symbol,cur_exchng_rate,cur_code,book_id,transaction_id,amount,wallet_balance,user_id,user_type,`desc`,`type`,transaction_date) VALUES(\"%s\",\"%s\",\"%s\",\"%d\",\"%s\",\"%s\",\"%s\",\"%d\",\"%d\",\"%s\",\"%d\",\"%s\")", DB_TBL_PREFIX, $city_currency_symbol, $city_currency_exchange, $city_currency_code, $booking_id, $transaction_id, $driver_balance_due_to_discounts, $driver_wallet_amount, $_SESSION["uid"], 1, __("Balance payment as a result of rider discounts for booking: {---1}", ["#" . $booking_id]), 2, gmdate("Y-m-d H:i:s", time()));
                                if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
                                    mysqli_query($GLOBALS["DB"], "ROLLBACK");
                                    $error = ["error" => __("An error has occured")];
                                    echo json_encode($error);
                                    exit;
                                }
                            } else {
                                $query = sprintf("UPDATE %stbl_drivers SET wallet_amount = wallet_amount - %f WHERE driver_id = \"%d\"", DB_TBL_PREFIX, $owner_franchise_earning_converted, $_SESSION["uid"]);
                                if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
                                    mysqli_query($GLOBALS["DB"], "ROLLBACK");
                                    $error = ["error" => __("An error has occured")];
                                    echo json_encode($error);
                                    exit;
                                }
                                $transaction_id = crypto_string();
                                $query = sprintf("INSERT INTO %stbl_wallet_transactions (cur_symbol,cur_exchng_rate,cur_code,book_id,transaction_id,amount,wallet_balance,user_id,user_type,`desc`,`type`,transaction_date) VALUES(\"%s\",\"%s\",\"%s\",\"%d\",\"%s\",\"%s\",\"%s\",\"%d\",\"%d\",\"%s\",\"%d\",\"%s\")", DB_TBL_PREFIX, $city_currency_symbol, $city_currency_exchange, $city_currency_code, $booking_id, $transaction_id, $owner_franchise_earning, $driver_wallet_amount - $owner_franchise_earning_converted, $_SESSION["uid"], 1, __("Service charge deduction on ride completion for booking: {---1}", ["#" . $booking_id]), 3, gmdate("Y-m-d H:i:s", time()));
                                if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
                                    mysqli_query($GLOBALS["DB"], "ROLLBACK");
                                    $error = ["error" => __("An error has occured")];
                                    echo json_encode($error);
                                    exit;
                                }
                            }
                            $deficit_due_to_discounts = $amount_paid_by_rider - $ride_fare;
                            $owner_franchise_earning = $deficit_due_to_discounts + $owner_franchise_earning;
                            $owner_franchise_earning_converted = (double) $owner_franchise_earning / $city_currency_exchange;
                            $query = sprintf("UPDATE %stbl_franchise SET fwallet_amount = fwallet_amount + %f WHERE id = \"%d\"", DB_TBL_PREFIX, $owner_franchise_earning_converted, 1);
                            if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
                                mysqli_query($GLOBALS["DB"], "ROLLBACK");
                                $error = ["error" => __("An error has occured")];
                                echo json_encode($error);
                                exit;
                            }
                            $transaction_msg = "Ride completion earning after deducting driver commission, for booking: #" . $booking_id;
                            if($owner_franchise_earning < 0) {
                                $debit_or_credit = "Debit";
                                $transaction_type = 3;
                            } else {
                                $debit_or_credit = "Credit";
                                $transaction_type = 2;
                            }
                            if($referral_used && $coupon_used) {
                                if($coupon_discount_type == 0) {
                                    $transaction_msg = $debit_or_credit . " for completed ride with booking ID: #" . $booking_id . ". Customer referral discount of " . $referral_discount_value . "% followed by a coupon discount of " . $coupon_discount_value . "% was applied.";
                                } else {
                                    $transaction_msg = $debit_or_credit . " for completed ride with booking ID: #" . $booking_id . ". Customer referral discount and Coupon flat fare price was used.";
                                }
                            } elseif($referral_used) {
                                $transaction_msg = $debit_or_credit . " for completed ride with booking ID: #" . $booking_id . ". Customer referral discount of " . $referral_discount_value . "% was applied.";
                            } elseif($coupon_used) {
                                if($coupon_discount_type == 0) {
                                    $transaction_msg = $debit_or_credit . " for completed ride with booking ID: #" . $booking_id . ". Customer used coupon discount of " . $coupon_discount_value . "%.";
                                } else {
                                    $transaction_msg = $debit_or_credit . " for completed ride with booking ID: #" . $booking_id . ". Customer used coupon flat fare price.";
                                }
                            }
                            $transaction_id = crypto_string();
                            $query = sprintf("INSERT INTO %stbl_wallet_transactions (cur_symbol,cur_exchng_rate,cur_code,book_id,transaction_id,amount,wallet_balance,user_id,user_type,`desc`,`type`,transaction_date) VALUES(\"%s\",\"%s\",\"%s\",\"%d\",\"%s\",\"%s\",\"%s\",\"%d\",\"%d\",\"%s\",\"%d\",\"%s\")", DB_TBL_PREFIX, $city_currency_symbol, $city_currency_exchange, $city_currency_code, $booking_id, $transaction_id, $owner_franchise_earning, $owner_franchise_earning_converted + $owner_wallet_amount, 1, 2, mysqli_real_escape_string($GLOBALS["DB"], $transaction_msg), $transaction_type, gmdate("Y-m-d H:i:s", time()));
                            if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
                                mysqli_query($GLOBALS["DB"], "ROLLBACK");
                                $error = ["error" => __("An error has occured")];
                                echo json_encode($error);
                                exit;
                            }
                        } else {
                            $owner_and_franchise_earning = $booking_cost - $driver_earning;
                            $owner_and_franchise_earning_converted = (double) $owner_and_franchise_earning / $city_currency_exchange;
                            $other_franchise_earning = $owner_and_franchise_earning * $franchise_commision / 100;
                            $other_franchise_earning_converted = (double) $other_franchise_earning / $city_currency_exchange;
                            $query = sprintf("UPDATE %stbl_franchise SET fwallet_amount = fwallet_amount + %f WHERE id = \"%d\"", DB_TBL_PREFIX, $other_franchise_earning_converted, $booking_data["franchise_id"]);
                            if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
                                mysqli_query($GLOBALS["DB"], "ROLLBACK");
                                $error = ["error" => __("An error has occured")];
                                echo json_encode($error);
                                exit;
                            }
                            $transaction_id = crypto_string();
                            $query = sprintf("INSERT INTO %stbl_wallet_transactions (cur_symbol,cur_exchng_rate,cur_code,book_id,transaction_id,amount,wallet_balance,user_id,user_type,`desc`,`type`,transaction_date) VALUES(\"%s\",\"%s\",\"%s\",\"%d\",\"%s\",\"%s\",\"%s\",\"%d\",\"%d\",\"%s\",\"%d\",\"%s\")", DB_TBL_PREFIX, $city_currency_symbol, $city_currency_exchange, $city_currency_code, $booking_id, $transaction_id, $other_franchise_earning, $other_franchise_earning_converted + $franchise_wallet_amount, $booking_data["franchise_id"], 2, "Ride completion earning (" . $franchise_commision . "%) for booking: #" . $booking_id, 2, gmdate("Y-m-d H:i:s", time()));
                            if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
                                mysqli_query($GLOBALS["DB"], "ROLLBACK");
                                $error = ["error" => __("An error has occured")];
                                echo json_encode($error);
                                exit;
                            }
                            $query = sprintf("UPDATE %stbl_drivers SET wallet_amount = wallet_amount + %f WHERE driver_id = \"%d\"", DB_TBL_PREFIX, $driver_balance_due_to_discounts_converted, $_SESSION["uid"]);
                            if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
                                mysqli_query($GLOBALS["DB"], "ROLLBACK");
                                $error = ["error" => __("An error has occured")];
                                echo json_encode($error);
                                exit;
                            }
                            $driver_wallet_amount = $driver_wallet_amount + $driver_balance_due_to_discounts_converted;
                            if($driver_balance_due_to_discounts_converted < 0) {
                                $transaction_id = crypto_string();
                                $query = sprintf("INSERT INTO %stbl_wallet_transactions (cur_symbol,cur_exchng_rate,cur_code,book_id,transaction_id,amount,wallet_balance,user_id,user_type,`desc`,`type`,transaction_date) VALUES(\"%s\",\"%s\",\"%s\",\"%d\",\"%s\",\"%s\",\"%s\",\"%d\",\"%d\",\"%s\",\"%d\",\"%s\")", DB_TBL_PREFIX, $city_currency_symbol, $city_currency_exchange, $city_currency_code, $booking_id, $transaction_id, $driver_balance_due_to_discounts, $driver_wallet_amount, $_SESSION["uid"], 1, __("Balance deduction for cash payment for booking: {---1}", ["#" . $booking_id]), 3, gmdate("Y-m-d H:i:s", time()));
                                if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
                                    mysqli_query($GLOBALS["DB"], "ROLLBACK");
                                    $error = ["error" => __("An error has occured")];
                                    echo json_encode($error);
                                    exit;
                                }
                            } elseif(0 < $driver_balance_due_to_discounts_converted) {
                                $transaction_id = crypto_string();
                                $query = sprintf("INSERT INTO %stbl_wallet_transactions (cur_symbol,cur_exchng_rate,cur_code,book_id,transaction_id,amount,wallet_balance,user_id,user_type,`desc`,`type`,transaction_date) VALUES(\"%s\",\"%s\",\"%s\",\"%d\",\"%s\",\"%s\",\"%s\",\"%d\",\"%d\",\"%s\",\"%d\",\"%s\")", DB_TBL_PREFIX, $city_currency_symbol, $city_currency_exchange, $city_currency_code, $booking_id, $transaction_id, $driver_balance_due_to_discounts, $driver_wallet_amount, $_SESSION["uid"], 1, __("Balance payment as a result of rider discounts for booking: {---1}", ["#" . $booking_id]), 2, gmdate("Y-m-d H:i:s", time()));
                                if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
                                    mysqli_query($GLOBALS["DB"], "ROLLBACK");
                                    $error = ["error" => __("An error has occured")];
                                    echo json_encode($error);
                                    exit;
                                }
                            } else {
                                $query = sprintf("UPDATE %stbl_drivers SET wallet_amount = wallet_amount - %f WHERE driver_id = \"%d\"", DB_TBL_PREFIX, $owner_and_franchise_earning_converted, $_SESSION["uid"]);
                                if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
                                    mysqli_query($GLOBALS["DB"], "ROLLBACK");
                                    $error = ["error" => __("An error has occured")];
                                    echo json_encode($error);
                                    exit;
                                }
                                $transaction_id = crypto_string();
                                $query = sprintf("INSERT INTO %stbl_wallet_transactions (cur_symbol,cur_exchng_rate,cur_code,book_id,transaction_id,amount,wallet_balance,user_id,user_type,`desc`,`type`,transaction_date) VALUES(\"%s\",\"%s\",\"%s\",\"%d\",\"%s\",\"%s\",\"%s\",\"%d\",\"%d\",\"%s\",\"%d\",\"%s\")", DB_TBL_PREFIX, $city_currency_symbol, $city_currency_exchange, $city_currency_code, $booking_id, $transaction_id, $owner_and_franchise_earning, $driver_wallet_amount - $owner_and_franchise_earning_converted, $_SESSION["uid"], 1, __("Service charge deduction on ride completion for booking: {---1}", ["#" . $booking_id]), 3, gmdate("Y-m-d H:i:s", time()));
                                if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
                                    mysqli_query($GLOBALS["DB"], "ROLLBACK");
                                    $error = ["error" => __("An error has occured")];
                                    echo json_encode($error);
                                    exit;
                                }
                            }
                            $owner_franchise_earning = $owner_and_franchise_earning - $other_franchise_earning;
                            $deficit_due_to_discounts = $amount_paid_by_rider - $ride_fare;
                            $owner_franchise_earning = $deficit_due_to_discounts + $owner_franchise_earning;
                            $owner_franchise_earning_converted = (double) $owner_franchise_earning / $city_currency_exchange;
                            $query = sprintf("UPDATE %stbl_franchise SET fwallet_amount = fwallet_amount + %f WHERE id = \"%d\"", DB_TBL_PREFIX, $owner_franchise_earning_converted, 1);
                            if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
                                mysqli_query($GLOBALS["DB"], "ROLLBACK");
                                $error = ["error" => __("An error has occured")];
                                echo json_encode($error);
                                exit;
                            }
                            if($owner_franchise_earning < 0) {
                                $debit_or_credit = "Debit";
                                $transaction_type = 3;
                            } else {
                                $debit_or_credit = "Credit";
                                $transaction_type = 2;
                            }
                            $transaction_msg = $debit_or_credit . " for ride completion after deducting driver and franchise commission, for booking: #" . $booking_id;
                            if($referral_used && $coupon_used) {
                                if($coupon_discount_type == 0) {
                                    $transaction_msg = $debit_or_credit . " for completed ride with booking ID: #" . $booking_id . ". Customer referral discount of " . $referral_discount_value . "% followed by a coupon discount of " . $coupon_discount_value . "% was applied.";
                                } else {
                                    $transaction_msg = $debit_or_credit . " for completed ride with booking ID: #" . $booking_id . ". Customer referral discount and Coupon flat fare price was used.";
                                }
                            } elseif($referral_used) {
                                $transaction_msg = $debit_or_credit . " for completed ride with booking ID: #" . $booking_id . ". Customer referral discount of " . $referral_discount_value . "% was applied.";
                            } elseif($coupon_used) {
                                if($coupon_discount_type == 0) {
                                    $transaction_msg = $debit_or_credit . " for completed ride with booking ID: #" . $booking_id . ". Customer used coupon discount of " . $coupon_discount_value . "%.";
                                } else {
                                    $transaction_msg = $debit_or_credit . " for completed ride with booking ID: #" . $booking_id . ". Customer used coupon flat fare price.";
                                }
                            }
                            $transaction_id = crypto_string();
                            $query = sprintf("INSERT INTO %stbl_wallet_transactions (cur_symbol,cur_exchng_rate,cur_code,book_id,transaction_id,amount,wallet_balance,user_id,user_type,`desc`,`type`,transaction_date) VALUES(\"%s\",\"%s\",\"%s\",\"%d\",\"%s\",\"%s\",\"%s\",\"%d\",\"%d\",\"%s\",\"%d\",\"%s\")", DB_TBL_PREFIX, $city_currency_symbol, $city_currency_exchange, $city_currency_code, $booking_id, $transaction_id, $owner_franchise_earning, $owner_franchise_earning_converted + $owner_wallet_amount, 1, 2, mysqli_real_escape_string($GLOBALS["DB"], $transaction_msg), $transaction_type, gmdate("Y-m-d H:i:s", time()));
                            if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
                                mysqli_query($GLOBALS["DB"], "ROLLBACK");
                                $error = ["error" => __("An error has occured")];
                                echo json_encode($error);
                                exit;
                            }
                        }
                    }
                    $booking_title = str_pad($booking_id, 5, "0", STR_PAD_LEFT);
                    $title = WEBSITE_NAME . " - " . __("Ride completed", [], "d|" . $rlang);
                    $body = __("Your ride with booking ID({---1}) was completed successfully", [(string) $booking_title], "d|" . $rlang);
                    $device_tokens = $booking_data["push_token"];
                    if(!empty($device_tokens)) {
                        sendPushNotification($title, $body, $device_tokens, NULL, 0);
                    }
                    $photo_file = isset($booking_data["driver_photo"]) ? $booking_data["driver_photo"] : "0";
                    $booking_title = str_pad($booking_id, 5, "0", STR_PAD_LEFT);
                    $title = WEBSITE_NAME . " - " . __("Ride completed", [], "d|" . $rlang);
                    $body = __("Your ride with booking ID({---1}) was completed successfully", [(string) $booking_title], "d|" . $rlang);
                    $device_tokens = $booking_data["push_token"];
                    $data = ["action" => "driver-complete", "booking_id" => $booking_id, "driver_firstname" => $booking_data["driver_firstname"], "driver_photo" => SITE_URL . "ajaxphotofile.php?file=" . $photo_file, "ride_amount" => $city_currency_symbol . $amount_paid_by_rider, "ride_duration" => $ride_duration_formated, "ride_distance" => $distance_travelled / 1000, "reward_points_earned" => $points_earned];
                    if(!empty($device_tokens)) {
                        sendPushNotification($title, $body, $device_tokens, $data);
                    }
                    sendRealTimeNotification("ridr-" . $booking_data["customer_id"], $data);
                    $content = __("Your ride with booking ID({---1}) was completed successfully", [(string) $booking_title], "d|" . $rlang);
                    $query = sprintf("INSERT INTO %stbl_notifications(person_id,user_type,content,n_type,date_created) VALUES \r\n    (\"%d\",0,\"%s\",4,\"%s\")", DB_TBL_PREFIX, $booking_data["customer_id"], mysqli_real_escape_string($GLOBALS["DB"], $content), gmdate("Y-m-d H:i:s", time()));
                    $result = mysqli_query($GLOBALS["DB"], $query);
                    mysqli_query($GLOBALS["DB"], "COMMIT");
                    $data_array = ["success" => 1];
                    echo json_encode($data_array);
                    exit;
                }
                mysqli_query($GLOBALS["DB"], "ROLLBACK");
                $error = ["error" => __("An error has occured")];
                echo json_encode($error);
                exit;
            }
            mysqli_query($GLOBALS["DB"], "ROLLBACK");
            $error = ["error" => __("An error has occured")];
            echo json_encode($error);
            exit;
        }
        $error = ["error" => __("An error has occured")];
        echo json_encode($error);
        exit;
    }
    $error = ["error" => __("An error has occured")];
    echo json_encode($error);
    exit;
}
function getpersoninfo()
{
    if(empty($_SESSION["loggedin"])) {
        $error = ["error" => __("Please login to continue")];
        echo json_encode($error);
        exit;
    }
    $booking_id = (int) $_GET["booking_id"];
    $query = sprintf("SELECT %1\$stbl_users.user_id, %1\$stbl_users.firstname,%1\$stbl_users.lastname, %1\$stbl_users.user_rating, %1\$stbl_users.account_create_date,%1\$stbl_users.completed_rides,%1\$stbl_users.cancelled_rides, %1\$stbl_users.photo_file FROM %1\$stbl_bookings \r\n    INNER JOIN %1\$stbl_users ON %1\$stbl_users.user_id = %1\$stbl_bookings.user_id\r\n    WHERE %1\$stbl_bookings.id = %2\$d AND %1\$stbl_bookings.driver_id = %3\$d", DB_TBL_PREFIX, $booking_id, $_SESSION["uid"]);
    if(($result = mysqli_query($GLOBALS["DB"], $query)) && mysqli_num_rows($result)) {
        $user_data = mysqli_fetch_assoc($result);
        $comments_ratings = "";
        $photo_file = isset($user_data["photo_file"]) ? $user_data["photo_file"] : "0";
        $query = sprintf("SELECT %1\$stbl_drivers.firstname, %1\$stbl_ratings_drivers.driver_comment, %1\$stbl_ratings_drivers.driver_rating FROM %1\$stbl_ratings_drivers \r\n            INNER JOIN %1\$stbl_bookings ON %1\$stbl_bookings.id = %1\$stbl_ratings_drivers.booking_id\r\n            INNER JOIN %1\$stbl_drivers ON %1\$stbl_drivers.driver_id = %1\$stbl_ratings_drivers.driver_id\r\n            WHERE %1\$stbl_bookings.user_id = %2\$d ORDER BY %1\$stbl_ratings_drivers.id DESC LIMIT 100", DB_TBL_PREFIX, $user_data["user_id"]);
        if(($result = mysqli_query($GLOBALS["DB"], $query)) && mysqli_num_rows($result)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $comments_ratings .= "<div style='padding: 10px 5px;border-top:thin solid #ccc;'> <div><span style='font-size:12px;font-weight:bold;margin-bottom:5px;'>" . $row["firstname"] . "</span></div> <div><img src='img/rating-" . $row["driver_rating"] . ".png' style='width:50px;' /></div> <div><span style='font-size:14px;'>" . $row["driver_comment"] . "</span></div> </div>";
            }
            $comments_ratings = "<div style='font-size:14px;font-weight:bold;padding: 5px 5px;text-align:left;'>" . __("Comments and Ratings") . "</div>" . $comments_ratings;
        }
        if(empty($comments_ratings)) {
            $comments_ratings = "<div style='padding: 50px 5px;text-align:center;'>" . __("No comments and ratings available") . "</div>";
        }
        $data = ["success" => 1, "userdata" => $user_data, "comments" => $comments_ratings, "photo" => SITE_URL . "ajaxuserphotofile.php?file=" . $photo_file];
        echo json_encode($data);
        exit;
    }
    $error = ["error" => 1];
    echo json_encode($error);
    exit;
}
function getDriverOnride()
{
    if(empty($_SESSION["loggedin"])) {
        $error = ["error" => "Please re-login and retry."];
        echo json_encode($error);
        exit;
    }
    $booking_onride = [];
    $booking_pending = [];
    $booking_accepted = [];
    $data = [];
    $query = sprintf("SELECT *, DATE(%1\$stbl_bookings.date_created) AS created_date,%1\$stbl_bookings.id AS booking_id,%1\$stbl_bookings.driver_id AS b_driver_id,%1\$stbl_bookings.status AS b_status,%1\$stbl_driver_allocate.status AS a_status FROM %1\$stbl_bookings     \r\n    LEFT JOIN %1\$stbl_driver_allocate ON %1\$stbl_driver_allocate.booking_id = %1\$stbl_bookings.id\r\n    LEFT JOIN %1\$stbl_users ON %1\$stbl_users.user_id = %1\$stbl_bookings.user_id\r\n    WHERE %1\$stbl_bookings.driver_id = %2\$d AND (%1\$stbl_bookings.status = 0 OR %1\$stbl_bookings.status = 1)  OR (%1\$stbl_driver_allocate.driver_id = %2\$d AND %1\$stbl_driver_allocate.status = 0) ORDER BY %1\$stbl_bookings.date_created  \r\n    DESC LIMIT 0,10", DB_TBL_PREFIX, $_SESSION["uid"]);
    if($result = mysqli_query($GLOBALS["DB"], $query)) {
        if(mysqli_num_rows($result)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
                if($row["b_status"] == 1) {
                    $booking_onride[$row["created_date"]]["date"] = $row["created_date"];
                    $booking_onride[$row["created_date"]]["onride"][] = $row;
                }
                if($row["b_status"] == 0 && $row["b_driver_id"] == $_SESSION["uid"]) {
                    $booking_accepted[$row["created_date"]]["date"] = $row["created_date"];
                    $booking_accepted[$row["created_date"]]["accepted"][] = $row;
                }
                if($row["a_status"] == 0 && $row["b_driver_id"] == 0) {
                    $booking_pending[$row["created_date"]]["date"] = $row["created_date"];
                    $booking_pending[$row["created_date"]]["pending"][] = $row;
                }
            }
            mysqli_free_result($result);
        }
        $booking_onride_str = "";
        $booking_pend_str = "";
        $booking_accept_str = "";
        foreach ($booking_onride as $bookingdatasort) {
            $b_date_format = date("l, M j, Y", strtotime($bookingdatasort["date"] . " UTC"));
            $booking_onride_str .= "<ons-list-header style='border-top: thin solid grey;border-bottom: thin solid grey;font-size: 14px;font-weight:bold;'>" . $b_date_format . "</ons-list-header>";
            foreach ($bookingdatasort["onride"] as $bookingdatasort_canc) {
                $booking_time = date("g:i A", strtotime($bookingdatasort_canc["date_created"] . " UTC"));
                $booking_ptime = date("g:i A", strtotime($bookingdatasort_canc["pickup_datetime"] . " UTC"));
                $booking_pdate_time = date("d/m/Y g:i A", strtotime($bookingdatasort_canc["pickup_datetime"] . " UTC"));
                $booking_payment_type = "";
                $booking_fare = $bookingdatasort_canc["cur_symbol"] . $bookingdatasort_canc["estimated_cost"];
                if(!empty($bookingdatasort_canc["payment_type"])) {
                    if($bookingdatasort_canc["payment_type"] == 1) {
                        $booking_payment_type = "Cash";
                    } elseif($bookingdatasort_canc["payment_type"] == 2) {
                        $booking_payment_type = "Wallet";
                    } else {
                        $booking_payment_type = "Card";
                    }
                }
                $user_photo = explode("/", $bookingdatasort_canc["photo_file"]);
                $user_photo_file = isset($user_photo[5]) ? SITE_URL . "ajaxuserphotofile.php?file=" . $user_photo[5] : SITE_URL . "ajaxuserphotofile.php?file=0";
                $booking_title = str_pad($bookingdatasort_canc["booking_id"], 5, "0", STR_PAD_LEFT);
                $booking_onride_str .= "<ons-list-item id='booking-list-item-" . $bookingdatasort_canc["booking_id"] . "' modifier='longdivider'>\r\n                  \r\n                                        <div class='center'>\r\n                                                <div style='width:100%;'><span class='list-item__title'>\r\n                                                    <div style='border-bottom: thin solid #ccc;margin-bottom:10px;padding-bottom: 5px;'>" . $booking_time . "</div>\r\n                                                    <div style='display:inline-block;width:80px;height:80px;float:left;margin-bottom:5px;margin-left:20px;background-image:url(" . $user_photo_file . ");background-size:cover;border-radius:50%;margin-right:10px;'></div>\r\n                                                    <div style='display:inline-block;'>\r\n                                                        <p style='margin:0;'>" . $bookingdatasort_canc["user_firstname"] . "</p>\r\n                                                        <p style='margin:0;'><img style='width:80px;' src='img/rating-" . $bookingdatasort_canc["user_rating"] . ".png' /></p>\r\n                                                        <div>\r\n                                                            <ons-button style='border: thin solid;' modifier='outline' onclick=call_rider('" . $bookingdatasort_canc["user_phone"] . "')><ons-icon icon='fa-phone' ></ons-icon></ons-button>\r\n                                                            <ons-button style='border: thin solid;' modifier='outline' onclick=sms_rider('" . $bookingdatasort_canc["user_phone"] . "')><ons-icon icon='fa-envelope' ></ons-icon></ons-button>\r\n                                                            <ons-button style='border: thin solid;color: red;' modifier='outline' onclick=dropoffmap(0," . $bookingdatasort_canc["dropoff_long"] . "," . $bookingdatasort_canc["dropoff_lat"] . ")><ons-icon icon='fa-map-marker' ></ons-icon></ons-button>\r\n                                                            <ons-button style='border: thin solid;color: red;' modifier='outline' onclick=dropoffmap(1," . $bookingdatasort_canc["dropoff_long"] . "," . $bookingdatasort_canc["dropoff_lat"] . ")><ons-icon icon='fa-location-arrow' ></ons-icon></ons-button>\r\n                                                        </div>\r\n                                                    </div>\r\n                                                </div>\r\n                                                <span style='text-align: left;margin-bottom: 15px;margin-left:20px;' class='list-item__subtitle'><span>Booking ID:#" . $booking_title . "</span> | <span>Fare:#" . $booking_fare . "</span> <span>(" . $booking_payment_type . ")</span> </span>                               \r\n                                                \r\n                                                \r\n                                                \r\n                                                <span class='list-item__subtitle' style='margin-bottom:5px;'><ons-icon icon='fa-circle' size='14px' style='color: #24c539; font-size: 14px;position: absolute;'></ons-icon> <span style='display:inline-block;margin-left:22px;font-weight:bold;'>" . $bookingdatasort_canc["pickup_address"] . "</span></span>\r\n                                                <span class='list-item__subtitle' ><ons-icon icon='fa-map-marker' size='14px' style='color: red; font-size: 14px;position: absolute;'></ons-icon> <span style='display:inline-block;margin-left:22px;font-weight:bold;'>" . $bookingdatasort_canc["dropoff_address"] . "</span></span>\r\n                                                \r\n                                                \r\n                                                <span class='list-item__subtitle' style='margin-top: 10px;border-top: thin solid grey;padding-top: 10px;'><ons-button onclick=driverendride(" . $bookingdatasort_canc["booking_id"] . ") style='width:48%;color:white;font-size: 16px;'> COMPLETE</ons-button> <ons-button onclick='onclick=drivercancel(" . $bookingdatasort_canc["booking_id"] . ")' style='width:48%;color:white;font-size: 16px;background-color:black'> CANCEL</ons-button></span>\r\n                                                    \r\n                                                    \r\n                                                \r\n                                                \r\n                                        </div>\r\n                                    \r\n                                    </ons-list-item>";
            }
        }
        foreach ($booking_pending as $bookingdatasort) {
            $b_date_format = date("l, M j, Y", strtotime($bookingdatasort["date"] . " UTC"));
            $booking_pend_str .= "<ons-list-header style='border-top: thin solid grey;border-bottom: thin solid grey;font-size: 14px;font-weight:bold;'>" . $b_date_format . "</ons-list-header>";
            foreach ($bookingdatasort["pending"] as $bookingdatasort_canc) {
                $booking_time = date("g:i A", strtotime($bookingdatasort_canc["date_created"] . " UTC"));
                $booking_ptime = date("g:i A", strtotime($bookingdatasort_canc["pickup_datetime"] . " UTC"));
                $booking_pdate_time = date("d/m/Y g:i A", strtotime($bookingdatasort_canc["pickup_datetime"] . " UTC"));
                $booking_payment_type = "";
                if(!empty($bookingdatasort_canc["payment_type"])) {
                    if($bookingdatasort_canc["payment_type"] == 1) {
                        $booking_payment_type = "Cash";
                    } elseif($bookingdatasort_canc["payment_type"] == 2) {
                        $booking_payment_type = "Wallet";
                    } else {
                        $booking_payment_type = "Card";
                    }
                }
                $booking_title = str_pad($bookingdatasort_canc["booking_id"], 5, "0", STR_PAD_LEFT);
                $booking_pend_str .= "<ons-list-item id='booking-list-item-" . $bookingdatasort_canc["booking_id"] . "' modifier='longdivider'>\r\n              \r\n                                        <div class='center'>\r\n                                            <div style='width:100%;'><span class='list-item__title'>" . $booking_time . "</div>\r\n                                            <span style='text-align: left;margin-bottom: 15px;' class='list-item__subtitle'><span>Booking ID:#" . $booking_title . "</span></span>                               \r\n                                                                                                                                    \r\n                                            <span class='list-item__subtitle' style='margin-bottom:5px;'><ons-icon icon='fa-circle' size='14px' style='color: #24c539; font-size: 14px;position: absolute;'></ons-icon> <span style='display:inline-block;margin-left:22px;font-weight:bold;'>" . $bookingdatasort_canc["pickup_address"] . "</span></span>\r\n                                            <span class='list-item__subtitle'><ons-icon icon='fa-map-marker' size='14px' style='color: red; font-size: 14px;position: absolute;'></ons-icon> <span style='display:inline-block;margin-left:22px;font-weight:bold;'>" . $bookingdatasort_canc["dropoff_address"] . "</span></span>\r\n                                            <hr/>\r\n                                            <span class='list-item__subtitle' style='margin-top: 10px;border-top: thin solid grey;padding-top: 10px;'><ons-button onclick='driveracceptride(" . $bookingdatasort_canc["booking_id"] . ")' style='width:48%;color:white;font-size: 16px;'> ACCEPT</ons-button> <ons-button onclick=drivercancel(" . $bookingdatasort_canc["booking_id"] . ") style='width:48%;color:white;font-size: 16px;background-color:black'> CANCEL</ons-button></span>\r\n                                        \r\n                                        </div> \r\n                                      </ons-list-item>\r\n                                      ";
            }
        }
        foreach ($booking_accepted as $bookingdatasort) {
            $b_date_format = date("l, M j, Y", strtotime($bookingdatasort["date"] . " UTC"));
            $booking_accept_str .= "<ons-list-header style='border-top: thin solid grey;border-bottom: thin solid grey;font-size: 14px;font-weight:bold;'>" . $b_date_format . "</ons-list-header>";
            foreach ($bookingdatasort["accepted"] as $bookingdatasort_canc) {
                $booking_time = date("g:i A", strtotime($bookingdatasort_canc["date_created"] . " UTC"));
                $booking_ptime = date("g:i A", strtotime($bookingdatasort_canc["pickup_datetime"] . " UTC"));
                $booking_pdate_time = date("d/m/Y g:i A", strtotime($bookingdatasort_canc["pickup_datetime"] . " UTC"));
                $booking_payment_type = "";
                $booking_fare = $bookingdatasort_canc["cur_symbol"] . $bookingdatasort_canc["estimated_cost"];
                if(!empty($bookingdatasort_canc["payment_type"])) {
                    if($bookingdatasort_canc["payment_type"] == 1) {
                        $booking_payment_type = "Cash";
                    } elseif($bookingdatasort_canc["payment_type"] == 2) {
                        $booking_payment_type = "Wallet";
                    } else {
                        $booking_payment_type = "Card";
                    }
                }
                $user_photo = explode("/", $bookingdatasort_canc["photo_file"]);
                $user_photo_file = isset($user_photo[5]) ? SITE_URL . "ajaxuserphotofile.php?file=" . $user_photo[5] : SITE_URL . "ajaxuserphotofile.php?file=0";
                $booking_title = str_pad($bookingdatasort_canc["booking_id"], 5, "0", STR_PAD_LEFT);
                $booking_accept_str .= "<ons-list-item id='booking-list-item-" . $bookingdatasort_canc["booking_id"] . "' modifier='longdivider'>\r\n                  \r\n                                            <div class='center'>\r\n                                                    <div style='width:100%;'><span class='list-item__title'>\r\n                                                        <div style='border-bottom: thin solid #ccc;margin-bottom:10px;padding-bottom: 5px;'>" . $booking_time . "</div>\r\n                                                        <div style='display:inline-block;width:80px;height:80px;float:left;margin-bottom:5px;margin-left:20px;background-image:url(" . $user_photo_file . ");background-size:cover;border-radius:50%;margin-right:10px;'></div>\r\n                                                        <div style='display:inline-block;'>\r\n                                                            <p style='margin:0;'>" . $bookingdatasort_canc["user_firstname"] . "</p>\r\n                                                            <p style='margin:0;'><img style='width:80px;' src='img/rating-" . $bookingdatasort_canc["user_rating"] . ".png' /></p>\r\n                                                            <div>\r\n                                                                <ons-button style='border: thin solid;' modifier='outline' onclick=call_rider('" . $bookingdatasort_canc["user_phone"] . "')><ons-icon icon='fa-phone' ></ons-icon></ons-button>\r\n                                                                <ons-button style='border: thin solid;' modifier='outline' onclick=sms_rider('" . $bookingdatasort_canc["user_phone"] . "')><ons-icon icon='fa-envelope' ></ons-icon></ons-button>\r\n                                                                <ons-button style='border: thin solid;color: #24c539;' modifier='outline' onclick=pickupmap(0," . $bookingdatasort_canc["pickup_long"] . "," . $bookingdatasort_canc["pickup_lat"] . ")><ons-icon icon='fa-map-marker' ></ons-icon></ons-button>\r\n                                                                <ons-button style='border: thin solid;color: #24c539;' modifier='outline' onclick=pickupmap(1," . $bookingdatasort_canc["pickup_long"] . "," . $bookingdatasort_canc["pickup_lat"] . ")><ons-icon icon='fa-location-arrow' ></ons-icon></ons-button>\r\n                                                            </div>\r\n                                                        </div>\r\n                                                    </div>\r\n                                                    <span style='text-align: left;margin-bottom: 15px;margin-left:20px;' class='list-item__subtitle'><span>Booking ID:#" . $booking_title . "</span> | <span>Fare:#" . $booking_fare . "</span> <span>(" . $booking_payment_type . ")</span> </span>                               \r\n                                                    \r\n                                                    \r\n                                                    \r\n                                                    <span class='list-item__subtitle' style='margin-bottom:5px;'><ons-icon icon='fa-circle' size='14px' style='color: #24c539; font-size: 14px;position: absolute;'></ons-icon> <span style='display:inline-block;margin-left:22px;font-weight:bold;'>" . $bookingdatasort_canc["pickup_address"] . "</span></span>\r\n                                                    <span class='list-item__subtitle' style='padding-bottom: 10px;border-bottom: thin solid #ccc;margin-bottom: 10px;' ><ons-icon icon='fa-map-marker' size='14px' style='color: red; font-size: 14px;position: absolute;'></ons-icon> <span style='display:inline-block;margin-left:22px;font-weight:bold;'>" . $bookingdatasort_canc["dropoff_address"] . "</span></span>\r\n                                                    <span class='list-item__subtitle'>\r\n                                                        <ons-button style='width:30%;' onclick=driverarrived(" . $bookingdatasort_canc["booking_id"] . ")>Arrived</ons-button>\r\n                                                        <ons-button style='width:30%;' onclick=driverstartride(" . $bookingdatasort_canc["booking_id"] . ")>Onride</ons-button>\r\n                                                        <ons-button style='width:30%;background-color: black;' onclick=drivercancel(" . $bookingdatasort_canc["booking_id"] . ")>Cancel</ons-button>\r\n                                                        \r\n                                                    </span>\r\n                                                    \r\n                                            </div>\r\n                                          \r\n                                          </ons-list-item>";
            }
        }
        if(empty($booking_onride_str)) {
            $booking_onride_str .= "  <div style='width:100%;text-align:center;position: absolute;top: 45%;'> No rides in progress </div> ";
        }
        if(empty($booking_accept_str)) {
            $booking_accept_str .= "  <div style='width:100%;text-align: center;position: absolute;top: 45%;'> No accepted rides.</div> ";
        }
        if(empty($booking_pend_str)) {
            $booking_pend_str .= "  <div style='width:100%;text-align: center;position: absolute;top: 45%;'> No allocated rides. </div> ";
        }
        $data_array = ["success" => 1, "booking_accepted" => $booking_accept_str, "booking_onride" => $booking_onride_str, "booking_pend" => $booking_pend_str];
        echo json_encode($data_array);
        exit;
    } else {
        $error = ["error" => "Error retrieving job records."];
        echo json_encode($error);
        exit;
    }
}
function acceptride()
{
    if(empty($_SESSION["loggedin"])) {
        $error = ["error" => __("Please login to continue")];
        echo json_encode($error);
        exit;
    }
    $booking_id = (int) $_GET["bookingid"];
    $query = sprintf("SELECT * FROM %1\$stbl_bookings WHERE driver_id = %2\$s AND (`status` = 0 OR `status` = 1)", DB_TBL_PREFIX, $_SESSION["uid"]);
    if(($result = mysqli_query($GLOBALS["DB"], $query)) && mysqli_num_rows($result)) {
        $error = ["error" => __("You have a ride in progress. You cannot accept another one until completed")];
        echo json_encode($error);
        exit;
    }
    $query = sprintf("SELECT * FROM %1\$stbl_driver_allocate \r\n    INNER JOIN %1\$stbl_bookings ON %1\$stbl_bookings.id = %1\$stbl_driver_allocate.booking_id \r\n    WHERE %1\$stbl_driver_allocate.booking_id = %2\$d AND (%1\$stbl_bookings.status = 2 OR %1\$stbl_bookings.status = 5 OR %1\$stbl_driver_allocate.status = 1)", DB_TBL_PREFIX, $booking_id);
    if(($result = mysqli_query($GLOBALS["DB"], $query)) && mysqli_num_rows($result)) {
        $error = ["error" => __("This booking allocation has expired. you did not respond on time. Please always respond promptly when a booking is allocated to you.")];
        echo json_encode($error);
        exit;
    }
    $booking_data = [];
    $query = sprintf("SELECT  %1\$stbl_bookings.waypoint1_address,%1\$stbl_bookings.waypoint1_long,%1\$stbl_bookings.waypoint1_lat,%1\$stbl_bookings.waypoint2_address,%1\$stbl_bookings.waypoint2_long,%1\$stbl_bookings.waypoint2_lat,%1\$stbl_bookings.completion_code,%1\$stbl_bookings.cur_exchng_rate,%1\$stbl_bookings.estimated_cost,%1\$stbl_bookings.payment_type,%1\$stbl_users.push_notification_token AS push_token,%1\$stbl_bookings.user_id AS customer_id, %1\$stbl_bookings.pickup_lat, %1\$stbl_bookings.pickup_long, %1\$stbl_bookings.dropoff_lat,%1\$stbl_bookings.dropoff_long, %1\$stbl_users.disp_lang AS u_lang FROM %1\$stbl_bookings \r\n    INNER JOIN %1\$stbl_users ON %1\$stbl_users.user_id = %1\$stbl_bookings.user_id\r\n    WHERE %1\$stbl_bookings.id = \"%2\$d\"", DB_TBL_PREFIX, $booking_id);
    if($result = mysqli_query($GLOBALS["DB"], $query)) {
        if(mysqli_num_rows($result)) {
            $booking_data = mysqli_fetch_assoc($result);
            $rlang = $booking_data["u_lang"];
            $driver_data = [];
            $query = sprintf("SELECT *, %1\$stbl_franchise.id AS franchise_id FROM %1\$stbl_drivers \r\n    LEFT JOIN %1\$stbl_franchise ON %1\$stbl_franchise.id = %1\$stbl_drivers.franchise_id\r\n    LEFT JOIN %1\$stbl_driver_location ON %1\$stbl_driver_location.driver_id = %1\$stbl_drivers.driver_id \r\n    WHERE %1\$stbl_drivers.driver_id = \"%2\$d\"", DB_TBL_PREFIX, $_SESSION["uid"]);
            if(($result = mysqli_query($GLOBALS["DB"], $query)) && mysqli_num_rows($result)) {
                $driver_data = mysqli_fetch_assoc($result);
            }
            if(isset($booking_data["payment_type"]) && $booking_data["payment_type"] == 1) {
                $drv_booking_balance = (double) $booking_data["estimated_cost"] - $booking_data["estimated_cost"] * $driver_data["driver_commision"] / 100;
                $drv_booking_balance_converted = $drv_booking_balance / $booking_data["cur_exchng_rate"];
                $booking_balance = (double) $driver_data["wallet_amount"] - $drv_booking_balance_converted;
                if($booking_balance < DRIVER_MIN_WALLET_BALANCE) {
                    $error = ["error" => __("Insufficient fund in wallet. Please fund your wallet")];
                    echo json_encode($error);
                    exit;
                }
            }
            $url = "https://maps.googleapis.com/maps/api/directions/json?origin=" . $driver_data["lat"] . "," . $driver_data["long"] . "&destination=" . $booking_data["pickup_lat"] . "," . $booking_data["pickup_long"] . "&key=" . GMAP_API_KEY;
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HTTPGET, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            $json_response = curl_exec($curl);
            $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            $response = json_decode($json_response, true);
            $driver_franchise_name = isset($driver_data["franchise_name"]) ? $driver_data["franchise_name"] : "";
            $driver_franchise_commision = isset($driver_data["franchise_commision"]) ? $driver_data["franchise_commision"] : 0;
            $driver_franchise_id = isset($driver_data["franchise_id"]) ? $driver_data["franchise_id"] : 0;
            $query = sprintf("UPDATE %stbl_bookings SET franchise_id = \"%d\", driver_id = \"%d\", driver_firstname = \"%s\",\r\n    driver_lastname = \"%s\", driver_phone = \"%s\",franchise_name = \"%s\",driver_commision = \"%s\",franchise_commision = \"%s\" WHERE id = \"%d\"", DB_TBL_PREFIX, $driver_franchise_id, $_SESSION["uid"], $_SESSION["firstname"], $_SESSION["lastname"], $_SESSION["country_dial_code"] . $_SESSION["phone"], $driver_franchise_name, $driver_data["driver_commision"], $driver_franchise_commision, $booking_id);
            if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
                $error = ["error" => __("An error has occured")];
                echo json_encode($error);
                exit;
            }
            $query = sprintf("UPDATE %stbl_driver_allocate SET `status` = 1 WHERE booking_id = \"%d\" AND driver_id = \"%d\"", DB_TBL_PREFIX, $booking_id, $_SESSION["uid"]);
            if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
                $error = ["error" => __("An error has occured")];
                echo json_encode($error);
                exit;
            }
            $booking_title = str_pad($booking_id, 5, "0", STR_PAD_LEFT);
            $title = WEBSITE_NAME . " - " . __("Driver Assigned", [], "d|" . $rlang);
            $body = __("A driver has been assigned to your ride with booking ID({---1}) and is on his way. You will be contacted shortly", [(string) $booking_title], "d|" . $rlang);
            $device_tokens = $booking_data["push_token"];
            if(!empty($device_tokens)) {
                sendPushNotification($title, $body, $device_tokens);
            }
            $photo_file = isset($driver_data["photo_file"]) ? $driver_data["photo_file"] : "0";
            $title = "";
            $body = "";
            $device_tokens = $booking_data["push_token"];
            $data = ["action" => "driver-assigned", "booking_id" => $booking_id, "driver_id" => $driver_data["driver_id"], "driver_firstname" => $driver_data["firstname"], "driver_phone" => $driver_data["country_dial_code"] . $driver_data["phone"], "driver_platenum" => $driver_data["car_plate_num"], "driver_rating" => $driver_data["driver_rating"], "driver_location_lat" => $driver_data["lat"], "pickup_lat" => $booking_data["pickup_lat"], "pickup_long" => $booking_data["pickup_long"], "dropoff_lat" => $booking_data["dropoff_lat"], "dropoff_long" => $booking_data["dropoff_long"], "driver_location_long" => $driver_data["long"], "driver_carmodel" => $driver_data["car_model"], "driver_carid" => $driver_data["ride_id"], "driver_completed_rides" => $driver_data["completed_rides"], "driver_photo" => SITE_URL . "ajaxphotofile.php?file=" . $photo_file, "completion_code" => $booking_data["completion_code"], "waypoint1_address" => $booking_data["waypoint1_address"], "waypoint1_long" => $booking_data["waypoint1_long"], "waypoint1_lat" => $booking_data["waypoint1_lat"], "waypoint2_address" => $booking_data["waypoint2_address"], "waypoint2_long" => $booking_data["waypoint2_long"], "waypoint2_lat" => $booking_data["waypoint2_lat"]];
            if(!empty($device_tokens)) {
                sendPushNotification($title, $body, $device_tokens, $data);
            }
            sendRealTimeNotification("ridr-" . $booking_data["customer_id"], $data);
            $content = __("A driver has been assigned to your ride with booking ID({---1}) and is on his way. You will be contacted shortly", [(string) $booking_title], "d|" . $rlang);
            $query = sprintf("INSERT INTO %stbl_notifications(person_id,user_type,content,n_type,date_created) VALUES \r\n    (\"%d\",0,\"%s\",0,\"%s\")", DB_TBL_PREFIX, $booking_data["customer_id"], mysqli_real_escape_string($GLOBALS["DB"], $content), gmdate("Y-m-d H:i:s", time()));
            $result = mysqli_query($GLOBALS["DB"], $query);
            $data_array = ["success" => 1, "directions" => $response];
            echo json_encode($data_array);
            exit;
        }
        $error = ["error" => __("An error has occured")];
        echo json_encode($error);
        exit;
    }
    $error = ["error" => __("An error has occured")];
    echo json_encode($error);
    exit;
}
function getdirections()
{
    if(empty($_SESSION["loggedin"])) {
        $data = ["error" => __("Please login to continue")];
        echo json_encode($data);
        exit;
    }
    if(empty($_GET["p-lat"]) || empty($_GET["p-lng"]) || empty($_GET["d-lat"]) || empty($_GET["d-lng"])) {
        $data = ["error" => __("An error has occured")];
        echo json_encode($data);
        exit;
    }
    $url = "https://maps.googleapis.com/maps/api/directions/json?origin=" . $_GET["p-lat"] . "," . $_GET["p-lng"] . "&destination=" . $_GET["d-lat"] . "," . $_GET["d-lng"] . "&key=" . GMAP_API_KEY;
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPGET, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    $json_response = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    $response = json_decode($json_response, true);
    if(json_last_error()) {
        $data = ["error" => __("An error has occured")];
        echo json_encode($data);
        exit;
    }
    $data = ["success" => 1, "direction_details" => $response];
    echo json_encode($data);
    exit;
}
function walletwithdraw()
{
    if(empty($_SESSION["loggedin"])) {
        $error = ["error" => __("Please login to continue")];
        echo json_encode($error);
        exit;
    }
    $driver_wallet_details = [];
    $amount = (double) $_POST["amount"];
    if(empty($amount) || $amount < 0) {
        $error = ["error" => __("You have entered an incorrect amount")];
        echo json_encode($error);
        exit;
    }
    $query = sprintf("SELECT * FROM %stbl_wallet_withdrawal WHERE person_id = \"%d\" AND user_type = 0 AND request_status = 0", DB_TBL_PREFIX, $_SESSION["uid"]);
    if($result = mysqli_query($GLOBALS["DB"], $query)) {
        if(mysqli_num_rows($result)) {
            $row = mysqli_fetch_assoc($result);
            $withdrawal_date = date("l, M j, Y H:i:s", strtotime($row["date_requested"] . " UTC"));
            $error = ["error" => __("You already have a pending withdrawal request of {---1} requested on - {---2}", [$row["cur_symbol"] . $row["withdrawal_amount"], (string) $withdrawal_date])];
            echo json_encode($error);
            exit;
        }
        $query = sprintf("SELECT %1\$stbl_drivers.wallet_amount,%1\$stbl_currencies.symbol,%1\$stbl_currencies.iso_code,%1\$stbl_currencies.exchng_rate FROM %1\$stbl_drivers \r\n    LEFT JOIN %1\$stbl_routes ON %1\$stbl_routes.id = %1\$stbl_drivers.route_id\r\n    LEFT JOIN %1\$stbl_currencies ON %1\$stbl_currencies.id = %1\$stbl_routes.city_currency_id\r\n    WHERE %1\$stbl_drivers.driver_id = %2\$d", DB_TBL_PREFIX, $_SESSION["uid"]);
        if($result = mysqli_query($GLOBALS["DB"], $query)) {
            if(mysqli_num_rows($result)) {
                $driver_wallet_details = mysqli_fetch_assoc($result);
                $amount_converted = $amount / $driver_wallet_details["exchng_rate"];
                if(isset($driver_wallet_details["wallet_amount"])) {
                    $balance = (double) $driver_wallet_details["wallet_amount"] - DRIVER_WITHDRAL_MIN_BALANCE - $amount_converted;
                    if(empty($balance) || $balance < 0) {
                        $error = ["error" => __("Insufficient amount in wallet! You cannot withraw the requested amount")];
                        echo json_encode($error);
                        exit;
                    }
                    $query = sprintf("UPDATE %stbl_drivers SET wallet_amount = wallet_amount - %f WHERE driver_id = \"%d\"", DB_TBL_PREFIX, $amount_converted, $_SESSION["uid"]);
                    $result = mysqli_query($GLOBALS["DB"], $query);
                    $query = sprintf("INSERT INTO %stbl_wallet_withdrawal(cur_symbol,cur_exchng_rate,cur_code,person_id,wallet_amount,wallet_balance,withdrawal_amount,date_requested) VALUES \r\n    (\"%s\",\"%s\",\"%s\",\"%d\",\"%f\",\"%f\",\"%f\",\"%s\")", DB_TBL_PREFIX, $driver_wallet_details["symbol"], $driver_wallet_details["exchng_rate"], $driver_wallet_details["iso_code"], $_SESSION["uid"], $driver_wallet_details["wallet_amount"], $driver_wallet_details["wallet_amount"] - $amount_converted, $amount, gmdate("Y-m-d H:i:s", time()));
                    $result = mysqli_query($GLOBALS["DB"], $query);
                    $transaction_id = crypto_string();
                    $query = sprintf("INSERT INTO %stbl_wallet_transactions (transaction_id,amount,cur_symbol,cur_exchng_rate,cur_code,wallet_balance,`user_id`,user_type,`desc`,`type`,transaction_date) VALUES \r\n    (\"%s\",\"%f\",\"%s\",\"%s\",\"%s\",\"%f\",\"%d\",\"%d\",\"%s\",\"%d\",\"%s\")", DB_TBL_PREFIX, $transaction_id, $amount, $driver_wallet_details["symbol"], $driver_wallet_details["exchng_rate"], $driver_wallet_details["iso_code"], $driver_wallet_details["wallet_amount"] - $amount_converted, $_SESSION["uid"], 1, __("Withdrawal request amount debit"), 3, gmdate("Y-m-d H:i:s", time()));
                    $result = mysqli_query($GLOBALS["DB"], $query);
                    $data_array = ["success" => 1];
                    echo json_encode($data_array);
                    exit;
                }
                $error = ["error" => __("Insufficient amount in wallet!")];
                echo json_encode($error);
                exit;
            }
            $error = ["error" => __("An error has occured")];
            echo json_encode($error);
            exit;
        }
        $error = ["error" => __("An error has occured")];
        echo json_encode($error);
        exit;
    }
    $error = ["error" => __("An error has occured")];
    echo json_encode($error);
    exit;
}
function startride()
{
    if(empty($_SESSION["loggedin"])) {
        $error = ["error" => __("Please login to continue")];
        echo json_encode($error);
        exit;
    }
    $booking_id = (int) $_GET["bookingid"];
    $query = sprintf("SELECT * FROM %stbl_bookings WHERE `status` = 1 AND driver_id = \"%d\"", DB_TBL_PREFIX, $_SESSION["uid"]);
    if(($result = mysqli_query($GLOBALS["DB"], $query)) && mysqli_num_rows($result)) {
        $error = ["error" => __("You have a ride in progress. You cannot start another one")];
        echo json_encode($error);
        exit;
    }
    $query = sprintf("UPDATE %stbl_bookings SET `status` = 1,date_started = \"%s\" \r\n    WHERE id = \"%d\" AND driver_id = \"%d\"", DB_TBL_PREFIX, gmdate("Y-m-d H:i:s", time()), $booking_id, $_SESSION["uid"]);
    if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
        $error = ["error" => __("An error has occured")];
        echo json_encode($error);
        exit;
    }
    $book_data = [];
    $query = sprintf("SELECT %1\$stbl_bookings.waypoint1_address,%1\$stbl_bookings.waypoint1_long,%1\$stbl_bookings.waypoint1_lat,%1\$stbl_bookings.waypoint2_address,%1\$stbl_bookings.waypoint2_long,%1\$stbl_bookings.waypoint2_lat,%1\$stbl_drivers.*,%1\$stbl_drivers.country_dial_code AS drv_country_dial_code,%1\$stbl_drivers.completed_rides AS compl_rides,%1\$stbl_drivers.ride_id AS driver_ride_id,%1\$stbl_bookings.completion_code,%1\$stbl_drivers.photo_file AS driver_photo,%1\$stbl_driver_location.lat,%1\$stbl_driver_location.long,%1\$stbl_users.push_notification_token AS user_push_token, %1\$stbl_users.user_id AS customer_id, %1\$stbl_bookings.pickup_lat, %1\$stbl_bookings.pickup_long, %1\$stbl_bookings.dropoff_lat,%1\$stbl_bookings.dropoff_long, %1\$stbl_users.disp_lang AS u_lang FROM %1\$stbl_bookings\r\n    INNER JOIN %1\$stbl_users ON %1\$stbl_users.user_id = %1\$stbl_bookings.user_id\r\n    INNER JOIN %1\$stbl_drivers ON %1\$stbl_drivers.driver_id = %1\$stbl_bookings.driver_id \r\n    LEFT JOIN %1\$stbl_driver_location ON %1\$stbl_driver_location.driver_id = %1\$stbl_drivers.driver_id \r\n    WHERE %1\$stbl_bookings.id = \"%3\$d\" AND %1\$stbl_drivers.driver_id = \"%2\$d\"", DB_TBL_PREFIX, $_SESSION["uid"], $booking_id);
    if(($result = mysqli_query($GLOBALS["DB"], $query)) && mysqli_num_rows($result)) {
        $book_data = mysqli_fetch_assoc($result);
    }
    $rlang = $book_data["u_lang"];
    $waypoints_str = "";
    $waypoint1 = "";
    $waypoint2 = "";
    if(!empty($book_data["waypoint1_address"])) {
        $waypoint1 = $book_data["waypoint1_lat"] . "," . $book_data["waypoint1_long"];
    }
    if(!empty($book_data["waypoint2_address"])) {
        $waypoint2 = $book_data["waypoint2_lat"] . "," . $book_data["waypoint2_long"];
    }
    if(!empty($waypoint1) && !empty($waypoint2)) {
        $waypoints_str = "&waypoints=via:" . $waypoint1 . "|" . $waypoint2;
    } elseif(!empty($waypoint1)) {
        $waypoints_str = "&waypoints=via:" . $waypoint1;
    } elseif(!empty($waypoint2)) {
        $waypoints_str = "&waypoints=via:" . $waypoint2;
    }
    $url = "https://maps.googleapis.com/maps/api/directions/json?origin=" . $book_data["lat"] . "," . $book_data["long"] . "&destination=" . $book_data["dropoff_lat"] . "," . $book_data["dropoff_long"] . "&key=" . GMAP_API_KEY . $waypoints_str;
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPGET, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    $json_response = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    $response = json_decode($json_response, true);
    $booking_title = str_pad($booking_id, 5, "0", STR_PAD_LEFT);
    $content = __("Your ride with booking ID {---1} has started", ["#" . $booking_title], "d|" . $rlang);
    $query = sprintf("INSERT INTO %stbl_notifications(person_id,user_type,content,n_type,date_created) VALUES \r\n    (\"%d\",0,\"%s\",0,\"%s\")", DB_TBL_PREFIX, $book_data["customer_id"], mysqli_real_escape_string($GLOBALS["DB"], $content), gmdate("Y-m-d H:i:s", time()));
    $result = mysqli_query($GLOBALS["DB"], $query);
    $photo_file = isset($book_data["driver_photo"]) ? $book_data["driver_photo"] : "0";
    $title = "";
    $body = "";
    $device_tokens = $book_data["user_push_token"];
    $data = ["action" => "customer-onride", "booking_id" => $booking_id, "driver_id" => $book_data["driver_id"], "driver_firstname" => $book_data["firstname"], "driver_phone" => $book_data["drv_country_dial_code"] . $book_data["phone"], "driver_platenum" => $book_data["car_plate_num"], "driver_rating" => $book_data["driver_rating"], "driver_location_lat" => $book_data["lat"], "pickup_lat" => $book_data["pickup_lat"], "pickup_long" => $book_data["pickup_long"], "dropoff_lat" => $book_data["dropoff_lat"], "dropoff_long" => $book_data["dropoff_long"], "driver_location_long" => $book_data["long"], "driver_carmodel" => $book_data["car_model"], "driver_carid" => $book_data["driver_ride_id"], "driver_completed_rides" => $book_data["compl_rides"], "completion_code" => $book_data["completion_code"], "driver_photo" => SITE_URL . "ajaxphotofile.php?file=" . $photo_file];
    if(!empty($device_tokens)) {
        sendPushNotification($title, $body, $device_tokens, $data);
    }
    sendRealTimeNotification("ridr-" . $book_data["customer_id"], $data);
    $data_array = ["success" => 1, "directions" => $response];
    echo json_encode($data_array);
    exit;
}
function gettodaytimeonline()
{
    if(empty($_SESSION["loggedin"])) {
        return 0;
    }
    $todays_date = date("Y-m-d", time());
    if(!isset($_SESSION["availability"]) || $_SESSION["availability"] == 0) {
        if(isset($_SESSION["time_online"][$todays_date]["last_time"])) {
            return $_SESSION["time_online"][$todays_date]["total_time"];
        }
        return 0;
    }
    if(isset($_SESSION["time_online"][$todays_date]["last_time"])) {
        $time_difference = time() - $_SESSION["time_online"][$todays_date]["last_time"];
        if(DRIVER_LOCATION_UPDATE_INTERVAL * 3 < $time_difference) {
            $_SESSION["time_online"][$todays_date]["last_time"] = time();
            return $_SESSION["time_online"][$todays_date]["total_time"];
        }
        $_SESSION["time_online"][$todays_date]["last_time"] = time();
        return $_SESSION["time_online"][$todays_date]["total_time"] += $time_difference;
    }
    unset($_SESSION["time_online"]);
    $_SESSION["time_online"][$todays_date]["last_time"] = time();
    $_SESSION["time_online"][$todays_date]["total_time"] = time() - $_SESSION["time_online"][$todays_date]["last_time"];
    return 0;
}
function getavailablecitydrivers()
{
    if(empty($_SESSION["loggedin"])) {
        $error = ["error" => "Please re-login and retry."];
        echo json_encode($error);
        exit;
    }
    $driver_today_earnings = 0;
    $completed_trips_today = 0;
    $query = sprintf("SELECT %1\$stbl_bookings.actual_cost, %1\$stbl_bookings.cur_exchng_rate, %1\$stbl_bookings.driver_commision FROM %1\$stbl_bookings\r\n    WHERE %1\$stbl_bookings.driver_id = %2\$d AND %1\$stbl_bookings.status = 3 AND DATE(%1\$stbl_bookings.date_completed) = \"%3\$s\"", DB_TBL_PREFIX, $_SESSION["uid"], date("Y-m-d", time()));
    if(($result = mysqli_query($GLOBALS["DB"], $query)) && mysqli_num_rows($result)) {
        while ($row = mysqli_fetch_assoc($result)) {
            $completed_trips_today++;
            $driver_today_earnings += (double) $row["actual_cost"] / $row["cur_exchng_rate"] * $row["driver_commision"] / 100;
        }
    }
    $city = (int) $_GET["city"];
    $priority_driver = 0;
    $drivers_location_data = [];
    $location_info_age = gmdate("Y-m-d H:i:s", time() - LOCATION_INFO_VALID_AGE);
    $query = sprintf("SELECT %1\$stbl_driver_location.*,%1\$stbl_drivers.firstname, %1\$stbl_drivers.ride_id,%1\$stbl_rides.ride_type FROM %1\$stbl_driver_location \r\n    INNER JOIN %1\$stbl_drivers ON %1\$stbl_driver_location.driver_id = %1\$stbl_drivers.driver_id\r\n    INNER JOIN %1\$stbl_rides ON %1\$stbl_rides.id = %1\$stbl_drivers.ride_id\r\n    WHERE %1\$stbl_drivers.driver_id != %4\$d AND %1\$stbl_drivers.route_id = %2\$d AND %1\$stbl_drivers.is_activated = 1 AND %1\$stbl_drivers.available = 1 AND %1\$stbl_driver_location.location_date > \"%3\$s\" LIMIT 300", DB_TBL_PREFIX, $city, $location_info_age, $_SESSION["uid"]);
    if(!empty($priority_driver)) {
        $query = sprintf("SELECT %1\$stbl_driver_location.*,%1\$stbl_drivers.firstname, %1\$stbl_drivers.ride_id,%1\$stbl_rides.ride_type FROM %1\$stbl_driver_location \r\n        INNER JOIN %1\$stbl_drivers ON %1\$stbl_driver_location.driver_id = %1\$stbl_drivers.driver_id\r\n        INNER JOIN %1\$stbl_rides ON %1\$stbl_rides.id = %1\$stbl_drivers.ride_id\r\n        WHERE %1\$stbl_drivers.driver_id = %2\$d", DB_TBL_PREFIX, $priority_driver);
    }
    if($result = mysqli_query($GLOBALS["DB"], $query)) {
        if(mysqli_num_rows($result)) {
            $count = 0;
            $ride_types = [];
            for ($color_num = 0; $row = mysqli_fetch_assoc($result); $count++) {
                $drivers_location_data[$count]["position"]["lat"] = $row["lat"];
                $drivers_location_data[$count]["position"]["lng"] = $row["long"];
                $drivers_location_data[$count]["disableAutoPan"] = true;
                if(!array_key_exists($row["ride_id"], $ride_types)) {
                    $color_num++;
                    if(5 < $color_num) {
                        $color_num = 1;
                    }
                    $ride_types[$row["ride_id"]] = empty($priority_driver) ? "img/city-driver-icon-" . $color_num . ".png" : "img/driver-marker-icon.png";
                    $drivers_location_data[$count]["icon"]["url"] = "img/city-driver-icon-" . $color_num . ".png";
                    $drivers_location_data[$count]["title"] = empty($priority_driver) ? $row["ride_type"] : "Your Driver " . $row["firstname"];
                    $drivers_location_data[$count]["driver_id"] = $row["driver_id"];
                } else {
                    $drivers_location_data[$count]["icon"]["url"] = $ride_types[$row["ride_id"]];
                    $drivers_location_data[$count]["title"] = empty($priority_driver) ? $row["ride_type"] : "Your Driver " . $row["firstname"];
                    $drivers_location_data[$count]["driver_id"] = $row["driver_id"];
                }
            }
            mysqli_free_result($result);
            $data_array = ["success" => 1, "drivers_locations" => $drivers_location_data, "driver_today_earning" => $driver_today_earnings, "completed_trips" => $completed_trips_today];
            echo json_encode($data_array);
            exit;
        }
        $data_array = ["success" => 1, "drivers_locations" => $drivers_location_data, "driver_today_earning" => $driver_today_earnings, "completed_trips" => $completed_trips_today];
        echo json_encode($data_array);
        exit;
    }
    $error = ["error" => "Database error."];
    echo json_encode($error);
    exit;
}
function driverarrived()
{
    if(empty($_SESSION["loggedin"])) {
        $error = ["error" => __("Please login to continue")];
        echo json_encode($error);
        exit;
    }
    $booking_id = (int) $_GET["bookingid"];
    if(empty($_SESSION["arrived_set"][$booking_id])) {
        $query = sprintf("UPDATE %stbl_bookings SET /* `status` = 1, */date_arrived = \"%s\" \r\n        WHERE id = \"%d\" AND driver_id = \"%d\"", DB_TBL_PREFIX, gmdate("Y-m-d H:i:s", time()), $booking_id, $_SESSION["uid"]);
        if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
        } else {
            $_SESSION["arrived_set"][$booking_id] = 1;
        }
    }
    $book_data = [];
    $query = sprintf("SELECT %1\$stbl_drivers.*,%1\$stbl_drivers.country_dial_code AS drv_country_dial_code,%1\$stbl_drivers.completed_rides AS compl_rides,%1\$stbl_drivers.ride_id AS driver_ride_id,%1\$stbl_bookings.completion_code,%1\$stbl_drivers.photo_file AS driver_photo,%1\$stbl_driver_location.lat,%1\$stbl_driver_location.long,%1\$stbl_users.push_notification_token AS user_push_token,%1\$stbl_users.user_id AS customer_id, %1\$stbl_bookings.pickup_lat, %1\$stbl_bookings.pickup_long, %1\$stbl_bookings.dropoff_lat,%1\$stbl_bookings.dropoff_long,%1\$stbl_users.disp_lang AS u_lang FROM %1\$stbl_bookings\r\n    INNER JOIN %1\$stbl_users ON %1\$stbl_users.user_id = %1\$stbl_bookings.user_id\r\n    INNER JOIN %1\$stbl_drivers ON %1\$stbl_drivers.driver_id = %1\$stbl_bookings.driver_id \r\n    LEFT JOIN %1\$stbl_driver_location ON %1\$stbl_driver_location.driver_id = %1\$stbl_drivers.driver_id \r\n    WHERE %1\$stbl_bookings.id = \"%3\$d\" AND %1\$stbl_drivers.driver_id = \"%2\$d\"", DB_TBL_PREFIX, $_SESSION["uid"], $booking_id);
    if(($result = mysqli_query($GLOBALS["DB"], $query)) && mysqli_num_rows($result)) {
        $book_data = mysqli_fetch_assoc($result);
    }
    $rlang = $book_data["u_lang"];
    $photo_file = isset($book_data["driver_photo"]) ? $book_data["driver_photo"] : "0";
    $title = "";
    $body = "";
    $device_tokens = $book_data["user_push_token"];
    $data = ["action" => "driver-arrived", "booking_id" => $booking_id, "driver_id" => $book_data["driver_id"], "driver_firstname" => $book_data["firstname"], "driver_phone" => $book_data["drv_country_dial_code"] . $book_data["phone"], "driver_platenum" => $book_data["car_plate_num"], "driver_rating" => $book_data["driver_rating"], "driver_location_lat" => $book_data["lat"], "driver_location_long" => $book_data["long"], "pickup_lat" => $book_data["pickup_lat"], "pickup_long" => $book_data["pickup_long"], "dropoff_lat" => $book_data["dropoff_lat"], "dropoff_long" => $book_data["dropoff_long"], "driver_carmodel" => $book_data["car_model"], "driver_carid" => $book_data["driver_ride_id"], "driver_completed_rides" => $book_data["compl_rides"], "completion_code" => $book_data["completion_code"], "driver_photo" => SITE_URL . "ajaxphotofile.php?file=" . $photo_file];
    if(!empty($device_tokens)) {
        sendPushNotification($title, $body, $device_tokens, $data);
        $booking_title = str_pad($booking_id, 5, "0", STR_PAD_LEFT);
        $title = WEBSITE_NAME . " - " . __("Driver has arrived", [], "d|" . $rlang);
        $body = __("Your driver for booking ID({---1}) has arrived. Please meet him immediately", [(string) $booking_title], "d|" . $rlang);
        sendPushNotification($title, $body, $device_tokens);
    }
    sendRealTimeNotification("ridr-" . $book_data["customer_id"], $data);
    $data_array = ["success" => 1];
    echo json_encode($data_array);
    exit;
}
function getreferralsdata()
{
    if(empty($_SESSION["loggedin"])) {
        $error = ["error" => __("Please login to continue")];
        echo json_encode($error);
        exit;
    }
    $referrals_data = [];
    $referrals_data_html = "";
    $query = sprintf("SELECT %1\$stbl_drivers.driver_id,%1\$stbl_referral_drivers.number_of_rides,%1\$stbl_referral_drivers.beneficiary,%1\$stbl_referral_drivers.driver_incentive,%1\$stbl_drivers.photo_file,%1\$stbl_drivers.referral_task_progress, %1\$stbl_drivers.firstname, %1\$stbl_drivers.lastname, %1\$stbl_drivers.referral_target_status, DATE(%1\$stbl_drivers.account_create_date) AS account_date FROM %1\$stbl_drivers\r\n    INNER JOIN %1\$stbl_referral_drivers ON %1\$stbl_referral_drivers.route_id = %1\$stbl_drivers.reg_route_id\r\n    WHERE %1\$stbl_drivers.reg_with_referal_code = \"%2\$s\" AND %1\$stbl_referral_drivers.status = 1 ORDER BY %1\$stbl_drivers.account_create_date DESC LIMIT 100", DB_TBL_PREFIX, $_SESSION["ref_code"]);
    if($result = mysqli_query($GLOBALS["DB"], $query)) {
        if(mysqli_num_rows($result)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $referrals_data[$row["account_date"]][] = $row;
            }
            foreach ($referrals_data as $key => $val) {
                $referrals_data_html .= "<ons-list-header style='border-top:thin solid #ccc;border-bottom:thin solid #ccc;'>" . $key . "</ons-list-header>";
                foreach ($val as $ref_driver) {
                    $driver_name = $ref_driver["firstname"] . " " . $ref_driver["lastname"];
                    $photo_file = isset($ref_driver["photo_file"]) ? $ref_driver["photo_file"] : "0";
                    $driver_photo = SITE_URL . "ajaxphotofile.php?file=" . $photo_file;
                    $progress_bar_color = "#2196f3";
                    $progress_bar_width = "1%";
                    $ref_progress_percent = 0;
                    $status = __("Task in progress");
                    $ref_progress_text = __("{---1} trips completed", ["0/" . $ref_driver["number_of_rides"]]);
                    if(!empty($ref_driver["referral_task_progress"])) {
                        if($ref_driver["number_of_rides"] < $ref_driver["referral_task_progress"]) {
                            $ref_driver["referral_task_progress"] = $ref_driver["number_of_rides"];
                        }
                        $ref_progress_percent = ceil($ref_driver["referral_task_progress"] / $ref_driver["number_of_rides"] * 100);
                        $ref_progress_percent = 100 < $ref_progress_percent ? 100 : $ref_progress_percent;
                        $progress_bar_width = $ref_progress_percent . "%";
                        $ref_progress_text = __("{---1} trips completed", [$ref_driver["referral_task_progress"] . "/" . $ref_driver["number_of_rides"]]);
                    }
                    if($ref_driver["referral_target_status"] == 1) {
                        $progress_bar_color = "#4caf50";
                        $status = __("Task completed");
                    } elseif($ref_driver["referral_target_status"] == 2) {
                        $progress_bar_color = "#f44336";
                        $status = __("Task not completed");
                    }
                    $referrals_data_html .= "<ons-list-item modifier='longdivider'>\r\n                                        <div class='left'><img id='ref-driver-img-" . $ref_driver["driver_id"] . "' src='img/driver.png' style='width:48px;border-radius:50%;' /></div>\r\n                                        <div class='center' style='padding-right: 20px;'>\r\n                                            <div style='width:100%'><span class='list-item__title' style='font-weight:bold;'>" . $driver_name . "</span></div>\r\n                                            <div style='width:100%;text-align:right;'>" . $status . "</div>\r\n                                            <div style='border-radius: 10px;border:2px solid #white;padding:2px;margin-top:10px;margin-bottom:10px;width:100%;background-color:black;'><div style='width:" . $progress_bar_width . ";height:5px;background-color:" . $progress_bar_color . ";border-radius: 10px;'></div></div>\r\n                                            <div style='width:100%;margin-top:5px;'><span class='list-item__subtitle' style='width:60%;float:left'>" . $ref_progress_text . "</span> <span class='list-item__subtitle' style='width:30%;float:right;text-align:right;'>" . $ref_progress_percent . "%</span></div>\r\n                                            <div style='clear:both;'></div><img style='display:none' src='" . $driver_photo . "' onload='(function(el){\$(\"#ref-driver-img-" . $ref_driver["driver_id"] . "\").attr(\"src\",el.src)})(this)' />\r\n                                        </div>\r\n                                    </ons-list-item>";
                }
            }
            $data = ["success" => 1, "referrals_data" => "<ons-list>" . $referrals_data_html . "</ons-list>"];
            echo json_encode($data);
            exit;
        } else {
            $data = ["success" => 1, "referrals_data" => "<div class='center-screen'><p style='text-align:center;'>" . __("You have no referrals. Invite drivers to Droptaxi and earn a commission when your invited drivers register with your referral code and complete a task. You will be able to track their progress here") . "</p></div>"];
            echo json_encode($data);
            exit;
        }
    } else {
        $error = ["error" => __("An error has occured")];
        echo json_encode($error);
        exit;
    }
}
function promocheck()
{
    if(empty($_SESSION["loggedin"])) {
        $error = ["error" => __("Please login to continue")];
        echo json_encode($error);
        exit;
    }
    $promo_data = [];
    $promo_data_html = "";
    $query = sprintf("SELECT %1\$stbl_currencies.symbol, %1\$stbl_referral_drivers.invitee_incentive,%1\$stbl_routes.r_title,%1\$stbl_referral_drivers.number_of_rides,%1\$stbl_referral_drivers.number_of_days,%1\$stbl_drivers.reg_with_referal_code,%1\$stbl_drivers.referral_task_progress, %1\$stbl_drivers.referral_target_status,%1\$stbl_drivers.driver_id, %1\$stbl_drivers.reg_route_id FROM %1\$stbl_drivers\r\n    INNER JOIN %1\$stbl_referral_drivers ON %1\$stbl_referral_drivers.route_id = %1\$stbl_drivers.reg_route_id\r\n    INNER JOIN %1\$stbl_routes ON %1\$stbl_routes.id = %1\$stbl_drivers.reg_route_id\r\n    INNER JOIN %1\$stbl_currencies ON %1\$stbl_currencies.id = %1\$stbl_routes.city_currency_id\r\n    WHERE %1\$stbl_drivers.driver_id = %2\$d AND %1\$stbl_drivers.reg_with_referal_code != \"\" AND %1\$stbl_referral_drivers.status = %3\$d AND (%1\$stbl_referral_drivers.beneficiary = 0 OR %1\$stbl_referral_drivers.beneficiary = 2)", DB_TBL_PREFIX, $_SESSION["uid"], 1);
    if($result = mysqli_query($GLOBALS["DB"], $query)) {
        if(mysqli_num_rows($result)) {
            $promo_data = mysqli_fetch_assoc($result);
            $query = sprintf("SELECT id, DATE(date_completed) AS date_comp FROM %stbl_bookings WHERE driver_id = %d AND `status` = %d AND route_id = %d ORDER BY id ASC LIMIT %d", DB_TBL_PREFIX, $_SESSION["uid"], 3, $promo_data["reg_route_id"], $promo_data["number_of_rides"]);
            if($result = mysqli_query($GLOBALS["DB"], $query)) {
                if(mysqli_num_rows($result)) {
                    $driver_bookings = [];
                    while ($row = mysqli_fetch_assoc($result)) {
                        if(!empty($row)) {
                            $driver_bookings[] = $row;
                        }
                    }
                    $start_date = $driver_bookings[0]["date_comp"];
                    $progress_bar_color = "#2196f3";
                    $progress_bar_width = "1%";
                    $ref_progress_percent = 0;
                    $status = __("Task in progress");
                    $days_left_text = "";
                    $ref_progress_text = __("{---1} trips completed", ["0/" . $promo_data["number_of_rides"]]);
                    if(!empty($promo_data["referral_task_progress"])) {
                        if($promo_data["number_of_rides"] < $promo_data["referral_task_progress"]) {
                            $promo_data["referral_task_progress"] = $promo_data["number_of_rides"];
                        }
                        $ref_progress_percent = ceil($promo_data["referral_task_progress"] / $promo_data["number_of_rides"] * 100);
                        $ref_progress_percent = 100 < $ref_progress_percent ? 100 : $ref_progress_percent;
                        $progress_bar_width = $ref_progress_percent . "%";
                        $ref_progress_text = __("{---1} trips completed", [$promo_data["referral_task_progress"] . "/" . $promo_data["number_of_rides"]]);
                    }
                    if($promo_data["referral_target_status"] == 1) {
                        $progress_bar_color = "#4caf50";
                        $status = __("Task completed");
                    } elseif($promo_data["referral_target_status"] == 2) {
                        $progress_bar_color = "#f44336";
                        $status = __("Task not completed");
                    }
                    if(isValidDate($start_date)) {
                        $time_duration_sec = time() - strtotime($start_date);
                        $days_taken = intval($time_duration_sec / 86400);
                        $days_left = $promo_data["number_of_days"] - $days_taken;
                        if($days_left < 0) {
                            $days_left = 0;
                        }
                        if(empty($days_left)) {
                            $progress_bar_color = "#f44336";
                            $status = __("Task not completed");
                        }
                        $days_left_text = " | " . __("{---1} days left", [(string) $days_left]);
                    }
                    $promo_data_html .= "<div style='padding:10px;margin-top: 10px;margin-bottom:10px;background: linear-gradient(90deg, rgba(2,0,36,1) 0%, rgba(9,9,121,1) 27%, rgba(0,212,255,1) 100%);border-radius: 10px;box-shadow: 1px 1px 2px 0px grey;'>\r\n                                            \r\n                                            <p id='promo-desc' style='color: #fff;font-size: 16px;font-weight: bold;margin:0 0 15px 0;'>" . __("Earn {---1} when you complete {---2} trips in {---3} days", [$promo_data["symbol"] . $promo_data["invitee_incentive"], (string) $promo_data["number_of_rides"], (string) $promo_data["number_of_days"]]) . "</p>\r\n                                            <div style='border-radius: 10px;border:2px solid #white;padding:2px;margin-top:10px;margin-bottom:10px;width:100%;background-color:black;'><div style='width:" . $progress_bar_width . ";height:5px;background-color:" . $progress_bar_color . ";border-radius: 10px;'></div></div>\r\n                                            <p style='margin:0 0 10px 0;font-size:12px;color: #bdbdbd;' id='promo-usage'>" . $ref_progress_text . $days_left_text . "</p>\r\n                                            <p style='margin:0 0 10px 0;font-size:12px;color: #bdbdbd;' id='promo-status'>" . $status . "</p>\r\n                                            <hr>\r\n                                            <p style='margin:0;font-size:12px;color: #bdbdbd;' id='promo-city'>" . $promo_data["r_title"] . "</p>\r\n      \r\n                                        </div>";
                    $data = ["success" => 1, "promodata" => $promo_data_html];
                    echo json_encode($data);
                    exit;
                }
                $progress_bar_color = "#2196f3";
                $progress_bar_width = "1%";
                $ref_progress_percent = 0;
                $status = __("Task in progress");
                $days_left = "";
                $ref_progress_text = __("{---1} trips completed", ["0/" . $promo_data["number_of_rides"]]);
                if(!empty($promo_data["referral_task_progress"])) {
                    if($promo_data["number_of_rides"] < $promo_data["referral_task_progress"]) {
                        $promo_data["referral_task_progress"] = $promo_data["number_of_rides"];
                    }
                    $ref_progress_percent = ceil($promo_data["referral_task_progress"] / $promo_data["number_of_rides"] * 100);
                    $ref_progress_percent = 100 < $ref_progress_percent ? 100 : $ref_progress_percent;
                    $progress_bar_width = $ref_progress_percent . "%";
                    $ref_progress_text = __("{---1} trips completed", [$promo_data["referral_task_progress"] . "/" . $promo_data["number_of_rides"]]);
                }
                if($promo_data["referral_target_status"] == 1) {
                    $progress_bar_color = "#4caf50";
                    $status = __("Task completed");
                } elseif($promo_data["referral_target_status"] == 2) {
                    $progress_bar_color = "#f44336";
                    $status = __("Task not completed");
                }
                $promo_data_html .= "<div style='padding:10px;margin-top: 10px;margin-bottom:10px;background: linear-gradient(90deg, rgba(2,0,36,1) 0%, rgba(9,9,121,1) 27%, rgba(0,212,255,1) 100%);border-radius: 10px;box-shadow: 1px 1px 2px 0px grey;'>\r\n                                            \r\n                                            <p id='promo-desc' style='color: #fff;font-size: 16px;font-weight: bold;margin:0 0 15px 0;'>" . __("Earn {---1} when you complete {---2} trips in {---3} days", [$promo_data["symbol"] . $promo_data["invitee_incentive"], (string) $promo_data["number_of_rides"], (string) $promo_data["number_of_days"]]) . "</p>\r\n                                            <div style='border-radius: 10px;border:2px solid #white;padding:2px;margin-top:10px;margin-bottom:10px;width:100%;background-color:black;'><div style='width:" . $progress_bar_width . ";height:5px;background-color:" . $progress_bar_color . ";border-radius: 10px;'></div></div>\r\n                                            <p style='margin:0 0 10px 0;font-size:12px;color: #bdbdbd;' id='promo-usage'>" . $ref_progress_text . "</p>\r\n                                            <p style='margin:0 0 10px 0;font-size:12px;color: #bdbdbd;' id='promo-status'>" . $status . "</p>\r\n                                            <hr>\r\n                                            <p style='margin:0;font-size:12px;color: #bdbdbd;' id='promo-city'>" . $promo_data["r_title"] . "</p>\r\n      \r\n                                        </div>";
                $data = ["success" => 1, "promodata" => $promo_data_html];
                echo json_encode($data);
                exit;
            }
            $error = ["error" => __("An error has occured")];
            echo json_encode($error);
            exit;
        }
        $promo_data_html = "<div class='center-screen'><p style='text-align:center;'>" . __("You have no active promotions") . "</p></div>";
        $data = ["success" => 1, "promodata" => $promo_data_html];
        echo json_encode($data);
        exit;
    }
    $error = ["error" => __("An error has occured")];
    echo json_encode($error);
    exit;
}
function bookingcancel()
{
    if(empty($_SESSION["loggedin"])) {
        $error = ["error" => __("Please login to continue")];
        echo json_encode($error);
        exit;
    }
    $booking_id = (int) $_GET["bookingid"];
    if(empty($booking_id)) {
        $error = ["error" => "Invalid Data."];
        echo json_encode($error);
        exit;
    }
    $comment = mysqli_real_escape_string($GLOBALS["DB"], $_GET["comment"]);
    $book_data = [];
    $query = sprintf("SELECT %1\$stbl_bookings.status AS booking_status,%1\$stbl_drivers.*,%1\$stbl_users.push_notification_token AS user_push_token, %1\$stbl_users.user_id AS customer_id, %1\$stbl_users.disp_lang AS u_lang  FROM %1\$stbl_bookings\r\n    INNER JOIN %1\$stbl_users ON %1\$stbl_users.user_id = %1\$stbl_bookings.user_id\r\n    INNER JOIN %1\$stbl_drivers ON %1\$stbl_drivers.driver_id = %1\$stbl_bookings.driver_id \r\n    LEFT JOIN %1\$stbl_driver_location ON %1\$stbl_driver_location.driver_id = %1\$stbl_drivers.driver_id \r\n    WHERE %1\$stbl_bookings.id = \"%3\$d\" AND %1\$stbl_drivers.driver_id = \"%2\$d\"", DB_TBL_PREFIX, $_SESSION["uid"], $booking_id);
    if($result = mysqli_query($GLOBALS["DB"], $query)) {
        if(mysqli_num_rows($result)) {
            $book_data = mysqli_fetch_assoc($result);
            if($book_data["booking_status"] == 2 || $book_data["booking_status"] == 4 || $book_data["booking_status"] == 5) {
                $data_array = ["success" => 1];
                echo json_encode($data_array);
                exit;
            }
            $rlang = $book_data["u_lang"];
            $query = sprintf("UPDATE %stbl_bookings SET `status` = 4, cancel_comment = \"%s\" WHERE id = \"%d\" AND driver_id = \"%d\"", DB_TBL_PREFIX, $comment, $booking_id, $_SESSION["uid"]);
            if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
                $error = ["error" => __("An error has occured")];
                echo json_encode($error);
                exit;
            }
            $query = sprintf("UPDATE %stbl_driver_allocate SET `status` = %d WHERE booking_id = %d AND driver_id = %d", DB_TBL_PREFIX, 4, $booking_id, $_SESSION["uid"]);
            $result = mysqli_query($GLOBALS["DB"], $query);
            $query = sprintf("UPDATE %stbl_drivers SET cancelled_rides = cancelled_rides + 1 WHERE driver_id = %d", DB_TBL_PREFIX, $_SESSION["uid"]);
            $result = mysqli_query($GLOBALS["DB"], $query);
            $booking_title = str_pad($booking_id, 5, "0", STR_PAD_LEFT);
            $title = WEBSITE_NAME . " - " . __("Booking Cancelled", [], "d|" . $rlang);
            $body = __("Your booking {---1} has been cancelled by your assigned driver. Is this OK with you? Please contact us if there is a problem", ["#" . $booking_title], "d|" . $rlang);
            $device_tokens = $book_data["user_push_token"];
            if(!empty($device_tokens)) {
                sendPushNotification($title, $body, $device_tokens);
            }
            $title = "";
            $body = "";
            $device_tokens = $book_data["user_push_token"];
            $data = ["action" => "driver-cancelled", "booking_id" => $booking_id];
            if(!empty($device_tokens)) {
                sendPushNotification($title, $body, $device_tokens, $data);
            }
            sendRealTimeNotification("ridr-" . $book_data["customer_id"], $data);
            $content = __("Your booking {---1} has been cancelled by your assigned driver. Is this OK with you? Please contact us if there is a problem", ["#" . $booking_title], "d|" . $rlang);
            $query = sprintf("INSERT INTO %stbl_notifications(person_id,user_type,content,n_type,date_created) VALUES \r\n    (\"%d\",0,\"%s\",0,\"%s\")", DB_TBL_PREFIX, $book_data["customer_id"], mysqli_real_escape_string($GLOBALS["DB"], $content), gmdate("Y-m-d H:i:s", time()));
            $result = mysqli_query($GLOBALS["DB"], $query);
            $data_array = ["success" => 1];
            echo json_encode($data_array);
            exit;
        }
        $error = ["error" => __("An error has occured")];
        echo json_encode($error);
        exit;
    }
    $error = ["error" => __("An error has occured")];
    echo json_encode($error);
    exit;
}
function bookingallocatecancel()
{
    if(empty($_SESSION["loggedin"])) {
        $error = ["error" => __("Please login to continue")];
        echo json_encode($error);
        exit;
    }
    $booking_id = (int) $_POST["bookingid"];
    $query = sprintf("UPDATE %stbl_driver_allocate SET `status` = 2 WHERE booking_id= \"%d\" AND driver_id=\"%d\"", DB_TBL_PREFIX, $booking_id, $_SESSION["uid"]);
    if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
        $error = ["error" => __("An error has occured")];
        echo json_encode($error);
        exit;
    }
    $query = sprintf("UPDATE %stbl_drivers SET rejected_rides = rejected_rides + 1 WHERE driver_id = %d", DB_TBL_PREFIX, $_SESSION["uid"]);
    $result = mysqli_query($GLOBALS["DB"], $query);
    $data_array = ["success" => 1];
    echo json_encode($data_array);
    exit;
}
function getChatContent($mode = 0)
{
    if(empty($_SESSION["loggedin"])) {
        if($mode == 1) {
            return ["error" => 1];
        }
        $error = ["error" => __("Please login to continue")];
        echo json_encode($error);
        exit;
    }
    $booking_id = (int) $_GET["booking_id"];
    if(empty($booking_id)) {
        if($mode == 1) {
            return ["error" => 1];
        }
        $error = ["error" => __("An error has occured")];
        echo json_encode($error);
        exit;
    }
    $chat_data = [];
    $chat_messages_html = "";
    $count = 0;
    $chat_new_content_status = 0;
    $query = sprintf("SELECT * FROM %stbl_bookings WHERE id = %d AND driver_id = %d", DB_TBL_PREFIX, $booking_id, $_SESSION["uid"]);
    if($result = mysqli_query($GLOBALS["DB"], $query)) {
        if(!mysqli_num_rows($result)) {
            $error = ["error" => __("An error has occured")];
            echo json_encode($error);
            exit;
        }
        $query = sprintf("SELECT %1\$stbl_users.photo_file AS user_photo_file,%1\$stbl_users.firstname AS user_firstname,%1\$stbl_drivers.firstname AS driver_firstname,%1\$stbl_drivers.photo_file AS driver_photo_file, %1\$stbl_chats.chat_msg, %1\$stbl_chats.user_id AS chat_user_id, %1\$stbl_chats.driver_id AS chat_driver_id FROM %1\$stbl_chats \r\n    LEFT JOIN %1\$stbl_users ON %1\$stbl_users.user_id = %1\$stbl_chats.user_id\r\n    LEFT JOIN %1\$stbl_drivers ON %1\$stbl_drivers.driver_id = %1\$stbl_chats.driver_id\r\n    WHERE %1\$stbl_chats.booking_id = %2\$d ORDER BY date_created ASC", DB_TBL_PREFIX, $booking_id, $_SESSION["uid"]);
        if($result = mysqli_query($GLOBALS["DB"], $query)) {
            if(mysqli_num_rows($result)) {
                while ($row = mysqli_fetch_assoc($result)) {
                    if($row["chat_driver_id"] != 0) {
                        $driver_photo = isset($row["driver_photo_file"]) ? $row["driver_photo_file"] : "0";
                        $driver_photo = SITE_URL . "ajaxphotofile.php?file=" . $driver_photo;
                        $chat_messages_html .= "<div style='text-align:right;margin-top: 5px;'><div style='background-color:#7cb342;width:55%;border-radius:10px;padding:5px;display:inline-block'><div style='text-align:left'><i style='font-size: 22px;color: white;vertical-align: middle;' class='fa fa-user-circle-o'></i> <span style='font-weight:bold; font-size:14px;color:white;'>" . $row["driver_firstname"] . "</span></div><p style='margin: 5px 0;text-align: left;font-size: 14px;'>" . $row["chat_msg"] . "</p></div></div>";
                    } elseif($row["chat_user_id"] != 0) {
                        $count++;
                        $user_photo = isset($row["user_photo_file"]) ? $row["user_photo_file"] : "0";
                        $user_photo = SITE_URL . "ajaxuserphotofile.php?file=" . $user_photo;
                        $chat_messages_html .= "<div style='text-align:left;margin-top: 5px;'><div style='background-color:#1e88e5;width:55%;border-radius:10px;padding:5px;display:inline-block'><div style='text-align:left'><i style='font-size: 22px;color: white;vertical-align: middle;' class='fa fa-user-circle-o'></i> <span style='font-weight:bold; font-size:14px;color:white;'>" . $row["user_firstname"] . "</span></div><p style='margin: 5px 0;text-align: left;font-size: 14px;'>" . $row["chat_msg"] . "</p></div></div>";
                    }
                }
                if(isset($_SESSION["chats"][$booking_id]["rider_msg_count"])) {
                    if($_SESSION["chats"][$booking_id]["rider_msg_count"] < $count) {
                        $chat_new_content_status = 1;
                        $_SESSION["chats"][$booking_id]["rider_msg_count"] = $count;
                    }
                } else {
                    $_SESSION["chats"][$booking_id]["rider_msg_count"] = 0;
                    if($_SESSION["chats"][$booking_id]["rider_msg_count"] < $count) {
                        $chat_new_content_status = 1;
                        $_SESSION["chats"][$booking_id]["rider_msg_count"] = $count;
                    }
                }
                if($mode == 1) {
                    return ["success" => 1, "chat_content" => $chat_messages_html, "chat_new_content" => $chat_new_content_status];
                }
                $resp = ["success" => 1, "chat_content" => $chat_messages_html, "chat_new_content" => $chat_new_content_status];
                echo json_encode($resp);
                exit;
            }
            if($mode == 1) {
                return ["success" => 1, "chat_content" => "", "chat_new_content" => 0];
            }
            $resp = ["success" => 1, "chat_content" => "", "chat_new_content" => 0];
            echo json_encode($resp);
            exit;
        }
        if($mode == 1) {
            return ["error" => 1];
        }
        $error = ["error" => __("An error has occured")];
        echo json_encode($error);
        exit;
    }
    $error = ["error" => __("An error has occured")];
    echo json_encode($error);
    exit;
}
function chatSendMsg()
{
    if(empty($_SESSION["loggedin"])) {
        $error = ["error" => __("Please login to continue")];
        echo json_encode($error);
        exit;
    }
    $user_data = [];
    $booking_id = (int) $_GET["booking_id"];
    $chat_msg = mysqli_real_escape_string($GLOBALS["DB"], $_GET["chat_msg"]);
    if(empty($chat_msg)) {
        $error = ["error" => __("An error has occured")];
        echo json_encode($error);
        exit;
    }
    if(empty($booking_id)) {
        $error = ["error" => __("An error has occured")];
        echo json_encode($error);
        exit;
    }
    $query = sprintf("SELECT %1\$stbl_users.user_id, %1\$stbl_users.push_notification_token FROM %1\$stbl_bookings \r\n    INNER JOIN %1\$stbl_users ON %1\$stbl_users.user_id = %1\$stbl_bookings.user_id\r\n    WHERE %1\$stbl_bookings.id = %2\$d", DB_TBL_PREFIX, $booking_id);
    if($result = mysqli_query($GLOBALS["DB"], $query)) {
        if(mysqli_num_rows($result)) {
            $user_data = mysqli_fetch_assoc($result);
            $query = sprintf("INSERT INTO %stbl_chats (chat_msg,`driver_id`,booking_id,date_created) VALUES (\"%s\",\"%d\",\"%d\",\"%s\")", DB_TBL_PREFIX, $chat_msg, $_SESSION["uid"], $booking_id, gmdate("Y-m-d H:i:s", time()));
            if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
                $error = ["error" => __("An error has occured")];
                echo json_encode($error);
                exit;
            }
            $chat_messages_data = getchatcontent(1);
            $title = "";
            $body = "";
            $data = ["action" => "chat-message", "booking_id" => $booking_id, "message" => $chat_msg];
            $device_tokens = !empty($user_data["push_notification_token"]) ? $user_data["push_notification_token"] : 0;
            if(!empty($device_tokens)) {
                sendPushNotification($title, $body, $device_tokens, $data, 0);
            }
            sendRealTimeNotification("ridr-" . $user_data["user_id"], $data);
            if(isset($chat_messages_data["error"])) {
                $resp = ["success" => 1, "chat_content" => "", "chat_new_content" => 0];
                echo json_encode($resp);
                exit;
            }
            $resp = ["success" => 1, "chat_content" => $chat_messages_data["chat_content"], "chat_new_content" => $chat_messages_data["chat_new_content"]];
            echo json_encode($resp);
            exit;
        }
        $error = ["error" => __("An error has occured")];
        echo json_encode($error);
        exit;
    }
    $error = ["error" => __("An error has occured")];
    echo json_encode($error);
    exit;
}
function getusernotifications()
{
    if(empty($_SESSION["loggedin"])) {
        $error = ["error" => __("Please login to continue")];
        echo json_encode($error);
        exit;
    }
    $notification_data = [];
    $notification_data_date_sort = [];
    $formatted_notifications = "";
    $num_of_notifications = 0;
    $query = sprintf("SELECT COUNT(*) FROM %stbl_notifications WHERE (person_id = \"%d\" AND user_type = 1) OR (route_id = %d AND n_type = 5 AND user_type = 1)", DB_TBL_PREFIX, $_SESSION["uid"], $_SESSION["city_id"]);
    if($result = mysqli_query($GLOBALS["DB"], $query)) {
        if(mysqli_num_rows($result)) {
            $row = mysqli_fetch_assoc($result);
            $num_of_notifications = $row["COUNT(*)"];
        }
        mysqli_free_result($result);
    }
    $query = sprintf("SELECT *, DATE(date_created) AS created_date FROM %stbl_notifications WHERE (person_id = \"%d\" AND user_type = 1) OR (route_id = %d AND n_type = 5 AND user_type = 1) ORDER BY date_created DESC LIMIT 0,50", DB_TBL_PREFIX, $_SESSION["uid"], $_SESSION["city_id"]);
    if($result = mysqli_query($GLOBALS["DB"], $query)) {
        if(mysqli_num_rows($result)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $notification_data_date_sort[$row["created_date"]]["date"] = $row["created_date"];
                $notification_data_date_sort[$row["created_date"]]["notifications"][] = $row;
            }
            mysqli_free_result($result);
            foreach ($notification_data_date_sort as $notificationdatadatesort) {
                if(!empty($notificationdatadatesort["notifications"])) {
                    $notifications_formated_date = date("l, M j, Y", strtotime($notificationdatadatesort["date"] . " UTC"));
                    $formatted_notifications .= "<ons-list-header style='border-top:thin solid grey;border-bottom:thin solid grey;font-size:14px;'>" . $notifications_formated_date . "</ons-list-header>";
                    foreach ($notificationdatadatesort["notifications"] as $date_notifications) {
                        if($date_notifications["n_type"] == 5) {
                            $close_btn = "";
                        } else {
                            $close_btn = "<span style='float:right;'><ons-icon onclick = 'notifydelete(" . $date_notifications["id"] . ")' icon='fa-times-circle' size='18px' style='color:red'></ons-icon></span>";
                        }
                        $notification_time = date("g:i A", strtotime($date_notifications["date_created"] . " UTC"));
                        $notification_type = "";
                        switch ($date_notifications["n_type"]) {
                            case 1:
                                $notification_type = "<ons-icon icon='fa-bullhorn' size='14px' style='color:green;'></ons-icon>";
                                break;
                            case 2:
                                $notification_type = "<ons-icon icon='fa-bell' size='14px' style='color:blue;'></ons-icon>";
                                break;
                            case 3:
                                $notification_type = "<ons-icon icon='fa-money' size='14px' style='color:teal;'></ons-icon>";
                                break;
                            case 4:
                                $notification_type = "<ons-icon icon='fa-flag-checkered' size='14px' style='color:#9b9b0b;'></ons-icon>";
                                break;
                            case 5:
                                $notification_type = "<ons-icon icon='fa-star' size='14px' style='color:yellow;'></ons-icon>";
                                break;
                            default:
                                $notification_type = "<ons-icon icon='fa-bell' size='14px' style='color:#333;'></ons-icon>";
                                $formatted_notifications .= "<ons-list-item class='notification-item' id='notification-list-item-" . $date_notifications["id"] . "' modifier='longdivider'>\r\n                \r\n                                                <div class='center'>\r\n                                                    <div style='width:100%;'>" . $notification_type . " <span style='font-weight:bold;'class='list-item__title'>" . $notification_time . " </span> " . $close_btn . "</div>\r\n                                                    <span class='list-item__subtitle'>" . $date_notifications["content"] . "</span>                                                    \r\n                                                </div>\r\n                                            \r\n                                            </ons-list-item>";
                        }
                    }
                }
            }
            $data_array = ["success" => 1, "notifications" => $formatted_notifications, "n_count" => $num_of_notifications];
            echo json_encode($data_array);
            exit;
        } else {
            $error = ["nodata" => __("You do not have any notifications")];
            echo json_encode($error);
            exit;
        }
    } else {
        $error = ["error" => __("An error has occured")];
        echo json_encode($error);
        exit;
    }
}
function deletenotification()
{
    if(empty($_SESSION["loggedin"])) {
        $error = ["error" => __("Please login to continue")];
        echo json_encode($error);
        exit;
    }
    $notification_id = (int) $_POST["n_id"];
    $query = sprintf("DELETE FROM %stbl_notifications WHERE person_id = \"%d\" AND user_type = 1 AND id = \"%d\"", DB_TBL_PREFIX, $_SESSION["uid"], $notification_id);
    if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
        $error = ["error" => __("An error has occured")];
        echo json_encode($error);
        exit;
    }
    $data_array = ["success" => 1];
    echo json_encode($data_array);
    exit;
}
function paystackInit()
{
    if(empty($_SESSION["loggedin"])) {
        $error = ["error" => __("Please login to continue")];
        echo json_encode($error);
        exit;
    }
    include "../drop-files/lib/pgateways/paystack/paystack-transaction-init.php";
}
function pesapalInit()
{
    if(empty($_SESSION["loggedin"])) {
        $error = ["error" => __("Please login to continue")];
        echo json_encode($error);
        exit;
    }
    include "../drop-files/lib/pgateways/pesapal/pesapal-transaction-init.php";
}
function paytrInit()
{
    if(empty($_SESSION["loggedin"])) {
        $error = ["error" => __("Please login to continue")];
        echo json_encode($error);
        exit;
    }
    include "../drop-files/lib/pgateways/paytr/paytr-transaction-init.php";
}
function stripeInit()
{
    if(empty($_SESSION["loggedin"])) {
        $error = ["error" => __("Please login to continue")];
        echo json_encode($error);
        exit;
    }
    include "../drop-files/lib/pgateways/stripe/stripe-transaction-init.php";
}
function flutterwaveInit()
{
    if(empty($_SESSION["loggedin"])) {
        $error = ["error" => __("Please login to continue")];
        echo json_encode($error);
        exit;
    }
    include "../drop-files/lib/pgateways/flutterwave/flutterwave-transaction-init.php";
}
function paykuInit()
{
    if(empty($_SESSION["loggedin"])) {
        $error = ["error" => "Please login."];
        echo json_encode($error);
        exit;
    }
    include "../drop-files/lib/pgateways/payku/payku-transaction-init.php";
}
function midtransInit()
{
    if(empty($_SESSION["loggedin"])) {
        $error = ["error" => "Please login."];
        echo json_encode($error);
        exit;
    }
    include "../drop-files/lib/pgateways/midtrans/midtrans-transaction-init.php";
}
function paymobInit()
{
    if(empty($_SESSION["loggedin"])) {
        $error = ["error" => "Please login."];
        echo json_encode($error);
        exit;
    }
    include "../drop-files/lib/pgateways/paymob/paymob-transaction-init.php";
}
function customInt()
{
    if(empty($_SESSION["loggedin"])) {
        $error = ["error" => __("Please login to continue")];
        echo json_encode($error);
        exit;
    }
    include "../drop-files/lib/pgateways/custom/custom-transaction-init.php";
}

?>