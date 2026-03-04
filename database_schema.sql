-- Household Services Platform Database Schema
-- Create database
CREATE DATABASE IF NOT EXISTS chores_app;
USE chores_app;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    password VARCHAR(255) NOT NULL,
    profile_picture VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Workers table
CREATE TABLE workers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    skills TEXT NOT NULL,
    profile_picture VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bookings table
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_email VARCHAR(100) NOT NULL,
    service VARCHAR(100) NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    status ENUM('pending', 'accepted', 'completed', 'cancelled') DEFAULT 'pending',
    worker_email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_email) REFERENCES users(email) ON DELETE CASCADE,
    FOREIGN KEY (worker_email) REFERENCES workers(email) ON DELETE SET NULL
);

-- Ratings table
CREATE TABLE ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_email VARCHAR(100) NOT NULL,
    worker_email VARCHAR(100) NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    review TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_email) REFERENCES users(email) ON DELETE CASCADE,
    FOREIGN KEY (worker_email) REFERENCES workers(email) ON DELETE CASCADE,
    UNIQUE (user_email, worker_email)
);

-- Service Pricing table (Cameroon XAF currency)
CREATE TABLE service_pricing (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service VARCHAR(100) UNIQUE NOT NULL,
    base_price DECIMAL(10,2) NOT NULL,
    urgent_surcharge DECIMAL(10,2) DEFAULT 0.00,
    currency VARCHAR(3) DEFAULT 'XAF',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert Cameroon pricing (XAF)
INSERT INTO service_pricing (service, base_price, urgent_surcharge, currency) VALUES
('cleaning', 15000.00, 5000.00, 'XAF'),
('laundry', 8000.00, 3000.00, 'XAF'),
('grocery_runs', 12000.00, 4000.00, 'XAF'),
('minor_repairs', 20000.00, 8000.00, 'XAF'),
('babysitting', 18000.00, 6000.00, 'XAF'),
('cooking', 16000.00, 4500.00, 'XAF');

-- Insert sample data (optional)
-- INSERT INTO users (name, email, phone, address, password) VALUES 
-- ('John Doe', 'john@example.com', '1234567890', '123 Main St', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- INSERT INTO workers (name, email, phone, password, skills) VALUES 
-- ('Jane Smith', 'jane@example.com', '0987654321', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cleaning,cooking,laundry');
