<?php
require_once "db.php";
if($_SERVER['HTTP_ORIGIN'] == "https://musus.wocat.xyz"){
    header("Access-Control-Allow-Origin: https://musus.wocat.xyz");
}else if($_SERVER['HTTP_ORIGIN'] == "http://localhost:8080"){
    header("Access-Control-Allow-Origin: http://localhost:8080");
}else if($_SERVER['HTTP_ORIGIN'] == "http://musus.wocat.xyz"){
    header("Access-Control-Allow-Origin: http://musus.wocat.xyz");
}
header("Access-Control-Allow-Headers: content-type");
header("Access-Control-Allow-Credentials: true");

session_start();

// Check user is loggedIn
if(isset($_SESSION["userId"]) && $_SESSION["userId"] != "" ){
    if($_SERVER["REQUEST_METHOD"] === "GET"){
        // Retrieve comments
        if(isset($_GET["userId"]) && $_GET["userId"] !== "" && !is_array($_GET["userId"])){
            // Get UserData
            try{
                $stmt = $conn->prepare("SELECT firstName,surname,username,email FROM users WHERE id = :userId");
        
                // Bind params
                $stmt->bindParam(":userId", $_GET["userId"]);
        
                // Execute
                $stmt->execute();
                // Get data from query
                $results = $stmt->fetch(PDO::FETCH_ASSOC);
        
                $response["status"] = 200;
                $response["data"] = $results;
                echo json_encode($response);
            }catch(PDOException $e){
                $response["status"] = 500;
                echo json_encode($response);
            }
        }
    }else if($_SERVER["REQUEST_METHOD"] === "POST"){
        if(isset($_POST["action"]) && $_POST["action"] == "follow" && !is_array($_POST["action"])){
            if(isset($_POST["userId"]) && $_POST["userId"] != "" && !is_array($_POST["userId"])){
                if($_POST["userId"] != $_SESSION["userId"]){
                    // We don't have to check if user is currently followed, the check is done in the DB
                    try{
                        $stmt = $conn->prepare("INSERT INTO usersFollowers (followedUser, followerId) VALUES (:followedUser, :followedId)");
                
                        // Bind params
                        $stmt->bindParam(":followedUser", $_POST["userId"]);
                        $stmt->bindParam(":followedId", $_SESSION["userId"]);

                        // Execute
                        if($stmt->execute()){
                            $response["status"] = 200;
                            echo json_encode($response);
                        }else{
                            $response["status"] = 500;
                            echo json_encode($response);
                        }
                    }catch(PDOException $e){
                        $response["status"] = 500;
                        echo json_encode($response);
                    }
                }
            }
        } else if(isset($_POST["action"]) && $_POST["action"] == "unfollow" && !is_array($_POST["action"])){
            if(isset($_POST["userId"]) && $_POST["userId"] != "" && !is_array($_POST["userId"])){
                if($_POST["userId"] != $_SESSION["userId"]){
                    try{
                        $stmt = $conn->prepare("DELETE FROM usersFollowers WHERE followedUser = :followedUser AND followerId = :followerId");
                
                        // Bind params
                        $stmt->bindParam(":followedUser", $_POST["userId"]);
                        $stmt->bindParam(":followerId", $_SESSION["userId"]);

                        // Execute
                        if($stmt->execute()){
                            $response["status"] = 200;
                            echo json_encode($response);
                        }else{
                            $response["status"] = 500;
                            echo json_encode($response);
                        }
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