CREATE DATABASE IF NOT EXISTS hms;
USE hms;

-- Users table for authentication and basic user information
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    slug VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE,
    role ENUM('admin', 'provost', 'student', 'staff') NOT NULL,
    display_name VARCHAR(100),
    microsoft_id VARCHAR(100),
    google_id VARCHAR(100),
    profile_status ENUM('updated', 'not_updated') NOT NULL DEFAULT 'not_updated',
    password_hash VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);


-- Student profiles with additional information
CREATE TABLE `student_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(100) NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone_number` varchar(15) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `blood_group` varchar(5) DEFAULT NULL,
  `department` varchar(100) NOT NULL,
  `program` varchar(100) NOT NULL,
  `year` int(11) NOT NULL,
  `semester` varchar(20) NOT NULL,
  `guardian_name` varchar(100) DEFAULT NULL,
  `guardian_phone` varchar(15) DEFAULT NULL,
  `hall_name` varchar(100) NOT NULL,
  `room_number` varchar(10) NOT NULL,
  `division_id` int(11) DEFAULT NULL,
  `district_id` int(11) DEFAULT NULL,
  `upazila_id` int(11) DEFAULT NULL,
  `village_area` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_id` (`student_id`),
  KEY `slug` (`slug`),
  KEY `division_id` (`division_id`),
  KEY `district_id` (`district_id`),
  KEY `upazila_id` (`upazila_id`),
  CONSTRAINT `student_profiles_ibfk_1` FOREIGN KEY (`slug`) REFERENCES `users` (`slug`) ON DELETE CASCADE,
  CONSTRAINT `student_profiles_ibfk_2` FOREIGN KEY (`division_id`) REFERENCES `divisions` (`id`),
  CONSTRAINT `student_profiles_ibfk_3` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`),
  CONSTRAINT `student_profiles_ibfk_4` FOREIGN KEY (`upazila_id`) REFERENCES `upazilas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Rooms information
CREATE TABLE rooms (
    id INT PRIMARY KEY AUTO_INCREMENT,
    room_number VARCHAR(10) UNIQUE NOT NULL,
    capacity INT NOT NULL,
    floor_number INT NOT NULL,
    status ENUM('available', 'occupied') NOT NULL DEFAULT 'available'
);

-- Bed allocation
CREATE TABLE beds (
    id INT PRIMARY KEY AUTO_INCREMENT,
    room_id INT NOT NULL,
    bed_number VARCHAR(5) NOT NULL,
    student_profile_id INT,
    status ENUM('available', 'occupied') NOT NULL DEFAULT 'available',
    FOREIGN KEY (room_id) REFERENCES rooms(id),
    FOREIGN KEY (student_profile_id) REFERENCES student_profiles(id),
    UNIQUE KEY room_bed (room_id, bed_number)
);

-- Meal plans
CREATE TABLE meal_plans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    price_per_day DECIMAL(10, 2) NOT NULL
);

-- Daily meal selections
CREATE TABLE meal_selections (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_profile_id INT NOT NULL,
    date DATE NOT NULL,
    breakfast BOOLEAN DEFAULT FALSE,
    lunch BOOLEAN DEFAULT FALSE,
    dinner BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_profile_id) REFERENCES student_profiles(id),
    UNIQUE KEY student_date (student_profile_id, date)
);

-- Bills and payments
CREATE TABLE bills (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_profile_id INT NOT NULL,
    bill_type ENUM('hall_rent', 'mess_bill', 'other') NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    month DATE NOT NULL,
    due_date DATE NOT NULL,
    status ENUM('pending', 'paid', 'overdue') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_profile_id) REFERENCES student_profiles(id)
);

-- Payment transactions
CREATE TABLE payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bill_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_method ENUM('cash', 'online', 'bank_transfer') NOT NULL,
    transaction_id VARCHAR(100),
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bill_id) REFERENCES bills(id)
);

-- Complaints system
CREATE TABLE complaints (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_profile_id INT NOT NULL,
    category ENUM('maintenance', 'mess', 'security', 'other') NOT NULL,
    subject VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('pending', 'in_progress', 'resolved', 'closed') NOT NULL DEFAULT 'pending',
    priority ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    FOREIGN KEY (student_profile_id) REFERENCES student_profiles(id)
);

-- Complaint responses
CREATE TABLE complaint_responses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    complaint_id INT NOT NULL,
    responder_slug VARCHAR(100) NOT NULL,
    response TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (complaint_id) REFERENCES complaints(id),
    FOREIGN KEY (responder_slug) REFERENCES users(slug)
);

-- Notices and announcements
CREATE TABLE notices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    posted_by_slug VARCHAR(100) NOT NULL,
    importance ENUM('normal', 'important', 'urgent') NOT NULL DEFAULT 'normal',
    start_date DATE NOT NULL,
    end_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (posted_by_slug) REFERENCES users(slug)
);

-- Staff profiles
CREATE TABLE staff_profiles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    slug VARCHAR(100) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    designation VARCHAR(100) NOT NULL,
    phone_number VARCHAR(15),
    joining_date DATE NOT NULL,
    FOREIGN KEY (slug) REFERENCES users(slug) ON DELETE CASCADE
);