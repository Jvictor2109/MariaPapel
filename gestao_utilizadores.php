<?php
session_start();

if (!isset($_SESSION['user_id'])){
    header('Location: login.php');
    exit();
}

if (isset($_SESSION['e_administrador']) && $_SESSION['e_administrador'] === 0) {
    header('Location: index.php');
    exit();
}

require_once 'db_connect.php';

$mensagem = "";
$tipo_mensagem = "";

// Processar operações
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Inserir novo utilizador
    if (isset($_POST['inserir'])) {
        $username = $_POST['username'];
        $email = $_POST['email'];
        $nome = $_POST['nome'];
        $admin = isset($_POST['admin']) ? 1 : 0;
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        $password = "mpp3";
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO utilizador (username, email_utilizador, nome_utilizador, password_hash, e_administrador, utilizador_ativo, password_alterada, tentativas_falhas) 
                VALUES (?, ?, ?, ?, ?, ?, 0, 0)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssii", $username, $email, $nome, $password_hash, $admin, $ativo);

        if ($stmt->execute()) {
            $mensagem = "Utilizador inserido com sucesso! Password inicial: <strong>mpp3</strong>. O utilizador deve alterar a password no primeiro login.";
            $tipo_mensagem = "success";
        } else {
            $mensagem = "Erro ao inserir utilizador: " . $stmt->error;
            $tipo_mensagem = "error";
        }
        $stmt->close();
    }

    // Editar utilizador
    if (isset($_POST['editar'])) {
        $id = $_POST['id_utilizador'];
        $username = $_POST['username'];
        $email = $_POST['email'];
        $nome = $_POST['nome'];
        $admin = isset($_POST['admin']) ? 1 : 0;
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        $sql = "UPDATE utilizador 
                SET username=?, email_utilizador=?, nome_utilizador=?, e_administrador=?, utilizador_ativo=? 
                WHERE id_utilizador=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssiii", $username, $email, $nome, $admin, $ativo, $id);

        if ($stmt->execute()) {
            $mensagem = "Utilizador atualizado com sucesso!";
            $tipo_mensagem = "success";
        } else {
            $mensagem = "Erro ao atualizar utilizador: " . $stmt->error;
            $tipo_mensagem = "error";
        }
        $stmt->close();
    }

    // Limpar password
    if (isset($_POST['limpar_password'])) {
        $id = $_POST['id_utilizador'];

        $password = "mpp3";
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $sql = "UPDATE utilizador 
                SET password_hash=?, password_alterada=0, tentativas_falhas=0 
                WHERE id_utilizador=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $password_hash, $id);

        if ($stmt->execute()) {
            $mensagem = "Password limpa com sucesso para <strong>mpp3</strong>. O utilizador será obrigado a alterar no próximo login.";
            $tipo_mensagem = "success";
        } else {
            $mensagem = "Erro ao limpar password: " . $stmt->error;
            $tipo_mensagem = "error";
        }
        $stmt->close();
    }

    // NOTA: apagar utilizador REMOVIDO (não é possível eliminar utilizadores)
}

// Toggle: mostrar inativos?
$mostrar_inativos = (isset($_GET['mostrar_inativos']) && $_GET['mostrar_inativos'] == '1') ? 1 : 0;

// Helper para manter querystring nos links (preserva o toggle)
function qs_toggle($mostrar_inativos) {
    return $mostrar_inativos ? '&mostrar_inativos=1' : '';
}

// Obter utilizadores (por defeito só ativos; opcionalmente todos)
if ($mostrar_inativos) {
    $sql = "SELECT * FROM utilizador ORDER BY id_utilizador DESC LIMIT 20";
    $titulo_lista = "Lista de Utilizadores (Ativos e Inativos)";
    $texto_lista = "A mostrar os últimos 20 utilizadores (ativos e inativos).";
} else {
    $sql = "SELECT * FROM utilizador WHERE utilizador_ativo = 1 ORDER BY id_utilizador DESC LIMIT 20";
    $titulo_lista = "Lista de Utilizadores Ativos";
    $texto_lista = "A mostrar os últimos 20 utilizadores <strong>ativos</strong>.";
}

$result = $conn->query($sql);
$total_utilizadores = $result ? $result->num_rows : 0;

// Obter utilizador para edição
$utilizador_editar = null;
if (isset($_GET['editar'])) {
    $id_editar = intval($_GET['editar']);
    $sql = "SELECT * FROM utilizador WHERE id_utilizador=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_editar);
    $stmt->execute();
    $result_editar = $stmt->get_result();
    $utilizador_editar = $result_editar->fetch_assoc();
    $stmt->close();
}

