<?php
/* load php files within the directory specified */
function load_files($dir)
{
    $result = "";
    foreach (scandir($dir) as $filename) {
        $result = "";
        $path = $dir . '/' . $filename;
        if (is_dir($path)){
            $segments = explode("/",$path);
            if ( end($segments) == "." or end($segments) == "..") {
                $result = "dir invalid";
            }
            else {
                $result = "dir";
                load_files($path);
            }
        }
        else {
            $fileparts = pathinfo($path);
            if ($fileparts['extension'] == "php"){
                $result = "file valid";
                $relative_path = str_replace(dirname(__FILE__)."/","",$path);
                require $relative_path;
            }
            else $result = "file invalid";
        }
        $result = "$result: $path  \r";
    }
    return $result;
}
$dirname = dirname(__FILE__);
$dirname .= "/src";

load_files($dirname);
