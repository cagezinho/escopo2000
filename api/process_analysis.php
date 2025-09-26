<?php
/**
 * Processamento da Análise de SEO
 * Script principal que executa o crawler e análise
 */

// Se executado via linha de comando
if (php_sapi_name() === 'cli' && isset($argv[1])) {
    $analysisId = intval($argv[1]);
    require_once __DIR__ . '/../config/database_shared_hosting.php';
    require_once __DIR__ . '/../classes/Crawler.php';
    require_once __DIR__ . '/../classes/SEOAnalyzer.php';
    
    processAnalysis($analysisId);
    exit;
}

/**
 * Processa análise de SEO
 */
function processAnalysis($analysisId) {
    try {
        $db = Database::getInstance();
        $helper = new DatabaseHelper();
        
        // Buscar dados da análise
        $analysis = $helper->getAnalysis($analysisId);
        if (!$analysis) {
            throw new Exception("Análise não encontrada: $analysisId");
        }
        
        // Atualizar status para 'running'
        $helper->updateAnalysisStatus($analysisId, 'running', 0);
        $helper->addLog($analysisId, 'start', 'Iniciando processo de análise');
        
        // Inicializar crawler
        $crawler = new Crawler($analysisId, [
            'max_pages' => $analysis['max_pages'],
            'respect_robots' => $analysis['respect_robots'],
            'include_external' => $analysis['include_external']
        ]);
        
        // Executar crawler
        $helper->addLog($analysisId, 'crawl_start', 'Iniciando rastreamento do site');
        $crawlResults = $crawler->crawl($analysis['url']);
        
        // Atualizar progresso
        $helper->updateAnalysisStatus($analysisId, 'running', 50);
        $helper->addLog($analysisId, 'crawl_complete', "Rastreamento concluído: {$crawlResults['pages_found']} páginas encontradas");
        
        // Inicializar analisador de SEO
        $analyzer = new SEOAnalyzer($analysisId);
        
        // Executar análises
        $helper->addLog($analysisId, 'analysis_start', 'Iniciando análise de SEO');
        
        // Análise técnica
        $helper->updateAnalysisStatus($analysisId, 'running', 60);
        $analyzer->performTechnicalAnalysis();
        $helper->addLog($analysisId, 'technical_complete', 'Análise técnica concluída');
        
        // Análise de conteúdo
        $helper->updateAnalysisStatus($analysisId, 'running', 75);
        $analyzer->performContentAnalysis();
        $helper->addLog($analysisId, 'content_complete', 'Análise de conteúdo concluída');
        
        // Análise para IA/SGE
        $helper->updateAnalysisStatus($analysisId, 'running', 90);
        $analyzer->performAIAnalysis();
        $helper->addLog($analysisId, 'ai_complete', 'Análise para IA concluída');
        
        // Gerar relatórios finais
        $analyzer->generateReports();
        $helper->addLog($analysisId, 'reports_complete', 'Relatórios gerados');
        
        // Finalizar análise
        $helper->updateAnalysisStatus($analysisId, 'completed', 100);
        $helper->addLog($analysisId, 'complete', 'Análise concluída com sucesso');
        
        // Atualizar contadores finais
        $stats = $helper->getAnalysisStats($analysisId);
        $db->update(
            "UPDATE analyses SET total_pages_found = ?, total_pages_analyzed = ? WHERE id = ?",
            [$stats['total_pages'], $stats['total_pages'], $analysisId]
        );
        
    } catch (Exception $e) {
        // Em caso de erro, atualizar status
        $helper->updateAnalysisStatus($analysisId, 'failed');
        $helper->addLog($analysisId, 'error', 'Erro durante análise: ' . $e->getMessage(), 'error');
        
        error_log("Erro na análise $analysisId: " . $e->getMessage());
    }
}
?>
