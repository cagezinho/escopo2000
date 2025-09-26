<?php
/**
 * Classe SEOAnalyzer - Análise de SEO Técnico, Conteúdo e IA
 */

class SEOAnalyzer {
    private $analysisId;
    private $db;
    private $helper;
    
    public function __construct($analysisId) {
        $this->analysisId = $analysisId;
        $this->db = Database::getInstance();
        $this->helper = new DatabaseHelper();
    }
    
    /**
     * Executa análise técnica de SEO
     */
    public function performTechnicalAnalysis() {
        $this->helper->addLog($this->analysisId, 'technical_start', 'Iniciando análise técnica');
        
        // Analisar títulos
        $this->analyzeTitles();
        
        // Analisar meta descriptions
        $this->analyzeMetaDescriptions();
        
        // Analisar códigos de status
        $this->analyzeStatusCodes();
        
        // Analisar performance
        $this->analyzePerformance();
        
        // Analisar links quebrados
        $this->analyzeBrokenLinks();
        
        // Analisar canonicals
        $this->analyzeCanonicals();
        
        // Analisar robots
        $this->analyzeRobotsCompliance();
        
        // Analisar sitemap
        $this->analyzeSitemapCompliance();
        
        $this->helper->addLog($this->analysisId, 'technical_end', 'Análise técnica concluída');
    }
    
    /**
     * Executa análise de conteúdo
     */
    public function performContentAnalysis() {
        $this->helper->addLog($this->analysisId, 'content_start', 'Iniciando análise de conteúdo');
        
        // Analisar estrutura de headings
        $this->analyzeHeadingStructure();
        
        // Analisar conteúdo insuficiente
        $this->analyzeContentLength();
        
        // Analisar títulos genéricos
        $this->analyzeGenericTitles();
        
        // Analisar páginas órfãs
        $this->analyzeOrphanPages();
        
        // Analisar potencial para featured snippets
        $this->analyzeFeaturedSnippetPotential();
        
        // Analisar densidade de palavras-chave
        $this->analyzeKeywordDensity();
        
        // Analisar imagens
        $this->analyzeImages();
        
        $this->helper->addLog($this->analysisId, 'content_end', 'Análise de conteúdo concluída');
    }
    
    /**
     * Executa análise para IA e SGE
     */
    public function performAIAnalysis() {
        $this->helper->addLog($this->analysisId, 'ai_start', 'Iniciando análise para IA');
        
        // Analisar conteúdo Q&A
        $this->analyzeQAContent();
        
        // Analisar dados estruturados
        $this->analyzeStructuredData();
        
        // Analisar E-E-A-T
        $this->analyzeEEAT();
        
        // Analisar potencial para IA
        $this->analyzeAIPotential();
        
        $this->helper->addLog($this->analysisId, 'ai_end', 'Análise para IA concluída');
    }
    
    /**
     * Gera relatórios finais
     */
    public function generateReports() {
        $this->helper->addLog($this->analysisId, 'reports_start', 'Gerando relatórios');
        
        // Realizar clusterização de conteúdo
        $this->performContentClustering();
        
        // Calcular scores de prioridade para issues
        $this->calculateIssuePriorities();
        
        $this->helper->addLog($this->analysisId, 'reports_end', 'Relatórios gerados');
    }
    
    // ANÁLISES TÉCNICAS
    
