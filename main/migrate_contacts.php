<?php
require_once __DIR__ . "/includes/functions.php";
$db = getDB();
$db->exec("CREATE TABLE IF NOT EXISTS contacts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT DEFAULT NULL,
  guest_name VARCHAR(100) DEFAULT NULL,
  guest_email VARCHAR(150) DEFAULT NULL,
  subject VARCHAR(255) DEFAULT NULL,
  message TEXT NOT NULL,
  status ENUM('new','read','replied') NOT NULL DEFAULT 'new',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$db->exec("CREATE TABLE IF NOT EXISTS contact_replies (
  id INT AUTO_INCREMENT PRIMARY KEY,
  contact_id INT NOT NULL,
  admin_id INT NOT NULL,
  reply_text TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE,
  FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "Tables created OK";
