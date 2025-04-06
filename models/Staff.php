<?php
class Staff {
    private $conn;
    private $table = 'staff_profiles';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($userId, $data) {
        $query = "INSERT INTO " . $this->table . "
                (user_id, first_name, last_name, designation, phone_number, joining_date)
                VALUES
                (:user_id, :first_name, :last_name, :designation, :phone_number, :joining_date)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':first_name', $data['first_name']);
        $stmt->bindParam(':last_name', $data['last_name']);
        $stmt->bindParam(':designation', $data['designation']);
        $stmt->bindParam(':phone_number', $data['phone_number']);
        $stmt->bindParam(':joining_date', $data['joining_date']);

        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function getByUserId($userId) {
        $query = "SELECT * FROM " . $this->table . " WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update($id, $data) {
        $allowedFields = ['first_name', 'last_name', 'designation', 'phone_number'];
        $setFields = [];
        $params = [':id' => $id];

        foreach($data as $key => $value) {
            if(in_array($key, $allowedFields)) {
                $setFields[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }

        if(empty($setFields)) {
            return false;
        }

        $query = "UPDATE " . $this->table . " SET " . implode(', ', $setFields) . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        return $stmt->execute($params);
    }

    public function getAllStaff($limit = 10, $offset = 0) {
        $query = "SELECT sp.*, u.email, u.username 
                FROM " . $this->table . " sp 
                JOIN users u ON sp.user_id = u.id 
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function searchStaff($searchTerm) {
        $query = "SELECT sp.*, u.email, u.username 
                FROM " . $this->table . " sp 
                JOIN users u ON sp.user_id = u.id 
                WHERE sp.first_name LIKE :search 
                OR sp.last_name LIKE :search 
                OR sp.designation LIKE :search";
        
        $searchTerm = "%$searchTerm%";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':search', $searchTerm);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}