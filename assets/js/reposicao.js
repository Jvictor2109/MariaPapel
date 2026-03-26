// Variável que vai guardar todos os itens da tabela
dados = {};

document.addEventListener('DOMContentLoaded', function () {

    // Carrega a tabela assim que a pagina abre
    mostrarTabela();


    const btnAddPedido = document.getElementById('btn-add-pedido');
    const modalReposicao = document.getElementById('modal-reposicao');
    const closeModal = document.getElementById('close-modal');
    
    // Modal de Informações
    const modalInfo = document.getElementById('modal-info');
    const closeModalInfo = document.getElementById('close-modal-info');

    // Abre o modal ao clicar no botão
    if (btnAddPedido) {
        btnAddPedido.addEventListener('click', function (e) {
            e.preventDefault();
            modalReposicao.style.display = 'flex';
        });
    }

    // Fecha o modal ao clicar no X
    if (closeModal) {
        closeModal.addEventListener('click', function () {
            modalReposicao.style.display = 'none';
        });
    }
    
    // Fecha o modal Info ao clicar no X
    if (closeModalInfo) {
        closeModalInfo.addEventListener('click', function () {
            modalInfo.style.display = 'none';
        });
    }

    // Fecha o modal ao clicar fora da caixa do modal
    window.addEventListener('click', function (e) {
        if (e.target === modalReposicao) {
            modalReposicao.style.display = 'none';
        }
        if (e.target === modalInfo) {
            modalInfo.style.display = 'none';
        }
    });
});

// Adicionar pedido
function addPedido() {
    let modalReposicao = document.getElementById('modal-reposicao');
    // Pega os dados do formulário
    let artigo = document.getElementById('artigo').value;
    let referencia = document.getElementById('referencia').value;
    let tipo = document.getElementById('tipo').value;
    let cliente = document.getElementById('cliente').value;
    let telemovel = document.getElementById('telemovel').value;
    let urgencia = document.getElementById('urgencia').value;

    // Valida o artigo
    if (artigo === '') {
        modalReposicao.style.display = "none";
        msg.style.color = "red";
        msg.innerText = "Introduza um artigo";
        return;
    }

    // Constrói a variável dos dados
    let dados = {
        "acao": "adicionar",
        "artigo": artigo,
        "referencia": referencia,
        "tipo": tipo,
        "cliente": cliente,
        "telemovel": telemovel,
        "urgencia": urgencia
    }

    // Envia os dados pro servidor
    fetch('reposicao.php', {
        method: "post",
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(dados)
    }).then(response => response.json())
        .then(data => {

            if (data['resultado'] == "sucesso") {
                //tratar sucesso -> Recarregar tabela
                mostrarTabela();
                modalReposicao.style.display = "none";
                msg.style.color = "Green";
                msg.innerText = data['msg'];

            }
            else {
                //Mensagem de erro vinda do servidor
                modalReposicao.style.display = "none";
                msg.style.color = "red";
                msg.innerText = data['msg'];
            }
        });
}