    private function analyzeTitles() {
        // Encontrar títulos duplicados
        $duplicates = $this->db->select(
            "SELECT title, COUNT(*) as count 
             FROM page_content pc 
             JOIN pages p ON pc.page_id = p.id 
             WHERE p.analysis_id = ? AND pc.title IS NOT NULL AND pc.title != ''
             GROUP BY title 
             HAVING count > 1",
            [$this->analysisId]
        );
        
        foreach ($duplicates as $duplicate) {
            $this->createIssue(
                'technical',
                'duplicate_titles',
                'high',
                'Títulos Duplicados',
                "O título '{$duplicate['title']}' aparece em {$duplicate['count']} páginas",
                'Cada página deve ter um título único e descritivo',
                ['title' => $duplicate['title'], 'count' => $duplicate['count']]
            );
        }
        
        // Encontrar títulos muito curtos ou longos
        $titleIssues = $this->db->select(
            "SELECT p.url, pc.title, pc.title_length 
             FROM page_content pc 
             JOIN pages p ON pc.page_id = p.id 
             WHERE p.analysis_id = ? AND (pc.title_length < 30 OR pc.title_length > 60)",
            [$this->analysisId]
        );
        
        foreach ($titleIssues as $issue) {
            $severity = $issue['title_length'] < 20 || $issue['title_length'] > 70 ? 'high' : 'medium';
            $problem = $issue['title_length'] < 30 ? 'muito curto' : 'muito longo';
            
            $this->createIssue(
                'technical',
                'title_length',
                $severity,
                "Título $problem",
                "Página {$issue['url']} tem título com {$issue['title_length']} caracteres",
                'Títulos devem ter entre 30-60 caracteres para melhor exibição nos resultados de busca',
                ['url' => $issue['url'], 'title_length' => $issue['title_length']],
                $this->getPageId($issue['url'])
            );
        }
        
        // Páginas sem título
        $noTitle = $this->db->select(
            "SELECT p.url 
             FROM pages p 
             LEFT JOIN page_content pc ON p.id = pc.page_id 
             WHERE p.analysis_id = ? AND p.status_code = 200 AND (pc.title IS NULL OR pc.title = '')",
            [$this->analysisId]
        );
        
        foreach ($noTitle as $page) {
            $this->createIssue(
                'technical',
                'missing_title',
                'critical',
                'Título Ausente',
                "Página {$page['url']} não possui título",
                'Adicionar elemento <title> com descrição única da página',
                ['url' => $page['url']],
                $this->getPageId($page['url'])
            );
        }
    }
    
    private function analyzeMetaDescriptions() {
        // Meta descriptions ausentes
        $missingMeta = $this->db->select(
            "SELECT p.url 
             FROM pages p 
             LEFT JOIN page_content pc ON p.id = pc.page_id 
             WHERE p.analysis_id = ? AND p.status_code = 200 AND (pc.meta_description IS NULL OR pc.meta_description = '')",
            [$this->analysisId]
        );
        
        foreach ($missingMeta as $page) {
            $this->createIssue(
                'technical',
                'missing_meta_description',
                'medium',
                'Meta Description Ausente',
                "Página {$page['url']} não possui meta description",
                'Adicionar meta description entre 150-160 caracteres',
                ['url' => $page['url']],
                $this->getPageId($page['url'])
            );
        }
        
        // Meta descriptions duplicadas
        $duplicates = $this->db->select(
            "SELECT meta_description, COUNT(*) as count 
             FROM page_content pc 
             JOIN pages p ON pc.page_id = p.id 
             WHERE p.analysis_id = ? AND pc.meta_description IS NOT NULL AND pc.meta_description != ''
             GROUP BY meta_description 
             HAVING count > 1",
            [$this->analysisId]
        );
        
        foreach ($duplicates as $duplicate) {
            $this->createIssue(
                'technical',
                'duplicate_meta_descriptions',
                'medium',
                'Meta Descriptions Duplicadas',
                "Meta description aparece em {$duplicate['count']} páginas",
                'Cada página deve ter uma meta description única',
                ['meta_description' => substr($duplicate['meta_description'], 0, 100), 'count' => $duplicate['count']]
            );
        }
        
        // Meta descriptions muito curtas ou longas
        $lengthIssues = $this->db->select(
            "SELECT p.url, pc.meta_description_length 
             FROM page_content pc 
             JOIN pages p ON pc.page_id = p.id 
             WHERE p.analysis_id = ? AND pc.meta_description IS NOT NULL 
             AND (pc.meta_description_length < 120 OR pc.meta_description_length > 160)",
            [$this->analysisId]
        );
        
        foreach ($lengthIssues as $issue) {
            $problem = $issue['meta_description_length'] < 120 ? 'muito curta' : 'muito longa';
            $severity = $issue['meta_description_length'] < 50 || $issue['meta_description_length'] > 200 ? 'medium' : 'low';
            
            $this->createIssue(
                'technical',
                'meta_description_length',
                $severity,
                "Meta Description $problem",
                "Página {$issue['url']} tem meta description com {$issue['meta_description_length']} caracteres",
                'Meta descriptions devem ter entre 120-160 caracteres',
                ['url' => $issue['url'], 'length' => $issue['meta_description_length']],
                $this->getPageId($issue['url'])
            );
        }
    }
    
