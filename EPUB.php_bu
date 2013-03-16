<?php
/**
 * Created by JetBrains PhpStorm.
 * User: jgutix
 * Date: 02-04-13
 * Time: 05:18 PM
 */
class EPUB
{
    private $epub, $localFile;
    public $noFileLinks=array(), $xhtmlFiles;
    private static $shortedChapters;

    public function __construct($file)
    {
        $this->zip = new ZipArchive;
        //Opens a Zip archive
        if($epub = $this->zip->open($file)){
            $this->localFile = $file;
        }
    }

    public function close(){
        $this->zip->close();
    }

    public function addAppleOptions(){
        $this->zip->addFromString('META-INF/com.apple.ibooks.display-options.xml',
            '<?xml version="1.0" encoding="UTF-8"?>
            <display_options>
            <platform name="*">
            <option name="specified-fonts">true</option>
            </platform>
            </display_options>');
    }

    function fixLocalFile()
    {
//        $this->localFile = $file;
        $this->xhtmlFiles = $this->getXhtmlFiles();
        $this->setShortedChapters();
        foreach ($this->xhtmlFiles as $item) {
            $this->backToEpub($item, $this->fixContent($this->zip->getFromName($item), $item));
        }
        $this->zip->close();
    }

    private function setShortedChapters()
    {
        if(!isset(self::$shortedChapters)){
            foreach ($this->xhtmlFiles as $item) {
                $chunks = explode('_', $item);
                $chunks2 = explode('.', $chunks[1]);
                self::$shortedChapters[$chunks2[0]] = $item;
            }
        }


    }

    function getXhtmlFiles()
    {
        if(!file_exists($this->localFile)){
            echo 'File not found';
            return false;
        }
        $zip = zip_open($this->localFile);
        $xhtmlFiles = array();

        if ($zip!=false) {
            while ($zip_entry = zip_read($zip)) {
                $entryName = zip_entry_name($zip_entry);
                if (!is_dir($entryName)) {
                    $path_parts = pathinfo($entryName);
//                    $ext = strtolower(trim(isset ($path_parts['extension']) ? $path_parts['extension'] : ''));
                    if (isset ($path_parts['extension']) && strtolower(trim($path_parts['extension'])) == 'xhtml') {
                        $xhtmlFiles[] = $entryName;
                    }
                }
            }
        }
        zip_close($zip);
        return $xhtmlFiles;
    }

    private function fixContent($content, $file)
    {
        // Create DOM from string
        $html = str_get_html($content);
        foreach ($html->find('a') as $element) {
            $chunks = $chunks2 = null;
            //don't start with http|ftp|https|mailto don't end with xhtml neither
            if (!empty($element->href) && !preg_match('/[http|ftp|https|mailto]:/', $element->href)
                && !preg_match('/\.xhtml/', $element->href)
            ) {
                $chunks = explode('/', $element->href);
                $chunks2 = explode('#', isset($chunks[1])?$chunks[1]:$chunks[0]);
                $chapter = $chunks2[0];

                if (isset(self::$shortedChapters[$chapter])) {
                    $element->href = self::$shortedChapters[$chapter] . (isset($chunks2[1]) ? '#' . $chunks2[1] : '');
                } else {
                    $id = count($this->noFileLinks);
                    $class = 'nofile'.$id;
                    $element->class=$class;
                    $item = array('file'=>$file, 'class'=>$class,
                        'text'=>$element->plaintext, 'href'=>$element->href);
                    $this->noFileLinks[] = $item;
                }

            }
        }

        return $html;

    }

    private function backToEpub($file, $content)
    {
        $this->zip = new ZipArchive;
        //Opens a Zip archive
        if ($this->epub = $this->zip->open($this->localFile)) {
            if (!$this->zip->addFromString($file, $content)) {
                echo 'error';
            }
            $this->zip->close();
        } else {
            echo 'Not found!';
        }

    }

    public function fixOrphanLinks($data)
    {
        foreach($data as $key=>$item){
            if($key=='book'){
                continue;
            }
            $internalFile = str_replace('&','.',$key);
            $xhtml = $this->zip->getFromName($internalFile);
            $dom = str_get_html($xhtml);
            foreach($item as $class=>$newLink){
                $element = $dom->find('a.'.$class, 0);
                $element->href=$newLink;
            }
            $this->zip->addFromString($internalFile, $dom->innertext);
        }
        return array('ok'=>1);
    }
    
    public function uploadCSS($content){
        $cssFile = 'objavi.css';
        if (!$this->zip->addFromString($cssFile, $content)) {
            return array('ok'=>false);
        }
        $this->fixCSSReference($cssFile);
        $this->fixCSSInnerFilesReference($cssFile);
        $this->zip->close();
        return array('ok'=>1);
    }

    private function fixCSSInnerFilesReference($cssFile)
    {
        $zip = zip_open($this->localFile);
        while ($zip_entry = zip_read($zip)) {
            $entryName = zip_entry_name($zip_entry);
            if (!is_dir($entryName)) {
                $path_parts = pathinfo($entryName);
                if (isset ($path_parts['extension']) && strtolower(trim($path_parts['extension'])) == 'xhtml') {
                    $html = str_get_html($this->zip->getFromName($entryName));
                    if (!$html->find('link', 0)) {
                        $element = $html->find('head', 0);
                        $element->innertext = $element->innertext . '<link href="' . $cssFile . '" type="text/css" rel="stylesheet"/>';
                        $this->zip->addFromString($entryName, $html);
                    }

                }
            }
        }
        zip_close($zip);
    }

    private function fixCSSReference($cssFile){
        $internalFile = 'content.opf';
        $opf = $this->zip->getFromName($internalFile);
        $xml = new SimpleXMLElement($opf);
        $xml->registerXPathNamespace('opf', 'http://www.idpf.org/2007/opf');
        $result = $xml->xpath('//opf:manifest/opf:item[@media-type="text/css"]');
        if(empty($result)){
            $cssItem = $xml->manifest->addChild('item');
            $cssItem->addAttribute('href', $cssFile);
            $cssItem->addAttribute('id', 'css');
            $cssItem->addAttribute('media-type', 'text/css');
        }
    }

    private function createCoverHtml($file)
    {
        $htmlCoverFile = 'cover.xhtml';
        $content = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">'.
            '<html xmlns="http://www.w3.org/1999/xhtml">'.
            '<head>'.
            '<title>Cover</title>'.
            '<style type="text/css"> img { max-width: 100%; } </style>'.
            '</head>'.
            '<body>'.
            '<div id="cover-image">'.
            '<img src="'.$file.'" alt="cover"/>'.
            '</div>'.
            '</body>'.
            '</html>';
        if($this->zip->addFromString($htmlCoverFile, $content)){
            return $htmlCoverFile;
        }
        return false;
    }

    public function setCover($cover){
        $coverJpg = 'cover.jpeg';
        if (!$this->zip->addFromString($coverJpg, $cover)) {
            return false;
        }
        $html = $this->createCoverHtml($coverJpg);

        $this->setCoverReference($html, $coverJpg);
        return true;
    }

    private function setCoverReference($html, $jpg)
    {
        $internalFile = 'content.opf';
        $opf = $this->zip->getFromName($internalFile);
        $xml = new SimpleXMLElement($opf);

        $meta = $xml->metadata->addChild('meta');
        $meta->addAttribute('name', 'cover');
        $meta->addAttribute('content', 'cover-image');

        $coverHtmlItem = $xml->manifest->addChild('item');
        $coverHtmlItem->addAttribute('href', $html);
        $coverHtmlItem->addAttribute('id', 'cover');
        $coverHtmlItem->addAttribute('media-type', 'application/xhtml+xml');

        $coverJpgItem = $xml->manifest->addChild('item');
        $coverJpgItem->addAttribute('href', $jpg);
        $coverJpgItem->addAttribute('id', 'cover-image');
        $coverJpgItem->addAttribute('media-type', 'image/jpeg');

        $itemref1 = $xml->spine->addChild('itemref');
        $itemref1->addAttribute('idref', 'cover');
        $itemref1->addAttribute('linear', 'no');

        if (!isset($xml->guide)) {
            $xml->addChild('guide');
        }

        $reference = $xml->guide->addChild('reference');
        $reference->addAttribute('href', $html);
        $reference->addAttribute('type', 'cover');
        $reference->addAttribute('title', 'Cover');

        $this->zip->addFromString($internalFile, $xml->asXML());
        $this->zip->close();
    }

}