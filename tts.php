<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require( './openai.php' ) ;

$jsonField = file_get_contents('php://input') ;

if( isset($jsonField) ){
    $_REQUEST = json_decode( $jsonField , true);
}

$text     = $_REQUEST['text'] ?? '' ;

$language = $_REQUEST['lang'] ?? '' ;

$res = ['status' => false , 'error' => 'no text'] ;

if( isset($text[20]) ) {
    // Contact Trtotise Get Mp3
    $tmpName = __DIR__.'/public/public/uploads/'.rand(4444,999999).time().'.txt' ;
    $saved = file_put_contents( $tmpName , strip_tags( str_replace(["<br />" , "<br/>"] , "\n" , $text) ) ) ; 
    $url = tts( $tmpName  , $language ) ;
    $res = ['status' => true , 'url' => $url ?? ''] ;
}

echo json_encode( $res ) ;

?>