    private function analyzeStatusCodes() {
        // Páginas com erro 404
        $notFound = $this->db->select(
            "SELECT url FROM pages WHERE analysis_id = ? AND status_code = 404",
            [$this->analysisId]
        );
        
        foreach ($notFound as $page) {
            $this->createIssue(
                'technical',
                'page_404',
                'high',
                'Página Não Encontrada (404)',
                "Página {$page['url']} retorna erro 404",
                'Corrigir link ou implementar redirecionamento 301',
                ['url' => $page['url']],
                $this->getPageId($page['url'])
            );
        }
        
        // Páginas com erro 500
        $serverError = $this->db->select(
            "SELECT url FROM pages WHERE analysis_id = ? AND status_code >= 500",
            [$this->analysisId]
        );
        
        foreach ($serverError as $page) {
            $this->createIssue(
                'technical',
                'server_error',
                'critical',
                'Erro do Servidor',
                "Página {$page['url']} retorna erro de servidor",
                'Verificar e corrigir erro no servidor',
                ['url' => $page['url']],
                $this->getPageId($page['url'])
            );
        }
        
        // Muitos redirecionamentos
        $redirects = $this->db->select(
            "SELECT url, redirect_url FROM pages 
             WHERE analysis_id = ? AND status_code IN (301, 302, 307, 308) AND redirect_url IS NOT NULL",
            [$this->analysisId]
        );
        
        foreach ($redirects as $redirect) {
            $this->createIssue(
                'technical',
                'redirect_chain',
                'low',
                'Redirecionamento',
                "Página {$redirect['url']} redireciona para {$redirect['redirect_url']}",
                'Verificar se o redirecionamento é necessário e se não há cadeias de redirecionamento',
                ['url' => $redirect['url'], 'redirect_url' => $redirect['redirect_url']],
                $this->getPageId($redirect['url'])
            );
        }
    }
    
    private function analyzePerformance() {
        // Páginas lentas (>3 segundos)
        $slowPages = $this->db->select(
            "SELECT url, load_time FROM pages 
             WHERE analysis_id = ? AND load_time > 3000 AND status_code = 200",
            [$this->analysisId]
        );
        
        foreach ($slowPages as $page) {
            $severity = $page['load_time'] > 5000 ? 'high' : 'medium';
            
            $this->createIssue(
                'performance',
                'slow_page',
                $severity,
                'Página Lenta',
                "Página {$page['url']} carrega em {$page['load_time']}ms",
                'Otimizar imagens, minificar CSS/JS, usar cache e CDN',
                ['url' => $page['url'], 'load_time' => $page['load_time']],
                $this->getPageId($page['url'])
            );
        }
        
        // Páginas muito pesadas (>1MB)
        $heavyPages = $this->db->select(
            "SELECT url, page_size FROM pages 
             WHERE analysis_id = ? AND page_size > 1048576 AND status_code = 200",
            [$this->analysisId]
        );
        
        foreach ($heavyPages as $page) {
            $sizeMB = round($page['page_size'] / 1048576, 2);
            $severity = $page['page_size'] > 2097152 ? 'medium' : 'low';
            
            $this->createIssue(
                'performance',
                'heavy_page',
                $severity,
                'Página Pesada',
                "Página {$page['url']} tem {$sizeMB}MB",
                'Otimizar imagens, compactar código e remover recursos desnecessários',
                ['url' => $page['url'], 'size_mb' => $sizeMB],
                $this->getPageId($page['url'])
            );
        }
    }
    
    private function analyzeBrokenLinks() {
        // Links internos quebrados
        $brokenInternal = $this->db->select(
            "SELECT l.source_page_id, l.target_url, p1.url as source_url
             FROM links l
             JOIN pages p1 ON l.source_page_id = p1.id
             LEFT JOIN pages p2 ON l.target_url = p2.url AND p2.analysis_id = p1.analysis_id
             WHERE p1.analysis_id = ? AND l.link_type = 'internal' 
             AND (p2.id IS NULL OR p2.status_code >= 400)",
            [$this->analysisId]
        );
        
        foreach ($brokenInternal as $link) {
            $this->createIssue(
                'technical',
                'broken_internal_link',
                'medium',
                'Link Interno Quebrado',
                "Link de {$link['source_url']} para {$link['target_url']} está quebrado",
                'Corrigir URL ou remover link',
                ['source_url' => $link['source_url'], 'target_url' => $link['target_url']],
                $link['source_page_id']
            );
        }
    }
    
