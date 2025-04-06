-- Create table for credit transactions history
CREATE TABLE IF NOT EXISTS credit_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    type ENUM('recharge', 'deduction') NOT NULL,
    balance_after DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES student_profiles(id)
);

-- Add index for faster queries
CREATE INDEX idx_student_transactions ON credit_transactions(student_id, created_at);