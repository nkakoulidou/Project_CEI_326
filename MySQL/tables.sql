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
    name VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255) DEFAULT NULL,
    is_selected TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE committee_lists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    specialty_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    source_url VARCHAR(255) DEFAULT NULL,
    published_year YEAR NOT NULL,
    candidate_count INT NOT NULL DEFAULT 0,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (specialty_id) REFERENCES specialties (id)
);

CREATE TABLE candidates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    committee_list_id INT DEFAULT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    specialty VARCHAR(100),
    ranking INT,
    birth_date DATE DEFAULT NULL,
    application_year YEAR DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id),
    FOREIGN KEY (committee_list_id) REFERENCES committee_lists (id)
);
