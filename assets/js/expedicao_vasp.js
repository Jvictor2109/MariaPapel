function exportarCSV(){
    
    let tabela = document.getElementById('tabela');
    let dados = [];

    // Iterar sobre cada linha da tabela e salvar os dados na variável
    for(let i = 1; i < tabela.rows.length; i++){
        
        let manter = tabela.rows[i].cells[8].querySelector('input').checked;

        // Se nao for pra manter o artigo, seguir sem fazer nada
        if(manter == false){
            continue;
        }

        let titulo = tabela.rows[i].cells[1].innerText;
        let preco = tabela.rows[i].cells[2].innerText;
        let preco_venda = tabela.rows[i].cells[3].innerText;
        let ean = tabela.rows[i].cells[4].innerText;
        let stock = tabela.rows[i].cells[5].innerText;
        let fornecedor = 81;
        let iva = tabela.rows[i].cells[7].querySelector('select').value;
        
        // Criar o objeto
        item = {
            referencia : ean,
            ean : ean,
            titulo : titulo,
            stock : stock,
            preco_venda : preco_venda,
            preco : preco,
            fornecedor : fornecedor,
            iva : iva
        }
        dados.push(item);
    }
    
    // Envia request pro exportador em  csv, enviando os dados
    fetch('exportarCSV.php', {
        method: "POST",
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify(dados)
    }).then(response => response.json())
      .then(data => {
            if(data['sucesso']==true){
                window.location.href = 'exportarCSV.php';
            }
        });
}

function alterarIva(select, preco){
    let iva = parseFloat(select.value);
    let preco_sIva = document.getElementById('pvp_sIva_'+preco);
    let preco_bruto = parseFloat(preco_sIva.dataset.pvpbruto);
    
    preco_sIva.innerText = (preco_bruto/(1+iva)).toFixed(2)+"€";    
}