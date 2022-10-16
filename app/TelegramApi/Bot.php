<?php

namespace Televip\TelegramApi;

use Televip\TelegramApi\Request;

class Bot{
    
    private $request;
    
    public function __construct($token){
        $this->request = new Request();
        $this->request->setToken($token);
    }
    
    public function sendMessage($chat_id, $message, $link = NULL, $text_link = NULL){
        
        $dados = array(
            'chat_id' => $chat_id,
            'text' => $message,
            'parse_mode' => 'HTML'
        );
        
        if(!$text_link){
            $text_link = "Entrar no grupo!";
        }
        
        if($link){
            $dados["reply_markup"]["inline_keyboard"][] = array(
                array(
                    "text" => $text_link,
                    "url" => $link
                )
            );
        }
        
        return $response = $this->request->send('sendMessage', $dados);
        
    }
    
    public function sendMessageWithCallbackQuery($chat_id, $message, $options)
    {
        
        $dados = array(
            'chat_id' => $chat_id,
            'text' => $message,
            'parse_mode' => 'HTML',
            'reply_markup' => array(
                'inline_keyboard' => array(
                    $options
                )
            )
        );
        
        return $response = $this->request->send('sendMessage', $dados);
        
    }
    
    public function createChatInviteLink($chat_id){
        
        $dados = array(
            "chat_id"       => (int) $chat_id,
            "expire_date"   => time() + 3600,
            "member_limit"  => 1
        );
        
        $response = $this->request->send('createChatInviteLink', $dados);
        
        if($response->ok){
            return $response->result->invite_link;
        }
        
    }
    
    public function getChat($chat_id){
        
        $dados = array(
            "chat_id"  => (int) $chat_id
        );
        
        $response = $this->request->send('getChat', $dados);
        
        if($response->ok){
            return $response->result;
        }
        
    }
    
    public function unbanChatMember($chat_id, $user_id, $only_if_banned = true){
        
        $dados = array(
            "chat_id"  => (int) $chat_id,
            "user_id"  => (int) $user_id,
            "only_if_banned"  => $only_if_banned
        );
        
        $response = $this->request->send('unbanChatMember', $dados);
        
        if($response->ok){
            return $response->result;
        }
        
    }

}


