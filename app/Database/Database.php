<?php

namespace Televip\Database;

date_default_timezone_set('America/Sao_Paulo');

class Database{

  private $db;
  private $dsn;

  public function __construct($config)
  {

    try {
      
      $this->dsn = 'mysql:host=' . $config['HOST'] . ';dbname=' . $config['DB_NAME'];
      $this->db = new \PDO($this->dsn, $config['USERNAME'], $config['PASSWORD']);
      $this->db->setAttribute(\PDO::ATTR_AUTOCOMMIT, true);

    } catch (\PDOException $e) {
      echo '<pre>';
      echo '<br><br>';
      print_r($e->getMessage());
      echo '<br><br>';
      echo '</pre>';
    }

  }
  
  public function saveClient($dados){
      
    $timestamp = date('Y-m-d H:i:s');
    
    $sql = "INSERT INTO clientes (
      nome,
      email,
      celular,
      bot_id
    )VALUES(
      '" . ucfirst($dados["nome"]) . "',
      '" . strtolower($dados["email"]) . "',
      '" . $dados["celular"] . "',
      '" . $dados["user_id"] . "'
    )";
    
    $stmt = $this->db->prepare($sql);
    
    if($stmt->execute()){
      return true;
    }
    
    return false;
  
  }

  public function saveSubscriber($dados){
     
    $timestamp = date('Y-m-d H:i:s');
    
    echo $sql = "INSERT INTO assinantes (
      nome,
      email,
      celular,
      user_id,
      created_at,
      update_at
    )VALUES(
      '" . ucfirst($dados["nome"]) . "',
      '" . strtolower($dados["email"]) . "',
      '" . $dados["celular"] . "',
      "  . $dados["user_id"] . ",
      '"  . $timestamp . "',
      '"  . $timestamp . "'
    )";
    
    $stmt = $this->db->prepare($sql);
    
    if($stmt->execute()){
        $id = $this->db->lastInsertId();
        return $id;
    }
    
    return false;
  
  }
  
    public function associateGroup($assinante_id, $grupo_id, $data_expiracao = ''){
      
        $timestamp = date('Y-m-d H:i:s');
        
        if(!$grupo_id){
            return false;   
        }
    
        $sql = "INSERT INTO assinantes_grupos (
            id_assinante,
            id_grupo,
            data_ingresso,
            data_expiracao,
            flag
        )VALUES(
          $assinante_id,
          $grupo_id,
          '"  . $timestamp . "',
          '"  . $data_expiracao . "',
          1
        )";
        
        $stmt = $this->db->prepare($sql);
    
        if($stmt->execute()){
            return true;
        }
        
        return false;
      
    }

  public function getClientBotToken($bot_id){
    
    $sql = "SELECT token FROM bots WHERE bot_id = $bot_id";
    $stmt = $this->db->query($sql);
    
    while($bot = $stmt->fetch(\PDO::FETCH_OBJ)){
      return $bot->token;
    }
    
    return false;
 
  }

  private function generateChatLink($username, $titleGroup)
  {
    $title_base64 = base64_encode($titleGroup);
    return $invite_link = 'https://t.me/' . $username . '?start=' . $title_base64;
  }

  public function saveGroup($message)
  {
    
    $title = $message->chat->title;
    $username = 'TheTelevipBot';
    $participant_id = $message->from->id;
    $chat_id = $message->migrate_to_chat_id;

    $client = $this->getClient($participant_id);
    $invite_link = $this->generateChatLink($username, $title);
    
    $sql = "INSERT INTO grupos (
      title,
      user_id,
      chat_id,
      invite_link
    )VALUES(
      '" . utf8_decode($title) . "',
      "  . $client->id . ",
      '" . $chat_id . "',
      '" . $invite_link . "'
    )";
    
    $stmt = $this->db->prepare($sql);
    
    if($stmt->execute()){
      return true;
    }
    
    return false;
  
  }

  public function isSubscriber($chat_id)
  {

    $sql = "SELECT * FROM assinantes WHERE user_id = $chat_id";
    $stmt = $this->db->query($sql);
    
    while($subscriber = $stmt->fetch(\PDO::FETCH_OBJ)){
      return $subscriber->id;
    }
    
    return false;

  }
  
  public function isClient($bot_id)
  {
    
    $sql = "SELECT * FROM clientes WHERE bot_id = $bot_id LIMIT 1";
    $stmt = $this->db->query($sql);
    
    while($client = $stmt->fetch(\PDO::FETCH_OBJ)){
      return true;
    }
    
    return false;

  }

  public function getClient($bot_id)
  {
    
    $sql = "SELECT * FROM clientes WHERE bot_id = $bot_id";
    $stmt = $this->db->query($sql);
    
    while($client = $stmt->fetch(\PDO::FETCH_OBJ)){
      return $client;
    }
    
    return false;

  }

  public function getClientId($bot_id)
  {
    $sql = "SELECT id FROM clientes WHERE bot_id = $bot_id";
    $stmt = $this->db->query($sql);

    while($client = $stmt->fetch(\PDO::FETCH_OBJ)){
      return $client->id;
    }

    return false;
  }

  public function setConfigValue($dados)
  {

    $id_integracao = $dados['integracao_id'];
    $client_id = $dados['client_id'];
    $id_config = $dados['id_configuracao'];
    $campo = $dados['campo'];
    $valor = $dados['valor'];

    $sql = "INSERT INTO integracoes_cliente (
	integracao_id, 
	cliente_id, 
	id_configuracao, 
	campo, 
	valor
    ) VALUES (
	" . $id_integracao . ", 
	" . $client_id .", 
	" . $id_config . ", 
       '" . $campo . "', 
       '" . $valor . "'
    )";

    file_put_contents(__DIR__ . '/query.txt', $sql);

    $stmt = $this->db->prepare($sql);
    
    if($stmt->execute()){
      return true;
    } 

    return false;
  }

  public function getGroupIdTelegram($groupName_base64)
  {

    $titleGroup = base64_decode($groupName_base64);
    
    $sql = "SELECT chat_id FROM grupos WHERE title LIKE '%" . utf8_decode($titleGroup) . "%'";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    
    while($group = $stmt->fetch(\PDO::FETCH_OBJ)){
      return $group->chat_id;
    }
    
    return false;

  }
  
  public function getGroupId($grupo_id_telegram)
  {
    
    $sql = "SELECT id FROM grupos WHERE chat_id = '$grupo_id_telegram'";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    
    while($group = $stmt->fetch(\PDO::FETCH_OBJ)){
      return $group->id;
    }
    
    return false;

  }

  public function listGroups($bot_id)
  {
    
    $sql = "SELECT G.* FROM grupos G INNER JOIN clientes C ON (C.id = G.user_id) WHERE C.bot_id = $bot_id";
    
    $stmt = $this->db->query($sql);
    
    $grupos = array();
    
    while($grupo = $stmt->fetch(\PDO::FETCH_OBJ)){
      $grupos[] = $grupo;
    }
    
    return $grupos;

  }
  
    public function listIntegrations(){
    
        $stmt = $this->db->query("select * from integracoes");
        
        while($integracao = $stmt->fetch(\PDO::FETCH_OBJ)){
          yield $integracao;
        }
        
        return false;
    }
    
    public function configIntegration($id){
    
        $sql = "SELECT 
        	i.id,
            c.id as id_configuracao,
            i.nome,
            c.campo,
            c.mensagem
        FROM 
        	integracoes i
        INNER JOIN 
        	configuracao_integracoes c on (c.integracao_id = i.id)
        WHERE i.id = {$id}";
        $stmt = $this->db->query($sql);
        
        $configs = array();
        
        while($config = $stmt->fetch(\PDO::FETCH_OBJ)){
            $configs[] = $config;
        }
        
        return $configs;
    }
  
  public function listSubscribers($group_id)
  {
    
    $sql = "SELECT 
        a.nome,
        a.email 
    FROM 
        assinantes a
    INNER JOIN 
        assinantes_grupos ag ON (ag.id_assinante = a.id)
    WHERE ag.id_grupo = $group_id";
    
    $stmt = $this->db->query($sql);
    
    while($assinante = $stmt->fetch(\PDO::FETCH_OBJ)){
      yield $assinante;
    }
    
    return false;

  }
  
  public function getSubscriber($chat_id)
  {

    $sql = "SELECT 
        * 
    FROM 
        assinantes a
    join assinantes_grupos ag on (ag.id_assinante = a.id)
    join grupos g on (g.id = ag.id_grupo)
    WHERE a.user_id = $chat_id";
    $stmt = $this->db->query($sql);
    
    while($subscriber = $stmt->fetch(\PDO::FETCH_OBJ)){
      yield $subscriber;
    }
    
    return false;

  }


}
