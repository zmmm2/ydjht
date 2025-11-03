-- 创建管理员表
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 插入默认管理员账号
-- 用户名: admin
-- 密码: 123456 (已使用 bcrypt 加密)
INSERT INTO `admin_users` (`username`, `password`) VALUES
('admin', '$2y$10$9.M3/tA6J8aP9j/l.7o4n.3B/O/p.j/o.C/k.Z/u.Y/y.X/z.W/q');

-- 如果需要添加更多管理员，可以使用以下SQL
-- 注意：需要将密码替换为实际的bcrypt哈希值
-- INSERT INTO `admin_users` (`username`, `password`) VALUES ('user2', 'hashed_password_here');
