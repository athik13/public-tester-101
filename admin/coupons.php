<?php
session_start();
include("../../drop-files/lib/common.php");
include "../../drop-files/config/db.php";

if(isset($_SESSION['expired_session'])){
    header("location: ".SITE_URL."login.php?timeout=1");
    exit;
}

if(!(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == 1)){ //if user is not logged in run this code
  header("location: ".SITE_URL."login.php"); //Yes? then redirect user to the login page
  exit;
}

if($_SESSION['account_type'] /* != 2 && $_SESSION['account_type'] */ != 3){ ////if user is an admin or dispatcher
    $_SESSION['action_error'][] = "Access Denied!";
    header("location: ".SITE_URL."admin/index.php"); //Yes? then redirect user to the login page
    exit;
}

$GLOBALS['admin_template']['page_title'] = "<i class='fa fa-gift'></i> Coupons"; //Set the title of the page on the admin interface
$GLOBALS['admin_template']['active_menu'] = "coupon"; //Set the appropriate menu item active


//get all intra-city routes
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



//get all coupon codes
$coupon_codes_data = [];

$query = sprintf('SELECT *,%1$stbl_coupon_codes.id AS cid,%1$stbl_routes.id AS r_id FROM %1$stbl_coupon_codes 
INNER JOIN %1$stbl_routes ON %1$stbl_routes.id = %1$stbl_coupon_codes.city
ORDER BY date_created DESC', DB_TBL_PREFIX);


if($result = mysqli_query($GLOBALS['DB'], $query)){
  
    if(mysqli_num_rows($result)){
        while($row = mysqli_fetch_assoc($result)){
            $coupon_codes_data[] = $row;
        }
    
     }
    mysqli_free_result($result);
}

//get all vehicles data
$ridesdata = [];
$query = sprintf('SELECT *  FROM %stbl_rides WHERE avail = 1', DB_TBL_PREFIX);
if($result = mysqli_query($GLOBALS['DB'], $query)){
  
    if(mysqli_num_rows($result)){
        while($row = mysqli_fetch_assoc($result)){
            $ridesdata[$row['id']] = $row;
        }    
     }
    mysqli_free_result($result);
}



if(isset($_GET['status'])){ //coupon activate / deactivate button clicked
    $cid = empty($_GET['cid']) ? 0 : (int) $_GET['cid'];
    $c_status = empty($_GET['status']) ? 0 : 1;

    //update coupone database record
    $query = sprintf('UPDATE %stbl_coupon_codes SET `status` = %d WHERE id=%d', 
        DB_TBL_PREFIX, 
        $c_status,
        $cid
    );

    

        if(! $result = mysqli_query($GLOBALS['DB'], $query)){
            //echo mysqli_error($GLOBALS['DB']);
            
            $_SESSION['action_error'][] = "An error has occured. Could not update coupon status. Ensure database connection is working";
        
            ob_start();
            include('../../drop-files/templates/admin/couponstpl.php'); 
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
            



            $pageContent = ob_get_clean();
            $GLOBALS['admin_template']['page_content'] = $pageContent;
            include "../../drop-files/templates/admin/admin-interface.php";
            exit;

        
        }

            //refresh coupon codes data
            $coupon_codes_data = [];

            $query = sprintf('SELECT *,%1$stbl_coupon_codes.id AS cid,%1$stbl_routes.id AS r_id FROM %1$stbl_coupon_codes 
            INNER JOIN %1$stbl_routes ON %1$stbl_routes.id = %1$stbl_coupon_codes.city
            ORDER BY date_created DESC', DB_TBL_PREFIX);


            if($result = mysqli_query($GLOBALS['DB'], $query)){
            
                if(mysqli_num_rows($result)){
                    while($row = mysqli_fetch_assoc($result)){
                        $coupon_codes_data[] = $row;
                    }
                
                }
                mysqli_free_result($result);
            }


    
            $_SESSION['action_success'][] = empty($c_status) ? "Coupon deactivated!" : "Coupon activated!";
            ob_start();
            include('../../drop-files/templates/admin/couponstpl.php'); 
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
            $pageContent = ob_get_clean();
            $GLOBALS['admin_template']['page_content'] = $pageContent;
            include "../../drop-files/templates/admin/admin-interface.php";
            exit;



}elseif(isset($_GET['action']) && $_GET['action'] == "del"){ //coupon activate / deactivate button clicked
    $cid = empty($_GET['cid']) ? 0 : (int) $_GET['cid'];
    
    //delete coupon database record
    $query = sprintf('DELETE FROM %stbl_coupon_codes WHERE id=%d', 
        DB_TBL_PREFIX, 
        $cid
    );

    

    if(! $result = mysqli_query($GLOBALS['DB'], $query)){
        //echo mysqli_error($GLOBALS['DB']);
        
        $_SESSION['action_error'][] = "An error has occured. Could not delete coupon. Ensure database connection is working";
    
        ob_start();
        include('../../drop-files/templates/admin/couponstpl.php'); 
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
        



        $pageContent = ob_get_clean();
        $GLOBALS['admin_template']['page_content'] = $pageContent;
        include "../../drop-files/templates/admin/admin-interface.php";
        exit;

    
    }

        //refresh coupon codes data
        $coupon_codes_data = [];

        $query = sprintf('SELECT *,%1$stbl_coupon_codes.id AS cid,%1$stbl_routes.id AS r_id FROM %1$stbl_coupon_codes 
        INNER JOIN %1$stbl_routes ON %1$stbl_routes.id = %1$stbl_coupon_codes.city
        ORDER BY date_created DESC', DB_TBL_PREFIX);


        if($result = mysqli_query($GLOBALS['DB'], $query)){
        
            if(mysqli_num_rows($result)){
                while($row = mysqli_fetch_assoc($result)){
                    $coupon_codes_data[] = $row;
                }
            
            }
            mysqli_free_result($result);
        }



        $_SESSION['action_success'][] = "Coupon deleted successfully!";
        ob_start();
        include('../../drop-files/templates/admin/couponstpl.php'); 
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
        $pageContent = ob_get_clean();
        $GLOBALS['admin_template']['page_content'] = $pageContent;
        include "../../drop-files/templates/admin/admin-interface.php";
        exit;


}





if(empty($_POST)){ //let's render the add-new-franchise page UI'
    
    ob_start();
    include('../../drop-files/templates/admin/couponstpl.php');
    $pageContent = ob_get_clean();
    $GLOBALS['admin_template']['page_content'] = $pageContent;
    include "../../drop-files/templates/admin/admin-interface.php";
    exit;


}




if(isset($_POST['add-coupon'])){


            /* var_dump($_POST);
            exit; */
            
            if(empty($_POST['coupon-code'])){

                $_SESSION['action_error'][]    = "Please enter a coupon code";
            }


            if(empty($_POST['coupon-title'])){

                $_SESSION['action_error'][]    = "Please enter a coupon title";
            }


            if(empty($_POST['city-list'])){

                $_SESSION['action_error'][]    = "Please select a city where coupon is valid";
            }

            if(!isValidDate($_POST['coupon-active-date'], $format= 'Y-m-d')){
                $_SESSION['action_error'][]    = "Active date not in the correct format";
            }

            if(!isValidDate($_POST['coupon-expiry-date'], $format= 'Y-m-d')){
                $_SESSION['action_error'][]    = "Expiry date not in the correct format";
            }

            
            $sel_vehicles = '';

            if($_POST['vehicle-list'] == 1){
                if(isset($_POST['coupon-v'])){
                    $vehicles = $_POST['coupon-v'];
                    if(is_array($vehicles)){
                        $sel_vehicles = implode(",",$vehicles);
                        if(count($vehicles) == 1){
                            $sel_vehicles .= ",0";
                        }
                    }
                }
            }


            $city = (int) $_POST['city-list'];

            $usage_limt = (int) $_POST['coupon-usage-limit'];

            $user_usage_limt = (int) $_POST['coupon-user-limit'];

            $discount_type = !empty($_POST['coupon-discount-type']) ? 1 : 0;
            $discount = (float) $_POST['coupon-discount'];




            //check if coupon code and city entry already exists

            $query = sprintf('SELECT * FROM %stbl_coupon_codes WHERE coupon_code = "%s" AND city = %d', DB_TBL_PREFIX, mysqli_real_escape_string($GLOBALS['DB'], $_POST['coupon-code']), $city); //Get required user information from DB


            if($result = mysqli_query($GLOBALS['DB'], $query)){
                if(mysqli_num_rows($result)){
                    $_SESSION['action_error'][]    = "Coupon code already available for selected city";
                }
                
            }
            else{ //No record matching the USER ID was found in DB. Show view to notify user

                $_SESSION['action_error'][]    = "Database error!";
            }


            if(!empty($_SESSION['action_error'])){
            
                ob_start();
                include('../../drop-files/templates/admin/couponstpl.php'); 
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
                



                $pageContent = ob_get_clean();
                $GLOBALS['admin_template']['page_content'] = $pageContent;
                include "../../drop-files/templates/admin/admin-interface.php";
                exit;



            }

            //Store coupon data to database
            $query = sprintf('INSERT INTO %stbl_coupon_codes(coupon_title,vehicles,coupon_code,city,discount_type,discount_value,limit_count,user_limit_count,active_date,`expiry_date`,date_created) VALUES'.
            '("%s","%s","%s","%d","%d","%s","%d","%d","%s","%s","%s")', 
                DB_TBL_PREFIX, 
                mysqli_real_escape_string($GLOBALS['DB'], $_POST['coupon-title']),
                $sel_vehicles,
                mysqli_real_escape_string($GLOBALS['DB'], $_POST['coupon-code']),
                $city,
                $discount_type,
                $discount,
                $usage_limt,
                $user_usage_limt,
                mysqli_real_escape_string($GLOBALS['DB'], $_POST['coupon-active-date']),
                mysqli_real_escape_string($GLOBALS['DB'], $_POST['coupon-expiry-date']),
                gmdate('Y-m-d H:i:s', time())

            );


                if(! $result = mysqli_query($GLOBALS['DB'], $query)){
                    //echo mysqli_error($GLOBALS['DB']);
                    $err = mysqli_error($GLOBALS['DB']);
                    $_SESSION['action_error'][] = "{$err} An error has occured. Could not save new coupon code data to database. Ensure database connection is working";
                
                    ob_start();
                    include('../../drop-files/templates/admin/couponstpl.php'); 
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
                    



                    $pageContent = ob_get_clean();
                    $GLOBALS['admin_template']['page_content'] = $pageContent;
                    include "../../drop-files/templates/admin/admin-interface.php";
                    exit;

                
                }
                else{
                        $id = mysqli_insert_id ( $GLOBALS['DB'] );
                        
                    }

                    //refresh coupon codes data
                    $coupon_codes_data = [];

                    $query = sprintf('SELECT *,%1$stbl_coupon_codes.id AS cid,%1$stbl_routes.id AS r_id FROM %1$stbl_coupon_codes 
                    INNER JOIN %1$stbl_routes ON %1$stbl_routes.id = %1$stbl_coupon_codes.city
                    ORDER BY date_created DESC', DB_TBL_PREFIX);


                    if($result = mysqli_query($GLOBALS['DB'], $query)){
                    
                        if(mysqli_num_rows($result)){
                            while($row = mysqli_fetch_assoc($result)){
                                $coupon_codes_data[] = $row;
                            }
                        
                        }
                        mysqli_free_result($result);
                    }




            
                    $_SESSION['action_success'][] = "Coupon code was added successfully.";
                    ob_start();
                    include('../../drop-files/templates/admin/couponstpl.php'); 
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
                    $pageContent = ob_get_clean();
                    $GLOBALS['admin_template']['page_content'] = $pageContent;
                    include "../../drop-files/templates/admin/admin-interface.php";
                    exit;

}elseif(isset($_POST['edit-coupon'])){

     

    if(empty($_POST['coupon-code'])){

        $_SESSION['action_error'][]    = "Please enter a coupon code";
    }

    if(empty($_POST['coupon-title'])){

        $_SESSION['action_error'][]    = "Please enter a coupon title";
    }


    if(empty($_POST['city-list'])){

        $_SESSION['action_error'][]    = "Please select a city where coupon is valid";
    }

    if(!isValidDate($_POST['coupon-active-date'], $format= 'Y-m-d')){
        $_SESSION['action_error'][]    = "Active date not in the correct format";
    }

    if(!isValidDate($_POST['coupon-expiry-date'], $format= 'Y-m-d')){
        $_SESSION['action_error'][]    = "Expiry date not in the correct format";
    }

    $coupon_id = (int) $_POST['coupon-id'];

    $city = (int) $_POST['city-list'];

    $usage_limt = (int) $_POST['coupon-usage-limit'];

    $user_usage_limt = (int) $_POST['coupon-user-limit'];

    $discount_type = !empty($_POST['coupon-discount-type']) ? 1 : 0;
    $discount = (float) $_POST['coupon-discount'];


    $sel_vehicles = '';

    if($_POST['vehicle-list'] == 1){
        if(isset($_POST['coupon-v'])){
            $vehicles = $_POST['coupon-v'];
            if(is_array($vehicles)){
                $sel_vehicles = implode(",",$vehicles);
                if(count($vehicles) == 1){
                    $sel_vehicles .= ",0";
                }
            }
        }
    }
   


    if(!empty($_SESSION['action_error'])){
    
        ob_start();
        include('../../drop-files/templates/admin/couponstpl.php'); 
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
        



        $pageContent = ob_get_clean();
        $GLOBALS['admin_template']['page_content'] = $pageContent;
        include "../../drop-files/templates/admin/admin-interface.php";
        exit;



    }

    //update coupon record on database
    $query = sprintf('UPDATE %stbl_coupon_codes SET coupon_title = "%s",vehicles = "%s",coupon_code="%s",city=%d,discount_type=%d,discount_value="%s",limit_count=%d,user_limit_count=%d,active_date="%s",`expiry_date`="%s" WHERE id=%d', 
        DB_TBL_PREFIX, 
        mysqli_real_escape_string($GLOBALS['DB'], $_POST['coupon-title']),
        $sel_vehicles,
        mysqli_real_escape_string($GLOBALS['DB'], $_POST['coupon-code']),
        $city,
        $discount_type,
        $discount,
        $usage_limt,
        $user_usage_limt,
        mysqli_real_escape_string($GLOBALS['DB'], $_POST['coupon-active-date']),
        mysqli_real_escape_string($GLOBALS['DB'], $_POST['coupon-expiry-date']),
        $coupon_id

    );

    

        if(! $result = mysqli_query($GLOBALS['DB'], $query)){
            //echo mysqli_error($GLOBALS['DB']);
            
            $_SESSION['action_error'][] = "An error has occured. Could not update coupon code data on database. Ensure database connection is working";
        
            ob_start();
            include('../../drop-files/templates/admin/couponstpl.php'); 
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
            



            $pageContent = ob_get_clean();
            $GLOBALS['admin_template']['page_content'] = $pageContent;
            include "../../drop-files/templates/admin/admin-interface.php";
            exit;

        
        }

            //refresh coupon codes data
            $coupon_codes_data = [];

            $query = sprintf('SELECT *,%1$stbl_coupon_codes.id AS cid,%1$stbl_routes.id AS r_id FROM %1$stbl_coupon_codes 
            INNER JOIN %1$stbl_routes ON %1$stbl_routes.id = %1$stbl_coupon_codes.city
            ORDER BY date_created DESC', DB_TBL_PREFIX);


            if($result = mysqli_query($GLOBALS['DB'], $query)){
            
                if(mysqli_num_rows($result)){
                    while($row = mysqli_fetch_assoc($result)){
                        $coupon_codes_data[] = $row;
                    }
                
                }
                mysqli_free_result($result);
            }




    
            $_SESSION['action_success'][] = "Coupon code was updated successfully.";
            ob_start();
            include('../../drop-files/templates/admin/couponstpl.php'); 
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
            $pageContent = ob_get_clean();
            $GLOBALS['admin_template']['page_content'] = $pageContent;
            include "../../drop-files/templates/admin/admin-interface.php";
            exit;

}


?>