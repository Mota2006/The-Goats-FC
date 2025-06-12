<?php
session_start();

// Database connection
if (!isset($conn)) {
    $conn = new mysqli("localhost", "root", "", "the goats fc");
    if ($conn->connect_error) {
        die("Erro na ligação: " . $conn->connect_error);
    }
}

// Ensure $_SESSION variables are available (though not strictly used for display only)
$current_user = $_SESSION['username'] ?? '';
$current_login_id = $_SESSION['user_id'] ?? '';
$current_role = $_SESSION['role_login'] ?? '';

// Logic to fetch all scheduled events for display on the home page
$sql_events_display = "SELECT s.*, t.name as team_name FROM schedule s JOIN teams t ON s.team_id = t.id WHERE s.type = 'Jogo' ORDER BY s.date ASC, s.time ASC";
$events_display = $conn->query($sql_events_display);

if ($events_display === false) {
    // Handle error if query fails
    error_log("Error fetching events for display: " . $conn->error);
    $events_display = new mysqli_result(new mysqli()); // Create an empty result set to prevent errors in the loop
}

?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8" />
    <title>Página Inicial - The Goats FC</title>
    <link rel="icon" type="image/x-icon" href="logo.png" />
    <link rel="stylesheet" href="style.css"/>
    <style>
        /* Reset and base */
        *, *::before, *::after {
            box-sizing: border-box;
        }
        a {
            color: #374151;
            text-decoration: none;
            cursor: pointer;
            transition: color 0.3s ease;
        }
        a:hover,
        a:focus {
            color: #111827;
            outline: none;
        }
        /* Navbar */
        .navbar-container {
            position: sticky;
            top: 0;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 1rem;
            box-shadow: 0 2px 6px rgba(0,0,0,0.07);
            z-index: 1000;
            font-weight: 600;
            font-size: 16px;
            user-select: none;
        }
        .navbar-logo img {
            height: 48px;
            width: auto;
            border-radius: 6px;
        }

        /* Nav links */
        .nav-tabs {
            list-style: none;
            display: flex;
            gap: 2rem;
            margin: 0;
            padding: 0;
        }
        .nav-tabs .nav-item {
            
        }
        .nav-tabs .nav-link {
            padding: 0.5rem 0;
            text-transform: capitalize;
            font-weight: 600;
            color: #4b5563;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }
        .nav-tabs .nav-link.active,
        .nav-tabs .nav-link:hover,
        .nav-tabs .nav-link:focus {
            color: #111827;
            border-bottom-color: #2563eb; /* blue border underline */
            outline: none;
        }
        .tab-content {
            min-height: 350px;
            margin-top: 1rem;
        }

        /* Tab panes */
        .tab-pane {
            display: none;
            opacity: 0;
            transition: opacity 0.4s ease;
        }
        .tab-pane.active {
            display: block;
            opacity: 1;
        }
        /* Text sections with cards style */
        .card {
            background: #ffffff;
            border-radius: 0.75rem;
            padding: 1.5rem 2rem;
            box-shadow: 0 4px 12px rgb(0 0 0 / 0.05);
            max-width: 900px;
            margin: 0 auto;
            color: #374151;
            font-size: 18px;
            line-height: 1.7;
        }
        html, body {
            margin: 0;
            padding: 1rem;
            height: 100%;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const navLinks = document.querySelectorAll('.nav-link');
            const tabPanes = document.querySelectorAll('.tab-pane');

            navLinks.forEach(link => {
                link.addEventListener('click', function (e) {
                    // Verificar se o href aponta para um ID na mesma página (uma tab)
                    const href = this.getAttribute('href');
                    if (href && href.startsWith('#')) {
                        // Se for uma tab, prevenir o comportamento padrão do link
                        e.preventDefault();

                        // Remover a classe 'active' de todos os links de navegação
                        navLinks.forEach(nav => nav.classList.remove('active'));
                        // Adicionar a classe 'active' ao link clicado
                        this.classList.add('active');

                        // Esconder todos os painéis de tabs
                        tabPanes.forEach(pane => {
                            pane.classList.remove('active');
                        });
                        // Mostrar o painel de tab alvo
                        const targetId = href.substring(1);
                        const targetPane = document.getElementById(targetId);
                        if (targetPane) {
                            targetPane.classList.add('active');
                        }
                    }
                });
            });
        });
    </script>
