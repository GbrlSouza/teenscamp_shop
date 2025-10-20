<?php
// api.php
// API RESTful para manipulação de produtos e login de administrador

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Admin-Token'); // Token para autenticação

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/config.php';

// Carrega as variáveis de ambiente novamente (já estão carregadas em config.php, mas garante)
$adminUser = $_ENV['ADMIN_USER'] ?? 'admin';
$adminPass = $_ENV['ADMIN_PASS'] ?? '123456';
$baseUrl = rtrim($_ENV['SITE_URL'], '/');
$uploadDir = __DIR__ . '/img_uploads/';

// Funções Auxiliares
function respond($data, $code = 200)
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function authenticate($adminUser, $adminPass)
{
    $headers = getallheaders();
    $token = $headers['X-Admin-Token'] ?? null;
    
    // Autenticação insegura (base64 simples) - Apenas para este exemplo didático
    if (!$token || base64_decode($token) !== $adminUser . ':' . $adminPass) {
        respond(['error' => 'Não autorizado. Requer X-Admin-Token.'], 401);
    }
}

// Conexão
try {
    $pdo = getPDO();
} catch (Exception $e) {
    respond(['error' => 'Erro na conexão com o banco de dados.'], 500);
}

$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;


// Tratamento de Requisições

// Ação de Login (Não requer o X-Admin-Token, pois o objetivo é obtê-lo)
if ($action === 'login' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (
        isset($input['username'], $input['password']) &&
        $input['username'] === $adminUser &&
        $input['password'] === $adminPass
    ) {
        // Retorna um token simples para a autenticação subsequente
        $token = base64_encode($adminUser . ':' . $adminPass);
        respond(['success' => true, 'message' => 'Login bem-sucedido.', 'token' => $token]);
    } else {
        respond(['success' => false, 'message' => 'Usuário ou senha incorretos!'], 401);
    }
}


switch ($method) {
    case 'GET':
        // Listar ou Buscar Produto
        if ($id) {
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$product) respond(['error' => 'Produto não encontrado.'], 404);
            respond($product);
        } else {
            $stmt = $pdo->query("SELECT * FROM products ORDER BY created_at DESC");
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            respond($products);
        }
        break;

    case 'POST':
        // Adicionar Novo Produto (Requer autenticação)
        authenticate($adminUser, $adminPass);
        
        if (!isset($_FILES['image'])) {
            respond(['error' => 'É necessário enviar uma imagem.'], 400);
        }

        $name = $_POST['name'] ?? '';
        $price = $_POST['price'] ?? '';
        $category = $_POST['category'] ?? '';
        $description = $_POST['description'] ?? '';

        if (empty($name) || empty($price) || empty($category)) {
            respond(['error' => 'Campos obrigatórios faltando.'], 400);
        }

        $file = $_FILES['image'];
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
        if (!in_array($file['type'], $allowed)) {
            respond(['error' => 'Tipo de imagem inválido. Use JPG, PNG ou WEBP.'], 400);
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
             respond(['error' => 'Falha no upload da imagem.'], 500);
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $newName = uniqid('prod_', true) . '.' . $ext;
        $dest = $uploadDir . $newName;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            respond(['error' => 'Erro ao mover o arquivo para o destino.'], 500);
        }

        $imageUrl = $baseUrl . '/img_uploads/' . $newName;

        $stmt = $pdo->prepare("
            INSERT INTO products (name, price, image_url, category, description)
            VALUES (:name, :price, :image_url, :category, :description)
        ");

        try {
            $stmt->execute([
                ':name' => $name,
                ':price' => $price,
                ':image_url' => $imageUrl,
                ':category' => $category,
                ':description' => $description
            ]);
            respond(['message' => 'Produto criado com sucesso.', 'id' => $pdo->lastInsertId()], 201);
        } catch (Exception $e) {
            // Em caso de erro, tente remover o arquivo
            @unlink($dest);
            respond(['error' => 'Erro ao inserir produto no banco.', 'details' => $e->getMessage()], 500);
        }

        break;

    case 'PUT':
        // Atualizar Produto (Requer autenticação)
        authenticate($adminUser, $adminPass);
        if (!$id) respond(['error' => 'ID do produto é obrigatório.'], 400);

        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) respond(['error' => 'JSON inválido.'], 400);

        $fields = ['name', 'price', 'category', 'description', 'image_url'];
        $updates = [];
        $params = [];

        foreach ($fields as $f) {
            if (isset($data[$f])) {
                $updates[] = "$f = :$f";
                $params[":$f"] = $data[$f];
            }
        }

        if (empty($updates)) respond(['error' => 'Nenhum campo para atualizar.'], 400);
        $params[':id'] = $id;

        $sql = "UPDATE products SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        try {
            $stmt->execute($params);
            respond(['message' => 'Produto atualizado com sucesso.']);
        } catch (Exception $e) {
            respond(['error' => 'Erro ao atualizar produto.'], 500);
        }
        break;

    case 'DELETE':
        // Deletar Produto (Requer autenticação)
        authenticate($adminUser, $adminPass);
        if (!$id) respond(['error' => 'ID do produto é obrigatório.'], 400);

        // Opcional: Pegar a URL da imagem para deletar o arquivo do servidor (melhoria)
        $stmt = $pdo->prepare("SELECT image_url FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        try {
            $stmt->execute([$id]);
            
            // Deleta o arquivo físico da imagem (se encontrado e se estiver na pasta correta)
            if ($product && strpos($product['image_url'], $baseUrl . '/img_uploads/') === 0) {
                 $imageName = basename($product['image_url']);
                 $imagePath = $uploadDir . $imageName;
                 if (file_exists($imagePath)) {
                     @unlink($imagePath); // Tenta deletar o arquivo
                 }
            }
            
            respond(['message' => 'Produto excluído com sucesso.']);
        } catch (Exception $e) {
            respond(['error' => 'Erro ao excluir produto.'], 500);
        }
        break;

    default:
        respond(['error' => 'Método não suportado.'], 405);
        break;
}