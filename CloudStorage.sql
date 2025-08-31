CREATE DATABASE file_repo;
USE file_repo;
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL
);
CREATE TABLE folders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  folder_name VARCHAR(255) NOT NULL,
  parent_folder_id INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  deleted_at TIMESTAMP NULL DEFAULT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (parent_folder_id) REFERENCES folders(id),
  UNIQUE (user_id, folder_name, parent_folder_id)
);
CREATE TABLE files (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  folder_id INT DEFAULT NULL,
  filename VARCHAR(255) NOT NULL,
  path VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  deleted_at TIMESTAMP NULL DEFAULT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (folder_id) REFERENCES folders(id),
  UNIQUE (user_id, folder_id, filename)
);
CREATE TABLE shares (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  type ENUM('file', 'folder') NOT NULL,
  item_id INT NOT NULL,
  link VARCHAR(255) UNIQUE NOT NULL,
  permission ENUM('view', 'edit', 'upload') NOT NULL,
  password VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);
CREATE TABLE trash_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    original_path VARCHAR(255) NOT NULL,
    user_id INT NOT NULL,
    folder_id INT,
    deleted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    file_content LONGBLOB NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (folder_id) REFERENCES folders(id)
);
CREATE TABLE activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(50),
    item_type VARCHAR(20),
    item_id INT,
    item_name VARCHAR(255),
    details TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);