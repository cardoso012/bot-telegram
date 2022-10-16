<?php

namespace Televip\TelegramApi;

class Request{
    
    private $base_url = 'https://api.telegram.org/bot';
    private $token;
    private $debug = false;
    
    public function setToken($token){
        $this->token = $token;
    }
  
    public function setDebug(){
        return $this->debug = true;    
    }
    
    public function send($endpoint, $dados, $method = "POST"){
      
        $url = $this->base_url .  $this->token . '/' . $endpoint;
        $ch = curl_init( $url );
        
        if(isset($dados)){
            $payload = json_encode($dados);
            curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload);
        }
        
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
            'User-Agent: sprache/1.0',
            'Content-Type: application/json'
        ));
        
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        
        if($method == "POST") {
            curl_setopt($ch, CURLOPT_POST, 1);
        } else if($method == "GET"){
            curl_setopt($ch, CURLOPT_POST, 0);
        } else if($method == "PUT") {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        }
        
        # Return response instead of printing.
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        
        # Send request.
        $result = curl_exec($ch);
        
        curl_close($ch);
        if($this->debug){
            echo "<pre>$result</pre>";
        }
        
        return json_decode($result);
    
    }
    
}