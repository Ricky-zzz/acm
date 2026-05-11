-- 1. Cities Table
CREATE TABLE cities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Positions Table (This handles the "Rules")
-- Example: Title='Councilor', max_votes=12
CREATE TABLE positions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    city_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    max_votes INT DEFAULT 1,
    FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE CASCADE
);

-- 3. Candidates Table
CREATE TABLE candidates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    position_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    party VARCHAR(100),
    FOREIGN KEY (position_id) REFERENCES positions(id) ON DELETE CASCADE
);

-- 4. Authorized Ballots Table (For Security Demo)
-- These are the IDs the child machine will check against
CREATE TABLE ballots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    city_id INT NOT NULL,
    ballot_number VARCHAR(50) UNIQUE NOT NULL,
    status ENUM('unused', 'used') DEFAULT 'unused',
    FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE CASCADE
);
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert the default admin (password is 'admin123')
-- We use password_hash in PHP, but for now, we'll manually insert a hashed one
INSERT INTO users (username, password) VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');