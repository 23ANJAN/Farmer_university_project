CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100),
  email VARCHAR(100) UNIQUE,
  password VARCHAR(255),
  role ENUM('farmer','consumer')
);

CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  farmer_id INT,
  name VARCHAR(100),
  price DECIMAL(10,2),
  quantity INT,
  FOREIGN KEY (farmer_id) REFERENCES users(id)
);

CREATE TABLE negotiations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT,
  buyer_id INT,
  offer_price DECIMAL(10,2),
  FOREIGN KEY (product_id) REFERENCES products(id),
  FOREIGN KEY (buyer_id) REFERENCES users(id)
);

CREATE TABLE transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT,
  buyer_id INT,
  final_price DECIMAL(10,2),
  status VARCHAR(50),
  FOREIGN KEY (product_id) REFERENCES products(id),
  FOREIGN KEY (buyer_id) REFERENCES users(id)
);
