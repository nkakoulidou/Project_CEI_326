CREATE DATABASE IF NOT EXISTS dioristeon
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE dioristeon;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_code VARCHAR(20) NOT NULL UNIQUE,
    title VARCHAR(150) NOT NULL,
    category VARCHAR(100) NOT NULL,
    district VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE lists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_id INT NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    publication_date DATE NOT NULL,
    status ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_lists_service
        FOREIGN KEY (service_id) REFERENCES services(id)
        ON DELETE CASCADE
);

CREATE TABLE candidates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    identity_number VARCHAR(20) NOT NULL UNIQUE,
    phone VARCHAR(30),
    district VARCHAR(100),
    specialty VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_candidates_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
);

CREATE TABLE candidate_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    notify_new_lists TINYINT(1) NOT NULL DEFAULT 1,
    notify_status_changes TINYINT(1) NOT NULL DEFAULT 1,
    notify_rank_updates TINYINT(1) NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_candidate_preferences_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
);

CREATE TABLE list_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    list_id INT NOT NULL,
    candidate_id INT NOT NULL,
    rank_position INT NOT NULL,
    points DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    status ENUM('active', 'pending', 'appointed', 'removed') NOT NULL DEFAULT 'active',
    remarks VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_list_entries_list
        FOREIGN KEY (list_id) REFERENCES lists(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_list_entries_candidate
        FOREIGN KEY (candidate_id) REFERENCES candidates(id)
        ON DELETE CASCADE,
    CONSTRAINT uq_list_candidate UNIQUE (list_id, candidate_id),
    CONSTRAINT uq_list_rank UNIQUE (list_id, rank_position)
);

CREATE TABLE applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    candidate_id INT NOT NULL,
    list_id INT NOT NULL,
    application_code VARCHAR(30) NOT NULL UNIQUE,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    current_status ENUM('submitted', 'under_review', 'approved', 'rejected', 'appointed') NOT NULL DEFAULT 'submitted',
    timeline_note VARCHAR(255),
    CONSTRAINT fk_applications_candidate
        FOREIGN KEY (candidate_id) REFERENCES candidates(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_applications_list
        FOREIGN KEY (list_id) REFERENCES lists(id)
        ON DELETE CASCADE
);

CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT DEFAULT NULL,
    description VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_activity_logs_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
);
