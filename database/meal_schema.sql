-- Create table for student meal credits
CREATE TABLE IF NOT EXISTS student_meal_credits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    credits INT DEFAULT 0,
    last_recharge DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES student_profiles(id)
);

-- Create table for meal schedules
CREATE TABLE IF NOT EXISTS meal_schedules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    month INT NOT NULL,
    year INT NOT NULL,
    day1 TINYINT DEFAULT 0,
    day2 TINYINT DEFAULT 0,
    day3 TINYINT DEFAULT 0,
    day4 TINYINT DEFAULT 0,
    day5 TINYINT DEFAULT 0,
    day6 TINYINT DEFAULT 0,
    day7 TINYINT DEFAULT 0,
    day8 TINYINT DEFAULT 0,
    day9 TINYINT DEFAULT 0,
    day10 TINYINT DEFAULT 0,
    day11 TINYINT DEFAULT 0,
    day12 TINYINT DEFAULT 0,
    day13 TINYINT DEFAULT 0,
    day14 TINYINT DEFAULT 0,
    day15 TINYINT DEFAULT 0,
    day16 TINYINT DEFAULT 0,
    day17 TINYINT DEFAULT 0,
    day18 TINYINT DEFAULT 0,
    day19 TINYINT DEFAULT 0,
    day20 TINYINT DEFAULT 0,
    day21 TINYINT DEFAULT 0,
    day22 TINYINT DEFAULT 0,
    day23 TINYINT DEFAULT 0,
    day24 TINYINT DEFAULT 0,
    day25 TINYINT DEFAULT 0,
    day26 TINYINT DEFAULT 0,
    day27 TINYINT DEFAULT 0,
    day28 TINYINT DEFAULT 0,
    day29 TINYINT DEFAULT 0,
    day30 TINYINT DEFAULT 0,
    day31 TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES student_profiles(id),
    UNIQUE KEY unique_monthly_schedule (student_id, month, year)
);

-- Create table for meal menu
CREATE TABLE IF NOT EXISTS meal_menu (
    id INT PRIMARY KEY AUTO_INCREMENT,
    meal_type ENUM('breakfast', 'lunch', 'dinner') NOT NULL,
    day_of_week TINYINT NOT NULL, -- 0 = Sunday, 6 = Saturday
    items TEXT NOT NULL,
    serving_time VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create table for meal statistics
CREATE TABLE IF NOT EXISTS meal_statistics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    month INT NOT NULL,
    year INT NOT NULL,
    total_meals INT DEFAULT 0,
    total_cost INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES student_profiles(id),
    UNIQUE KEY unique_monthly_stats (student_id, month, year)
);