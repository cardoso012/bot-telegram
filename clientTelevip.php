<?php

include_once(__DIR__ . '/inputWebhooks.php');
include_once(__DIR__ . '/messages.php');
include_once(__DIR__ . '/vendor/autoload.php');

use Televip\TelegramApi\Bot;
use Televip\Helpers\BotHelper;
use Televip\Database\Database;
use Televip\Config\Config;

$db = new Database(Config::getDatabaseConfig());

// Busca o bot token do cliente
$token = Config::getClientTelevipConfig();

// Instancia o Bot
$bot = new Bot($token);

$json = json_decode($data);

// Verifica se o Bot foi adicionado à algum grupo e salva os dados do grupo
if(BotHelper::isBotANewChatMember($json)){
    
    // $message = "Bot adicionado ao grupo \"{$json->message->chat->title}\"\n\nGostaria de gerenciar mais grupos?";
    $message = "Fantástico!\n\nTelevip foi adicionado ao grupo \"{$json->message->chat->title}\"\n\nGostaria de gerenciar mais grupos?";
    
    $options = array(
        array(
            "text" => "SIM",
            "callback_data" => 1
        ),
        array(
            "text" => "NÃO",
            "callback_data" => 0
        )
    );
    
    $bot->sendMessageWithCallbackQuery($json->message->from->id, $message, $options);
    
    $db->saveGroup($json->message);
    exit;
}

// "Pega" o id do chat atual
$chat_id = BotHelper::getChatId($json);

if(isset($json->callback_query)){
    
    $selectedOption = $json->callback_query->data;
    
    if($selectedOption == '0'){
        
        $chat_link = 'https://t.me/CentralTelevipBot?start=configurar_integracoes';
        $message = "Excelente!\n\nÀ partir de agora, em nossa Central Televip, você pode listar os seus grupos, e compartilhar o acesso a eles com os seus assinantes!";
        $bot->sendMessage($chat_id, $message, $chat_link, $text_link = "Clique aqui!");
        exit;
        
    }
    
    $message = "Certo!\n\nMe adicione aos grupos que deseja como administrador, e me dê todas as permissões!";
    $bot->sendMessage($chat_id, $message, $chat_link, $text_link = "Clique aqui!");
    exit;
    
}

// Inicia a sessão
session_id($chat_id);
session_cache_expire(60);
session_start();

// Verifica se existe a variavel "tipo_mensagem" na session, senão atribui o valor = 1
if(!isset($_SESSION["tipo_mensagem"])){
    $_SESSION["tipo_mensagem"] = 1;
}

// Extrai as informacoes do JSON que está dentro de "message"
$info = BotHelper::extractInfoFromMessage($json);

$command = null;
$groupName = null;

preg_match('/(\/start) (.+)/', $info["text_message"], $commandMatch);

if(isset($commandMatch[2]) && base64_decode($commandMatch[2]) == "Olá, quero gerenciar meus grupos!")
{
    $client = $db->getClient($chat_id);
    $message = "Olá, {$info['first_name']}, vamos gerenciar os seus grupos!\n\nPara isto, siga o passo a passo:\n\n1. Entre no (primeiro) grupo que você deseja gerenciar.\n2. Vá até o menu de membros desse grupo e adicione o usuário @TheTelevipBot ao grupo.\n3. Logo após, defina @TheTelevipBot como administrador do seu grupo, concedendo todas as permissões.\n\nLogo que você terminar as configurações e estiver tudo certo, eu te avisarei aqui!";
    $bot->sendMessage($chat_id, $message);
    exit;
}


//-------------------------------------**-------------------------------------//


if($commandMatch){
  $command = $commandMatch[1];
  $groupName = $commandMatch[2];
  $_SESSION["grupo_id"] = $db->getGroupIdTelegram($commandMatch[2]);
}

if($command == '/start'){
    
    $_SESSION["tipo_mensagem"] = 1;
    
    if($db->isSubscriber($chat_id)){
        
        $_SESSION["tipo_mensagem"] = 3;
        $bot->unbanChatMember($_SESSION["grupo_id"], $_SESSION["user_id"]);
        
        // Gera convite para grupo com tempo de expiracao de 1 hora
        $invite_link = $bot->createChatInviteLink($_SESSION["grupo_id"]);
        
        $message = $messages[$_SESSION["tipo_mensagem"]];
        $bot->sendMessage($chat_id, $message, $invite_link);
        $_SESSION["tipo_mensagem"] = 4;
        session_write_close();
        exit;
        
    }
    
    $message = $messages[$_SESSION["tipo_mensagem"]];

    // Formata o corpo da mensagem
    $message = str_replace("##NOME##", $info["first_name"], $message);
    $message = str_replace("##NOME_GRUPO##", base64_decode($groupName), $message);
    
    // Envia mensagem
    $bot->sendMessage($chat_id, $message);

    // Salva os dados na session
    $_SESSION["user_id"] = $info["user_id"];
    $_SESSION["nome"] = $info["first_name"] . ' ' . $info["last_name"];
    $_SESSION["tipo_mensagem"] = 2;

    session_write_close();
    exit;
    
}

if($_SESSION["tipo_mensagem"] == 2){
    
    if($info["type_message"] != 'email'){
        $message = 'Por favor, digite um email valido';
        $bot->sendMessage($chat_id, $message);
        session_write_close();
        exit;
    }
    
    $message = $messages[$_SESSION["tipo_mensagem"]];
    $bot->sendMessage($chat_id, $message);
    
    $_SESSION["email"] = $info["text_message"];
    $_SESSION["tipo_mensagem"] = 3;
    session_write_close();
    exit;
    
}

if($_SESSION["tipo_mensagem"] == 3){
    
    if($info["type_message"] != 'phone_number'){
        $message = 'Por favor, digite um numero de celular valido';
        $bot->sendMessage($chat_id, $message);
        session_write_close();
        exit;
    }
    
    $bot->unbanChatMember($_SESSION["grupo_id"], $_SESSION["user_id"]);
    
    // Gera convite para grupo com tempo de expiracao de 1 hora
    $invite_link = $bot->createChatInviteLink($_SESSION["grupo_id"]);
    
    $message = $messages[$_SESSION["tipo_mensagem"]];
    $bot->sendMessage($chat_id, $message, $invite_link);
    
    $_SESSION["celular"] = $info["text_message"];

    $_SESSION["tipo_mensagem"] = 4;
    
    if(!$db->isSubscriber($chat_id)){
        $user_id = $db->saveSubscriber($_SESSION);
        $db->associateGroup($user_id, $db->getGroupId($_SESSION["grupo_id"]));

    }
    session_write_close();
    exit;
    
}
