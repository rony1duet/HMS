<?php
require_once 'Model.php';
require_once 'User.php';

class Staff extends Model
{
    protected $table = 'staff_profiles';
    protected $primaryKey = 'id';
    protected $fillable = ['slug', 'full_name', 'email', 'working_hall', 'working_role', 'phone_number', 'joining_date'];
    public function __construct($db)
    {
        parent::__construct($db);
        $this->conn = $db;
    }

    public function createStaffProfile($data)
    {
        try {
            // Validate required fields
            $requiredFields = ['full_name', 'email', 'working_hall', 'working_role', 'joining_date'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw new Exception("$field is required.");
                }
            }

            // Validate email format
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email format.");
            }

            // Validate phone number format if provided
            if (!empty($data['phone_number'])) {
                if (!preg_match('/^[0-9]{11}$/', $data['phone_number'])) {
                    throw new Exception("Phone number must be 11 digits.");
                }
            }

            // Start transaction
            $this->conn->beginTransaction();

            try {
                // Create user first
                $user = new User($this->conn);
                $userData = [
                    'email' => $data['email'],
                    'display_name' => $data['full_name'],
                    'role' => 'staff',
                    'created_at' => date('Y-m-d H:i:s'),
                    'last_login' => null
                ];

                // Generate a unique slug
                $data['slug'] = $this->generateSlug();
                $userData['slug'] = $data['slug'];

                // Create the user
                $userId = $user->create($userData);

                if (!$userId) {
                    throw new Exception("Failed to create user account.");
                }

                // Prepare SQL statement for staff profile
                $sql = "INSERT INTO staff_profiles (slug, full_name, email, working_hall, working_role, phone_number, joining_date) 
                        VALUES (:slug, :full_name, :email, :working_hall, :working_role, :phone_number, :joining_date)";

                $stmt = $this->conn->prepare($sql);

                // Bind parameters
                $stmt->bindParam(':slug', $data['slug']);
                $stmt->bindParam(':full_name', $data['full_name']);
                $stmt->bindParam(':email', $data['email']);
                $stmt->bindParam(':working_hall', $data['working_hall']);
                $stmt->bindParam(':working_role', $data['working_role']);
                $stmt->bindParam(':phone_number', $data['phone_number']);
                $stmt->bindParam(':joining_date', $data['joining_date']);

                // Execute the statement
                if (!$stmt->execute()) {
                    throw new Exception("Failed to create staff profile.");
                }

                // Commit transaction
                $this->conn->commit();
                return true;
            } catch (Exception $e) {
                // Rollback transaction on error
                $this->conn->rollBack();
                throw $e;
            }
        } catch (PDOException $e) {
            throw new Exception("Database error: " . $e->getMessage());
        } catch (Exception $e) {
            throw new Exception("Error: " . $e->getMessage());
        }
    }

    public function generateSlug()
    {
        $slug = '';
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        for ($i = 0; $i < 10; $i++) {
            $slug .= $characters[rand(0, $charactersLength - 1)];
        }
        return $slug;
    }
    public function isExistInStaff($email)
    {
        $sql = "SELECT COUNT(*) FROM staff_profiles WHERE email = :email";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }
}
