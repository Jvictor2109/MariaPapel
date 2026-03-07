<?php
// Nada antes da tag de abertura PHP
?>


<!-- Login -->
<!-- Seção de Login/Logout no header -->
<section id="search" class="alt">
    <?php if (isset($_SESSION['user_id'])): ?>
        <!-- Se o utilizador ESTÁ autenticado -->
        <div style="text-align: center; padding: 10px; background: #f5f6f7; border-radius: 5px;">
            <p style="margin: 0 0 5px 0; font-weight: bold;">
                 <?php echo htmlspecialchars($_SESSION['nome_utilizador'] ?? 'Utilizador'); ?>
            </p>
            <p style="margin: 0 0 10px 0; font-size: 0.8em; color: #666;">
                <?php echo isset($_SESSION['e_administrador']) && $_SESSION['e_administrador'] == 1 ? '🔑 Administrador' : '👤 Utilizador'; ?>
            </p>
            <a href="logout.php" class="button primary" >Logout</a>
        </div>
    <?php else: ?>
        <!-- Se o utilizador NÃO ESTÁ autenticado -->
        <a href="login.php" class="button primary">Login</a>
    <?php endif; ?>
</section>


<!-- Menu -->
<nav id="menu">
    <header class="major">
        <h2>Menu</h2>
    </header>
    <ul>
        <li><a href="index.php">Início</a></li>
            <li><a href="expedicao_vasp.php">Expedições VASP</a></li>
                <?php
                    if (isset($_SESSION['e_administrador']) && $_SESSION['e_administrador'] === 1) {
                        echo '<li>
                            <span class="opener">Painel de Controlo</span>
                            <ul>
                                <li><a href="gestao_utilizadores.php">Utilizador</a></li>
                                <li><a href="#">Ipsum Adipiscing</a></li>
                                <li><a href="#">Tempus Magna</a></li>
                                <li><a href="#">Feugiat Veroeros</a></li>
                            </ul>
                        </li>';
                    }
                ?>
    </ul>
</nav>
<?php
// Nada depois do menu
?>