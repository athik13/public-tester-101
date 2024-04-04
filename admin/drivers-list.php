<?php
session_start();
include("../../drop-files/lib/common.php");
include "../../drop-files/config/db.php";

$drivers_page_items = array();
$p_lat = 0.00;
$p_lng = 0.00;
$d_lat = 0.00;
$d_lng = 0.00;
$ride_booked_id = 0; 



$p_lat = !empty($_GET['plat']) ? (float) $_GET['plat'] : 0.00;
$p_lng = !empty($_GET['plng']) ? (float) $_GET['plng'] : 0.00;
$d_lat = !empty($_GET['dlat']) ? (float) $_GET['dlat'] : 0.00;
$d_lng = !empty($_GET['dlng']) ? (float) $_GET['dlng'] : 0.00;

$ride_booked_id = !empty($_GET['ride_id']) ? (int) $_GET['ride_id'] : 0;
$booking_id = !empty($_GET['booking_id']) ? (int) $_GET['booking_id'] : 0;

if(isset($_SESSION['expired_session'])){
    echo "<h1 style='text-align:center;'>Your session has expired. Please logout and login to continue.</h1>";
    exit;
}

if(!(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == 1)){ //if user is not logged in run this code
    echo "<h1 style='text-align:center;'>Please login to continue.</h1>";
    exit;
}

if($_SESSION['account_type'] != 2 && $_SESSION['account_type'] != 3){ ////if user is an admin or dispatcher
    echo "<h1 style='text-align:center;'>You are not authorized to access this page!.</h1>";
    exit;
}

$route_id = (int) $_GET['route_id'];

$booked_drivers_data = [];

