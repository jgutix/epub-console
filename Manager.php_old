<?php
/**
 * Created by JetBrains PhpStorm.
 * User: jgutix
 * Date: 02-04-13
 * Time: 03:15 PM
 */
class Manager
{
    public function getFromObjavi($book)
    {
        require 'getepub.php';
        $result = array('ok'=>false);
//        if(isset($_POST['book'])){
        if(isset($book)){
            $link = GetEpub::getURL($book, Config::get('server'));
            if(!empty($link)){
                $localLink = GetEpub::getFileEpub($book, $link);
                if(Config::get('automatic-apple-fix')){
                    $epub = new EPUB($localLink);
                    $epub->addAppleOptions();
                    $epub->close();
                }
                return array('ok'=>1, 'link'=>$localLink);
            }else{
                $result['error']='Objavi returns nothing';
            }
        }else{
            $result['error']='Not enough info';
        }
        return $result;
    }

    public function fixLinks($book){
        //no epub extension
        if ($book) {
            $local = 'tmp/'.$book.'.epub';
            $epub = new EPUB($local);
            $epub->fixLocalFile();
            $result = array('ok' => 1, 'epub' => $local);
            if(!empty($epub->noFileLinks)){
                $result['orphanLinks']=$epub->noFileLinks;
                $result['xhtmlFiles'] = $epub->xhtmlFiles;
                $result['book'] = $book;
            }
            return $result;

        }else{
            return array('ok' => false, 'error' => 'Missing info');
        }
    }

    public function fixOrphans(){
        $epub = new EPUB('tmp/'.$_POST['book'].'.epub');
        $epub->fixOrphanLinks($_POST);
        return array('ok'=>1);
    }
    
    public function injectCSS(){
        if(isset($_POST['book']) && isset($_POST['css'])){
            $epub = new EPUB('tmp/'.$_POST['book'].'.epub');
            $epub->uploadCSS($_POST['css']);
            return array('ok'=>1);
        }else{
            return array('ok'=>false, 'error'=>'Some parameters are missing');
        }
    }

    public function injectCover(){
        if(isset($_POST['book']) && isset($_POST['cover'])){
            $coverJpg = self::decodeImg($_POST['cover']);
            $epub = new EPUB('tmp/'.$_POST['book'].'.epub');
            if($coverJpg===false){
                return array('error'=>'Error en la codificacion');
            }
            $epub->setCover($coverJpg);
            return array('ok'=>1);

        }else{
            return array('ok'=>false, 'error'=>'Missing data');
        }
    }

    private static function decodeImg($base64Content){
        if (!preg_match('/data:([^;]*);base64,(.*)/', $base64Content, $matches)) {
            return false;
        }

        return base64_decode(chunk_split($matches[2]));
    }

    public function addMetadata()
    {
        $book='tmp/'.$_POST['book'].'.epub';
        $zip1 = new ZipArchive;
            //Opens a Zip archive
        $epub = $zip1->open($book);
        if( $epub !== true ){
            die("cannot open for writing.".$epub);
        }
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

        $zip1->addFromString($internalFile, $xml->asXML());
        $zip1->close();

        echo json_encode(array('ok'=>1));
    }

    function test(){
        echo 'Test';
    }
}
