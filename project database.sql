CREATE DATABASE IF NOT EXISTS my_project_db;
USE my_project_db;

-- 1. Admin Table
CREATE TABLE admin (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'Admin'
);

-- 2. Entrepreneur / Agent Table
CREATE TABLE agent (
    agent_id INT AUTO_INCREMENT PRIMARY KEY,
    qualification TEXT,
    bio TEXT,
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending'
);

-- 3. End User Table
CREATE TABLE end_user (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL
);

-- 4. Template Table
CREATE TABLE template (
    template_id INT AUTO_INCREMENT PRIMARY KEY,
    idea_id INT,
    category_id INT,
    title VARCHAR(150) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 5. Business Idea Table
CREATE TABLE business_idea (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    industry_sector VARCHAR(100),
    title VARCHAR(150) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    investment_required DECIMAL(10,2)
);

-- 6. Query Table
CREATE TABLE query (
    query_id INT AUTO_INCREMENT PRIMARY KEY,
    question TEXT NOT NULL,
    answer TEXT,
    status ENUM('Pending', 'Answered') DEFAULT 'Pending'
);

-- 7. Feedback Table
CREATE TABLE feedback (
    feedback_id INT AUTO_INCREMENT PRIMARY KEY,
    message TEXT NOT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);