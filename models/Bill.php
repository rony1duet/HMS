<?php
class Bill {
    private $conn;
    private $table = 'bills';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table . "
                (student_id, bill_type, amount, month, due_date, status)
                VALUES
                (:student_id, :bill_type, :amount, :month, :due_date, :status)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':student_id', $data['student_id']);
        $stmt->bindParam(':bill_type', $data['bill_type']);
        $stmt->bindParam(':amount', $data['amount']);
        $stmt->bindParam(':month', $data['month']);
        $stmt->bindParam(':due_date', $data['due_date']);
        $stmt->bindParam(':status', $data['status']);

        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function getStudentBills($studentId, $status = null) {
        $query = "SELECT b.*, p.amount as paid_amount 
                FROM " . $this->table . " b 
                LEFT JOIN payments p ON b.id = p.bill_id 
                WHERE b.student_id = :student_id";
        
        if($status) {
            $query .= " AND b.status = :status";
        }

        $query .= " ORDER BY b.due_date ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':student_id', $studentId);
        
        if($status) {
            $stmt->bindParam(':status', $status);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatus($billId, $status) {
        $query = "UPDATE " . $this->table . " SET status = :status WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':id', $billId);
        $stmt->bindParam(':status', $status);

        return $stmt->execute();
    }

    public function getBillDetails($billId) {
        $query = "SELECT b.*, p.amount as paid_amount, p.payment_date, p.payment_method 
                FROM " . $this->table . " b 
                LEFT JOIN payments p ON b.id = p.bill_id 
                WHERE b.id = :bill_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':bill_id', $billId);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getOverdueBills() {
        $query = "SELECT b.*, s.first_name, s.last_name, s.student_id as student_number 
                FROM " . $this->table . " b 
                JOIN student_profiles s ON b.student_id = s.id 
                WHERE b.status = 'pending' AND b.due_date < CURDATE()";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}