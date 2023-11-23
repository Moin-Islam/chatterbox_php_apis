<?php
include_once("../../config/Headers.php");
include_once('../../objects/User.php');

$user = new User($pdo);

if (isset($_GET["service"])) {
    $ServiceName = $_GET["service"];

    switch ($ServiceName) {
        case "create":
            $user->create();
            break;
        case "authentication":
            $user->authentication();
            break;
        case "activeusers":
            $user->getActiveUsers();
            break;
    }
}