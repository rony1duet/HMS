<?php
class Student
{
    private $conn;
    private $table = 'student_profiles';
    private $validBloodGroups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
    private $validGenders = ['Male', 'Female', 'Other'];

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function create($slug, $data)
    {
        $query = "INSERT INTO " . $this->table . "
                (slug, student_id, full_name, email, phone_number, date_of_birth,
                gender, blood_group, department, program, year, semester,
                guardian_name, guardian_phone, hall_name, room_number,
                division_id, district_id, upazila_id, village_area)
                VALUES
                (:slug, :student_id, :full_name, :email, :phone_number, :date_of_birth,
                :gender, :blood_group, :department, :program, :year, :semester,
                :guardian_name, :guardian_phone, :hall_name, :room_number,
                :division_id, :district_id, :upazila_id, :village_area)";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':slug', $slug);
            $stmt->bindParam(':student_id', $data['student_id']);
            $stmt->bindParam(':full_name', $data['full_name']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':phone_number', $data['phone_number']);
            $stmt->bindParam(':date_of_birth', $data['date_of_birth']);
            $stmt->bindParam(':gender', $data['gender']);
            $stmt->bindParam(':blood_group', $data['blood_group']);
            $stmt->bindParam(':department', $data['department']);
            $stmt->bindParam(':program', $data['program']);
            $stmt->bindParam(':year', $data['year']);
            $stmt->bindParam(':semester', $data['semester']);
            $stmt->bindParam(':guardian_name', $data['guardian_name']);
            $stmt->bindParam(':guardian_phone', $data['guardian_phone']);
            $stmt->bindParam(':hall_name', $data['hall_name']);
            $stmt->bindParam(':room_number', $data['room_number']);
            $stmt->bindParam(':division_id', $data['division_id']);
            $stmt->bindParam(':district_id', $data['district_id']);
            $stmt->bindParam(':upazila_id', $data['upazila_id']);
            $stmt->bindParam(':village_area', $data['village_area']);

            if ($stmt->execute()) {
                return $this->conn->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log('Error creating student profile: ' . $e->getMessage());
            throw new Exception('Failed to create student profile');
        }
    }

