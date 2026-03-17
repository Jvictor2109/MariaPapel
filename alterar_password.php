<?php
// alterar_password.php - Alteração obrigatória de password
session_start();

// Verificar se o utilizador está autenticado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include('db_connect.php');

// Verificar na base de dados se precisa alterar a password
$sql_check = "SELECT password_alterada FROM utilizador WHERE id_utilizador = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("i", $_SESSION['user_id']);
$stmt_check->execute();
$result_check = $stmt_check->get_result();
$user_data = $result_check->fetch_assoc();
$stmt_check->close();

// Se password_alterada = 1 e não foi forçado pelo primeiro acesso, redirecionar
$is_first_access = isset($_GET['first']) && $_GET['first'] == 1;
if ($user_data['password_alterada'] == 1 && !$is_first_access && !isset($_SESSION['force_password_change'])) {
    header('Location: index.php');
    exit();
}

$error_message = '';
$success_message = '';

// Processar alteração de password
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validações
    if (empty($new_password) || empty($confirm_password)) {
        $error_message = 'Por favor, preencha todos os campos.';
    } elseif (!$is_first_access && empty($current_password)) {
        $error_message = 'Por favor, preencha a password atual.';
    } elseif ($new_password !== $confirm_password) {
        $error_message = 'As novas passwords não coincidem.';
    } elseif (strlen($new_password) < 8) {
        $error_message = 'A nova password deve ter pelo menos 8 caracteres.';
    } else {
        try {
            // Se for primeiro acesso, não precisamos verificar a password atual
            if ($is_first_access) {
                $current_password_valid = true;
            } else {
                // Verificar password atual
                $sql = "SELECT password_hash FROM utilizador WHERE id_utilizador = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $_SESSION['user_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                $current_password_valid = ($user && password_verify($current_password, $user['password_hash']));
                $stmt->close();
            }
            
            if ($current_password_valid) {
                // Password atual correta (ou primeiro acesso) - atualizar
                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                
                $update_sql = "UPDATE utilizador 
                               SET password_hash = ?, 
                                   password_alterada = 1,
                                   tentativas_falhas = 0,
                                   ultimo_login = NOW()
                               WHERE id_utilizador = ?";
                
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("si", $new_password_hash, $_SESSION['user_id']);
                
                if ($update_stmt->execute()) {
                    // Atualizar sessão
                    $_SESSION['password_alterada'] = 1;
                    if (isset($_SESSION['require_password_change'])) {
                        unset($_SESSION['require_password_change']);
                    }
                    if (isset($_SESSION['force_password_change'])) {
                        unset($_SESSION['force_password_change']);
                    }
                    
                    $success_message = 'Password alterada com sucesso! Redirecionando para a página inicial...';
                    echo '<script>
                        setTimeout(function() {
                            window.location.href = "index.php";
                        }, 2000);
                    </script>';
                } else {
                    $error_message = 'Erro ao atualizar a password.';
                }
                
                $update_stmt->close();
            } else {
                $error_message = 'Password atual incorreta.';
            }
            
        } catch (Exception $e) {
            error_log("Erro ao alterar password: " . $e->getMessage());
            $error_message = 'Erro no sistema. Por favor, tente mais tarde.';
        }
    }
}
?>
<!DOCTYPE HTML>
<html>
<head>
    <title>MPP - Alterar Password</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
    <link rel="stylesheet" href="assets/css/main.css" />
    <style>
        .password-strength {
            margin-top: 5px;
            font-size: 0.85em;
        }
        .strength-weak { color: #dc3545; }
        .strength-medium { color: #ffc107; }
        .strength-strong { color: #28a745; }
    </style>
</head>
<body class="is-preload">
    <div id="wrapper">
        <div id="main">
            <div class="inner">
                <header id="header">
                    <a href="index.php" class="logo">
                        <span class="logo-text">Maria Papel Papelaria - Alterar Password</span>
                    </a>
                </header>

                <section>
                    <header class="major">
                        <h2><?php echo $is_first_access ? '🔐 Primeiro Acesso - Alteração Obrigatória' : '🔄 Alterar Password'; ?></h2>
                        <?php if ($is_first_access): ?>
                            <p>É obrigatório alterar a password no primeiro acesso por motivos de segurança.</p>
                        <?php else: ?>
                            <p>Altere a sua password de acesso ao sistema.</p>
                        <?php endif; ?>
                    </header>
                    
                    <?php if (!empty($error_message)): ?>
                        <div class="mensagem error">
                            <strong>Erro:</strong> <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success_message)): ?>
                        <div class="mensagem success">
                            <strong>Sucesso:</strong> <?php echo htmlspecialchars($success_message); ?>
                            <p>Se não for redirecionado automaticamente, <a href="index.php">clique aqui</a>.</p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-6 col-12-small">
                            <div class="box">
                                <form method="post" action="" id="passwordForm">
                                    <div class="row gtr-uniform">
                                        <?php if (!$is_first_access): ?>
                                            <div class="col-12">
                                                <label for="current_password">Password Atual *</label>
                                                <input type="password" name="current_password" id="current_password" 
                                                       placeholder="Digite a sua password atual" required />
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="col-12">
                                            <label for="new_password">Nova Password *</label>
                                            <input type="password" name="new_password" id="new_password" 
                                                   placeholder="Mínimo 8 caracteres" required />
                                            <div id="passwordStrength" class="password-strength"></div>
                                        </div>
                                        
                                        <div class="col-12">
                                            <label for="confirm_password">Confirmar Nova Password *</label>
                                            <input type="password" name="confirm_password" id="confirm_password" 
                                                   placeholder="Digite novamente a nova password" required />
                                            <div id="passwordMatch" class="password-strength"></div>
                                        </div>
                                        
                                        <div class="col-12">
                                            <p><small>* Campos obrigatórios</small></p>
                                            <ul class="actions">
                                                <li><input type="submit" value="Alterar Password" class="primary" id="submitBtn" /></li>
                                                <?php if (!$is_first_access): ?>
                                                    <li><a href="index.php" class="button">Cancelar</a></li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <div class="col-6 col-12-small">
                            <div class="box">
                                <h3>📋 Política de Passwords</h3>
                                <ul>
                                    <li><strong>Mínimo 8 caracteres</strong></li>
                                    <li>Recomendado usar combinação de:
                                        <ul>
                                            <li>Letras maiúsculas (A-Z)</li>
                                            <li>Letras minúsculas (a-z)</li>
                                            <li>Números (0-9)</li>
                                            <li>Símbolos (!@#$%^&*)</li>
                                        </ul>
                                    </li>
                                    <li>Evite passwords comuns:
                                        <ul>
                                            <li>"123456", "password", "qwerty"</li>
                                            <li>Datas de nascimento</li>
                                            <li>Nomes próprios</li>
                                        </ul>
                                    </li>
                                    <li>A password será atualizada em todos os dispositivos</li>
                                </ul>
                                
                                <?php if ($is_first_access): ?>
                                    <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-radius: 5px; border-left: 4px solid #ffc107;">
                                        <h4>⚠️ Aviso Importante</h4>
                                        <p>Esta é uma alteração <strong>obrigatória</strong> por motivos de segurança.</p>
                                        <p>O sistema não permitirá o acesso até que altere a password.</p>
                                        <p>Guarde a nova password em local seguro.</p>
                                    </div>
                                <?php else: ?>
                                    <div style="margin-top: 20px; padding: 15px; background: #e7f3fe; border-radius: 5px; border-left: 4px solid #2196F3;">
                                        <h4>💡 Dica de Segurança</h4>
                                        <p>Altere a sua password regularmente (recomendado a cada 90 dias).</p>
                                        <p>Não use a mesma password em vários serviços.</p>
                                        <p>Considere usar um gestor de passwords.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
    
    <script src="assets/js/jquery.min.js"></script>
    <script>
    $(document).ready(function() {
        $('#new_password').focus();
        
        // Função para verificar força da password
        function checkPasswordStrength(password) {
            let strength = 0;
            let tips = "";
            
            if (password.length < 8) {
                tips = "Muito curta";
                return {strength: 0, tips: tips};
            }
            
            // Verificar critérios
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]+/)) strength++;
            if (password.match(/[A-Z]+/)) strength++;
            if (password.match(/[0-9]+/)) strength++;
            if (password.match(/[$@#&!]+/)) strength++;
            
            switch(strength) {
                case 1:
                    tips = "Muito fraca";
                    break;
                case 2:
                    tips = "Fraca";
                    break;
                case 3:
                    tips = "Média";
                    break;
                case 4:
                    tips = "Forte";
                    break;
                case 5:
                    tips = "Muito forte";
                    break;
            }
            
            return {strength: strength, tips: tips};
        }
        
        // Verificar força da password em tempo real
        $('#new_password').on('keyup', function() {
            const password = $(this).val();
            const strengthInfo = checkPasswordStrength(password);
            const strengthDiv = $('#passwordStrength');
            
            if (password.length === 0) {
                strengthDiv.html('');
                return;
            }
            
            let strengthClass = 'strength-weak';
            if (strengthInfo.strength >= 3) strengthClass = 'strength-medium';
            if (strengthInfo.strength >= 4) strengthClass = 'strength-strong';
            
            strengthDiv.html(`Força: <span class="${strengthClass}">${strengthInfo.tips}</span>`);
            
            // Validar comprimento mínimo
            if (password.length > 0 && password.length < 8) {
                $(this).css('border-color', '#dc3545');
            } else if (password.length >= 8) {
                $(this).css('border-color', '#28a745');
            } else {
                $(this).css('border-color', '');
            }
        });
        
        // Verificar se passwords coincidem
        $('#confirm_password').on('keyup', function() {
            const password = $('#new_password').val();
            const confirm = $(this).val();
            const matchDiv = $('#passwordMatch');
            
            if (confirm.length === 0) {
                matchDiv.html('');
                return;
            }
            
            if (password !== confirm) {
                $(this).css('border-color', '#dc3545');
                matchDiv.html('<span class="strength-weak">As passwords não coincidem</span>');
            } else {
                $(this).css('border-color', '#28a745');
                matchDiv.html('<span class="strength-strong">✓ As passwords coincidem</span>');
            }
        });
        
        // Validar formulário antes de enviar
        $('#passwordForm').on('submit', function(e) {
            const password = $('#new_password').val();
            const confirm = $('#confirm_password').val();
            const current = $('#current_password').val();
            
            <?php if (!$is_first_access): ?>
            if (!current) {
                alert('Por favor, preencha a password atual.');
                e.preventDefault();
                return false;
            }
            <?php endif; ?>
            
            if (password.length < 8) {
                alert('A nova password deve ter pelo menos 8 caracteres.');
                e.preventDefault();
                return false;
            }
            
            if (password !== confirm) {
                alert('As passwords não coincidem.');
                e.preventDefault();
                return false;
            }
            
            return true;
        });
    });
    </script>
</body>
</html>
<?php
if (isset($conn)) {
    $conn->close();
}
?>