<?php
// Verificação de sessão em todas as páginas protegidas
session_start();

//Se não tiver sessão iniciada
if (!isset($_SESSION['user_id'])){
    // Redirecionar para a página login
    header('Location: login.php');
    exit();
}

//Se não for administrador
if (isset($_SESSION['e_administrador']) && $_SESSION['e_administrador'] === 0) {
    // Redirecionar para a página principal
    header('Location: index.php');
    exit();
}

// Incluir a conexão à base de dados
require_once 'db_connect.php';

// Agora $conn já está disponível a partir do db_connect.php

// Variáveis para mensagens
$mensagem = "";
$tipo_mensagem = ""; // success, error, warning

// Processar operações CRUD
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Inserir nova localização
    if (isset($_POST['inserir'])) {
        $nome_localizacao = $_POST['nome_localizacao'];
        $ativo = isset($_POST['ativo']) ? 1 : 0;
        
        $sql = "INSERT INTO localizacao (nome_localizacao, ativo) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $nome_localizacao, $ativo);
        
        if ($stmt->execute()) {
            $mensagem = "Localização inserida com sucesso!";
            $tipo_mensagem = "success";
        } else {
            $mensagem = "Erro ao inserir localização: " . $stmt->error;
            $tipo_mensagem = "error";
        }
        $stmt->close();
    }
    
    // Editar localização
    if (isset($_POST['editar'])) {
        $id = $_POST['id_localizacao'];
        $nome_localizacao = $_POST['nome_localizacao'];
        $ativo = isset($_POST['ativo']) ? 1 : 0;
        
        $sql = "UPDATE localizacao SET nome_localizacao=?, ativo=? WHERE id_localizacao=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $nome_localizacao, $ativo, $id);
        
        if ($stmt->execute()) {
            $mensagem = "Localização atualizada com sucesso!";
            $tipo_mensagem = "success";
        } else {
            $mensagem = "Erro ao atualizar localização: " . $stmt->error;
            $tipo_mensagem = "error";
        }
        $stmt->close();
    }
}

// Obter todas as localizações
$sql = "SELECT * FROM localizacao ORDER BY id_localizacao DESC";
$result = $conn->query($sql);
$total_localizacoes = $result->num_rows;

// Obter localização para edição (se solicitado)
$localizacao_editar = null;
if (isset($_GET['editar'])) {
    $id_editar = $_GET['editar'];
    $sql = "SELECT * FROM localizacao WHERE id_localizacao=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_editar);
    $stmt->execute();
    $result_editar = $stmt->get_result();
    $localizacao_editar = $result_editar->fetch_assoc();
    $stmt->close();
}
?>

<!DOCTYPE HTML>
<!--
	Editorial by HTML5 UP
	html5up.net | @ajlkn
	Free for personal and commercial use under the CCA 3.0 license (html5up.net/license)
-->
<html>
<head>
    <title>MPP3 - Gestão de Localizações</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
    <link rel="stylesheet" href="assets/css/main.css" />
    <style>
        /* Estilos para a tabela */
        .tabela-simplificada th,
        .tabela-simplificada td {
            padding: 10px 15px;
        }
        
        .tabela-simplificada .acoes {
            white-space: nowrap;
        }
        
        .tabela-simplificada .acoes .btn {
            padding: 5px 10px;
            font-size: 0.85em;
            margin: 0 2px;
        }
        
        /* Estilos específicos para localizações */
        .status-ativo {
            color: green;
            font-weight: bold;
        }
        
        .status-inativo {
            color: red;
            font-weight: bold;
        }
        
        /* Responsividade */
        @media (max-width: 768px) {
            .tabela-simplificada th,
            .tabela-simplificada td {
                padding: 8px 10px;
                font-size: 0.9em;
            }
            
            .tabela-simplificada .acoes {
                white-space: normal;
                display: flex;
                flex-direction: column;
                gap: 5px;
            }
            
            .tabela-simplificada .acoes .btn {
                width: 100%;
                margin: 2px 0;
                text-align: center;
            }
        }
    </style>
