<?php
session_start();

// Se a request vier pelo fetch
if($_SERVER['REQUEST_METHOD'] == "POST"){
    $dados = json_decode(file_get_contents('php://input'), true);
    $_SESSION['csv'] = stream_get_contents(exportarCSV($dados));
    echo json_encode(['sucesso' => true]);

} // Se vier pelo redirect, manda o arquivo pra download
else{
    header("Content-type: text/csv");
    header("Content-Disposition: attachment; filename=artigos.csv;");
    echo "\xEF\xBB\xBF";
    echo $_SESSION['csv'];
    unset($_SESSION['csv']);
    exit();
}

function exportarCSV($dados){
    $f = fopen('php://memory', 'w');
    $colunas = ["Referência", "Ean", "Título", "Stock", "Preço de venda", "Preço de custo", "Fornecedor", "Iva"];
    fputcsv($f, $colunas, ";");

    foreach($dados as $linha){
        fputcsv($f, $linha, ";");
    }
    fseek($f, 0);

    return $f;
}
    