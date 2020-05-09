<?php
require_once "db.php";
header("Access-Control-Allow-Origin: http://localhost:8082");
header("Access-Control-Allow-Headers: content-type");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, DELETE");

// User must be logged in
session_start();

if(!isset($_SESSION["userId"])){
    die();
}

if($_SESSION["userId"] === ""){
    die();
}

if($_SERVER["REQUEST_METHOD"] === "POST"){
    $uploadDir = "uploads";
    // Checks required parameters
    if(isset($_POST["title"]) && isset($_POST["description"]) && isset($_POST["tags"]) && isset($_POST["visibility"])){
        // Check data is not empty
        if($_POST["title"] != "" && $_POST["description"] != "" && $_POST["tags"] != "" && $_POST["visibility"] != ""){
            // Check not arrays
            if(!is_array($_POST["title"]) && !is_array($_POST["description"]) && !is_array($_POST["tags"]) && !is_array($_POST["visibility"])){
                // Decide type of post
                header('Content-Type: application/json');
                if(!isset($_POST["url"])){
                    if(isset($_FILES["file"])){
                        // Uploading file
                        $isExternal = 0;
                        if($_FILES["file"]["size"] < 2000000 && file_exists($_FILES["file"]["tmp_name"])){
                            $FileExtension = strtolower(pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION));
                            $check = getimagesize($_FILES["file"]["tmp_name"]);

                            // File appears to be an image and valid extension
                            if($check !== false && ($FileExtension === "jpeg" || $FileExtension === "png") ){
                                $RandomID = bin2hex(random_bytes(32));  //TODO: Check for duplicates
                                $FileName = "$uploadDir/$RandomID.$FileExtension";

                                if(move_uploaded_file($_FILES["file"]["tmp_name"], $FileName)){
                                    if(isset($_POST["pictureId"]) && $_POST["pictureId"] !== "" && !is_array($_POST["pictureId"])){
                                        // Updating current post

                                        // Verify ownership of the post
                                        try{
                                            $conn->beginTransaction(); // Begin transaction
                                            $stmt = $conn->prepare("SELECT ownerId,URL,isExternal FROM pictures WHERE id = :pictureId");

                                            // Bind params
                                            $stmt->bindParam(":pictureId", $_POST["pictureId"]);
                                    
                                            // Execute
                                            $stmt->execute();
                                            // Get data from query
                                            $resultsOwnerShip = $stmt->fetch(PDO::FETCH_ASSOC);
    
                                            if($resultsOwnerShip["ownerId"] == $_SESSION["userId"]){             
                                                // User is owner
                                                // Update post
                                                $stmt = $conn->prepare("UPDATE pictures SET title = :title, description = :description, isExternal = :isExternal, URL = :URL, visibility = :visibility WHERE id = :pictureId");
                                
                                                // Bind params
                                                $stmt->bindParam(":title", $_POST["title"]);
                                                $stmt->bindParam(":description", $_POST["description"]);
                                                $stmt->bindParam(":isExternal", $isExternal);
                                                $stmt->bindParam(":URL", $FileName);
                                                $stmt->bindParam(":pictureId", $_POST["pictureId"]);
                                                
                                                $isPublic = 0;
                                                if($_POST["visibility"] === "Public"){
                                                    $isPublic = 1;
                                                }
                                                $stmt->bindParam(":visibility", $isPublic);
                                                
                                                // Execute
                                                $stmt->execute();

                                                // Get available tags
                                                $stmtTags = $conn->prepare("SELECT id,tag FROM tags");
                                                // Execute
                                                $stmtTags->execute();
                                                // Get data from query
                                                $availableTags = $stmtTags->fetchAll(PDO::FETCH_ASSOC);

                                                $wantedTags = explode(",", $_POST["tags"]);

                                                // Get current tags
                                                $stmtCurrentTags = $conn->prepare("SELECT tag FROM pictureTags INNER JOIN tags ON pictureTags.tagId = tags.id WHERE pictureId = :pictureId");
                                                $stmtCurrentTags->bindParam(":pictureId", $_POST["pictureId"]);
                                                // Execute
                                                $stmtCurrentTags->execute();
                                                // Get data from query
                                                $CurrentTags = $stmtCurrentTags->fetchAll(PDO::FETCH_ASSOC);
                                                
                                                foreach($CurrentTags as $tmpTag){
                                                    foreach($wantedTags as &$updateTag){ // Reference to modify it
                                                        if(strtoupper($tmpTag["tag"]) == strtoupper($updateTag)){
                                                            $updateTag = "";
                                                        }
                                                    }
                                                }
                                                
                                                foreach($availableTags as $currentTag){
                                                    foreach($wantedTags as &$wantedTag){ // Reference to modify it
                                                        if(strtoupper($currentTag["tag"]) == strtoupper($wantedTag)){
                                                            $stmtTag = $conn->prepare("INSERT INTO pictureTags (pictureId, tagId) VALUES (:pictureId, :tagId)");

                                                            $stmtTag->bindParam(":pictureId", $_POST["pictureId"]);
                                                            $stmtTag->bindParam(":tagId", $currentTag["id"]);

                                                            $stmtTag->execute();

                                                            // Tag found
                                                            $wantedTag = "";
                                                        }
                                                    }
                                                }

                                                // Create new tag for these
                                                foreach($wantedTags as $toCreateTag){
                                                    if($toCreateTag != ""){
                                                        // Create tag
                                                        $stmtTag = $conn->prepare("INSERT INTO tags (tag) VALUES (:tagName)");

                                                        $stmtTag->bindParam(":tagName", $toCreateTag);

                                                        $stmtTag->execute();
                                                        $tagId = $conn->lastInsertId();

                                                        // Asign tag to post
                                                        $stmtTag = $conn->prepare("INSERT INTO pictureTags (pictureId, tagId) VALUES (:pictureId, :tagId)");

                                                        $stmtTag->bindParam(":pictureId", $_POST["pictureId"]);
                                                        $stmtTag->bindParam(":tagId", $tagId);

                                                        $stmtTag->execute();
                                                    }
                                                }
                            
                                                if($resultsOwnerShip["isExternal"] == 0){
                                                    // Delete old file only if local file
                                                    unlink($resultsOwnerShip["URL"]);
                                                }

                                                // Done, return post ID
                                                $response["status"] = 200;
                                                $conn->commit();

                                                echo json_encode($response);
                                            }else{
                                                // User is not the owner
                                                $response["status"] = 500;
                                                unlink($FileName); // Delete uploaded file
                                                echo json_encode($response);
                                            }
                                        }catch(PDOException $e){
                                                $response["status"] = 500;
                                                // Delete uploaded file
                                                unlink($FileName);
                                                $conn->rollback();
                                                echo json_encode($response);
                                        }
                                    }else{
                                        // New post
                                        try{
                                            $conn->beginTransaction(); // Begin transaction
                                            $stmt = $conn->prepare("INSERT INTO pictures (ownerId, title, description, isExternal, URL, visibility) VALUES (:ownerId, :title, :description, :isExternal, :URL, :visibility)");
                            
                                            // Bind params
                                            $stmt->bindParam(":ownerId", $_SESSION["userId"]);
                                            $stmt->bindParam(":title", $_POST["title"]);
                                            $stmt->bindParam(":description", $_POST["description"]);
                                            $stmt->bindParam(":isExternal", $isExternal);
                                            $stmt->bindParam(":URL", $FileName);

                                            $isPublic = 0;
                                            if($_POST["visibility"] === "Public"){
                                                $isPublic = 1;
                                            }
                                            $stmt->bindParam(":visibility", $isPublic);
                                            
                                            // Execute
                                            $stmt->execute();
                        
                                            $postId = $conn->lastInsertId(); // Per connection, no race condition

                                            // Get available tags
                                            $stmtTags = $conn->prepare("SELECT id,tag FROM tags");
                                            // Execute
                                            $stmtTags->execute();
                                            // Get data from query
                                            $availableTags = $stmtTags->fetchAll(PDO::FETCH_ASSOC);
    
                                            $wantedTags = explode(",", $_POST["tags"]);
                                           
                                            foreach($availableTags as $currentTag){
                                                foreach($wantedTags as &$wantedTag){ // Reference to modify it
                                                    if(strtoupper($currentTag["tag"]) == strtoupper($wantedTag)){
                                                        $stmtTag = $conn->prepare("INSERT INTO pictureTags (pictureId, tagId) VALUES (:pictureId, :tagId)");

                                                        $stmtTag->bindParam(":pictureId", $postId);
                                                        $stmtTag->bindParam(":tagId", $currentTag["id"]);

                                                        $stmtTag->execute();

                                                        // Tag found
                                                        $wantedTag = "";
                                                    }
                                                }
                                            }

                                            // Create new tag for these
                                            foreach($wantedTags as $toCreateTag){
                                                if($toCreateTag != ""){
                                                    // Create tag
                                                    $stmtTag = $conn->prepare("INSERT INTO tags (tag) VALUES (:tagName)");

                                                    $stmtTag->bindParam(":tagName", $toCreateTag);

                                                    $stmtTag->execute();
                                                    $tagId = $conn->lastInsertId();

                                                    // Asign tag to post
                                                    $stmtTag = $conn->prepare("INSERT INTO pictureTags (pictureId, tagId) VALUES (:pictureId, :tagId)");

                                                    $stmtTag->bindParam(":pictureId", $postId);
                                                    $stmtTag->bindParam(":tagId", $tagId);

                                                    $stmtTag->execute();
                                                }
                                            }

                                            $conn->commit();
                                            $response["postId"] = $postId;
                                            $response["status"] = 200;
                                            echo json_encode($response);
                                        }catch(PDOException $e){
                                            $response["status"] = 500;
                                            // Delete uploaded file
                                            echo $e->getMessage();
                                            unlink($FileName);
                                            $conn->rollback();
                                            echo json_encode($response);
                                        }
                                    }
                                }
                            }
                        }else{
                            // File to large
                            $response["status"] = 413;
                            echo json_encode($response);
                        }
                    }else{
                        // No file change, only update data
                        // Verify ownership of the post
                        try{
                            $conn->beginTransaction(); // Begin transaction
                            $stmt = $conn->prepare("SELECT ownerId FROM pictures WHERE id = :pictureId");

                            // Bind params
                            $stmt->bindParam(":pictureId", $_POST["pictureId"]);
                    
                            // Execute
                            $stmt->execute();
                            // Get data from query
                            $resultsOwnerShip = $stmt->fetch(PDO::FETCH_ASSOC);
    
                            if($resultsOwnerShip["ownerId"] == $_SESSION["userId"]){     
                                // User is owner
                                $stmt = $conn->prepare("UPDATE pictures SET title = :title, description = :description, visibility = :visibility WHERE id = :pictureId");
                                
                                // Bind params
                                $stmt->bindParam(":title", $_POST["title"]);
                                $stmt->bindParam(":description", $_POST["description"]);
                                $stmt->bindParam(":pictureId", $_POST["pictureId"]);
                                
                                $isPublic = 0;
                                if($_POST["visibility"] === "Public"){
                                    $isPublic = 1;
                                }
                                $stmt->bindParam(":visibility", $isPublic);
                                
                                // Execute
                                $stmt->execute();

                                // Get available tags
                                $stmtTags = $conn->prepare("SELECT id,tag FROM tags");
                                // Execute
                                $stmtTags->execute();
                                // Get data from query
                                $availableTags = $stmtTags->fetchAll(PDO::FETCH_ASSOC);

                                $wantedTags = explode(",", $_POST["tags"]);

                                // Get current tags
                                $stmtCurrentTags = $conn->prepare("SELECT tag FROM pictureTags INNER JOIN tags ON pictureTags.tagId = tags.id WHERE pictureId = :pictureId");
                                $stmtCurrentTags->bindParam(":pictureId", $_POST["pictureId"]);
                                // Execute
                                $stmtCurrentTags->execute();
                                // Get data from query
                                $CurrentTags = $stmtCurrentTags->fetchAll(PDO::FETCH_ASSOC);
                                
                                foreach($CurrentTags as $tmpTag){
                                    foreach($wantedTags as &$updateTag){ // Reference to modify it
                                        if(strtoupper($tmpTag["tag"]) == strtoupper($updateTag)){
                                            $updateTag = "";
                                        }
                                    }
                                }
                                
                                foreach($availableTags as $currentTag){
                                    foreach($wantedTags as &$wantedTag){ // Reference to modify it
                                        if(strtoupper($currentTag["tag"]) == strtoupper($wantedTag)){
                                            $stmtTag = $conn->prepare("INSERT INTO pictureTags (pictureId, tagId) VALUES (:pictureId, :tagId)");

                                            $stmtTag->bindParam(":pictureId", $_POST["pictureId"]);
                                            $stmtTag->bindParam(":tagId", $currentTag["id"]);

                                            $stmtTag->execute();

                                            // Tag found
                                            $wantedTag = "";
                                        }
                                    }
                                }

                                // Create new tag for these
                                foreach($wantedTags as $toCreateTag){
                                    if($toCreateTag != ""){
                                        // Create tag
                                        $stmtTag = $conn->prepare("INSERT INTO tags (tag) VALUES (:tagName)");

                                        $stmtTag->bindParam(":tagName", $toCreateTag);

                                        $stmtTag->execute();
                                        $tagId = $conn->lastInsertId();

                                        // Asign tag to post
                                        $stmtTag = $conn->prepare("INSERT INTO pictureTags (pictureId, tagId) VALUES (:pictureId, :tagId)");

                                        $stmtTag->bindParam(":pictureId", $_POST["pictureId"]);
                                        $stmtTag->bindParam(":tagId", $tagId);

                                        $stmtTag->execute();
                                    }
                                }

                                // Done, return post ID
                                $response["status"] = 200;
                                $conn->commit();
                                echo json_encode($response);
                            }else{
                                // User is not the owner
                                $response["status"] = 500;
                                echo json_encode($response);
                            }
                        }catch(PDOException $e){
                            $response["status"] = 500;
                            $conn->rollback();
                            echo json_encode($response);
                        }
                    }
                }else if(!isset($_FILES["file"]) && isset($_POST["url"]) && $_POST["url"] != "" && !is_array($_POST["url"])){
                    // Uploading url
                    // Set response header
                    $isExternal = 1;
                    // Insert in DB
                    try{
                        $conn->beginTransaction(); // Begin transaction
                        // Maybe we are updating
                        if(isset($_POST["pictureId"]) && $_POST["pictureId"] !== "" && !is_array($_POST["pictureId"])){
                            // Verify ownership of the post
                            $stmt = $conn->prepare("SELECT ownerId FROM pictures WHERE id = :pictureId");

                            // Bind params
                            $stmt->bindParam(":pictureId", $_POST["pictureId"]);
                    
                            // Execute
                            $stmt->execute();
                            // Get data from query
                            $results = $stmt->fetch(PDO::FETCH_ASSOC);

                            if($results["ownerId"] == $_SESSION["userId"]){     
                                $stmt = $conn->prepare("UPDATE pictures SET title = :title, description = :description, isExternal = :isExternal, URL = :URL, visibility = :visibility WHERE id = :pictureId");
                        
                                // Bind params
                                $stmt->bindParam(":title", $_POST["title"]);
                                $stmt->bindParam(":description", $_POST["description"]);
                                $stmt->bindParam(":isExternal", $isExternal);
                                $stmt->bindParam(":URL", $_POST["url"]);
                                $stmt->bindParam(":pictureId", $_POST["pictureId"]);

                                $isPublic = 0;
                                if($_POST["visibility"] === "Public"){
                                    $isPublic = 1;
                                }
                                $stmt->bindParam(":visibility", $isPublic);
        
                                // Execute
                                $stmt->execute();

                                // Get available tags
                                $stmtTags = $conn->prepare("SELECT id,tag FROM tags");
                                // Execute
                                $stmtTags->execute();
                                // Get data from query
                                $availableTags = $stmtTags->fetchAll(PDO::FETCH_ASSOC);

                                $wantedTags = explode(",", $_POST["tags"]);

                                // Get current tags
                                $stmtCurrentTags = $conn->prepare("SELECT tag FROM pictureTags INNER JOIN tags ON pictureTags.tagId = tags.id WHERE pictureId = :pictureId");
                                $stmtCurrentTags->bindParam(":pictureId", $_POST["pictureId"]);
                                // Execute
                                $stmtCurrentTags->execute();
                                // Get data from query
                                $CurrentTags = $stmtCurrentTags->fetchAll(PDO::FETCH_ASSOC);
                                
                                foreach($CurrentTags as $tmpTag){
                                    foreach($wantedTags as &$updateTag){ // Reference to modify it
                                        if(strtoupper($tmpTag["tag"]) == strtoupper($updateTag)){
                                            $updateTag = "";
                                        }
                                    }
                                }
                                
                                foreach($availableTags as $currentTag){
                                    foreach($wantedTags as &$wantedTag){ // Reference to modify it
                                        if(strtoupper($currentTag["tag"]) == strtoupper($wantedTag)){
                                            $stmtTag = $conn->prepare("INSERT INTO pictureTags (pictureId, tagId) VALUES (:pictureId, :tagId)");

                                            $stmtTag->bindParam(":pictureId", $_POST["pictureId"]);
                                            $stmtTag->bindParam(":tagId", $currentTag["id"]);

                                            $stmtTag->execute();

                                            // Tag found
                                            $wantedTag = "";
                                        }
                                    }
                                }

                                // Create new tag for these
                                foreach($wantedTags as $toCreateTag){
                                    if($toCreateTag != ""){
                                        // Create tag
                                        $stmtTag = $conn->prepare("INSERT INTO tags (tag) VALUES (:tagName)");

                                        $stmtTag->bindParam(":tagName", $toCreateTag);

                                        $stmtTag->execute();
                                        $tagId = $conn->lastInsertId();

                                        // Asign tag to post
                                        $stmtTag = $conn->prepare("INSERT INTO pictureTags (pictureId, tagId) VALUES (:pictureId, :tagId)");

                                        $stmtTag->bindParam(":pictureId", $_POST["pictureId"]);
                                        $stmtTag->bindParam(":tagId", $tagId);

                                        $stmtTag->execute();
                                    }
                                }
            
                                // Done, return post ID
                                $response["status"] = 200;
                                $conn->commit();
                                echo json_encode($response);
                            }else{
                                // User is not the owner
                                $response["status"] = 500;
                                echo json_encode($response);
                            }
                        }else{
                            $stmt = $conn->prepare("INSERT INTO pictures (ownerId, title, description, isExternal, URL, visibility) VALUES (:ownerId, :title, :description, :isExternal, :URL, :visibility)");
                    
                            // Bind params
                            $stmt->bindParam(":ownerId", $_SESSION["userId"]);
                            $stmt->bindParam(":title", $_POST["title"]);
                            $stmt->bindParam(":description", $_POST["description"]);
                            $stmt->bindParam(":isExternal", $isExternal);
                            $stmt->bindParam(":URL", $_POST["url"]);
    
                            $isPublic = 0;
                            if($_POST["visibility"] === "Public"){
                                $isPublic = 1;
                            }
                            $stmt->bindParam(":visibility", $isPublic);
    
                            // Execute
                            $stmt->execute();
        
                            // Done, return post ID
                            $postId = $conn->lastInsertId(); // Per connection, no race condition

                            // Get available tags
                            $stmtTags = $conn->prepare("SELECT id,tag FROM tags");
                            // Execute
                            $stmtTags->execute();
                            // Get data from query
                            $availableTags = $stmtTags->fetchAll(PDO::FETCH_ASSOC);

                            $wantedTags = explode(",", $_POST["tags"]);
                            
                            foreach($availableTags as $currentTag){
                                foreach($wantedTags as &$wantedTag){ // Reference to modify it
                                    if(strtoupper($currentTag["tag"]) == strtoupper($wantedTag)){
                                        $stmtTag = $conn->prepare("INSERT INTO pictureTags (pictureId, tagId) VALUES (:pictureId, :tagId)");

                                        $stmtTag->bindParam(":pictureId", $postId);
                                        $stmtTag->bindParam(":tagId", $currentTag["id"]);

                                        $stmtTag->execute();

                                        // Tag found
                                        $wantedTag = "";
                                    }
                                }
                            }

                            // Create new tag for these
                            foreach($wantedTags as $toCreateTag){
                                if($toCreateTag != ""){
                                    // Create tag
                                    $stmtTag = $conn->prepare("INSERT INTO tags (tag) VALUES (:tagName)");

                                    $stmtTag->bindParam(":tagName", $toCreateTag);

                                    $stmtTag->execute();
                                    $tagId = $conn->lastInsertId();

                                    // Asign tag to post
                                    $stmtTag = $conn->prepare("INSERT INTO pictureTags (pictureId, tagId) VALUES (:pictureId, :tagId)");

                                    $stmtTag->bindParam(":pictureId", $postId);
                                    $stmtTag->bindParam(":tagId", $tagId);

                                    $stmtTag->execute();
                                }
                            }

                            $response["status"] = 200;
                            $conn->commit();
                            $response["postId"] = $postId;
                            echo json_encode($response);
                        }
                    }catch(PDOException $e){
                        $response["status"] = 500;
                        echo $e->getMessage();
                        $conn->rollback();
                        echo json_encode($response);
                    }
                }
            }
        }
    }
} else if($_SERVER["REQUEST_METHOD"] === "DELETE"){
    $rawBody = file_get_contents('php://input');
    $postData = json_decode($rawBody);
    if(isset($postData->pictureId) && $postData->pictureId !== "" && !is_array($postData->pictureId)){
        // Check if user is owner
        try{
            $conn->beginTransaction(); // Begin transaction
            $stmt = $conn->prepare("SELECT ownerId,URL,isExternal FROM pictures WHERE id = :pictureId");

            // Bind params
            $stmt->bindParam(":pictureId", $postData->pictureId);
    
            // Execute
            $stmt->execute();
            // Get data from query
            $resultsOwnerShip = $stmt->fetch(PDO::FETCH_ASSOC);

            if($resultsOwnerShip["ownerId"] == $_SESSION["userId"]){             
                // Can't delete if the post has comments
                $stmt = $conn->prepare("SELECT count(*) FROM pictureComments INNER JOIN users ON pictureComments.ownerId = users.id WHERE pictureComments.pictureId = :pictureId");
    
                // Bind params
                $stmt->bindParam(":pictureId", $postData->pictureId);
        
                // Execute
                $stmt->execute();

                if($stmt->fetchColumn() !== 0){
                    $response["status"] = 409; // Notify user, post has comments
                    echo json_encode($response); 
                }else{
                    // Check if we have to delete the file from disk
                    $stmt = $conn->prepare("SELECT isExternal,URL FROM pictures WHERE pictures.id = :pictureId");
        
                    // Bind params
                    $stmt->bindParam(":pictureId", $postData->pictureId);
            
                    // Execute
                    $stmt->execute();
                    // Get data from query
                    $pictureData = $stmt->fetch(PDO::FETCH_ASSOC);

                    // Delete associated tags, save before for latter use
                    $stmtTags = $conn->prepare("SELECT pictureTags.tagId as id FROM pictureTags WHERE pictureTags.pictureId = :pictureId");
                    $stmtTags->bindParam(":pictureId", $postData->pictureId);
                    // Execute
                    $stmtTags->execute();
                    // Get data from query
                    $pictureTags = $stmtTags->fetchAll(PDO::FETCH_ASSOC);

                    $stmtTags = $conn->prepare("DELETE FROM pictureTags WHERE pictureId = :pictureId");
                    $stmtTags->bindParam(":pictureId", $postData->pictureId);
                    // Execute
                    $stmtTags->execute();

                    // Delete votes
                    $stmtVotes = $conn->prepare("DELETE FROM pictureVotes WHERE pictureId = :pictureId");
                    $stmtVotes->bindParam(":pictureId", $postData->pictureId);
                    // Execute
                    $stmtVotes->execute();

                    // Delete post
                    $stmt = $conn->prepare("DELETE FROM pictures WHERE id = :pictureId");

                    // Bind params
                    $stmt->bindParam(":pictureId", $postData->pictureId);

                    // Execute
                    if($stmt->execute()){
                        // Check ocurrences in pictureTags per tag
                        foreach($pictureTags as $tag){
                            $stmtTags = $conn->prepare("SELECT count(*) FROM pictureTags WHERE tagId = :tagId");
                            $stmtTags->bindParam(":tagId", $tag["id"]);
                            $stmtTags->execute();

                            if($stmtTags->fetchColumn() === 0){
                                // Delete tag
                                $stmtTagDelete = $conn->prepare("DELETE FROM tags WHERE id = :tagId");

                                // Bind params
                                $stmtTagDelete->bindParam(":tagId", $tag["id"]);
                                $stmtTagDelete->execute();
                            }
                        }

                        $response["status"] = 200;
                        $conn->commit();
                        // If file was local delete the file from disk
                        if($pictureData["isExternal"] == 0){
                            // Unlink file from disk
                            unlink($pictureData["URL"]);
                        }
                        echo json_encode($response);
                    }else{
                        $response["status"] = 500;
                        echo json_encode($response); 
                    }
                }
            }else{
                // User is not the owner
                $response["status"] = 500;
                echo json_encode($response);
            }
        }catch(PDOException $e){
                $response["status"] = 500;
                $conn->rollback();
                echo json_encode($response);
        }
    }
}

?>