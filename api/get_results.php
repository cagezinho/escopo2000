<?php
/**
 * API Endpoint: Obter Resultados da Análise
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
    
    // Verificar se análise existe e está completa
    $analysis = $helper->getAnalysis($analysisId);
    
    if (!$analysis) {
        throw new Exception('Análise não encontrada');
    }
    
    if ($analysis['status'] !== 'completed') {
        throw new Exception('Análise ainda não foi concluída');
    }
    
    // Montar dados dos resultados
    $results = [
        'success' => true,
        'analysis_id' => $analysisId,
        'data' => [
            'summary' => generateSummary($analysisId, $db),
            'charts' => generateChartData($analysisId, $db),
            'technical' => generateTechnicalData($analysisId, $db),
            'content' => generateContentData($analysisId, $db),
            'ai' => generateAIData($analysisId, $db)
        ]
    ];
    
    echo json_encode($results);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Gera dados de resumo geral
 */
function generateSummary($analysisId, $db) {
    $summary = [];
    
    // Contar páginas por status
    $pagesOk = $db->selectOne(
        "SELECT COUNT(*) as count FROM pages WHERE analysis_id = ? AND status_code = 200",
        [$analysisId]
    );
    $summary['pages_ok'] = $pagesOk['count'];
    
    // Contar issues por severidade
    $issues = $db->select(
        "SELECT severity, COUNT(*) as count FROM issues WHERE analysis_id = ? GROUP BY severity",
        [$analysisId]
    );
    
    $summary['warnings'] = 0;
    $summary['errors'] = 0;
    $summary['opportunities'] = 0;
    
    foreach ($issues as $issue) {
        switch ($issue['severity']) {
            case 'low':
            case 'medium':
                $summary['warnings'] += $issue['count'];
                break;
            case 'high':
            case 'critical':
                $summary['errors'] += $issue['count'];
                break;
        }
        $summary['opportunities'] += $issue['count'];
    }
    
    // Total de páginas analisadas
    $totalPages = $db->selectOne(
        "SELECT COUNT(*) as count FROM pages WHERE analysis_id = ?",
        [$analysisId]
    );
    $summary['total_pages'] = $totalPages['count'];
    
    // Tempo de análise
    $analysis = $db->selectOne(
        "SELECT TIMESTAMPDIFF(SECOND, start_time, end_time) as duration FROM analyses WHERE id = ?",
        [$analysisId]
    );
    $summary['analysis_time'] = $analysis['duration'] ?? 0;
    
    return $summary;
}

/**
 * Gera dados para gráficos
 */
function generateChartData($analysisId, $db) {
    $charts = [];
    
    // Distribuição de status HTTP
    $statusData = $db->select(
        "SELECT status_code, COUNT(*) as count FROM pages WHERE analysis_id = ? GROUP BY status_code",
        [$analysisId]
    );
    
    $charts['status_distribution'] = [];
    foreach ($statusData as $row) {
        $statusText = getStatusText($row['status_code']);
        $charts['status_distribution'][$statusText] = $row['count'];
    }
    
    // Distribuição de performance
    $performanceRanges = [
        ['min' => 0, 'max' => 1000, 'label' => '< 1s'],
        ['min' => 1000, 'max' => 2000, 'label' => '1-2s'],
        ['min' => 2000, 'max' => 3000, 'label' => '2-3s'],
        ['min' => 3000, 'max' => 5000, 'label' => '3-5s'],
        ['min' => 5000, 'max' => 999999, 'label' => '> 5s']
    ];
    
    $charts['performance_distribution'] = [];
    foreach ($performanceRanges as $range) {
        $count = $db->selectOne(
            "SELECT COUNT(*) as count FROM pages 
             WHERE analysis_id = ? AND load_time >= ? AND load_time < ? AND status_code = 200",
            [$analysisId, $range['min'], $range['max']]
        );
        $charts['performance_distribution'][] = $count['count'];
    }
    
    return $charts;
}

/**
 * Gera dados técnicos
 */
