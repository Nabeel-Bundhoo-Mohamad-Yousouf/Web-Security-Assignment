CREATE DATABASE Bibliohaha;

-- Switch to the database
USE Bibliohaha;

-- 3. Then create all the tables
-- User table (supertype)
CREATE TABLE Users (
    user_ID INT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL
);

-- Admin table (subtype of User)
CREATE TABLE Admin (
    user_ID INT PRIMARY KEY,
    FOREIGN KEY (user_ID) REFERENCES Users(user_ID)
);

-- Customer table (subtype of User)
CREATE TABLE Customer (
    user_ID INT PRIMARY KEY,
    customer_ID INT UNIQUE NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    FOREIGN KEY (user_ID) REFERENCES Users(user_ID)
);

-- Book table
CREATE TABLE Book (
    book_ID INT PRIMARY KEY,
    title TEXT,
    author TEXT,
    genre TEXT,
    book_description TEXT,
    price DECIMAL(10,2),
    rental_fee DECIMAL(10,2),
    stock_num INT,
	img_url TEXT
);

-- Registration Form table
CREATE TABLE Registration_Form (
    Registration_No INT PRIMARY KEY,
    customer_ID INT,
    date_time DATETIME,
    FOREIGN KEY (customer_ID) REFERENCES Customer(customer_ID)
);

-- Purchase table
CREATE TABLE Purchase (
    purchase_ID INT PRIMARY KEY,
    customer_ID INT,
    Book_ID INT,
    date_time DATETIME,
    quantity INT,
    price DECIMAL(10,2),
	reviewed BOOLEAN DEFAULT 0,
    FOREIGN KEY (customer_ID) REFERENCES Customer(customer_ID),
    FOREIGN KEY (Book_ID) REFERENCES Book(book_ID)
);

-- Rental table
CREATE TABLE Rental (
    Rent_ID INT PRIMARY KEY,
    customer_ID INT,
    Book_ID INT,
    start_date DATE,
    End_date DATE,
	reviewed BOOLEAN DEFAULT 0,
    FOREIGN KEY (customer_ID) REFERENCES Customer(customer_ID),
    FOREIGN KEY (Book_ID) REFERENCES Book(book_ID)
);

-- Delivery table
CREATE TABLE Delivery (
    delivery_ID INT PRIMARY KEY,
    customer_ID INT,
    book_ID INT,
    location VARCHAR(255),
    delivery_code VARCHAR(50),
    delivery_date DATE,
    FOREIGN KEY (customer_ID) REFERENCES Customer(customer_ID),
    FOREIGN KEY (book_ID) REFERENCES Book(book_ID)
);

-- Pickup table
CREATE TABLE Pickup (
    pickup_code VARCHAR(50) PRIMARY KEY,
    customer_ID INT,
    book_ID INT,
    pickup_date DATE,
    FOREIGN KEY (customer_ID) REFERENCES Customer(customer_ID),
    FOREIGN KEY (book_ID) REFERENCES Book(book_ID)
);

-- Review table
CREATE TABLE Review (
    review_ID INT PRIMARY KEY,
    customer_ID INT,
    book_ID INT,
    rating INT,
    review TEXT,
    review_description TEXT,
    date DATE,
	reviewed BOOLEAN,
    FOREIGN KEY (customer_ID) REFERENCES Customer(customer_ID),
    FOREIGN KEY (book_ID) REFERENCES Book(book_ID)
);

-- Messages table
CREATE TABLE Messages (
	meesage_ID AUTO_INCREMENT PRIMARY KEY,
	sender_name VARCHAR(100) NOT NULL,
	sender_email VARCHAR(255) NOT NULL,
	message_text TEXT NOT NULL,
	date_sent DATETIME NOT NULL
);

