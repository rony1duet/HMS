<?php
require_once __DIR__ . '/Model.php';

class Room extends Model {
    protected $table = 'rooms';

    public function create($data) {
        try {
            return parent::create([
                'room_number' => $data['room_number'],
                'category_id' => $data['category_id'],
                'floor_number' => $data['floor_number'],
                'status' => $data['status'] ?? 'available'
            ]);
        } catch (Exception $e) {
            error_log('Error creating room: ' . $e->getMessage());
            throw new Exception('Failed to create room');
        }
    }

    public function getAvailableRooms($categoryId = null) {
        try {
            $query = "SELECT r.*, rc.name as category_name, rc.price 
                    FROM {$this->table} r 
                    JOIN room_categories rc ON r.category_id = rc.id 
                    WHERE r.status = :status";
            
            $params = [':status' => 'available'];
            
            if ($categoryId) {
                $query .= " AND r.category_id = :category_id";
                $params[':category_id'] = $categoryId;
            }

            $stmt = $this->executeQuery($query, $params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Error getting available rooms: ' . $e->getMessage());
            throw new Exception('Failed to get available rooms');
        }
    }

    public function updateStatus($roomId, $status) {
        try {
            return parent::update($roomId, ['status' => $status]);
        } catch (Exception $e) {
            error_log('Error updating room status: ' . $e->getMessage());
            throw new Exception('Failed to update room status');
        }
    }

    public function getRoomDetails($roomId) {
        try {
            $query = "SELECT r.*, rc.name as category_name, rc.price, 
                    COUNT(b.id) as total_beds,
                    SUM(CASE WHEN b.status = 'occupied' THEN 1 ELSE 0 END) as occupied_beds
                    FROM {$this->table} r 
                    JOIN room_categories rc ON r.category_id = rc.id 
                    LEFT JOIN beds b ON r.id = b.room_id
                    WHERE r.id = :room_id
                    GROUP BY r.id";

            $stmt = $this->executeQuery($query, [':room_id' => $roomId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Error getting room details: ' . $e->getMessage());
            throw new Exception('Failed to get room details');
        }
    }

    public function getAllRooms($limit = 10, $offset = 0, $filters = []) {
        try {
            $query = "SELECT r.*, rc.name as category_name, rc.price 
                    FROM {$this->table} r 
                    JOIN room_categories rc ON r.category_id = rc.id";
            
            $params = [];
            
            if (!empty($filters)) {
                $conditions = [];
                foreach ($filters as $key => $value) {
                    $conditions[] = "r.$key = :$key";
                    $params[":$key"] = $value;
                }
                $query .= " WHERE " . implode(' AND ', $conditions);
            }
            
            $query .= " LIMIT :limit OFFSET :offset";
            $params[':limit'] = $limit;
            $params[':offset'] = $offset;

            $stmt = $this->executeQuery($query, $params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Error getting all rooms: ' . $e->getMessage());
            throw new Exception('Failed to get all rooms');
        }
    }
}