<?php
session_start();
include("../drop-files/lib/common.php");

header("location: ".SITE_URL."login.php"); //uncomment to bypass homepage and redirect user to the login page
exit;

include "../drop-files/templates/headertpl.php";
include "../drop-files/templates/homepagetpl.php";
include "../drop-files/templates/footertpl.php";
exit;

?>
