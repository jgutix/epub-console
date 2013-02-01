<?php
/**
 * Created by JetBrains PhpStorm.
 * User: jgutix
 * Date: 01-18-13
 * Time: 04:12 PM
 */

error_reporting(E_ALL);
require 'simple_html_dom.php';
require 'getepub.php';
class FixLinks
{
    private $epub, $localFile, $shortedChapters;
    public $noFileLinks=array(), $xhtmlFiles;

    function fixLocalFile($file)
    {
        $this->localFile = $file;
        $this->xhtmlFiles = $this->getXhtmlFiles();
        $this->shortedChapters = array();
        foreach ($this->xhtmlFiles as $item) {
            $chunks = explode('_', $item);
            $chunks2 = explode('.', $chunks[1]);
            $this->shortedChapters[$chunks2[0]] = $item;
        }
        $zip1 = new ZipArchive;
            //Opens a Zip archive
        $epub = $zip1->open($this->localFile);
        foreach ($this->xhtmlFiles as $item) {
            $this->backToEpub($item, $this->fixContent($zip1->getFromName($item), $item));
        }
        $zip1->close();
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

    function fixContent($content, $file)
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
                $chapter = '';
                $chunks2 = explode('#', isset($chunks[1])?$chunks[1]:$chunks[0]);
                $chapter = $chunks2[0];

                if (isset($this->shortedChapters[$chapter])) {
                    $element->href = $this->shortedChapters[$chapter] . (isset($chunks2[1]) ? '#' . $chunks2[1] : '');
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

    function backToEpub($file, $content)
    {
        $zip1 = new ZipArchive;
        //Opens a Zip archive
        if ($this->epub = $zip1->open($this->localFile)) {
            if (!$zip1->addFromString($file, $content)) {
                echo 'error';
            }
            $zip1->close();
        } else {
            echo 'Not found!';
        }

    }
}

//no epub extension
if (isset($_GET['book']) && isset($_GET['local'])) {
    $fix = new FixLinks();
    $fix->fixLocalFile($_GET['local']);
    $result = array('ok' => 1, 'epub' => $_GET['local']);
    if(!empty($fix->noFileLinks)){
        $result['orphanLinks']=$fix->noFileLinks;
        $result['xhtmlFiles'] = $fix->xhtmlFiles;
        $result['book'] = $_GET['book'];
    }
    echo json_encode($result);

}else{
    echo json_encode(array('ok' => false, 'error' => 'Missing info'));
}