    private function analyzeCanonicals() {
        // Páginas sem canonical (quando necessário)
        $missingCanonical = $this->db->select(
            "SELECT p.url FROM pages p
             LEFT JOIN page_content pc ON p.id = pc.page_id
             WHERE p.analysis_id = ? AND p.status_code = 200 
             AND p.canonical_url IS NULL
             AND p.url LIKE '%?%'", // URLs com parâmetros
            [$this->analysisId]
        );
        
        foreach ($missingCanonical as $page) {
            $this->createIssue(
                'technical',
                'missing_canonical',
                'low',
                'Canonical Ausente',
                "Página {$page['url']} com parâmetros não possui canonical",
                'Adicionar tag canonical para evitar conteúdo duplicado',
                ['url' => $page['url']],
                $this->getPageId($page['url'])
            );
        }
    }
    
    // ANÁLISES DE CONTEÚDO
    
    private function analyzeHeadingStructure() {
        // Páginas sem H1
        $noH1 = $this->db->select(
            "SELECT p.url FROM pages p
             JOIN page_content pc ON p.id = pc.page_id
             WHERE p.analysis_id = ? AND p.status_code = 200 
             AND (pc.h1 IS NULL OR pc.h1 = '' OR pc.h1_count = 0)",
            [$this->analysisId]
        );
        
        foreach ($noH1 as $page) {
            $this->createIssue(
                'content',
                'missing_h1',
                'high',
                'H1 Ausente',
                "Página {$page['url']} não possui H1",
                'Adicionar elemento H1 descritivo e único',
                ['url' => $page['url']],
                $this->getPageId($page['url'])
            );
        }
        
        // Múltiplos H1
        $multipleH1 = $this->db->select(
            "SELECT p.url, pc.h1_count FROM pages p
             JOIN page_content pc ON p.id = pc.page_id
             WHERE p.analysis_id = ? AND p.status_code = 200 AND pc.h1_count > 1",
            [$this->analysisId]
        );
        
        foreach ($multipleH1 as $page) {
            $this->createIssue(
                'content',
                'multiple_h1',
                'medium',
                'Múltiplos H1',
                "Página {$page['url']} possui {$page['h1_count']} elementos H1",
                'Usar apenas um H1 por página',
                ['url' => $page['url'], 'h1_count' => $page['h1_count']],
                $this->getPageId($page['url'])
            );
        }
    }
    
    private function analyzeContentLength() {
        // Conteúdo insuficiente (<300 palavras)
        $shortContent = $this->db->select(
            "SELECT p.url, pc.word_count FROM pages p
             JOIN page_content pc ON p.id = pc.page_id
             WHERE p.analysis_id = ? AND p.status_code = 200 AND pc.word_count < 300",
            [$this->analysisId]
        );
        
        foreach ($shortContent as $page) {
            $severity = $page['word_count'] < 100 ? 'high' : 'medium';
            
            $this->createIssue(
                'content',
                'short_content',
                $severity,
                'Conteúdo Insuficiente',
                "Página {$page['url']} tem apenas {$page['word_count']} palavras",
                'Expandir conteúdo com informações relevantes e úteis',
                ['url' => $page['url'], 'word_count' => $page['word_count']],
                $this->getPageId($page['url'])
            );
        }
    }
    
    private function analyzeGenericTitles() {
        // Títulos genéricos
        $genericPatterns = [
            'Home', 'Início', 'Principal', 'Página Inicial',
            'Sobre', 'About', 'Contato', 'Contact',
            'Produto', 'Serviço', 'Categoria'
        ];
        
        foreach ($genericPatterns as $pattern) {
            $generic = $this->db->select(
                "SELECT p.url, pc.title FROM pages p
                 JOIN page_content pc ON p.id = pc.page_id
                 WHERE p.analysis_id = ? AND p.status_code = 200 
                 AND pc.title LIKE ?",
                [$this->analysisId, "%$pattern%"]
            );
            
            foreach ($generic as $page) {
                $this->createIssue(
                    'content',
                    'generic_title',
                    'medium',
                    'Título Genérico',
                    "Página {$page['url']} tem título genérico: {$page['title']}",
                    'Criar título mais específico e descritivo',
                    ['url' => $page['url'], 'title' => $page['title']],
                    $this->getPageId($page['url'])
                );
            }
        }
    }
    
