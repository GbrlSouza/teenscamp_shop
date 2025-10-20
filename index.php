<?php
// index.php
// Frontend e Estrutura HTML/CSS/JS

// Inclui o config para carregar o .env e definir a função getPDO() (embora o index.php não use PDO diretamente, ele carrega o ambiente)
require_once __DIR__ . '/config.php';

// A URL base é usada para referenciar recursos (CSS, JS, Imagens)
$baseUrl = $_ENV['SITE_URL'] ?? '';
$cssUrl = $baseUrl . '/style.css';
$jsUrl = $baseUrl . '/script.js';

// Função para buscar produtos se o JavaScript falhar, mas manteremos o JS para a loja dinâmica
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teens Camp Shop | Geração Metanoia</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    
    <link rel="stylesheet" href="<?= htmlspecialchars($cssUrl) ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body class="bg-light text-dark" style="font-family: 'Montserrat', sans-serif;">
    
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top" style="z-index: 1030;">
        <div class="container-fluid container">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <div class="bg-danger rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 48px; height: 48px;">
                    <span class="text-white fw-bold fs-5">TC</span>
                </div>
                <h1 class="fs-4 fw-bold text-uppercase mb-0">
                    <span class="text-danger">Teens Camp</span> Shop
                </h1>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation" id="mobile-menu-btn">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active text-danger fw-bold" aria-current="page" href="#">Loja</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" id="cart-btn">
                            <i class="fas fa-shopping-cart"></i> Carrinho (<span id="cart-count" class="badge rounded-pill bg-danger">0</span>)
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#adminLoginModal" id="admin-btn">
                            <i class="fas fa-user-shield"></i> Admin
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container my-5">
        <section class="mb-5">
            <div class="text-center mb-5">
                <h2 class="display-3 fw-bolder text-uppercase" style="color: #8B0000;">
                    <span class="title-outline" style="-webkit-text-stroke: 2px #8B0000; color: transparent;">Produtos</span>
                </h2>
                <p class="lead text-muted">Apoie nosso acampamento e leve um pedaço da Geração Metanoia para casa!</p>
            </div>
            
            <div class="mb-4 d-flex justify-content-center">
                 <div class="btn-group" role="group" aria-label="Filtro de Categorias">
                    <button type="button" class="btn btn-outline-danger active" data-category="all">Todos</button>
                    <button type="button" class="btn btn-outline-danger" data-category="camisetas">Camisetas</button>
                    <button type="button" class="btn btn-outline-danger" data-category="acessorios">Acessórios</button>
                    <button type="button" class="btn btn-outline-danger" data-category="livros">Livros</button>
                 </div>
            </div>

            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4" id="products-container">
                <div class="col text-center">
                    <div class="spinner-border text-danger" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Carregando produtos...</p>
                </div>
            </div>
        </section>
        
        <section class="text-center my-5 p-4 bg-white rounded shadow-sm">
            <h3 class="fs-4 fw-bold text-danger mb-4">Siga-nos no Instagram!</h3>
            <p class="lead">Fique por dentro das novidades e dos nossos eventos.</p>
            <a href="https://www.instagram.com/teenscamp_setor3/" target="_blank" class="btn btn-outline-danger">
                <i class="fab fa-instagram me-2"></i> @teenscamp
            </a>
        </section>
    </main>

    <footer class="bg-dark text-white py-4 mt-auto flex-shrink-0">
        <div class="container text-center">
            <p class="mb-0">&copy; <?= date('Y') ?> Teens Camp Shop | Geração Metanoia.</p>
        </div>
    </footer>

    <div class="modal fade" id="cartModal" tabindex="-1" aria-labelledby="cartModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="cartModalLabel"><i class="fas fa-shopping-cart me-2"></i> Seu Carrinho</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <ul class="list-group list-group-flush" id="cart-items">
                        <li class="list-group-item text-muted text-center">O carrinho está vazio.</li>
                    </ul>
                </div>
                <div class="modal-footer d-block">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="fs-5 fw-bold">Subtotal:</span>
                        <span class="fs-5 fw-bold text-danger" id="cart-subtotal">R$ 0,00</span>
                    </div>
                    <button type="button" class="btn btn-danger w-100" id="checkout-btn"><i class="fas fa-money-check-alt me-2"></i> Finalizar Compra</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="adminLoginModal" tabindex="-1" aria-labelledby="adminLoginModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title" id="adminLoginModalLabel"><i class="fas fa-lock me-2"></i> Acesso Administrativo</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="admin-login-form">
                        <div class="mb-3">
                            <label for="username" class="form-label">Usuário:</label>
                            <input type="text" class="form-control" id="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Senha:</label>
                            <input type="password" class="form-control" id="password" required>
                            <div class="form-text">As credenciais são definidas no arquivo **.env**.</div>
                        </div>
                        <button type="submit" class="btn btn-danger w-100">Entrar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="adminPanelModal" tabindex="-1" aria-labelledby="adminPanelModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="adminPanelModalLabel"><i class="fas fa-cogs me-2"></i> Painel Administrativo</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" id="close-admin-panel"></button>
                </div>
                <div class="modal-body">
                    <h3 class="mb-4 text-danger fw-bold">Gerenciamento de Produtos</h3>
                    
                    <div class="card mb-4">
                        <div class="card-header fw-bold bg-light">Adicionar Novo Produto</div>
                        <div class="card-body">
                            <form id="add-product-form" enctype="multipart/form-data">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="product-name" class="form-label">Nome do Produto</label>
                                        <input type="text" class="form-control" id="product-name" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="product-price" class="form-label">Preço (R$)</label>
                                        <input type="number" step="0.01" class="form-control" id="product-price" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="product-image" class="form-label">Imagem do Produto</label>
                                        <input type="file" class="form-control" id="product-image" accept="image/*" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="product-category" class="form-label">Categoria</label>
                                        <select class="form-select" id="product-category" required>
                                            <option value="">Selecione...</option>
                                            <option value="camisetas">Camisetas</option>
                                            <option value="acessorios">Acessórios</option>
                                            <option value="livros">Livros</option>
                                            <option value="variados">Variados</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label for="product-description" class="form-label">Descrição</label>
                                        <textarea id="product-description" rows="3" class="form-control"></textarea>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-danger fw-bold">
                                            Adicionar Produto
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div>
                        <h4 class="text-xl fw-bold mb-3 text-danger">Produtos Cadastrados</h4>
                        <div class="list-group" id="admin-products-list">
                            <p class="text-center text-muted">Carregando lista...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    
    <script src="<?= htmlspecialchars($jsUrl) ?>"></script>
</body>

</html>