function generateTechnicalData($analysisId, $db) {
    $technical = [];
    
    // Resumo técnico
    $technical['summary'] = [
        'duplicate_titles' => countIssueType($analysisId, 'duplicate_titles', $db),
        'missing_meta_descriptions' => countIssueType($analysisId, 'missing_meta_description', $db),
        'not_found_pages' => countPagesByStatus($analysisId, 404, $db),
        'slow_pages' => countSlowPages($analysisId, $db),
        'broken_links' => countIssueType($analysisId, 'broken_internal_link', $db),
        'missing_canonical' => countIssueType($analysisId, 'missing_canonical', $db)
    ];
    
    // Páginas com detalhes técnicos
    $technical['pages'] = $db->select(
        "SELECT p.url, p.status_code, p.load_time, p.page_size, 
                pc.title, pc.title_length, pc.meta_description_length,
                GROUP_CONCAT(DISTINCT i.title SEPARATOR ', ') as issues
         FROM pages p
         LEFT JOIN page_content pc ON p.id = pc.page_id
         LEFT JOIN issues i ON p.id = i.page_id AND i.category = 'technical'
         WHERE p.analysis_id = ?
         GROUP BY p.id
         ORDER BY p.load_time DESC
         LIMIT 100",
        [$analysisId]
    );
    
    // Processar dados das páginas
    foreach ($technical['pages'] as &$page) {
        $page['issues'] = $page['issues'] ? explode(', ', $page['issues']) : [];
        $page['load_time'] = intval($page['load_time']);
        $page['page_size'] = intval($page['page_size']);
    }
    
    return $technical;
}

/**
 * Gera dados de conteúdo
 */
function generateContentData($analysisId, $db) {
    $content = [];
    
    // Oportunidades de conteúdo
    $content['opportunities'] = [
        'pages_without_h1' => countIssueType($analysisId, 'missing_h1', $db),
        'short_content_pages' => countIssueType($analysisId, 'short_content', $db),
        'generic_titles' => countIssueType($analysisId, 'generic_title', $db),
        'orphan_pages' => countIssueType($analysisId, 'orphan_page', $db),
        'featured_snippet_potential' => countIssueType($analysisId, 'featured_snippet_opportunity', $db)
    ];
    
    // Páginas com análise de conteúdo
    $content['pages'] = $db->select(
        "SELECT p.url, pc.title, pc.title_length, pc.h1, pc.word_count,
                GROUP_CONCAT(DISTINCT i.title SEPARATOR ', ') as content_opportunities
         FROM pages p
         LEFT JOIN page_content pc ON p.id = pc.page_id
         LEFT JOIN issues i ON p.id = i.page_id AND i.category = 'content'
         WHERE p.analysis_id = ? AND p.status_code = 200
         GROUP BY p.id
         ORDER BY pc.word_count ASC
         LIMIT 100",
        [$analysisId]
    );
    
    // Processar dados de conteúdo
    foreach ($content['pages'] as &$page) {
        $page['content_opportunities'] = $page['content_opportunities'] ? 
            explode(', ', $page['content_opportunities']) : [];
        $page['word_count'] = intval($page['word_count']);
        $page['title_length'] = intval($page['title_length']);
    }
    
    return $content;
}

/**
 * Gera dados para IA e SGE
 */
