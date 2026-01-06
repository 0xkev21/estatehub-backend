<?php

$host = 'localhost';
$user = 'root';
$password = 'Abc@1234';
$port = 3306;
$db = 'estatehub';

$con = new mysqli($host, $user, $password, $db, $port);
if($con->connect_error) {
  die(json_encode(["message"=>"Connection error"]));
}

?>