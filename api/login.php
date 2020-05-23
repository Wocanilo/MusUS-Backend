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

if($_SERVER["REQUEST_METHOD"] === "POST"){
    // JSON DECODE
    $rawBody = file_get_contents('php://input');
    $postData = json_decode($rawBody);
    // Check that parameters are set
    if(isset($postData->username) && isset($postData->password)){
        // Check data is not empty
        if($postData->username !== "" && $postData->password){
            // Check not arrays
            if(!is_array($postData->username) && !is_array($postData->password)){
                    // Set content type header
                    header('Content-Type: application/json');
                    try{
                        $stmt = $conn->prepare("SELECT id,username,password,firstName,surname,email FROM users WHERE username=:username LIMIT 1");
        
                        // Bind params
                        $stmt->bindParam(":username", $postData->username);
    
                        // Execute
                        $stmt->execute();
                        // Get data from query
                        $userData = $stmt->fetch();

                        // No data returned
                        if($userData === false){
                            $response["status"] = 500;
                        }else{
                            // Check password
                            if(password_verify($postData->password, $userData["password"])){
                                // Start session
                                session_start();

                                $_SESSION["username"] = $userData["username"];
                                $_SESSION["userId"] = $userData["id"];
                                $_SESSION["firstName"] = $userData["firstName"];
                                $_SESSION["surname"] = $userData["surname"];
                                $_SESSION["email"] = $userData["email"];

                                // All OK
                                $user = array();
                                $user["username"] = $userData["username"];
                                $user["firstName"] = $userData["firstName"];
                                $user["surname"] = $userData["surname"];
                                $user["email"] = $userData["email"];
                                $user["userId"] = $userData["id"];

                                // Return following data
                                $stmt = $conn->prepare("SELECT followedUser as id FROM usersFollowers WHERE followerId=:userId");
        
                                // Bind params
                                $stmt->bindParam(":userId", $userData["id"]);
            
                                // Execute
                                $stmt->execute();
                                // Get data from query
                                $followers = $stmt->fetchAll(PDO::FETCH_COLUMN);

                                // Return votes
                                $stmt = $conn->prepare("SELECT pictureId as id,isPositive FROM pictureVotes WHERE ownerId=:userId");

                                // Bind params
                                $stmt->bindParam(":userId", $userData["id"]);
            
                                // Execute
                                $stmt->execute();
                                // Get data from query
                                $pictureVotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                $user["followedUsers"] = $followers;
                                $user["pictureVotes"] = $pictureVotes;
                                $response["userData"] = $user;
                                $response["status"] = 200;
                            }else{
                                // Invalid password
                                $response["status"] = 500;
                            }
                        }
                        echo json_encode($response);
                    }catch(PDOException $e){
                        $response["status"] = 500;
                        echo json_encode($response);
                    }
            }
        }
    }
}

?>