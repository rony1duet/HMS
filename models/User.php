<?php
require_once __DIR__ . '/Model.php';

class User extends Model
{
    protected $table = 'users';

    public function findOrCreateByMicrosoftId($userData)
    {
        try {
            $stmt = $this->executeQuery(
                "SELECT id FROM {$this->table} WHERE microsoft_id = :microsoft_id",
                [':microsoft_id' => $userData['microsoft_id']]
            );

            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->updateLastLogin($row['id']);
                return $row['id'];
            }

            return $this->createFromMicrosoft($userData);
        } catch (Exception $e) {
            error_log('Error finding/creating user: ' . $e->getMessage());
            throw new Exception('Failed to process user authentication');
        }
    }

    private function createFromMicrosoft($userData)
    {
        try {
            $role = $this->determineRole($userData['email']);

            $userId = parent::create([
                'microsoft_id' => $userData['microsoft_id'],
                'email' => $userData['email'],
                'display_name' => $userData['display_name'],
                'role' => $role,
                'created_at' => date('Y-m-d H:i:s'),
                'last_login' => null
            ]);

            return [
                'id' => $userId,
                'username' => $userData['display_name'],
                'role' => $role,
                'email' => $userData['email'],
                'created_at' => date('Y-m-d H:i:s')
            ];
        } catch (Exception $e) {
            error_log('Error creating user from Microsoft: ' . $e->getMessage());
            throw new Exception('Failed to create user account');
        }
    }

    private function determineRole($email)
    {
        if (strpos($email, '@student.duet.ac.bd') !== false) {
            if (strpos($email, 'student') !== false) {
                return 'student';
            }
            return 'staff';
        }
        return 'guest';
    }

    private function updateLastLogin($userId)
    {
        try {
            return parent::update($userId, ['last_login' => date('Y-m-d H:i:s')]);
        } catch (Exception $e) {
            error_log('Error updating last login: ' . $e->getMessage());
            throw new Exception('Failed to update last login time');
        }
    }

    public function getLastLogin($userId)
    {
        try {
            $result = parent::findById($userId);
            return $result ? $result['last_login'] : null;
        } catch (Exception $e) {
            error_log('Error getting last login: ' . $e->getMessage());
            throw new Exception('Failed to get last login time');
        }
    }

    public function getUserById($id)
    {
        try {
            return parent::findById($id);
        } catch (Exception $e) {
            error_log('Error getting user: ' . $e->getMessage());
            throw new Exception('Failed to get user details');
        }
    }
}
