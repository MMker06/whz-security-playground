-- 쇼핑몰 전용 가짜 유저 테이블
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

CREATE TABLE IF NOT EXISTS products (
  id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name varchar(50) NOT NULL,
  price decimal(12,2) NOT NULL,
  description varchar(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS orders (
  id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id int NOT NULL,
  product_id int NOT NULL,
  quantity int NOT NULL DEFAULT '1',
  order_date datetime DEFAULT CURRENT_TIMESTAMP
);

-- SQL Injection 실습용: 비밀번호는 의도적으로 복잡하게 설정 (정상 로그인 불가)
INSERT IGNORE INTO userTBL (email, username, password) VALUES ('admin_shop@whz.com', 'admin', 'this_is_fake_password_1234');
INSERT IGNORE INTO userTBL (email, username, password) VALUES ('guest@whz.com', 'guest', 'guest');

INSERT IGNORE INTO products (name, price, description) VALUES ('컴퓨터', 1500000.00, '최신형 게이밍 PC');
INSERT IGNORE INTO products (name, price, description) VALUES ('모니터', 500000.00, '32인치 4K 모니터');
INSERT IGNORE INTO products (name, price, description) VALUES ('키보드', 120000.00, '기계식 갈축 키보드');
