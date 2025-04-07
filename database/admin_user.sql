USE hms;

-- Add password_hash column to users table if not exists
ALTER TABLE users
ADD COLUMN IF NOT EXISTS password_hash VARCHAR(255) NULL;

-- Create remember_tokens table for "Remember Me" functionality
CREATE TABLE IF NOT EXISTS remember_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    slug VARCHAR(100) NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (slug) REFERENCES users(slug) ON DELETE CASCADE,
    UNIQUE KEY unique_token (token)
);

-- Insert admin user with hashed password (default password: Admin@123)
INSERT INTO users (role, email, display_name, password_hash)
VALUES ('admin', 'admin@duet.ac.bd', 'System Administrator', '$2y$10$ow2ZXumr1d./flbMihP2heYmY8aDd.qUtJeFDJnhv/JJf3D1QmVPS')
ON DUPLICATE KEY UPDATE
    display_name = VALUES(display_name),
    password_hash = VALUES(password_hash);