<?php
require_once "db.php";
if($_SERVER['HTTP_ORIGIN'] == "https://musus.wocat.xyz"){
        header("Access-Control-Allow-Origin: https://musus.wocat.xyz");
}else if($_SERVER['HTTP_ORIGIN'] == "http://localhost:8082"){
        header("Access-Control-Allow-Origin: http://localhost:8082");
}
header("Access-Control-Allow-Headers: content-type");
header("Access-Control-Allow-Credentials: true");

if($_SERVER["REQUEST_METHOD"] === "POST"){
    // JSON DECODE
    $rawBody = file_get_contents('php://input');
    $postData = json_decode($rawBody);
    if(isset($postData->username) && isset($postData->password) && isset($postData->repeatPassword) && isset($postData->firstName) && isset($postData->surname) && isset($postData->email)){
        // Check data is not empty
        if($postData->username !== "" && $postData->password !== "" && $postData->repeatPassword !== "" && $postData->firstName !== "" && $postData->surname !== "" && $postData->email !== ""){
            // Check not arrays
            if(!is_array($postData->username) && !is_array($postData->password) && !is_array($postData->repeatPassword) && !is_array($postData->firstName) && !is_array($postData->surname) && !is_array($postData->email)){
                $response = [];
                // Check passwords and email valid
                if($postData->password === $postData->repeatPassword && filter_var($postData->email, FILTER_VALIDATE_EMAIL)){
                    // Set content type header
                    header('Content-Type: application/json');
                    try{
                        // The DB checks for duplicates usernames/emails
                        $stmt = $conn->prepare("INSERT INTO users (firstName, surname, username, password, email) VALUES (:firstName, :surname, :username, :password, :email)");
        
                        // Bind params
                        $stmt->bindParam(":firstName", $postData->firstName);
                        $stmt->bindParam(":surname", $postData->surname);
                        $stmt->bindParam(":username", $postData->username);
                        $stmt->bindParam(":email", $postData->email);
                        // Saved hashed password, by default BCRYPT
                        $stmt->bindParam(":password", password_hash($postData->password, PASSWORD_BCRYPT));
    
                        // Execute
                        $stmt->execute();
    
                        // Done
                        $response["status"] = 200;
                        echo json_encode($response);
                    }catch(PDOException $e){
                        $response["status"] = 500;
                        echo json_encode($response);
                    }
                }
            }
        }
    }
}

?>