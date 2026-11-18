CREATE TABLE IF NOT EXISTS userTBL (
  id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  realname varchar(50) NOT NULL DEFAULT 'fake',
  email varchar(100) NOT NULL,
  address varchar(255) DEFAULT NULL,
  username varchar(50) NOT NULL,
  password varchar(50) NOT NULL,
  level1_clear tinyint DEFAULT '0',
  level2_clear tinyint DEFAULT '0'
);

CREATE TABLE IF NOT EXISTS boardTBL (
  board_id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  username varchar(20) NOT NULL,
  title varchar(512) NOT NULL,
  content text NOT NULL,
  date varchar(255) NOT NULL,
  views varchar(20) NOT NULL DEFAULT '0',
  main_uid int DEFAULT NULL,
  solve_token varchar(64) DEFAULT NULL
);

-- 쿠키 변조 실습용 계정 (id=1, id=2)
INSERT IGNORE INTO userTBL (email, username, password) VALUES ('admin_board@whz.com', 'admin', 'board_admin_pass');
INSERT IGNORE INTO userTBL (email, username, password) VALUES ('victim@whz.com', 'victim', 'victim123');

-- 기본 게시글
INSERT IGNORE INTO boardTBL (board_id, username, title, content, date, views)
  VALUES (1, 'admin', '공지사항', '비속어 사용 금지입니다.', '2024-05-15 10:00:00', '15');
INSERT IGNORE INTO boardTBL (board_id, username, title, content, date, views)
  VALUES (2, 'victim', '도와주세요', '게시판이 이상해요!', '2024-05-15 11:00:00', '2');
