<?php
class Staff
{
    private $conn;
    private $table = 'staff_profiles';

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function create($slug, $data)
    {
        $query = "INSERT INTO " . $this->table . "
                (slug, full_name, email, working_hall, working_role, phone_number, joining_date)
                VALUES
                (:slug, :full_name, :email, :working_hall, :working_role, :phone_number, :joining_date)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':slug', $slug);
        $stmt->bindParam(':full_name', $data['full_name']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':working_hall', $data['working_hall']);
        $stmt->bindParam(':working_role', $data['working_role']);
        $stmt->bindParam(':phone_number', $data['phone_number']);
        $stmt->bindParam(':joining_date', $data['joining_date']);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function getBySlug($slug)
    {
        $query = "SELECT * FROM " . $this->table . " WHERE slug = :slug";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':slug', $slug);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update($slug, $data)
    {
        $allowedFields = ['full_name', 'working_hall', 'working_role', 'phone_number'];
        $setFields = [];
        $params = [':slug' => $slug];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $setFields[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }

        if (empty($setFields)) {
            return false;
        }

        $query = "UPDATE " . $this->table . " SET " . implode(', ', $setFields) . " WHERE slug = :slug";
        $stmt = $this->conn->prepare($query);

        return $stmt->execute($params);
    }

    public function getAllStaff($limit = 10, $offset = 0)
    {
        $query = "SELECT sp.*, u.username 
                FROM " . $this->table . " sp 
                JOIN users u ON sp.slug = u.slug 
                LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function searchStaff($searchTerm)
    {
        $query = "SELECT sp.*, u.username 
                FROM " . $this->table . " sp 
                JOIN users u ON sp.slug = u.slug 
                WHERE sp.full_name LIKE :search 
                OR sp.working_role LIKE :search 
                OR sp.working_hall LIKE :search";

        $searchTerm = "%$searchTerm%";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':search', $searchTerm);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByEmail($email)
    {
        $query = "SELECT * FROM " . $this->table . " WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?? false;
    }
}
