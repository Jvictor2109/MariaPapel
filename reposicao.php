<?php
// Verificação de sessão em todas as páginas protegidas
session_start();

require_once('db_connect.php');

// Verifica se está recebendo um formulário 
if ($_SERVER['REQUEST_METHOD'] == "POST") {
	$request = json_decode(file_get_contents('php://input'), true);

	// Verifica o tipo de pedido e faz as ações necessárias
	switch ($request['acao']) {
		case "adicionar":
			if (addItem($conn, $request)) {
				echo json_encode(['resultado' => 'sucesso', 'msg' => 'Item adicionado com sucesso!']);
			} else {
				echo json_encode(['resultado' => 'erro', 'msg' => 'Não foi possível adicionar à base de dados']);
			}
			exit();

		case "listar":
			echo json_encode(mostrarDados($conn));
			exit();

		case "atualizar":
			if (attItem($conn, $request)) {
				echo json_encode(['resultado' => 'sucesso', 'msg' => 'Item atualizado com sucesso!']);
				exit();
			} else {
				echo json_encode(['resultado' => 'erro', 'msg' => 'Erro ao atualizar pedido']);
				exit();
			}
	}

	// TODO: lidar com filtros da tabela e atualizações de pedido
}

// Funções
function mostrarDados($conn)
{
	$sql = "SELECT * FROM reposicao ORDER BY item_id DESC";
	$resultado = mysqli_query($conn, $sql);
	$dados = mysqli_fetch_all($resultado, MYSQLI_ASSOC);

	return $dados;
}


function addItem($conn, $request)
{
	// Verifica campos obrigatórios
	$artigo = $request['artigo'];
	if (empty($artigo)) {
		echo json_encode(['resultado' => 'erro', 'msg' => 'Introduza o nome do artigo']);
		exit();
	}

	// Pega o resto dos dados
	$referencia = $request['referencia'];
	$tipo = $request['tipo'];
	$cliente = $request['cliente'];
	$telemovel = $request['telemovel'];
	$tipo = $request['tipo'];
	$urgencia = $request['urgencia'];

	// Informações de data e utilizador
	$user_id = $_SESSION['user_id'];
	$nome_criador = "";
	$data_criado = date("Y-m-d");

	$sql = "SELECT nome_utilizador FROM utilizador WHERE id_utilizador = ?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("i", $user_id);
	$stmt->execute();
	$stmt->bind_result($nome_criador);
	$stmt->fetch();
	$stmt->close();


	// Adiciona na base de dados
	$sql = "INSERT INTO reposicao (artigo, referencia, tipo, urgencia, nome_cliente, telefone_cliente, data_criacao, criado_por)
			VALUES (?,?,?,?,?,?,?,?)";

	$stmt = $conn->prepare($sql);
	$stmt->bind_param("ssssssss", $artigo, $referencia, $tipo, $urgencia, $cliente, $telemovel, $data_criado, $nome_criador);

	if ($stmt->execute()) {
		return True;
	} else {
		return False;
	}
}

function attItem($conn, $request)
{
	$estado = $request['estado'];
	$item_id = intval($request['id']);
	$data = date("y-m-d");
	$user_id = $_SESSION['user_id'];
	$utilizador = "";

	$sql = "SELECT nome_utilizador FROM utilizador WHERE id_utilizador = ?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("i", $user_id);
	$stmt->execute();
	$stmt->bind_result($utilizador);
	$stmt->fetch();
	$stmt->close();



	if ($estado == "pedido") {

		$pedido = 1;
		$sql = "UPDATE reposicao
				SET pedido = ?, data_pedido = ?, pedido_por = ?
				WHERE item_id = ?";

		$stmt = $conn->prepare($sql);
		$stmt->bind_param("issi", $pedido, $data, $utilizador, $item_id);
	} else if ($estado == "concluido") {
		$concluido = 1;
		$sql = "UPDATE reposicao
				SET concluido = ?, data_conclusao = ?, concluido_por = ?
				WHERE item_id = ?";

		$stmt = $conn->prepare($sql);
		$stmt->bind_param("issi", $concluido, $data, $utilizador, $item_id);
	}

	if ($stmt->execute()) {
		return true;
	} else {
		return false;
	}
}


