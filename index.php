<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Juan
 * Date: 10-08-12
 * Time: 02:37 PM
 */

require 'Config.php';
$URI = explode('/',trim(str_replace(Config::get('folder'), '', $_SERVER['REQUEST_URI']),'/'));

$controller = ucwords($URI[0]);
$method = $URI[1];
$params = (isset($_GET)&&!empty($_GET)&&count($_GET)>1?reset($_GET):(isset($URI[2])?$URI[2]:''));

require 'simple_html_dom.php';
require 'EPUB.php';
//security
require_once preg_replace('/\W/si', '', $controller).'.php';

//$object = new $controller();
//$result = call_user_func( $controller.'::'.$method, $params ); // (As of PHP 5.2.3)
$result = $controller::$method($params); //the right way

//All requests are returned as json
if(!empty($result)){
    echo json_encode($result);
}