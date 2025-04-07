-- Provost approval status tracking
CREATE TABLE provost_approvals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    slug VARCHAR(100) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    approved_by_slug VARCHAR(100),
    hall_id INT,
    approval_date TIMESTAMP NULL,
    rejection_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (slug) REFERENCES users(slug) ON DELETE CASCADE,
    FOREIGN KEY (approved_by_slug) REFERENCES users(slug),
    FOREIGN KEY (hall_id) REFERENCES halls(id)
);

-- Add index for faster lookups
CREATE INDEX idx_provost_slug ON provost_approvals(slug);
CREATE INDEX idx_provost_status ON provost_approvals(status);