<?php

namespace Televip\Helpers;

class BotHelper{

  private function __construct()
  {

  }

  public static function getChatId($json)
  {

    if(isset($json->callback_query)){
        return $json->callback_query->message->chat->id;
    }
    
    return $json->message->chat->id;

  }

  /*public static function isBotANewChatMember($json)
  {
  
    $isNewChatMember = $json->message->new_chat_member ?? false;
    $isBot = $json->message->new_chat_member->is_bot ?? false;
    $hasMigrateChatId = $json->message->migrate_to_chat_id ?? false;
  
    if(($isNewChatMember && $isBot)){
      return true;
    }

    return false;
  
  }*/
  
  /*public static function isBotANewChatMember($json)
  {
  
    $username = $json->message->from->username ?? '';
    $typeGroup = $json->message->sender_chat->group ?? false;
    
    if($username == 'GroupAnonymousBot' && $typeGroup == 'supergroup'){
        return true;
    }
    
    return false;
  
  }*/
  
  public static function isBotANewChatMember($json)
  {
    
    if(isset($json->message->migrate_to_chat_id)){
        return true;
    }
    
    return false;
  
  }

  public static function extractInfoFromMessage($json)
  {
    
    $message = isset($json->callback_query) ? $json->callback_query->message : $json->message;

    return array(
      'user_id'      => $message->from->id ?? '',
      'text_message' => $message->text ?? '',
      'type_message' => $message->entities[0]->type ?? 'text',
      'first_name'   => $message->from->first_name  ?? '',
      'last_name'    => $message->from->last_name   ?? '',
    );

  }

  public static function hasMessage($json)
  {

    if(property_exists($json, 'message')){
      return true;
    }

    return false;

  }


}