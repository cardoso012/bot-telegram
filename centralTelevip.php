<?php

include_once(__DIR__ . '/inputWebhooks.php');
include_once(__DIR__ . '/messages.php');
include_once(__DIR__ . '/vendor/autoload.php');

use Televip\TelegramApi\Bot;
use Televip\Helpers\BotHelper;
use Televip\Database\Database;
use Televip\Config\Config;

$json = json_decode($data);

$db = new Database(Config::getDatabaseConfig());

// Busca o bot token central
$token = Config::getCentralTelevipConfig();

// Instancia o Bot
$bot = new Bot($token);

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

if($info["text_message"] == '/start configurar_integracoes'){
    
    $message = "Olá, {$info['first_name']}!\n\nSou a Central Televip e vou te ajudar no passo a passo de como configurar suas integracoes!";
    
    $integracoes = $db->listIntegrations();
    $options = array();
    
    foreach($integracoes as $integracao){
        $options[] = array(
            "text" => ucfirst($integracao->nome),
            "callback_data" => $integracao->id
        );
    }
    
    $bot->sendMessageWithCallbackQuery($chat_id, $message, $options);
    
    if($db->isClient($chat_id)){
        $_SESSION['cliente_id'] = $db->getClient($chat_id)->id;
    }

    // Salva os dados na session
    $_SESSION["user_id"] = $info["user_id"];
    $_SESSION["nome"] = $info["first_name"] . ' ' . $info["last_name"];
    $_SESSION["tipo_mensagem"] = 100;
    session_write_close();
    exit;
    
}

if($info["text_message"] == '/configurar_integracoes'){
    $message = "Olá, {$info['first_name']}!\n\nSou a Central Televip e vou te ajudar no passo a passo de como configurar suas integracoes!";
    
    $integracoes = $db->listIntegrations();
    $options = array();
    
    foreach($integracoes as $integracao){
        $options[] = array(
            "text" => ucfirst($integracao->nome),
            "callback_data" => $integracao->id
        );
    }
    
    $bot->sendMessageWithCallbackQuery($chat_id, $message, $options);
    
    if($db->isClient($chat_id)){
        $_SESSION['cliente_id'] = $db->getClient($chat_id)->id;
    }

    // Salva os dados na session
    $_SESSION["user_id"] = $info["user_id"];
    $_SESSION["nome"] = $info["first_name"] . ' ' . $info["last_name"];
    $_SESSION["tipo_mensagem"] = 100;
    session_write_close();
    exit;
}

if($info["text_message"] == '/start'){
    
    $isClient = $db->isClient($chat_id);
    
    if($isClient){
        exit;
    }
    
    $message = "Olá, ##FIRST_NAME##!\n\nSou a Central Televip e vou te ajudar no passo a passo de como gerenciar os seus grupos!\n\nPrimeiro, informe o seu melhor email";
    $message = str_replace('##FIRST_NAME##', $info['first_name'], $message);

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
        $message = "Por favor, digite um e-mail valido!";
        $bot->sendMessage($chat_id, $message);
        session_write_close();
        exit;
    }

    $_SESSION["email"] = $info["text_message"];
    $_SESSION["email_confirmado"] = 1;
    $_SESSION["tipo_mensagem"] = 3;

    $message = "Excelente!\n\nAgora, por favor, digite seu número de celular completo, com DDD.\n\nCaso seu número seja estrangeiro, digite com o código do país.\n\nExemplo Brasil: 11 990990909\n\nExemplo Exterior: 39 3248998660";
    $bot->sendMessage($chat_id, $message);
    session_write_close();
    exit;
    
}

if($_SESSION["email_confirmado"] == 1 && $_SESSION["tipo_mensagem"] == 3){

    if($info["type_message"] != 'phone_number'){
        $message = "Por favor, digite um numero valido!";
        $bot->sendMessage($chat_id, $message);
        session_write_close();
        exit;
    }
    
    $message = "Maravilha!\n\nAgora, já posso te direcionar ao bot \"Televip Assinantes\", para você começar a gerenciar os seus grupos.";
    $bot->sendMessage($chat_id, $message, $chat_link, $text_link = "Clique aqui!");
    
    $_SESSION["tipo_mensagem"] = 4;
    $_SESSION["celular"] = $info["text_message"];
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
        $message .= $grupo->id . ' - ' . $chat->title . ': ' . $grupo->invite_link . "\n\n";
    }
    
    $bot->sendMessage($chat_id, $message);
    $bot->sendMessage(
        $chat_id,
        "Para listar os assinantes do grupo, basta usar o comando /listar_assinantes NUMERO_DO_GRUPO. \n\nEx: /listar_assinantes 1"
    );
    exit;

}