    private function analyzeOrphanPages() {
        // Páginas órfãs (sem links internos)
        $orphans = $this->db->select(
            "SELECT p.url FROM pages p
             WHERE p.analysis_id = ? AND p.status_code = 200
             AND p.id NOT IN (
                 SELECT DISTINCT l.target_page_id 
                 FROM links l 
                 JOIN pages p2 ON l.source_page_id = p2.id
                 WHERE p2.analysis_id = ? AND l.target_page_id IS NOT NULL
             )",
            [$this->analysisId, $this->analysisId]
        );
        
        foreach ($orphans as $page) {
            $this->createIssue(
                'content',
                'orphan_page',
                'medium',
                'Página Órfã',
                "Página {$page['url']} não possui links internos apontando para ela",
                'Adicionar links internos relevantes para esta página',
                ['url' => $page['url']],
                $this->getPageId($page['url'])
            );
        }
    }
    
    private function analyzeImages() {
        // Imagens sem ALT
        $imagesNoAlt = $this->db->select(
            "SELECT p.url, COUNT(*) as images_without_alt FROM pages p
             JOIN images i ON p.id = i.page_id
             WHERE p.analysis_id = ? AND (i.alt IS NULL OR i.alt = '')
             GROUP BY p.id, p.url",
            [$this->analysisId]
        );
        
        foreach ($imagesNoAlt as $page) {
            $this->createIssue(
                'accessibility',
                'images_without_alt',
                'medium',
                'Imagens sem ALT',
                "Página {$page['url']} tem {$page['images_without_alt']} imagens sem texto alternativo",
                'Adicionar atributo alt descritivo para todas as imagens',
                ['url' => $page['url'], 'count' => $page['images_without_alt']],
                $this->getPageId($page['url'])
            );
        }
    }
    
    // ANÁLISES PARA IA
    
    private function analyzeQAContent() {
        // Detectar conteúdo FAQ
        $faqPages = $this->db->select(
            "SELECT p.url, pc.title, pc.h1 FROM pages p
             JOIN page_content pc ON p.id = pc.page_id
             WHERE p.analysis_id = ? AND p.status_code = 200
             AND (pc.title LIKE '%FAQ%' OR pc.title LIKE '%pergunt%' 
                  OR pc.h1 LIKE '%FAQ%' OR pc.h1 LIKE '%pergunt%')",
            [$this->analysisId]
        );
        
        // Criar oportunidades para FAQ schema
        foreach ($faqPages as $page) {
            $this->createIssue(
                'ai',
                'faq_schema_opportunity',
                'medium',
                'Oportunidade FAQ Schema',
                "Página {$page['url']} possui conteúdo FAQ sem dados estruturados",
                'Implementar schema.org FAQPage para melhor visibilidade em IA',
                ['url' => $page['url']],
                $this->getPageId($page['url'])
            );
        }
    }
    
    private function analyzeStructuredData() {
        // Páginas sem dados estruturados
        $noSchema = $this->db->select(
            "SELECT p.url FROM pages p
             WHERE p.analysis_id = ? AND p.status_code = 200
             AND p.id NOT IN (
                 SELECT DISTINCT page_id FROM structured_data WHERE page_id = p.id
             )",
            [$this->analysisId]
        );
        
        foreach ($noSchema as $page) {
            $this->createIssue(
                'ai',
                'missing_structured_data',
                'low',
                'Dados Estruturados Ausentes',
                "Página {$page['url']} não possui dados estruturados",
                'Implementar schema.org apropriado (Article, Product, etc.)',
                ['url' => $page['url']],
                $this->getPageId($page['url'])
            );
        }
    }
    
