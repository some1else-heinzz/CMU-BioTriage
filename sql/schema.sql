CREATE DATABASE IF NOT EXISTS snakebite CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE snakebite;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('staff','admin') NOT NULL DEFAULT 'staff',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE facilities (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  place VARCHAR(255) NOT NULL DEFAULT '',
  phone VARCHAR(30) NOT NULL DEFAULT '',
  active TINYINT(1) NOT NULL DEFAULT 1
);

-- Append-only: a new row per confirmation. Never UPDATE or DELETE rows here.
CREATE TABLE confirmations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  facility_id INT NOT NULL,
  user_id INT NOT NULL,
  status ENUM('available','restock','out') NOT NULL,
  method ENUM('phone','pgb_post','in_person','other') NOT NULL,
  restock_date DATE NULL,
  restock_source VARCHAR(500) NOT NULL DEFAULT '',
  source_person VARCHAR(100) NOT NULL DEFAULT '',
  note VARCHAR(500) NOT NULL DEFAULT '',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (facility_id) REFERENCES facilities(id),
  FOREIGN KEY (user_id) REFERENCES users(id),
  INDEX idx_fac_time (facility_id, created_at)
);

-- Facilities named in the Discover interviews. No stock data is pre-filled.
INSERT INTO facilities (name, place) VALUES
('Bukidnon Provincial Hospital (BPH)', 'Maramag, Bukidnon. Nearest known stock, about 15 km from CMU.'),
('Bukidnon Provincial Medical Center (BPMC)', 'Malaybalay, Bukidnon'),
('Valencia Sanitarium / Adventist Medical Center', 'Named by the ER nurse; location to be confirmed'),
('Cagayan de Oro City hospitals', 'Referral destination named by the ER nurse; hospital not specified');
