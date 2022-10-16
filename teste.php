<?php

include_once(__DIR__ . '/vendor/autoload.php');

use Televip\Database\Database;
use Televip\Config\Config;
use Televip\TelegramApi\Bot;

$db = new Database(Config::getDatabaseConfig());




$dados = array(
    "nome" => 'Lucas Cardoso',
    "email" => 'lucas.cardoso1228@hotmail.com',
    "celular" => '16992133086',
    "user_id" => 1474882695
);


print_r($db);
var_dump($db->saveSubscriber($dados));






exit;


$token = $db->getClientBotToken($_GET["client_id"]);
$bot = new Bot($token);

$info["text_message"] = "/start " . $_GET['grupo'];

preg_match('/(\/start) (.+)/', $info["text_message"], $commandMatch);

if($commandMatch){
  echo '<br>';
  echo $command = $commandMatch[1];
  echo '<br>';
  echo $groupName = $commandMatch[2];
  echo '<br>';
}


echo $ID_GRUPO = $db->getGroupId($groupName);
echo '<br>';
echo $invite_link = $bot->createChatInviteLink($ID_GRUPO);

/*
    URL PARA TESTAR: https://api.spracheundwissen.com/televip_teste/teste.php?client_id=5383202962&grupo=cXVhbHF1ZXIgY29pc2E=
*/