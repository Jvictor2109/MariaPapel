function exportarExcel(){
    let tabela = document.getElementById('tabela');
    let alteracoes = {}

    // Itera sobre cada linha e salva as informações de iva e de manter o item
    for(let i = 1; i < tabela.rows.length; i++){
        let id = parseInt(tabela.rows[i].cells[0].dataset.id);
        let manter = tabela.rows[i].cells[8].querySelector('input').checked;
        let iva = tabela.rows[i].cells[7].querySelector('select').value;
        iva = parseFloat(iva);
        
        // Adicionar as alterações no objeto
        alteracoes[id] = {
            "manter":manter,
            "iva":iva
        }
        
    }
    
    // Envia request pro exportador (agora excel) enviando os dados
    fetch('exportarExcel.php', {
        method: "POST",
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify(alteracoes)
    }).then(response => response.json())
      .then(data => {
            if(data['sucesso']==true){
                window.location.href = 'exportarExcel.php';
            }
        });
}

function alterarIva(select, preco){
    let iva = parseFloat(select.value);
    let preco_sIva = document.getElementById('pvp_sIva_'+preco);
    let preco_bruto = parseFloat(preco_sIva.dataset.pvpbruto);
    
    preco_sIva.innerText = (preco_bruto/(1+iva)).toFixed(2)+"€";    
}