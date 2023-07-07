<?php 

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require( './openai.php' ) ;

$file = $_FILES['file'] ?? [] ;

$res = ['status' => false , 'error' => 'no file'] ;

$tmpName2 = __DIR__.'/public/public/uploads/'.rand(4444,999999).time().'.txt' ;



if( isset($file['name']) ) {
    $fileName = $file['name']  ;
    $ext      = strtolower( strrchr($fileName , '.') ) ;

    if( $ext == '.txt' ) {
        $text = file_get_contents( $file['tmp_name'] ) ;
        $res = ['status' => true , 'data' => [ 'name' => $fileName , 'ext' => $ext , 'size' => $file['size'] , 'content' => $text]  ]  ;    
    }elseif( $ext == '.pdf' || $ext == '.docx' ) {
        
        //echo $tmpName ; die();

        $tmpName = __DIR__.'/public/public/uploads/'.rand(4444,999999).time().$ext ;


        if( move_uploaded_file( $file['tmp_name'] , $tmpName )){

            $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://164.90.171.8/v3/content.php',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array('file'=> new CURLFILE( $tmpName )),
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer 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'
            ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);

            $res = (array) json_decode($response) ;

        } else {
            $res = ['status' => false , 'error' => 'file not uploaded'] ;
        }

        

        
    }elseif( $ext == '.mp3' || $ext == '.mp4' ) {
        $tmpName = __DIR__.'/public/public/uploads/'.rand(4444,999999).time().$ext ;
        //echo $tmpName ; die();
        if( move_uploaded_file( $file['tmp_name'] , $tmpName )){
            $text = stt( $tmpName  , trim($ext,'.') ) ;
            $res = ['status' => true , 'data' => [ 'name' => $fileName , 'ext' => $ext , 'size' => $file['size'] , 'content' => $text]  ]  ;    
        } else {
            $res = ['status' => false , 'error' => 'file not uploaded'] ;
        }
    }elseif( in_array( $ext , ['.jpg' , '.jpeg' , '.png' , '.webp'] ) ){
        $tmpName = __DIR__.'/public/public/uploads/'.rand(4444,999999).time().$ext ;
        //echo $tmpName ; die();
        if( move_uploaded_file( $file['tmp_name'] , $tmpName )){
            $tmpUrl = str_replace( __DIR__ , 'http://'.$_SERVER['SERVER_NAME'].'/v3/' ,  $tmpName ) ;
            $text = ocr( $tmpUrl  , 'eng' ) ;
            $res = ['status' => true , 'data' => [ 'name' => $fileName , 'ext' => $ext , 'size' => $file['size'] , 'content' => $text]  ]  ;    
        } else {
            $res = ['status' => false , 'error' => 'file not uploaded'] ;
        }
    } else {
        $res = ['status' => false , 'error' => 'file not supported'] ;
    }

}

if( $res['status'] === true ){
    file_put_contents( $tmpName2 , $res['data']->content ?? $res['data']['content'] ?? '' ) ;
    $res['code'] = str_replace( [__DIR__.'/public/public/uploads/' , '.txt'] , '' ,  $tmpName2 ) ;
}

echo json_encode( $res ) ;

?>