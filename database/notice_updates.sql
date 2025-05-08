-- Add hall_id to notices table for hall-specific notices
ALTER TABLE notices
ADD COLUMN hall_id INT,
ADD FOREIGN KEY (hall_id) REFERENCES halls(id);

-- Create table for notice attachments
CREATE TABLE notice_attachments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    notice_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_type ENUM('pdf', 'image') NOT NULL,
    file_size INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (notice_id) REFERENCES notices(id) ON DELETE CASCADE
);

-- Add indexes for better performance
CREATE INDEX idx_notice_hall ON notices(hall_id);
CREATE INDEX idx_notice_attachments ON notice_attachments(notice_id);