</head>
<body class="is-preload">

    <!-- Wrapper -->
    <div id="wrapper">

        <!-- Main -->
        <div id="main">
            <div class="inner">

                <!-- Header -->
                <?php include('header.php'); ?>
                
                <!-- Banner -->
                <section>
                    <header class="main">
                        <h2>Gestão de Localizações</h2>
                    </header>

                    <!-- Mensagem de status -->
                    <?php if (!empty($mensagem)): ?>
                        <div class="mensagem <?php echo $tipo_mensagem; ?>">
                            <?php echo $mensagem; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Formulário para inserir/editar -->
                    <div class="box">
                        <h3><?php echo $localizacao_editar ? 'Editar Localização' : 'Nova Localização'; ?></h3>
                        <form method="post" action="">
                            <?php if ($localizacao_editar): ?>
                                <input type="hidden" name="id_localizacao" value="<?php echo $localizacao_editar['id_localizacao']; ?>">
                            <?php endif; ?>
                            
                            <div class="row gtr-uniform">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="nome_localizacao">Nome da Localização:</label>
                                        <input type="text" name="nome_localizacao" id="nome_localizacao" 
                                               value="<?php echo $localizacao_editar ? htmlspecialchars($localizacao_editar['nome_localizacao']) : ''; ?>" 
                                               required placeholder="Nome da localização">
                                    </div>
                                </div>
                                
                                <div class="col-12">
                                    <div class="checkbox-group">
                                        <div>
                                            <input type="checkbox" id="ativo" name="ativo" 
                                                   <?php echo !$localizacao_editar || ($localizacao_editar && $localizacao_editar['ativo'] == 1) ? 'checked' : ''; ?>>
                                            <label for="ativo">Localização ativa</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-12 form-acoes">
                                    <?php if ($localizacao_editar): ?>
                                        <input type="submit" name="editar" value="Atualizar Localização" class="button primary">
                                        <a href="?" class="button primary">Cancelar</a>
                                    <?php else: ?>
                                        <input type="submit" name="inserir" value="Inserir Localização" class="button primary">
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
                    </div>

                    <hr class="major" />

                    <!-- Lista de Localizações -->
                    <h2>Lista de Localizações (<?php echo $total_localizacoes; ?>)</h2>
                    
                    <div class="table-wrapper">
                        <table class="tabela-simplificada">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nome</th>
                                    <th>Estado</th>
                                    <th>Data Criação</th>
                                    <th>Última Atualização</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result->num_rows > 0): 
                                    while($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo $row['id_localizacao']; ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['nome_localizacao']); ?></td>
                                        <td>
                                            <?php if ($row['ativo'] == 1): ?>
                                                <span class="status-ativo">Ativa</span>
                                            <?php else: ?>
                                                <span class="status-inativo">Inativa</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo date('d/m/Y H:i', strtotime($row['data_criacao'])); ?>
                                        </td>
                                        <td>
                                            <?php echo $row['data_atualizacao'] ? date('d/m/Y H:i', strtotime($row['data_atualizacao'])) : '-'; ?>
                                        </td>
                                        <td class="acoes">
                                            <a href="?editar=<?php echo $row['id_localizacao']; ?>" class="button primary">Editar</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center;">Não existem localizações registadas.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                </section>

            </div>
        </div>

        <!-- Sidebar -->
        <div id="sidebar">
            <div class="inner">
                <!-- Menu -->
                <?php include('menu.php'); ?>

                <!-- Footer -->
                <?php include('footer.php'); ?>

            </div>
        </div>

    </div>

    <!-- Scripts -->
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/browser.min.js"></script>
    <script src="assets/js/breakpoints.min.js"></script>
    <script src="assets/js/util.js"></script>
    <script src="assets/js/main.js"></script>

</body>
</html>

<?php
// Fechar conexão
$conn->close();
?>