CREATE DATABASE IF NOT EXISTS Bibliohaha;
USE Bibliohaha;

-- Users table
CREATE TABLE Users (
    user_ID INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

-- Admin table
CREATE TABLE Admin (
    user_ID INT PRIMARY KEY,
    FOREIGN KEY (user_ID) REFERENCES Users(user_ID) ON DELETE CASCADE
);

-- Customer table
CREATE TABLE Customer (
    customer_ID INT AUTO_INCREMENT PRIMARY KEY,
    user_ID INT UNIQUE NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    FOREIGN KEY (user_ID) REFERENCES Users(user_ID) ON DELETE CASCADE
);

-- Book table (column names don't match with the one on main branch, pls fix it accordingly)
CREATE TABLE Book (
    book_ID INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    genre VARCHAR(100),
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    rental_fee DECIMAL(10,2) NOT NULL,
    stock_quantity INT NOT NULL DEFAULT 0,
    image_url VARCHAR(500),
    INDEX idx_title (title),
    INDEX idx_genre (genre)
);

-- Purchase table
CREATE TABLE Purchase (
    purchase_ID INT AUTO_INCREMENT PRIMARY KEY,
    customer_ID INT NOT NULL,
    book_ID INT NOT NULL,
    date_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    reviewed TINYINT(1) DEFAULT 0,
    FOREIGN KEY (customer_ID) REFERENCES Customer(customer_ID) ON DELETE CASCADE,
    FOREIGN KEY (book_ID) REFERENCES Book(book_ID) ON DELETE CASCADE
);

-- Rental table
CREATE TABLE Rental (
    Rent_ID INT AUTO_INCREMENT PRIMARY KEY,
    customer_ID INT NOT NULL,
    Book_ID INT NOT NULL,
    start_date DATE NOT NULL,
    End_date DATE NOT NULL,
    reviewed TINYINT(1) DEFAULT 0,
    FOREIGN KEY (customer_ID) REFERENCES Customer(customer_ID) ON DELETE CASCADE,
    FOREIGN KEY (Book_ID) REFERENCES Book(book_ID) ON DELETE CASCADE
);

-- Review table
CREATE TABLE Review (
    review_ID INT AUTO_INCREMENT PRIMARY KEY,
    customer_ID INT NOT NULL,
    book_ID INT NOT NULL,
    rating TINYINT CHECK (rating BETWEEN 1 AND 5),
    review TEXT,
    review_description TEXT,
    date DATE DEFAULT (CURRENT_DATE),
    FOREIGN KEY (customer_ID) REFERENCES Customer(customer_ID) ON DELETE CASCADE,
    FOREIGN KEY (book_ID) REFERENCES Book(book_ID) ON DELETE CASCADE
);

-- Messages ...message instead of meesage ...need to be fixed on main branch
CREATE TABLE Messages (
    message_ID INT AUTO_INCREMENT PRIMARY KEY,
    sender_name VARCHAR(100) NOT NULL,
    sender_email VARCHAR(255) NOT NULL,
    message_text TEXT NOT NULL,
    date_sent DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Sample Books (it might conflict with what's on main branch)
INSERT INTO Book (book_ID, title, author, genre, description, price, rental_fee, stock_quantity, image_url) VALUES
(2134, '1984', 'George Orwell', 'Science Fiction', 'Nineteen Eighty-Four is a dystopian novel...', 420.00, 80.00, 8, '1984.jpg'),
(2135, 'Pride and Prejudice', 'Jane Austen', 'Romance', 'Pride and Prejudice is a novel of manners...', 350.00, 65.00, 20, 'Pride and Prejudice.jpg'),
(2136, 'Steve Jobs', 'Walter Isaacson', 'Biography', 'Steve Jobs is the authorized biography...', 480.00, 90.00, 10, 'Steve Jobs.jpg'),
(2137, 'The Catcher in the Rye', 'J.D. Salinger', 'Fiction', 'The Catcher in the Rye opens with Holden...', 395.00, 75.00, 3, 'The Catcher in the Rye.png'),
(2138, 'The Girl with the Dragon Tattoo', 'Stieg Larsson', 'Mystery', 'The premise revolves around the disappearance...', 425.00, 80.00, 14, 'The Girl with the Dragon Tattoo.jpg'),
(2139, 'The Great Gatsby', 'F. Scott Fitzgerald', 'Fiction', 'The story of the mysteriously wealthy Jay Gatsby...', 450.00, 85.00, 15, 'The Great Gatsby.jpg'),
(2140, 'The Name of the Wind', 'Patrick Rothfuss', 'Fantasy', 'So begins a tale unequaled in fantasy literature...', 440.00, 85.00, 16, 'The Name of the Wind.jpg'),
(2141, 'To Kill a Mockingbird', 'Harper Lee', 'Fiction', 'Set in small-town Alabama, chronicles the childhood...', 380.00, 70.00, 12, 'To Kill a Mockingbird.jpg');

-- Total Revenue View
CREATE OR REPLACE VIEW vw_total_revenue AS
SELECT COALESCE(SUM(price * quantity), 0) AS total_revenue
FROM Purchase
WHERE status = 'completed';

-- Sample Admin User (password = "admin123" hashed with password_hash())
INSERT INTO Users (username, password) VALUES 
('admin', '$2y$10$JDJ5JDEwJEdvLmJ5dGVzJDEwJHMxMiRjYXJyeS5zYWx0JGRhdGE=');
-- Note to Nabeel:  real hash for "admin123" → use password_hash('admin123', PASSWORD_DEFAULT) in PHP

INSERT INTO Admin (user_ID) VALUES (1);
