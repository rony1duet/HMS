<?php
abstract class Model
{
    protected $conn;
    protected $table;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    protected function executeQuery($query, $params = [])
    {
        try {
            $stmt = $this->conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            error_log('Database Error: ' . $e->getMessage());
            throw new Exception('Database operation failed');
        }
    }

    protected function findById($id)
    {
        $query = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = $this->executeQuery($query, [':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    protected function findAll($conditions = [], $orderBy = '')
    {
        $query = "SELECT * FROM {$this->table}";

        if (!empty($conditions)) {
            $query .= " WHERE " . implode(' AND ', array_map(fn($key) => "$key = :$key", array_keys($conditions)));
        }

        if ($orderBy) {
            $query .= " ORDER BY $orderBy";
        }

        $stmt = $this->executeQuery($query, $conditions);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    protected function create($data)
    {
        $fields = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_map(fn($field) => ":$field", array_keys($data)));

        $query = "INSERT INTO {$this->table} ($fields) VALUES ($placeholders)";
        $this->executeQuery($query, $data);
        return $this->conn->lastInsertId();
    }

    protected function update($id, $data)
    {
        $fields = implode(', ', array_map(fn($field) => "$field = :$field", array_keys($data)));
        $query = "UPDATE {$this->table} SET $fields WHERE id = :id";

        $data[':id'] = $id;
        return $this->executeQuery($query, $data)->rowCount() > 0;
    }

    protected function delete($id)
    {
        $query = "DELETE FROM {$this->table} WHERE id = :id";
        return $this->executeQuery($query, [':id' => $id])->rowCount() > 0;
    }
}
