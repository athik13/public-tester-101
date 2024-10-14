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
    if(file_exists(FILES_FOLDER . "/lang/rider/" . $_SESSION["lang"] . ".php")) {
        include FILES_FOLDER . "/lang/rider/" . $_SESSION["lang"] . ".php";
    } else {
        include FILES_FOLDER . "/lang/rider/en.php";
    }
} else {
    include FILES_FOLDER . "/lang/rider/en.php";
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
        $error = ["notloggedin" => "Please login to cotinue."];
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
function updateUserPhoto()
{
    if(empty($_SESSION["loggedin"])) {
        $error = ["error" => __("Please login to continue")];
        echo json_encode($error);
        exit;
    }
    $uploaded_photo_encoded = $_POST["photo"];
    $uploaded_photo_encoded_array = explode(",", $uploaded_photo_encoded);
    $image_data = array_pop($uploaded_photo_encoded_array);
    $uploaded_photo_decoded = base64_decode($image_data);
    if(!$uploaded_photo_decoded) {
        $error = ["error" => __("Invalid photo format")];
        echo json_encode($error);
        exit;
    }
    $filename = crypto_string("distinct", 20);
    @mkdir(@realpath(CUSTOMER_PHOTO_PATH) . "/" . $filename[0] . "/" . $filename[1] . "/" . $filename[2], 511, true);
    $image_path = realpath(CUSTOMER_PHOTO_PATH) . "/" . $filename[0] . "/" . $filename[1] . "/" . $filename[2] . "/";
    $file = $image_path . $filename . ".jpg";
    file_put_contents($file, $uploaded_photo_decoded);
    $user_photo = $filename . ".jpg";
    $query = sprintf("UPDATE %stbl_users SET photo_file = \"%s\" WHERE user_id = \"%d\"", DB_TBL_PREFIX, $user_photo, $_SESSION["uid"]);
    if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
        $error = ["error" => __("Failed to update your photo")];
        echo json_encode($error);
        exit;
    }
    $data = ["success" => 1, "photo_url" => SITE_URL . "ajaxuserphotofile.php?file=" . $user_photo];
    echo json_encode($data);
    exit;
}
function updateUserProfile()
{
    if(empty($_SESSION["loggedin"])) {
        $error = ["error" => __("Please login to continue")];
        echo json_encode($error);
        exit;
    }
    $user_country = codeToCountryName(strtoupper($_POST["country_code"]));
    if(!$user_country) {
        $error = ["error" => __("Invalid country selected")];
        echo json_encode($error);
        exit;
    }
    if(!filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
        $error = ["error" => __("Your email is not valid")];
        echo json_encode($error);
        exit;
    }
    $msg = "";
    $query = sprintf("SELECT user_id,email, phone FROM %stbl_users WHERE user_id != \"%d\" AND (email = \"%s\" OR phone=\"%s\")", DB_TBL_PREFIX, $_SESSION["uid"], mysqli_real_escape_string($GLOBALS["DB"], $_POST["email"]), mysqli_real_escape_string($GLOBALS["DB"], $_POST["phone"]));
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
        if(!empty($_POST["oldpassword"]) && empty($_POST["newpassword"])) {
            $error = ["error" => __("Your new password cannot be empty")];
            echo json_encode($error);
            exit;
        }
        if(!empty($_POST["oldpassword"])) {
            $query = sprintf("SELECT user_id FROM %stbl_users WHERE pwd_raw = \"%s\" AND user_id = \"%d\"", DB_TBL_PREFIX, mysqli_real_escape_string($GLOBALS["DB"], $_POST["oldpassword"]), $_SESSION["uid"]);
            if($result = mysqli_query($GLOBALS["DB"], $query)) {
                if(mysqli_num_rows($result)) {
                    $query = sprintf("UPDATE %stbl_users SET `pwd_raw` = \"%s\" WHERE user_id = \"%d\"", DB_TBL_PREFIX, mysqli_real_escape_string($GLOBALS["DB"], $_POST["newpassword"]), $_SESSION["uid"]);
                    $result = mysqli_query($GLOBALS["DB"], $query);
                    $msg = __("Password was changed successfully") . "<br>";
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
        $query = sprintf("UPDATE %stbl_users SET `phone` = \"%s\", `email` = \"%s\", country_code = \"%s\", country_dial_code = \"%s\", country = \"%s\" WHERE user_id = \"%d\"", DB_TBL_PREFIX, mysqli_real_escape_string($GLOBALS["DB"], $_POST["phone"]), mysqli_real_escape_string($GLOBALS["DB"], $_POST["email"]), mysqli_real_escape_string($GLOBALS["DB"], $_POST["country_code"]), "+" . mysqli_real_escape_string($GLOBALS["DB"], $_POST["country_dial_code"]), $user_country, $_SESSION["uid"]);
        $result = mysqli_query($GLOBALS["DB"], $query);
        $_SESSION["email"] = mysqli_real_escape_string($GLOBALS["DB"], $_POST["email"]);
        $_SESSION["phone"] = mysqli_real_escape_string($GLOBALS["DB"], $_POST["phone"]);
        $_SESSION["country_dial_code"] = "+" . mysqli_real_escape_string($GLOBALS["DB"], $_POST["country_dial_code"]);
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
        $query = sprintf("UPDATE %stbl_users SET `push_notification_token` = NULL WHERE push_notification_token = \"%s\"", DB_TBL_PREFIX, $push_notification_token);
        $result = mysqli_query($GLOBALS["DB"], $query);
        $query = sprintf("UPDATE %stbl_users SET `push_notification_token` = \"%s\" WHERE user_id = \"%d\"", DB_TBL_PREFIX, $push_notification_token, $_SESSION["uid"]);
        $result = mysqli_query($GLOBALS["DB"], $query);
        $_SESSION["push_token"] = $push_notification_token;
    }
}
function userLogin()
{
    $phone = $_POST["phone"];
    $password = $_POST["password"];
    $country_dial_code = $_POST["country_call_code"];
    $token = "";
    $user_account_details = [];
    $display_language = mysqli_real_escape_string($GLOBALS["DB"], $_POST["display_lang"]);
    if(isset($_POST["timezone"]) && isValidTimezoneId($_POST["timezone"])) {
        $_SESSION["timezone"] = $_POST["timezone"];
        date_default_timezone_set($_SESSION["timezone"]);
    } else {
        date_default_timezone_set("Africa/Lagos");
    }
    $query = sprintf("SELECT reward_points,route_id,country_code,country_dial_code,user_rating,photo_file,account_active,referal_code,push_notification_token,`address`,account_type,user_id,firstname, lastname,email,phone,is_activated,account_active,last_login_date,account_create_date,referal_code,wallet_amount FROM %stbl_users WHERE phone = \"%s\" AND pwd_raw = \"%s\" AND country_dial_code = \"%s\"", DB_TBL_PREFIX, mysqli_real_escape_string($GLOBALS["DB"], $_POST["phone"]), mysqli_real_escape_string($GLOBALS["DB"], $_POST["password"]), "+" . $country_dial_code);
    if($result = mysqli_query($GLOBALS["DB"], $query)) {
        if(mysqli_num_rows($result)) {
            $user_account_details = mysqli_fetch_assoc($result);
            if($user_account_details["is_activated"] == 0) {
                $_SESSION["not_activated_user"]["uid"] = $user_account_details["user_id"];
                $data = ["success" => "1", "is_activated" => $user_account_details["is_activated"], "loggedin" => 0];
                echo json_encode($data);
                exit;
            }
            $ref_code_settings = [];
            $query = sprintf("SELECT * FROM %stbl_referral WHERE id = 1", DB_TBL_PREFIX);
            if(($result = mysqli_query($GLOBALS["DB"], $query)) && mysqli_num_rows($result)) {
                $ref_code_settings = mysqli_fetch_assoc($result);
            }
            $referral_url = SITE_URL;
            $refcode_copy = isset($ref_code_settings["status"]) && $ref_code_settings["status"] == 1 && !empty($user_account_details["referal_code"]) ? __("Hi, you can now book rides from your phone anywhere, anytime using Droptaxi Taxi service. Sign-up now with my referral code {---1} and get a discount on your first ride.", [(string) $user_account_details["referal_code"]], "r|" . $display_language) : "";
            $ref_code_desc = __("Earn {---1}% discount on your next ride when you invite a friend to register on Droptaxi using your referral code", [$ref_code_settings["discount_value"]], "r|" . $display_language);
            $ref_code = isset($ref_code_settings["status"]) && $ref_code_settings["status"] == 1 && !empty($user_account_details["referal_code"]) ? "<input id='user-refcode-text' type='text' hidden='hidden' value='" . $refcode_copy . "'><span style='display:block;color:#42a5f5;font-family: Roboto,Noto,sans-serif;font-size:13px;font-weight:400;'>" . __("Referral code") . "</span><p style='margin-top:5px;padding: 15px 5px;border: thin dashed;'><b id='user-refcode'>" . $user_account_details["referal_code"] . "</b><b style='color: blue;float: right;' onclick=share_message('',\$('#user-refcode-text').val(),'" . $referral_url . "') >" . __("Share") . "</b></p><p>" . $ref_code_desc . "</p>" : "";
            $photo_file = isset($user_account_details["photo_file"]) ? $user_account_details["photo_file"] : "0";
            $_SESSION["firstname"] = $user_account_details["firstname"];
            $_SESSION["lastname"] = $user_account_details["lastname"];
            $_SESSION["uid"] = $user_account_details["user_id"];
            $_SESSION["email"] = $user_account_details["email"];
            $_SESSION["route_id"] = $user_account_details["route_id"];
            $_SESSION["phone"] = $user_account_details["phone"];
            $_SESSION["country_dial_code"] = $user_account_details["country_dial_code"];
            $_SESSION["address"] = $user_account_details["address"];
            $_SESSION["referal_code"] = $user_account_details["referal_code"];
            $_SESSION["account_type"] = $user_account_details["account_type"];
            $_SESSION["lastseen"] = $user_account_details["last_login_date"];
            $_SESSION["joined"] = $user_account_details["account_create_date"];
            $_SESSION["loggedin"] = 1;
            $_SESSION["reward_points"] = $user_account_details["reward_points"];
            $_SESSION["is_activated"] = $user_account_details["is_activated"];
            $_SESSION["user_rating"] = $user_account_details["user_rating"];
            $_SESSION["wallet_amt"] = $user_account_details["wallet_amount"];
            $_SESSION["push_token"] = $user_account_details["push_notification_token"];
            $_SESSION["photo"] = SITE_URL . "ajaxuserphotofile.php?file=" . $photo_file;
            $profiledata = ["success" => 1, "firstname" => $_SESSION["firstname"], "lastname" => $_SESSION["lastname"], "email" => $_SESSION["email"], "phone" => $_SESSION["phone"], "route_id" => $_SESSION["route_id"], "address" => $_SESSION["address"], "userid" => $_SESSION["uid"], "ref_code" => $ref_code, "ref_code_copy_msg" => $refcode_copy, "ref_desc" => $ref_code_desc, "photo" => $_SESSION["photo"], "user_rating" => $_SESSION["user_rating"], "country_code" => $user_account_details["country_code"], "country_dial_code" => $user_account_details["country_dial_code"]];
            $ongoing_booking = [];
            $query = sprintf("SELECT *,%1\$stbl_bookings.id AS booking_id FROM %1\$stbl_bookings \r\n    INNER JOIN %1\$stbl_drivers ON %1\$stbl_drivers.driver_id = %1\$stbl_bookings.driver_id\r\n    INNER JOIN %1\$stbl_driver_location ON %1\$stbl_driver_location.driver_id = %1\$stbl_bookings.driver_id\r\n    WHERE %1\$stbl_bookings.user_id = %2\$d AND %1\$stbl_bookings.driver_id != 0 AND (%1\$stbl_bookings.status = 0 OR %1\$stbl_bookings.status = 1) ORDER BY %1\$stbl_bookings.id DESC LIMIT 1", DB_TBL_PREFIX, $_SESSION["uid"]);
            if(($result = mysqli_query($GLOBALS["DB"], $query)) && mysqli_num_rows($result)) {
                $row = mysqli_fetch_assoc($result);
                $action = "";
                if(!empty($row["date_arrived"]) && $row["status"] == 0) {
                    $action = "driver-arrived";
                } elseif(empty($row["date_arrived"]) && $row["status"] == 0) {
                    $action = "driver-assigned";
                } else {
                    $action = "customer-onride";
                }
                $driver_photo_file = isset($row["photo_file"]) ? $row["photo_file"] : "0";
                $ongoing_booking = ["action" => $action, "route_id" => $row["route_id"], "booking_id" => $row["booking_id"], "driver_id" => $row["driver_id"], "driver_firstname" => $row["firstname"], "driver_phone" => $row["phone"], "driver_platenum" => $row["car_plate_num"], "driver_rating" => $row["driver_rating"], "driver_location_lat" => $row["lat"], "driver_location_long" => $row["long"], "pickup_lat" => $row["pickup_lat"], "pickup_long" => $row["pickup_long"], "dropoff_lat" => $row["dropoff_lat"], "dropoff_long" => $row["dropoff_long"], "driver_carmodel" => $row["car_model"], "driver_carid" => $row["ride_id"], "driver_completed_rides" => $row["completed_rides"], "completion_code" => $row["completion_code"], "driver_photo" => SITE_URL . "ajaxphotofile.php?file=" . $driver_photo_file];
            }
            $recent_bookings_loc = [];
            if(!empty($_SESSION["route_id"])) {
                $query = sprintf("SELECT %1\$stbl_bookings.dropoff_address, %1\$stbl_bookings.dropoff_long, %1\$stbl_bookings.dropoff_lat FROM %1\$stbl_bookings\r\n        WHERE %1\$stbl_bookings.user_id = %2\$d AND %1\$stbl_bookings.route_id = %3\$d AND %1\$stbl_bookings.status = 3 ORDER BY %1\$stbl_bookings.id DESC LIMIT 15", DB_TBL_PREFIX, $_SESSION["uid"], $_SESSION["route_id"]);
                if(($result = mysqli_query($GLOBALS["DB"], $query)) && mysqli_num_rows($result)) {
                    $unique_address = [];
                    $count = 0;
                    while ($row = mysqli_fetch_assoc($result)) {
                        if(!in_array_r($row["dropoff_address"], $unique_address)) {
                            $count++;
                            $unique_address[] = $row["dropoff_address"];
                            $recent_bookings_loc[] = $row;
                            if($count == 4) {
                            }
                            break;
                        }
                    }
                }
            }
            $recent_loc_data = ["route_id" => $_SESSION["route_id"], "locations" => $recent_bookings_loc];
            $default_currency_data = [];
            $query = sprintf("SELECT * FROM %stbl_currencies WHERE `default` = 1", DB_TBL_PREFIX);
            if(($result = mysqli_query($GLOBALS["DB"], $query)) && mysqli_num_rows($result)) {
                $default_currency_data = mysqli_fetch_assoc($result);
            }
            $tariff_data = getroutetariffs();
            $online_payment_data = ["merchantid" => P_G_PK, "storeid" => STORE_ID, "devid" => DEV_ID, "notifyurl" => NOTIFY_URL];
            $app_settings = ["payment_type" => PAYMENT_TYPE, "ride_otp" => RIDE_OTP, "default_payment_gateway" => DEFAULT_PAYMENT_GATEWAY, "wallet_topup_presets" => WALLET_TOPUP_PRESETS, "driver_tip_presets" => DRIVER_TIP_PRESETS];
            $firebase_rtdb_conf = ["databaseURL" => FB_RTDB_URL, "apiKey" => FB_WEB_API_KEY, "storageBucket" => FB_STORAGE_BCKT];
            $query = sprintf("UPDATE %stbl_users SET last_login_date = \"%s\", disp_lang = \"%s\", login_count = login_count + 1 WHERE user_id = %d", DB_TBL_PREFIX, gmdate("Y-m-d H:i:s", time()), $display_language, $_SESSION["uid"]);
            $result = mysqli_query($GLOBALS["DB"], $query);
            $_SESSION["lang"] = $display_language;
            session_regenerate_id();
            $data = ["loggedin" => 1, "ongoing_bk" => $ongoing_booking, "fb_conf" => $firebase_rtdb_conf, "recent_locs" => $recent_loc_data, "is_activated" => $user_account_details["is_activated"], "account_active" => $user_account_details["account_active"], "wallet_amt" => $_SESSION["wallet_amt"], "reward_points" => $_SESSION["reward_points"], "cc_num" => CALL_CENTER_NUMBER, "profileinfo" => $profiledata, "tariff_data" => $tariff_data, "profileinfo" => $profiledata, "online_pay" => $online_payment_data, "app_version_ios" => APP_VERSION_CUSTOMER_IOS, "app_version_android" => APP_VERSION_CUSTOMER_ANDROID, "customer_app_update_url_ios" => CUSTOMER_APP_UPDATE_URL_IOS, "customer_app_update_url_android" => CUSTOMER_APP_UPDATE_URL_ANDROID, "scheduled_ride_enabled" => SCHEDULED_RIDE_ENABLED, "default_currency" => $default_currency_data, "app_settings" => $app_settings, "sess_id" => base64_encode(session_id())];
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
function userLogout()
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
    $query = sprintf("SELECT * FROM %stbl_users WHERE `user_id` = %d AND pwd_raw = \"%s\"", DB_TBL_PREFIX, $_SESSION["uid"], $password);
    if($result = mysqli_query($GLOBALS["DB"], $query)) {
        if(!mysqli_num_rows($result)) {
            $error = ["error" => __("Invalid account")];
            echo json_encode($error);
            exit;
        }
        $query = sprintf("SELECT * FROM %stbl_bookings WHERE `user_id` = %d AND (`status` = 0 OR `status` = 1 OR `status` = 6)", DB_TBL_PREFIX, $_SESSION["uid"]);
        if($result = mysqli_query($GLOBALS["DB"], $query)) {
            if(mysqli_num_rows($result)) {
                $error = ["error" => __("An error has occured")];
                echo json_encode($error);
                exit;
            }
            $query = sprintf("DELETE FROM %stbl_users WHERE `user_id` = %d AND pwd_raw = \"%s\"", DB_TBL_PREFIX, $_SESSION["uid"], $password);
            $result = mysqli_query($GLOBALS["DB"], $query);
            $query = sprintf("DELETE FROM %stbl_notifications WHERE `person_id` = %d AND user_type = %d", DB_TBL_PREFIX, $_SESSION["uid"], 0);
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
    $driver_tip = (double) $_GET["driver_tip"];
    $msg = "";
    if($rating < 0 || $rating < 1) {
        $rating = 1;
    } elseif(5 < $rating) {
        $rating = 5;
    }
    $booking_data = [];
    $query = sprintf("SELECT %1\$stbl_drivers.disp_lang,%1\$stbl_drivers.push_notification_token,%1\$stbl_users.wallet_amount AS user_wallet_amount,%1\$stbl_drivers.wallet_amount AS driver_wallet_amount,%1\$stbl_currencies.exchng_rate,%1\$stbl_currencies.symbol,%1\$stbl_currencies.iso_code,%1\$stbl_bookings.driver_id, %1\$stbl_drivers.driver_rating FROM %1\$stbl_bookings \r\n    INNER JOIN %1\$stbl_users ON %1\$stbl_users.user_id = %1\$stbl_bookings.user_id\r\n    INNER JOIN %1\$stbl_routes ON %1\$stbl_routes.id = %1\$stbl_users.route_id\r\n    INNER JOIN %1\$stbl_currencies ON %1\$stbl_currencies.id = %1\$stbl_routes.city_currency_id\r\n    INNER JOIN %1\$stbl_drivers ON %1\$stbl_drivers.driver_id = %1\$stbl_bookings.driver_id\r\n    WHERE %1\$stbl_bookings.id = %2\$d", DB_TBL_PREFIX, $booking_id);
    if($result = mysqli_query($GLOBALS["DB"], $query)) {
        if(mysqli_num_rows($result)) {
            $booking_data = mysqli_fetch_assoc($result);
            $query = sprintf("INSERT INTO %stbl_ratings_users (booking_id,`user_id`,user_comment,user_rating) VALUES (%d,%d,\"%s\",%d)", DB_TBL_PREFIX, $booking_id, $_SESSION["uid"], mysqli_real_escape_string($GLOBALS["DB"], strip_tags($_GET["comment"])), $rating);
            if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
                $error = ["error" => __("An error has occured")];
                echo json_encode($error);
                exit;
            }
            $driver_new_rating = floor(($booking_data["driver_rating"] + $rating) / 2);
            if(5 < $driver_new_rating) {
                $driver_new_rating = 5;
            }
            $query = sprintf("UPDATE %stbl_drivers SET driver_rating = %d WHERE driver_id = %d", DB_TBL_PREFIX, $driver_new_rating, $booking_data["driver_id"]);
            $result = mysqli_query($GLOBALS["DB"], $query);
            if(!empty($driver_tip)) {
                $driver_tip_converted = $driver_tip / $booking_data["exchng_rate"];
                $rider_wallet_ballance = $booking_data["user_wallet_amount"] - $driver_tip_converted;
                if($rider_wallet_ballance >= 0) {
                    $query = sprintf("UPDATE %stbl_users SET wallet_amount = wallet_amount - %f WHERE user_id = %d", DB_TBL_PREFIX, $driver_tip_converted, $_SESSION["uid"]);
                    $result = mysqli_query($GLOBALS["DB"], $query);
                    $query = sprintf("UPDATE %stbl_drivers SET wallet_amount = wallet_amount + %f WHERE driver_id = %d", DB_TBL_PREFIX, $driver_tip_converted, $booking_data["driver_id"]);
                    $result = mysqli_query($GLOBALS["DB"], $query);
                    $transaction_msg = __("Debit for the reward you sent to your driver");
                    $transaction_id = crypto_string();
                    $query = sprintf("INSERT INTO %stbl_wallet_transactions (cur_symbol,cur_exchng_rate,cur_code,book_id,transaction_id,amount,wallet_balance,user_id,user_type,`desc`,`type`,transaction_date) VALUES(\"%s\",\"%s\",\"%s\",\"%d\",\"%s\",\"%s\",\"%s\",\"%d\",\"%d\",\"%s\",\"%d\",\"%s\")", DB_TBL_PREFIX, $booking_data["symbol"], $booking_data["exchng_rate"], $booking_data["iso_code"], $booking_id, $transaction_id, $driver_tip, $rider_wallet_ballance, $_SESSION["uid"], 0, mysqli_real_escape_string($GLOBALS["DB"], $transaction_msg), 3, gmdate("Y-m-d H:i:s", time()));
                    $result = mysqli_query($GLOBALS["DB"], $query);
                    $transaction_msg = __("A reward from your passenger", [], "r|" . $booking_data["disp_lang"]);
                    $transaction_id = crypto_string();
                    $query = sprintf("INSERT INTO %stbl_wallet_transactions (cur_symbol,cur_exchng_rate,cur_code,book_id,transaction_id,amount,wallet_balance,user_id,user_type,`desc`,`type`,transaction_date) VALUES(\"%s\",\"%s\",\"%s\",\"%d\",\"%s\",\"%s\",\"%s\",\"%d\",\"%d\",\"%s\",\"%d\",\"%s\")", DB_TBL_PREFIX, $booking_data["symbol"], $booking_data["exchng_rate"], $booking_data["iso_code"], $booking_id, $transaction_id, $driver_tip, $driver_tip_converted + $booking_data["driver_wallet_amount"], $booking_data["driver_id"], 1, mysqli_real_escape_string($GLOBALS["DB"], $transaction_msg), 2, gmdate("Y-m-d H:i:s", time()));
                    $result = mysqli_query($GLOBALS["DB"], $query);
                    $title = WEBSITE_NAME . " - " . __("A reward from your passenger", [], "r|" . $booking_data["disp_lang"]);
                    $body = __("You have been rewarded {---1} by your passenger", [$booking_data["symbol"] . $driver_tip], "r|" . $booking_data["disp_lang"]);
                    $device_tokens = !empty($booking_data["push_notification_token"]) ? $booking_data["push_notification_token"] : 0;
                    if(!empty($device_tokens)) {
                        sendPushNotification($title, $body, $device_tokens, NULL, 1);
                    }
                    $msg = __("Your driver reward has been sent. Thank you");
                } else {
                    $msg = __("You do not have enough money in your wallet to reward your driver. Please add money to your wallet");
                }
            }
            $data = ["success" => 1, "message" => $msg];
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
function updateusercity()
{
    if(empty($_SESSION["loggedin"])) {
        $error = ["error" => __("Please login to continue")];
        echo json_encode($error);
        exit;
    }
    $route_id = (int) $_GET["route_id"];
    if(!$route_id) {
        $error = ["error" => __("An error has occured")];
        echo json_encode($error);
        exit;
    }
    $query = sprintf("UPDATE %stbl_users SET `route_id` = \"%d\" WHERE user_id = \"%d\"", DB_TBL_PREFIX, $route_id, $_SESSION["uid"]);
    if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
        $error = ["error" => __("An error has occured")];
        echo json_encode($error);
        exit;
    }
    $_SESSION["route_id"] = $route_id;
    $recent_bookings_loc = [];
    $query = sprintf("SELECT %1\$stbl_bookings.dropoff_address, %1\$stbl_bookings.dropoff_long, %1\$stbl_bookings.dropoff_lat FROM %1\$stbl_bookings\r\n    WHERE %1\$stbl_bookings.user_id = %2\$d AND %1\$stbl_bookings.route_id = %3\$d AND %1\$stbl_bookings.status = 3 ORDER BY %1\$stbl_bookings.id DESC LIMIT 15", DB_TBL_PREFIX, $_SESSION["uid"], $_SESSION["route_id"]);
    if(($result = mysqli_query($GLOBALS["DB"], $query)) && mysqli_num_rows($result)) {
        $unique_address = [];
        $count = 0;
        while ($row = mysqli_fetch_assoc($result)) {
            if(!in_array_r($row["dropoff_address"], $unique_address)) {
                $count++;
                $unique_address[] = $row["dropoff_address"];
                $recent_bookings_loc[] = $row;
                if($count == 4) {
                }
                break;
            }
        }
    }
    $recent_loc_data = ["route_id" => $route_id, "locations" => $recent_bookings_loc];
    $data = ["success" => 1, "recent_locs" => $recent_loc_data];
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
    $query = sprintf("SELECT * FROM %stbl_appinfo_pages WHERE id = 1 OR id = 3 OR id = 5", DB_TBL_PREFIX);
    if($result = mysqli_query($GLOBALS["DB"], $query)) {
        if(mysqli_num_rows($result)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $user_info_pages[$row["id"]] = $row;
            }
            $data = ["success" => 1, "about" => $user_info_pages[1]["content"], "terms" => $user_info_pages[3]["content"], "drivewith" => $user_info_pages[5]["content"]];
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
    $query = sprintf("SELECT * FROM %1\$stbl_help_cat WHERE %1\$stbl_help_cat.show_rider = 1", DB_TBL_PREFIX);
    if($result = mysqli_query($GLOBALS["DB"], $query)) {
        if(mysqli_num_rows($result)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $help_categories_data[$row["id"]] = $row;
            }
            $help_topics_strings = [];
            $query = sprintf("SELECT id,cat_id,title,excerpt FROM %1\$stbl_appinfo_pages WHERE %1\$stbl_appinfo_pages.show_rider = 1 AND %1\$stbl_appinfo_pages.type = 1", DB_TBL_PREFIX);
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
                    $error = ["error" => __("An error has occured")];
                    echo json_encode($error);
                    exit;
                }
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
    } else {
        $error = ["error" => __("An error has occured")];
        echo json_encode($error);
        exit;
    }
}
function getplacesautocomplete()
{
    if(empty($_SESSION["loggedin"])) {
        exit;
    }
    $place_hint = "";
    $restrict_result = "";
    if(!empty($_GET["place_hint"])) {
        $place_hint = urlencode($_GET["place_hint"]);
        if(strlen($place_hint) < 2) {
            exit;
        }
        $city_radius = !empty($_GET["city_radius"]) ? $_GET["city_radius"] : "2000";
        $autocomp_session = !empty($_GET["session"]) ? $_GET["session"] : time();
        if(!empty($_GET["location_lat"]) && !empty($_GET["location_lng"])) {
            $restrict_result = "&location=" . $_GET["location_lat"] . "," . $_GET["location_lng"] . "&radius=" . $city_radius . "&strictbounds=true&sessionkey=" . $autocomp_session . "x" . $_SESSION["uid"];
        }
        $url = "https://maps.googleapis.com/maps/api/place/autocomplete/json?input=" . $place_hint . "&key=" . GMAP_API_KEY . $restrict_result;
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
            exit;
        }
        $data = ["success" => 1, "places" => $response];
        echo json_encode($data);
        exit;
    }
    exit;
}
function geocodeplace()
{
    if(empty($_SESSION["loggedin"])) {
        $data = ["error" => __("Please login to continue")];
        echo json_encode($data);
        exit;
    }
    $place_id = "";
    $response2 = NULL;
    $tariff_data = [];
    if(!empty($_GET["place_id"])) {
        $place_id = $_GET["place_id"];
        $get_directions = (int) $_GET["get_direction"];
        $point_lat = !empty($_GET["point_lat"]) ? $_GET["point_lat"] : NULL;
        $point_long = !empty($_GET["point_long"]) ? $_GET["point_long"] : NULL;
        $location_type = isset($_GET["location_type"]) && $_GET["location_type"] == 1 ? 1 : 0;
        $url = "https://maps.googleapis.com/maps/api/geocode/json?place_id=" . $place_id . "&key=" . GMAP_API_KEY;
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
        if(!empty($get_directions) && !empty($point_lat) && !empty($point_long) && $response["status"] == "OK") {
            if($location_type) {
                $p_lat = $point_lat;
                $p_lng = $point_long;
                $d_lat = $response["results"][0]["geometry"]["location"]["lat"];
                $d_lng = $response["results"][0]["geometry"]["location"]["lng"];
            } else {
                $p_lat = $response["results"][0]["geometry"]["location"]["lat"];
                $p_lng = $response["results"][0]["geometry"]["location"]["lng"];
                $d_lat = $point_lat;
                $d_lng = $point_long;
            }
            $url = "https://maps.googleapis.com/maps/api/directions/json?origin=" . $p_lat . "," . $p_lng . "&destination=" . $d_lat . "," . $d_lng . "&key=" . GMAP_API_KEY;
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HTTPGET, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            $json_response = curl_exec($curl);
            $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            $response2 = json_decode($json_response, true);
            if(json_last_error()) {
                $data = ["error" => __("An error has occured")];
                echo json_encode($data);
                exit;
            }
            $tariff_data = getroutetariffs();
        }
        $data = ["success" => 1, "place_details" => $response, "directions" => $response2, "tariff_data" => $tariff_data];
        echo json_encode($data);
        exit;
    }
    $data = ["error" => __("Your Selected location is invalid. Please select another location")];
    echo json_encode($data);
    exit;
}
function couponCheck()
{
    if(empty($_SESSION["loggedin"])) {
        $data = ["error" => __("Please login to continue")];
        echo json_encode($data);
        exit;
    }
    if(empty($_GET["coupon_code"])) {
        $data = ["error" => __("Coupon code is invalid")];
        echo json_encode($data);
        exit;
    }
    $msg = "";
    $query = sprintf("SELECT %1\$stbl_coupon_codes.*,%1\$stbl_coupon_codes.id AS coupon_id, %1\$stbl_currencies.symbol FROM %1\$stbl_coupon_codes \r\n    INNER JOIN %1\$stbl_users ON %1\$stbl_users.route_id = %1\$stbl_coupon_codes.city\r\n    INNER JOIN %1\$stbl_routes ON %1\$stbl_routes.id = %1\$stbl_coupon_codes.city\r\n    INNER JOIN %1\$stbl_currencies ON %1\$stbl_currencies.id = %1\$stbl_routes.city_currency_id\r\n    WHERE %1\$stbl_coupon_codes.coupon_code = \"%2\$s\" AND %1\$stbl_users.user_id = %3\$d", DB_TBL_PREFIX, mysqli_real_escape_string($GLOBALS["DB"], $_GET["coupon_code"]), $_SESSION["uid"]);
    if($result = mysqli_query($GLOBALS["DB"], $query)) {
        if(mysqli_num_rows($result)) {
            $row = mysqli_fetch_assoc($result);
            $coupon_start_date = strtotime($row["active_date"]);
            $coupon_end_date = strtotime($row["expiry_date"]);
            if($row["status"]) {
                if($coupon_start_date < time() && time() < $coupon_end_date) {
                    $coupon_vehicles = [];
                    $coupon_vehicles_str = "";
                    $vehicles_msg = "<br>" . __("Coupon is valid for all vehicle types") . "<br>";
                    if(!empty($row["vehicles"])) {
                        $query = sprintf("SELECT id, ride_type FROM %stbl_rides WHERE id IN(%s)", DB_TBL_PREFIX, $row["vehicles"]);
                        if($result = mysqli_query($GLOBALS["DB"], $query)) {
                            if(mysqli_num_rows($result)) {
                                $vehicles_msg = "<br>" . __("Coupon is valid for vehicle type") . "<br>";
                                while ($res = mysqli_fetch_assoc($result)) {
                                    $coupon_vehicles[] = $res;
                                    $vehicles_msg .= "-" . $res["ride_type"] . "<br>";
                                }
                            } else {
                                $data = ["error" => __("An error has occured")];
                                echo json_encode($data);
                                exit;
                            }
                        } else {
                            $data = ["error" => __("An error has occured")];
                            echo json_encode($data);
                            exit;
                        }
                    }
                    $query = sprintf("SELECT SUM(%1\$stbl_coupons_used.times_used) AS all_usage, SUM(IF(%1\$stbl_coupons_used.user_id = %2\$d,%1\$stbl_coupons_used.times_used,NULL)) AS user_usage FROM %1\$stbl_coupons_used WHERE coupon_id = %3\$d", DB_TBL_PREFIX, $_SESSION["uid"], $row["coupon_id"]);
                    if($result = mysqli_query($GLOBALS["DB"], $query)) {
                        if(mysqli_num_rows($result)) {
                            $usage_data = mysqli_fetch_assoc($result);
                            if($row["limit_count"] <= $usage_data["all_usage"] || $row["user_limit_count"] <= $usage_data["user_usage"]) {
                                $data = ["error" => __("Usage limit of this coupon has been exceeded")];
                                echo json_encode($data);
                                exit;
                            }
                        } else {
                            $data = ["error" => __("An error has occured")];
                            echo json_encode($data);
                            exit;
                        }
                    }
                    if($row["discount_type"]) {
                        $msg = __("This coupon qualifies you for a flat fee of {---1}", [$row["symbol"] . $row["discount_value"]]) . $vehicles_msg;
                    } else {
                        $msg = __("This coupon qualifies you for a {---1} discount", [$row["discount_value"] . "%"]) . $vehicles_msg;
                    }
                    $data = ["success" => 1, "message" => $msg];
                    echo json_encode($data);
                    exit;
                }
                $data = ["error" => __("This coupon has expired")];
                echo json_encode($data);
                exit;
            }
            $data = ["error" => __("This coupon is no longer active")];
            echo json_encode($data);
            exit;
        }
        $data = ["error" => __("Invalid coupon code")];
        echo json_encode($data);
        exit;
    }
    $data = ["error" => __("An error has occured")];
    echo json_encode($data);
    exit;
}
function promocodecheck()
{
    if(empty($_SESSION["loggedin"])) {
        $data = ["error" => __("Please login to continue")];
        echo json_encode($data);
        exit;
    }
    if(empty($_GET["coupon_code"])) {
        $data = ["error" => __("Promo code is invalid")];
        echo json_encode($data);
        exit;
    }
    $msg = "";
    $title = "";
    $usage_limit_count = 0;
    $user_usage_count = 0;
    $days_left = 0;
    $city = "";
    $query = sprintf("SELECT %1\$stbl_coupon_codes.*,%1\$stbl_coupon_codes.id AS coupon_id, %1\$stbl_currencies.symbol, %1\$stbl_routes.r_title FROM %1\$stbl_coupon_codes \r\n    INNER JOIN %1\$stbl_users ON %1\$stbl_users.route_id = %1\$stbl_coupon_codes.city\r\n    INNER JOIN %1\$stbl_routes ON %1\$stbl_routes.id = %1\$stbl_coupon_codes.city\r\n    INNER JOIN %1\$stbl_currencies ON %1\$stbl_currencies.id = %1\$stbl_routes.city_currency_id\r\n    WHERE %1\$stbl_coupon_codes.coupon_code = \"%2\$s\" AND %1\$stbl_users.user_id = %3\$d", DB_TBL_PREFIX, mysqli_real_escape_string($GLOBALS["DB"], $_GET["coupon_code"]), $_SESSION["uid"]);
    if($result = mysqli_query($GLOBALS["DB"], $query)) {
        if(mysqli_num_rows($result)) {
            $row = mysqli_fetch_assoc($result);
            $coupon_start_date = strtotime($row["active_date"]);
            $coupon_end_date = strtotime($row["expiry_date"]);
            $usage_limit_count = $row["user_limit_count"];
            $title = $row["coupon_title"];
            $city = $row["r_title"];
            $seconds_to_expiry = $coupon_end_date - time();
            if($seconds_to_expiry < 0) {
                $days_left = 0;
            } else {
                $days_left = ceil($seconds_to_expiry / 86400);
            }
            if($row["status"]) {
                if($coupon_start_date < time() && time() < $coupon_end_date) {
                    $coupon_vehicles = [];
                    $coupon_vehicles_str = "";
                    $vehicles_msg = "";
                    if(!empty($row["vehicles"])) {
                        $query = sprintf("SELECT id, ride_type FROM %stbl_rides WHERE id IN(%s)", DB_TBL_PREFIX, $row["vehicles"]);
                        if($result = mysqli_query($GLOBALS["DB"], $query)) {
                            if(mysqli_num_rows($result)) {
                                $vehicles_msg = "<br>" . __("Promo is valid for vehicle type") . "<br>";
                                while ($res = mysqli_fetch_assoc($result)) {
                                    $coupon_vehicles[] = $res;
                                    $vehicles_msg .= $res["ride_type"] . ",";
                                }
                            } else {
                                $data = ["error" => __("An error has occured")];
                                echo json_encode($data);
                                exit;
                            }
                        } else {
                            $data = ["error" => __("An error has occured")];
                            echo json_encode($data);
                            exit;
                        }
                    }
                    $query = sprintf("SELECT SUM(%1\$stbl_coupons_used.times_used) AS all_usage, SUM(IF(%1\$stbl_coupons_used.user_id = %2\$d,%1\$stbl_coupons_used.times_used,NULL)) AS user_usage FROM %1\$stbl_coupons_used WHERE coupon_id = %3\$d", DB_TBL_PREFIX, $_SESSION["uid"], $row["coupon_id"]);
                    if($result = mysqli_query($GLOBALS["DB"], $query)) {
                        if(mysqli_num_rows($result)) {
                            $usage_data = mysqli_fetch_assoc($result);
                            $user_usage_count = empty($usage_data["user_usage"]) ? 0 : $usage_data["user_usage"];
                            if($row["limit_count"] <= $usage_data["all_usage"] || $row["user_limit_count"] <= $usage_data["user_usage"]) {
                                $data = ["error" => __("Usage limit of this promo code has been exceeded")];
                                echo json_encode($data);
                                exit;
                            }
                        } else {
                            $data = ["error" => __("An error has occured")];
                            echo json_encode($data);
                            exit;
                        }
                    }
                    if($row["discount_type"]) {
                        $msg = __("This promo qualifies you for a flat fee of {---1}", [$row["symbol"] . $row["discount_value"]]) . $vehicles_msg;
                    } else {
                        $msg = __("This promo qualifies you for a {---1} discount", [$row["discount_value"] . "%"]) . $vehicles_msg;
                    }
                    $data = ["success" => 1, "message" => $msg, "usage_limit_count" => $usage_limit_count, "user_usage_count" => $user_usage_count, "days_left" => $days_left, "coupon_title" => $title, "city" => $city];
                    echo json_encode($data);
                    exit;
                }
                $data = ["error" => __("This promo has expired")];
                echo json_encode($data);
                exit;
            }
            $data = ["error" => __("This promo is no longer active")];
            echo json_encode($data);
            exit;
        }
        $data = ["error" => __("Promo code is invalid")];
        echo json_encode($data);
        exit;
    }
    $data = ["error" => __("An error has occured")];
    echo json_encode($data);
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
    $waypoints_str = "";
    $waypoint1 = "";
    $waypoint2 = "";
    $waypoints_data = $_GET["waypoints"];
    if(isset($waypoints_data["dest-1"]) && !empty($waypoints_data["dest-1"]["address"])) {
        $waypoint1 = $waypoints_data["dest-1"]["lat"] . "," . $waypoints_data["dest-1"]["lng"];
    }
    if(isset($waypoints_data["dest-2"]) && !empty($waypoints_data["dest-2"]["address"])) {
        $waypoint2 = $waypoints_data["dest-2"]["lat"] . "," . $waypoints_data["dest-2"]["lng"];
    }
    if(!empty($waypoint1) && !empty($waypoint2)) {
        $waypoints_str = "&waypoints=via:" . $waypoint1 . "|" . $waypoint2;
    } elseif(!empty($waypoint1)) {
        $waypoints_str = "&waypoints=via:" . $waypoint1;
    } elseif(!empty($waypoint2)) {
        $waypoints_str = "&waypoints=via:" . $waypoint2;
    }
    $tariff_data = [];
    $url = "https://maps.googleapis.com/maps/api/directions/json?origin=" . $_GET["p-lat"] . "," . $_GET["p-lng"] . "&destination=" . $_GET["d-lat"] . "," . $_GET["d-lng"] . "&key=" . GMAP_API_KEY . $waypoints_str;
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
        $data = ["error" => __("An error has occured"), "msg" => $response];
        echo json_encode($data);
        exit;
    }
    $tariff_data = getroutetariffs();
    $data = ["success" => 1, "direction_details" => $response, "tariff_data" => $tariff_data];
    echo json_encode($data);
    exit;
}
function checkLoginStatus()
{
    if(isset($_POST["timezone"]) && isValidTimezoneId($_POST["timezone"])) {
        $_SESSION["timezone"] = $_POST["timezone"];
        date_default_timezone_set($_SESSION["timezone"]);
    } else {
        date_default_timezone_set("Africa/Lagos");
    }
    $display_language = mysqli_real_escape_string($GLOBALS["DB"], $_POST["display_lang"]);
    if(!empty($_SESSION["loggedin"])) {
        $user_account_details = [];
        $query = sprintf("SELECT reward_points,country_code, route_id,country_dial_code,user_rating,photo_file,account_active,referal_code,push_notification_token,`address`,account_type,user_id,firstname, lastname,email,phone,is_activated,account_active,last_login_date,account_create_date,referal_code,wallet_amount FROM %stbl_users WHERE user_id = %d", DB_TBL_PREFIX, $_SESSION["uid"]);
        if($result = mysqli_query($GLOBALS["DB"], $query)) {
            if(mysqli_num_rows($result)) {
                $user_account_details = mysqli_fetch_assoc($result);
                if($user_account_details["is_activated"] == 0) {
                    $data = ["success" => "1", "is_activated" => $user_account_details["is_activated"], "loggedin" => 0];
                    echo json_encode($data);
                    exit;
                }
                $ref_code_settings = [];
                $query = sprintf("SELECT * FROM %stbl_referral WHERE id = 1", DB_TBL_PREFIX);
                if(($result = mysqli_query($GLOBALS["DB"], $query)) && mysqli_num_rows($result)) {
                    $ref_code_settings = mysqli_fetch_assoc($result);
                }
                $referral_url = SITE_URL;
                $refcode_copy = isset($ref_code_settings["status"]) && $ref_code_settings["status"] == 1 && !empty($user_account_details["referal_code"]) ? __("Hi, you can now book rides from your phone anywhere, anytime using Droptaxi Taxi service. Sign-up now with my referral code {---1} and get a discount on your first ride.", [(string) $user_account_details["referal_code"]], "r|" . $display_language) : "";
                $ref_code_desc = __("Earn {---1}% discount on your next ride when you invite a friend to register on Droptaxi using your referral code", [$ref_code_settings["discount_value"]], "r|" . $display_language);
                $ref_code = isset($ref_code_settings["status"]) && $ref_code_settings["status"] == 1 && !empty($user_account_details["referal_code"]) ? "<input id='user-refcode-text' type='text' hidden='hidden' value='" . $refcode_copy . "'><span style='display:block;color:#42a5f5;font-family: Roboto,Noto,sans-serif;font-size:13px;font-weight:400;'>" . __("Referral code") . "</span><p style='margin-top:5px;padding: 15px 5px;border: thin dashed;'><b id='user-refcode'>" . $user_account_details["referal_code"] . "</b><b style='color: blue;float: right;' onclick=share_message('',\$('#user-refcode-text').val(),'" . $referral_url . "') >" . __("Share") . "</b></p><p>" . $ref_code_desc . "</p>" : "";
                if(isset($user_account_details["push_notification_token"])) {
                    $_SESSION["push_token"] = $user_account_details["push_notification_token"];
                }
                $default_currency_data = [];
                $query = sprintf("SELECT * FROM %stbl_currencies WHERE `default` = 1", DB_TBL_PREFIX);
                if(($result = mysqli_query($GLOBALS["DB"], $query)) && mysqli_num_rows($result)) {
                    $default_currency_data = mysqli_fetch_assoc($result);
                }
                $tariff_data = getroutetariffs();
                $photo_file = isset($user_account_details["photo_file"]) ? $user_account_details["photo_file"] : "0";
                $_SESSION["firstname"] = $user_account_details["firstname"];
                $_SESSION["lastname"] = $user_account_details["lastname"];
                $_SESSION["uid"] = $user_account_details["user_id"];
                $_SESSION["email"] = $user_account_details["email"];
                $_SESSION["route_id"] = $user_account_details["route_id"];
                $_SESSION["phone"] = $user_account_details["phone"];
                $_SESSION["country_dial_code"] = $user_account_details["country_dial_code"];
                $_SESSION["address"] = $user_account_details["address"];
                $_SESSION["referal_code"] = $user_account_details["referal_code"];
                $_SESSION["account_type"] = $user_account_details["account_type"];
                $_SESSION["lastseen"] = $user_account_details["last_login_date"];
                $_SESSION["joined"] = $user_account_details["account_create_date"];
                $_SESSION["loggedin"] = 1;
                $_SESSION["reward_points"] = $user_account_details["reward_points"];
                $_SESSION["is_activated"] = $user_account_details["is_activated"];
                $_SESSION["user_rating"] = $user_account_details["user_rating"];
                $_SESSION["wallet_amt"] = $user_account_details["wallet_amount"];
                $_SESSION["push_token"] = $user_account_details["push_notification_token"];
                $_SESSION["photo"] = SITE_URL . "ajaxuserphotofile.php?file=" . $photo_file;
                $profiledata = ["success" => 1, "firstname" => $_SESSION["firstname"], "lastname" => $_SESSION["lastname"], "email" => $_SESSION["email"], "route_id" => $_SESSION["route_id"], "phone" => $_SESSION["phone"], "address" => $_SESSION["address"], "userid" => $_SESSION["uid"], "ref_code" => $ref_code, "ref_code_copy_msg" => $refcode_copy, "ref_desc" => $ref_code_desc, "photo" => $_SESSION["photo"], "user_rating" => $_SESSION["user_rating"], "country_code" => $user_account_details["country_code"], "country_dial_code" => $user_account_details["country_dial_code"]];
                $online_payment_data = ["merchantid" => P_G_PK, "storeid" => STORE_ID, "devid" => DEV_ID, "notifyurl" => NOTIFY_URL];
                $app_settings = ["payment_type" => PAYMENT_TYPE, "ride_otp" => RIDE_OTP, "default_payment_gateway" => DEFAULT_PAYMENT_GATEWAY, "wallet_topup_presets" => WALLET_TOPUP_PRESETS, "driver_tip_presets" => DRIVER_TIP_PRESETS];
                $firebase_rtdb_conf = ["databaseURL" => FB_RTDB_URL, "apiKey" => FB_WEB_API_KEY, "storageBucket" => FB_STORAGE_BCKT];
                $query = sprintf("UPDATE %stbl_users SET last_login_date = \"%s\", disp_lang = \"%s\", login_count = login_count + 1 WHERE user_id = %d", DB_TBL_PREFIX, gmdate("Y-m-d H:i:s", time()), $display_language, $_SESSION["uid"]);
                $result = mysqli_query($GLOBALS["DB"], $query);
                $_SESSION["lang"] = $display_language;
                $ongoing_booking = [];
                $query = sprintf("SELECT *,%1\$stbl_bookings.id AS booking_id FROM %1\$stbl_bookings \r\n        INNER JOIN %1\$stbl_drivers ON %1\$stbl_drivers.driver_id = %1\$stbl_bookings.driver_id\r\n        INNER JOIN %1\$stbl_driver_location ON %1\$stbl_driver_location.driver_id = %1\$stbl_bookings.driver_id\r\n        WHERE %1\$stbl_bookings.user_id = %2\$d AND %1\$stbl_bookings.driver_id != 0 AND (%1\$stbl_bookings.status = 0 OR %1\$stbl_bookings.status = 1) ORDER BY %1\$stbl_bookings.id DESC LIMIT 1", DB_TBL_PREFIX, $_SESSION["uid"]);
                if(($result = mysqli_query($GLOBALS["DB"], $query)) && mysqli_num_rows($result)) {
                    $row = mysqli_fetch_assoc($result);
                    $action = "";
                    if(!empty($row["date_arrived"]) && $row["status"] == 0) {
                        $action = "driver-arrived";
                    } elseif(empty($row["date_arrived"]) && $row["status"] == 0) {
                        $action = "driver-assigned";
                    } else {
                        $action = "customer-onride";
                    }
                    $driver_photo_file = isset($row["photo_file"]) ? $row["photo_file"] : "0";
                    $ongoing_booking = ["action" => $action, "route_id" => $row["route_id"], "booking_id" => $row["booking_id"], "driver_id" => $row["driver_id"], "driver_firstname" => $row["firstname"], "driver_phone" => $row["phone"], "driver_platenum" => $row["car_plate_num"], "driver_rating" => $row["driver_rating"], "driver_location_lat" => $row["lat"], "driver_location_long" => $row["long"], "pickup_lat" => $row["pickup_lat"], "pickup_long" => $row["pickup_long"], "dropoff_lat" => $row["dropoff_lat"], "dropoff_long" => $row["dropoff_long"], "driver_carmodel" => $row["car_model"], "driver_carid" => $row["ride_id"], "driver_completed_rides" => $row["completed_rides"], "completion_code" => $row["completion_code"], "driver_photo" => SITE_URL . "ajaxphotofile.php?file=" . $driver_photo_file];
                }
                $recent_bookings_loc = [];
                if(!empty($_SESSION["route_id"])) {
                    $query = sprintf("SELECT %1\$stbl_bookings.dropoff_address, %1\$stbl_bookings.dropoff_long, %1\$stbl_bookings.dropoff_lat FROM %1\$stbl_bookings\r\n            WHERE %1\$stbl_bookings.user_id = %2\$d AND %1\$stbl_bookings.route_id = %3\$d AND %1\$stbl_bookings.status = 3 ORDER BY %1\$stbl_bookings.id DESC LIMIT 10", DB_TBL_PREFIX, $_SESSION["uid"], $_SESSION["route_id"]);
                    if(($result = mysqli_query($GLOBALS["DB"], $query)) && mysqli_num_rows($result)) {
                        $unique_address = [];
                        $count = 0;
                        while ($row = mysqli_fetch_assoc($result)) {
                            if(!in_array_r($row["dropoff_address"], $unique_address)) {
                                $count++;
                                $unique_address[] = $row["dropoff_address"];
                                $recent_bookings_loc[] = $row;
                                if($count == 4) {
                                }
                                break;
                            }
                        }
                    }
                }
                $recent_loc_data = ["route_id" => $_SESSION["route_id"], "locations" => $recent_bookings_loc];
                $data = ["loggedin" => 1, "ongoing_bk" => $ongoing_booking, "fb_conf" => $firebase_rtdb_conf, "recent_locs" => $recent_loc_data, "is_activated" => $_SESSION["is_activated"], "account_active" => $user_account_details["account_active"], "wallet_amt" => $_SESSION["wallet_amt"], "reward_points" => $_SESSION["reward_points"], "cc_num" => CALL_CENTER_NUMBER, "profileinfo" => $profiledata, "tariff_data" => $tariff_data, "online_pay" => $online_payment_data, "app_version_ios" => APP_VERSION_CUSTOMER_IOS, "app_version_android" => APP_VERSION_CUSTOMER_ANDROID, "customer_app_update_url_ios" => CUSTOMER_APP_UPDATE_URL_IOS, "customer_app_update_url_android" => CUSTOMER_APP_UPDATE_URL_ANDROID, "scheduled_ride_enabled" => SCHEDULED_RIDE_ENABLED, "default_currency" => $default_currency_data, "app_settings" => $app_settings, "sess_id" => base64_encode(session_id())];
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
    $tariff_data = getroutetariffs();
    $display_language = mysqli_real_escape_string($GLOBALS["DB"], $_POST["display_lang"]);
    $_SESSION["lang"] = $display_language;
    $data = ["loggedin" => 0, "tariff_data" => $tariff_data, "app_version_ios" => APP_VERSION_CUSTOMER_IOS, "app_version_android" => APP_VERSION_CUSTOMER_ANDROID, "customer_app_update_url_ios" => CUSTOMER_APP_UPDATE_URL_IOS, "customer_app_update_url_android" => CUSTOMER_APP_UPDATE_URL_ANDROID, "cc_num" => CALL_CENTER_NUMBER, "sess_id" => base64_encode(session_id())];
    echo json_encode($data);
    exit;
}
function userRegister()
{
    $_POST["firstname"] = str_replace(" ", "", $_POST["firstname"]);
    $_POST["lastname"] = str_replace(" ", "", $_POST["lastname"]);
    $_POST["email"] = trim($_POST["email"]);
    if(empty($_POST["firstname"])) {
        $error = ["error" => __("Please enter your first name")];
        echo json_encode($error);
        exit;
    }
    if(empty($_POST["lastname"])) {
        $error = ["error" => __("Please enter your last name")];
        echo json_encode($error);
        exit;
    }
    if(!filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
        $error = ["error" => __("Your email is not a valid email format")];
        echo json_encode($error);
        exit;
    }
    if(64 < strlen($_POST["email"])) {
        $error = ["error" => __("Your email is too long")];
        echo json_encode($error);
        exit;
    }
    if(20 < strlen($_POST["phone"])) {
        $error = ["error" => __("Your phone number is too long")];
        echo json_encode($error);
        exit;
    }
    if(strlen($_POST["phone"]) < 5) {
        $error = ["error" => __("Your phone number is too short")];
        echo json_encode($error);
        exit;
    }
    if(strlen($_POST["password"]) < 8) {
        $error = ["error" => __("Password is too short")];
        echo json_encode($error);
        exit;
    }
    if(60 < strlen($_POST["password"])) {
        $error = ["error" => __("Password is too long")];
        echo json_encode($error);
        exit;
    }
    if($_POST["password"] !== $_POST["rpassword"]) {
        $error = ["error" => __("Passwords are not the same")];
        echo json_encode($error);
        exit;
    }
    if(empty($_POST["password"]) || empty($_POST["rpassword"])) {
        $error = ["error" => __("Please enter a password")];
        echo json_encode($error);
        exit;
    }
    $user_country = codeToCountryName(strtoupper($_POST["country_code"]));
    if(!$user_country) {
        $error = ["error" => __("Invalid country selected")];
        echo json_encode($error);
        exit;
    }
    $query = sprintf("SELECT user_id,email,phone,country_dial_code FROM %stbl_users WHERE email = \"%s\" OR phone = \"%s\"", DB_TBL_PREFIX, mysqli_real_escape_string($GLOBALS["DB"], $_POST["email"]), mysqli_real_escape_string($GLOBALS["DB"], $_POST["phone"]));
    if($result = mysqli_query($GLOBALS["DB"], $query)) {
        if(mysqli_num_rows($result)) {
            $row = mysqli_fetch_assoc($result);
            if($row["email"] == mysqli_real_escape_string($GLOBALS["DB"], $_POST["email"])) {
                $error = ["error" => __("The email already exists. Please use a different email")];
                echo json_encode($error);
                exit;
            }
            if($row["country_dial_code"] . $row["phone"] == "+" . mysqli_real_escape_string($GLOBALS["DB"], $_POST["country_dial_code"]) . mysqli_real_escape_string($GLOBALS["DB"], $_POST["phone"])) {
                $error = ["error" => __("The phone number already exists. Please use a different phone number")];
                echo json_encode($error);
                exit;
            }
            $error = ["error" => __("The email or phone number already exists. Please use a different email or phone number")];
            echo json_encode($error);
            exit;
        }
        if($_POST["validate_only"] == 1) {
            $data = ["success" => 1];
            echo json_encode($data);
            exit;
        }
        if(!empty($_POST["userphoto"])) {
            $uploaded_photo_encoded = $_POST["userphoto"];
            $uploaded_photo_encoded_array = explode(",", $uploaded_photo_encoded);
            $image_data = array_pop($uploaded_photo_encoded_array);
            $uploaded_photo_decoded = base64_decode($image_data);
            if(!$uploaded_photo_decoded) {
                $error = ["error" => __("Please upload a passport photo in JPEG format")];
                echo json_encode($error);
                exit;
            }
            $filename = crypto_string("distinct", 20);
            @mkdir(@realpath(CUSTOMER_PHOTO_PATH) . "/" . $filename[0] . "/" . $filename[1] . "/" . $filename[2], 511, true);
            $image_path = realpath(CUSTOMER_PHOTO_PATH) . "/" . $filename[0] . "/" . $filename[1] . "/" . $filename[2] . "/";
            $file = $image_path . $filename . ".jpg";
            file_put_contents($file, $uploaded_photo_decoded);
            $user_passport_photo = $filename . ".jpg";
        } else {
            $user_passport_photo = "";
        }
        $ref_code_result_msg = __("Thank you for joining our ride service. We hope you enjoy every ride you take. Enjoy!");
        $ref_user_data = [];
        $referal_settings_data = [];
        $query = sprintf("SELECT `status`,beneficiary,discount_value FROM %stbl_referral WHERE id = %d", DB_TBL_PREFIX, 1);
        if(($result = mysqli_query($GLOBALS["DB"], $query)) && mysqli_num_rows($result)) {
            $referal_settings_data = mysqli_fetch_assoc($result);
        }
        $invitee_referal_benefit = 0;
        if(!empty($_POST["ref_code"]) && strlen($_POST["ref_code"]) < 11 && !empty($referal_settings_data["status"])) {
            $ref_code = mysqli_real_escape_string($GLOBALS["DB"], $_POST["ref_code"]);
            $query = sprintf("SELECT user_id FROM %stbl_users WHERE referal_code = \"%s\"", DB_TBL_PREFIX, $ref_code);
            if(($result = mysqli_query($GLOBALS["DB"], $query)) && mysqli_num_rows($result)) {
                $ref_user_data = mysqli_fetch_assoc($result);
                $customer_notification_msg = "";
                if($referal_settings_data["beneficiary"] == 0 || $referal_settings_data["beneficiary"] == 2) {
                    $query = sprintf("UPDATE %stbl_users SET referral_count = referral_count + 1, referral_discounts_count = referral_discounts_count + 1 WHERE user_id = %d", DB_TBL_PREFIX, $ref_user_data["user_id"]);
                    $result = mysqli_query($GLOBALS["DB"], $query);
                    $customer_notification_msg = __("A new customer registered on the serivce with your referral code. You are eligible to a {---1} discount on your next ride. Thank you", [$referal_settings_data["discount_value"] . "%"]);
                    $query = sprintf("INSERT INTO %stbl_notifications(person_id,user_type,content,n_type,date_created) VALUES \r\n                        (\"%d\",0,\"%s\",0,\"%s\")", DB_TBL_PREFIX, $ref_user_data["user_id"], mysqli_real_escape_string($GLOBALS["DB"], $customer_notification_msg), gmdate("Y-m-d H:i:s", time()));
                    $result = mysqli_query($GLOBALS["DB"], $query);
                } elseif($referal_settings_data["beneficiary"] == 1) {
                    $query = sprintf("UPDATE %stbl_users SET referral_count = referral_count + 1 WHERE user_id = %d", DB_TBL_PREFIX, $ref_user_data["user_id"]);
                    $result = mysqli_query($GLOBALS["DB"], $query);
                    $customer_notification_msg = __("A new customer registered on the serivce with your referral code. Thank you for growing our great and awesome service");
                    $query = sprintf("INSERT INTO %stbl_notifications(person_id,user_type,content,n_type,date_created) VALUES \r\n                        (\"%d\",0,\"%s\",0,\"%s\")", DB_TBL_PREFIX, $ref_user_data["user_id"], mysqli_real_escape_string($GLOBALS["DB"], $customer_notification_msg), gmdate("Y-m-d H:i:s", time()));
                    $result = mysqli_query($GLOBALS["DB"], $query);
                }
                if($referal_settings_data["beneficiary"] == 1 || $referal_settings_data["beneficiary"] == 2) {
                    $invitee_referal_benefit = 1;
                    $ref_code_result_msg = __("Thank you for joining our ride service. The referral code you registered with has earned you a {---1} discount on your next ride. Enjoy", [$referal_settings_data["discount_value"] . "%"]);
                }
            }
        }
        $new_user_referal_code = "";
        $x = 0;
        while ($x < 10) {
            $new_user_referal_code = crypto_string("ABCDEFGHIJKLMNOPQRSTUVWXYZ", 4);
            $new_user_referal_code .= crypto_string("123456789", 4);
            $query = sprintf("SELECT * FROM %stbl_users WHERE referal_code = \"%s\"", DB_TBL_PREFIX, $new_user_referal_code);
            if($result = mysqli_query($GLOBALS["DB"], $query)) {
                if(mysqli_num_rows($result)) {
                    $x++;
                }
                break;
            }
        }
        $verified_phone_number = (int) $_POST["verified_phone_num"];
        $query = sprintf("INSERT INTO %stbl_users (route_id,account_active,is_activated,`address`,firstname, lastname, email, phone, pwd_raw, password_hash, account_create_date,referal_code,photo_file, referral_discounts_count,country, country_code, country_dial_code) VALUES(\"%d\",\"%d\",\"%d\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\")", DB_TBL_PREFIX, 1, 1, $verified_phone_number, mysqli_real_escape_string($GLOBALS["DB"], ucfirst(strtolower(strip_tags($_POST["address"])))), mysqli_real_escape_string($GLOBALS["DB"], ucfirst(strtolower(strip_tags($_POST["firstname"])))), mysqli_real_escape_string($GLOBALS["DB"], ucfirst(strtolower(strip_tags($_POST["lastname"])))), mysqli_real_escape_string($GLOBALS["DB"], strip_tags($_POST["email"])), mysqli_real_escape_string($GLOBALS["DB"], strip_tags($_POST["phone"])), mysqli_real_escape_string($GLOBALS["DB"], $_POST["password"]), password_hash(mysqli_real_escape_string($GLOBALS["DB"], $_POST["password"]), PASSWORD_DEFAULT), gmdate("Y-m-d H:i:s", time()), $new_user_referal_code, $user_passport_photo, $invitee_referal_benefit, $user_country, mysqli_real_escape_string($GLOBALS["DB"], $_POST["country_code"]), "+" . mysqli_real_escape_string($GLOBALS["DB"], $_POST["country_dial_code"]));
        if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
            $error = ["error" => __("An error has occured")];
            echo json_encode($error);
            exit;
        }
        $user_id = mysqli_insert_id($GLOBALS["DB"]);
        $query = sprintf("INSERT INTO %stbl_notifications(person_id,user_type,content,n_type,date_created) VALUES \r\n            (\"%d\",0,\"%s\",0,\"%s\")", DB_TBL_PREFIX, $user_id, mysqli_real_escape_string($GLOBALS["DB"], $ref_code_result_msg), gmdate("Y-m-d H:i:s", time()));
        $result = mysqli_query($GLOBALS["DB"], $query);
        $token = crypto_string("nozero", 5);
        if(!$user_id) {
            $error = ["error" => __("An error has occured")];
            echo json_encode($error);
            exit;
        }
        $query = sprintf("INSERT INTO %stbl_account_codes (user_id, code) VALUES (\"%d\",\"%s\")", DB_TBL_PREFIX, $user_id, $token);
        if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
            $query = sprintf("DELETE FROM %stbl_users WHERE user_id = \"%d\"", DB_TBL_PREFIX, $user_id);
            $result = mysqli_query($GLOBALS["DB"], $query);
            $error = ["error" => __("An error has occured")];
            echo json_encode($error);
            exit;
        }
        $mail_sender_address = "From: " . MAIL_SENDER;
        $headers = [$mail_sender_address, "MIME-Version: 1.0", "Content-Type: text/html; charset=\"iso-8859-1\""];
        if(1) {
            if(EMAIL_TRANSPORT == 1) {
                mail($_POST["email"], RIDERS_REG_EMAIL_SUBJ, "<html>" . RIDERS_REG_EMAIL_MSG . "</html>", join("\r\n", $headers));
            } else {
                sendMail($_POST["email"], RIDERS_REG_EMAIL_SUBJ, RIDERS_REG_EMAIL_MSG);
            }
        }
        $_SESSION["new_reg"]["uid"] = $user_id;
        $_SESSION["new_reg"]["email"] = $_POST["email"];
        $_SESSION["new_reg"]["phone"] = $_POST["phone"];
        $data = ["success" => 1];
        echo json_encode($data);
        exit;
    }
    $error = ["error" => __("An error has occured")];
    echo json_encode($error);
    exit;
}
function userResendCode()
{
    if(isset($_SESSION["code_resend"]["time"]) && $_SESSION["code_resend"]["time"] < time() - 60) {
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
    $query = sprintf("DELETE FROM %stbl_account_codes WHERE user_id = \"%d\" AND context=0", DB_TBL_PREFIX, $user_id);
    $result = mysqli_query($GLOBALS["DB"], $query);
    $query = sprintf("INSERT INTO %stbl_account_codes (user_id, code) VALUES (\"%d\",\"%s\")", DB_TBL_PREFIX, $user_id, $code);
    if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
        $error = ["error" => "Error resending activation code"];
        echo json_encode($error);
        exit;
    }
    $message = "";
    $message .= "<html>";
    $message .= "<div style = \"width:100%;\"><img src=\"http://" . $_SERVER["HTTP_HOST"] . "/img/logo-mid.png\" width=\"180px\" style=\"margin-left:auto; margin-right:auto; display:block;\"/><br/>";
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
    $query = sprintf("SELECT user_id,email,phone FROM %stbl_users WHERE email = \"%s\"", DB_TBL_PREFIX, $email);
    if($result = mysqli_query($GLOBALS["DB"], $query)) {
        if(mysqli_num_rows($result)) {
            $user_account_details = mysqli_fetch_assoc($result);
            $code = crypto_string("nozero", 6);
            $query = sprintf("DELETE FROM %stbl_account_codes WHERE user_id = \"%d\" AND user_type = 0 AND context=1", DB_TBL_PREFIX, $user_account_details["user_id"]);
            $result = mysqli_query($GLOBALS["DB"], $query);
            $query = sprintf("INSERT INTO %stbl_account_codes (user_id, code,user_type,context) VALUES (\"%d\",\"%s\",0,1)", DB_TBL_PREFIX, $user_account_details["user_id"], $code);
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
    $query = sprintf("SELECT user_id FROM %stbl_account_codes WHERE code = \"%s\" AND user_type = 0 AND context = 1", DB_TBL_PREFIX, $passcode);
    if($result = mysqli_query($GLOBALS["DB"], $query)) {
        if(mysqli_num_rows($result)) {
            $user_account_details = mysqli_fetch_assoc($result);
            $newpassword = crypto_string("hexdec", 5);
            $query = sprintf("UPDATE %stbl_users SET `pwd_raw` = \"%s\" WHERE user_id = \"%d\"", DB_TBL_PREFIX, $newpassword, $user_account_details["user_id"]);
            if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
                $error = ["error" => __("An error has occured")];
                echo json_encode($error);
                exit;
            }
            $query = sprintf("DELETE FROM %stbl_account_codes WHERE code = \"%s\" AND user_type = 0 AND context=1", DB_TBL_PREFIX, $passcode);
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
        $error = ["error" => __("Please enter an activation code")];
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
    $query = sprintf("SELECT code FROM %stbl_account_codes WHERE code = \"%d\" AND user_id = \"%d\" AND context = 0", DB_TBL_PREFIX, $code, $user_id);
    if($result = mysqli_query($GLOBALS["DB"], $query)) {
        if(mysqli_num_rows($result)) {
            $row = mysqli_fetch_assoc($result);
            $query = sprintf("UPDATE %stbl_users SET is_activated = 1, account_active = 1 WHERE user_id = \"%d\"", DB_TBL_PREFIX, $user_id);
            if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
                $error = ["error" => __("An error has occured.")];
                echo json_encode($error);
                exit;
            }
            $query = sprintf("DELETE FROM %stbl_account_codes WHERE user_id = \"%d\" AND code=\"%d\"", DB_TBL_PREFIX, $user_id, $code);
            $result = mysqli_query($GLOBALS["DB"], $query);
            $_SESSION["is_activated"] = 1;
            $response = ["success" => __("Your account has been successfully activated. Restart the App")];
            echo json_encode($response);
            exit;
        }
        $error = ["error" => __("Wrong activation code")];
        echo json_encode($error);
        exit;
    }
    $error = ["error" => __("An error has occured")];
    echo json_encode($error);
    exit;
}
function messagedriver()
{
    $driver_id = (int) $_POST["driver_id"];
    $content = $_POST["content"];
    if(empty($_SESSION["loggedin"])) {
        $error = ["error" => "Please re-login and retry."];
        echo json_encode($error);
        exit;
    }
    $query = sprintf("INSERT INTO %stbl_notifications(person_id,user_type,content,n_type,date_created) VALUES \r\n        (\"%d\",1,\"%s\",1,\"%s\")", DB_TBL_PREFIX, $driver_id, mysqli_real_escape_string($GLOBALS["DB"], $content), gmdate("Y-m-d H:i:s", time()));
    if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
        $error = ["error" => "Failed to send message."];
        echo json_encode($error);
        exit;
    }
    $success = ["success" => "Message sent successfully"];
    echo json_encode($success);
    exit;
}
function messagecustomer()
{
    $user_id = (int) $_POST["user_id"];
    $content = $_POST["content"];
    if(empty($_SESSION["loggedin"])) {
        $error = ["error" => "Please re-login and retry."];
        echo json_encode($error);
        exit;
    }
    $query = sprintf("INSERT INTO %stbl_notifications(person_id,user_type,content,n_type,date_created) VALUES \r\n        (\"%d\",0,\"%s\",1,\"%s\")", DB_TBL_PREFIX, $user_id, mysqli_real_escape_string($GLOBALS["DB"], $content), gmdate("Y-m-d H:i:s", time()));
    if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
        $error = ["error" => "Failed to send message."];
        echo json_encode($error);
        exit;
    }
    $success = ["success" => "Message sent successfully"];
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
function getroutetariffs()
{
    $tariff_data = [];
    $rides_data = [];
    $query = sprintf("SELECT *,%1\$stbl_routes.id AS route_id  FROM %1\$stbl_routes\r\n    INNER JOIN %1\$stbl_currencies ON %1\$stbl_currencies.id = %1\$stbl_routes.city_currency_id\r\n    INNER JOIN %1\$stbl_rides_tariffs ON %1\$stbl_rides_tariffs.routes_id = %1\$stbl_routes.id\r\n    INNER JOIN %1\$stbl_rides ON %1\$stbl_rides_tariffs.ride_id = %1\$stbl_rides.id\r\n    WHERE %1\$stbl_rides.avail = 1 ORDER BY %1\$stbl_routes.r_title, %1\$stbl_rides.id ASC", DB_TBL_PREFIX);
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
                $select_options .= "<img data-cfare='" . $ridesdata["cfare_enabled"] . "' data-ppenabled='" . $ridesdata["pp_enabled"] . "' data-ppstart='" . $ridesdata["pp_start"] . "' data-ppend='" . $ridesdata["pp_end"] . "' data-ppdays='" . $ridesdata["pp_active_days"] . "' data-ppchargetype='" . $ridesdata["pp_charge_type"] . "' data-ppchargevalue='" . $ridesdata["pp_charge_value"] . "' data-img='" . $ride_image . "' data-cpk = '" . $ridesdata["cost_per_km"] . "' data-cpm = '" . $ridesdata["cost_per_minute"] . "' data-puc = '" . $ridesdata["pickup_cost"] . "' data-doc='" . $ridesdata["drop_off_cost"] . "' data-cc='" . $ridesdata["cancel_cost"] . "' data-ncpk = '" . $ridesdata["ncost_per_km"] . "' data-ncpm = '" . $ridesdata["ncost_per_minute"] . "' data-npuc = '" . $ridesdata["npickup_cost"] . "' data-ndoc='" . $ridesdata["ndrop_off_cost"] . "' data-ncc='" . $ridesdata["ncancel_cost"] . "' value='" . $ridesdata["ride_id"] . "' data-rideid='" . $ridesdata["ride_id"] . "' data-ridedesc='" . $ride_desc . "' data-title='" . $ride_title . "' style='width:100px;margin-right:auto;margin-left:auto;' src='" . $ride_image . "' />";
            }
            $rides_data[$key]["cars_html"] = $select_options;
        }
    }
    $rides_data["city"] = $city_select_options;
    $rides_data["state"] = $state_select_options;
    $rides_data["preloadrides"] = $rides_url;
    if(PAYMENT_TYPE == 2) {
        $rides_data["payment_options"] = "<option value='1'>" . __("Cash") . "</option><option value='2'>" . __("Wallet") . "</option>";
    } elseif(PAYMENT_TYPE == 1) {
        $rides_data["payment_options"] = "<option value='2'>" . __("Wallet") . "</option>";
    } else {
        $rides_data["payment_options"] = "<option value='1'>" . __("Cash") . "</option>";
    }
    $rides_data["nighttime"] = ["start_hour" => NIGHT_START, "end_hour" => NIGHT_END];
    $data_array = ["success" => 1, "result" => $rides_data];
    return $data_array;
}
function getcallcenternum()
{
    $data_array = ["success" => 1, "cc_num" => CALL_CENTER_NUMBER];
    echo json_encode($data_array);
    exit;
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
    $transaction_data_sort["debit-data"] = [];
    $transaction_data_sort["funding-data"] = [];
    $query = sprintf("SELECT *,%1\$stbl_wallet_transactions.cur_exchng_rate AS exchng_rate,%1\$stbl_bookings.cur_symbol AS b_cur_symbol,%1\$stbl_wallet_transactions.cur_symbol AS t_cur_symbol,%1\$stbl_wallet_transactions.transaction_id AS transaction_id,DATE(%1\$stbl_wallet_transactions.transaction_date) AS transaction_date,%1\$stbl_wallet_transactions.transaction_date AS transaction_dates FROM %1\$stbl_wallet_transactions \r\n    LEFT JOIN %1\$stbl_bookings ON %1\$stbl_bookings.id = %1\$stbl_wallet_transactions.book_id\r\n    LEFT JOIN %1\$stbl_users ON %1\$stbl_users.user_id = %1\$stbl_wallet_transactions.user_id\r\n    LEFT JOIN %1\$stbl_routes ON %1\$stbl_routes.id = %1\$stbl_users.route_id\r\n    LEFT JOIN %1\$stbl_currencies ON %1\$stbl_currencies.id = %1\$stbl_routes.city_currency_id\r\n    WHERE %1\$stbl_wallet_transactions.user_id = \"%2\$d\" AND %1\$stbl_wallet_transactions.user_type = 0 ORDER BY %1\$stbl_wallet_transactions.transaction_date DESC LIMIT 0,300 ", DB_TBL_PREFIX, $_SESSION["uid"]);
    if(($result = mysqli_query($GLOBALS["DB"], $query)) && mysqli_num_rows($result)) {
        while ($row = mysqli_fetch_assoc($result)) {
            if($row["type"] == 2 || $row["type"] == 3) {
                $transaction_data_sort["debit-data"][$row["transaction_date"]]["date"] = $row["transaction_date"];
                $transaction_data_sort["debit-data"][$row["transaction_date"]]["data"][] = $row;
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
                $indicate_credit_debit = "<ons-icon icon='fa-circle' size='14px' style='color: green; font-size: 14px;'></ons-icon>";
                $template .= "<ons-list-item modifier='longdivider'>\r\n                            \r\n                                        <div class='center'>\r\n                                            <div style='width:100%;margin-bottom:15px;'>" . $indicate_credit_debit . " <span class='list-item__title'>" . $transaction_time . "</span> </div>\r\n                                            <span class='list-item__subtitle' style='margin-bottom:5px;'><span style='color:#000;font-size:20px;'>" . __("Amount") . ": " . $transaction_d["t_cur_symbol"] . $transaction_d["amount"] . "</span></span>\r\n                                            <span class='list-item__subtitle'><span style='color:#777'><ons-icon icon='fa-square' size='8px' style='vertical-align: bottom;color: #1867c2; font-size: 10px;'></ons-icon> " . __("Description") . ": " . $transaction_d["desc"] . "</span></span>\r\n                                            <span class='list-item__subtitle'><span style='color:#777'><ons-icon icon='fa-square' size='8px' style='vertical-align: bottom;color: #1867c2; font-size: 10px;'></ons-icon> " . __("Transaction ID") . ":" . $transaction_id_upper . " </span></span>                                            \r\n                                            <span class='list-item__subtitle'><span style='color:#777'><ons-icon icon='fa-square' size='8px' style='vertical-align: bottom;color: #1867c2; font-size: 10px;'></ons-icon> " . __("Wallet Balance") . ":</span> " . $transaction_d["t_cur_symbol"] . $wallet_balance_converted . "</span>\r\n                                        </div>\r\n                                    \r\n                                    </ons-list-item>";
            }
        }
    }
    foreach ($transaction_data_sort["debit-data"] as $transactiondatasort) {
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
    $query = sprintf("SELECT wallet_amount,reward_points FROM %stbl_users WHERE user_id = \"%d\"", DB_TBL_PREFIX, $_SESSION["uid"]);
    if($result = mysqli_query($GLOBALS["DB"], $query)) {
        if(mysqli_num_rows($result)) {
            $user_wallet_details = mysqli_fetch_assoc($result);
            $_SESSION["wallet_amt"] = $user_wallet_details["wallet_amount"];
            $_SESSION["reward_points"] = $user_wallet_details["reward_points"];
            $data_array = ["success" => 1, "reward_points" => $_SESSION["reward_points"], "wallet_amt" => $_SESSION["wallet_amt"], "wallet_history" => $template, "wallet_debit" => $template2];
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
function redeempoints()
{
    $user_data = [];
    $query = sprintf("SELECT * FROM %1\$stbl_users WHERE user_id = %2\$d", DB_TBL_PREFIX, $_SESSION["uid"]);
    if($result = mysqli_query($GLOBALS["DB"], $query)) {
        if(mysqli_num_rows($result)) {
            $user_data = mysqli_fetch_assoc($result);
            $reward_points_data = [];
            $query = sprintf("SELECT * FROM %1\$stbl_reward_points WHERE id = %2\$d", DB_TBL_PREFIX, 1);
            if($result = mysqli_query($GLOBALS["DB"], $query)) {
                if(mysqli_num_rows($result)) {
                    $reward_points_data = mysqli_fetch_assoc($result);
                    if($user_data["reward_points"] < $reward_points_data["min_points_redeemable"]) {
                        $error = ["error" => __("You need to have up to {---1} reward points to redeem. Increase your reward points by taking more trips", [$reward_points_data["min_points_redeemable"]])];
                        echo json_encode($error);
                        exit;
                    }
                    $point_val = $user_data["reward_points"] * $reward_points_data["points_to_cur_conv"];
                    $query = sprintf("UPDATE %1\$stbl_users SET wallet_amount = wallet_amount + %2\$f, reward_points = 0, reward_points_redeemed = reward_points_redeemed + %2\$f WHERE user_id = %3\$d", DB_TBL_PREFIX, $point_val, $_SESSION["uid"]);
                    if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
                        $error = ["error" => __("An error has occured")];
                        echo json_encode($error);
                        exit;
                    }
                    $default_currency_data = [];
                    $query = sprintf("SELECT * FROM %stbl_currencies WHERE `default` = 1", DB_TBL_PREFIX);
                    if(($result = mysqli_query($GLOBALS["DB"], $query)) && mysqli_num_rows($result)) {
                        $default_currency_data = mysqli_fetch_assoc($result);
                    }
                    $transaction_id = crypto_string();
                    $query = sprintf("INSERT INTO %stbl_wallet_transactions (cur_symbol,cur_exchng_rate,cur_code,transaction_id,amount,wallet_balance,user_id,user_type,`desc`,`type`,transaction_date) VALUES(\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%d\",\"%d\",\"%s\",\"%d\",\"%s\")", DB_TBL_PREFIX, $default_currency_data["symbol"], $default_currency_data["exchng_rate"], $default_currency_data["iso_code"], $transaction_id, $point_val, $user_data["wallet_amount"], $_SESSION["uid"], 0, __("Redeemed reward points funds"), 0, gmdate("Y-m-d H:i:s", time()));
                    $result = mysqli_query($GLOBALS["DB"], $query);
                    $resp = ["success" => 1];
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
        $error = ["error" => __("An error has occured")];
        echo json_encode($error);
        exit;
    }
    $error = ["error" => __("An error has occured")];
    echo json_encode($error);
    exit;
}
function newbooking()
{
    if(empty($_SESSION["loggedin"])) {
        $error = ["error" => __("Please login to continue")];
        echo json_encode($error);
        exit;
    }
    $tariff_data = [];
    $num_of_pending_booking = 0;
    $booking_data = [];
    $paddress = mysqli_real_escape_string($GLOBALS["DB"], $_GET["paddress"]);
    $daddress = mysqli_real_escape_string($GLOBALS["DB"], $_GET["daddress"]);
    $plng = mysqli_real_escape_string($GLOBALS["DB"], $_GET["plng"]);
    $plat = mysqli_real_escape_string($GLOBALS["DB"], $_GET["plat"]);
    $dlng = mysqli_real_escape_string($GLOBALS["DB"], $_GET["dlng"]);
    $dlat = mysqli_real_escape_string($GLOBALS["DB"], $_GET["dlat"]);
    $payment_type = (int) $_GET["p_type"];
    $pdatetime = mysqli_real_escape_string($GLOBALS["DB"], $_GET["pdatetime"]);
    $ride_id = (int) $_GET["ride_id"];
    $route_id = (int) $_GET["route_id"];
    $scheduled_booking = (int) $_GET["scheduled"];
    $price = $_GET["booking_price"];
    $price_hash = mysqli_real_escape_string($GLOBALS["DB"], $_GET["b_token"]);
    $coupon_code = mysqli_real_escape_string($GLOBALS["DB"], $_GET["coupon_code"]);
    $multidestination = (int) $_GET["multidestination"];
    $waypoints_data = $_GET["waypoints"];
    if(md5("projectgics" . $price) !== $price_hash) {
        $error = ["error" => "Error booking your ride. Price mismatch. - " . $price_hash];
        echo json_encode($error);
        exit;
    }
    $price = (double) $_GET["booking_price"];
    if($scheduled_booking && strtotime($pdatetime) < time() + 3600) {
        $error = ["error" => __("Please set a time atleast 1 hour ahead for scheduled ride")];
        echo json_encode($error);
        exit;
    }
    if($scheduled_booking) {
        $pdatetime = gmdate("Y-m-d H:i:s", strtotime($pdatetime));
    } else {
        $pdatetime = gmdate("Y-m-d H:i:s", time());
    }
    $query = sprintf("SELECT COUNT(*) FROM %1\$stbl_bookings WHERE %1\$stbl_bookings.user_id = %2\$d AND (%1\$stbl_bookings.status = 0 OR %1\$stbl_bookings.status = 1)", DB_TBL_PREFIX, $_SESSION["uid"]);
    if($result = mysqli_query($GLOBALS["DB"], $query)) {
        if(mysqli_num_rows($result)) {
            $row = mysqli_fetch_assoc($result);
            $num_of_pending_booking = $row["COUNT(*)"];
            if(USER_MAX_NUM_PEND_BOOKINGS <= $num_of_pending_booking) {
                $error = ["error" => __("You cannot have more than {---1} uncompleted bookings. Please cancel some bookings", [USER_MAX_NUM_PEND_BOOKINGS])];
                echo json_encode($error);
                exit;
            }
        }
        mysqli_free_result($result);
    }
    $query = sprintf("SELECT * FROM %1\$stbl_bookings WHERE %1\$stbl_bookings.user_id = %2\$d AND (%1\$stbl_bookings.status = 0 OR %1\$stbl_bookings.status = 1) ORDER BY %1\$stbl_bookings.id DESC LIMIT 1", DB_TBL_PREFIX, $_SESSION["uid"]);
    if($result = mysqli_query($GLOBALS["DB"], $query)) {
        if(mysqli_num_rows($result)) {
            $booking_data = mysqli_fetch_assoc($result);
        }
        mysqli_free_result($result);
    }
    if(!empty($booking_data)) {
        $last_booking_pickup_datetime_seconds = strtotime($booking_data["pickup_datetime"] . " UTC");
        $new_booking_pickup_datetime_seconds = strtotime($pdatetime . " UTC");
        $time_passed = $new_booking_pickup_datetime_seconds - $last_booking_pickup_datetime_seconds;
        if(($booking_data["status"] == 0 || $booking_data["status"] == 1) && $time_passed < MIN_BOOKING_INTERVAL) {
            $error = ["error" => __("You currently have an on-going or pending ride within the set pickup time")];
            echo json_encode($error);
            exit;
        }
    }
    $query = sprintf("SELECT * FROM %1\$stbl_rides_tariffs \r\n    INNER JOIN %1\$stbl_routes ON %1\$stbl_routes.id = %1\$stbl_rides_tariffs.routes_id\r\n    LEFT JOIN %1\$stbl_currencies ON %1\$stbl_currencies.id = %1\$stbl_routes.city_currency_id\r\n    WHERE %1\$stbl_rides_tariffs.routes_id = \"%2\$d\" AND %1\$stbl_rides_tariffs.ride_id = \"%3\$d\"", DB_TBL_PREFIX, $route_id, $ride_id);
    if($result = mysqli_query($GLOBALS["DB"], $query)) {
        if(mysqli_num_rows($result)) {
            $tariff_data = mysqli_fetch_assoc($result);
            $currency_symbol = isset($tariff_data["symbol"]) ? $tariff_data["symbol"] : "₦";
            $currency_exchange_rate = isset($tariff_data["exchng_rate"]) ? $tariff_data["exchng_rate"] : 1;
            $currency_code = isset($tariff_data["iso_code"]) ? $tariff_data["iso_code"] : "NGN";
            if($tariff_data["r_scope"] == 0 && empty($scheduled_booking)) {
                $location_info_age = gmdate("Y-m-d H:i:s", time() - LOCATION_INFO_VALID_AGE);
                $driver_available = 0;
                $query = sprintf("SELECT %1\$stbl_drivers.driver_id,%1\$stbl_drivers.push_notification_token,%1\$stbl_driver_location.*,%1\$stbl_drivers.route_id, %1\$stbl_drivers.ride_id FROM %1\$stbl_driver_location \r\n        INNER JOIN %1\$stbl_drivers ON %1\$stbl_driver_location.driver_id = %1\$stbl_drivers.driver_id\r\n        WHERE %1\$stbl_drivers.route_id = %3\$d AND %1\$stbl_drivers.ride_id = %4\$d AND %1\$stbl_drivers.is_activated = 1 AND %1\$stbl_drivers.available = 1 AND %1\$stbl_driver_location.location_date > \"%2\$s\"", DB_TBL_PREFIX, $location_info_age, $route_id, $ride_id);
                if(($result = mysqli_query($GLOBALS["DB"], $query)) && mysqli_num_rows($result)) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $distance = distance($plat, $plng, $row["lat"], $row["long"]);
                        if($distance <= MAX_DRIVER_DISTANCE) {
                            $driver_available = 1;
                            break;
                        }
                    }
                    mysqli_free_result($result);
                }
                if(!$driver_available) {
                }
            }
            $user_account_details = [];
            $query = sprintf("SELECT referral_discounts_count,wallet_amount FROM %stbl_users WHERE user_id = %d", DB_TBL_PREFIX, $_SESSION["uid"]);
            if($result = mysqli_query($GLOBALS["DB"], $query)) {
                if(mysqli_num_rows($result)) {
                    $user_account_details = mysqli_fetch_assoc($result);
                    if($payment_type == 2) {
                        $balance = (double) $user_account_details["wallet_amount"] - $price;
                        if(empty($balance) || $balance < 0) {
                            $error = ["error" => __("You have Insufficient fund in your wallet for this ride")];
                            echo json_encode($error);
                            exit;
                        }
                    }
                    $user_referral_eligible = 0;
                    $user_referral_discount = 0;
                    if(0 < $user_account_details["referral_discounts_count"]) {
                        $query = sprintf("SELECT * FROM %stbl_referral WHERE id = %d AND `status` = %d", DB_TBL_PREFIX, 1, 1);
                        if(($result = mysqli_query($GLOBALS["DB"], $query)) && mysqli_num_rows($result)) {
                            $row = mysqli_fetch_assoc($result);
                            $ref_discount_value = $row["discount_value"];
                            $user_referral_eligible = 1;
                            $user_referral_discount = $ref_discount_value;
                        }
                    }
                    $coupon_discount_value = 0;
                    $coupon_discount_type = 0;
                    $coupon_code_invalid = 0;
                    if(!empty($coupon_code)) {
                        $query = sprintf("SELECT * FROM %stbl_coupon_codes WHERE coupon_code = \"%s\" AND `status` = %d", DB_TBL_PREFIX, $coupon_code, 1);
                        if($result = mysqli_query($GLOBALS["DB"], $query)) {
                            if(mysqli_num_rows($result)) {
                                $row = mysqli_fetch_assoc($result);
                                $coupon_start_date = strtotime($row["active_date"]);
                                $coupon_end_date = strtotime($row["expiry_date"]);
                                if($coupon_start_date < time() && time() < $coupon_end_date) {
                                    $query = sprintf("SELECT SUM(%1\$stbl_coupons_used.times_used) AS all_usage, SUM(IF(%1\$stbl_coupons_used.user_id = %2\$d,%1\$stbl_coupons_used.times_used,NULL)) AS user_usage FROM %1\$stbl_coupons_used WHERE coupon_id = %3\$d", DB_TBL_PREFIX, $_SESSION["uid"], $row["id"]);
                                    if($result = mysqli_query($GLOBALS["DB"], $query)) {
                                        if(mysqli_num_rows($result)) {
                                            $usage_data = mysqli_fetch_assoc($result);
                                            if($row["limit_count"] <= $usage_data["all_usage"] || $row["user_limit_count"] <= $usage_data["user_usage"]) {
                                                $coupon_code = "";
                                                $coupon_code_invalid = 1;
                                            } elseif(!empty($row["vehicles"])) {
                                                $vehicles = explode(",", $row["vehicles"]);
                                                $ride_found = 0;
                                                if(is_array($vehicles)) {
                                                    foreach ($vehicles as $vehicle) {
                                                        if($ride_id == $vehicle) {
                                                            $ride_found = 1;
                                                            $coupon_discount_value = $row["discount_value"];
                                                            $coupon_discount_type = $row["discount_type"];
                                                            if(!$ride_found) {
                                                                $coupon_code = "";
                                                                $coupon_code_invalid = 1;
                                                            }
                                                        }
                                                    }
                                                }
                                            } else {
                                                $coupon_discount_value = $row["discount_value"];
                                                $coupon_discount_type = $row["discount_type"];
                                            }
                                        } else {
                                            $coupon_code = "";
                                            $coupon_code_invalid = 1;
                                        }
                                    } else {
                                        $coupon_code = "";
                                        $coupon_code_invalid = 1;
                                    }
                                } else {
                                    $coupon_code = "";
                                    $coupon_code_invalid = 1;
                                }
                            } else {
                                $coupon_code = "";
                                $coupon_code_invalid = 1;
                            }
                        } else {
                            $coupon_code = "";
                            $coupon_code_invalid = 1;
                        }
                    }
                    $waypoint1_address = "";
                    $waypoint1_lat = "";
                    $waypoint1_lng = "";
                    $waypoint2_address = "";
                    $waypoint2_lat = "";
                    $waypoint2_lng = "";
                    if($multidestination) {
                        if(isset($waypoints_data["dest-1"]) && $waypoints_data["dest-1"]["address"] != "") {
                            $waypoint1_address = $waypoints_data["dest-1"]["address"];
                            $waypoint1_lat = $waypoints_data["dest-1"]["lat"];
                            $waypoint1_lng = $waypoints_data["dest-1"]["lng"];
                        }
                        if(isset($waypoints_data["dest-2"]) && $waypoints_data["dest-2"]["address"] != "") {
                            $waypoint2_address = $waypoints_data["dest-2"]["address"];
                            $waypoint2_lat = $waypoints_data["dest-2"]["lat"];
                            $waypoint2_lng = $waypoints_data["dest-2"]["lng"];
                        }
                    }
                    $completion_code = crypto_string("123456789", 4);
                    $query = sprintf("INSERT INTO %stbl_bookings (waypoint1_address,waypoint1_long,waypoint1_lat,waypoint2_address,waypoint2_long,waypoint2_lat,referral_used,referral_discount_value,coupon_code,coupon_discount_type,coupon_discount_value,cur_symbol,cur_exchng_rate,cur_code,completion_code,scheduled,user_firstname,user_lastname,user_phone,user_id,pickup_datetime, pickup_address, pickup_long, pickup_lat, dropoff_address, dropoff_long,dropoff_lat,estimated_cost,route_id,ride_id,payment_type,date_created) VALUES(\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%d\",\"%s\",\"%s\",\"%s\",\"%d\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%d\",\"%d\",\"%d\",\"%s\")", DB_TBL_PREFIX, $waypoint1_address, $waypoint1_lng, $waypoint1_lat, $waypoint2_address, $waypoint2_lng, $waypoint2_lat, $user_referral_eligible, $user_referral_discount, $coupon_code, $coupon_discount_type, $coupon_discount_value, $currency_symbol, $currency_exchange_rate, $currency_code, $completion_code, $scheduled_booking, $_SESSION["firstname"], $_SESSION["lastname"], $_SESSION["country_dial_code"] . $_SESSION["phone"], $_SESSION["uid"], $pdatetime, $paddress, $plng, $plat, $daddress, $dlng, $dlat, $price, $route_id, $ride_id, $payment_type, gmdate("Y-m-d H:i:s", time()));
                    if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
                        $error = ["error" => __("An error has occured")];
                        echo json_encode($error);
                        exit;
                    }
                    $data_array = ["success" => 1, "coupon_code_invalid" => $coupon_code_invalid];
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
        $error = ["error" => __("An error has occured")];
        echo json_encode($error);
        exit;
    }
    $error = ["error" => __("An error has occured")];
    echo json_encode($error);
    exit;
}
function getavailablecitydrivers()
{
    if(empty($_SESSION["loggedin"])) {
        $error = ["error" => __("Please login to continue")];
        echo json_encode($error);
        exit;
    }
    $city = (int) $_GET["city"];
    $priority_driver = (int) $_GET["priority_driver"];
    $drivers_location_data = [];
    $location_info_age = gmdate("Y-m-d H:i:s", time() - LOCATION_INFO_VALID_AGE);
    $query = sprintf("SELECT %1\$stbl_driver_location.*,%1\$stbl_drivers.firstname, %1\$stbl_drivers.ride_id,%1\$stbl_rides.ride_type FROM %1\$stbl_driver_location \r\n    INNER JOIN %1\$stbl_drivers ON %1\$stbl_driver_location.driver_id = %1\$stbl_drivers.driver_id\r\n    INNER JOIN %1\$stbl_rides ON %1\$stbl_rides.id = %1\$stbl_drivers.ride_id\r\n    WHERE %1\$stbl_drivers.route_id = %2\$d AND %1\$stbl_drivers.is_activated = 1 AND %1\$stbl_drivers.available = 1 AND %1\$stbl_driver_location.location_date > \"%3\$s\" LIMIT 100", DB_TBL_PREFIX, $city, $location_info_age);
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
            $data_array = ["success" => 1, "drivers_locations" => $drivers_location_data];
            echo json_encode($data_array);
            exit;
        }
        $data_array = ["success" => 1, "drivers_locations" => $drivers_location_data];
        echo json_encode($data_array);
        exit;
    }
    $error = ["error" => __("An error has occured")];
    echo json_encode($error);
    exit;
}
function resumebooking()
{
    if(empty($_SESSION["loggedin"])) {
        $error = ["error" => __("Please login to continue")];
        echo json_encode($error);
        exit;
    }
    $booking_id = (int) $_GET["booking_id"];
    $ongoing_booking = [];
    $query = sprintf("SELECT *,%1\$stbl_bookings.id AS booking_id FROM %1\$stbl_bookings \r\n    INNER JOIN %1\$stbl_drivers ON %1\$stbl_drivers.driver_id = %1\$stbl_bookings.driver_id\r\n    INNER JOIN %1\$stbl_driver_location ON %1\$stbl_driver_location.driver_id = %1\$stbl_bookings.driver_id\r\n    WHERE %1\$stbl_bookings.id = %3\$d AND %1\$stbl_bookings.user_id = %2\$d AND %1\$stbl_bookings.driver_id != 0 AND (%1\$stbl_bookings.status = 0 OR %1\$stbl_bookings.status = 1)", DB_TBL_PREFIX, $_SESSION["uid"], $booking_id);
    if($result = mysqli_query($GLOBALS["DB"], $query)) {
        if(mysqli_num_rows($result)) {
            $row = mysqli_fetch_assoc($result);
            $action = "";
            if(!empty($row["date_arrived"]) && $row["status"] == 0) {
                $action = "driver-arrived";
            } elseif(empty($row["date_arrived"]) && $row["status"] == 0) {
                $action = "driver-assigned";
            } else {
                $action = "customer-onride";
            }
            $driver_photo_file = isset($row["photo_file"]) ? $row["photo_file"] : "0";
            $ongoing_booking = ["action" => $action, "route_id" => $row["route_id"], "booking_id" => $row["booking_id"], "driver_id" => $row["driver_id"], "driver_firstname" => $row["firstname"], "driver_phone" => $row["phone"], "driver_platenum" => $row["car_plate_num"], "driver_rating" => $row["driver_rating"], "driver_location_lat" => $row["lat"], "driver_location_long" => $row["long"], "pickup_lat" => $row["pickup_lat"], "pickup_long" => $row["pickup_long"], "dropoff_lat" => $row["dropoff_lat"], "dropoff_long" => $row["dropoff_long"], "driver_carmodel" => $row["car_model"], "driver_carid" => $row["ride_id"], "driver_completed_rides" => $row["completed_rides"], "completion_code" => $row["completion_code"], "driver_photo" => SITE_URL . "ajaxphotofile.php?file=" . $driver_photo_file];
            $error = ["success" => 1, "ongoing_bk" => $ongoing_booking];
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
function getbookings()
{
    if(empty($_SESSION["loggedin"])) {
        $error = ["error" => __("Please login to continue")];
        echo json_encode($error);
        exit;
    }
    $booking_data = [];
    $booking_pend_onride = "";
    $booking_completed = "";
    $booking_cancelled = "";
    $booking_data_sort = [];
    $retry_button_shown = 0;
    $cancelled_bookings_count = 0;
    $query = sprintf("SELECT *, %1\$stbl_drivers.photo_file AS drvr_photo,DATE(%1\$stbl_bookings.date_created) AS created_date,%1\$stbl_bookings.id AS booking_id FROM %1\$stbl_bookings \r\n    LEFT JOIN %1\$stbl_drivers ON %1\$stbl_drivers.driver_id = %1\$stbl_bookings.driver_id\r\n    LEFT JOIN %1\$stbl_rides ON %1\$stbl_rides.id = %1\$stbl_bookings.ride_id\r\n    LEFT JOIN %1\$stbl_users ON %1\$stbl_users.user_id = %1\$stbl_bookings.user_id\r\n    WHERE %1\$stbl_bookings.user_id = %2\$s ORDER BY %1\$stbl_bookings.date_created DESC LIMIT 0,500 ", DB_TBL_PREFIX, $_SESSION["uid"]);
    if($result = mysqli_query($GLOBALS["DB"], $query)) {
        if(mysqli_num_rows($result)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $booking_data[] = $row;
                if($row["status"] == 0 || $row["status"] == 1) {
                    $booking_data_sort[$row["created_date"]]["date"] = $row["created_date"];
                    $booking_data_sort[$row["created_date"]]["pend_onride"][] = $row;
                } elseif($row["status"] == 3) {
                    $booking_data_sort[$row["created_date"]]["date"] = $row["created_date"];
                    $booking_data_sort[$row["created_date"]]["completed"][] = $row;
                } elseif($row["status"] == 2 || $row["status"] == 4 || $row["status"] == 5) {
                    $booking_data_sort[$row["created_date"]]["date"] = $row["created_date"];
                    $booking_data_sort[$row["created_date"]]["cancelled"][] = $row;
                }
            }
            mysqli_free_result($result);
            foreach ($booking_data_sort as $bookingdatasort) {
                if(!empty($bookingdatasort["pend_onride"])) {
                    $b_date_format = date("l, M j, Y", strtotime($bookingdatasort["date"] . " UTC"));
                    $booking_pend_onride .= "<ons-list-header style='border-top: thin solid grey;border-bottom: thin solid grey;font-size: 14px;font-weight:bold;'>" . $b_date_format . "</ons-list-header>";
                    foreach ($bookingdatasort["pend_onride"] as $bookingdatasort_po) {
                        $booking_time = date("g:i A", strtotime($bookingdatasort_po["date_created"] . " UTC"));
                        $booking_ptime = date("g:i A", strtotime($bookingdatasort_po["date_created"] . " UTC"));
                        $booking_driver = isset($bookingdatasort_po["driver_id"]) ? $bookingdatasort_po["driver_firstname"] . " " . $bookingdatasort_po["driver_lastname"] : "N/A";
                        $booking_driver_assigned = isset($bookingdatasort_po["driver_id"]) ? 1 : 0;
                        $resume_booking_btn = $booking_driver_assigned == 1 ? "<span class='list-item__subtitle' id='resume-bk-" . $bookingdatasort_po["booking_id"] . "' ><ons-button style='width:100%' onclick='event.stopPropagation();resumeBooking(" . $bookingdatasort_po["booking_id"] . ")'> <i class='fa fa-angle-double-right'></i> </ons-button></span>" : "";
                        $booking_completion_code = !empty($bookingdatasort_po["completion_code"]) ? $bookingdatasort_po["completion_code"] : "N/A";
                        $status = "";
                        $close_btn = "";
                        if($bookingdatasort_po["status"] == 0 && $booking_driver_assigned != 0) {
                            $status = "<span style='color: #ef6c00;font-weight: bold;border: thin solid #ef6c00;padding: 3px 5px;font-size: 12px;'>" . __("Driver is on his way") . "</span>";
                            $close_btn = "<span style='display:inline-block;float:right'><ons-icon onclick = 'event.stopPropagation();bookingcancel(" . $bookingdatasort_po["booking_id"] . "," . $booking_driver_assigned . ")' icon='fa-times-circle' size='18px' style='color:red'></ons-icon></span>";
                        } elseif($bookingdatasort_po["status"] == 0) {
                            $status = "<span style='color: #e541e5;font-weight: bold;border: thin solid #e541e5;padding: 3px 5px;font-size: 12px;'>" . __("Pending trip") . "</span>";
                            $close_btn = "<span style='display:inline-block;float:right'><ons-icon onclick = 'event.stopPropagation();bookingcancel(" . $bookingdatasort_po["booking_id"] . "," . $booking_driver_assigned . ")' icon='fa-times-circle' size='18px' style='color:red'></ons-icon></span>";
                        } else {
                            $status = "<span style='color: #43a047;font-weight: bold;border: thin solid #43a047;padding: 3px 5px;font-size: 12px;'>" . __("Current trip") . "</span>";
                            $close_btn = "";
                        }
                        $booking_pdate_time = date("l, M j, Y g:i A", strtotime($bookingdatasort_po["pickup_datetime"] . " UTC"));
                        $booking_type = $bookingdatasort_po["scheduled"] == 1 ? "Schedule ride" : "Instant ride";
                        $drvr_photo_file = isset($bookingdatasort_po["drvr_photo"]) ? SITE_URL . "ajaxphotofile.php?file=" . $bookingdatasort_po["drvr_photo"] : "";
                        $booking_payment_type = "";
                        if(!empty($bookingdatasort_po["payment_type"])) {
                            if($bookingdatasort_po["payment_type"] == 1) {
                                $booking_payment_type = __("Cash");
                            } elseif($bookingdatasort_po["payment_type"] == 2) {
                                $booking_payment_type = __("Wallet");
                            } else {
                                $booking_payment_type = "Card";
                            }
                        }
                        $ride_filename = explode("/", $bookingdatasort_po["ride_img"]);
                        $ride_image = SITE_URL . "img/ride_imgs/" . array_pop($ride_filename);
                        $booking_title = str_pad($bookingdatasort_po["booking_id"], 5, "0", STR_PAD_LEFT);
                        $item_data = [];
                        $item_data = ["car_desc" => $bookingdatasort_po["ride_desc"], "driver_phone" => $bookingdatasort_po["driver_phone"], "driver_car_details" => $bookingdatasort_po["car_model"] . " [" . $bookingdatasort_po["car_color"] . "]", "driver_plate_num" => $bookingdatasort_po["car_plate_num"], "driver_rating" => $bookingdatasort_po["driver_rating"], "payment_type" => $booking_payment_type, "pick_up_time" => $booking_pdate_time, "driver_image" => $drvr_photo_file, "car_image" => $ride_image, "driver_name" => $booking_driver, "booking_cost" => $bookingdatasort_po["cur_symbol"] . $bookingdatasort_po["estimated_cost"], "car_type" => $bookingdatasort_po["ride_type"], "p_location" => $bookingdatasort_po["pickup_address"], "d_location" => $bookingdatasort_po["dropoff_address"], "booking_id" => $booking_title, "booking_type" => $booking_type, "booking_status" => $bookingdatasort_po["status"], "coupon_code" => $bookingdatasort_po["coupon_code"]];
                        $item_data_json = json_encode($item_data);
                        $booking_pend_onride .= "<ons-list-item onclick='showbookingdetails(" . $bookingdatasort_po["booking_id"] . ")' id='booking-list-item-" . $bookingdatasort_po["booking_id"] . "' modifier='longdivider'>\r\n                \r\n                                            <div class='center'>\r\n                                                <div style='width:100%;'><span class='list-item__title'>" . $booking_time . " </span> " . $status . " " . $close_btn . "</div>\r\n                                                <span style='text-align: left;margin-bottom: 15px;' class='list-item__subtitle'><span>ID:#" . $booking_title . " | OTP code: " . $booking_completion_code . "</span></span>                               \r\n                                                <span class='list-item__subtitle' style='margin-bottom:5px;'><ons-icon icon='fa-circle' size='14px' style='color: #24c539; font-size: 14px;position: absolute;'></ons-icon> <span style='display:inline-block;margin-left:22px;font-weight:bold;'>" . $bookingdatasort_po["pickup_address"] . "</span></span>\r\n                                                <span class='list-item__subtitle'><ons-icon icon='fa-map-marker' size='14px' style='color: red; font-size: 14px;position: absolute;'></ons-icon> <span style='display:inline-block;margin-left:22px;font-weight:bold;'>" . $bookingdatasort_po["dropoff_address"] . "</span></span>\r\n                                                <span id='booking-list-item-data-" . $bookingdatasort_po["booking_id"] . "' type='text' style='display:none'>" . $item_data_json . "</span>\r\n                                                " . $resume_booking_btn . "\r\n                                            </div>\r\n                                            \r\n                                        \r\n                                        </ons-list-item>";
                    }
                }
                if(!empty($bookingdatasort["completed"])) {
                    $b_date_format = date("l, M j, Y", strtotime($bookingdatasort["date"] . " UTC"));
                    $booking_completed .= "<ons-list-header style='border-top: thin solid grey;border-bottom: thin solid grey;font-size: 14px;font-weight:bold;'>" . $b_date_format . "</ons-list-header>";
                    foreach ($bookingdatasort["completed"] as $bookingdatasort_comp) {
                        $booking_time = date("g:i A", strtotime($bookingdatasort_comp["date_created"] . " UTC"));
                        $booking_ptime = date("g:i A", strtotime($bookingdatasort_comp["date_created"] . " UTC"));
                        $booking_dtime = isset($bookingdatasort_comp["dropoff_datetime"]) ? date("g:i A", strtotime($bookingdatasort_comp["dropoff_datetime"] . " UTC")) : "N/A";
                        $booking_paid_amt = isset($bookingdatasort_comp["paid_amount"]) ? $bookingdatasort_comp["paid_amount"] : "N/A";
                        $booking_driver = isset($bookingdatasort_comp["driver_id"]) ? $bookingdatasort_comp["driver_firstname"] . " " . $bookingdatasort_comp["driver_lastname"] : "N/A";
                        $booking_completion_code = !empty($bookingdatasort_comp["completion_code"]) ? $bookingdatasort_comp["completion_code"] : "N/A";
                        $booking_pdate_time = date("l, M j, Y g:i A", strtotime($bookingdatasort_comp["pickup_datetime"] . " UTC"));
                        $booking_type = $bookingdatasort_comp["scheduled"] == 1 ? "Schedule ride" : "Instant ride";
                        $drvr_photo_file = isset($bookingdatasort_comp["drvr_photo"]) ? SITE_URL . "ajaxphotofile.php?file=" . $bookingdatasort_comp["drvr_photo"] : "0";
                        $booking_payment_type = "";
                        if(!empty($bookingdatasort_comp["payment_type"])) {
                            if($bookingdatasort_comp["payment_type"] == 1) {
                                $booking_payment_type = __("Cash");
                            } elseif($bookingdatasort_comp["payment_type"] == 2) {
                                $booking_payment_type = __("Wallet");
                            } else {
                                $booking_payment_type = "Card";
                            }
                        }
                        $ride_filename = explode("/", $bookingdatasort_comp["ride_img"]);
                        $ride_image = SITE_URL . "img/ride_imgs/" . array_pop($ride_filename);
                        $booking_title = str_pad($bookingdatasort_comp["booking_id"], 5, "0", STR_PAD_LEFT);
                        $ride_duration = "0 Secs";
                        if(!empty($bookingdatasort_comp["date_started"]) && !empty($bookingdatasort_comp["date_completed"])) {
                            $ride_start_time = strtotime($bookingdatasort_comp["date_started"]);
                            $ride_end_time = strtotime($bookingdatasort_comp["date_completed"]);
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
                        $item_data = ["car_desc" => $bookingdatasort_comp["ride_desc"], "driver_phone" => $bookingdatasort_comp["driver_phone"], "driver_car_details" => $bookingdatasort_comp["car_model"] . " [" . $bookingdatasort_comp["car_color"] . "]", "driver_plate_num" => $bookingdatasort_comp["car_plate_num"], "driver_rating" => $bookingdatasort_comp["driver_rating"], "payment_type" => $booking_payment_type, "pick_up_time" => $booking_pdate_time, "driver_image" => $drvr_photo_file, "car_image" => $ride_image, "driver_name" => $booking_driver, "booking_cost" => $bookingdatasort_comp["cur_symbol"] . $bookingdatasort_comp["paid_amount"], "car_type" => $bookingdatasort_comp["ride_type"], "p_location" => $bookingdatasort_comp["pickup_address"], "d_location" => $bookingdatasort_comp["dropoff_address"], "booking_id" => $booking_title, "booking_type" => $booking_type, "booking_status" => $bookingdatasort_comp["status"], "coupon_code" => $bookingdatasort_comp["coupon_code"], "distance_travelled" => !empty($bookingdatasort_comp["distance_travelled"]) ? round($bookingdatasort_comp["distance_travelled"] * 0, 2) . " KM" : "0 Km", "paid_amount" => $bookingdatasort_comp["cur_symbol"] . $bookingdatasort_comp["paid_amount"], "ride_duration" => $ride_duration];
                        $item_data_json = json_encode($item_data);
                        $booking_completed .= "<ons-list-item onclick='showbookingdetails(" . $bookingdatasort_comp["booking_id"] . ")' data-ridedesc='" . $bookingdatasort_comp["ride_desc"] . "'  data-driverphone='" . $bookingdatasort_comp["driver_phone"] . "' data-ptype='" . $booking_payment_type . "' data-put='" . $booking_pdate_time . "' data-driverimg='" . $drvr_photo_file . "' data-rideimg='" . $ride_image . "' data-drivername='" . $booking_driver . "' data-cost='" . $bookingdatasort_comp["estimated_cost"] . "' data-ride='" . $bookingdatasort_comp["ride_type"] . "' data-pul='" . $bookingdatasort_comp["pickup_address"] . "' data-dol='" . $bookingdatasort_comp["dropoff_address"] . "' data-btitle='" . $booking_title . "' id='booking-list-item-" . $bookingdatasort_comp["booking_id"] . "' modifier='longdivider'>\r\n                                            <div class='center'>\r\n                                                <div style='width:100%;'><span class='list-item__title'>" . $booking_time . " </span> <span style='color: #1976d2;font-weight: bold;border: thin solid #1976d2;padding: 3px 5px;font-size: 12px;'>" . __("Completed") . "</span></div>\r\n                                                <span style='text-align: left;margin-bottom: 15px;' class='list-item__subtitle'><span>ID:#" . $booking_title . " | OTP code: " . $booking_completion_code . "</span></span>                               \r\n                                                <span class='list-item__subtitle' style='margin-bottom:5px;'><ons-icon icon='fa-circle' size='14px' style='color: #24c539; font-size: 14px;position: absolute;'></ons-icon> <span style='display:inline-block;margin-left:22px;font-weight:bold;'>" . $bookingdatasort_comp["pickup_address"] . "</span></span>\r\n                                                <span class='list-item__subtitle'><ons-icon icon='fa-map-marker' size='14px' style='color: red; font-size: 14px;position: absolute;'></ons-icon> <span style='display:inline-block;margin-left:22px;font-weight:bold;'>" . $bookingdatasort_comp["dropoff_address"] . "</span></span>\r\n                                                <span id='booking-list-item-data-" . $bookingdatasort_comp["booking_id"] . "' type='text' style='display:none'>" . $item_data_json . "</span>\r\n                                            </div>\r\n                                        \r\n                                        </ons-list-item>";
                    }
                }
                if(!empty($bookingdatasort["cancelled"])) {
                    $b_date_format = date("l, M j, Y", strtotime($bookingdatasort["date"] . " UTC"));
                    $booking_cancelled .= "<ons-list-header style='border-top: thin solid grey;border-bottom: thin solid grey;font-size: 14px;font-weight:bold;'>" . $b_date_format . "</ons-list-header>";
                    foreach ($bookingdatasort["cancelled"] as $bookingdatasort_canc) {
                        $booking_time = date("g:i A", strtotime($bookingdatasort_canc["date_created"] . " UTC"));
                        $booking_ptime = date("g:i A", strtotime($bookingdatasort_canc["date_created"] . " UTC"));
                        $booking_driver = isset($bookingdatasort_canc["driver_id"]) ? $bookingdatasort_canc["driver_firstname"] . " " . $bookingdatasort_canc["driver_lastname"] : "N/A";
                        $booking_completion_code = !empty($bookingdatasort_canc["completion_code"]) ? $bookingdatasort_canc["completion_code"] : "N/A";
                        $retry_btn = "";
                        if(!$retry_button_shown && !$cancelled_bookings_count && time() - 300 < strtotime($bookingdatasort_canc["date_created"] . " UTC")) {
                            $retry_btn = "<span onclick = 'event.stopPropagation();bookingretry(" . $bookingdatasort_canc["booking_id"] . ")' style='display:inline-block;float:right;padding: 3px 5px;'><ons-icon icon='fa-refresh' size='18px' style='color:#3F9DD1'></ons-icon></span>";
                            $retry_button_shown = 1;
                        }
                        if($bookingdatasort_canc["status"] == 5) {
                            $status = "<span style='color: #999999;font-weight: bold;border: thin solid #999999;padding: 3px 5px;font-size: 12px;'>" . __("Expired") . "</span>";
                        } elseif($bookingdatasort_canc["status"] == 4) {
                            $status = "<span style='color: #e53935;font-weight: bold;border: thin solid #e53935;padding: 3px 5px;font-size: 12px;'>" . __("Cancelled by driver") . "</span>";
                            $retry_btn = "";
                        } elseif($bookingdatasort_canc["status"] == 2) {
                            $status = "<span style='color: #e53935;font-weight: bold;border: thin solid #e53935;padding: 3px 5px;font-size: 12px;'>" . __("Cancelled by you") . "</span>";
                            $retry_btn = "";
                        } else {
                            $status = "<span style='color: #e53935;font-weight: bold;border: thin solid #e53935;padding: 3px 5px;font-size: 12px;'>" . __("Cancelled") . "</span>";
                            $retry_btn = "";
                        }
                        $booking_pdate_time = date("l, M j, Y g:i A", strtotime($bookingdatasort_canc["pickup_datetime"] . " UTC"));
                        $booking_type = $bookingdatasort_canc["scheduled"] == 1 ? "Schedule ride" : "Instant ride";
                        $drvr_photo_file = isset($bookingdatasort_canc["drvr_photo"]) ? SITE_URL . "ajaxphotofile.php?file=" . $bookingdatasort_canc["drvr_photo"] : "0";
                        $booking_payment_type = "";
                        if(!empty($bookingdatasort_canc["payment_type"])) {
                            if($bookingdatasort_canc["payment_type"] == 1) {
                                $booking_payment_type = __("Cash");
                            } elseif($bookingdatasort_canc["payment_type"] == 2) {
                                $booking_payment_type = __("Wallet");
                            } else {
                                $booking_payment_type = "Card";
                            }
                        }
                        $ride_filename = explode("/", $bookingdatasort_canc["ride_img"]);
                        $ride_image = SITE_URL . "img/ride_imgs/" . array_pop($ride_filename);
                        $booking_title = str_pad($bookingdatasort_canc["booking_id"], 5, "0", STR_PAD_LEFT);
                        $item_data = [];
                        $item_data = ["car_desc" => $bookingdatasort_canc["ride_desc"], "driver_phone" => $bookingdatasort_canc["driver_phone"], "driver_car_details" => $bookingdatasort_canc["car_model"] . " [" . $bookingdatasort_canc["car_color"] . "]", "driver_plate_num" => $bookingdatasort_canc["car_plate_num"], "driver_rating" => $bookingdatasort_canc["driver_rating"], "payment_type" => $booking_payment_type, "pick_up_time" => $booking_pdate_time, "driver_image" => $drvr_photo_file, "car_image" => $ride_image, "driver_name" => $booking_driver, "booking_cost" => $bookingdatasort_canc["cur_symbol"] . $bookingdatasort_canc["estimated_cost"], "car_type" => $bookingdatasort_canc["ride_type"], "p_location" => $bookingdatasort_canc["pickup_address"], "d_location" => $bookingdatasort_canc["dropoff_address"], "booking_id" => $booking_title, "booking_type" => $booking_type, "booking_status" => $bookingdatasort_canc["status"], "coupon_code" => $bookingdatasort_canc["coupon_code"]];
                        $item_data_json = json_encode($item_data);
                        $booking_cancelled .= "<ons-list-item onclick='showbookingdetails(" . $bookingdatasort_canc["booking_id"] . ")' data-ridedesc='" . $bookingdatasort_canc["ride_desc"] . "'  data-driverphone='" . $bookingdatasort_canc["driver_phone"] . "' data-ptype='" . $booking_payment_type . "' data-put='" . $booking_pdate_time . "' data-driverimg='" . $drvr_photo_file . "' data-rideimg='" . $ride_image . "' data-drivername='" . $booking_driver . "' data-cost='" . $bookingdatasort_canc["estimated_cost"] . "' data-ride='" . $bookingdatasort_canc["ride_type"] . "' data-pul='" . $bookingdatasort_canc["pickup_address"] . "' data-dol='" . $bookingdatasort_canc["dropoff_address"] . "' data-btitle='" . $booking_title . "' id='booking-list-item-" . $bookingdatasort_canc["booking_id"] . "' modifier='longdivider'>\r\n                \r\n                                            <div class='center'>\r\n                                                <div style='width:100%;'><span class='list-item__title'>" . $booking_time . " </span> " . $status . " " . $retry_btn . "</div>\r\n                                                <span style='text-align: left;margin-bottom: 15px;' class='list-item__subtitle'><span>ID:#" . $booking_title . " | OTP code: " . $booking_completion_code . "</span></span>                               \r\n                                                <span class='list-item__subtitle' style='margin-bottom:5px;'><ons-icon icon='fa-circle' size='14px' style='color: #24c539; font-size: 14px;position: absolute;'></ons-icon> <span style='display:inline-block;margin-left:22px;font-weight:bold;'>" . $bookingdatasort_canc["pickup_address"] . "</span></span>\r\n                                                <span class='list-item__subtitle'><ons-icon icon='fa-map-marker' size='14px' style='color: red; font-size: 14px;position: absolute;'></ons-icon> <span style='display:inline-block;margin-left:22px;font-weight:bold;'>" . $bookingdatasort_canc["dropoff_address"] . "</span></span>\r\n                                                <span id='booking-list-item-data-" . $bookingdatasort_canc["booking_id"] . "' type='text' style='display:none'>" . $item_data_json . "</span>\r\n                                            </div>\r\n                                        \r\n                                        </ons-list-item>";
                        $cancelled_bookings_count++;
                    }
                }
            }
            $data_array = ["success" => 1, "pend_onride" => $booking_pend_onride, "booking_comp" => $booking_completed, "booking_canc" => $booking_cancelled];
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
function bookingretry()
{
    if(empty($_SESSION["loggedin"])) {
        $error = ["error" => __("Please login to continue")];
        echo json_encode($error);
        exit;
    }
    $booking_id = (int) $_POST["bookingid"];
    $booking_data = [];
    $query = sprintf("SELECT * FROM %1\$stbl_bookings WHERE %1\$stbl_bookings.id = \"%3\$d\" AND %1\$stbl_bookings.user_id = \"%2\$d\"", DB_TBL_PREFIX, $_SESSION["uid"], $booking_id);
    if($result = mysqli_query($GLOBALS["DB"], $query)) {
        if(mysqli_num_rows($result)) {
            $booking_data = mysqli_fetch_assoc($result);
            if(!empty($booking_data["driver_id"])) {
                $error = ["error" => __("Driver already assigned to booking")];
                echo json_encode($error);
                exit;
            }
            if($booking_data["status"] != 5) {
                $error = ["error" => __("Booking status is invalid")];
                echo json_encode($error);
                exit;
            }
            if(strtotime($booking_data["date_created"] . " UTC") < time() - 300) {
                $error = ["error" => __("Booking cannot be restarted. Please create a new booking")];
                echo json_encode($error);
                exit;
            }
            $query = sprintf("UPDATE %1\$stbl_bookings SET %1\$stbl_bookings.status = 0, %1\$stbl_bookings.pickup_datetime = \"%2\$s\" WHERE %1\$stbl_bookings.id = %3\$d", DB_TBL_PREFIX, gmdate("Y-m-d H:i:s", time()), $booking_id);
            if($result = mysqli_query($GLOBALS["DB"], $query)) {
                $data_array = ["success" => 1];
                echo json_encode($data_array);
                exit;
            }
            $error = ["error" => __("An error has occured")];
            echo json_encode($error);
            exit;
        }
        $error = ["error" => __("Booking information was not found")];
        echo json_encode($error);
        exit;
    }
    $error = ["error" => "An error has occured"];
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
    $booking_id = (int) $_POST["bookingid"];
    $booking_data = [];
    $query = sprintf("SELECT %1\$stbl_franchise.fwallet_amount,%1\$stbl_drivers.wallet_amount AS driver_wallet_amount,%1\$stbl_bookings.driver_commision,%1\$stbl_bookings.franchise_commision,%1\$stbl_users.wallet_amount AS user_wallet_amount,%1\$stbl_rides_tariffs.cancel_cost,%1\$stbl_rides_tariffs.ncancel_cost,%1\$stbl_bookings.cur_symbol,%1\$stbl_bookings.cur_exchng_rate,%1\$stbl_bookings.cur_code,%1\$stbl_bookings.status, %1\$stbl_drivers.*,%1\$stbl_drivers.disp_lang AS d_lang FROM %1\$stbl_bookings\r\n    INNER JOIN %1\$stbl_users ON %1\$stbl_users.user_id = %1\$stbl_bookings.user_id\r\n    INNER JOIN %1\$stbl_rides_tariffs ON %1\$stbl_rides_tariffs.routes_id = %1\$stbl_bookings.route_id AND %1\$stbl_rides_tariffs.ride_id = %1\$stbl_bookings.ride_id\r\n    LEFT JOIN %1\$stbl_franchise ON %1\$stbl_franchise.id = %1\$stbl_bookings.franchise_id\r\n    LEFT JOIN %1\$stbl_drivers ON %1\$stbl_drivers.driver_id = %1\$stbl_bookings.driver_id \r\n    WHERE %1\$stbl_bookings.id = \"%3\$d\" AND %1\$stbl_users.user_id = \"%2\$d\"", DB_TBL_PREFIX, $_SESSION["uid"], $booking_id);
    if($result = mysqli_query($GLOBALS["DB"], $query)) {
        if(mysqli_num_rows($result)) {
            $booking_data = mysqli_fetch_assoc($result);
            if($booking_data["status"] == 1 || $booking_data["status"] == 3) {
                $error = ["error" => __("Your trip has already started. You cannot cancel this trip. Ask your driver to cancel the trip")];
                echo json_encode($error);
                exit;
            }
            if($booking_data["status"] == 2 || $booking_data["status"] == 4 || $booking_data["status"] == 5) {
                $error = ["error" => __("Booking already cancelled")];
                echo json_encode($error);
                exit;
            }
            $query = sprintf("UPDATE %stbl_bookings SET `status` = 2 WHERE id = \"%d\"", DB_TBL_PREFIX, $booking_id);
            if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
                $error = ["error" => __("An error has occured")];
                echo json_encode($error);
                exit;
            }
            $dlang = $booking_data["d_lang"];
            $query = sprintf("UPDATE %stbl_driver_allocate SET `status` = %d WHERE booking_id = %d", DB_TBL_PREFIX, 4, $booking_id);
            $result = mysqli_query($GLOBALS["DB"], $query);
            $query = sprintf("UPDATE %stbl_users SET cancelled_rides = cancelled_rides + 1 WHERE `user_id` = %d", DB_TBL_PREFIX, $_SESSION["uid"]);
            $result = mysqli_query($GLOBALS["DB"], $query);
            if(!empty($booking_data["driver_id"])) {
                $current_hour = (int) date("H");
                if(NIGHT_START <= $current_hour || $current_hour <= NIGHT_END) {
                    if(0 < $booking_data["user_wallet_amount"] && 0 < $booking_data["ncancel_cost"]) {
                        $rider_wallet_balance = $booking_data["user_wallet_amount"] - $booking_data["ncancel_cost"];
                        if($rider_wallet_balance < 0) {
                            $booking_data["ncancel_cost"] = $booking_data["user_wallet_amount"];
                        }
                        $night_time_cancel_cost_converted = (double) $booking_data["ncancel_cost"] / $booking_data["cur_exchng_rate"];
                        $query = sprintf("UPDATE %stbl_users SET wallet_amount = wallet_amount - %f WHERE user_id = %d", DB_TBL_PREFIX, $night_time_cancel_cost_converted, $_SESSION["uid"]);
                        $result = mysqli_query($GLOBALS["DB"], $query);
                        $booking_title = str_pad($booking_id, 5, "0", STR_PAD_LEFT);
                        $notification_msg = __("Penalty deduction for cancelling driver-assigned booking {---1}", ["#" . $booking_title]);
                        $query = sprintf("INSERT INTO %stbl_notifications(person_id,user_type,content,n_type,date_created) VALUES \r\n                (\"%d\",0,\"%s\",3,\"%s\")", DB_TBL_PREFIX, $_SESSION["uid"], mysqli_real_escape_string($GLOBALS["DB"], $notification_msg), gmdate("Y-m-d H:i:s", time()));
                        $result = mysqli_query($GLOBALS["DB"], $query);
                        $transaction_id = crypto_string();
                        $query = sprintf("INSERT INTO %stbl_wallet_transactions (cur_symbol,cur_exchng_rate,cur_code,book_id,transaction_id,amount,wallet_balance,user_id,user_type,`desc`,`type`,transaction_date) VALUES(\"%s\",\"%s\",\"%s\",\"%d\",\"%s\",\"%s\",\"%s\",\"%d\",\"%d\",\"%s\",\"%d\",\"%s\")", DB_TBL_PREFIX, $booking_data["cur_symbol"], $booking_data["cur_exchng_rate"], $booking_data["cur_code"], $booking_id, $transaction_id, $booking_data["ncancel_cost"], $booking_data["user_wallet_amount"] - $night_time_cancel_cost_converted, $_SESSION["uid"], 0, mysqli_real_escape_string($GLOBALS["DB"], $notification_msg), 3, gmdate("Y-m-d H:i:s", time()));
                        $result = mysqli_query($GLOBALS["DB"], $query);
                        $driver_earning_amount = (double) $booking_data["ncancel_cost"] * $booking_data["driver_commision"] / 100;
                        $driver_earning_amount_converted = $driver_earning_amount / $booking_data["cur_exchng_rate"];
                        $query = sprintf("UPDATE %stbl_drivers SET wallet_amount = wallet_amount + %f WHERE driver_id = \"%d\"", DB_TBL_PREFIX, $driver_earning_amount_converted, $booking_data["driver_id"]);
                        $result = mysqli_query($GLOBALS["DB"], $query);
                        $transaction_id = crypto_string();
                        $query = sprintf("INSERT INTO %stbl_wallet_transactions (cur_symbol,cur_exchng_rate,cur_code,book_id,transaction_id,amount,wallet_balance,user_id,user_type,`desc`,`type`,transaction_date) VALUES(\"%s\",\"%s\",\"%s\",\"%d\",\"%s\",\"%s\",\"%s\",\"%d\",\"%d\",\"%s\",\"%d\",\"%s\")", DB_TBL_PREFIX, $booking_data["cur_symbol"], $booking_data["cur_exchng_rate"], $booking_data["cur_code"], $booking_id, $transaction_id, $driver_earning_amount, $driver_earning_amount_converted + $booking_data["driver_wallet_amount"], $booking_data["driver_id"], 1, "Earning for cancellation of booking: #" . $booking_id . " by rider", 2, gmdate("Y-m-d H:i:s", time()));
                        $result = mysqli_query($GLOBALS["DB"], $query);
                        if($booking_data["franchise_id"] == 1) {
                            $owner_franchise_earning = $booking_data["ncancel_cost"] - $driver_earning_amount;
                            $owner_franchise_earning_converted = $owner_franchise_earning / $booking_data["cur_exchng_rate"];
                            $query = sprintf("UPDATE %stbl_franchise SET fwallet_amount = fwallet_amount + %f WHERE id = \"%d\"", DB_TBL_PREFIX, $owner_franchise_earning_converted, 1);
                            $result = mysqli_query($GLOBALS["DB"], $query);
                            $transaction_id = crypto_string();
                            $query = sprintf("INSERT INTO %stbl_wallet_transactions (cur_symbol,cur_exchng_rate,cur_code,book_id,transaction_id,amount,wallet_balance,user_id,user_type,`desc`,`type`,transaction_date) VALUES(\"%s\",\"%s\",\"%s\",\"%d\",\"%s\",\"%s\",\"%s\",\"%d\",\"%d\",\"%s\",\"%d\",\"%s\")", DB_TBL_PREFIX, $booking_data["cur_symbol"], $booking_data["cur_exchng_rate"], $booking_data["cur_code"], $booking_id, $transaction_id, $owner_franchise_earning, $owner_franchise_earning_converted + $booking_data["fwallet_amount"], $booking_data["franchise_id"], 2, "Earning for cancellation of booking: #" . $booking_id . " by rider", 2, gmdate("Y-m-d H:i:s", time()));
                            $result = mysqli_query($GLOBALS["DB"], $query);
                        } else {
                            $owner_wallet_amount = 0;
                            $query = sprintf("SELECT fwallet_amount FROM %stbl_franchise WHERE id = 1", DB_TBL_PREFIX);
                            if(($result = mysqli_query($GLOBALS["DB"], $query)) && mysqli_num_rows($result)) {
                                $row = mysqli_fetch_assoc($result);
                                $owner_wallet_amount = $row["fwallet_amount"];
                            }
                            $owner_and_franchise_earning = $booking_data["ncancel_cost"] - $driver_earning_amount;
                            $other_franchise_earning = $owner_and_franchise_earning * $booking_data["franchise_commision"] / 100;
                            $other_franchise_earning_converted = (double) $other_franchise_earning / $booking_data["cur_exchng_rate"];
                            $owner_franchise_earning = $owner_and_franchise_earning - $other_franchise_earning;
                            $owner_franchise_earning_converted = $owner_franchise_earning / $booking_data["cur_exchng_rate"];
                            $query = sprintf("UPDATE %stbl_franchise SET fwallet_amount = fwallet_amount + %f WHERE id = \"%d\"", DB_TBL_PREFIX, $owner_franchise_earning_converted, 1);
                            $result = mysqli_query($GLOBALS["DB"], $query);
                            $transaction_id = crypto_string();
                            $query = sprintf("INSERT INTO %stbl_wallet_transactions (cur_symbol,cur_exchng_rate,cur_code,book_id,transaction_id,amount,wallet_balance,user_id,user_type,`desc`,`type`,transaction_date) VALUES(\"%s\",\"%s\",\"%s\",\"%d\",\"%s\",\"%s\",\"%s\",\"%d\",\"%d\",\"%s\",\"%d\",\"%s\")", DB_TBL_PREFIX, $booking_data["cur_symbol"], $booking_data["cur_exchng_rate"], $booking_data["cur_code"], $booking_id, $transaction_id, $owner_franchise_earning, $owner_franchise_earning_converted + $owner_wallet_amount, 1, 2, "Earning for cancellation of booking: #" . $booking_id . " by rider", 2, gmdate("Y-m-d H:i:s", time()));
                            $result = mysqli_query($GLOBALS["DB"], $query);
                            $query = sprintf("UPDATE %stbl_franchise SET fwallet_amount = fwallet_amount + %f WHERE id = \"%d\"", DB_TBL_PREFIX, $owner_franchise_earning_converted, $booking_data["frnchise_id"]);
                            $result = mysqli_query($GLOBALS["DB"], $query);
                            $transaction_id = crypto_string();
                            $query = sprintf("INSERT INTO %stbl_wallet_transactions (cur_symbol,cur_exchng_rate,cur_code,book_id,transaction_id,amount,wallet_balance,user_id,user_type,`desc`,`type`,transaction_date) VALUES(\"%s\",\"%s\",\"%s\",\"%d\",\"%s\",\"%s\",\"%s\",\"%d\",\"%d\",\"%s\",\"%d\",\"%s\")", DB_TBL_PREFIX, $booking_data["cur_symbol"], $booking_data["cur_exchng_rate"], $booking_data["cur_code"], $booking_id, $transaction_id, $other_franchise_earning, $other_franchise_earning_converted + $booking_data["fwallet_amount"], $booking_data["franchise_id"], 2, "Earning for cancellation of booking: #" . $booking_id . " by rider", 2, gmdate("Y-m-d H:i:s", time()));
                            $result = mysqli_query($GLOBALS["DB"], $query);
                        }
                    }
                } elseif(0 < $booking_data["user_wallet_amount"] && 0 < $booking_data["cancel_cost"]) {
                    $rider_wallet_balance = $booking_data["user_wallet_amount"] - $booking_data["cancel_cost"];
                    if($rider_wallet_balance < 0) {
                        $booking_data["cancel_cost"] = $booking_data["user_wallet_amount"];
                    }
                    $day_time_cancel_cost_converted = (double) $booking_data["cancel_cost"] / $booking_data["cur_exchng_rate"];
                    $query = sprintf("UPDATE %stbl_users SET wallet_amount = wallet_amount - %f WHERE user_id = %d", DB_TBL_PREFIX, $day_time_cancel_cost_converted, $_SESSION["uid"]);
                    $result = mysqli_query($GLOBALS["DB"], $query);
                    $booking_title = str_pad($booking_id, 5, "0", STR_PAD_LEFT);
                    $notification_msg = __("Penalty deduction for cancelling driver-assigned booking {---1}", ["#" . $booking_title]);
                    $query = sprintf("INSERT INTO %stbl_notifications(person_id,user_type,content,n_type,date_created) VALUES \r\n                (\"%d\",0,\"%s\",3,\"%s\")", DB_TBL_PREFIX, $_SESSION["uid"], mysqli_real_escape_string($GLOBALS["DB"], $notification_msg), gmdate("Y-m-d H:i:s", time()));
                    $result = mysqli_query($GLOBALS["DB"], $query);
                    $transaction_id = crypto_string();
                    $query = sprintf("INSERT INTO %stbl_wallet_transactions (cur_symbol,cur_exchng_rate,cur_code,book_id,transaction_id,amount,wallet_balance,user_id,user_type,`desc`,`type`,transaction_date) VALUES(\"%s\",\"%s\",\"%s\",\"%d\",\"%s\",\"%s\",\"%s\",\"%d\",\"%d\",\"%s\",\"%d\",\"%s\")", DB_TBL_PREFIX, $booking_data["cur_symbol"], $booking_data["cur_exchng_rate"], $booking_data["cur_code"], $booking_id, $transaction_id, $booking_data["cancel_cost"], $booking_data["user_wallet_amount"] - $day_time_cancel_cost_converted, $_SESSION["uid"], 0, mysqli_real_escape_string($GLOBALS["DB"], $notification_msg), 3, gmdate("Y-m-d H:i:s", time()));
                    $result = mysqli_query($GLOBALS["DB"], $query);
                    $driver_earning_amount = (double) $booking_data["cancel_cost"] * $booking_data["driver_commision"] / 100;
                    $driver_earning_amount_converted = $driver_earning_amount / $booking_data["cur_exchng_rate"];
                    $query = sprintf("UPDATE %stbl_drivers SET wallet_amount = wallet_amount + %f WHERE driver_id = \"%d\"", DB_TBL_PREFIX, $driver_earning_amount_converted, $booking_data["driver_id"]);
                    $result = mysqli_query($GLOBALS["DB"], $query);
                    $transaction_id = crypto_string();
                    $query = sprintf("INSERT INTO %stbl_wallet_transactions (cur_symbol,cur_exchng_rate,cur_code,book_id,transaction_id,amount,wallet_balance,user_id,user_type,`desc`,`type`,transaction_date) VALUES(\"%s\",\"%s\",\"%s\",\"%d\",\"%s\",\"%s\",\"%s\",\"%d\",\"%d\",\"%s\",\"%d\",\"%s\")", DB_TBL_PREFIX, $booking_data["cur_symbol"], $booking_data["cur_exchng_rate"], $booking_data["cur_code"], $booking_id, $transaction_id, $driver_earning_amount, $driver_earning_amount_converted + $booking_data["driver_wallet_amount"], $booking_data["driver_id"], 1, "Earning for cancellation of booking: #" . $booking_id . " by rider", 2, gmdate("Y-m-d H:i:s", time()));
                    $result = mysqli_query($GLOBALS["DB"], $query);
                    if($booking_data["franchise_id"] == 1) {
                        $owner_franchise_earning = $booking_data["cancel_cost"] - $driver_earning_amount;
                        $owner_franchise_earning_converted = $owner_franchise_earning / $booking_data["cur_exchng_rate"];
                        $query = sprintf("UPDATE %stbl_franchise SET fwallet_amount = fwallet_amount + %f WHERE id = \"%d\"", DB_TBL_PREFIX, $owner_franchise_earning_converted, 1);
                        $result = mysqli_query($GLOBALS["DB"], $query);
                        $transaction_id = crypto_string();
                        $query = sprintf("INSERT INTO %stbl_wallet_transactions (cur_symbol,cur_exchng_rate,cur_code,book_id,transaction_id,amount,wallet_balance,user_id,user_type,`desc`,`type`,transaction_date) VALUES(\"%s\",\"%s\",\"%s\",\"%d\",\"%s\",\"%s\",\"%s\",\"%d\",\"%d\",\"%s\",\"%d\",\"%s\")", DB_TBL_PREFIX, $booking_data["cur_symbol"], $booking_data["cur_exchng_rate"], $booking_data["cur_code"], $booking_id, $transaction_id, $owner_franchise_earning, $owner_franchise_earning_converted + $booking_data["fwallet_amount"], $booking_data["franchise_id"], 2, "Earning for cancellation of booking: #" . $booking_id . " by rider", 2, gmdate("Y-m-d H:i:s", time()));
                        $result = mysqli_query($GLOBALS["DB"], $query);
                    } else {
                        $owner_wallet_amount = 0;
                        $query = sprintf("SELECT fwallet_amount FROM %stbl_franchise WHERE id = 1", DB_TBL_PREFIX);
                        if(($result = mysqli_query($GLOBALS["DB"], $query)) && mysqli_num_rows($result)) {
                            $row = mysqli_fetch_assoc($result);
                            $owner_wallet_amount = $row["fwallet_amount"];
                        }
                        $owner_and_franchise_earning = $booking_data["cancel_cost"] - $driver_earning_amount;
                        $other_franchise_earning = $owner_and_franchise_earning * $booking_data["franchise_commision"] / 100;
                        $other_franchise_earning_converted = (double) $other_franchise_earning / $booking_data["cur_exchng_rate"];
                        $owner_franchise_earning = $owner_and_franchise_earning - $other_franchise_earning;
                        $owner_franchise_earning_converted = $owner_franchise_earning / $booking_data["cur_exchng_rate"];
                        $query = sprintf("UPDATE %stbl_franchise SET fwallet_amount = fwallet_amount + %f WHERE id = \"%d\"", DB_TBL_PREFIX, $owner_franchise_earning_converted, 1);
                        $result = mysqli_query($GLOBALS["DB"], $query);
                        $transaction_id = crypto_string();
                        $query = sprintf("INSERT INTO %stbl_wallet_transactions (cur_symbol,cur_exchng_rate,cur_code,book_id,transaction_id,amount,wallet_balance,user_id,user_type,`desc`,`type`,transaction_date) VALUES(\"%s\",\"%s\",\"%s\",\"%d\",\"%s\",\"%s\",\"%s\",\"%d\",\"%d\",\"%s\",\"%d\",\"%s\")", DB_TBL_PREFIX, $booking_data["cur_symbol"], $booking_data["cur_exchng_rate"], $booking_data["cur_code"], $booking_id, $transaction_id, $owner_franchise_earning, $owner_franchise_earning_converted + $owner_wallet_amount, 1, 2, "Earning for cancellation of booking: #" . $booking_id . " by rider", 2, gmdate("Y-m-d H:i:s", time()));
                        $result = mysqli_query($GLOBALS["DB"], $query);
                        $query = sprintf("UPDATE %stbl_franchise SET fwallet_amount = fwallet_amount + %f WHERE id = \"%d\"", DB_TBL_PREFIX, $owner_franchise_earning_converted, $booking_data["frnchise_id"]);
                        $result = mysqli_query($GLOBALS["DB"], $query);
                        $transaction_id = crypto_string();
                        $query = sprintf("INSERT INTO %stbl_wallet_transactions (cur_symbol,cur_exchng_rate,cur_code,book_id,transaction_id,amount,wallet_balance,user_id,user_type,`desc`,`type`,transaction_date) VALUES(\"%s\",\"%s\",\"%s\",\"%d\",\"%s\",\"%s\",\"%s\",\"%d\",\"%d\",\"%s\",\"%d\",\"%s\")", DB_TBL_PREFIX, $booking_data["cur_symbol"], $booking_data["cur_exchng_rate"], $booking_data["cur_code"], $booking_id, $transaction_id, $other_franchise_earning, $other_franchise_earning_converted + $booking_data["fwallet_amount"], $booking_data["franchise_id"], 2, "Earning for cancellation of booking: #" . $booking_id . " by rider", 2, gmdate("Y-m-d H:i:s", time()));
                        $result = mysqli_query($GLOBALS["DB"], $query);
                    }
                }
                $booking_title = str_pad($booking_id, 5, "0", STR_PAD_LEFT);
                $title = WEBSITE_NAME . " - Booking Cancelled";
                $body = __("The booking with ID({---1}) has been cancelled by the rider", [(string) $booking_title], "d|" . $dlang);
                $device_tokens = !empty($booking_data["push_notification_token"]) ? $booking_data["push_notification_token"] : 0;
                if(!empty($device_tokens)) {
                    sendPushNotification($title, $body, $device_tokens, NULL, 0);
                }
                $title = "";
                $body = "";
                $data = ["action" => "customer-cancelled", "booking_id" => $booking_id];
                $device_tokens = !empty($booking_data["push_notification_token"]) ? $booking_data["push_notification_token"] : 0;
                if(!empty($device_tokens)) {
                    sendPushNotification($title, $body, $device_tokens, $data, 0);
                }
                sendRealTimeNotification("drvr-" . $booking_data["driver_id"], $data);
            }
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
    $query = sprintf("SELECT * FROM %stbl_bookings WHERE id = %d AND user_id = %d", DB_TBL_PREFIX, $booking_id, $_SESSION["uid"]);
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
                        $count++;
                        $driver_photo = isset($row["driver_photo_file"]) ? $row["driver_photo_file"] : "0";
                        $driver_photo = SITE_URL . "ajaxphotofile.php?file=" . $driver_photo;
                        $chat_messages_html .= "<div style='text-align:left;margin-top: 5px;'><div style='background-color:#1e88e5;width:55%;border-radius:10px;padding:5px;display:inline-block'><div><i style='font-size: 22px;color: white;vertical-align: middle;' class='fa fa-user-circle-o'></i> <span style='font-weight:bold; font-size:14px;color:white;'>" . $row["driver_firstname"] . "</span></div><p style='margin: 5px 0;text-align: left;font-size: 14px;'>" . $row["chat_msg"] . "</p></div></div>";
                    } elseif($row["chat_user_id"] != 0) {
                        $user_photo = isset($row["user_photo_file"]) ? $row["user_photo_file"] : "0";
                        $user_photo = SITE_URL . "ajaxuserphotofile.php?file=" . $user_photo;
                        $chat_messages_html .= "<div style='text-align:right;margin-top: 5px;'><div style='background-color:#7cb342;width:55%;border-radius:10px;padding:5px;display:inline-block'><div style='text-align:left'><i style='font-size: 22px;color: white;vertical-align: middle;' class='fa fa-user-circle-o'></i> <span style='font-weight:bold; font-size:14px;color:white;'>" . $row["user_firstname"] . "</span></div><p style='margin: 5px 0;text-align: left;font-size: 14px;'>" . $row["chat_msg"] . "</p></div></div>";
                    }
                }
                if(isset($_SESSION["chats"][$booking_id]["driver_msg_count"])) {
                    if($_SESSION["chats"][$booking_id]["driver_msg_count"] < $count) {
                        $chat_new_content_status = 1;
                        $_SESSION["chats"][$booking_id]["driver_msg_count"] = $count;
                    }
                } else {
                    $_SESSION["chats"][$booking_id]["driver_msg_count"] = 0;
                    if($_SESSION["chats"][$booking_id]["driver_msg_count"] < $count) {
                        $chat_new_content_status = 1;
                        $_SESSION["chats"][$booking_id]["driver_msg_count"] = $count;
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
    $driver_data = [];
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
    $query = sprintf("SELECT %1\$stbl_drivers.driver_id, %1\$stbl_drivers.push_notification_token FROM %1\$stbl_bookings \r\n    INNER JOIN %1\$stbl_drivers ON %1\$stbl_drivers.driver_id = %1\$stbl_bookings.driver_id\r\n    WHERE %1\$stbl_bookings.id = %2\$d", DB_TBL_PREFIX, $booking_id);
    if($result = mysqli_query($GLOBALS["DB"], $query)) {
        if(mysqli_num_rows($result)) {
            $driver_data = mysqli_fetch_assoc($result);
            $query = sprintf("INSERT INTO %stbl_chats (chat_msg,`user_id`,booking_id,date_created) VALUES (\"%s\",\"%d\",\"%d\",\"%s\")", DB_TBL_PREFIX, $chat_msg, $_SESSION["uid"], $booking_id, gmdate("Y-m-d H:i:s", time()));
            if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
                $error = ["error" => __("An error has occured")];
                echo json_encode($error);
                exit;
            }
            $chat_messages_data = getchatcontent(1);
            $title = "";
            $body = "";
            $data = ["action" => "chat-message", "booking_id" => $booking_id, "message" => $chat_msg];
            $device_tokens = !empty($driver_data["push_notification_token"]) ? $driver_data["push_notification_token"] : 0;
            if(!empty($device_tokens)) {
                sendPushNotification($title, $body, $device_tokens, $data, 0);
            }
            sendRealTimeNotification("drvr-" . $driver_data["driver_id"], $data);
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
function syncservertime()
{
    $server_time = round(microtime(true) * 1000);
    $data = ["success" => 1, "server_time" => $server_time];
    echo json_encode($data);
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
    $query = sprintf("SELECT %1\$stbl_drivers.driver_id,%1\$stbl_drivers.firstname,%1\$stbl_drivers.lastname, %1\$stbl_drivers.driver_rating, %1\$stbl_drivers.account_create_date,%1\$stbl_drivers.completed_rides,%1\$stbl_drivers.cancelled_rides,%1\$stbl_drivers.rejected_rides, %1\$stbl_drivers.photo_file FROM %1\$stbl_bookings \r\n    INNER JOIN %1\$stbl_drivers ON %1\$stbl_drivers.driver_id = %1\$stbl_bookings.driver_id\r\n    WHERE %1\$stbl_bookings.id = %2\$d AND %1\$stbl_bookings.user_id = %3\$d", DB_TBL_PREFIX, $booking_id, $_SESSION["uid"]);
    if(($result = mysqli_query($GLOBALS["DB"], $query)) && mysqli_num_rows($result)) {
        $user_data = mysqli_fetch_assoc($result);
        $comments_ratings = "";
        $photo_file = isset($user_data["photo_file"]) ? $user_data["photo_file"] : "0";
        $query = sprintf("SELECT %1\$stbl_users.firstname, %1\$stbl_ratings_users.user_comment, %1\$stbl_ratings_users.user_rating FROM %1\$stbl_ratings_users \r\n            INNER JOIN %1\$stbl_bookings ON %1\$stbl_bookings.id = %1\$stbl_ratings_users.booking_id\r\n            INNER JOIN %1\$stbl_users ON %1\$stbl_users.user_id = %1\$stbl_ratings_users.user_id\r\n            WHERE %1\$stbl_bookings.driver_id = %2\$d ORDER BY %1\$stbl_ratings_users.id DESC LIMIT 100", DB_TBL_PREFIX, $user_data["driver_id"]);
        if(($result = mysqli_query($GLOBALS["DB"], $query)) && mysqli_num_rows($result)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $comments_ratings .= "<div style='padding: 10px 5px;border-top:thin solid #ccc;'> <div><span style='font-size:12px;font-weight:bold;margin-bottom:5px;'>" . $row["firstname"] . "</span></div> <div><img src='img/rating-" . $row["user_rating"] . ".png' style='width:50px;' /></div> <div><span style='font-size:14px;'>" . $row["user_comment"] . "</span></div> </div>";
            }
            $comments_ratings = "<div style='font-size:14px;font-weight:bold;padding: 5px 5px;text-align:left;'>" . __("Comments and Ratings") . "</div>" . $comments_ratings;
        }
        if(empty($comments_ratings)) {
            $comments_ratings = "<div style='padding: 50px 5px;text-align:center;'>" . __("No comments and ratings available") . "</div>";
        }
        $data = ["success" => 1, "userdata" => $user_data, "comments" => $comments_ratings, "photo" => SITE_URL . "ajaxphotofile.php?file=" . $photo_file];
        echo json_encode($data);
        exit;
    }
    $error = ["error" => 1];
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
    $user_route_id = !empty($_SESSION["route_id"]) ? $_SESSION["route_id"] : 0;
    $query = sprintf("SELECT COUNT(*) FROM %stbl_notifications WHERE (person_id = \"%d\" AND user_type = 0) OR (route_id = %d AND n_type = 5 AND user_type = 0)", DB_TBL_PREFIX, $_SESSION["uid"], $user_route_id);
    if($result = mysqli_query($GLOBALS["DB"], $query)) {
        if(mysqli_num_rows($result)) {
            $row = mysqli_fetch_assoc($result);
            $num_of_notifications = $row["COUNT(*)"];
        }
        mysqli_free_result($result);
    }
    $query = sprintf("SELECT *, DATE(date_created) AS created_date FROM %stbl_notifications WHERE (person_id = \"%d\" AND user_type = 0) OR (route_id = %d AND n_type = 5 AND user_type = 0) ORDER BY date_created DESC LIMIT 0,100", DB_TBL_PREFIX, $_SESSION["uid"], $user_route_id);
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
                                $formatted_notifications .= "<ons-list-item class='notification-item' id='notification-list-item-" . $date_notifications["id"] . "' modifier='longdivider'>\r\n                \r\n                                                <div class='center'>\r\n                                                <div style='width:100%;'>" . $notification_type . " <span style='font-weight:bold;'class='list-item__title'>" . $notification_time . " </span> " . $close_btn . "</div>\r\n                                                    <span class='list-item__subtitle'>" . $date_notifications["content"] . "</span>                                                    \r\n                                                </div>\r\n                                            \r\n                                            </ons-list-item>";
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
    $query = sprintf("DELETE FROM %stbl_notifications WHERE person_id = \"%d\" AND user_type = 0 AND id = \"%d\"", DB_TBL_PREFIX, $_SESSION["uid"], $notification_id);
    if(!($result = mysqli_query($GLOBALS["DB"], $query))) {
        $error = ["error" => "Failed to delete notifications"];
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
        $error = ["error" => "Please login."];
        echo json_encode($error);
        exit;
    }
    include "../drop-files/lib/pgateways/paystack/paystack-transaction-init.php";
}
function pesapalInit()
{
    if(empty($_SESSION["loggedin"])) {
        $error = ["error" => "Please login."];
        echo json_encode($error);
        exit;
    }
    include "../drop-files/lib/pgateways/pesapal/pesapal-transaction-init.php";
}
function paytrInit()
{
    if(empty($_SESSION["loggedin"])) {
        $error = ["error" => "Please login."];
        echo json_encode($error);
        exit;
    }
    include "../drop-files/lib/pgateways/paytr/paytr-transaction-init.php";
}
function stripeInit()
{
    if(empty($_SESSION["loggedin"])) {
        $error = ["error" => "Please login."];
        echo json_encode($error);
        exit;
    }
    include "../drop-files/lib/pgateways/stripe/stripe-transaction-init.php";
}
function flutterwaveInit()
{
    if(empty($_SESSION["loggedin"])) {
        $error = ["error" => "Please login."];
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
function customInit()
{
    if(empty($_SESSION["loggedin"])) {
        $error = ["error" => "Please re-login and retry."];
        echo json_encode($error);
        exit;
    }
    include "../drop-files/lib/pgateways/custom/custom-transaction-init.php";
}

?>