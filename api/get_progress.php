<?php
/**
 * API Endpoint: Obter Progresso da Análise
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Tratar requisições OPTIONS para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Verificar se é GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Incluir dependências
require_once '../config/database.php';

try {
    // Verificar parâmetro analysis_id
    if (!isset($_GET['analysis_id']) || !is_numeric($_GET['analysis_id'])) {
        throw new Exception('ID da análise é obrigatório');
    }
    
    $analysisId = intval($_GET['analysis_id']);
    
    // Conectar ao banco
    $db = Database::getInstance();
    $helper = new DatabaseHelper();
    
    // Buscar dados da análise
    $analysis = $helper->getAnalysis($analysisId);
    
    if (!$analysis) {
        throw new Exception('Análise não encontrada');
    }
    
    // Buscar logs recentes
    $logs = $db->select(
        "SELECT step, message, level, created_at 
         FROM analysis_logs 
         WHERE analysis_id = ? 
         ORDER BY created_at DESC 
         LIMIT 10",
        [$analysisId]
    );
    
    // Calcular estatísticas básicas
    $stats = $helper->getAnalysisStats($analysisId);
    
    // Montar detalhes do progresso
    $details = [];
    
    foreach ($logs as $log) {
        $icon = getLogIcon($log['step'], $log['level']);
        $color = getLogColor($log['level']);
        
        $details[] = [
            'text' => $log['message'],
            'icon' => $icon,
            'color' => $color,
            'time' => date('H:i:s', strtotime($log['created_at']))
        ];
    }
    
    // Determinar progresso e status
    $progress = [
        'analysis_id' => $analysisId,
        'status' => $analysis['status'],
        'current' => $analysis['progress'],
        'total' => 100,
        'completed' => $analysis['status'] === 'completed',
        'failed' => $analysis['status'] === 'failed',
        'message' => getProgressMessage($analysis['status'], $analysis['progress']),
        'details' => array_reverse($details), // Mostrar do mais antigo para o mais recente
        'stats' => [
            'total_pages' => $stats['total_pages'] ?? 0,
            'pages_analyzed' => $analysis['total_pages_analyzed'] ?? 0,
            'start_time' => $analysis['start_time'],
            'estimated_completion' => calculateEstimatedCompletion($analysis)
        ]
    ];
    
    // Se completou, adicionar resumo final
    if ($analysis['status'] === 'completed') {
        $progress['summary'] = generateCompletionSummary($analysisId, $db);
    }
    
    // Se falhou, adicionar detalhes do erro
    if ($analysis['status'] === 'failed') {
        $errorLog = $db->selectOne(
            "SELECT message FROM analysis_logs 
             WHERE analysis_id = ? AND level = 'error' 
             ORDER BY created_at DESC LIMIT 1",
            [$analysisId]
        );
        
        $progress['error'] = $errorLog ? $errorLog['message'] : 'Erro desconhecido';
    }
    
    echo json_encode($progress);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Retorna ícone baseado no tipo de log
 */
function getLogIcon($step, $level) {
    $stepIcons = [
        'init' => 'play-circle',
        'start' => 'clock',
        'crawl_start' => 'spider',
        'crawl_complete' => 'check-circle',
        'analysis_start' => 'search',
        'technical_complete' => 'cog',
        'content_complete' => 'file-alt',
        'ai_complete' => 'robot',
        'reports_complete' => 'chart-bar',
        'complete' => 'check-circle',
        'error' => 'exclamation-circle'
    ];
    
    if (isset($stepIcons[$step])) {
        return $stepIcons[$step];
    }
    
    return $level === 'error' ? 'exclamation-circle' : 'info-circle';
}

/**
 * Retorna cor baseada no nível do log
 */
function getLogColor($level) {
    $colors = [
        'info' => 'blue',
        'warning' => 'yellow',
        'error' => 'red',
        'debug' => 'gray'
    ];
    
    return $colors[$level] ?? 'blue';
}

/**
 * Retorna mensagem de progresso baseada no status
 */
function getProgressMessage($status, $progress) {
    switch ($status) {
        case 'pending':
            return 'Aguardando início da análise...';
        case 'running':
            if ($progress < 25) return 'Rastreando páginas do site...';
            if ($progress < 50) return 'Coletando dados das páginas...';
            if ($progress < 75) return 'Analisando aspectos técnicos...';
            if ($progress < 90) return 'Analisando conteúdo e oportunidades...';
            return 'Finalizando análise e gerando relatórios...';
        case 'completed':
            return 'Análise concluída com sucesso!';
        case 'failed':
            return 'Erro durante a análise';
        default:
            return 'Status desconhecido';
    }
}

/**
 * Calcula tempo estimado para conclusão
 */
function calculateEstimatedCompletion($analysis) {
    if ($analysis['status'] !== 'running' || !$analysis['start_time']) {
        return null;
    }
    
    $elapsed = time() - strtotime($analysis['start_time']);
    $progress = $analysis['progress'];
    
    if ($progress <= 0) {
        return null;
    }
    
    $estimatedTotal = ($elapsed / $progress) * 100;
    $remaining = $estimatedTotal - $elapsed;
    
    return $remaining > 0 ? time() + $remaining : null;
}

/**
 * Gera resumo da análise completada
 */
function generateCompletionSummary($analysisId, $db) {
    $summary = [];
    
    // Total de páginas analisadas
    $pages = $db->selectOne(
        "SELECT COUNT(*) as total FROM pages WHERE analysis_id = ?",
        [$analysisId]
    );
    $summary['total_pages'] = $pages['total'];
    
    // Total de issues encontrados
    $issues = $db->selectOne(
        "SELECT COUNT(*) as total FROM issues WHERE analysis_id = ?",
        [$analysisId]
    );
    $summary['total_issues'] = $issues['total'];
    
    // Issues por severidade
    $severity = $db->select(
        "SELECT severity, COUNT(*) as count FROM issues 
         WHERE analysis_id = ? GROUP BY severity",
        [$analysisId]
    );
    
    $summary['issues_by_severity'] = [];
    foreach ($severity as $row) {
        $summary['issues_by_severity'][$row['severity']] = $row['count'];
    }
    
    // Top 3 problemas
    $topIssues = $db->select(
        "SELECT title, COUNT(*) as count FROM issues 
         WHERE analysis_id = ? 
         GROUP BY type, title 
         ORDER BY count DESC 
         LIMIT 3",
        [$analysisId]
    );
    
    $summary['top_issues'] = array_map(function($issue) {
        return [
            'title' => $issue['title'],
            'count' => $issue['count']
        ];
    }, $topIssues);
    
    return $summary;
}
?>
