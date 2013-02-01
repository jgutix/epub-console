<?php
/**
 * Created by JetBrains PhpStorm.
 * User: jgutix
 * Date: 01-22-13
 * Time: 10:52 AM
 */
if(isset($_POST['book']) && isset($_POST['cover'])){
    $file = 'tmp/'.$_POST['book'].'.epub';
    $content =  $_POST['cover'];

    $zip1 = new ZipArchive;
    //Opens a Zip archive
    if ($epub = $zip1->open($file)) {

        if (!preg_match('/data:([^;]*);base64,(.*)/', $content, $matches)) {
            die("error");
        }

        $base64 = base64_decode(chunk_split($matches[2]));
        if(!$base64){
            return json_encode(array('error'=>'Error en la codificacion'));
        }
        $coverJpg = 'cover.jpeg';
        if (!$zip1->addFromString($coverJpg, $base64)) {
            echo json_encode(array('ok'=>false));
            die();
        }
        $htmlCoverFile = 'cover.xhtml';
        $htmlCoverContent = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">'.
            '<html xmlns="http://www.w3.org/1999/xhtml">'.
            '<head>'.
            '<title>Cover</title>'.
            '<style type="text/css"> img { max-width: 100%; } </style>'.
            '</head>'.
            '<body>'.
            '<div id="cover-image">'.
            '<img src="'.$coverJpg.'" alt="cover"/>'.
            '</div>'.
            '</body>'.
            '</html>';
        $zip1->addFromString($htmlCoverFile, $htmlCoverContent);

        $internalFile = 'content.opf';
        $opf = $zip1->getFromName($internalFile);
        $xml = new SimpleXMLElement($opf);

        $meta = $xml->metadata->addChild('meta');
        $meta->addAttribute('name', 'cover');
        $meta->addAttribute('content', 'cover-image');

        $coverHtmlItem = $xml->manifest->addChild('item');
        $coverHtmlItem->addAttribute('href', $htmlCoverFile);
        $coverHtmlItem->addAttribute('id', 'cover');
        $coverHtmlItem->addAttribute('media-type', 'application/xhtml+xml');

        $coverJpgItem = $xml->manifest->addChild('item');
        $coverJpgItem->addAttribute('href', $coverJpg);
        $coverJpgItem->addAttribute('id', 'cover-image');
        $coverJpgItem->addAttribute('media-type', 'image/jpeg');

        $itemref1 = $xml->spine->addChild('itemref');
        $itemref1->addAttribute('idref', 'cover');
        $itemref1->addAttribute('linear', 'no');

        if(!isset($xml->guide)){
            $xml->addChild('guide');
        }

        $reference = $xml->guide->addChild('reference');
        $reference->addAttribute('href', $htmlCoverFile);
        $reference->addAttribute('type','cover');
        $reference->addAttribute('title','Cover');

        $zip1->addFromString($internalFile, $xml->asXML());
        $zip1->close();

        echo json_encode(array('ok'=>1));
    } else {
        echo json_encode(array('ok'=>false));
    }
}else{
    echo json_encode(array('ok'=>false, 'error'=>'Missing data'));
}