<?php
//Backend
// we must respond to a DELETE request 
// It reads the json from request , perfroms the insert into tea_products table
// returns the json response indicating success

//Frontend
// add a fetch call to the new api endpoint delete_product.php
// after the fetch you will call loadTeaProducts() to update the new db to front end


define('TEA_APP_ACCESS', true);

require_once '../config.php';

header('Content-Type: application/json');

//Endpint with RESTful api design to accept POST request
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE'){
    http_response_code(400);
    echo json_encode(['status' => 'error' , 'message' => 'Invalid request method.']);
    exit;
}

//Read the Json for request body
$json_data = file_get_contents('php://input');

//decode into php array
$data = json_decode($json_data, true);

if (
    !isset($data['id']) || !is_numeric($data['id'])
) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid input. Please provide a valid ID']);
    exit;
}

//sanitize id
$id = (int)$data['id'];

try {
    $db = DatabaseConnection::getInstance()->getConnection();

    //sql query di delete database

    $sql = "DELETE FROM tea_products WHERE id = ? ";
    //sending query to db engine
    $stmt = $db -> prepare($sql);
    $stmt -> execute([$id]);

    http_response_code(200);
    echo json_encode(['status' => 'success' , 'message' => ' The product has succesfullly been deleted']);

} catch(PDOException $e) {

    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database errer:' .$e ->getMessage()]);
}