//get drivers in the city who are currently onride or allocated to bookings
$query = sprintf('SELECT %1$stbl_bookings.id AS booking_id,%1$stbl_bookings.driver_id AS booking_driver, %1$stbl_driver_allocate.driver_id AS booking_driver_alloc, %1$stbl_driver_allocate.status AS booking_driver_alloc_status  FROM %1$stbl_bookings
INNER JOIN %1$stbl_drivers ON %1$stbl_drivers.driver_id = %1$stbl_bookings.driver_id
LEFT JOIN %1$stbl_driver_allocate ON %1$stbl_driver_allocate.booking_id = %1$stbl_bookings.id
WHERE (%1$stbl_bookings.status = 0 OR %1$stbl_bookings.status = 1)', DB_TBL_PREFIX);

if($result = mysqli_query($GLOBALS['DB'], $query)){
  
    if(mysqli_num_rows($result)){
        while($row = mysqli_fetch_assoc($result)){
            if(!empty($row['booking_driver'])){
                $booked_drivers_data[$row['booking_driver']] = array('driver_id' => $row['booking_driver'], 'status' => "Servicing booking <a href='view-booking.php?bkid={$row['booking_id']}'>#{$row['booking_id']}</a>");
                break;
            }
            if(!empty($row['booking_driver_alloc'] && $row['booking_driver_alloc_status'] == 0 )){
                $booked_drivers_data[$row['booking_driver_alloc']] = array('driver_id' => $row['booking_driver_alloc'], 'status' => "Allocated to booking <a href='view-booking.php?bkid={$row['booking_id']}'>#{$row['booking_id']}</a>");
                break;
            }
            
        }
    
     }
    mysqli_free_result($result);
}



//get all drivers
$location_info_age = gmdate('Y-m-d H:i:s', time() - LOCATION_INFO_VALID_AGE);

$query = sprintf('SELECT * FROM %1$stbl_drivers
LEFT JOIN %1$stbl_rides ON %1$stbl_rides.id = %1$stbl_drivers.ride_id
INNER JOIN %1$stbl_routes ON %1$stbl_routes.id = %1$stbl_drivers.route_id
LEFT JOIN %1$stbl_franchise ON %1$stbl_franchise.id = %1$stbl_drivers.franchise_id
INNER JOIN %1$stbl_driver_location ON %1$stbl_driver_location.driver_id = %1$stbl_drivers.driver_id
WHERE %1$stbl_drivers.available = 1 AND %1$stbl_driver_location.location_date > "%2$s"', DB_TBL_PREFIX, $location_info_age);


if($result = mysqli_query($GLOBALS['DB'], $query)){
  
    if(mysqli_num_rows($result)){
        while($row = mysqli_fetch_assoc($result)){
            $drivers_page_items[] = $row;
        }
    
     }
    mysqli_free_result($result);
}   


?>

<!DOCTYPE html>
<html>
    <head>

        <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
        <!-- Bootstrap 3.3.6 -->
        <link rel="stylesheet" href="../css/bootstrap.min.css">
        <link rel="stylesheet" href="../font-awesome/css/font-awesome.min.css">
        <!-- Ionicons -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/ionicons/2.0.1/css/ionicons.min.css">
        <!-- Theme style -->
        <link rel="stylesheet" href="../css/AdminLTE.min.css">
        <!-- AdminLTE Skins. Choose a skin from the css/skins
            folder instead of downloading all of them to reduce the load. -->
        <link rel="stylesheet" href="../css/skins/sisc.css">
        <link rel="stylesheet" href="../css/admin-style.css">
        <link rel="stylesheet" href="../css/sweetalert.css">
        <link rel="stylesheet" href="../fancybox/source/jquery.fancybox.css?v=2.1.6" type="text/css" media="screen" />
    </head>


    <body>

        <div class="container">
            <div class="row">
                <div class="col-sm-12 table-responsive" style="height:100%;margin-bottom: 100px;">
                    <?php echo "<h3 style='text-align:center;' >Dispatch Driver For Booking ". (!empty($booking_id) ? "#{$booking_id}": "") . "</h3>";?>
                    <hr>
                    <table class='table table-bordered'>
                    <thead>
                        <tr>
                        <th>#</th>
                        <th></th>    
                        <th style="">Photo</th>
                        <th style="">Driver</th>                        
                        <th style="">Status</th>
                        <th style="">Franchise</th>
                        <th style="">Vehicle</th>
                        <th style="">City</th>
                        
                        
                        </tr>
                    </thead>
                    <tbody>
                        
                        <?php
                        
                        $count = 1;
                            foreach($drivers_page_items as $driverspageitems){

                                //if($ride_booked_id && $ride_booked_id != $driverspageitems['ride_id'])continue;

                                if(isset($booked_drivers_data[$driverspageitems['driver_id']])){
                                    $driver_status = "<i class='fa fa-info-circle' style='color:blue'></i> " . $booked_drivers_data[$driverspageitems['driver_id']]['status'];
                                    $driver_available = 'disabled';
                                }else{
                                    $driver_status = "<i class='fa fa-check-circle' style='color:#06d606'></i> Available";
                                    $driver_available = '';
                                }
                                
                                if(!empty($driverspageitems['ride_type'])){
                                    $ride_type = $driverspageitems['ride_id'] == $ride_booked_id ? "<i class='fa fa-circle' style='color:#06d606'></i> {$driverspageitems['ride_type']}" : "<span style='color:red'>{$driverspageitems['ride_type']}</span>";
                                }else{
                                    $ride_type = "N/A";
                                }

                                $photo_file = isset($driverspageitems['photo_file']) ? $driverspageitems['photo_file'] : "0";
                                $driver_name = $driverspageitems['firstname'] . " " .  $driverspageitems['lastname'] . "<br>" . $driverspageitems['country_dial_code'] . " " . (!empty(DEMO) ? mask_string($driverspageitems['phone']) : $driverspageitems['phone']);
                                

                                echo "<tr><td>". $count++ . "</td><td>"."<input {$driver_available} class='drvr-sel' name='driver-select' id='driver-select-{$driverspageitems['driver_id']}' type='radio' data-drvrid='{$driverspageitems['driver_id']}' data-drvrname='{$driver_name}'>"."</td><td>"."<img class='' width='32px' src='../photofile.php?file=". $photo_file ."' />"."</td><td>".$driver_name."</td><td>".$driver_status . "</td><td>". $driverspageitems['franchise_name'] . "</td><td>".$ride_type  ."</td><td>{$driverspageitems['r_title']}</td></tr>";
                            }

                            
                       
                        ?>
                    </tbody>
                    </table>
                    <div><?php if(empty($driverspageitems)){echo "<h3 style='text-align:center;'>No Driver Available</h3>";} ?></div>
                    


                </div>

                <div style="padding:15px 0;bottom: 0;width: 100%;background: white;position:fixed;">
                    <input  type="text"  hidden="hidden" id="okclicked"   name="okclicked" value="0" >
                    
                    <button style = "max-width:200px;" id="driverselectdone" class="btn btn-block btn-primary btn-flat center-block" value="1" name="driverselectdone">Ok</button>
                </div>

            </div>

        </div>







    <!-- jQuery 2.2.3 -->
    <script src="../js/jquery-1.9.1.min.js"></script>
    <!-- Bootstrap 3.3.6 -->
    <script src="../js/bootstrap.min.js"></script>
     <script>
     $('#driverselectdone').click(function(){
        $("#okclicked").val('1');
        parent.$.fancybox.close( [true] )
         
         
     })


     </script>   

    </body>


</html>
