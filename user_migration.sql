-- user_migration.sql (Version 3 - Final, Non-Idempotent)

-- Step 1: Create the `users` table.
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('employee', 'executive') NOT NULL DEFAULT 'employee',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Step 2: Add a `user_id` column to the `daily_inventory` table.
ALTER TABLE `daily_inventory` ADD COLUMN `user_id` INT NOT NULL;
ALTER TABLE `daily_inventory` ADD FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE;

-- Step 3: Add a `user_id` column to the `sales` table.
ALTER TABLE `sales` ADD COLUMN `user_id` INT NOT NULL;
ALTER TABLE `sales` ADD FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE;

-- Step 4: Insert sample users.
INSERT INTO `users` (`username`, `password_hash`, `role`) VALUES
('exec_user', '$2y$10$g/xJ2e.s.0A9.b/c3.D4E.u/f5.G6H7I8J9K0L1M2N3O4P5Q6R7S', 'executive'),
('emp_user', '$2y$10$a/B1c.d2E.f3G.h4I.j5K.l6M7N8O9P0Q1R2S3T4U5V6W7X8Y9Z0', 'employee');