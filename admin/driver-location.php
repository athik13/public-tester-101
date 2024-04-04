<?php
session_start();
include("../../drop-files/lib/common.php");
include "../../drop-files/config/db.php";

$driver_id = !empty($_GET['driver_id']) ? (int) $_GET['driver_id'] : 0;
$drivers_location_items = [];

if(empty($driver_id)){
    echo "<h1 style='text-align:center;'>No location information available.</h1>";
    exit;
}

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



$query = sprintf('SELECT * FROM %1$stbl_driver_location WHERE driver_id = %2$d ', DB_TBL_PREFIX, $driver_id);


if($result = mysqli_query($GLOBALS['DB'], $query)){
  
    if(mysqli_num_rows($result)){
        while($row = mysqli_fetch_assoc($result)){
            $drivers_location_items[] = $row;
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
        <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo GMAP_API_KEY; ?>&libraries=places"></script>
    </head>


    <body>

        <div class="container">
            <div class="row">
                <div class="col-sm-12">
                    <h4 id='location-update'></h4>
                    <div style="width:100%;height:500px;" id="driver-location-map"></div>  


                </div>

                

            </div>

        </div>


        





    <!-- jQuery 2.2.3 -->
    <script src="../js/jquery-1.9.1.min.js"></script>
    <!-- Bootstrap 3.3.6 -->
    <script src="../js/bootstrap.min.js"></script>
    <script>

        var map = undefined; 
        var bounds = undefined; 
        var marker = undefined;                  
        var latLong = undefined;
        var mapOptions = undefined;
        var longitue;
        var latitude;



        if (typeof google === 'object' && typeof google.maps === 'object') {
            
            if(typeof mapOptions === 'undefined'){
                mapOptions = {
                center: new google.maps.LatLng(9.0338725,8.677457),
                zoom: 5,
                disableDefaultUI: false,
                mapTypeId: google.maps.MapTypeId.ROADMAP
            };
            map = new google.maps.Map(document.getElementById("driver-location-map"), mapOptions);
            directionsService = new google.maps.DirectionsService;
            directionsDisplay = new google.maps.DirectionsRenderer({
                map: map
            });
            bounds = new google.maps.LatLngBounds();
            latitude = 9.0338725;
            longitude = 8.677457;
            latLong = new google.maps.LatLng(latitude,longitude);
            marker = new google.maps.Marker({
                                                position: latLong,
                                                map: map
                                            });
                    

            }

            
        }






        </script>

    </body>


</html>
