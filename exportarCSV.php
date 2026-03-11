<?php
session_start();

// Se a request vier pelo fetch
if($_SERVER['REQUEST_METHOD'] == "POST"){
    // Recebe os dados pela sessão e as alterações pelo fetch
    $alteracoes = json_decode(file_get_contents('php://input'), true);
    $dados = $_SESSION['dados'];
    $dadosProcessados = [];
    // Itera sobre cada item das alterações e modifica os dados
    foreach($alteracoes as $id => $info){

        if($info['manter'] == false){
            continue;
        }

        $pvp = floatval($dados[$id]['pvp']);
        $iva = floatval($info['iva']);
        $pvp_sIva = number_format($pvp/(1+$iva), 2);

        $dadosProcessados[] = [
            "titulo"=> $dados[$id]['descricao'],
            "iva"=> $info['iva'] * 100,
            "referencia"=>$dados[$id]['ean'],
            "ean"=>$dados[$id]['ean'],
            "pvp_sIva"=> $pvp_sIva,
            "tem_stock"=>$dados[$id]['tem_stock'],
            "stock"=>$dados[$id]['stock'],
            "categoria"=>$dados[$id]['categoria'],
            "tipo_artigo"=>$dados[$id]['tipo_artigo'],
            "inventario_existencia"=>$dados[$id]['inventario_existencia'],
            "un_medida"=>$dados[$id]['un_medida'],
            "fornecedor"=>$dados[$id]['fornecedor'],
            "preco_custo"=>$dados[$id]['preco']
        ];
    }
    
    
    $_SESSION['csv'] = stream_get_contents(exportarCSV($dadosProcessados));
    echo json_encode(['sucesso' => true]);
} // Se vier pelo redirect, manda o arquivo pra download
else{
    $filename = "artigos_vasp_". date("d/m/Y") . ".csv";
    header("Content-type: text/csv");
    header("Content-Disposition: attachment; filename={$filename};");
    echo "\xEF\xBB\xBF";
    echo $_SESSION['csv'];
    unset($_SESSION['csv']);
    exit();
}

function exportarCSV($dados){
    $f = fopen('php://memory', 'w');
    $colunas = ["Título", "Iva", "Referência", "Ean",
                "Preço de venda", "Tem stock", "Stock", 
                "Categoria", "Tipo de artigo", "Inventário existências",
                "Unidade medida", "Fornecedor", "Preço custo"];
    fputcsv($f, $colunas, ";");

    foreach($dados as $linha){
        fputcsv($f, $linha, ";");
    }
    fseek($f, 0);

    return $f;
}
    