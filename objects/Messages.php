<?php

class Messages
{

    private $conn;
    private $table_name = "messages";
    public $id;
    public $sender_id;
    public $receiver_id;
    public $message_text;
    public $created_at;

    public function __construct($pdo)
    {
        $this->conn = $pdo;
    }

    public function SendMessage()
    {

        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->sender_id) && !empty($data->receiver_id) && !empty($data->message_text)) {
            $this->sender_id = $data->sender_id;
            $this->receiver_id = $data->receiver_id;
            $this->message_text = $data->message_text;
        }

        $query = "SELECT id from conversations WHERE user1_id = :user1_id AND user2_id = :user2_id";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":user1_id", $this->sender_id);
        $stmt->bindParam(":user2_id", $this->receiver_id);
        $stmt->execute();

        $conversationsId = null;
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $conversationsId = $row['id'];
        }

        if ($conversationsId) {
            $query = "INSERT INTO $this->table_name (sender_id, receiver_id, message_text)
                        VALUES (:sender_id, :receiver_id, :message_text)";
            $stmt = $this->conn->prepare($query);

            $this->message_text = htmlspecialchars(strip_tags($this->message_text));

            $stmt->bindParam(":sender_id", $this->sender_id);
            $stmt->bindParam(":receiver_id", $this->receiver_id);
            $stmt->bindParam(":message_text", $this->message_text);

            if ($stmt->execute()) {
                $messageId = $this->conn->lastInsertId();
                $query = "UPDATE conversations SET last_message_id = :last_message_id WHERE id = :id";
                $stmt = $this->conn->prepare($query);

                $stmt->bindParam(":last_message_id", $messageId);
                $stmt->bindParam(":id", $conversationsId);
                $stmt->execute();
                http_response_code(200);
                echo json_encode([
                    "message" => "Message sent and conversation updated",
                    "status" => "200"
                ]);
            } else {
                http_response_code(401);
                echo json_encode([
                    "message" => "Unable to send the message and update the conversation",
                    "status" => "401"
                ]);
            }
        } else {

            $query = "INSERT INTO $this->table_name (sender_id, receiver_id, message_text)
            VALUES (:sender_id, :receiver_id, :message_text)";
            $stmt = $this->conn->prepare($query);

            $this->message_text = htmlspecialchars(strip_tags($this->message_text));

            $stmt->bindParam(":sender_id", $this->sender_id);
            $stmt->bindParam(":receiver_id", $this->receiver_id);
            $stmt->bindParam(":message_text", $this->message_text);

            if ($stmt->execute()) {
                $lastMessageid = $this->conn->lastInsertId();
                $query = "INSERT INTO conversations (user1_id, user2_id, last_message_id)
                            VALUES (:user1_id, :user2_id, :last_message_id)";
                $stmt = $this->conn->prepare($query);

                $stmt->bindParam(":user1_id", $this->sender_id);
                $stmt->bindParam(":user2_id", $this->receiver_id);
                $stmt->bindParam(":last_message_id", $lastMessageid);


                if ($stmt->execute()) {
                    http_response_code(200);
                    echo json_encode([
                        "message" => "Message sent and conversation created",
                        "status" => "200"
                    ]);
                } else {
                    http_response_code(401);
                    echo json_encode([
                        "message" => "Unable to send the message",
                        "status" => "401"
                    ]);
                }
            }
        }
    }

    public function FetchMessage()
    {

        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->sender_id) && !empty($data->receiver_id)) {
            $this->sender_id = $data->sender_id;
            $this->receiver_id = $data->receiver_id;
        }

        $query = "SELECT * FROM $this->table_name WHERE sender_id = :sender_id AND receiver_id = :receiver_id";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":sender_id", $this->sender_id);
        $stmt->bindParam(":receiver_id", $this->receiver_id);

        if ($stmt->execute()) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $usermessage = $row["message_text"];
            http_response_code(201);
            echo json_encode([
                "response_message" => "Successfully fetched User Message",
                "code" => 201,
                "message" => $usermessage,
                "sender" => $this->sender_id,
                "receiver" => $this->receiver_id
            ]);
        } else {
            http_response_code(401);
            echo json_encode([
                "response_message" => "Unable to fetch User Message",
                "code" => 401,
            ]);
        }
    }

    public function FetchLastMsg()
    {
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->sender_id) && !empty($data->receiver_id)) {
            $this->sender_id = $data->sender_id;
            $this->receiver_id = $data->receiver_id;
        }

        $query = "SELECT last_message_id FROM conversations WHERE user1_id = :user1_id AND user2_id = :user2_id";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":user1_id", $this->sender_id);
        $stmt->bindParam(":user2_id", $this->receiver_id);

        if ($stmt->execute()) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $LastMessageId = $row['last_message_id'];
            $LastMessageId;

            $query = "SELECT message_text, created_at FROM $this->table_name WHERE id=:id";
            $stmt = $this->conn->prepare($query);

            $stmt->bindParam(":id", $LastMessageId);

            if ($stmt->execute()) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $lastMessage = $row["message_text"];
                $lastMsgTime = $row["created_at"];

                http_response_code(201);
                echo json_encode([
                    "response_message" => "Successfully fetched User's last Message",
                    "code" => 201,
                    "last_message" => $lastMessage,
                    "created_at" => $lastMsgTime
                ]);
            } else {
                http_response_code(201);
                echo json_encode([
                    "response_message" => "Unable to fetch user's last Message",
                    "code" => 201,
                ]);
            }
        }
    }
}