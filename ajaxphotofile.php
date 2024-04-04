<?php
include("../drop-files/lib/common.php");
$dummyfile = "img/user-photo.jpg";

//This script Loads a file from local server if the user is logedin


if(!empty($_GET['file'])){

        //we are using a filename from the GET superglobal; we must sanitize this data to avoid path traversal attack
        $image_path = realpath(USER_PHOTO_PATH) .  "/". $_GET['file'][0] . "/" . $_GET['file'][1] . "/" . $_GET['file'][2] . "/";

        $file = $image_path . $_GET['file'];

        if(strpos($file,realpath(USER_PHOTO_PATH)) !== 0){
                //posibble path traversal attack; let's be kind and just serve up the dummy photo ;)'
                
                header('Pragma: public');
                header('Cache-Control: max-age=86400,public');
                header('Expires: '. gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));
                header('Content-Type: image');
                readfile($dummyfile);
                exit;

        }  

        
        
        if(!file_exists($file))$file = $dummyfile;

        
        header('Pragma: public');
        header('Cache-Control: max-age=86400,public');
        header('Expires: '. gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));
        header('Content-Type: image');        
        readfile($file);
        exit;

}
else{

        header('Pragma: public');
        header('Cache-Control: max-age=86400,public');
        header('Expires: '. gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));
        header('Content-Type: image');
        readfile($dummyfile);
        exit;
}

?> 