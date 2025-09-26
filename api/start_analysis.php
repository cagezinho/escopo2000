<?php
/**
 * API Endpoint: Iniciar Análise de SEO
 * Recebe parâmetros da URL e inicia o processo de análise
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Tratar requisições OPTIONS para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Verificar se é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Incluir dependências
require_once '../config/database_shared_hosting.php';
require_once '../classes/Crawler.php';
require_once '../classes/SEOAnalyzer.php';

try {
    // Ler dados JSON da requisição
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Dados inválidos recebidos');
    }
    
    // Validar parâmetros obrigatórios
    if (empty($input['url'])) {
        throw new Exception('URL é obrigatória');
    }
    
    $url = filter_var($input['url'], FILTER_VALIDATE_URL);
    if (!$url) {
        throw new Exception('URL inválida');
    }
    
    // Extrair domínio
    $parsedUrl = parse_url($url);
    $domain = $parsedUrl['host'];
    
    // Parâmetros opcionais
    $maxPages = isset($input['maxPages']) ? intval($input['maxPages']) : 100;
    $respectRobots = isset($input['respectRobots']) ? (bool)$input['respectRobots'] : true;
    $includeExternal = isset($input['includeExternal']) ? (bool)$input['includeExternal'] : false;
    
    // Validar limites
    if ($maxPages < 1 || $maxPages > 1000) {
        throw new Exception('Número de páginas deve estar entre 1 e 1000');
    }
    
    // Conectar ao banco
    $db = Database::getInstance();
    $helper = new DatabaseHelper();
    
    // Verificar se não há análise em andamento para este domínio
    $existingAnalysis = $db->selectOne(
        "SELECT id FROM analyses WHERE domain = ? AND status IN ('pending', 'running') ORDER BY created_at DESC LIMIT 1",
        [$domain]
    );
    
    if ($existingAnalysis) {
        // Retornar análise existente
        echo json_encode([
            'success' => true,
            'analysis_id' => $existingAnalysis['id'],
            'message' => 'Análise já em andamento para este domínio'
        ]);
        exit;
    }
    
    // Criar nova análise
    $analysisId = $helper->createAnalysis($url, $domain, $maxPages, $respectRobots, $includeExternal);
    
    if (!$analysisId) {
        throw new Exception('Erro ao criar análise no banco de dados');
    }
    
    // Adicionar log inicial
    $helper->addLog($analysisId, 'init', 'Análise iniciada', 'info', [
        'url' => $url,
        'max_pages' => $maxPages,
        'respect_robots' => $respectRobots,
        'include_external' => $includeExternal
    ]);
    
    // Iniciar processo de análise em background
    startBackgroundAnalysis($analysisId);
    
    // Retornar resposta de sucesso
    echo json_encode([
        'success' => true,
        'analysis_id' => $analysisId,
        'message' => 'Análise iniciada com sucesso'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Inicia processo de análise em background
 */
function startBackgroundAnalysis($analysisId) {
    // Para desenvolvimento, executar de forma síncrona
    // Em produção, usar job queue ou exec em background
    
    if (defined('ENABLE_BACKGROUND_PROCESSING') && ENABLE_BACKGROUND_PROCESSING) {
        // Executar em background usando exec
        $scriptPath = __DIR__ . '/process_analysis.php';
        $command = "php \"$scriptPath\" $analysisId > /dev/null 2>&1 &";
        
        if (PHP_OS_FAMILY === 'Windows') {
            pclose(popen("start /B " . $command, "r"));
        } else {
            exec($command);
        }
    } else {
        // Executar de forma síncrona para desenvolvimento
        // Registrar shutdown function para não bloquear resposta HTTP
        register_shutdown_function(function() use ($analysisId) {
            if (connection_status() === CONNECTION_NORMAL) {
                fastcgi_finish_request(); // Finaliza resposta HTTP se disponível
            }
            
            require_once __DIR__ . '/process_analysis.php';
            processAnalysis($analysisId);
        });
    }
}
?>
