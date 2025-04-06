-- Provost approval status tracking
CREATE TABLE provost_approvals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_slug VARCHAR(100) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    approved_by_slug VARCHAR(100),
    approval_date TIMESTAMP NULL,
    rejection_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_slug) REFERENCES users(slug) ON DELETE CASCADE,
    FOREIGN KEY (approved_by_slug) REFERENCES users(slug)
);

-- Add index for faster lookups
CREATE INDEX idx_provost_user_slug ON provost_approvals(user_slug);
CREATE INDEX idx_provost_status ON provost_approvals(status);