<?php
include_once("../../config/Headers.php");
include_once('../../objects/Messages.php');

$message = new Messages($pdo);

if (isset($_GET["service"])){
    $ServiceName = $_GET["service"];

    switch ($ServiceName){
        case "SendMessage":
            $message->SendMessage();
            break;
        case "FetchUserMessage" :
            $message->FetchMessage();
            break;
        case "FetchLastMessage" :
            $message->FetchLastMsg();
            break;
    }
}