<?php
//Backend
// we must respond to a POST request 
// It reads the json from request , perfroms the insert into tea_products table
// returns the json response indicating success

//Frontend
// add a fetch call to the new api endpoint add_product.php
// after the fetch you will call loadTeaProducts() to update the new db to front end


//satisfy the config.php requirements
define('TEA_APP_ACCESS', true);

//connect to DB
require_once '../config.php';

//set the content header to return jSON
header('Content-Type: application/json');

//Endpoint with RESTful API design to accept POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.' ] );
    exit;

}

//read the raw JSON for request body
$json_data = file_get_contents('php://input');

//decode the JSON into PHP associative array
$data = json_decode($json_data, true);

//-Validation-
// must validate on the server 
if (
    !isset($data['name']) || empty(trim($data['name'])) ||
    !isset($data['vendor_cost']) || !is_numeric($data['vendor_cost']) ||
    !isset($data['selling_price']) || !is_numeric($data['selling_price'])
) {
    http_response_code(400) ;
    echo json_encode(['status' => 'error', 'message' => 'Invalid input. Please provide name, vendor_cost and selling_price ']);
    exit;
}

//sanitize the input 
$name = trim($data['name']);
$description = isset($data['description']) ? trim($data['description']) : '';
$vendor_cost = (float)$data['vendor_cost'];
$selling_price = (float)$data['selling_price'];

// Database Work
try {
    $db = DatabaseConnection::getInstance()->getConnection();

    //Sql quesry with place holders (?)
    $sql = "INSERT INTO tea_products (name , description , vendor_cost, selling_price) VALUES (?,?,?,?)";

    // sending the query to database engine
    $stmt = $db ->prepare($sql);
    $stmt -> execute([$name,$description, $vendor_cost,$selling_price]);

    // show query was succesfull
    http_response_code(201) ;
    echo json_encode(['status' => 'success', 'message ' => 'Tea product added successfully.']);

} catch (PDOException $e) {
    // This is for errors in database management
    http_response_code(500) ;
    echo json_encode(['status' => 'error', 'message ' => 'Database error:' . $e->getMessage()]);


}
?>