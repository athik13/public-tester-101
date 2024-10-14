<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2 & 7.3
 * @ Decoder version: 1.1.6
 * @ Release: 10/08/2022
 */

// Decoded file for php version 72.
include dirname(__DIR__) . "/drop-files/lib/common.php";
include dirname(__DIR__) . "/drop-files/config/db.php";
require dirname(__DIR__) . "/drop-files/lang/autodispatch/dict.php";
if(!isset($argv)) {
    exit("not for browser!");
}
define("BOOKING_PROCESSING_LIMIT", 50);
define("BOOKING_PICKUP_TIME_AUTO_CANCEL", DRIVER_ALLOCATE_ACCEPT_DURATION);
define("BOOKING_PICKUP_TIME_AGE_LIMIT", BOOKING_PICKUP_TIME_AUTO_CANCEL + DRIVER_ALLOCATE_ACCEPT_DURATION * 1);
define("MAX_CURRENT_DRIVER_REQUESTS", 1);
define("MAX_DRIVERS_ALLOCATE", 1);
define("UNCOMPLETED_BOOKING_AUTO_CANCEL_AGE", 86400);
define("SLEEP_TIME", 1);
set_time_limit(0);
date_default_timezone_set("UTC");
$file = fopen("crondata.dat", "w");
if($file === false) {
    echo "Unable to open file, check that you have permissions to open/write";
    exit;
}
$instances = exec("ps aux|grep " . basename(__FILE__) . "|grep -v grep|wc -l");
if(2 < $instances) {
    exit;
}
$count = 0;
$cron_exec_time = 0;
$data_count = 0;
$elapsed_exec_time_array = [];
$average_exec_time = 0;
while (1) {
    $cron_exec_time = microtime(true);
    if($cron_file_run = fopen(dirname(__FILE__) . "/crondata.txt", "r+")) {
        $data = fgets($cron_file_run);
        $data = trim($data);
        if($data == "0" || $data == "off") {
            fclose($cron_file_run);
            exit;
        }
        if($data == "2" || $data == "restart") {
            echo "restarted -" . time();
            fseek($cron_file_run, 0);
            fwrite($cron_file_run, "1");
            fclose($cron_file_run);
            exit;
        }
        fclose($cron_file_run);
        if(!mysqli_ping($GLOBALS["DB"])) {
            exit;
        }
        $elapsed_allocate_time = gmdate("Y-m-d H:i:s", time() - DRIVER_ALLOCATE_ACCEPT_DURATION);
        $query = sprintf("UPDATE %1\$stbl_driver_allocate\r\n        JOIN %1\$stbl_drivers ON %1\$stbl_drivers.driver_id = %1\$stbl_driver_allocate.driver_id\r\n        SET %1\$stbl_driver_allocate.status = 3, %1\$stbl_drivers.rejected_rides = %1\$stbl_drivers.rejected_rides + 1\r\n        WHERE %1\$stbl_driver_allocate.status = 0 AND %1\$stbl_driver_allocate.date_allocated <  \"%2\$s\"", DB_TBL_PREFIX, $elapsed_allocate_time);
        $result = mysqli_query($GLOBALS["DB"], $query);
        $elapsed_pickup_time = gmdate("Y-m-d H:i:s", time() - BOOKING_PICKUP_TIME_AGE_LIMIT);
        $query = sprintf("UPDATE %1\$stbl_bookings \r\n        JOIN %1\$stbl_routes ON %1\$stbl_routes.id = %1\$stbl_bookings.route_id\r\n        SET %1\$stbl_bookings.status = 5 \r\n        WHERE %1\$stbl_bookings.pickup_datetime <  \"%2\$s\" AND %1\$stbl_bookings.driver_id = 0 AND %1\$stbl_routes.r_scope = 0", DB_TBL_PREFIX, $elapsed_pickup_time);
        $result = mysqli_query($GLOBALS["DB"], $query);
        $bookings_data = [];
        $bookings_data_routes = [];
        $bookings_data_driver_alloc = [];
        $bookings_pickuptime_lower_limit = gmdate("Y-m-d H:i:s", time() - BOOKING_PICKUP_TIME_AGE_LIMIT);
        $bookings_pickuptime_upper_limit = gmdate("Y-m-d H:i:s", time());
        $query = sprintf("SELECT %1\$stbl_bookings.waypoint1_address,%1\$stbl_bookings.waypoint1_long,%1\$stbl_bookings.waypoint1_lat,%1\$stbl_bookings.waypoint2_address,%1\$stbl_bookings.waypoint2_long,%1\$stbl_bookings.waypoint2_lat,%1\$stbl_users.user_rating, %1\$stbl_users.photo_file, %1\$stbl_users.firstname, %1\$stbl_users.user_id, %1\$stbl_users.push_notification_token, %1\$stbl_bookings.id AS booking_id,%1\$stbl_bookings.pickup_address,%1\$stbl_bookings.dropoff_address,%1\$stbl_bookings.completion_code,%1\$stbl_bookings.pickup_long,%1\$stbl_bookings.pickup_lat,%1\$stbl_bookings.dropoff_long,%1\$stbl_bookings.dropoff_lat,%1\$stbl_bookings.route_id,%1\$stbl_bookings.user_phone,%1\$stbl_bookings.estimated_cost,%1\$stbl_bookings.payment_type,%1\$stbl_bookings.coupon_code,%1\$stbl_bookings.coupon_discount_type,%1\$stbl_bookings.coupon_discount_value,%1\$stbl_bookings.referral_discount_value,%1\$stbl_bookings.referral_used,%1\$stbl_bookings.ride_id,%1\$stbl_bookings.pickup_datetime,%1\$stbl_driver_allocate.driver_id AS driver_id, %1\$stbl_driver_allocate.status, %1\$stbl_users.disp_lang FROM %1\$stbl_bookings\r\n        INNER JOIN %1\$stbl_routes ON %1\$stbl_routes.id = %1\$stbl_bookings.route_id AND %1\$stbl_routes.r_scope = 0\r\n        INNER JOIN %1\$stbl_users ON %1\$stbl_users.user_id = %1\$stbl_bookings.user_id\r\n        LEFT JOIN %1\$stbl_driver_allocate ON %1\$stbl_driver_allocate.booking_id = %1\$stbl_bookings.id\r\n        WHERE %1\$stbl_bookings.driver_id = 0 AND %1\$stbl_bookings.status = 0 AND %1\$stbl_bookings.pickup_datetime > \"%2\$s\" AND %1\$stbl_bookings.pickup_datetime < \"%3\$s\" LIMIT %4\$d", DB_TBL_PREFIX, $bookings_pickuptime_lower_limit, $bookings_pickuptime_upper_limit, BOOKING_PROCESSING_LIMIT);
        if($result = mysqli_query($GLOBALS["DB"], $query)) {
            if(mysqli_num_rows($result)) {
                while ($row = mysqli_fetch_assoc($result)) {
                    if(!empty($row["driver_id"])) {
                        $bookings_data_driver_alloc[$row["booking_id"]][$row["driver_id"]] = $row;
                    }
                    $bookings_data[$row["booking_id"]]["b_id"] = $row["booking_id"];
                    $bookings_data[$row["booking_id"]]["p_long"] = $row["pickup_long"];
                    $bookings_data[$row["booking_id"]]["p_address"] = $row["pickup_address"];
                    $bookings_data[$row["booking_id"]]["d_address"] = $row["dropoff_address"];
                    $bookings_data[$row["booking_id"]]["p_lat"] = $row["pickup_lat"];
                    $bookings_data[$row["booking_id"]]["p_lng"] = $row["pickup_long"];
                    $bookings_data[$row["booking_id"]]["d_lat"] = $row["dropoff_lat"];
                    $bookings_data[$row["booking_id"]]["d_lng"] = $row["dropoff_long"];
                    $bookings_data[$row["booking_id"]]["token"] = $row["push_notification_token"];
                    $bookings_data[$row["booking_id"]]["uid"] = $row["user_id"];
                    $bookings_data[$row["booking_id"]]["pickup_datetime"] = $row["pickup_datetime"];
                    $bookings_data[$row["booking_id"]]["user_rating"] = $row["user_rating"];
                    $bookings_data[$row["booking_id"]]["user_firstname"] = $row["firstname"];
                    $bookings_data[$row["booking_id"]]["user_photo"] = $row["photo_file"];
                    $bookings_data[$row["booking_id"]]["user_phone"] = $row["user_phone"];
                    $bookings_data[$row["booking_id"]]["coupon_code"] = $row["coupon_code"];
                    $bookings_data[$row["booking_id"]]["coupon_discount_type"] = $row["coupon_discount_type"];
                    $bookings_data[$row["booking_id"]]["referral_discount_value"] = $row["referral_discount_value"];
                    $bookings_data[$row["booking_id"]]["coupon_discount_value"] = $row["coupon_discount_value"];
                    $bookings_data[$row["booking_id"]]["referral_used"] = $row["referral_used"];
                    $bookings_data[$row["booking_id"]]["estimated_cost"] = $row["estimated_cost"];
                    $bookings_data[$row["booking_id"]]["payment_type"] = $row["payment_type"];
                    $bookings_data[$row["booking_id"]]["ride_id"] = $row["ride_id"];
                    $bookings_data[$row["booking_id"]]["route_id"] = $row["route_id"];
                    $bookings_data[$row["booking_id"]]["u_lang"] = $row["disp_lang"];
                    $bookings_data[$row["booking_id"]]["completion_code"] = $row["completion_code"];
                    $bookings_data[$row["booking_id"]]["waypoint1_address"] = $row["waypoint1_address"];
                    $bookings_data[$row["booking_id"]]["waypoint1_long"] = $row["waypoint1_long"];
                    $bookings_data[$row["booking_id"]]["waypoint1_lat"] = $row["waypoint1_lat"];
                    $bookings_data[$row["booking_id"]]["waypoint2_address"] = $row["waypoint2_address"];
                    $bookings_data[$row["booking_id"]]["waypoint2_long"] = $row["waypoint2_long"];
                    $bookings_data[$row["booking_id"]]["waypoint2_lat"] = $row["waypoint2_lat"];
                    $bookings_data_routes[] = $row["route_id"];
                }
                mysqli_free_result($result);
                $bookings_data_routes = array_unique($bookings_data_routes);
                $route_ids_string = implode(",", $bookings_data_routes);
                $drivers_location_data = [];
                $drivers_alloc_pending = [];
                $drivers_alloc_servicing = [];
                $location_info_age = gmdate("Y-m-d H:i:s", time() - LOCATION_INFO_VALID_AGE);
                $query = sprintf("SELECT driver_id, `status` FROM %stbl_driver_allocate\r\n         WHERE `status` = 0 OR `status` = 1", DB_TBL_PREFIX);
                if(($result = mysqli_query($GLOBALS["DB"], $query)) && mysqli_num_rows($result)) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        if($row["status"] == "0") {
                            $drivers_alloc_pending[$row["driver_id"]] = 1;
                        } elseif($row["status"] == "1") {
                            if(empty($drivers_alloc_servicing[$row["driver_id"]])) {
                                $drivers_alloc_servicing[$row["driver_id"]] = 1;
                            } else {
                                $drivers_alloc_servicing[$row["driver_id"]] += 1;
                            }
                        }
                    }
                }
                $query = sprintf("SELECT %1\$stbl_drivers.driver_id,%1\$stbl_drivers.disp_lang,%1\$stbl_drivers.push_notification_token,%1\$stbl_driver_location.long,%1\$stbl_driver_location.lat, %1\$stbl_drivers.route_id, %1\$stbl_drivers.ride_id FROM %1\$stbl_driver_location \r\n         INNER JOIN %1\$stbl_drivers ON %1\$stbl_driver_location.driver_id = %1\$stbl_drivers.driver_id\r\n         WHERE %1\$stbl_drivers.route_id IN(%3\$s) AND %1\$stbl_drivers.is_activated = 1 AND %1\$stbl_drivers.available = 1 AND %1\$stbl_driver_location.location_date > \"%2\$s\" AND %1\$stbl_drivers.wallet_amount > %4\$f", DB_TBL_PREFIX, $location_info_age, $route_ids_string, DRIVER_MIN_WALLET_BALANCE);
                if(($result = mysqli_query($GLOBALS["DB"], $query)) && mysqli_num_rows($result)) {
                    $drivers_online_count = 0;
                    while ($row = mysqli_fetch_assoc($result)) {
                        $drivers_online_count++;
                        if(AVAILABLE_ACTIVE_DRIVER_COUNT_NUMBER < 5000 && AVAILABLE_ACTIVE_DRIVER_COUNT_NUMBER < $drivers_online_count) {
                            break;
                        }
                        if(isset($drivers_alloc_pending[$row["driver_id"]])) {
                        } elseif(empty($drivers_alloc_servicing[$row["driver_id"]])) {
                            $drivers_location_data[$row["driver_id"]] = $row;
                        } elseif($drivers_alloc_servicing[$row["driver_id"]] < MAX_CURRENT_DRIVER_REQUESTS) {
                            $drivers_location_data[$row["driver_id"]] = $row;
                        }
                    }
                    mysqli_free_result($result);
                }
                foreach ($bookings_data as $bookingsdata) {
                    $driver_pending_accept = 0;
                    $drivers_cancelled_allocate = [];
                    if(!empty($bookings_data_driver_alloc[$bookingsdata["b_id"]])) {
                        foreach ($bookings_data_driver_alloc[$bookingsdata["b_id"]] as $bookingsdata_allocated) {
                            if($bookingsdata_allocated["status"] == "0" || $bookingsdata_allocated["status"] == "1") {
                                $driver_pending_accept = 1;
                            }
                            if($bookingsdata_allocated["status"] == "2" || $bookingsdata_allocated["status"] == "3") {
                                $drivers_cancelled_allocate[] = $bookingsdata_allocated["driver_id"];
                            }
                        }
                    }
                    if($driver_pending_accept) {
                    } else {
                        $drivers_distance_array = [];
                        foreach ($drivers_location_data as $driverslocationdata) {
                            if($bookingsdata["route_id"] != $driverslocationdata["route_id"]) {
                            } elseif($bookingsdata["ride_id"] != $driverslocationdata["ride_id"]) {
                            } elseif(in_array($driverslocationdata["driver_id"], $drivers_cancelled_allocate)) {
                            } else {
                                $booking_pickup_long = !empty($bookingsdata["p_long"]) ? $bookingsdata["p_long"] : 0;
                                $booking_pickup_lat = !empty($bookingsdata["p_long"]) ? $bookingsdata["p_lat"] : 0;
                                $driver_location_long = !empty($driverslocationdata["long"]) ? $driverslocationdata["long"] : 0;
                                $driver_location_lat = !empty($driverslocationdata["lat"]) ? $driverslocationdata["lat"] : 0;
                                $distance = distance($booking_pickup_lat, $booking_pickup_long, $driver_location_lat, $driver_location_long);
                                $drivers_distance_array[$driverslocationdata["driver_id"]] = $distance;
                            }
                        }
                        asort($drivers_distance_array);
                        $driver_found = 0;
                        $number_of_drivers_to_allocate = 0;
                        foreach ($drivers_distance_array as $driver_id => $driver_distance) {
                            $number_of_drivers_to_allocate++;
                            if(MAX_DRIVERS_ALLOCATE < $number_of_drivers_to_allocate) {
                                if(!$driver_found && strtotime($bookingsdata["pickup_datetime"] . " UTC") < time() - BOOKING_PICKUP_TIME_AUTO_CANCEL) {
                                    $query = sprintf("UPDATE %stbl_bookings SET `status` = 5 WHERE id = \"%d\"", DB_TBL_PREFIX, $bookingsdata["b_id"]);
                                    $result = mysqli_query($GLOBALS["DB"], $query);
                                    $query = sprintf("DELETE FROM %stbl_driver_allocate WHERE booking_id = \"%d\"", DB_TBL_PREFIX, $bookingsdata["b_id"]);
                                    $result = mysqli_query($GLOBALS["DB"], $query);
                                    $booking_title = str_pad($bookingsdata["b_id"], 5, "0", STR_PAD_LEFT);
                                    $title = WEBSITE_NAME . " - " . ___("Booking Cancelled", [], $bookingsdata["u_lang"]);
                                    $body = ___("We couldn't locate a driver close to your set pickup location for booking ID({---1}). Please try again later.", [(string) $booking_title], $bookingsdata["u_lang"]);
                                    $device_tokens = $bookingsdata["token"];
                                    if(!empty($device_tokens)) {
                                        sendPushNotification($title, $body, $device_tokens, NULL, 0);
                                    }
                                    $booking_title = str_pad($bookingsdata["b_id"], 5, "0", STR_PAD_LEFT);
                                    $title = "";
                                    $body = "";
                                    $device_tokens = $bookingsdata["token"];
                                    $data = ["action" => "app-message", "no_driver" => 1, "booking_id" => $bookingsdata["b_id"], "title" => "", "message" => ___("Your Booking with booking ID({---1}) has been cancelled. No driver is available within your set pickup location. Please try again later.", [(string) $booking_title], $bookingsdata["u_lang"])];
                                    if(!empty($device_tokens)) {
                                        sendPushNotification($title, $body, $device_tokens, $data, 0);
                                    }
                                    $content = ___("Your Booking with booking ID({---1}) has been cancelled. No driver is available within your set pickup location. Please try again later.", [(string) $booking_title], $bookingsdata["u_lang"]);
                                    $query = sprintf("INSERT INTO %stbl_notifications(person_id,user_type,content,n_type,date_created) VALUES \r\n                        (\"%d\",0,\"%s\",0,\"%s\")", DB_TBL_PREFIX, $bookingsdata["uid"], mysqli_real_escape_string($GLOBALS["DB"], $content), gmdate("Y-m-d H:i:s", time()));
                                    $result = mysqli_query($GLOBALS["DB"], $query);
                                }
                            } elseif(!empty($driver_id) && $driver_distance <= MAX_DRIVER_DISTANCE) {
                                $driver_found = 1;
                                $query = sprintf("INSERT INTO %stbl_driver_allocate(booking_id,driver_id,`status`,date_allocated) VALUES \r\n                        (\"%d\",\"%d\",\"%d\",\"%s\")", DB_TBL_PREFIX, $bookingsdata["b_id"], $driver_id, 0, gmdate("Y-m-d H:i:s", time()));
                                $result = mysqli_query($GLOBALS["DB"], $query);
                                $closest_driver_data = [];
                                $booking_title = str_pad($bookingsdata["b_id"], 5, "0", STR_PAD_LEFT);
                                $title = WEBSITE_NAME . " - " . ___("New Booking", [], $drivers_location_data[$driver_id]["disp_lang"]);
                                $body = ___("You have a new booking with ID({---1}). Please respond to this booking immediately as your customer is waiting.", [(string) $booking_title], $drivers_location_data[$driver_id]["disp_lang"]);
                                $device_tokens = !empty($drivers_location_data[$driver_id]["push_notification_token"]) ? $drivers_location_data[$driver_id]["push_notification_token"] : 0;
                                if(!empty($device_tokens)) {
                                    sendPushNotification($title, $body, $device_tokens, NULL, 0);
                                }
                                $title = "";
                                $body = "";
                                $photo_file = isset($bookingsdata["user_photo"]) ? $bookingsdata["user_photo"] : "0";
                                $data = ["action" => "driver-allocate", "booking_id" => $bookingsdata["b_id"], "p_address" => $bookingsdata["p_address"], "p_lat" => $bookingsdata["p_lat"], "p_lng" => $bookingsdata["p_lng"], "d_address" => $bookingsdata["d_address"], "d_lat" => $bookingsdata["d_lat"], "d_lng" => $bookingsdata["d_lng"], "rider_image" => SITE_URL . "ajaxuserphotofile.php?file=" . $photo_file, "rider_name" => $bookingsdata["user_firstname"], "rider_phone" => $bookingsdata["user_phone"], "rider_rating" => $bookingsdata["user_rating"], "completion_code" => $bookingsdata["completion_code"], "driver_accept_duration" => DRIVER_ALLOCATE_ACCEPT_DURATION, "sent_time" => time(), "fare" => $bookingsdata["estimated_cost"], "payment_type" => $bookingsdata["payment_type"], "coupon_code" => $bookingsdata["coupon_code"], "coupon_discount_type" => $bookingsdata["coupon_discount_type"], "coupon_discount_value" => $bookingsdata["coupon_discount_value"], "referral_discount_value" => $bookingsdata["referral_discount_value"], "referral_used" => $bookingsdata["referral_used"], "waypoint1_address" => $bookingsdata["waypoint1_address"], "waypoint1_long" => $bookingsdata["waypoint1_long"], "waypoint1_lat" => $bookingsdata["waypoint1_lat"], "waypoint2_address" => $bookingsdata["waypoint2_address"], "waypoint2_long" => $bookingsdata["waypoint2_long"], "waypoint2_lat" => $bookingsdata["waypoint2_lat"]];
                                $device_tokens = !empty($drivers_location_data[$driver_id]["push_notification_token"]) ? $drivers_location_data[$driver_id]["push_notification_token"] : 0;
                                if(!empty($device_tokens)) {
                                    sendPushNotification($title, $body, $device_tokens, $data, 0);
                                }
                                sendRealTimeNotification("drvr-" . $driver_id, $data);
                                unset($drivers_location_data[$driver_id]);
                            }
                        }
                    }
                }
                unset($body);
                unset($booking_pickup_lat);
                unset($booking_pickup_long);
                unset($booking_title);
                unset($bookings_data);
                unset($bookings_data_driver_alloc);
                unset($bookings_data_routes);
                unset($bookings_pickuptime_lower_limit);
                unset($bookings_pickuptime_upper_limit);
                unset($bookingsdata);
                unset($bookingsdata_allocated);
                unset($closest_driver_data);
                unset($content);
                unset($cron_file_run);
                unset($data);
                unset($device_tokens);
                unset($distance);
                unset($driver_distance);
                unset($driver_found);
                unset($driver_id);
                unset($driver_location_lat);
                unset($driver_location_long);
                unset($driver_pending_accept);
                unset($drivers_cancelled_allocate);
                unset($drivers_distance_array);
                unset($drivers_location_data);
                unset($driverslocationdata);
                unset($elapsed_allocate_time);
                unset($file);
                unset($location_info_age);
                unset($number_of_drivers_to_allocate);
                unset($query);
                unset($result);
                unset($route_ids_string);
                unset($row);
                unset($title);
                $elapsed_exec_time = microtime(true) - $cron_exec_time;
                $elapsed_exec_time_array[] = $elapsed_exec_time;
                $data_count++;
                if(2 < $data_count) {
                    $data_count = 0;
                    $average_exec_time = array_sum($elapsed_exec_time_array) / 3;
                    $elapsed_exec_time_array = [];
                }
                $data = ["elapsed_exec" => $elapsed_exec_time, "avg_time" => $average_exec_time, "last_updated" => time()];
                sleep(SLEEP_TIME);
            } else {
                sleep(SLEEP_TIME);
            }
        } else {
            sleep(SLEEP_TIME);
        }
    } else {
        exit;
    }
}
fclose($file);
exit;

?>