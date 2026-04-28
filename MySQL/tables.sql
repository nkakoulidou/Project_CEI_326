CREATE DATABASE IF NOT EXISTS dioristeon;

USE dioristeon;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE specialties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE candidates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    birth_date DATE,
    specialty_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (specialty_id) REFERENCES specialties (id) ON DELETE SET NULL
);

CREATE INDEX idx_candidate_name ON candidates (first_name, last_name);

CREATE TABLE lists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    year INT NOT NULL,
    specialty_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (specialty_id) REFERENCES specialties (id) ON DELETE CASCADE
);

CREATE TABLE list_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    list_id INT NOT NULL,
    candidate_id INT NOT NULL,
    position INT NOT NULL,
    total_score DECIMAL(6, 2),
    degree_date DATE,
    degree_grade DECIMAL(4, 2),
    service_points DECIMAL(5, 2),
    application_date DATE,
    notes VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (list_id) REFERENCES lists (id) ON DELETE CASCADE,
    FOREIGN KEY (candidate_id) REFERENCES candidates (id) ON DELETE CASCADE,
    UNIQUE (list_id, candidate_id)
);

CREATE INDEX idx_list_entries_position ON list_entries (position);

CREATE INDEX idx_list_entries_score ON list_entries (total_score);

CREATE TABLE user_candidate_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    candidate_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    FOREIGN KEY (candidate_id) REFERENCES candidates (id) ON DELETE CASCADE,
    UNIQUE (user_id, candidate_id)
);

CREATE TABLE tracked_candidates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    candidate_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    FOREIGN KEY (candidate_id) REFERENCES candidates (id) ON DELETE CASCADE,
    UNIQUE (user_id, candidate_id)
);



CREATE TABLE applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    candidate_id INT NOT NULL,
    list_id INT NOT NULL,
    application_code VARCHAR(30) NOT NULL UNIQUE,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    current_status ENUM('submitted', 'under_review', 'approved', 'rejected', 'appointed') NOT NULL DEFAULT 'submitted',
    timeline_note VARCHAR(255),
    FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE CASCADE,
    FOREIGN KEY (list_id) REFERENCES lists(id) ON DELETE CASCADE
);