function generateAIData($analysisId, $db) {
    $ai = [];
    
    // Oportunidades para IA
    $ai['opportunities'] = [
        'pages_with_faq' => countPagesWithFAQ($analysisId, $db),
        'faq_potential' => countIssueType($analysisId, 'faq_schema_opportunity', $db),
        'howto_content' => countPagesWithHowTo($analysisId, $db),
        'table_content' => countPagesWithTables($analysisId, $db),
        'direct_answer_potential' => countDirectAnswerPotential($analysisId, $db),
        'comparison_potential' => countComparisonPotential($analysisId, $db),
        'list_potential' => countListPotential($analysisId, $db),
        'faq_recommendations' => [
            'Implementar schema.org FAQPage em páginas com perguntas frequentes',
            'Estruturar conteúdo em formato de pergunta e resposta',
            'Criar seções FAQ em páginas de produto/serviço'
        ],
        'structured_recommendations' => [
            'Usar listas numeradas para tutoriais passo-a-passo',
            'Implementar schema.org HowTo para guias',
            'Criar tabelas comparativas para produtos/serviços'
        ]
    ];
    
    // Análise E-E-A-T
    $eatData = $db->selectOne(
        "SELECT 
            AVG(experience_score) as avg_experience,
            AVG(expertise_score) as avg_expertise,
            AVG(authoritativeness_score) as avg_authoritativeness,
            AVG(trustworthiness_score) as avg_trustworthiness,
            AVG(total_score) as avg_total
         FROM eat_analysis ea
         JOIN pages p ON ea.page_id = p.id
         WHERE p.analysis_id = ?",
        [$analysisId]
    );
    
    $ai['eat_analysis'] = [
        'experience_score' => round($eatData['avg_experience'] ?? 0),
        'expertise_score' => round($eatData['avg_expertise'] ?? 0),
        'authoritativeness_score' => round($eatData['avg_authoritativeness'] ?? 0),
        'trustworthiness_score' => round($eatData['avg_trustworthiness'] ?? 0),
        'total_score' => round($eatData['avg_total'] ?? 0),
        'recommendations' => generateEATRecommendations($analysisId, $db)
    ];
    
    // Dados estruturados
    $structuredData = $db->select(
        "SELECT schema_type, COUNT(*) as count 
         FROM structured_data sd
         JOIN pages p ON sd.page_id = p.id
         WHERE p.analysis_id = ?
         GROUP BY schema_type",
        [$analysisId]
    );
    
    $ai['structured_data'] = [
        'total_pages_with_schema' => countPagesWithSchema($analysisId, $db),
        'faq_schema_count' => getSchemaCount($structuredData, 'FAQPage'),
        'article_schema_count' => getSchemaCount($structuredData, 'Article'),
        'howto_schema_count' => getSchemaCount($structuredData, 'HowTo'),
        'opportunities' => [
            'Implementar schema.org FAQPage em ' . countIssueType($analysisId, 'faq_schema_opportunity', $db) . ' páginas',
            'Adicionar schema.org Article para ' . countBlogPosts($analysisId, $db) . ' artigos/posts',
            'Criar schema.org HowTo para ' . countTutorialPages($analysisId, $db) . ' tutoriais',
            'Implementar schema.org Product para páginas de produto'
        ]
    ];
    
    return $ai;
}

// Funções auxiliares

function getStatusText($statusCode) {
    $statusTexts = [
        200 => '200 - OK',
        301 => '301 - Redirecionamento',
        302 => '302 - Redirecionamento Temporário',
        404 => '404 - Não Encontrado',
        500 => '500 - Erro do Servidor'
    ];
    
    return $statusTexts[$statusCode] ?? $statusCode . ' - Outros';
}

function countIssueType($analysisId, $type, $db) {
    $result = $db->selectOne(
        "SELECT COUNT(*) as count FROM issues WHERE analysis_id = ? AND type = ?",
        [$analysisId, $type]
    );
    
    return $result['count'];
}

function countPagesByStatus($analysisId, $status, $db) {
    $result = $db->selectOne(
        "SELECT COUNT(*) as count FROM pages WHERE analysis_id = ? AND status_code = ?",
        [$analysisId, $status]
    );
    
    return $result['count'];
}

function countSlowPages($analysisId, $db) {
    $result = $db->selectOne(
        "SELECT COUNT(*) as count FROM pages WHERE analysis_id = ? AND load_time > 3000 AND status_code = 200",
        [$analysisId]
    );
    
    return $result['count'];
}

function countPagesWithFAQ($analysisId, $db) {
    $result = $db->selectOne(
        "SELECT COUNT(*) as count FROM pages p
         JOIN page_content pc ON p.id = pc.page_id
         WHERE p.analysis_id = ? AND p.status_code = 200
         AND (pc.title LIKE '%FAQ%' OR pc.title LIKE '%pergunt%' OR pc.h1 LIKE '%FAQ%')",
        [$analysisId]
    );
    
    return $result['count'];
}

function countPagesWithHowTo($analysisId, $db) {
    $result = $db->selectOne(
        "SELECT COUNT(*) as count FROM pages p
         JOIN page_content pc ON p.id = pc.page_id
         WHERE p.analysis_id = ? AND p.status_code = 200
         AND (pc.title LIKE '%como%' OR pc.title LIKE '%tutorial%' OR pc.title LIKE '%passo%')",
        [$analysisId]
    );
    
    return $result['count'];
}