</head>
<body>
    <div>
        <ul class="nav nav-tabs" role="tablist" aria-label="Main Navigation">  
            <li class="nav-item" role="presentation">
                <a class="nav-link active" data-bs-toggle="pill" href="#home" role="tab" aria-selected="true" tabindex="0">Home</a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" data-bs-toggle="tab" href="#historia" role="tab" aria-selected="false" tabindex="-1">História</a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" data-bs-toggle="tab" href="#sobre-nos" role="tab" aria-selected="false" tabindex="-1">Sobre Nós</a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" data-bs-toggle="tab" href="#registo" role="tab" aria-selected="false" tabindex="-1">REGISTO</a>
            </li>
            <li>
                <a class="nav-link" data-bs-toggle="tab"  href="Login_Registo/login_view.php" role="tab" aria-selected="false" tabindex="-1">LOGIN</a>
            </li>                  
        </ul>
    </div>    

    <header>
        The Goats FC
    </header>

    <main class="container">
        <div class="tab-content">
            <div class="tab-pane container active" id="home" role="tabpanel" aria-hidden="false">
                <section>
                    <h2>Bem-vindo!</h2>
                    <p style="text-align: center;">Seja bem-vindo à casa dos The Goats FC! Aqui poderá encontrar os próximos jogos e treinos, e ficar a par de tudo o que acontece no nosso clube. A nossa paixão pelo futebol impulsiona-nos a cada dia, e estamos entusiasmados por partilhar esta jornada consigo.</p>
                    <h2>Próximos Jogos</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Hora</th>
                                <th>Equipa</th>
                                <th>Adversário</th>
                                <th>Descrição</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $events_display->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['date']) ?></td>
                                    <td><?= htmlspecialchars($row['time']) ?></td>
                                    <td><?= nl2br(htmlspecialchars($row['team_name'])) ?></td>
                                    <td><?= nl2br(htmlspecialchars($row['opponent'])) ?></td>
                                    <td><?= nl2br(htmlspecialchars($row['description'])) ?></td>
                                </tr>
                            <?php endwhile; ?>
                            <?php if ($events_display->num_rows === 0): ?>
                                <tr>
                                    <td colspan="5">Não há jogos agendados no momento.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </section>
            </div>

            <div class="tab-pane container fade" id="historia" role="tabpanel" aria-hidden="true">
                <section class="card" aria-label="História do Clube">
                    <h2>História do Clube</h2>
                    <p>
                        Fundado em 1998, The Goats FC começou como um pequeno grupo de entusiastas apaixonados por futebol, com o sonho de construir um clube que representasse a dedicação, a comunidade e o espírito de equipa. Ao longo dos anos, crescemos não só em tamanho mas também em ambição e profissionalismo.  
                    </p>
                    <p>
                        O nosso clube tem estado presente em várias competições regionais e nacionais, sempre com foco em promover o talento local e desenvolver jovens atletas. Acreditamos que o verdadeiro sucesso vai além dos troféus, passando pelo impacto positivo que temos na nossa comunidade e na vida dos nossos jogadores.
                    </p>
                    <p>
                        Hoje, The Goats FC não é apenas um clube de futebol – é uma família que partilha valores de respeito, paixão e excelência. Estamos orgulhosos do nosso percurso e entusiasmados com o futuro que estamos a construir juntos.
                    </p>
                </section>
            </div>

            <div class="tab-pane container fade" id="sobre-nos" role="tabpanel" aria-hidden="true">
                <section class="card" aria-label="Sobre o Clube">
                    <h2>O Nosso Clube</h2>
                    <p>The Goats FC é um clube de futebol dedicado à excelência, ao trabalho de equipa e ao envolvimento na comunidade. Esforçamo-nos por alcançar os mais altos padrões tanto dentro como fora do campo. Junte-se a nós na nossa jornada para o sucesso!</p>
                    <p>A nossa filosofia é construída sobre a paixão, disciplina e melhoria contínua. Acreditamos em cultivar talento e fomentar um forte sentido de camaradagem entre os nossos jogadores e apoiantes.</p>
                </section>
            </div>
            <div class="tab-pane container fade" id="registo" role="tabpanel" aria-hidden="false">
                <section class="card" aria-label="Registo" style="text-align: center;">
                    <h2>Quer Fazer Parte da Nossa Família?</h2>
                    <p>Junte-se ao The Goats FC! Quer seja para jogar, voluntariar-se ou apoiar, há um lugar para si na nossa comunidade.</p>
                    <a href="Login_Registo/registo_view.php" class="nav-link">Registe-se Agora!</a>
                </section>
            </div>                                       
        </div>
    </main>
</body>
</html>