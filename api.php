<?php 

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require( './openai.php' ) ;


if(empty($_POST)){
    $_REQUEST = json_decode(file_get_contents('php://input'), true);
}

$lang   = $_REQUEST['lang'] ?? 'English' ; 

$prompts = [
    'Summary2' => "Provide a concise summary of user content in about 5000 word , highlighting the main points, key arguments, and significant findings. Ensure that the summary captures the essence of the article's content and provides a clear understanding of its key takeaways. Format the headers in bold for better readability.", 
    "Dialogue" => "Create a dynamic and engaging dialogue between two individuals discussing the key points and insights presented in the article. The dialogue should  present   the article content in an interesting and captivating manner. Ensure that the dialogue is fast-paced, thought-provoking, and arouses curiosity in the listeners." ,
    "Podcast" => "Create an informative podcast, between 2 persons to dive into the fascinating world of the article. They discuss key insights and findings from the article, exploring its implications and shedding light on important aspects. Through lively conversation and thought-provoking questions, they bring the information to life and create a captivating listening experience.  Listeners will be intrigued by the in-depth analysis, personal anecdotes, and expert opinions shared in this podcast. Tune in to discover new perspectives, gain valuable knowledge, and get inspired by the exciting world of [topic/article subject ." ,
    "Quiz" => "Design an interactive quiz to test your audience's knowledge on the article. Create a series of multiple-choice questions that challenge their understanding and encourage critical thinking. Consider incorporating visuals or multimedia elements to make the quiz visually appealing and interactive. Ensure that the questions are clear, concise, and well-structured. Provide immediate feedback for each question to enhance the learning experience. Your goal is to create an enjoyable and educational quiz that engages your audience and helps them expand their knowledge on the topic , Make summary as OUTPUT: #Heading ###H3 and #####H5" ,
    "Twitter thread" => "Compose a captivating Twitter thread consisting of 7 tweets on a thought-provoking article. Each tweet should build upon the previous one and contribute to a coherent and engaging narrative. Choose a subject that resonates with your target audience and allows for meaningful discussion. Use concise and compelling language to deliver your message effectively. Incorporate relevant hashtags and visuals to enhance the impact of your thread. Your goal is to create a thread that captures attention, stimulates conversation, and leaves a lasting impression on your audience" ,
];


$key  = $_REQUEST['key'] ?? 'Summary' ;

if( isset($_REQUEST['id']) ){
    $chatid = str_replace( ['..' , '.' , '/'] , '' , $_REQUEST['id'] ) ;
    $chat   = trim(strip_tags( file_get_contents( __DIR__.'/public/public/uploads/'.$chatid.'.txt' ) )) ;
}else{
    $chat   = trim(strip_tags($_REQUEST['chat'] ?? '')) ;
}

header('Content-Type: Application/json') ;
if( !isset($chat[15]) ) {
    $result = ['status' => false , 'error' => 'Please Provide Content'] ;
}else{
    if( !isset($prompts[$key]) ){ $key = 'Summary' ; }
    $answer = chat( $prompts[$key] , $chat , 1700 , true ) ;
    $result = ['status' => true , 'answer' =>  nl2br($answer) ] ;
}

echo json_encode( $result ) ;
