<?php

class User
{
    private $conn;
    private $table_name = "users";
    public $id;
    public $username;
    public $email;
    public $password_hash;
    public $image;
    public $status;
    public $userStatus = "online";

    public function __construct($pdo)
    {
        $this->conn = $pdo;
    }

    public function create()
    {

        $data = json_decode(file_get_contents("php://input"));

        // echo '<pre>';
        // print_r($data); die();

        if (!empty($data->username) && !empty($data->email) && !empty($data->password_hash) && !empty($data->image)) {
            $this->username = $data->username;
            $this->email = $data->email;
            $this->password_hash = md5(trim($data->password_hash));
            $this->image = $data->image;
        }

        $query = "INSERT INTO $this->table_name(username,email,password_hash,image,status)
        VALUES (:username, :email, :password_hash, :image, :status)";
        $stmt = $this->conn->prepare($query);

        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->image = htmlspecialchars(strip_tags($this->image));
        $this->status = "offline";

        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password_hash", $this->password_hash);
        $stmt->bindParam(":image", $this->image);
        $stmt->bindParam(":status", $this->status);

        try {
            $stmt->execute();
            http_response_code(200);
            echo json_encode([
                "message" => "New User Added",
                "status" => 200
            ]);
        } catch (e) {
            http_response_code(401);
            echo json_encode([
                "message" => "Unable to create new user"
            ]);
        }
    }

    public function authentication()
    {

        $data = json_decode(file_get_contents("php://input"));
        if (!empty($data->email) && !empty($data->password_hash)) {
            $this->email = $data->email;
            $this->password_hash = md5($data->password_hash);
        }
        $query = "SELECT * FROM $this->table_name WHERE email = :email";
        $stmt = $this->conn->prepare($query);


        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->password_hash = htmlspecialchars(strip_tags($this->password_hash));
        $stmt->bindParam(":email", $this->email);

        try {
            if ($stmt->execute()) {
                $num = $stmt->rowCount();
                if ($num > 0) {
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    $storedPassword = $row["password_hash"];
                    $userId = $row["id"];
                    if ($this->password_hash == $storedPassword) {

                        $SetStatus = "UPDATE $this->table_name SET status=:status WHERE email= :email";
                        $stmt1 = $this->conn->prepare($SetStatus);


                        $stmt1->bindParam(":status", $this->userStatus);
                        $stmt1->bindParam(":email", $this->email);
                        $stmt1->execute();

                        $stmt->execute();
                        $userInfo = $stmt->fetch(PDO::FETCH_ASSOC);
                        $userName = $userInfo["username"];
                        $userStat = $userInfo["status"];
                        $userImage = $userInfo["image"];

                        $queryActiveUsers = "SELECT * FROM $this->table_name WHERE status = :status";
                        $stmtActiveUsers = $this->conn->prepare($queryActiveUsers);
                        $stmtActiveUsers->bindParam(":status", $this->userStatus);
                        $stmtActiveUsers->execute();
                        $number = $stmtActiveUsers->rowCount();

                        if ($number > 0) {
                            $online_users = [];
                            $online_users['records'] = array();

                            while ($rowActiveUsers = $stmtActiveUsers->fetch(PDO::FETCH_ASSOC)) {
                                extract($rowActiveUsers);

                                $online_list = [
                                    "id" => $id,
                                    "username" => $username,
                                    "email" => $email,
                                    "image" => $image,
                                    "status" => $status
                                ];

                                array_push($online_users['records'], $online_list);
                            }
                            http_response_code(200);
                            echo json_encode([
                                "message" => "Successful Login",
                                "code" => 200,
                                "userid" => $userId,
                                "username" => $userName,
                                "userimage" => $userImage,
                                "userStatus" => $userStat,
                                "onlineUsers" => $online_users

                            ]);
                        }

                    } else {
                        http_response_code(400);
                        echo json_encode([
                            "message" => "Unsuccessful Login",
                            "code" => 400,
                        ]);
                    }
                } else {
                    http_response_code(401);
                    echo json_encode([
                        "message" => "Unsuccessful Login",
                        "code" => 401,
                    ]);
                }
            }
        } catch (e) {

        }
    }

    public function getActiveUsers()
    {
        $queryActiveUsers = "SELECT * FROM $this->table_name WHERE status = :status";
        $stmt = $this->conn->prepare($queryActiveUsers);
        $stmt->bindParam(":status", $this->userStatus);

        try {
            $stmt->execute();
            $num = $stmt->rowCount();

            if ($num > 0) {
                $online_users = [];
                $online_users['records'] = array();

                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    extract($row);

                    $online_list = [
                        "id" => $id,
                        "username" => $username,
                        "email" => $email,
                        "image" => $image,
                        "status" => $status
                    ];

                    array_push($online_users['records'], $online_list);
                }

                http_response_code(200);
                echo json_encode($online_users);
            }
        } catch (e) {
            http_response_code(404);
            echo json_encode([
                "message" => "No Active Users",
                "code" => 401,
            ]);
        }
    }

}