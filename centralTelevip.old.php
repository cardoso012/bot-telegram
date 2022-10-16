<?php

include_once(__DIR__ . '/inputWebhooks.php');
include_once(__DIR__ . '/messages.php');
include_once(__DIR__ . '/vendor/autoload.php');

use Televip\TelegramApi\Bot;
use Televip\Helpers\BotHelper;
use Televip\Database\Database;
use Televip\Config\Config;

$db = new Database(Config::getDatabaseConfig());

// Busca o bot token central
$token = Config::getCentralTelevipConfig();

// Instancia o Bot
$bot = new Bot($token);

$json = json_decode($data);

// Verifica se no JSON tem o campo "message"

// "Pega" o id do chat atual
$chat_id = BotHelper::getChatId($json);

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

$start_message = "Olá, quero gerenciar meus grupos!";
$chat_link = 'https://t.me/TheTelevipBot?start=' . base64_encode($start_message);

preg_match('/(\/start) (.+)/', $info["text_message"], $commandMatch);

if($commandMatch)
{   
    $commandStart = $commandMatch[1];
    $dados = explode('&', base64_decode($commandMatch[2]));
    
    foreach($dados as $dado){
        
        $campos = explode('=', $dado);
        $_SESSION[$campos[0]] = $campos[1];
        
    }
    
}

if($commandStart == '/start'){
    
    $message = "Olá, ##FIRST_NAME##!\n\nSou a Central Televip e vou te ajudar no passo a passo de como gerenciar os seus grupos!\n\nPrimeiro, vamos confirmar seus dados. O email que você utilizou na compra em nosso site é ##EMAIL_DA_COMPRA##?\n";
    
    $message = str_replace('##FIRST_NAME##', $info['first_name'], $message);
    $message = str_replace('##EMAIL_DA_COMPRA##', $_SESSION["email"], $message);

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
    
    $bot->sendMessageWithCallbackQuery($chat_id, $message, $options);

    // Salva os dados na session
    $_SESSION["user_id"] = $info["user_id"];
    $_SESSION["nome"] = $info["first_name"] . ' ' . $info["last_name"];
    session_write_close();
    exit;
    
}

if(isset($json->callback_query)){
    
    if($json->callback_query->data == '0'){
    
        $message = "Entendi!\n\nPor favor, digite o e-mail que você utilizou na sua inscrição em nosso site!\n";
        $bot->sendMessage($chat_id, $message);
        $_SESSION["email_confirmado"] = 0;
        session_write_close();
        exit;
        
    }
    
    $_SESSION["email_confirmado"] = 1;
    
}

if(isset($_SESSION["email_confirmado"]) && $_SESSION["email_confirmado"] == 0){
    
    if($info["type_message"] != 'email'){
        $message = "Por favor, digite um e-mail valido!";
        $bot->sendMessage($chat_id, $message);
        session_write_close();
        exit;
    }
    
    $_SESSION["email_confirmado"] = 1;
    $_SESSION["email"] = $info["text_message"];
    
}

if(isset($_SESSION["email_confirmado"]) && $_SESSION["email_confirmado"] == 1){
    
    $message = "Maravilha!\n\nAgora, vou te direcionar ao bot Televip Assinantes, é ele quem vai gerenciar os seus assinantes.";
    $bot->sendMessage($chat_id, $message, $chat_link, $text_link = "Clique aqui!");
    
    $db->saveClient($_SESSION);
    unset($_SESSION["email_confirmado"]);
    session_destroy();
    exit;
    
}

if($info["text_message"] == '/listar_grupos'){
    
    $bot->sendMessage($chat_id, 'Listando grupos...');
    $grupos = $db->listGroups($chat_id);
    
    if(!$grupos){
        $bot->sendMessage($chat_id, "Você ainda não possui grupos gerenciáveis. Por favor, adicione o nosso bot Televip aos seus grupos!");
        exit;
    }
    
    $message = "Lista de grupos:\n\n";
    
    $sprache_token = Config::getClientTelevipConfig();
    $sprache_bot = new Bot($sprache_token);
    
    foreach($grupos as $grupo){
        $chat = $sprache_bot->getChat($grupo->chat_id);
        $message .= $chat->title . ': ' . $grupo->invite_link . "\n\n";
    }
    
    $bot->sendMessage($chat_id, $message);

}
