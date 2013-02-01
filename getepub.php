<?php
/**
 * Created by JetBrains PhpStorm.
 * User: jgutix
 * Date: 01-21-13
 * Time: 08:02 AM
*/
class GetEpub{

    public static function getURL($book, $server)
    {
        $objavi = "http://objavi.booktype.pro/?book=$book&server=$server&mode=epub&destination=nowhere";
        return file_get_contents($objavi);
    }

    public static function getFileEpub($book, $url){
        ini_set('max_execution_time', 180);
        $localLink = 'tmp/'.$book.'.epub';
        if(copy($url, $localLink)){
            return $localLink;
        }else{
            echo 'error';
            return false;
        }
    }

}