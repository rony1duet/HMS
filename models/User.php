<?php
require_once __DIR__ . '/Model.php';

class User extends Model
{
    protected $table = 'users';

    // AUTHENTICATION METHODS

    public function findOrCreateByGoogleId($userData)
    {
        try {
            $stmt = $this->executeQuery(
                "SELECT id FROM {$this->table} WHERE google_id = :google_id",
                [':google_id' => $userData['google_id']]
            );

            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->updateLastLogin($row['id']);
                return $row['id'];
            }

            return $this->createFromGoogle($userData);
        } catch (Exception $e) {
            error_log('Error finding/creating user: ' . $e->getMessage());
            throw new Exception('Failed to process user authentication');
        }
    }

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

    public function findByGoogleId($googleId)
    {
        try {
            $stmt = $this->executeQuery(
                "SELECT * FROM {$this->table} WHERE google_id = :google_id",
                [':google_id' => $googleId]
            );

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['slug'] : null;
        } catch (Exception $e) {
            error_log('Error finding user by Google ID: ' . $e->getMessage());
            throw new Exception('Failed to find user');
        }
    }

    public function findByMicrosoftId($microsoftId)
    {
        try {
            $stmt = $this->executeQuery(
                "SELECT * FROM {$this->table} WHERE microsoft_id = :microsoft_id",
                [':microsoft_id' => $microsoftId]
            );

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['slug'] : null;
        } catch (Exception $e) {
            error_log('Error finding user by Microsoft ID: ' . $e->getMessage());
            throw new Exception('Failed to find user');
        }
    }

    // USER CREATION METHODS

    private function createFromGoogle($userData)
    {
        try {
            $role = $this->determineRole($userData['email']);
            $userData['slug'] = $this->generateSlug();

            $userId = parent::create([
                'google_id' => $userData['google_id'],
                'email' => $userData['email'],
                'display_name' => $userData['display_name'],
                'slug' => $userData['slug'],
                'role' => $role,
                'created_at' => date('Y-m-d H:i:s'),
                'last_login' => null
            ]);

            return [
                'id' => $userId,
                'username' => $userData['display_name'],
                'role' => $role,
                'email' => $userData['email'],
                'slug' => $userData['slug'],
                'created_at' => date('Y-m-d H:i:s')
            ];
        } catch (Exception $e) {
            error_log('Error creating user from Google: ' . $e->getMessage());
            throw new Exception('Failed to create user account');
        }
    }

    private function createFromMicrosoft($userData)
    {
        try {
            $role = $this->determineRole($userData['email']);
            $userData['slug'] = $this->generateSlug();

            $userId = parent::create([
                'microsoft_id' => $userData['microsoft_id'],
                'email' => $userData['email'],
                'display_name' => $userData['display_name'],
                'slug' => $userData['slug'],
                'role' => $role,
                'created_at' => date('Y-m-d H:i:s'),
                'last_login' => null
            ]);

            return [
                'id' => $userId,
                'display_name' => $userData['display_name'],
                'role' => $role,
                'email' => $userData['email'],
                'slug' => $userData['slug'],
                'created_at' => date('Y-m-d H:i:s')
            ];
        } catch (Exception $e) {
            error_log('Error creating user from Microsoft: ' . $e->getMessage());
            throw new Exception('Failed to create user account');
        }
    }

    // USER RETRIEVAL METHODS

    public function getUserById($id)
    {
        try {
            return parent::findById($id);
        } catch (Exception $e) {
            error_log('Error getting user: ' . $e->getMessage());
            throw new Exception('Failed to get user details');
        }
    }

    public function getProfileStatus($slug)
    {
        try {
            $stmt = $this->executeQuery(
                "SELECT * FROM users WHERE slug = :slug",
                [':slug' => $slug]
            );

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['profile_status'] : null;
        } catch (Exception $e) {
            error_log('Error getting profile status: ' . $e->getMessage());
            throw new Exception('Failed to get profile status');
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

    // UTILITY METHODS

    private function updateLastLogin($userId)
    {
        try {
            return parent::update($userId, ['last_login' => date('Y-m-d H:i:s')]);
        } catch (Exception $e) {
            error_log('Error updating last login: ' . $e->getMessage());
            throw new Exception('Failed to update last login time');
        }
    }

    public function determineRole($email)
    {
        if (strpos($email, '@student.duet.ac.bd') !== false) {
            return 'student';
        }
        if ($email === 'rony.hossen.duet@gmail.com') { //temporary solution
            return 'provost';
        }
        if ($email === 'admin@duet.ac.bd') {
            return 'admin';
        } else {
            return 'staff';
        }
    }

    private function generateSlug()
    {
        $slug = '';
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        for ($i = 0; $i < 10; $i++) {
            $slug .= $characters[rand(0, $charactersLength - 1)];
        }
        return $slug;
    }
}
