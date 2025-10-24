<?php
session_start();
// Se já estiver logado, redireciona para o painel
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

require_once '../app/config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                header('Location: comandas.php');
                exit();
            } else {
                $error = 'Email ou senha inválidos.';
            }
        } catch (PDOException $e) {
            $error = "Erro no banco de dados: " . $e->getMessage();
        }
    } else {
        $error = 'Preencha todos os campos.';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Restaurante</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        :root {
            --primary-color: #e74c3c;
            --primary-dark: #c0392b;
            --secondary-color: #f39c12;
            --light-bg: #f8f9fa;
            --dark-text: #2c3e50;
            --gradient: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-bg);
            color: var(--dark-text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><rect width="100" height="100" fill="%23f8f9fa"/><path d="M0 0L100 100M100 0L0 100" stroke="%23e0e0e0" stroke-width="0.5"/></svg>');
        }
        
        .login-container {
            width: 100%;
            max-width: 450px;
            margin: 0 auto;
        }
        
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .card-header {
            background: var(--gradient);
            color: white;
            padding: 30px 20px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .card-header::before {
            content: "";
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: rgba(255, 255, 255, 0.1);
            transform: rotate(45deg);
        }
        
        .logo {
            font-size: 3.5rem;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }
        
        .card-header h1 {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 5px;
            position: relative;
            z-index: 1;
        }
        
        .card-header p {
            font-size: 0.9rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }
        
        .card-body {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        
        .form-label {
            font-weight: 500;
            margin-bottom: 8px;
            display: block;
            color: var(--dark-text);
        }
        
        .input-group {
            position: relative;
        }
        
        .input-group-text {
            background: var(--light-bg);
            border: 1px solid #ddd;
            border-right: none;
            border-radius: 10px 0 0 10px;
            color: #777;
        }
        
        .form-control {
            border: 1px solid #ddd;
            border-left: none;
            border-radius: 0 10px 10px 0;
            padding: 12px 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.2);
            border-color: var(--primary-color);
        }
        
        .form-control:focus + .input-group-text {
            border-color: var(--primary-color);
        }
        
        .btn-login {
            background: var(--gradient);
            border: none;
            color: white;
            padding: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 10px;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.4);
        }
        
        .alert {
            border-radius: 10px;
            padding: 12px 15px;
            margin-bottom: 20px;
            border: none;
        }
        
        .alert-danger {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--primary-dark);
            border-left: 4px solid var(--primary-color);
        }
        
        .footer-links {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9rem;
        }
        
        .footer-links a {
            color: var(--primary-color);
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .footer-links a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }
        
        /* Responsividade */
        @media (max-width: 576px) {
            .login-container {
                max-width: 100%;
            }
            
            .card-body {
                padding: 25px 20px;
            }
            
            .card-header {
                padding: 25px 15px;
            }
            
            .logo {
                font-size: 3rem;
            }
            
            .card-header h1 {
                font-size: 1.5rem;
            }
        }
        
        /* Animações */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .login-card {
            animation: fadeIn 0.5s ease-out;
        }
        
        /* Efeito de loading no botão */
        .btn-loading {
            position: relative;
            color: transparent;
        }
        
        .btn-loading::after {
            content: "";
            position: absolute;
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin-left: -10px;
            margin-top: -10px;
            border: 2px solid #ffffff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 1s ease infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="card-header">
                <div class="logo">
                    <i class="fas fa-utensils"></i>
                </div>
                <h1>Sistema Restaurante</h1>
                <p>Entre com suas credenciais para acessar o painel</p>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="login.php" id="loginForm">
                    <div class="form-group">
                        <label for="email" class="form-label">E-mail</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" placeholder="seu@email.com" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">Senha</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Sua senha" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-login" id="loginButton">
                        <i class="fas fa-sign-in-alt me-2"></i>Entrar
                    </button>
                </form>
                
                <div class="footer-links">
                    <a href="#"><i class="fas fa-question-circle me-1"></i>Esqueci minha senha</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simulação de loading no botão de login
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const button = document.getElementById('loginButton');
            button.classList.add('btn-loading');
            button.disabled = true;
            
            // Simula um delay para demonstrar o efeito de loading
            setTimeout(function() {
                button.classList.remove('btn-loading');
                button.disabled = false;
            }, 2000);
        });
        
        // Efeito de foco nos campos de entrada
        const inputs = document.querySelectorAll('.form-control');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                if (this.value === "") {
                    this.parentElement.parentElement.classList.remove('focused');
                }
            });
        });
    </script>
</body>
</html>