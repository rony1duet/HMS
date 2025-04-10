<?php
require_once __DIR__ . '/Model.php';

class AdminProfile extends Model
{
    protected $table = 'admin_profiles';

    public function __construct($conn)
    {
        parent::__construct($conn);
    }

    public function getByUserId($slug)
    {
        $sql = "SELECT * FROM {$this->table} WHERE slug = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(1, $slug, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateProfile($slug, $data)
    {
        // Validate required fields
        $requiredFields = ['full_name', 'designation', 'joining_date', 'email'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new Exception("Field {$field} is required");
            }
        }

        try {
            $this->conn->beginTransaction();

            $existingProfile = $this->getByUserId($slug);
            $params = [
                'full_name' => htmlspecialchars($data['full_name']),
                'email' => filter_var($data['email'], FILTER_SANITIZE_EMAIL),
                'phone_number' => !empty($data['phone_number']) ? htmlspecialchars($data['phone_number']) : null,
                'designation' => htmlspecialchars($data['designation']),
                'joining_date' => $data['joining_date'],
                'slug' => $slug
            ];

            if ($existingProfile) {
                // Update existing profile
                $sql = "UPDATE {$this->table} SET 
                        full_name = :full_name,
                        email = :email,
                        phone_number = :phone_number,
                        designation = :designation,
                        joining_date = :joining_date";

                if (!empty($data['profile_image_uri'])) {
                    $sql .= ", profile_image_uri = :profile_image_uri";
                    $params['profile_image_uri'] = $data['profile_image_uri'];
                }

                $sql .= " WHERE slug = :slug";
            } else {
                // Create new profile
                $sql = "INSERT INTO {$this->table} 
                        (slug, full_name, email, phone_number, designation, joining_date, profile_image_uri)
                        VALUES (:slug, :full_name, :email, :phone_number, :designation, :joining_date, :profile_image_uri)";

                $params['profile_image_uri'] = !empty($data['profile_image_uri']) ? $data['profile_image_uri'] : null;
            }

            $stmt = $this->conn->prepare($sql);
            if ($stmt->execute($params)) {
                // Update profile_status in users table
                $updateStatusQuery = "UPDATE users SET profile_status = 'updated' WHERE slug = :slug";
                $statusStmt = $this->conn->prepare($updateStatusQuery);
                $statusStmt->bindParam(':slug', $slug);
                $success = $statusStmt->execute();

                if ($success) {
                    $this->conn->commit();
                    return true;
                }
                $this->conn->rollBack();
                return false;
            }
            $this->conn->rollBack();
            return false;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }
}
