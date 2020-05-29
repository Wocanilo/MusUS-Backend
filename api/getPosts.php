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

                    foreach($results as $CurrentResultIndex => $CurrentResultValue){
                        // Get votes
                        $stmtVotes = $conn->prepare("SELECT (votes.totalEntries - votes.positive) as negative,votes.positive as positive FROM (SELECT pictureId,count(*) as totalEntries,sum(isPositive) as positive FROM pictureVotes GROUP BY pictureId) as votes WHERE votes.pictureId = :pictureId");
                        $stmtVotes->bindParam(":pictureId", $results[$CurrentResultIndex]["id"]);
                        // Execute
                        $stmtVotes->execute();
                        // Get data from query
                        $dataVotes = $stmtVotes->fetch();
                        if($dataVotes === false){
                            $results[$CurrentResultIndex]["negative"] = 0;
                            $results[$CurrentResultIndex]["positive"] = 0;
                        }else{
                            $results[$CurrentResultIndex]["negative"] = $dataVotes["negative"];
                            $results[$CurrentResultIndex]["positive"] = $dataVotes["positive"];
                        }
                    }
                    $response["data"] = $results;
                }

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
                    $stmtVotes = $conn->prepare("SELECT (votes.totalEntries - votes.positive) as negative,votes.positive as positive FROM (SELECT pictureId,count(*) as totalEntries,sum(isPositive) as positive FROM pictureVotes GROUP BY pictureId) as votes WHERE votes.pictureId = :pictureId");
                    $stmtVotes->bindParam(":pictureId", $_GET["pictureId"]);
                    // Execute
                    $stmtVotes->execute();
                    // Get data from query
                    $dataVotes = $stmtVotes->fetch();
                    if($dataVotes === false){
                        $results["negative"] = 0;
                        $results["positive"] = 0;
                    }else{
                        $results["negative"] = $dataVotes["negative"];
                        $results["positive"] = $dataVotes["positive"];
                    }

                }

                // If private picture only show if owner
                if($results["visibility"] == 0){
                    if($_SESSION["userId"] == $results["userId"]){
                        $response["data"] = $results;
                        $response["data"]["tags"] = $pictureTags;
                    }else{
                        $response["status"] = 403; // Forbidden
                    }
                }else{
                    $response["data"] = $results;
                    $response["data"]["tags"] = $pictureTags;
                }
                
                echo json_encode($response);
            }catch(PDOException $e){
                $response["status"] = 500;
                echo $e->getMessage();
                echo json_encode($response);
            }
        }else if(isset($_GET["pictureId"]) && !is_array($_GET["pictureId"]) && isset($_GET["action"]) && !is_array($_GET["action"]) && $_GET["action"] == "votePost" && isset($_GET["type"]) && !is_array($_GET["type"])){
            try{
                $stmt = $conn->prepare("INSERT INTO pictureVotes (pictureId, ownerId, isPositive) VALUES (:pictureId, :ownerId, :isPositive)");
        
                if($_GET["type"] == "positive"){
                    $voteType = 1;
                }else{
                    $voteType = 0;
                }

                // Bind params
                $stmt->bindParam(":pictureId", $_GET["pictureId"]);
                $stmt->bindParam(":ownerId", $_SESSION["userId"]);
                $stmt->bindParam(":isPositive", $voteType);

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
        else if(isset($_GET["action"]) && !is_array($_GET["action"]) && $_GET["action"] == "followingPosts" && isset($_GET["search"]) && !is_array($_GET["search"])){
            try{
                $stmt = $conn->prepare("SELECT id,title,description,isExternal,URL,DATE_FORMAT(createdAt, '%d/%m/%Y') as createdAt FROM pictures WHERE ownerId IN (SELECT followedUser FROM usersFollowers WHERE followerId = :userId) AND visibility = 1 AND title LIKE :search LIMIT :limit");

                // Bind params
                $searchTerm = "%".$_GET["search"]."%";
                $stmt->bindParam(":userId", $_SESSION["userId"]);
                $stmt->bindParam(":limit", $postsLimit);
                $stmt->bindParam(":search", $searchTerm);

                // Execute
                $stmt->execute();
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                // No data returned
                if($results === false){
                    $response["status"] = 500;
                }else{
                    $response["status"] = 200;

                    foreach($results as $CurrentResultIndex => $CurrentResultValue){
                        // Get votes
                        $stmtVotes = $conn->prepare("SELECT (votes.totalEntries - votes.positive) as negative,votes.positive as positive FROM (SELECT pictureId,count(*) as totalEntries,sum(isPositive) as positive FROM pictureVotes GROUP BY pictureId) as votes WHERE votes.pictureId = :pictureId");
                        $stmtVotes->bindParam(":pictureId", $results[$CurrentResultIndex]["id"]);
                        // Execute
                        $stmtVotes->execute();
                        // Get data from query
                        $dataVotes = $stmtVotes->fetch();
                        if($dataVotes === false){
                            $results[$CurrentResultIndex]["negative"] = 0;
                            $results[$CurrentResultIndex]["positive"] = 0;
                        }else{
                            $results[$CurrentResultIndex]["negative"] = $dataVotes["negative"];
                            $results[$CurrentResultIndex]["positive"] = $dataVotes["positive"];
                        }
                    }
                    $response["data"] = $results;
                }


                echo json_encode($response);
            }catch(PDOException $e){
                $response["status"] = 500;
                echo json_encode($response);
            }
        }
        else if(isset($_GET["action"]) && !is_array($_GET["action"]) && $_GET["action"] == "trendingPosts" && isset($_GET["search"]) && !is_array($_GET["search"])){
            try{
                $stmt = $conn->prepare("SELECT id,title,description,isExternal,URL,DATE_FORMAT(createdAt, '%d/%m/%Y') as createdAt,picturesPuntuation.positive,picturesPuntuation.negative FROM pictures INNER JOIN (SELECT data.pictureId,data.positive,data.negative, ((data.positive + 1.9208) / (data.positive + data.negative) -1.96 * SQRT((data.positive * data.negative) / (data.positive + data.negative) + 0.9604) /(data.positive + data.negative)) / (1 + 3.8416 / (data.positive + data.negative))AS ci_lower_bound FROM (SELECT votes.pictureId,(votes.totalEntries - votes.positive) as negative,votes.positive as positive FROM (SELECT pictureId,count(*) as totalEntries,sum(isPositive) as positive FROM pictureVotes GROUP BY pictureId) as votes) data WHERE data.positive + data.negative > 0) as picturesPuntuation ON pictures.id = picturesPuntuation.pictureId WHERE visibility = 1 AND createdAt >= (NOW() - INTERVAL 1 WEEK) AND title LIKE :search ORDER BY ci_lower_bound DESC LIMIT :limit");
        
                // Bind params
                $stmt->bindParam(":limit", $postsLimit);
                $searchTerm = "%".$_GET["search"]."%";
                $stmt->bindParam(":search", $searchTerm);

                // Execute
                $stmt->execute();
                $pictureData = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if($pictureData === false){
                    $response["status"] = 500;
                }else{
                    $response["status"] = 200;
                    $response["data"] = $pictureData;
                }


                echo json_encode($response);
            }catch(PDOException $e){
                $response["status"] = 500;
                echo json_encode($response);
            }
        } 
         else if(isset($_GET["action"]) && !is_array($_GET["action"]) && $_GET["action"] == "trendingUsers"){
            try{
                $stmt = $conn->prepare("SELECT DISTINCT firstname,surname,users.id,username,email,ci_lower_bound FROM users INNER JOIN (SELECT usersVotes.ownerId,((usersVotes.totalPositives + 1.9208) / (usersVotes.totalPositives + usersVotes.totalNegatives) -1.96 * SQRT((usersVotes.totalPositives * usersVotes.totalNegatives) / (usersVotes.totalPositives + usersVotes.totalNegatives) + 0.9604) /(usersVotes.totalPositives + usersVotes.totalNegatives)) / (1 + 3.8416 / (usersVotes.totalPositives + usersVotes.totalNegatives)) AS ci_lower_bound FROM (SELECT totalVotesPerUser.ownerId,totalVotesPerUser.totalPositives,totalVotesPerUser.totalNegatives FROM (SELECT ownerId,sum(data.positive) as totalPositives,sum(data.negative) as totalNegatives FROM (SELECT votes.pictureId,(votes.totalEntries - votes.positive) as negative,votes.positive as positive FROM (SELECT pictureId,count(*) as totalEntries,sum(isPositive) as positive FROM pictureVotes WHERE createdAt >= (NOW() - INTERVAL 1 WEEK) GROUP BY pictureId) as votes) as data INNER JOIN pictures ON pictureId = pictures.id WHERE data.positive + data.negative > 0 GROUP BY ownerId) as totalVotesPerUser) as usersVotes) UsersPuntuation ON UsersPuntuation.ownerId = users.id  ORDER BY ci_lower_bound DESC LIMIT :limit");
        
                // Bind params
                $stmt->bindParam(":limit", $postsLimit);

                // Execute
                $stmt->execute();
                $usersData = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if($usersData === false){
                    $response["status"] = 500;
                }else{
                    $response["status"] = 200;
                    $response["data"] = $usersData;
                }


                echo json_encode($response);
            }catch(PDOException $e){
                $response["status"] = 500;
                echo json_encode($response);
            }
        }
        else if(isset($_GET["action"]) && !is_array($_GET["action"]) && $_GET["action"] == "tagsPosts" && isset($_GET["search"]) && !is_array($_GET["search"])){
            try{
                $stmt = $conn->prepare("SELECT DISTINCT pictures.id,title,description,isExternal,URL,DATE_FORMAT(pictures.createdAt, '%d/%m/%Y') as createdAt FROM pictureTags INNER JOIN pictures ON pictures.id = pictureTags.pictureId WHERE tagId IN (SELECT id FROM tags WHERE tag LIKE :search) AND visibility = 1 LIMIT :limit");
        
                // Bind params
                $searchTerm = "%".$_GET["search"]."%";
                $stmt->bindParam(":search", $searchTerm);
                $stmt->bindParam(":limit", $postsLimit);

                // Execute
                $stmt->execute();
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // No data returned
                if($results === false){
                    $response["status"] = 500;
                }else{
                    $response["status"] = 200;

                    foreach($results as $CurrentResultIndex => $CurrentResultValue){
                        // Get votes
                        $stmtVotes = $conn->prepare("SELECT (votes.totalEntries - votes.positive) as negative,votes.positive as positive FROM (SELECT pictureId,count(*) as totalEntries,sum(isPositive) as positive FROM pictureVotes GROUP BY pictureId) as votes WHERE votes.pictureId = :pictureId");
                        $stmtVotes->bindParam(":pictureId", $results[$CurrentResultIndex]["id"]);
                        // Execute
                        $stmtVotes->execute();
                        // Get data from query
                        $dataVotes = $stmtVotes->fetch();
                        if($dataVotes === false){
                            $results[$CurrentResultIndex]["negative"] = 0;
                            $results[$CurrentResultIndex]["positive"] = 0;
                        }else{
                            $results[$CurrentResultIndex]["negative"] = $dataVotes["negative"];
                            $results[$CurrentResultIndex]["positive"] = $dataVotes["positive"];
                        }
                    }
                    $response["data"] = $results;
                }

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
            $stmt = $conn->prepare("SELECT id,title,description,isExternal,URL,DATE_FORMAT(createdAt, '%d/%m/%Y') as createdAt FROM pictures WHERE visibility = 1 AND createdAt >= (NOW() - INTERVAL 1 WEEK) ORDER BY id DESC LIMIT :limit");
    
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

                foreach($results as $CurrentResultIndex => $CurrentResultValue){
                    // Get votes
                    $stmtVotes = $conn->prepare("SELECT (votes.totalEntries - votes.positive) as negative,votes.positive as positive FROM (SELECT pictureId,count(*) as totalEntries,sum(isPositive) as positive FROM pictureVotes GROUP BY pictureId) as votes WHERE votes.pictureId = :pictureId");
                    $stmtVotes->bindParam(":pictureId", $results[$CurrentResultIndex]["id"]);
                    // Execute
                    $stmtVotes->execute();
                    // Get data from query
                    $dataVotes = $stmtVotes->fetch();
                    if($dataVotes === false){
                        $results[$CurrentResultIndex]["negative"] = 0;
                        $results[$CurrentResultIndex]["positive"] = 0;
                    }else{
                        $results[$CurrentResultIndex]["negative"] = $dataVotes["negative"];
                        $results[$CurrentResultIndex]["positive"] = $dataVotes["positive"];
                    }
                }
                $response["data"] = $results;
            }

            echo json_encode($response);
        }catch(PDOException $e){
            $response["status"] = 500;
            echo json_encode($response);
        }
    }
}

?>