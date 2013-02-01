<?php
/**
 * Created by JetBrains PhpStorm.
 * User: jgutix
 * Date: 01-30-13
 * Time: 11:02 AM
 */
require 'simple_html_dom.php';
$zip1 = new ZipArchive;
$_POST['book']='safari-documentation';
    //Opens a Zip archive
$epub = $zip1->open('tmp/'.$_POST['book'].'.epub');
$internalFile = 'content.opf';
$opf = $zip1->getFromName($internalFile);

$xml = new SimpleXMLElement($opf);
//Use that namespace
$namespaces = $xml->getNameSpaces(true);
//Now we don't have the URL hard-coded
$dc = $xml->metadata->children($namespaces['dc']);

if(!empty($_POST['title'])){
    $dc->title = $_POST['title'];
}
if(!empty($_POST['title'])){
    $dc->creator=$_POST['author'];
}

if(!empty($_POST['title'])){
    $dc->publisher= $_POST['publisher'];
}

if(!empty($_POST['title'])){
    $dc->date[2]= $_POST['date'];
}

if(!empty($_POST['title'])){
    $dc->rights= $_POST['rights'];
}

//foreach($publisher as $item)
//{
//    $item->appendChild($dom->createTextNode('Juan Gutiérrez'));
//}
//
$zip1->addFromString($internalFile, $xml->asXML());
$zip1->close();

echo json_encode(array('ok'=>1));