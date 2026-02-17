-- Software Consultancy Management System Database Schema

CREATE DATABASE IF NOT EXISTS consultancy_management;
USE consultancy_management;

-- Users table (for both Admin and Clients)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'client') NOT NULL DEFAULT 'client',
    phone VARCHAR(20),
    company_name VARCHAR(100),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive') DEFAULT 'active'
);

-- Services table
CREATE TABLE services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    category VARCHAR(50),
    base_price DECIMAL(10, 2),
    duration_days INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive') DEFAULT 'active'
);

-- Service Requests table
CREATE TABLE service_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    service_id INT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    status ENUM('requested', 'approved', 'in_progress', 'completed', 'rejected') DEFAULT 'requested',
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL
);

-- Proposals table
CREATE TABLE proposals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    request_id INT NOT NULL,
    admin_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    estimated_cost DECIMAL(10, 2) NOT NULL,
    estimated_duration INT,
    terms_conditions TEXT,
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    responded_at TIMESTAMP NULL,
    FOREIGN KEY (request_id) REFERENCES service_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Projects table
CREATE TABLE projects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    request_id INT NOT NULL,
    proposal_id INT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    status ENUM('requested', 'approved', 'in_progress', 'completed') DEFAULT 'requested',
    start_date DATE,
    end_date DATE,
    progress_percentage INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES service_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (proposal_id) REFERENCES proposals(id) ON DELETE SET NULL
);

-- Milestones table
CREATE TABLE milestones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    due_date DATE,
    status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- Invoices table
CREATE TABLE invoices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    tax_amount DECIMAL(10, 2) DEFAULT 0,
    total_amount DECIMAL(10, 2) NOT NULL,
    description TEXT,
    status ENUM('pending', 'paid', 'overdue') DEFAULT 'pending',
    issued_date DATE NOT NULL,
    due_date DATE NOT NULL,
    paid_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- Payments table
CREATE TABLE payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    invoice_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_method VARCHAR(50),
    transaction_id VARCHAR(100),
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
);

-- Messages table
CREATE TABLE messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    subject VARCHAR(200),
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Notifications table
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT,
    type VARCHAR(50),
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert default admin user (password: admin123)
-- Note: Run setup.php after importing this schema to ensure the password hash is correct
-- Or manually update the password using: UPDATE users SET password = '$2y$10$...' WHERE username = 'admin';
INSERT INTO users (username, email, password, full_name, role, phone, company_name) VALUES
('admin', 'admin@consultancy.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'System Administrator', 'admin', '1234567890', 'Consultancy Management System');

-- Insert sample services
INSERT INTO services (name, description, category, base_price, duration_days) VALUES
('Web Development', 'Custom web application development using modern technologies', 'Development', 5000.00, 30),
('Mobile App Development', 'Native and cross-platform mobile application development', 'Development', 8000.00, 45),
('Software Consulting', 'Expert consultation on software architecture and best practices', 'Consulting', 2000.00, 7),
('UI/UX Design', 'User interface and user experience design services', 'Design', 3000.00, 21),
('Database Design', 'Database architecture and optimization services', 'Development', 2500.00, 14),
('Cloud Migration', 'Migrating applications to cloud platforms', 'Infrastructure', 6000.00, 35),
('Security Audit', 'Comprehensive security assessment and recommendations', 'Security', 4000.00, 14),
('Maintenance & Support', 'Ongoing maintenance and technical support', 'Support', 1500.00, 30);
