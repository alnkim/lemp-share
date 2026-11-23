<?php
header('Content-Type: application/json');
$status = [
    "status" => "OK",
    "timestamp" => date("Y-m-d H:i:s"),
    "service" => "Cosc 428 LEMP Backend"
];
try {
    $pdo = new PDO("mysql:host=mysql;dbname=appdb", "appuser", "apppass");
    $status["database"] = "Connected";
} catch (Exception $e) {
    $status["database"] = "Disconnected";
    $status["status"] = "ERROR";
}
echo json_encode($status);
?>