if($info["text_message"] == '/listar_assinantes'){
    
    $bot->sendMessage($chat_id, 'Listando grupos...');
    $grupos = $db->listGroups($chat_id);
    
    if(!$grupos){
        $bot->sendMessage($chat_id, "Você ainda não possui grupos gerenciáveis. Por favor, adicione o nosso bot Televip aos seus grupos!");
        exit;
    }
    
    $message = "Estes são os seus grupos:\n\n";
    
    $sprache_token = Config::getClientTelevipConfig();
    $sprache_bot = new Bot($sprache_token);
    
    foreach($grupos as $grupo){
        $chat = $sprache_bot->getChat($grupo->chat_id);
        $message .= $grupo->id . ' - ' . $chat->title . "\n\n";
    }
    
    $bot->sendMessage($chat_id, $message);
    
    sleep(1);
    
    $message = "Informe o numero do grupo para listar os assinantes";
    $bot->sendMessage($chat_id, $message);
    
    $_SESSION["numero_grupo"] = "";
    
    session_write_close();
    exit;
    
}

if(isset($_SESSION["numero_grupo"])){
    
    $assinantes = $db->listSubscribers($_SESSION["numero_grupo"]);
    
    if(!$assinantes){
        $bot->sendMessage($chat_id, "Nenhum assinante encontrado nesse grupo");
        session_write_close();
        exit;
    }
    
    $lista_de_assinantes = "";
    
    foreach($assinantes as $assinante){
        $lista_de_assinantes .= $assinante->nome . ' - ' . $assinante->email . "\n";
    }
    
    $bot->sendMessage($chat_id, $lista_de_assinantes);
    session_write_close();
    exit;
    
}

if((isset($json->callback_query) && $_SESSION['tipo_mensagem'] == 100) || $info["text_message"] == '/configurar_integracoes'){
    
    $referenciaDocumentacao = 'https://help.hotmart.com/pt-BR/article/como-configurar-sua-api-atraves-do-webhook-postback-/360001491352';
    
    $url = 'https://api.spracheundwissen.com/televip_teste/webhooks.php?integracao=' . $_SESSION['nome_integracao'] . '&client_id=' . $_SESSION['client_id'];
    $message = "Integração configurada com sucesso!\n\nAgora configure na sua plataforma de vendas, nas configurações de webhooks a seguinte URL:\n\n{$url}\n\n\n\nPasso a passo para configuração do webhook:\n\n{$referenciaDocumentacao}";
    $bot->sendMessage($chat_id, $message);
    session_write_close();
    exit;
}

/*
if((isset($json->callback_query) && $_SESSION['tipo_mensagem'] == 100) || $info["text_message"] == '/configurar_integracoes'){
    
    $_SESSION['client_id'] = $db->getClientId($chat_id);
    $_SESSION['id_integracao'] = $json->callback_query->data;
    $_SESSION['configuracoes'] = $db->configIntegration($json->callback_query->data);
    $_SESSION['nome_integracao'] = $_SESSION['configuracoes'][0]->nome;
    $message = $_SESSION['configuracoes'][0]->mensagem;
    $bot->sendMessage($chat_id, $message);

    $_SESSION['tipo_mensagem'] = 101;
    session_write_close();
    exit;
}

if($_SESSION['tipo_mensagem'] > 100){
    
    $id_configuracao = $_SESSION['configuracoes'][0]->id_configuracao;
    $campo = $_SESSION['configuracoes'][0]->campo;
    $message = $_SESSION['configuracoes'][0]->mensagem;

    $db->setConfigValue(array(
        'integracao_id' => $_SESSION['id_integracao'],
        'client_id' => $_SESSION['client_id'], 
        'id_configuracao' => $id_configuracao, 
        'campo' => $campo,
        'valor' => $info["text_message"]
    ));
    
    array_shift($_SESSION['configuracoes']);
    
    if(empty($_SESSION['configuracoes'])){
        $url = 'https://api.spracheundwissen.com/televip_teste/webhooks.php?integracao=' . $_SESSION['nome_integracao'] . '&client_id=' . $_SESSION['client_id'];
        $message = "Integração configurada com sucesso!\n\nAgora configure na sua plataforma de vendas, nas configurações de webhooks a seguinte URL:\n\n{$url}";
        $bot->sendMessage($chat_id, $message);
        session_write_close();
        exit;
    }
    
    $bot->sendMessage($chat_id, $message);

    if(!empty($_SESSION['configuracoes'])){
        $_SESSION['tipo_mensagem'] += 1;
    }
    
    session_write_close();
    exit;
}
*/














