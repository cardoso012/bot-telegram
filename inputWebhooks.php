<?php

$data = file_get_contents('php://input');
$fp = fopen(__DIR__ . '/webhook.txt', 'a+');
fwrite($fp, date('Y-m-d H:i:s') . ' - ' . $data . "\n\n");
fclose($fp);