function filtrarDados()
{
	// TODO: Função de filtrar
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
	<title>MPP - Reposição</title>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
	<link rel="stylesheet" href="assets/css/main.css" />
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
				<section id="search" class="alt">
					<?php
					// Verificar se já está autenticado
					if (isset($_SESSION['user_id'])) { ?>
						<!-- Formulário para adicionar item -->
						<div id="modal-reposicao" class="modal-overlay" style="display: none;">
							<div class="box modal-content">
								<span id="close-modal" class="modal-close">&times;</span>
								<h3>Adicionar Pedido</h3>

								<div class="row gtr-uniform">
									<div class="col-6 col-12xsmall">
										<label for="artigo">Artigo:</label>
										<input type="text" id="artigo" required>

										<label for="referencia">Referência:</label>
										<input type="text" id="referencia">

										<label for="tipo">Tipo:</label>
										<select name="tipo" id="tipo">
											<option value="papelaria">Papelaria</option>
											<option value="tinteiros">Tinteiros</option>
											<option value="livros">Livros</option>
										</select>


									</div>

									<div class="col-6 col-12xsmall">
										<label for="cliente">Cliente:</label>
										<input type="text" id="cliente">

										<label for="telemovel">Telemóvel</label>
										<input type="text" id="telemovel">

										<label for="urgencia">Urgência:</label>
										<select name="urgencia" id="urgencia">
											<option value="muito urgente">Muito Urgente</option>
											<option value="urgente">Urgente</option>
											<option value="nao urgente">Não Urgente</option>
										</select>
									</div>
								</div>

								<div class="row gtr-uniform" style="margin-top: 1.5em;">
									<div class="col-12">
										<ul class="actions">
											<li><input type="submit" value="Adicionar" class="primary" onclick="addPedido()" /></li>
										</ul>
									</div>
								</div>

							</div>
						</div>

						<!-- Modal de Informações -->
						<div id="modal-info" class="modal-overlay" style="display: none;">
							<div class="box modal-content" style="max-width: 500px;">
								<span id="close-modal-info" class="modal-close">&times;</span>
								<h3>Informações do Artigo</h3>
								<div id="info-content">
									<!-- Dados do item são adicionados no javascript -->
								</div>
							</div>
						</div>

						<div style="display: flex; align-items: center; gap: 20px; margin-bottom: 2em;">
							<a href="#" id="btn-add-pedido" class="button primary" style="margin-bottom: 0;">Adicionar pedido</a>
							<h5 id="msg" style="margin: 0; line-height: 1;"></h5>
						</div>


						<h2 style="margin-top: 1.5em; margin-bottom: 1.5em;">Itens em falta</h2>

						<!-- Barra de seleção de filtros
						<div class="divFiltros">
							<h3>Filtrar por: </h3>
							
							<div class="filtros">
								<select name="filtroUrgencia">
									<option value=""> </option>
									<option value="muito urgente">Muito urgente</option>
									<option value="urgente">Urgente</option>
									<option value="nao urgente">Não Urgente</option>
								</select>
							</div>
						</div> -->

						<!-- Tabela que mostra os itens -->
						<div class="table-wrapper" style="max-height: 400px; overflow-y: auto;">
							<table>
								<thead>
									<tr>
										<th>Artigo</th>
										<th>Referência</th>
										<th>Tipo</th>
										<th>Urgência</th>
										<th style="min-width: 14em; text-align: center;">Ações</th>
									</tr>
								</thead>
								<tbody>

								</tbody>
							</table>
						</div>
			</div>

		<?php } else {
						echo <<<HTML
													<section id="banner">
														<div class="col-6 col-12-small">
															<div class="box">
																<h2>Informações do Sistema</h2>
																<p>Este é o sistema de gestão da Maria Papel Papelaria. Para aceder às funcionalidades administrativas, é necessário autenticar-se com as suas credenciais.</p>
																<p>Se ainda não possui uma conta, contacte o administrador do sistema para obter acesso.</p>
															</div>
														</div>
													</section>
													HTML;
					}

		?>


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
	<script src="assets/js/reposicao.js"></script>

</body>

</html>