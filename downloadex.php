<?php
session_start();
include("../drop-files/lib/common.php");


//This script Loads a file from local server if the user is logedin


if(!(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == 1)){ //if user is not logged in run this code
  
       //$file = $_GET['file'];
         
        
        exit;
  

}

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == 1){ //if user is logged in run this code

        if(!empty($_GET['file'])){

                //we are using a filename from the GET superglobal; we must sanitize this data to avoid path traversal attack
                $file_path = realpath(FILES_FOLDER) .  "/uploads/exports" . "/";

                $file = $file_path . $_GET['file'];

                if(strpos($file,realpath(FILES_FOLDER)) !== 0){
                        //posibble path traversal attack; let's be kind and just serve up the dummy photo ;)'
                        
                        
                        exit;

                }  

                
                
                if(!file_exists($file)){
                    
                    exit;
                }

                
                header('Pragma: public');
                header('Cache-Control: max-age=86400,public');
                header('Expires: '. gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));
                   
                header("Content-Type: application/force-download");
                header("Content-Type: application/octet-stream");
                header("Content-Type: application/download");

                // disposition / encoding on response body
                header("Content-Disposition: attachment;filename={$_GET['file']}");
                header("Content-Transfer-Encoding: binary");     
                readfile($file);
                exit;

        }

}

?> 