<?php
/**
 * Created by JetBrains PhpStorm.
 * User: jgutix
 * Date: 01-24-13
 * Time: 08:18 PM
 */
require 'simple_html_dom.php';
$zip1 = new ZipArchive;
    //Opens a Zip archive
$epub = $zip1->open('tmp/'.$_POST['book'].'.epub');
$fullHTML = '';
foreach($_POST as $key=>$item){
    if($key=='book'){
        continue;
    }
    $internalFile = str_replace('&','.',$key);
    $xhtml = $zip1->getFromName($internalFile);
    $dom = str_get_html($xhtml);
    foreach($item as $class=>$newLink){
        $element = $dom->find('a.'.$class, 0);
        $element->href=$newLink;
    }
    $zip1->addFromString($internalFile, $dom->innertext);
}
echo json_encode(array('ok'=>1));