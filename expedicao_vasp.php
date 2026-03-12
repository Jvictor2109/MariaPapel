<?php
// Verificação de sessão em todas as páginas protegidas
session_start();

// Verifica em pra qual a request GET veio: primeiro acesso ou downlad
if(!isset($_SESSION['upload_feito'])){
	unset($_SESSION['dados']);
} 
else{
	unset($_SESSION['upload_feito']);
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
		<title>MPP - Expedição VASP</title>
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
									<div class="col-6 col-12-small">
										<div class="box">
											<h2>Expedições VASP</h2>

											<!-- Mensagens de erro -->
											 <?php 
											 if(isset($_SESSION['erro'])){?>
												<h3><?= $_SESSION['erro'] ?></h3>
											 <?php }
											 unset($_SESSION['erro']);
											 ?>

											<!-- Formulário para introduzir pdf -->
												<form method="post" action="extrairTabela.php" enctype="multipart/form-data">
													<div class="row gtr-uniform">
														<div class="col-6 col-12-xsmall">
															<input type="file" name="pdf_file" id="pdf_file"/>
														</div>
														<div class="col-12">
															<input type="submit" value="Enviar PDF" class="primary" />
														</div>
													</div>
												</form>

											<!-- Tabela mostrando os artigos extraídos -->
											<h4>Artigos encontrados</h4>
											<div class="table-wrapper">
												<table class="alt" id="tabela">
													<thead>
														<tr>
															<th>Artigo</th>
															<th>Descrição</th>
															<th>Preço Custo</th>
															<th>PVP s/IVA</th>
															<th>EAN</th>
															<th>Qtd</th>
															<th>Fornecedor</th>
															<th>Iva</th>
															<th>Manter</th>
														</tr>
													</thead>
													<tbody>
														<?php 
														if(isset($_SESSION['dados'])){
															$dados = $_SESSION['dados'];
															$i = 0;
															foreach($dados as $id => $artigo){?>
															<tr>
																<td data-id="<?= $id ?>"><?= $artigo['artigo'] ?></td>
																<td><?= $artigo['descricao'] ?></td>
																<td><?= $artigo['preco'] ?>€</td>
																<td data-pvpbruto="<?= $artigo['pvp'] ?>" id="pvp_sIva_<?= $i ?>"><?= $artigo['pvp_sIva'] ?>€</td>
																<td><?= $artigo['ean'] ?></td>
																<td><?= $artigo['stock'] ?></td>
																<td>81</td>
																<td>
																	<select name="iva_select" id="iva_select" onchange="alterarIva(this, <?= $i ?>)">
																		<option value="0.06">6%</option>
																		<option value="0.23">23%</option>
																	</select>
																</td>
																<td>
																	<input type="checkbox" name="artigo_check_<?= $i ?>" id="artigo_check_<?= $i ?>" checked>
																	<label for="artigo_check_<?= $i ?>"></label>
																</td>
															</tr>
															<?php 
															$i++;
															}
														}?>
													</tbody>
												</table>
											</div>

											<input type="button" value="Exportar Excel" class="secondary" onclick="exportarExcel()"/>

										</div>
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
			<script src="assets/js/expedicao_vasp.js"></script>
	</body>
</html>