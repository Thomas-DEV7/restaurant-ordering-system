-- Usuários e controle de acesso
CREATE TABLE categories (
id INT AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(80) NOT NULL,
is_active TINYINT(1) NOT NULL DEFAULT 1,
sort_order INT NOT NULL DEFAULT 0
);


-- Produtos do cardápio
CREATE TABLE products (
id INT AUTO_INCREMENT PRIMARY KEY,
category_id INT NOT NULL,
name VARCHAR(120) NOT NULL,
description TEXT NULL,
price DECIMAL(10,2) NOT NULL,
is_active TINYINT(1) NOT NULL DEFAULT 1,
sku VARCHAR(50) NULL,
kitchen_printer_group ENUM('cozinha','bar','padrao') NOT NULL DEFAULT 'cozinha',
FOREIGN KEY (category_id) REFERENCES categories(id)
);


-- Comandas/Pedidos
CREATE TABLE orders (
id INT AUTO_INCREMENT PRIMARY KEY,
code VARCHAR(20) NOT NULL UNIQUE, -- ex: MESA-10, BALCAO-03, DELIVERY-XYZ
status ENUM('aberta','em_preparo','pronta','finalizada','cancelada') NOT NULL DEFAULT 'aberta',
service_fee_rate DECIMAL(5,2) NOT NULL DEFAULT 10.00, -- % configurável por pedido
service_fee_opt_in TINYINT(1) NOT NULL DEFAULT 1, -- cliente aceita (1) ou não (0)
subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
service_fee_value DECIMAL(10,2) NOT NULL DEFAULT 0.00,
total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
opened_by INT NULL,
closed_by INT NULL,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
closed_at DATETIME NULL,
note VARCHAR(255) NULL,
FOREIGN KEY (opened_by) REFERENCES users(id),
FOREIGN KEY (closed_by) REFERENCES users(id)
);


-- Itens da comanda
CREATE TABLE order_items (
id INT AUTO_INCREMENT PRIMARY KEY,
order_id INT NOT NULL,
product_id INT NOT NULL,
qty DECIMAL(10,2) NOT NULL DEFAULT 1.00,
unit_price DECIMAL(10,2) NOT NULL,
total_price DECIMAL(10,2) NOT NULL,
note VARCHAR(255) NULL,
status ENUM('novo','enviado_cozinha','em_preparo','pronto','cancelado') NOT NULL DEFAULT 'novo',
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (order_id) REFERENCES orders(id),
FOREIGN KEY (product_id) REFERENCES products(id)
);


-- Pagamentos (opcional; para detalhar formas de pagamento)
CREATE TABLE payments (
id INT AUTO_INCREMENT PRIMARY KEY,
order_id INT NOT NULL,
method ENUM('dinheiro','cartao','pix','outro') NOT NULL,
amount DECIMAL(10,2) NOT NULL,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (order_id) REFERENCES orders(id)
);


-- Usuário admin inicial (password: admin123 – mude depois)
INSERT INTO users(name,email,password_hash,role)
VALUES ('Admin','admin@local','{REPLACE_WITH_PASSWORD_HASH}','admin');