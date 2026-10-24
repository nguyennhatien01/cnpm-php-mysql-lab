CREATE TABLE IF NOT EXISTS tasks (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT NULL,
    priority ENUM('Low', 'Medium', 'High') DEFAULT 'Medium', -- Phục vụ Bài tập 2
    is_done TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO tasks (title, description, priority, is_done)
SELECT 'Tìm hiểu GitHub', 'Tạo repository cá nhân và push source code.', 'High', 0
WHERE NOT EXISTS (SELECT 1 FROM tasks);
ALTER TABLE tasks ADD COLUMN priority VARCHAR(20) DEFAULT 'Medium';