function mostrarTabela(){
    // Busca os dados no servidor
    fetch('reposicao.php',{
        method:"post",
        headers:{"Content-Type":"application/json"},
        body: JSON.stringify({
            "acao":"listar"
        })
    }).then(response => response.json())
    .then(data => {
        dados = data;

        // Gera cada linha da tabela dinamicamente
        let tbody = document.querySelector('tbody');

        // Limpa o tbody antes de reescrever
        tbody.innerHTML = '';

        dados.forEach(element => {
            let linha = document.createElement('tr');

            // Cria cada célula da linha
            let artigo = document.createElement('td');
            artigo.innerText = element.artigo;           
            linha.appendChild(artigo);
            
            let referencia = document.createElement('td');
            referencia.innerText = element.referencia;           
            linha.appendChild(referencia);
            
            let tipo = document.createElement('td');
            tipo.innerText = element.tipo;           
            linha.appendChild(tipo);
            
            let urgencia = document.createElement('td');
            urgencia.innerText = element.urgencia;
            
            // Adiciona estilo com base no tipo de urgencia
            switch (element.urgencia) {
                case "muito urgente":
                    urgencia.classList.add('muito-urgente');
                    break;
                    
                case "urgente":
                    urgencia.classList.add('urgente');
                    break;
                    
                case "nao urgente":
                    urgencia.classList.add('nao-urgente');
                    break;
            }
            linha.appendChild(urgencia);

            // Botões de ação: Mostra o botão correspondente ao estado atual do pedido
            // Se o item já tiver estado de concluído, mostra somente um texto
            let acoes = document.createElement('td');
            acoes.style.textAlign = 'center';

            if(element.concluido == 1){
                // Mensagem de pedido concluído
                let badge = document.createElement('span');
                badge.innerHTML = "&#10004; Pedido Concluído";
                badge.className = "badge-concluido";
                acoes.appendChild(badge);
            }
            else if (element.pedido == 1){
                // Botão "Marcar como concluído"
                let botao = document.createElement('button');
                botao.innerText = "Marcar como Concluído";
                botao.classList.add('button', 'small', 'btn-concluido');
                botao.addEventListener('click', () => attConcluido(element.item_id));
                acoes.appendChild(botao);   
            }
            else{
                // Botão "Marcar como pedido"
                let botao = document.createElement('button');
                botao.innerText = "Marcar como pedido";
                botao.classList.add('button', 'small', 'btn-pedido');
                botao.addEventListener('click', () => attPedido(element.item_id));
                acoes.appendChild(botao);   
            }
            
            // Botão de mais informações
            let btnInfo = document.createElement('a');
            btnInfo.href = "#";
            btnInfo.innerText = "Mais info.";
            btnInfo.style.marginLeft = "15px";
            btnInfo.addEventListener('click', function(e) {
                e.preventDefault();

                let infoModal = document.getElementById('info-content');

                infoModal.innerHTML = `  
                    <span><strong>CLiente: </strong> ${element.nome_cliente || '-'}</span>
                    <p><strong>Nº Telemóvel: </strong> ${element.telefone_cliente || '-'}</p>
                    <span><strong>Data da criação do artigo: </strong> ${element.data_criacao}</span>
                    <p><strong>Artigo criado por: </strong> ${element.criado_por}</p>
                    <span><strong>Data marcado como "Pedido": </strong> ${element.data_pedido || '-'}</span>
                    <p><strong>Marcado como "Pedido" por: </strong> ${element.pedido_por || '-'}</p>
                    <span><strong>Data marcado como "Concluído": </strong> ${element.data_conclusao || '-'}</span>
                    <p><strong>Marcado como "Concluído" por: </strong> ${element.concluido_por || '-'}</p>
                `;

                document.getElementById('modal-info').style.display = 'flex';
            });
            acoes.appendChild(btnInfo);


            linha.appendChild(acoes);

            tbody.appendChild(linha);
        });
    })
}


function attPedido(id){
    fetch('reposicao.php', {
        method:"post",
        headers:{"Content-Type":"application/json"},
        body: JSON.stringify({
            "acao": "atualizar",
            "estado":"pedido",
            "id":id
        })
    }).then(response=>response.json())
    .then(data=>{
            if (data['resultado'] == "sucesso") {
                //tratar sucesso -> Recarregar tabela
                mostrarTabela();
                msg.style.color = "Green";
                msg.innerText = data['msg'];

            }
            else {
                //Mensagem de erro vinda do servidor
                msg.style.color = "red";
                msg.innerText = data['msg'];
            }
    })
}


function attConcluido(id){
    fetch('reposicao.php', {
        method:"post",
        headers:{"Content-Type":"application/json"},
        body: JSON.stringify({
            "acao": "atualizar",
            "estado":"concluido",
            "id":id
        })
    }).then(response=>response.json())
    .then(data=>{
            if (data['resultado'] == "sucesso") {
                //tratar sucesso -> Recarregar tabela
                mostrarTabela();
                msg.style.color = "Green";
                msg.innerText = data['msg'];

            }
            else {
                //Mensagem de erro vinda do servidor
                msg.style.color = "red";
                msg.innerText = data['msg'];
            }
    })
}
