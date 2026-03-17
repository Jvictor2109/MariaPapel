<?php
require_once 'vendor/autoload.php';
session_start();
// Verifica se o arquivo recebido é um pdf
if($_FILES['pdf_file']['type'] != "application/pdf"){
    $_SESSION['erro'] = "O arquivo enviado não é um PDF";
    header("Location: expedicao_vasp.php");
    exit();
}


// Extrai o conteúdo do PDF recebido
$parser = new \Smalot\PdfParser\Parser();
$pdf = $parser->parseFile($_FILES["pdf_file"]["tmp_name"]);
$pageData = $pdf->getPages();
$pageData = $pageData[0]->getDataTm();


// Identificar os limites inferiores e superiores da tabela
// Redirecionar com erro caso não tenha tabela
$limitesTabela = limitesTabela($pageData);
if(empty($limitesTabela)){
    $_SESSION['erro'] = "Tabela não encontrada";
    header("Location: expedicao_vasp.php");
    exit();
}

// Extrair a tabela do resto do PDF
$tabela = extrairTabela($pageData, $limitesTabela);

// Extrair os dados da tabela
$dados = extrairDados($tabela);

// Retorna a tabela pela sessão
$_SESSION['dados'] = $dados;
$_SESSION['upload_feito'] = true;
header("Location: expedicao_vasp.php");
exit();


function extrairDados($tabela){
    $dados = [];
    $i = 1; // Identificador unico de cada item
    // Percorre cada linha da tabela
    foreach($tabela as $linha){
        // Dados que não mudam
        $iva = 0.06;
        $tem_stock = 1;
        $categoria = "VASP";
        $tipo_artigo = "Produto";
        $inventario_existencia = "Mercadorias";
        $un_medida = "Unidade";
        $fornecedor = 81;

        // Inicializacao de variaveis
        $ean = "";
        $artigo = "";
        $preco = 0;
        $descricao = "";
        $pvp = 0;
        $quantidade = 0;


        // verifica cada elemento da linha e extrai os dados necessários
        foreach($linha as $elemento){
            $x = floatval($elemento['x']);
            switch($x){
                case $x > 30 && $x < 40:
                    $artigo = $elemento['conteudo'];
                    break;
                
                case $x == 81.338:
                    $descricao = $elemento['conteudo'];
                    break;

                case $x > 260 && $x < 266:
                    $preco_com_iva = floatval(str_replace(",", ".", $elemento['conteudo']));
                    $preco = number_format($preco_com_iva/(1+$iva),2);
                    break;
                
                case $x > 305 && $x < 311:
                    $pvp = floatval(str_replace(",", ".", $elemento['conteudo']));
                    $pvp_sIva = number_format($pvp/(1+$iva), 2);
                    break;

                case $x == 424.347:
                    $ean = $elemento['conteudo'];
                    break;
                
                case $x > 540 && $x < 555:
                    $quantidade = (int) $elemento['conteudo'];
                    break;
                
                default:
                    break; 
            }
        }
        
        $dados["{$i}"] = ['artigo'=>$artigo,
                    'iva'=> $iva,
                    'descricao'=>$descricao, 
                    'preco'=>$preco,
                    'preco_com_iva' => $preco_com_iva, 
                    'pvp'=>$pvp,
                    'pvp_sIva'=>$pvp_sIva, 
                    'ean'=>$ean, 
                    'stock'=>$quantidade,
                    'tem_stock'=>$tem_stock,
                    'categoria'=>$categoria,
                    'tipo_artigo'=>$tipo_artigo,
                    'inventario_existencia'=>$inventario_existencia,
                    'un_medida'=>$un_medida,
                    'fornecedor'=>$fornecedor
                ];
        $i++;
    }

    return $dados;
}


function extrairTabela($pageData, $limitesTabela){
    $tabela = [];

    $ultimo_y = 0;
    foreach($pageData as $elemento){
        $y = (float) $elemento[0][5];

        if($y <= $limitesTabela['comeco'] && $y >= $limitesTabela['fim']){
            
            $x = $elemento[0][4];
            $conteudo = $elemento[1];


            // Compara com o Y da linha anterior, pra verificar se é um outro artigo, ou quebra de linha do mesmo
            if($ultimo_y - $y < 9 && $ultimo_y != 0 && $ultimo_y - $y > 0){
                foreach($tabela[$ultimo_y] as &$linha){
                    if($linha['x'] == $x){
                        $linha['conteudo'] .= $conteudo;
                    }
                }
                continue;
            }
        

            if(!isset($tabela[$y])){
                $tabela[$y] = [];
            }
                    
            $tabela[$y][] = ['x'=>$x, 'conteudo'=>$conteudo];
            }
            $ultimo_y = $y;
    }

    // Elimina título e cabeçalho da tabela
    array_shift($tabela);
    array_shift($tabela);
    array_shift($tabela);

    return $tabela;
}



function limitesTabela($pageData){
    $limitesTabela = [];
    foreach($pageData as $elemento){
        if($elemento[1] == "Totais por Artigo:"){
            $limitesTabela['comeco'] = (float) $elemento[0][5];
        }
    
        if($elemento[1] == "Detalhe por Guia:"){
            $limitesTabela['fim'] = (float) $elemento[0][5];
        }
    }

    return $limitesTabela;
}
?>