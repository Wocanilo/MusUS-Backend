<?php
require_once "db.php";
header("Access-Control-Allow-Origin: http://localhost:8082");
header("Access-Control-Allow-Headers: content-type");
header("Access-Control-Allow-Credentials: true");

if($_SERVER["REQUEST_METHOD"] === "GET"){
    $postsLimit = 18;

    session_start();
    // Set content type header
    header('Content-Type: application/json');

    if(isset($_SESSION["userId"]) && $_SESSION["userId"] != "" ){
        // Autenticated request, all data from post

        if(isset($_GET["userId"]) && !is_array($_GET["userId"]) && $_GET["userId"] !== ""){
            // Get Posts of user
            try{

                // Check if user requested is current user to check private posts
                if($_GET["userId"] == $_SESSION["userId"]){
                    $stmt = $conn->prepare("SELECT id,title,description,isExternal,URL,DATE_FORMAT(pictures.createdAt, '%d/%m/%Y') as createdAt FROM pictures WHERE ownerId = :userId");
                }else{
                    $stmt = $conn->prepare("SELECT id,title,description,isExternal,URL,DATE_FORMAT(pictures.createdAt, '%d/%m/%Y') as createdAt FROM pictures WHERE ownerId = :userId AND visibility = 1");
                }
        
                // Bind params
                $stmt->bindParam(":userId", $_GET["userId"]);
        
                // Execute
                $stmt->execute();
                // Get data from query
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // No data returned
                if($results === false){
                    $response["status"] = 500;
                }else{
                    $response["status"] = 200;
                }


                $response["data"] = $results;
                echo json_encode($response);
            }catch(PDOException $e){
                $response["status"] = 500;
                echo json_encode($response);
            }
        }else if(isset($_GET["pictureId"]) && !is_array($_GET["pictureId"]) && isset($_GET["action"]) && !is_array($_GET["action"]) && $_GET["action"] == "getPost"){
            try{
                $stmt = $conn->prepare("SELECT users.id as userId,title,description,isExternal,URL,DATE_FORMAT(pictures.createdAt, '%d/%m/%Y') as createdAt,username,visibility FROM pictures INNER JOIN users ON users.id = pictures.ownerId WHERE pictures.id = :pictureId");
        
                // Bind params
                $stmt->bindParam(":pictureId", $_GET["pictureId"]);
        
                // Execute
                $stmt->execute();
                // Get data from query
                $results = $stmt->fetch(PDO::FETCH_ASSOC);

                // No data returned
                if($results === false){
                    $response["status"] = 500;
                }else{
                    $response["status"] = 200;
                    // Get tags
                    $stmtTags = $conn->prepare("SELECT pictureTags.id as id,tag FROM pictureTags INNER JOIN tags ON pictureTags.tagId = tags.id WHERE pictureTags.pictureId = :pictureId");
                    $stmtTags->bindParam(":pictureId", $_GET["pictureId"]);
                    // Execute
                    $stmtTags->execute();
                    // Get data from query
                    $pictureTags = $stmtTags->fetchAll(PDO::FETCH_ASSOC);

                    // Get votes
                    $stmtVotes = $conn->prepare("SELECT count(*) FROM pictureVotes WHERE pictureId = :pictureId");
                    $stmtVotes->bindParam(":pictureId", $_GET["pictureId"]);
                    // Execute
                    $stmtVotes->execute();
                    // Get data from query
                    $pictureVotes = $stmtVotes->fetchColumn();
                }

                // If private picture only show if owner
                if($results["visibility"] == 0){
                    if($_SESSION["userId"] == $results["userId"]){
                        $response["data"] = $results;
                    }else{
                        $response["status"] = 403; // Forbidden
                    }
                }else{
                    $response["data"] = $results;
                    $response["data"]["tags"] = $pictureTags;
                    $response["data"]["votes"] = $pictureVotes;
                }
                
                echo json_encode($response);
            }catch(PDOException $e){
                $response["status"] = 500;
                echo $e->getMessage();
                echo json_encode($response);
            }
        }else if(isset($_GET["pictureId"]) && !is_array($_GET["pictureId"]) && isset($_GET["action"]) && !is_array($_GET["action"]) && $_GET["action"] == "votePost"){
            try{
                $stmt = $conn->prepare("INSERT INTO pictureVotes (pictureId, ownerId) VALUES (:pictureId, :ownerId)");
        
                // Bind params
                $stmt->bindParam(":pictureId", $_GET["pictureId"]);
                $stmt->bindParam(":ownerId", $_SESSION["userId"]);

                // Execute
                $stmt->execute();

                $response["status"] = 200;
                echo json_encode($response);
            }catch(PDOException $e){
                $response["status"] = 500;
                echo json_encode($response);
            }
        }else if(isset($_GET["pictureId"]) && !is_array($_GET["pictureId"]) && isset($_GET["action"]) && !is_array($_GET["action"]) && $_GET["action"] == "unVotePost"){
            try{
                $stmt = $conn->prepare("DELETE FROM pictureVotes WHERE ownerId = :ownerId AND pictureId = :pictureId");
        
                // Bind params
                $stmt->bindParam(":pictureId", $_GET["pictureId"]);
                $stmt->bindParam(":ownerId", $_SESSION["userId"]);
    
                // Execute
                $stmt->execute();
    
                $response["status"] = 200;
                echo json_encode($response);
            }catch(PDOException $e){
                $response["status"] = 500;
                echo json_encode($response);
            }
        }
        else if(isset($_GET["action"]) && !is_array($_GET["action"]) && $_GET["action"] == "followingPosts"){
            try{
                $stmt = $conn->prepare("SELECT id,title,description,isExternal,URL,DATE_FORMAT(createdAt, '%d/%m/%Y') as createdAt FROM pictures WHERE ownerId IN (SELECT followedUser FROM usersFollowers WHERE followerId = :userId) AND visibility = 1 LIMIT :limit");
        
                // Bind params
                $stmt->bindParam(":userId", $_SESSION["userId"]);
                $stmt->bindParam(":limit", $postsLimit);

                // Execute
                $stmt->execute();
                $pictureData = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $response["data"] = $pictureData;
                $response["status"] = 200;
                echo json_encode($response);
            }catch(PDOException $e){
                $response["status"] = 500;
                echo json_encode($response);
            }
        }
    }
    else{
        // Anonymous request
        try{
            $stmt = $conn->prepare("SELECT id,title,description,isExternal,URL,DATE_FORMAT(createdAt, '%d/%m/%Y') as createdAt FROM pictures WHERE visibility = 1 ORDER BY id DESC LIMIT :limit");
    
            // Bind params
            $stmt->bindParam(":limit", $postsLimit);
    
            // Execute
            $stmt->execute();
            // Get data from query
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
            // No data returned
            if($results === false){
                $response["status"] = 500;
            }else{
                $response["status"] = 200;

                foreach($results as &$picture){
                    // Get votes
                    $stmtVotes = $conn->prepare("SELECT count(*) FROM pictureVotes WHERE pictureId = :pictureId");
                    $stmtVotes->bindParam(":pictureId", $picture["id"]);
                    // Execute
                    $stmtVotes->execute();
                    // Get data from query
                    $picture["votes"] = $stmtVotes->fetchColumn();
                }

            }
            $response["data"] = $results;
            echo json_encode($response);
        }catch(PDOException $e){
            $response["status"] = 500;
            echo json_encode($response);
        }
    }
}

?>