    public function getBySlug($slug)
    {
        $query = "SELECT * FROM " . $this->table . " WHERE slug = :slug";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':slug', $slug);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update($id, $data)
    {
        $allowedFields = [
            'full_name',
            'phone_number',
            'department',
            'year',
            'semester',
            'division_id',
            'district_id',
            'upazila_id',
            'village_area',
            'guardian_name',
            'guardian_phone',
            'room_number'
        ];
        $setFields = [];
        $params = [':id' => intval($id)];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $setFields[] = "$key = :$key";
                $params[":$key"] = is_array($value) ? json_encode($value) : $value;
            }
        }

        if (empty($setFields)) {
            return false;
        }

        $query = "UPDATE " . $this->table . " SET " . implode(', ', $setFields) . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        return $stmt->execute($params);
    }

    public function getAllStudents($limit = 10, $offset = 0)
    {
        $query = "SELECT sp.*, u.email, u.username 
                FROM " . $this->table . " sp 
                JOIN users u ON sp.id = u.id 
                LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function searchStudents($searchTerm)
    {
        $query = "SELECT sp.*, u.email, u.username 
                FROM " . $this->table . " sp 
                JOIN users u ON sp.id = u.id 
                WHERE sp.student_id LIKE :search 
                OR sp.first_name LIKE :search 
                OR sp.last_name LIKE :search 
                OR sp.department LIKE :search";

        $searchTerm = "%$searchTerm%";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':search', $searchTerm);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getByUserId($userId)
    {
        $query = "SELECT * FROM " . $this->table . " WHERE slug = (SELECT slug FROM users WHERE id = :id)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $userId);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateProfile($slug, $data)
    {
        try {
            // Validate user exists
            $userQuery = "SELECT slug FROM users WHERE slug = :slug";
            $userStmt = $this->conn->prepare($userQuery);
            $userStmt->bindParam(':slug', $slug);
            $userStmt->execute();

            if (!$userStmt->fetch()) {
                throw new Exception("Invalid user slug");
            }

            // Validate required fields
            $requiredFields = [
                'student_id',
                'full_name',
                'email',
                'gender',
                'department',
                'program',
                'year',
                'semester',
                'hall_name',
                'room_number'
            ];

            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || trim($data[$field]) === '') {
                    throw new Exception(ucfirst(str_replace('_', ' ', $field)) . " is required");
                }
            }

            // Validate year range
            if (!in_array($data['year'], [1, 2, 3, 4, 5])) {
                throw new Exception("Year must be between 1 and 5");
            }

            // Validate semester type
            if (!in_array($data['semester'], ['First', 'Second'])) {
                throw new Exception("Semester type must be either First or Second");
            }

            // Validate phone number format
            if (!preg_match('/^\+?[0-9]{10,13}$/', $data['phone_number'])) {
                throw new Exception("Invalid phone number format. Please use 10-13 digits with optional + prefix");
            }

            // Validate guardian phone number format
            if (!preg_match('/^\+?[0-9]{10,13}$/', $data['guardian_phone'])) {
                throw new Exception("Invalid guardian phone number format. Please use 10-13 digits with optional + prefix");
            }

            // Validate department
            $validDepartments = ['CSE', 'EEE', 'ME', 'CE', 'TE', 'IPE', 'Arch', 'MME', 'ChE', 'FE'];
            if (!in_array($data['department'], $validDepartments)) {
                throw new Exception("Invalid department selected");
            }

            // Validate gender
            if (!in_array($data['gender'], $this->validGenders)) {
                throw new Exception("Invalid gender selected");
            }

            // Validate blood group if provided
            if (!empty($data['blood_group']) && !in_array($data['blood_group'], $this->validBloodGroups)) {
                throw new Exception("Invalid blood group selected");
            }

            // Validate date of birth if provided
            if (!empty($data['date_of_birth'])) {
                $date = DateTime::createFromFormat('Y-m-d', $data['date_of_birth']);
                if (!$date || $date->format('Y-m-d') !== $data['date_of_birth']) {
                    throw new Exception("Invalid date of birth format");
                }
            }

            // Validate location IDs
            $locationQuery = "SELECT COUNT(*) FROM divisions WHERE id = :division_id";
            $stmt = $this->conn->prepare($locationQuery);
            $stmt->bindParam(':division_id', $data['division_id'], PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->fetchColumn() == 0) {
                throw new Exception("Invalid division selected");
            }

            $locationQuery = "SELECT COUNT(*) FROM districts WHERE id = :district_id AND division_id = :division_id";
            $stmt = $this->conn->prepare($locationQuery);
            $stmt->bindParam(':district_id', $data['district_id'], PDO::PARAM_INT);
            $stmt->bindParam(':division_id', $data['division_id'], PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->fetchColumn() == 0) {
                throw new Exception("Invalid district selected");
            }

            $locationQuery = "SELECT COUNT(*) FROM upazilas WHERE id = :upazila_id AND district_id = :district_id";
            $stmt = $this->conn->prepare($locationQuery);
            $stmt->bindParam(':upazila_id', $data['upazila_id'], PDO::PARAM_INT);
            $stmt->bindParam(':district_id', $data['district_id'], PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->fetchColumn() == 0) {
                throw new Exception("Invalid upazila selected");
            }

            // Check if profile exists and prepare query
            $existingProfile = $this->getBySlug($slug);
            $query = $existingProfile ?
                "UPDATE " . $this->table . " SET 
                    student_id = :student_id,
                    full_name = :full_name,
                    email = :email,
                    phone_number = :phone_number,
                    date_of_birth = :date_of_birth,
                    gender = :gender,
                    blood_group = :blood_group,
                    department = :department,
                    program = :program,
                    year = :year,
                    semester = :semester,
                    guardian_name = :guardian_name,
                    guardian_phone = :guardian_phone,
                    hall_name = :hall_name,
                    room_number = :room_number,
                    division_id = :division_id,
                    district_id = :district_id,
                    upazila_id = :upazila_id,
                    village_area = :village_area
                    WHERE slug = :slug" :
                "INSERT INTO " . $this->table . "
                    (slug, student_id, full_name, email, phone_number, date_of_birth,
                    gender, blood_group, department, program, year, semester,
                    guardian_name, guardian_phone, hall_name, room_number,
                    division_id, district_id, upazila_id, village_area)
                    VALUES
                    (:slug, :student_id, :full_name, :email, :phone_number, :date_of_birth,
                    :gender, :blood_group, :department, :program, :year, :semester,
                    :guardian_name, :guardian_phone, :hall_name, :room_number,
                    :division_id, :district_id, :upazila_id, :village_area)";

            $stmt = $this->conn->prepare($query);

            $stmt->bindParam(':slug', $slug);
            $stmt->bindParam(':student_id', $data['student_id']);
            $stmt->bindParam(':full_name', $data['full_name']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':phone_number', $data['phone_number']);
            $stmt->bindParam(':date_of_birth', $data['date_of_birth']);
            $stmt->bindParam(':gender', $data['gender']);
            $stmt->bindParam(':blood_group', $data['blood_group']);
            $stmt->bindParam(':department', $data['department']);
            $stmt->bindParam(':program', $data['program']);
            $stmt->bindParam(':year', $data['year']);
            $stmt->bindParam(':semester', $data['semester']);
            $stmt->bindParam(':guardian_name', $data['guardian_name']);
            $stmt->bindParam(':guardian_phone', $data['guardian_phone']);
            $stmt->bindParam(':hall_name', $data['hall_name']);
            $stmt->bindParam(':room_number', $data['room_number']);
            $stmt->bindParam(':division_id', $data['division_id']);
            $stmt->bindParam(':district_id', $data['district_id']);
            $stmt->bindParam(':upazila_id', $data['upazila_id']);
            $stmt->bindParam(':village_area', $data['village_area']);

            if ($stmt->execute()) {
                // Update profile_status in users table
                $updateStatusQuery = "UPDATE users SET profile_status = 'updated' WHERE slug = :slug";
                $statusStmt = $this->conn->prepare($updateStatusQuery);
                $statusStmt->bindParam(':slug', $slug);
                return $statusStmt->execute();
            }
            return false;
        } catch (Exception $e) {
            error_log('Error updating student profile: ' . $e->getMessage());
            echo $e->getMessage();
            throw $e;
        }
    }
}
