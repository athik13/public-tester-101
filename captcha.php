<?php
session_start();
include("../drop-files/lib/common.php");


$image = imagecreatetruecolor(200, 50);
 
imageantialias($image, true);
 
$colors = [];
 
$red = rand(125, 175);
$green = rand(125, 175);
$blue = rand(125, 175);
 
for($i = 0; $i < 5; $i++) {
  $colors[] = imagecolorallocate($image, $red - 20*$i, $green - 20*$i, $blue - 20*$i);
}
 
imagefill($image, 0, 0, $colors[0]);
 
for($i = 0; $i < 10; $i++) {
  imagesetthickness($image, rand(2, 10));
  $rect_color = $colors[rand(1, 4)];
  //imagerectangle($image, rand(-10, 190), rand(-10, 10), rand(-10, 190), rand(40, 60), $rect_color);
  $width_height = rand(20, 50);
  imageellipse($image,rand(-10, 190),rand(-10, 50),$width_height,$width_height,$rect_color);
}


$black = imagecolorallocate($image, 0, 0, 0);
$white = imagecolorallocate($image, 255, 255, 255);
$textcolors = [$black, $white];
$font_path = realpath("fonts/Arial-Bold.ttf"); 
$fonts = [$font_path];
 
$string_length = 6;
$captcha_string = crypto_string( 'hexdec', $string_length );
 
for($i = 0; $i < $string_length; $i++) {
  $letter_space = 170/$string_length;
  $initial = 15;
   
  imagettftext($image, 20, rand(-15, 15), $initial + $i*$letter_space, rand(20, 40), $textcolors[rand(0, 1)], $fonts[0], $captcha_string[$i]);
}

$_SESSION['captcha'] = $captcha_string;


header('Content-type: image/png');
imagepng($image);
imagedestroy($image);

?>


