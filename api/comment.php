<?php
require_once "db.php";
if($_SERVER['HTTP_ORIGIN'] == "https://musus.wocat.xyz"){
        header("Access-Control-Allow-Origin: https://musus.wocat.xyz");
}else if($_SERVER['HTTP_ORIGIN'] == "http://localhost:8082"){
        header("Access-Control-Allow-Origin: http://localhost:8082");
}
header("Access-Control-Allow-Headers: content-type");
header("Access-Control-Allow-Credentials: true");

session_start();

// Check user is loggedIn
if(isset($_SESSION["userId"]) && $_SESSION["userId"] != "" ){
    if($_SERVER["REQUEST_METHOD"] === "POST"){
        // JSON DECODE
        $rawBody = file_get_contents('php://input');
        $postData = json_decode($rawBody);
        if(isset($postData->id) && isset($postData->comment)){
            // Check data is not empty
            if($postData->id !== "" && $postData->comment !== ""){
                // Set content type header
                header('Content-Type: application/json');
                    try{
                        // The DB checks for duplicates usernames/emails
                        $stmt = $conn->prepare("INSERT INTO pictureComments (pictureId, ownerId, comment) VALUES (:pictureId, :ownerId, :comment)");
        
                        // Bind params
                        $stmt->bindParam(":pictureId", $postData->id);
                        $stmt->bindParam(":ownerId", $_SESSION["userId"]);
                        $stmt->bindParam(":comment", $postData->comment);
    
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
    }else if($_SERVER["REQUEST_METHOD"] === "GET"){
        // Retrieve comments
        if(isset($_GET["pictureId"]) && $_GET["pictureId"] !== "" && !is_array($_GET["pictureId"])){
            try{
                // Check if post is public
                $stmt = $conn->prepare("SELECT ownerId,visibility FROM pictures WHERE id = :pictureId");
                $stmt->bindParam(":pictureId", $_GET["pictureId"]);
                $stmt->execute();
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

                var_dump($results);
                if($results["visibility"] === 0 && $results["userId"] != $_SESSION["userId"]){ // If post is private and user not owner don't show comments
                    $response["status"] = 503; // Forbbiden
                    die(json_encode($response));
                }

                $stmt = $conn->prepare("SELECT users.id as userId,pictureComments.id as commentId,username,comment FROM pictureComments INNER JOIN users ON pictureComments.ownerId = users.id WHERE pictureComments.pictureId = :pictureId");
        
                // Bind params
                $stmt->bindParam(":pictureId", $_GET["pictureId"]);
        
                // Execute
                $stmt->execute();
                // Get data from query
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
                $response["status"] = 200;
                $response["data"] = $results;
                echo json_encode($response);
            }catch(PDOException $e){
                $response["status"] = 500;
                echo json_encode($response);
            }
        }
        
    }
}


?>