-- Create table for halls
CREATE TABLE IF NOT EXISTS halls (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    gender ENUM('Male', 'Female') NOT NULL
);

-- Insert predefined halls
INSERT INTO halls (name, gender) VALUES
('Dr. Fazlur Rahman Khan Hall', 'Male'),
('Shahid Muktijodda Hall', 'Male'),
('Dr. Qudrat-E-Khuda Hall', 'Male'),
('Shaheed Tazuddin Ahmad Hall', 'Male'),
('Kazi Nazrul Islam Hall', 'Male'),
('Bijoy 24 Hall', 'Male'),
('Madam Curie Hall', 'Female');

-- Add foreign key to student_profiles table
ALTER TABLE student_profiles
ADD CONSTRAINT fk_student_hall
FOREIGN KEY (hall_name) REFERENCES halls(name);

-- Add foreign key to staff_profiles table
ALTER TABLE staff_profiles
ADD CONSTRAINT fk_staff_hall
FOREIGN KEY (working_hall) REFERENCES halls(name);