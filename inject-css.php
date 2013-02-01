<?php
/**
 * Created by JetBrains PhpStorm.
 * User: jgutix
 * Date: 01-22-13
 * Time: 10:52 AM
 */
if(isset($_POST['book']) && isset($_POST['css'])){
    require 'simple_html_dom.php';
    $file = 'tmp/'.$_POST['book'].'.epub';
    $content = $_POST['css'];
    $zip1 = new ZipArchive;
    //Opens a Zip archive
    if ($epub = $zip1->open($file)) {
        $cssFile = 'objavi.css';
        if (!$zip1->addFromString($cssFile, $content)) {
            echo json_encode(array('ok'=>false));
            die();
        }
        $internalFile = 'content.opf';
        $opf = $zip1->getFromName($internalFile);
        $xml = new SimpleXMLElement($opf);
        $xml->registerXPathNamespace('opf', 'http://www.idpf.org/2007/opf');
        $result = $xml->xpath('//opf:manifest/opf:item[@media-type="text/css"]');
        if(empty($result)){
            $cssItem = $xml->manifest->addChild('item');
            $cssItem->addAttribute('href', $cssFile);
            $cssItem->addAttribute('id', 'css');
            $cssItem->addAttribute('media-type', 'text/css');
        }

        $zip = zip_open($file);
        $xhtmlFiles = array();
        while ($zip_entry = zip_read($zip)) {
            $entryName = zip_entry_name($zip_entry);
            if (!is_dir($entryName)) {
                $path_parts = pathinfo($entryName);
                if (isset ($path_parts['extension']) && strtolower(trim($path_parts['extension'])) == 'xhtml') {
                    $html = str_get_html($zip1->getFromName($entryName));
                    if(!$html->find('link', 0)){
                        $element = $html->find('head', 0);
                        $element->innertext=$element->innertext.'<link href="'.$cssFile.'" type="text/css" rel="stylesheet"/>';
                        $zip1->addFromString($entryName, $html);
                    }

                }
            }
        }
        zip_close($zip);
        $zip1->close();
        echo json_encode(array('ok'=>1));
    } else {
        echo json_encode(array('ok'=>false));
    }
}else{
    echo json_encode(array('ok'=>false, 'error'=>'Some parameters are missing'));
}
