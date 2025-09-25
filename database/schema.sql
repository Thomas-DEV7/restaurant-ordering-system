-- Tabela de Usuários
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'atendente') NOT NULL
);

-- Tabela de Produtos (Cardápio)
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    category ENUM('prato', 'bebida', 'diversos') NOT NULL,
    image VARCHAR(255) NULL,
    status ENUM('active', 'inactive') DEFAULT 'active'
);

INSERT INTO products (name, description, price, category, image) VALUES (?, ?, ?, ?, ?)

