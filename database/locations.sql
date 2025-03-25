-- Bangladesh location tables

CREATE TABLE divisions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    bn_name VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE districts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    division_id INT NOT NULL,
    name VARCHAR(50) NOT NULL,
    bn_name VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (division_id) REFERENCES divisions(id)
);

CREATE TABLE upazilas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    district_id INT NOT NULL,
    name VARCHAR(50) NOT NULL,
    bn_name VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (district_id) REFERENCES districts(id)
);

-- Modify student_profiles table to add new fields
ALTER TABLE student_profiles
ADD COLUMN division_id INT,
ADD COLUMN district_id INT,
ADD COLUMN upazila_id INT,
ADD COLUMN village_area VARCHAR(255),
ADD FOREIGN KEY (division_id) REFERENCES divisions(id),
ADD FOREIGN KEY (district_id) REFERENCES districts(id),
ADD FOREIGN KEY (upazila_id) REFERENCES upazilas(id);