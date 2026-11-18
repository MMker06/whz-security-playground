CREATE TABLE IF NOT EXISTS userTBL (
  id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  realname varchar(50) NOT NULL,
  email varchar(100) NOT NULL UNIQUE,
  address varchar(255) DEFAULT NULL,
  username varchar(50) NOT NULL UNIQUE,
  password varchar(50) NOT NULL,
  level1_clear tinyint DEFAULT 0,
  level2_clear tinyint DEFAULT 0,
  level3_clear tinyint DEFAULT 0,
  score int DEFAULT 0
);

CREATE TABLE IF NOT EXISTS user_progress (
  user_id INT NOT NULL,
  stage_key VARCHAR(50) NOT NULL,
  completed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY(user_id, stage_key)
);

INSERT IGNORE INTO userTBL (realname, email, username, password) VALUES ('관리자', 'admin@whz.com', 'admin', 'admin123');
