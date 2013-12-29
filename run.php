<?php
$tsStart = microtime(true);
ini_set('memory_limit', '256M');
//ZF1 gdata library has a strict error. Declaration of Zend_Gdata::import() should be compatible with Zend_Gdata_App::import(arg1, arg2, arg3)
error_reporting(E_ALL ^ E_STRICT);
//set_time_limit(500);

//composer autoloader
require 'vendor/autoload.php';
//load local $config with youtube credentials.
require_once('./config.php');

//load export tool.
require_once('./Atv/ExportChannel.php');
$export = new Atv_ExportChannel();
$arrVideos = $export->exportDefaultChannel($config);

//Save data to file.
if(!is_writable('./videos')){
    mkdir('./videos');
    echo "Created videos directory.\n";
}
foreach($arrVideos as $video){
    $id = $video['id'];
    $file = json_encode($video,JSON_PRETTY_PRINT);
    file_put_contents("./videos/$id.json",$file);
}

$tsEnd = microtime(true);
echo 'Done. channel data written to ./videos folder.' .
      "\n Completed in " . ($tsEnd - $tsStart) . ' seconds. Mem: ' . memory_get_usage(true) . "\n";
