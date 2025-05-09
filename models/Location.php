<?php
class Location
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function getAllDivisions()
    {
        $query = "SELECT id, name, bn_name FROM divisions ORDER BY name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDistrictsByDivision($divisionId)
    {
        $query = "SELECT id, name, bn_name FROM districts WHERE division_id = :division_id ORDER BY name";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':division_id', $divisionId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUpazilasByDistrict($districtId)
    {
        $query = "SELECT id, name, bn_name FROM upazilas WHERE district_id = :district_id ORDER BY name";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':district_id', $districtId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDivisionById($id)
    {
        $query = "SELECT id, name FROM divisions WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getDistrictById($id)
    {
        $query = "SELECT id, name FROM districts WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUpazilaById($id)
    {
        $query = "SELECT id, name FROM upazilas WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