function countPagesWithTables($analysisId, $db) {
    // Implementação simplificada - em produção, analisaria o HTML
    $result = $db->selectOne(
        "SELECT COUNT(*) as count FROM pages p
         JOIN page_content pc ON p.id = pc.page_id
         WHERE p.analysis_id = ? AND p.status_code = 200
         AND (pc.title LIKE '%comparação%' OR pc.title LIKE '%tabela%' OR pc.title LIKE '%versus%')",
        [$analysisId]
    );
    
    return $result['count'];
}

function countDirectAnswerPotential($analysisId, $db) {
    $result = $db->selectOne(
        "SELECT COUNT(*) as count FROM pages p
         JOIN page_content pc ON p.id = pc.page_id
         WHERE p.analysis_id = ? AND p.status_code = 200
         AND (pc.title LIKE '%o que é%' OR pc.title LIKE '%what is%' OR pc.title LIKE '%definição%')",
        [$analysisId]
    );
    
    return $result['count'];
}

function countComparisonPotential($analysisId, $db) {
    $result = $db->selectOne(
        "SELECT COUNT(*) as count FROM pages p
         JOIN page_content pc ON p.id = pc.page_id
         WHERE p.analysis_id = ? AND p.status_code = 200
         AND (pc.title LIKE '%vs%' OR pc.title LIKE '%versus%' OR pc.title LIKE '%comparação%')",
        [$analysisId]
    );
    
    return $result['count'];
}

function countListPotential($analysisId, $db) {
    $result = $db->selectOne(
        "SELECT COUNT(*) as count FROM pages p
         JOIN page_content pc ON p.id = pc.page_id
         WHERE p.analysis_id = ? AND p.status_code = 200
         AND (pc.title LIKE '%melhores%' OR pc.title LIKE '%top%' OR pc.title LIKE '%lista%')",
        [$analysisId]
    );
    
    return $result['count'];
}

function countPagesWithSchema($analysisId, $db) {
    $result = $db->selectOne(
        "SELECT COUNT(DISTINCT p.id) as count FROM pages p
         JOIN structured_data sd ON p.id = sd.page_id
         WHERE p.analysis_id = ?",
        [$analysisId]
    );
    
    return $result['count'];
}

function getSchemaCount($structuredData, $schemaType) {
    foreach ($structuredData as $data) {
        if ($data['schema_type'] === $schemaType) {
            return $data['count'];
        }
    }
    return 0;
}

function countBlogPosts($analysisId, $db) {
    $result = $db->selectOne(
        "SELECT COUNT(*) as count FROM pages p
         JOIN page_content pc ON p.id = pc.page_id
         WHERE p.analysis_id = ? AND p.status_code = 200
         AND (p.url LIKE '%/blog/%' OR p.url LIKE '%/artigo/%' OR p.url LIKE '%/post/%')",
        [$analysisId]
    );
    
    return $result['count'];
}

function countTutorialPages($analysisId, $db) {
    $result = $db->selectOne(
        "SELECT COUNT(*) as count FROM pages p
         JOIN page_content pc ON p.id = pc.page_id
         WHERE p.analysis_id = ? AND p.status_code = 200
         AND (pc.title LIKE '%tutorial%' OR pc.title LIKE '%guia%' OR pc.title LIKE '%como fazer%')",
        [$analysisId]
    );
    
    return $result['count'];
}

function generateEATRecommendations($analysisId, $db) {
    $recommendations = [
        'Adicionar informações sobre autor e credenciais em artigos',
        'Incluir datas de publicação e atualização em conteúdos',
        'Implementar página "Sobre" com informações da empresa',
        'Adicionar links para fontes confiáveis e estudos relevantes',
        'Criar política de privacidade e termos de serviço',
        'Incluir informações de contato e endereço da empresa',
        'Adicionar testimoniais e avaliações de clientes',
        'Implementar certificações e selos de confiança'
    ];
    
    return $recommendations;
}
?>
