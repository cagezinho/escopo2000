<?php
/**
 * API Endpoint: Exportar Dados da Análise
 */

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
require_once '../config/database.php';

try {
    // Ler dados JSON da requisição
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Dados inválidos recebidos');
    }
    
    // Validar parâmetros
    if (!isset($input['analysis_id']) || !is_numeric($input['analysis_id'])) {
        throw new Exception('ID da análise é obrigatório');
    }
    
    if (!isset($input['format']) || !in_array($input['format'], ['csv', 'excel', 'pdf'])) {
        throw new Exception('Formato de exportação inválido');
    }
    
    $analysisId = intval($input['analysis_id']);
    $format = $input['format'];
    
    // Conectar ao banco
    $db = Database::getInstance();
    
    // Verificar se análise existe
    $analysis = $db->selectOne(
        "SELECT * FROM analyses WHERE id = ? AND status = 'completed'",
        [$analysisId]
    );
    
    if (!$analysis) {
        throw new Exception('Análise não encontrada ou não concluída');
    }
    
    // Buscar dados para exportação
    $exportData = prepareExportData($analysisId, $db);
    
    // Gerar arquivo baseado no formato
    switch ($format) {
        case 'csv':
            generateCSV($exportData, $analysis);
            break;
        case 'excel':
            generateExcel($exportData, $analysis);
            break;
        case 'pdf':
            generatePDF($exportData, $analysis);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Prepara dados para exportação
 */
function prepareExportData($analysisId, $db) {
    $data = [];
    
    // Informações da análise
    $data['analysis'] = $db->selectOne(
        "SELECT url, domain, max_pages, start_time, end_time, total_pages_analyzed FROM analyses WHERE id = ?",
        [$analysisId]
    );
    
    // Resumo das páginas
    $data['pages_summary'] = $db->select(
        "SELECT 
            status_code,
            COUNT(*) as count,
            AVG(load_time) as avg_load_time,
            AVG(page_size) as avg_page_size
         FROM pages 
         WHERE analysis_id = ? 
         GROUP BY status_code
         ORDER BY status_code",
        [$analysisId]
    );
    
    // Páginas detalhadas
    $data['pages'] = $db->select(
        "SELECT 
            p.url,
            p.status_code,
            p.load_time,
            p.page_size,
            pc.title,
            pc.title_length,
            pc.meta_description,
            pc.meta_description_length,
            pc.h1,
            pc.word_count,
            pc.internal_links_count,
            pc.external_links_count
         FROM pages p
         LEFT JOIN page_content pc ON p.id = pc.page_id
         WHERE p.analysis_id = ?
         ORDER BY p.load_time DESC",
        [$analysisId]
    );
    
    // Issues encontrados
    $data['issues'] = $db->select(
        "SELECT 
            i.category,
            i.type,
            i.severity,
            i.title,
            i.description,
            i.recommendation,
            p.url as page_url,
            i.impact_score,
            i.effort_score,
            i.priority_score
         FROM issues i
         LEFT JOIN pages p ON i.page_id = p.id
         WHERE i.analysis_id = ?
         ORDER BY i.priority_score DESC",
        [$analysisId]
    );
    
    // Resumo de issues por categoria
    $data['issues_summary'] = $db->select(
        "SELECT 
            category,
            severity,
            COUNT(*) as count
         FROM issues 
         WHERE analysis_id = ?
         GROUP BY category, severity
         ORDER BY category, severity",
        [$analysisId]
    );
    
    // Links quebrados
    $data['broken_links'] = $db->select(
        "SELECT 
            p1.url as source_url,
            l.target_url,
            l.anchor_text,
            p2.status_code as target_status
         FROM links l
         JOIN pages p1 ON l.source_page_id = p1.id
         LEFT JOIN pages p2 ON l.target_url = p2.url AND p2.analysis_id = p1.analysis_id
         WHERE p1.analysis_id = ? 
         AND l.link_type = 'internal'
         AND (p2.status_code >= 400 OR p2.status_code IS NULL)
         ORDER BY p1.url",
        [$analysisId]
    );
    
    // Imagens sem ALT
    $data['images_no_alt'] = $db->select(
        "SELECT 
            p.url as page_url,
            i.src as image_src,
            i.alt,
            i.title
         FROM images i
         JOIN pages p ON i.page_id = p.id
         WHERE p.analysis_id = ? AND (i.alt IS NULL OR i.alt = '')
         ORDER BY p.url",
        [$analysisId]
    );
    
    return $data;
}

/**
 * Gera exportação em CSV
 */
function generateCSV($data, $analysis) {
    $filename = 'analise_seo_' . $analysis['domain'] . '_' . date('Y-m-d') . '.csv';
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // BOM para UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Informações da análise
    fputcsv($output, ['RELATÓRIO DE ANÁLISE SEO'], ';');
    fputcsv($output, ['Site analisado:', $analysis['url']], ';');
    fputcsv($output, ['Data da análise:', date('d/m/Y H:i', strtotime($analysis['start_time']))], ';');
    fputcsv($output, ['Páginas analisadas:', $analysis['total_pages_analyzed']], ';');
    fputcsv($output, [''], ';');
    
    // Resumo de páginas por status
    fputcsv($output, ['RESUMO POR STATUS HTTP'], ';');
    fputcsv($output, ['Status', 'Quantidade', 'Tempo Médio (ms)', 'Tamanho Médio (bytes)'], ';');
    foreach ($data['pages_summary'] as $summary) {
        fputcsv($output, [
            $summary['status_code'],
            $summary['count'],
            round($summary['avg_load_time']),
            round($summary['avg_page_size'])
        ], ';');
    }
    fputcsv($output, [''], ';');
    
    // Issues por categoria
    fputcsv($output, ['PROBLEMAS ENCONTRADOS POR CATEGORIA'], ';');
    fputcsv($output, ['Categoria', 'Severidade', 'Quantidade'], ';');
    foreach ($data['issues_summary'] as $summary) {
        fputcsv($output, [
            ucfirst($summary['category']),
            ucfirst($summary['severity']),
            $summary['count']
        ], ';');
    }
    fputcsv($output, [''], ';');
    
    // Detalhes das páginas
    fputcsv($output, ['DETALHES DAS PÁGINAS ANALISADAS'], ';');
    fputcsv($output, [
        'URL', 'Status', 'Tempo (ms)', 'Tamanho (bytes)', 'Título', 
        'Comprimento Título', 'Meta Description', 'Comprimento Meta', 
        'H1', 'Palavras', 'Links Internos', 'Links Externos'
    ], ';');
    
    foreach ($data['pages'] as $page) {
        fputcsv($output, [
            $page['url'],
            $page['status_code'],
            $page['load_time'],
            $page['page_size'],
            $page['title'] ?? '',
            $page['title_length'] ?? 0,
            substr($page['meta_description'] ?? '', 0, 100),
            $page['meta_description_length'] ?? 0,
            $page['h1'] ?? '',
            $page['word_count'] ?? 0,
            $page['internal_links_count'] ?? 0,
            $page['external_links_count'] ?? 0
        ], ';');
    }
    fputcsv($output, [''], ';');
    
    // Issues detalhados
    fputcsv($output, ['PROBLEMAS DETALHADOS'], ';');
    fputcsv($output, [
        'Categoria', 'Tipo', 'Severidade', 'Título', 'Descrição', 
        'Recomendação', 'URL da Página', 'Score Impacto', 'Score Esforço', 'Prioridade'
    ], ';');
    
    foreach ($data['issues'] as $issue) {
        fputcsv($output, [
            ucfirst($issue['category']),
            $issue['type'],
            ucfirst($issue['severity']),
            $issue['title'],
            $issue['description'],
            $issue['recommendation'],
            $issue['page_url'] ?? 'Geral',
            $issue['impact_score'],
            $issue['effort_score'],
            round($issue['priority_score'], 2)
        ], ';');
    }
    
    // Links quebrados
    if (!empty($data['broken_links'])) {
        fputcsv($output, [''], ';');
        fputcsv($output, ['LINKS QUEBRADOS'], ';');
        fputcsv($output, ['Página de Origem', 'URL de Destino', 'Texto do Link', 'Status de Destino'], ';');
        
        foreach ($data['broken_links'] as $link) {
            fputcsv($output, [
                $link['source_url'],
                $link['target_url'],
                $link['anchor_text'],
                $link['target_status'] ?? 'Não encontrado'
            ], ';');
        }
    }
    
    // Imagens sem ALT
    if (!empty($data['images_no_alt'])) {
        fputcsv($output, [''], ';');
        fputcsv($output, ['IMAGENS SEM TEXTO ALTERNATIVO'], ';');
        fputcsv($output, ['Página', 'URL da Imagem', 'ALT', 'Title'], ';');
        
        foreach ($data['images_no_alt'] as $image) {
            fputcsv($output, [
                $image['page_url'],
                $image['image_src'],
                $image['alt'] ?? '',
                $image['title'] ?? ''
            ], ';');
        }
    }
    
    fclose($output);
}

/**
 * Gera exportação em Excel (HTML formatado)
 */
function generateExcel($data, $analysis) {
    $filename = 'analise_seo_' . $analysis['domain'] . '_' . date('Y-m-d') . '.xls';
    
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">';
    echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head>';
    echo '<body>';
    
    // Informações da análise
    echo '<table border="1">';
    echo '<tr><td colspan="4" style="background-color:#4472C4;color:white;font-weight:bold;text-align:center;font-size:14px;">RELATÓRIO DE ANÁLISE SEO</td></tr>';
    echo '<tr><td><b>Site analisado:</b></td><td colspan="3">' . htmlspecialchars($analysis['url']) . '</td></tr>';
    echo '<tr><td><b>Data da análise:</b></td><td>' . date('d/m/Y H:i', strtotime($analysis['start_time'])) . '</td>';
    echo '<td><b>Páginas analisadas:</b></td><td>' . $analysis['total_pages_analyzed'] . '</td></tr>';
    echo '<tr><td colspan="4"></td></tr>';
    echo '</table>';
    
    echo '<br>';
    
    // Resumo por status
    echo '<table border="1">';
    echo '<tr style="background-color:#70AD47;color:white;font-weight:bold;">';
    echo '<td colspan="4">RESUMO POR STATUS HTTP</td></tr>';
    echo '<tr style="background-color:#E2EFDA;font-weight:bold;">';
    echo '<td>Status</td><td>Quantidade</td><td>Tempo Médio (ms)</td><td>Tamanho Médio (bytes)</td></tr>';
    
    foreach ($data['pages_summary'] as $summary) {
        echo '<tr>';
        echo '<td>' . $summary['status_code'] . '</td>';
        echo '<td>' . $summary['count'] . '</td>';
        echo '<td>' . round($summary['avg_load_time']) . '</td>';
        echo '<td>' . round($summary['avg_page_size']) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    
    echo '<br>';
    
    // Issues por categoria
    echo '<table border="1">';
    echo '<tr style="background-color:#C5504B;color:white;font-weight:bold;">';
    echo '<td colspan="3">PROBLEMAS ENCONTRADOS</td></tr>';
    echo '<tr style="background-color:#F2DCDB;font-weight:bold;">';
    echo '<td>Categoria</td><td>Severidade</td><td>Quantidade</td></tr>';
    
    foreach ($data['issues_summary'] as $summary) {
        $color = getSeverityColor($summary['severity']);
        echo '<tr>';
        echo '<td>' . ucfirst($summary['category']) . '</td>';
        echo '<td style="background-color:' . $color . ';">' . ucfirst($summary['severity']) . '</td>';
        echo '<td>' . $summary['count'] . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    
    echo '<br>';
    
    // Top 10 issues mais críticos
    echo '<table border="1">';
    echo '<tr style="background-color:#FFC000;font-weight:bold;">';
    echo '<td colspan="4">TOP 10 PROBLEMAS PRIORITÁRIOS</td></tr>';
    echo '<tr style="background-color:#FFF2CC;font-weight:bold;">';
    echo '<td>Prioridade</td><td>Problema</td><td>Severidade</td><td>URL</td></tr>';
    
    $topIssues = array_slice($data['issues'], 0, 10);
    foreach ($topIssues as $issue) {
        $color = getSeverityColor($issue['severity']);
        echo '<tr>';
        echo '<td>' . round($issue['priority_score'], 1) . '</td>';
        echo '<td>' . htmlspecialchars($issue['title']) . '</td>';
        echo '<td style="background-color:' . $color . ';">' . ucfirst($issue['severity']) . '</td>';
        echo '<td>' . htmlspecialchars($issue['page_url'] ?? 'Geral') . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    
    echo '</body></html>';
}

/**
 * Gera exportação em PDF (HTML simples)
 */
function generatePDF($data, $analysis) {
    $filename = 'analise_seo_' . $analysis['domain'] . '_' . date('Y-m-d') . '.html';
    
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Relatório de Análise SEO - <?php echo htmlspecialchars($analysis['domain']); ?></title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
            .header { background-color: #4472C4; color: white; padding: 20px; text-align: center; margin-bottom: 20px; }
            .section { margin-bottom: 30px; }
            .section h2 { background-color: #E2EFDA; padding: 10px; margin: 0 0 15px 0; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; font-weight: bold; }
            .critical { background-color: #FFE6E6; }
            .high { background-color: #FFF0E6; }
            .medium { background-color: #FFFEE6; }
            .low { background-color: #E6F7FF; }
            .summary-card { display: inline-block; margin: 10px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
            .metric { font-size: 24px; font-weight: bold; color: #4472C4; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>Relatório de Análise SEO</h1>
            <p><?php echo htmlspecialchars($analysis['url']); ?></p>
            <p>Análise realizada em: <?php echo date('d/m/Y H:i', strtotime($analysis['start_time'])); ?></p>
        </div>
        
        <div class="section">
            <h2>Resumo Executivo</h2>
            <div class="summary-card">
                <div>Páginas Analisadas</div>
                <div class="metric"><?php echo $analysis['total_pages_analyzed']; ?></div>
            </div>
            <div class="summary-card">
                <div>Total de Problemas</div>
                <div class="metric"><?php echo count($data['issues']); ?></div>
            </div>
            <div class="summary-card">
                <div>Links Quebrados</div>
                <div class="metric"><?php echo count($data['broken_links']); ?></div>
            </div>
        </div>
        
        <div class="section">
            <h2>Status das Páginas</h2>
            <table>
                <thead>
                    <tr>
                        <th>Status HTTP</th>
                        <th>Quantidade</th>
                        <th>Tempo Médio (ms)</th>
                        <th>Tamanho Médio (KB)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['pages_summary'] as $summary): ?>
                    <tr>
                        <td><?php echo $summary['status_code']; ?></td>
                        <td><?php echo $summary['count']; ?></td>
                        <td><?php echo round($summary['avg_load_time']); ?></td>
                        <td><?php echo round($summary['avg_page_size'] / 1024, 1); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="section">
            <h2>Problemas por Severidade</h2>
            <table>
                <thead>
                    <tr>
                        <th>Categoria</th>
                        <th>Severidade</th>
                        <th>Quantidade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['issues_summary'] as $summary): ?>
                    <tr class="<?php echo $summary['severity']; ?>">
                        <td><?php echo ucfirst($summary['category']); ?></td>
                        <td><?php echo ucfirst($summary['severity']); ?></td>
                        <td><?php echo $summary['count']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="section">
            <h2>Principais Problemas (Top 15)</h2>
            <table>
                <thead>
                    <tr>
                        <th>Prioridade</th>
                        <th>Problema</th>
                        <th>Severidade</th>
                        <th>Recomendação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $topIssues = array_slice($data['issues'], 0, 15);
                    foreach ($topIssues as $issue): 
                    ?>
                    <tr class="<?php echo $issue['severity']; ?>">
                        <td><?php echo round($issue['priority_score'], 1); ?></td>
                        <td><?php echo htmlspecialchars($issue['title']); ?></td>
                        <td><?php echo ucfirst($issue['severity']); ?></td>
                        <td><?php echo htmlspecialchars(substr($issue['recommendation'], 0, 100)); ?>...</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if (!empty($data['broken_links'])): ?>
        <div class="section">
            <h2>Links Quebrados (Top 20)</h2>
            <table>
                <thead>
                    <tr>
                        <th>Página de Origem</th>
                        <th>Link Quebrado</th>
                        <th>Texto do Link</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $topBrokenLinks = array_slice($data['broken_links'], 0, 20);
                    foreach ($topBrokenLinks as $link): 
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($link['source_url']); ?></td>
                        <td><?php echo htmlspecialchars($link['target_url']); ?></td>
                        <td><?php echo htmlspecialchars($link['anchor_text'] ?? ''); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <div class="section">
            <h2>Páginas com Pior Performance (Top 20)</h2>
            <table>
                <thead>
                    <tr>
                        <th>URL</th>
                        <th>Status</th>
                        <th>Tempo (ms)</th>
                        <th>Tamanho (KB)</th>
                        <th>Título</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $slowPages = array_slice($data['pages'], 0, 20);
                    foreach ($slowPages as $page): 
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars(substr($page['url'], 0, 60)); ?>...</td>
                        <td><?php echo $page['status_code']; ?></td>
                        <td><?php echo $page['load_time']; ?></td>
                        <td><?php echo round($page['page_size'] / 1024, 1); ?></td>
                        <td><?php echo htmlspecialchars(substr($page['title'] ?? '', 0, 40)); ?>...</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div style="margin-top: 50px; text-align: center; color: #666; font-size: 12px;">
            <p>Relatório gerado por EscopoSEO em <?php echo date('d/m/Y H:i:s'); ?></p>
        </div>
    </body>
    </html>
    <?php
}

/**
 * Retorna cor baseada na severidade
 */
function getSeverityColor($severity) {
    $colors = [
        'critical' => '#FF6B6B',
        'high' => '#FFB347',
        'medium' => '#FFEB9C',
        'low' => '#B8E6B8'
    ];
    
    return $colors[$severity] ?? '#E6E6E6';
}
?>