INSERT INTO book (book_ID,title,author,genre,book_description,price,rental_fee,stock_num,img_url)
VALUES (2134, '1984', 'George Orwell', 'Science Fiction', 'Nineteen Eighty-Four is a dystopian novel by the English writer George Orwell. It was published on 8 June 1949 by Secker & Warburg as his ninth and final completed book. Thematically, it centres on totalitarianism, mass surveillance and repressive regimentation of people and behaviours.', 420.00, 80.00, 8,"1984.jpg"),
(2135, 'Pride and Prejudice', 'Jane Austen', 'Romance' , 'Pride and Prejudice is a novel of manners by Jane Austen, first published in 1813. The story follows the main character, Elizabeth Bennet, as she deals with issues of manners, upbringing, morality, education, and marriage in the society of the landed gentry of the British Regency.',  350.00 , 65.00, 20,"Pride and Prejudice.jpg"),
(2136, 'Steve Jobs', 'Walter Isaacson', 'Bibliography', 'Steve Jobs is the authorized self-titled biography of American business magnate and Apple co-founder Steve Jobs. The book was written at the request of Jobs by Walter Isaacson, a former executive at CNN and Time who had previously written best-selling biographies of Benjamin Franklin and Albert Einstein.', 480.00, 90.00, 10,"Steve Jobs.jpg"),
(2137, 'The Catcher in the Rye', 'J.D.Salinger', 'Fiction', 'The Catcher in the Rye (1951) opens with the sixteen-year-old Holden Caulfield who is disillusioned departure from what may be the last in a series of schools that have failed to inspire, nurture, or support him, followed by a painful, sleep-deprived odyssey through the streets of New York City.', 395.00, 75.00, 3,"The Catcher in the Rye.png"),
(2138, 'The Girl with the Dragon Tattoo', 'Stieg Larsson', 'Mystery', 'The premise of the book happens around the disappearance of Harriet Vanger who is part of a notable Swedish family. Her disappearance happened over forty years ago, and journalist Michael Blomkvist is hired by an aged uncle to help investigate this mystery.', 425.00, 80.00, 14,"The Girl with the Dragon Tatoo.jpg"),
(2139, 'The Great Gatsby','F.Scott Fitzgerald', 'Fiction','The story of the mysteriously wealthy Jay Gatsby and his love for the beautiful Daisy Buchanan, of lavish parties on Long Island at a time when The New York Times noted “gin was the national drink and sex the national obsession,” it is an exquisitely crafted tale of America in the 1920s.', 450.00, 85.00, 15,"The Great Gatsby.jpg"),
(2140, 'The Name of the Wind', 'Patrick Rothfuss', 'Fantasy', 'So begins a tale unequaled in fantasy literature—the story of a hero told in his own voice. It is a tale of sorrow, a tale of survival, a tale of the search for meaning in his universe, and how that search, and the indomitable will that drove it, gave birth to a legend.', 440.00, 85.00, 16,"The Name of the Wind.jpg"),
(2141, 'To Kill a Mockingbird', 'Harper Lee', 'Fiction', 'To Kill a Mockingbird is a 1961 novel by Harper Lee. Set in small-town Alabama, the novel is a bildungsroman, or coming-of-age story, and chronicles the childhood of Scout and Jem Finch as their father Atticus defends a Black man falsely accused of rape. Scout and Jem are mocked by classmates for this.', 380.00, 70.00, 12,"To Kill a Mockingbird.jpg");

CREATE PROCEDURE book_preview_search (IN search_title INT)
BEGIN
    -- First query: Book details with aggregate ratings
    SELECT 
        b.*, 
        COUNT(r.rating) AS rating_num, 
        AVG(r.rating) AS avg_rating
    FROM book AS b 
    LEFT JOIN review AS r ON b.book_ID = r.book_ID 
    WHERE b.title = search_title 
    GROUP BY b.book_ID;
    
    -- Second query: Individual reviews for the book
    SELECT 
        r.review, 
        r.review_description, 
        r.date, 
        c.customer_name
    FROM review AS r
    JOIN customer AS c ON r.customer_ID = c.customer_ID
    WHERE r.book_ID = search_ID;
END $$

DELIMITER ;

DELIMITER $$

CREATE PROCEDURE search_books(IN search_term TEXT, IN genre_search TEXT, IN filter TEXT)
BEGIN
SELECT b.*, COALESCE(COUNT(r.rating), 0) AS rating_num, COALESCE(AVG(r.rating),0) AS avg_rating  
FROM book AS b 
LEFT JOIN review AS r ON b.book_ID = r.book_ID 
WHERE (b.title LIKE CONCAT('%',search_term, '%') OR b.author LIKE CONCAT('%',search_term, '%')) 
AND (genre_search IS NULL OR genre_search = b.genre)
GROUP BY b.book_ID
ORDER BY 
CASE WHEN filter LIKE '%author%' THEN b.author END,
CASE WHEN filter LIKE '%price_asc%' THEN b.price END ASC,
CASE WHEN filter LIKE '%price_desc%' THEN b.price END DESC,
b.title ASC
LIMIT 6;
END $$

DELIMITER ;

DELIMITER $$

CREATE PROCEDURE footer_filters (IN genre_filter TEXT)
BEGIN
SELECT b.*, COUNT(r.rating) AS rating_num, AVG(r.rating) AS avg_rating
FROM book AS b 
LEFT JOIN review AS r ON b.book_ID = r.book_ID
WHERE b.genre= genre_filter;
END $$

DELIMITER ;

CREATE VIEW view_books AS
SELECT b.*, COUNT(r.rating) AS rating_num, AVG(r.rating) AS avg_rating 
FROM book AS b 
LEFT JOIN review AS r ON b.book_ID = r.book_ID 
GROUP BY b.book_ID
ORDER BY avg_rating DESC
LIMIT 12;


CREATE VIEW vw_total_revenue AS
SELECT COALESCE(SUM(p.price * p.quantity), 0) AS total_revenue
FROM Purchase p
WHERE p.status = 'completed';








