<?php

include_once(__DIR__ . '/inputWebhooks.php');
include_once(__DIR__ . '/messages.php');
include_once(__DIR__ . '/vendor/autoload.php');

use Televip\TelegramApi\Bot;
use Televip\Helpers\BotHelper;
use Televip\Database\Database;
use Televip\Config\Config;

// Instancia o Bot
$bot = new Bot(Config::getClientTelevipConfig());
$db = new Database(Config::getDatabaseConfig());

// $chat_id = 1474882695; // LUCAS TESTE
// $chat_id = 625116459; // RADAMES TESTE
$chat_id = 667233823; // MARINA TESTE
$nome = $email = $grupos = '';

$subscriber = $db->getSubscriber($chat_id);
foreach($subscriber as $sub){
    if(empty($nome) && empty($email)){
        $nome = $sub->nome;
        $email = $sub->email;
    }
    
    $grupos .= "{$sub->title}, ";
}

$grupos = utf8_encode(rtrim($grupos, ', '));

$swap_vars = array(
    "##NOME##"       => "<b>{$nome}</b>",
    "##NOME_GRUPO##" => "<b>{$grupos}</b>"
);

// $link_contato = 'https://wa.me/5535984735670?text=Ol%C3%A1%21+Tudo+bem%3F+Quero+retomar+o+acesso+ao%28s%29+grupo%28s%29+do+Telegram%21+Meu+e-mail+%C3%A9%3A%20' . urlencode($email);
$link_contato = 'https://api.spracheundwissen.com/whatsapp-atendimento/atendimento.php?email=' . urlencode($email);
$message = $messages['remocao-grupo'];

foreach($swap_vars as $key => $value){
    $message = str_replace($key, $value, $message);
}

$link = '<a href="' . $link_contato . '">Falar com suporte!</a>';

$bot->sendMessage($chat_id, $message, $link_contato, 'Falar com suporte!');