// Detalhes
$detalhes_id = isset($_GET['detalhes']) ? intval($_GET['detalhes']) : 0;
$utilizador_detalhes = null;
if ($detalhes_id > 0) {
    $sql = "SELECT * FROM utilizador WHERE id_utilizador=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $detalhes_id);
    $stmt->execute();
    $result_detalhes = $stmt->get_result();
    $utilizador_detalhes = $result_detalhes->fetch_assoc();
    $stmt->close();
}
?>

<!DOCTYPE HTML>
<html>
<head>
    <title>MPP3 - Gestão de Utilizadores</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
    <link rel="stylesheet" href="assets/css/main.css" />
    <style>
        .detalhes-utilizador{background-color:#f8f9fa;border:1px solid #dee2e6;border-radius:4px;padding:15px;margin:10px 0;display:none;}
        .detalhes-utilizador.visivel{display:block;animation:fadeIn .3s;}
        .detalhes-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:15px;}
        .detalhes-item{margin-bottom:10px;}
        .detalhes-label{font-weight:bold;color:#495057;display:block;margin-bottom:3px;}
        .detalhes-valor{color:#212529;padding:5px;background-color:#fff;border:1px solid #ced4da;border-radius:3px;word-break:break-word;}
        @keyframes fadeIn{from{opacity:0;transform:translateY(-10px);}to{opacity:1;transform:translateY(0);}}
        .tabela-simplificada th,.tabela-simplificada td{padding:10px 15px;}
        .tabela-simplificada .acoes{white-space:nowrap;}
        @media (max-width:768px){
            .detalhes-grid{grid-template-columns:1fr;}
            .tabela-simplificada th,.tabela-simplificada td{padding:8px 10px;font-size:.9em;}
            .tabela-simplificada .acoes{white-space:normal;display:flex;flex-direction:column;gap:5px;}
            .tabela-simplificada .acoes .button{width:100%;margin:2px 0;text-align:center;}
        }
        .filtro-bloco{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin:10px 0 20px 0;}
    </style>
</head>
<body class="is-preload">

<div id="wrapper">

    <div id="main">
        <div class="inner">

            <?php include('header.php'); ?>

            <section>
                <header class="main">
                    <h1>Gestão de Utilizadores</h1>
                </header>

                <?php if (!empty($mensagem)): ?>
                    <div class="mensagem <?php echo $tipo_mensagem; ?>">
                        <?php echo $mensagem; ?>
                    </div>
                <?php endif; ?>

                <!-- Formulário -->
                <div class="box">
                    <h3><?php echo $utilizador_editar ? 'Editar Utilizador' : 'Novo Utilizador'; ?></h3>
                    <form method="post" action="">
                        <?php if ($utilizador_editar): ?>
                            <input type="hidden" name="id_utilizador" value="<?php echo $utilizador_editar['id_utilizador']; ?>">
                        <?php endif; ?>

                        <div class="row gtr-uniform">
                            <div class="col-6 col-12-xsmall">
                                <label for="username">Username:</label>
                                <input type="text" name="username" id="username"
                                       value="<?php echo $utilizador_editar ? htmlspecialchars($utilizador_editar['username']) : ''; ?>"
                                       required placeholder="Nome de utilizador">
                            </div>

                            <div class="col-6 col-12-xsmall">
                                <label for="email">Email:</label>
                                <input type="email" name="email" id="email"
                                       value="<?php echo $utilizador_editar ? htmlspecialchars($utilizador_editar['email_utilizador']) : ''; ?>"
                                       required placeholder="Email do utilizador">
                            </div>

                            <div class="col-12">
                                <label for="nome">Nome Completo:</label>
                                <input type="text" name="nome" id="nome"
                                       value="<?php echo $utilizador_editar ? htmlspecialchars($utilizador_editar['nome_utilizador']) : ''; ?>"
                                       required placeholder="Nome completo do utilizador">
                            </div>

                            <?php if (!$utilizador_editar): ?>
                            <div class="col-12">
                                <div class="password-info">
                                    <h4>Informação sobre Password:</h4>
                                    <p><strong>Password inicial:</strong> Todos os novos utilizadores recebem a password padrão <strong>mpp3</strong>.</p>
                                    <p><strong>Obrigatoriedade:</strong> O utilizador será obrigado a alterar a password no primeiro login.</p>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="col-12">
                                <div class="checkbox-group">
                                    <div>
                                        <input type="checkbox" id="admin" name="admin"
                                            <?php echo ($utilizador_editar && $utilizador_editar['e_administrador'] == 1) ? 'checked' : ''; ?>>
                                        <label for="admin">Administrador</label>
                                    </div>
                                    <div>
                                        <input type="checkbox" id="ativo" name="ativo"
                                            <?php echo !$utilizador_editar || ($utilizador_editar && $utilizador_editar['utilizador_ativo'] == 1) ? 'checked' : ''; ?>>
                                        <label for="ativo">Utilizador Ativo</label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 form-acoes">
                                <?php if ($utilizador_editar): ?>
                                    <input type="submit" name="editar" value="Atualizar Utilizador" class="button primary">
                                    <a href="?<?php echo $mostrar_inativos ? 'mostrar_inativos=1' : ''; ?>" class="button primary">Cancelar</a>
                                <?php else: ?>
                                    <input type="submit" name="inserir" value="Inserir Utilizador" class="button primary">
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>

                    <?php if ($utilizador_editar): ?>
                    <div class="limpar-password-section">
                        <h4>Limpar Password</h4>
                        <p>Pode limpar a password deste utilizador para o valor padrão <strong>mpp3</strong>. O utilizador será obrigado a alterar a password no próximo login.</p>
                        <form method="post" action="" onsubmit="return confirm('Tem certeza que deseja limpar a password para mpp3?');">
                            <input type="hidden" name="id_utilizador" value="<?php echo $utilizador_editar['id_utilizador']; ?>">
                            <input type="submit" name="limpar_password" value="Limpar Password" class="button primary">
                        </form>
                    </div>
                    <?php endif; ?>
                </div>

                <hr class="major" />

                <!-- Toggle Mostrar Inativos -->
                <div class="filtro-bloco">
                    <?php if ($mostrar_inativos): ?>
                        <a href="?" class="button primary">Mostrar apenas ativos</a>
                        <span>Atualmente: a mostrar <strong>ativos + inativos</strong>.</span>
                    <?php else: ?>
                        <a href="?mostrar_inativos=1" class="button primary">Mostrar também inativos</a>
                        <span>Atualmente: a mostrar <strong>apenas ativos</strong>.</span>
                    <?php endif; ?>
                </div>

                <h2><?php echo $titulo_lista; ?> (<?php echo $total_utilizadores; ?>)</h2>
                <p><?php echo $texto_lista; ?> Clique em "+ Info" para ver todos os detalhes de um utilizador.</p>

                <div class="table-wrapper">
                    <table class="tabela-simplificada">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Nome</th>
                                <th>Admin</th>
                                <th>Estado</th>
                                <th>Password</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($result && $result->num_rows > 0):
                            $result->data_seek(0);
                            while($row = $result->fetch_assoc()):
                                $tentativas_falhas = $row['tentativas_falhas'] ?? 0;
                                $classe_tentativas = '';
                                if ($tentativas_falhas == 0) $classe_tentativas = 'tentativas-nenhuma';
                                elseif ($tentativas_falhas <= 2) $classe_tentativas = 'tentativas-baixas';
                                elseif ($tentativas_falhas <= 5) $classe_tentativas = 'tentativas-medias';
                                else $classe_tentativas = 'tentativas-altas';
                        ?>
                            <tr id="utilizador-<?php echo $row['id_utilizador']; ?>">
                                <td><strong><?php echo htmlspecialchars($row['username']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['nome_utilizador']); ?></td>
                                <td><?php echo $row['e_administrador'] == 1 ? '<span class="admin-sim">Sim</span>' : '<span class="admin-nao">Não</span>'; ?></td>
                                <td>
                                    <?php if ($row['utilizador_ativo'] == 1): ?>
                                        <span class="status-ativo">Ativo</span>
                                    <?php else: ?>
                                        <span class="status-inativo">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['password_alterada'] == 1): ?>
                                        <span class="password-status password-alterada">Alterada</span>
                                    <?php else: ?>
                                        <span class="password-status password-nao-alterada">A alterar</span>
                                    <?php endif; ?>
                                </td>
                                <td class="acoes">
                                    <?php if ($detalhes_id == $row['id_utilizador']): ?>
                                        <a href="?<?php echo $mostrar_inativos ? 'mostrar_inativos=1' : ''; ?>" class="button primary">← Voltar</a>
                                    <?php else: ?>
                                        <a href="?detalhes=<?php echo $row['id_utilizador']; ?><?php echo qs_toggle($mostrar_inativos); ?>#utilizador-<?php echo $row['id_utilizador']; ?>" class="button primary">+ Info</a>
                                    <?php endif; ?>
                                    <a href="?editar=<?php echo $row['id_utilizador']; ?><?php echo qs_toggle($mostrar_inativos); ?>" class="button primary">Editar</a>
                                    <!-- Apagar removido -->
                                </td>
                            </tr>

                            <?php if ($detalhes_id == $row['id_utilizador']): ?>
                            <tr>
                                <td colspan="6">
                                    <div class="detalhes-utilizador visivel" id="detalhes-<?php echo $row['id_utilizador']; ?>">
                                        <h4>Detalhes completos do Utilizador</h4>
                                        <div class="detalhes-grid">
                                            <div class="detalhes-item">
                                                <span class="detalhes-label">ID:</span>
                                                <div class="detalhes-valor"><?php echo $row['id_utilizador']; ?></div>
                                            </div>
                                            <div class="detalhes-item">
                                                <span class="detalhes-label">Username:</span>
                                                <div class="detalhes-valor"><?php echo htmlspecialchars($row['username']); ?></div>
                                            </div>
                                            <div class="detalhes-item">
                                                <span class="detalhes-label">Email:</span>
                                                <div class="detalhes-valor"><?php echo htmlspecialchars($row['email_utilizador']); ?></div>
                                            </div>
                                            <div class="detalhes-item">
                                                <span class="detalhes-label">Nome Completo:</span>
                                                <div class="detalhes-valor"><?php echo htmlspecialchars($row['nome_utilizador']); ?></div>
                                            </div>
                                            <div class="detalhes-item">
                                                <span class="detalhes-label">Administrador:</span>
                                                <div class="detalhes-valor"><?php echo $row['e_administrador'] == 1 ? 'Sim' : 'Não'; ?></div>
                                            </div>
                                            <div class="detalhes-item">
                                                <span class="detalhes-label">Estado:</span>
                                                <div class="detalhes-valor"><?php echo $row['utilizador_ativo'] == 1 ? 'Ativo' : 'Inativo'; ?></div>
                                            </div>
                                            <div class="detalhes-item">
                                                <span class="detalhes-label">Password:</span>
                                                <div class="detalhes-valor">
                                                    <?php echo $row['password_alterada'] == 1 ? 'Alterada pelo utilizador' : 'Precisa ser alterada'; ?>
                                                </div>
                                            </div>
                                            <div class="detalhes-item">
                                                <span class="detalhes-label">Tentativas Falhadas:</span>
                                                <div class="detalhes-valor <?php echo $classe_tentativas; ?>">
                                                    <?php echo $tentativas_falhas; ?> tentativa(s)
                                                </div>
                                            </div>
                                            <div class="detalhes-item">
                                                <span class="detalhes-label">Data de Criação:</span>
                                                <div class="detalhes-valor"><?php echo date('d/m/Y H:i:s', strtotime($row['data_criacao'])); ?></div>
                                            </div>
                                            <div class="detalhes-item">
                                                <span class="detalhes-label">Último Login:</span>
                                                <div class="detalhes-valor">
                                                    <?php echo $row['ultimo_login'] ? date('d/m/Y H:i:s', strtotime($row['ultimo_login'])) : 'Nunca fez login'; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div style="margin-top: 15px;">
                                            <a href="?<?php echo $mostrar_inativos ? 'mostrar_inativos=1' : ''; ?>" class="btn">Fechar Detalhes</a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>

                        <?php endwhile; else: ?>
                            <tr>
                                <td colspan="6" style="text-align:center;">Não existem utilizadores para mostrar.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </section>

        </div>
    </div>

    <div id="sidebar">
        <div class="inner">
            <?php include('menu.php'); ?>
            <?php include('footer.php'); ?>
        </div>
    </div>

</div>

<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/browser.min.js"></script>
<script src="assets/js/breakpoints.min.js"></script>
<script src="assets/js/util.js"></script>
<script src="assets/js/main.js"></script>

<script>
<?php if ($detalhes_id > 0): ?>
document.addEventListener('DOMContentLoaded', function() {
    var elemento = document.getElementById('utilizador-<?php echo $detalhes_id; ?>');
    if (elemento) elemento.scrollIntoView({ behavior: 'smooth' });
});
<?php endif; ?>
</script>

</body>
</html>

<?php
$conn->close();
?>
