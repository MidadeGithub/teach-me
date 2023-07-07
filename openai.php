<?php 

    function dd($array){
        print_r($array); die();
    }

    define('APIKEY' , '') ; // Gpt-3.5
    //define('APIKEY' , '') ; // Gpt-4
    

    function callGpt($msgs , $max_tokens = 1700 , $start = true){
        $apiKey = constant('APIKEY') ;

        $curl = curl_init();

        $jsonData = [
            "model" => "gpt-3.5-turbo-16k" ,
            "temperature"=> 0 ,
            "top_p" => 0,
            "n"=> 1,
            "stream"=> false ,
            "max_tokens"=> $max_tokens ,
            "presence_penalty"=> 0,
            "frequency_penalty"=> 0 ,
            "messages" => $msgs
        ] ;
        
        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.openai.com/v1/chat/completions',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($jsonData),
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer '.$apiKey
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $returnResponse = '' ;
        
        list( $status , $text , $ended ) = getAnswer( $response ) ;

        $returnResponse .= $text ;

        if( $ended === false  ) {
            $false = true ;

            if( $start ) {
                $false = false ;
                $msgs = [
                    [
                        "role" => "assistant",
                        "content" => " Continue"
                    ]
                ] ;
            }

            $returnResponse .= callGpt( $msgs , $max_tokens , $false ) ;
            
        }

        return nl2br( convertToHtml( $returnResponse ) ) ;
    }

    function chat($prompt , $text , $tokens = 4000 , $handelFirst = true , $MaxTokensToChar = 3000 , $max_tokens = 12000 ){

        
        if( $handelFirst ) {
             $text = preg_replace('/[ \t]+/', ' ', preg_replace('/\s*$^\s*/m', "\n", $text ));
             
             $length = strlen( $text ) ;
             if( $length > $tokens ) {
                
                $length = strlen( $text ) ;

                if( $length > $MaxTokensToChar ) {
                    $text = mb_substr( $text , 0 , $MaxTokensToChar , 'utf-8') ;
                }
                
                $text = mb_str_split( $text , $tokens , 'utf-8' ) ;
                global $_REQUEST ;
                if( $_REQUEST['key'] == 'chat' ) {
                    $text[] = $prompt ;
                    $listChat = $_REQUEST['list'] ;

                    foreach( $listChat as $ch ) {
                        if($ch['from'] == 'user') {
                            $text[] = 'User Asked '. ($ch['text'] ?? '' ) ;
                        }else{
                            $text[] = 'Your Answer was '. ($ch['text'] ?? '' );
                        }
                    }

                }else{
                    $text[] = 'Depending on all Messages , collect them in one text ,  fix grammar , then Do '. $prompt ;
                }
             }
             
        }

        $jsonData = [] ;

        
        $jsonData['messages'][] = [
            "role" => "system",
            "content" => "You are Marqoumx. You can handle any text send to you by the user , you can do any request bu the user , If you didn't find answer depending on the content , please replay i don't know only"
        ] ;
        

        if( trim($prompt) != '' && 1 == 0) {
            $jsonData['messages'][] = [
                "role" => "assistant",
                "content" => $prompt
            ] ;
        }

        if( is_array($text) ) {
            foreach( $text as $k => $t ){
                if( substr($t ,0 , 15) == 'Your Answer was' ) {
                    $jsonData['messages'][] = [
                        "role" => "assistant",
                        "content" => str_replace('Chatgpt Answer was' , '' , $t)
                    ];
                }else{
                    $jsonData['messages'][] = [
                        "role" => "user",
                        "content" => $t
                    ];
                }
                
            }
        } else {
            $jsonData['messages'][] = [
                "role" => "user",
                "content" => $text
            ];
        }

        // dd( $jsonData['messages'] ) ;
             
        return callGpt( $jsonData['messages'] ?? [] , $max_tokens ) ;

    }

    function getVideoData($link) {
        $youtube = "https://www.youtube.com/oembed?url=". $link ."&format=json";
        $curl = curl_init($youtube);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $return = curl_exec($curl);
        curl_close($curl);
        return json_decode($return, true);
    }

    function summerizeYt($link , $title ){
        preg_match("#(?<=v=)[a-zA-Z0-9-]+(?=&)|(?<=v\/)[^&\n]+(?=\?)|(?<=v=)[^&\n]+|(?<=youtu.be/)[^&\n]+#", $link, $matches);
        $youtubeId = $matches[0] ;
        /*
        $youtube = "https://momaiz.net/midade/youtube.php?youtube=".$youtubeId ;
        $curl = curl_init($youtube);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $return = curl_exec($curl);
        curl_close($curl);
        */
       $prompt = 'Make Full Summary from this Srt , with format with #Headings, ##H2  , ###H3 [ 00:00:00 ] , about 2 lines excerpt + bullet points.' ;

        //$prompt = 'Summarize this content in points';
        $text  =  file_get_contents( 'https://momaiz.net/midade/youtube.php?youtube='.$youtubeId ) ;

        $response = chat( $prompt , $text , 6000 , false , 4500 , 5500 ) ;

        return [$response , nl2br($text) ] ;
    }

    function Summaryhtml($text) {
        
        // Split the text into an array of lines
        $lines = explode("\n", $text);
    
        $res = '' ;
        // Loop through each line and check for hash characters at the beginning
        foreach ($lines as $line) {
            $num_hashes = strspn($line, '#');

            // Replace the hash characters with the corresponding HTML heading tag
            switch ($num_hashes) {
                case 1:
                    $line = '<h1>' . substr($line, $num_hashes ) . '</h1>';
                    break;
                case 2:
                    $line = '<h2>' . substr($line, $num_hashes ) . '</h2>';
                    break;
                case 3:
                        $line = '<h3>' . substr($line, $num_hashes ) . '</h3>';
                        break;
                case 4:
                        $line = '<h4>' . substr($line, $num_hashes ) . '</h4>';
                        break;
                case 5:
                    $line = '<h5>' . substr($line, $num_hashes ) . '</h5>';
                    break;
                case 6:
                    $line = '<p>' . substr($line, $num_hashes ) . '</p>';
                    break;
                default: 
                        $line = '<span> '.substr($line, 0).' </span><br />' ;
                        break;
                // add more cases for additional heading levels if needed
            }
    
            // Output the modified line
            $res .= $line . "";
        }

        return $res ;
    }

    function convertToHtml($summary){
         
         $data = explode( "\n\n" , $summary ) ;
         //unset($data[0]) ;
            $html = '' ;
            foreach($data as $line) {
                $data2 = explode( "\n" , $line ) ;
                foreach($data2 as $line2) {
                    $html .=  Summaryhtml($line2).' ';
                }
            }
            return $html ;
    }

    function getAnswer( $response ){
        $json = (array) json_decode( $response ) ;
       
        if( isset($json['choices']) ) {

            $result = $json['choices'][0]->message->content;

            $ended = true ;

            if( $json['choices'][0]->finish_reason != 'stop' ) {
                $ended = false ;
            }

            $return = [true , $result , $ended] ;

        }else{
            dd($json) ;
            $return = [false , 'Error' , true] ;
        }
        

        return $return ;
    }

    function tts($file , $lang = ''){

        if( $lang == '' ){
            $content = file_get_contents($file) ;
            $lang = dlang( mb_substr( $content , 0 , 50 , 'utf-8' ) ) ;
        }

        $curl = curl_init();
        curl_setopt_array($curl, array(
          CURLOPT_URL => 'http://164.90.171.8/tts/gtts.php',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS => array('file'=> new CURLFILE( $file ) , 'tld' => 'com.au' , 'lang' => $lang ),
          CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer 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'
          ),
        ));
        
        $response = json_decode( curl_exec($curl) );
        
        curl_close($curl);
        if( $response->status ){
            return $response->data->content ;
        }else{
            return "" ;
        }
    }

    function stt($file , $ext = 'mp3'){
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.openai.com/v1/audio/transcriptions',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => array('file'=> new CURLFILE( $file ),'model' => 'whisper-1','prompt' => 'Extract text from mp4 or mp3 ','response_format' => 'text','temperature' => '0','language' => '','transcription' => $ext ),
        CURLOPT_HTTPHEADER => array(
            'Accept: application/json',
            'Authorization: Bearer '.constant('APIKEY')
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response->text ?? $response ;
    }

    function ocr($url , $language = 'eng'){
        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.ocr.space/parse/imageurl?apikey=helloworld&url='.$url.'&language='.$language.'&isOverlayRequired=true',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'apikey: helloworld'
        ),
        ));

        $response = json_decode( curl_exec($curl) );

        curl_close($curl);

        return $response->ParsedResults[0]->ParsedText ?? '' ;
        
    }

    function dlang($text = ''){
        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => 'https://ws.detectlanguage.com/0.2/detect',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS => array('q' => $text ),
          CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer 1da31f77f109dc05f0006f06d490339b'
          ),
        ));

        $response = json_decode( curl_exec($curl) ) ;

        curl_close($curl);
        
        return  $response->data->detections[0]->language ?? 'en' ;

    }

?>
