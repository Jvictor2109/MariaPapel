<?php
session_start();

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if($_SERVER['REQUEST_METHOD'] == "POST"){
    $alteracoes = json_decode(file_get_contents('php://input'), true);
    $dados = $_SESSION['dados'];
    $dadosProcessados = [];

    foreach($alteracoes as $id => $info){
        if($info['manter'] == false){
            continue;
        }

        $pvp = floatval($dados[$id]['pvp']);
        $preco_com_iva = floatval($dados[$id]['preco_com_iva']);
        $iva = floatval($info['iva']);
        $pvp_sIva = number_format($pvp/(1+$iva), 2);
        $preco_sIva = number_format($preco_com_iva/(1+$iva), 2);

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
            "preco_custo"=>$preco_sIva
        ];
    }

    $_SESSION['xlsx'] = exportarXLSX($dadosProcessados);
    echo json_encode(['sucesso' => true]);

} else {
    $filename = "artigos_vasp_" . date("d-m-Y") . ".xlsx";
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header("Content-Disposition: attachment; filename={$filename}");
    header('Cache-Control: max-age=0');

    echo $_SESSION['xlsx'];
    unset($_SESSION['xlsx']);
    exit();
}

function exportarXLSX($dados){
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $colunas = ["Título", "Iva", "Referência", "Ean",
                "Preço de venda", "Tem stock", "Stock",
                "Categoria", "Tipo de artigo", "Inventário existências",
                "Unidade medida", "Fornecedor", "Preço custo"];

    $sheet->fromArray($colunas, null, 'A1');

    $linha = 2;
    foreach($dados as $row){
        $sheet->fromArray(array_values($row), null, 'A' . $linha);
        $linha++;
    }

    ob_start();
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    return ob_get_clean();
}