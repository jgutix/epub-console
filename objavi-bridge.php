<?php
/**
 * Created by JetBrains PhpStorm.
 * User: jgutix
 * Date: 01-21-13
 * Time: 12:20 PM
 */
require 'getepub.php';
$result = array('ok'=>false);
if(isset($_POST['book'])){
    include 'cfg.php';
    $link = GetEpub::getURL($_POST['book'], $config['server']);
    if(!empty($link)){
        $localLink = GetEpub::getFileEpub($_POST['book'], $link);
        $zip1 = new ZipArchive;
            //Opens a Zip archive
        $epub = $zip1->open($localLink);
        $zip1->addFromString('META-INF/com.apple.ibooks.display-options.xml',
            '<?xml version="1.0" encoding="UTF-8"?>
            <display_options>
            <platform name="*">
            <option name="specified-fonts">true</option>
            </platform>
            </display_options>');
        echo json_encode(array('ok'=>1, 'link'=>$localLink));
        exit;
    }else{
        $result['error']='Objavi returns nothing';
    }
}else{
    $result['error']='Not enough info';
}

echo $result;