    private function analyzeEEAT() {
        // Analisar indicadores E-E-A-T para cada página
        $pages = $this->db->select(
            "SELECT p.id, p.url FROM pages p
             WHERE p.analysis_id = ? AND p.status_code = 200",
            [$this->analysisId]
        );
        
        foreach ($pages as $page) {
            $eatScore = $this->calculateEEATScore($page['id']);
            
            // Salvar análise E-E-A-T
            $this->db->insert(
                "INSERT INTO eat_analysis (page_id, experience_score, expertise_score, 
                                         authoritativeness_score, trustworthiness_score, total_score)
                 VALUES (?, ?, ?, ?, ?, ?)",
                [
                    $page['id'],
                    $eatScore['experience'],
                    $eatScore['expertise'],
                    $eatScore['authoritativeness'],
                    $eatScore['trustworthiness'],
                    $eatScore['total']
                ]
            );
            
            // Criar issues para scores baixos
            if ($eatScore['total'] < 50) {
                $this->createIssue(
                    'ai',
                    'low_eat_score',
                    'high',
                    'Score E-E-A-T Baixo',
                    "Página {$page['url']} tem score E-E-A-T de {$eatScore['total']}/100",
                    'Melhorar indicadores de experiência, expertise, autoridade e confiabilidade',
                    ['url' => $page['url'], 'eat_score' => $eatScore['total']],
                    $page['id']
                );
            }
        }
    }
    
    // MÉTODOS AUXILIARES
    
    private function createIssue($category, $type, $severity, $title, $description, $recommendation, $data = null, $pageId = null) {
        // Calcular scores de impacto e esforço
        $impactScore = $this->calculateImpactScore($category, $type, $severity);
        $effortScore = $this->calculateEffortScore($type);
        $priorityScore = ($impactScore * 0.7) + (100 - $effortScore) * 0.3;
        
        $this->db->insert(
            "INSERT INTO issues (analysis_id, page_id, category, type, severity, title, description, 
                               recommendation, impact_score, effort_score, priority_score, data)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $this->analysisId,
                $pageId,
                $category,
                $type,
                $severity,
                $title,
                $description,
                $recommendation,
                $impactScore,
                $effortScore,
                $priorityScore,
                $data ? json_encode($data) : null
            ]
        );
    }
    
    private function calculateImpactScore($category, $type, $severity) {
        $baseScores = [
            'critical' => 90,
            'high' => 75,
            'medium' => 50,
            'low' => 25
        ];
        
        $categoryMultipliers = [
            'technical' => 1.0,
            'content' => 0.8,
            'ai' => 0.6,
            'performance' => 0.9,
            'accessibility' => 0.7
        ];
        
        return round($baseScores[$severity] * ($categoryMultipliers[$category] ?? 1.0));
    }
    
    private function calculateEffortScore($type) {
        $effortMap = [
            'missing_title' => 20,
            'missing_meta_description' => 25,
            'missing_h1' => 20,
            'duplicate_titles' => 40,
            'broken_internal_link' => 30,
            'slow_page' => 70,
            'heavy_page' => 60,
            'missing_structured_data' => 50,
            'low_eat_score' => 80,
            'short_content' => 60
        ];
        
        return $effortMap[$type] ?? 50;
    }
    
    private function calculateEEATScore($pageId) {
        // Implementação simplificada do cálculo E-E-A-T
        $experience = 10; // Base score
        $expertise = 10;
        $authoritativeness = 10;
        $trustworthiness = 10;
        
        // Verificar indicadores específicos
        $pageData = $this->db->selectOne(
            "SELECT pc.*, p.url FROM page_content pc 
             JOIN pages p ON pc.page_id = p.id 
             WHERE pc.page_id = ?",
            [$pageId]
        );
        
        if ($pageData) {
            // Experience: baseado no conteúdo detalhado
            if ($pageData['word_count'] > 500) $experience += 5;
            if ($pageData['word_count'] > 1000) $experience += 5;
            
            // Expertise: baseado na estrutura de headings
            if ($pageData['h2_count'] > 0) $expertise += 3;
            if ($pageData['h3_count'] > 0) $expertise += 2;
            
            // Authoritativeness: baseado em links externos
            if ($pageData['external_links_count'] > 0) $authoritativeness += 5;
            if ($pageData['external_links_count'] > 3) $authoritativeness += 5;
            
            // Trustworthiness: baseado em elementos técnicos
            if (!empty($pageData['title'])) $trustworthiness += 3;
            if (!empty($pageData['meta_description'])) $trustworthiness += 2;
        }
        
        return [
            'experience' => min(25, $experience),
            'expertise' => min(25, $expertise),
            'authoritativeness' => min(25, $authoritativeness),
            'trustworthiness' => min(25, $trustworthiness),
            'total' => min(100, $experience + $expertise + $authoritativeness + $trustworthiness)
        ];
    }
    
    private function getPageId($url) {
        $result = $this->db->selectOne(
            "SELECT id FROM pages WHERE analysis_id = ? AND url = ?",
            [$this->analysisId, $url]
        );
        
        return $result ? $result['id'] : null;
    }
    
    private function performContentClustering() {
        // Implementação simplificada de clusterização
        $pages = $this->db->select(
            "SELECT p.id, p.url, pc.title, pc.h1, pc.main_keywords 
             FROM pages p 
             JOIN page_content pc ON p.id = pc.page_id 
             WHERE p.analysis_id = ? AND p.status_code = 200",
            [$this->analysisId]
        );
        
        $clusters = $this->groupPagesByTopic($pages);
        
        foreach ($clusters as $clusterName => $clusterData) {
            $clusterId = $this->db->insert(
                "INSERT INTO content_clusters (analysis_id, cluster_name, main_topic, page_count, opportunity_level)
                 VALUES (?, ?, ?, ?, ?)",
                [
                    $this->analysisId,
                    $clusterName,
                    $clusterData['topic'],
                    count($clusterData['pages']),
                    $this->calculateOpportunityLevel(count($clusterData['pages']))
                ]
            );
            
            foreach ($clusterData['pages'] as $page) {
                $this->db->insert(
                    "INSERT INTO page_clusters (page_id, cluster_id, relevance_score, is_primary)
                     VALUES (?, ?, ?, ?)",
                    [$page['id'], $clusterId, 0.8, 1]
                );
            }
        }
    }
    
    private function groupPagesByTopic($pages) {
        // Agrupamento simples baseado em palavras-chave do título
        $clusters = [];
        
        foreach ($pages as $page) {
            $title = strtolower($page['title'] ?? '');
            $words = array_filter(explode(' ', $title), function($word) {
                return strlen($word) > 3;
            });
            
            if (empty($words)) continue;
            
            $mainWord = $words[0];
            
            if (!isset($clusters[$mainWord])) {
                $clusters[$mainWord] = [
                    'topic' => ucfirst($mainWord),
                    'pages' => []
                ];
            }
            
            $clusters[$mainWord]['pages'][] = $page;
        }
        
        return array_filter($clusters, function($cluster) {
            return count($cluster['pages']) > 1;
        });
    }
    
    private function calculateOpportunityLevel($pageCount) {
        if ($pageCount >= 10) return 'high';
        if ($pageCount >= 5) return 'medium';
        return 'low';
    }
    
    private function calculateIssuePriorities() {
        // Recalcular prioridades baseado no contexto global
        $this->db->execute(
            "UPDATE issues 
             SET priority_score = (impact_score * 0.7) + ((100 - effort_score) * 0.3)
             WHERE analysis_id = ?",
            [$this->analysisId]
        );
    }
    
    private function analyzeFeaturedSnippetPotential() {
        // Detectar potencial para featured snippets
        $listPages = $this->db->select(
            "SELECT p.url FROM pages p
             JOIN page_content pc ON p.id = pc.page_id
             WHERE p.analysis_id = ? AND p.status_code = 200
             AND (pc.title LIKE '%como%' OR pc.title LIKE '%what%' 
                  OR pc.title LIKE '%melhores%' OR pc.title LIKE '%top%')",
            [$this->analysisId]
        );
        
        foreach ($listPages as $page) {
            $this->createIssue(
                'content',
                'featured_snippet_opportunity',
                'medium',
                'Oportunidade Featured Snippet',
                "Página {$page['url']} tem potencial para featured snippet",
                'Estruturar conteúdo em listas, tabelas ou respostas diretas',
                ['url' => $page['url']],
                $this->getPageId($page['url'])
            );
        }
    }
    
    private function analyzeKeywordDensity() {
        // Analisar densidade excessiva de palavras-chave
        $pages = $this->db->select(
            "SELECT p.url, pc.keyword_density FROM pages p
             JOIN page_content pc ON p.id = pc.page_id
             WHERE p.analysis_id = ? AND p.status_code = 200
             AND pc.keyword_density IS NOT NULL",
            [$this->analysisId]
        );
        
        foreach ($pages as $page) {
            $density = json_decode($page['keyword_density'], true);
            
            if (!$density) continue;
            
            foreach ($density as $keyword => $percent) {
                if ($percent > 3.0) { // Densidade muito alta
                    $this->createIssue(
                        'content',
                        'keyword_stuffing',
                        'medium',
                        'Possível Keyword Stuffing',
                        "Página {$page['url']} tem densidade de {$percent}% para '{$keyword}'",
                        'Reduzir densidade da palavra-chave e variar o uso de sinônimos',
                        ['url' => $page['url'], 'keyword' => $keyword, 'density' => $percent],
                        $this->getPageId($page['url'])
                    );
                }
            }
        }
    }
    
    private function analyzeRobotsCompliance() {
        // Verificar páginas bloqueadas em robots.txt mas linkadas internamente
        $blockedButLinked = $this->db->select(
            "SELECT DISTINCT l.target_url 
             FROM links l 
             JOIN pages p ON l.source_page_id = p.id 
             WHERE p.analysis_id = ? AND l.link_type = 'internal'
             AND l.target_url IN (
                 SELECT url FROM pages 
                 WHERE analysis_id = ? AND robots_meta LIKE '%noindex%'
             )",
            [$this->analysisId, $this->analysisId]
        );
        
        foreach ($blockedButLinked as $link) {
            $this->createIssue(
                'technical',
                'blocked_but_linked',
                'medium',
                'Página Bloqueada mas Linkada',
                "Página {$link['target_url']} está bloqueada mas recebe links internos",
                'Remover links para páginas noindex ou permitir indexação',
                ['url' => $link['target_url']]
            );
        }
    }
    
    private function analyzeSitemapCompliance() {
        // Verificar páginas no sitemap que retornam erro
        $sitemapErrors = $this->db->select(
            "SELECT sa.urls FROM sitemap_analysis sa 
             WHERE sa.analysis_id = ? AND sa.urls IS NOT NULL",
            [$this->analysisId]
        );
        
        foreach ($sitemapErrors as $sitemap) {
            $urls = json_decode($sitemap['urls'], true);
            
            if (!$urls) continue;
            
            foreach ($urls as $urlData) {
                $url = $urlData['loc'];
                
                // Verificar se a URL existe nas páginas rastreadas
                $pageExists = $this->db->selectOne(
                    "SELECT status_code FROM pages WHERE analysis_id = ? AND url = ?",
                    [$this->analysisId, $url]
                );
                
                if (!$pageExists || $pageExists['status_code'] >= 400) {
                    $this->createIssue(
                        'technical',
                        'sitemap_error_url',
                        'medium',
                        'URL com Erro no Sitemap',
                        "URL {$url} está no sitemap mas retorna erro ou não foi encontrada",
                        'Corrigir URL ou remover do sitemap',
                        ['url' => $url]
                    );
                }
            }
        }
    }
    
    private function analyzeAIPotential() {
        // Analisar potencial para ranqueamento em IA
        $pages = $this->db->select(
            "SELECT p.url, pc.title, pc.h1, pc.word_count FROM pages p
             JOIN page_content pc ON p.id = pc.page_id
             WHERE p.analysis_id = ? AND p.status_code = 200",
            [$this->analysisId]
        );
        
        foreach ($pages as $page) {
            $aiPotential = $this->calculateAIPotential($page);
            
            if ($aiPotential['score'] > 70) {
                $this->createIssue(
                    'ai',
                    'high_ai_potential',
                    'low',
                    'Alto Potencial para IA',
                    "Página {$page['url']} tem alto potencial para ranqueamento em IA",
                    'Otimizar para respostas diretas e implementar dados estruturados',
                    array_merge(['url' => $page['url']], $aiPotential),
                    $this->getPageId($page['url'])
                );
            }
        }
    }
    
    private function calculateAIPotential($page) {
        $score = 0;
        $factors = [];
        
        // Fatores que aumentam potencial para IA
        $title = strtolower($page['title'] ?? '');
        $h1 = strtolower($page['h1'] ?? '');
        
        // Perguntas diretas
        if (preg_match('/\b(como|what|why|quando|onde|qual|quais|porque)\b/', $title . ' ' . $h1)) {
            $score += 25;
            $factors[] = 'Contém pergunta direta';
        }
        
        // Listas e comparações
        if (preg_match('/\b(melhores|top|lista|ranking|versus|vs|comparação)\b/', $title . ' ' . $h1)) {
            $score += 20;
            $factors[] = 'Conteúdo de lista/comparação';
        }
        
        // Conteúdo estruturado
        if ($page['word_count'] > 500 && $page['word_count'] < 2000) {
            $score += 15;
            $factors[] = 'Tamanho ideal para IA';
        }
        
        // Tutoriais
        if (preg_match('/\b(tutorial|guia|passo|steps|como fazer)\b/', $title . ' ' . $h1)) {
            $score += 20;
            $factors[] = 'Conteúdo tutorial/guia';
        }
        
        // Definições
        if (preg_match('/\b(o que é|what is|definição|significa)\b/', $title . ' ' . $h1)) {
            $score += 20;
            $factors[] = 'Conteúdo de definição';
        }
        
        return [
            'score' => $score,
            'factors' => $factors
        ];
    }
}
?>
