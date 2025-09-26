<?php
/**
 * Classe Crawler - Rastreamento de Sites para Análise de SEO
 */

class Crawler {
    private $analysisId;
    private $options;
    private $db;
    private $helper;
    private $visitedUrls = [];
    private $pendingUrls = [];
    private $robotsRules = [];
    private $sitemapUrls = [];
    private $startTime;
    private $userAgent = 'EscopoSEO Bot 1.0 (+https://example.com/bot)';
    
    public function __construct($analysisId, $options = []) {
        $this->analysisId = $analysisId;
        $this->options = array_merge([
            'max_pages' => 100,
            'respect_robots' => true,
            'include_external' => false,
            'delay' => 1,
            'timeout' => 30,
            'max_redirects' => 5,
            'max_depth' => 10
        ], $options);
        
        $this->db = Database::getInstance();
        $this->helper = new DatabaseHelper();
        $this->startTime = time();
    }
    
    /**
     * Inicia o rastreamento do site
     */
    public function crawl($startUrl) {
        try {
            $parsedUrl = parse_url($startUrl);
            $domain = $parsedUrl['host'];
            $baseUrl = $parsedUrl['scheme'] . '://' . $domain;
            
            $this->helper->addLog($this->analysisId, 'crawl_init', "Iniciando rastreamento de: $startUrl");
            
            // Analisar robots.txt
            if ($this->options['respect_robots']) {
                $this->analyzeRobotsTxt($baseUrl);
            }
            
            // Analisar sitemap.xml
            $this->analyzeSitemap($baseUrl);
            
            // Adicionar URL inicial à fila
            $this->addUrlToQueue($startUrl, 0);
            
            $pagesProcessed = 0;
            $maxPages = $this->options['max_pages'];
            
            // Loop principal de rastreamento
            while (!empty($this->pendingUrls) && $pagesProcessed < $maxPages) {
                $urlData = array_shift($this->pendingUrls);
                $url = $urlData['url'];
                $depth = $urlData['depth'];
                
                if (isset($this->visitedUrls[$url]) || $depth > $this->options['max_depth']) {
                    continue;
                }
                
                // Verificar robots.txt
                if ($this->options['respect_robots'] && !$this->isAllowedByRobots($url)) {
                    $this->helper->addLog($this->analysisId, 'robots_blocked', "URL bloqueada pelo robots.txt: $url");
                    continue;
                }
                
                // Fazer requisição HTTP
                $pageData = $this->fetchPage($url);
                
                if ($pageData) {
                    // Salvar página no banco
                    $pageId = $this->savePage($url, $pageData, $depth);
                    
                    // Extrair links se for página HTML
                    if ($pageData['content_type'] === 'text/html' && $pageData['status_code'] === 200) {
                        $this->extractLinks($pageId, $url, $pageData['content'], $domain, $depth);
                    }
                    
                    $pagesProcessed++;
                    $this->visitedUrls[$url] = true;
                    
                    // Atualizar progresso
                    $progress = min(50, ($pagesProcessed / $maxPages) * 50);
                    $this->helper->updateAnalysisStatus($this->analysisId, 'running', $progress);
                    
                    // Delay entre requisições
                    if ($this->options['delay'] > 0) {
                        sleep($this->options['delay']);
                    }
                }
            }
            
            $this->helper->addLog($this->analysisId, 'crawl_summary', 
                "Rastreamento concluído: $pagesProcessed páginas processadas em " . (time() - $this->startTime) . " segundos");
            
            return [
                'pages_found' => $pagesProcessed,
                'total_time' => time() - $this->startTime
            ];
            
        } catch (Exception $e) {
            $this->helper->addLog($this->analysisId, 'crawl_error', 'Erro no rastreamento: ' . $e->getMessage(), 'error');
            throw $e;
        }
    }
    
    /**
     * Adiciona URL à fila de processamento
     */
    private function addUrlToQueue($url, $depth) {
        $url = $this->normalizeUrl($url);
        
        if (!isset($this->visitedUrls[$url]) && !$this->isInQueue($url)) {
            $this->pendingUrls[] = ['url' => $url, 'depth' => $depth];
        }
    }
    
    /**
     * Verifica se URL já está na fila
     */
    private function isInQueue($url) {
        foreach ($this->pendingUrls as $item) {
            if ($item['url'] === $url) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Normaliza URL removendo fragmentos e parâmetros desnecessários
     */
    private function normalizeUrl($url) {
        $parsed = parse_url($url);
        
        if (!$parsed || !isset($parsed['host'])) {
            return $url;
        }
        
        $normalized = $parsed['scheme'] . '://' . $parsed['host'];
        
        if (isset($parsed['port']) && $parsed['port'] != 80 && $parsed['port'] != 443) {
            $normalized .= ':' . $parsed['port'];
        }
        
        $normalized .= isset($parsed['path']) ? $parsed['path'] : '/';
        
        if (isset($parsed['query'])) {
            // Manter apenas parâmetros importantes para SEO
            parse_str($parsed['query'], $params);
            $keepParams = ['page', 'p', 'category', 'cat', 'id', 'slug'];
            $filteredParams = array_intersect_key($params, array_flip($keepParams));
            
            if (!empty($filteredParams)) {
                $normalized .= '?' . http_build_query($filteredParams);
            }
        }
        
        return $normalized;
    }
    
    /**
     * Faz requisição HTTP para uma página
     */
    private function fetchPage($url) {
        $startTime = microtime(true);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => $this->options['max_redirects'],
            CURLOPT_TIMEOUT => $this->options['timeout'],
            CURLOPT_USERAGENT => $this->userAgent,
            CURLOPT_HEADER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_ENCODING => 'gzip,deflate'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $totalTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
        $redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        
        if (curl_error($ch)) {
            $this->helper->addLog($this->analysisId, 'fetch_error', "Erro ao buscar $url: " . curl_error($ch), 'warning');
            curl_close($ch);
            return null;
        }
        
        curl_close($ch);
        
        $headers = substr($response, 0, $headerSize);
        $content = substr($response, $headerSize);
        
        $loadTime = round((microtime(true) - $startTime) * 1000); // em milissegundos
        
        return [
            'status_code' => $httpCode,
            'content_type' => $contentType,
            'headers' => $headers,
            'content' => $content,
            'load_time' => $loadTime,
            'total_time' => $totalTime,
            'redirect_url' => $redirectUrl,
            'page_size' => strlen($content)
        ];
    }
    
    /**
     * Salva dados da página no banco
     */
    private function savePage($url, $pageData, $depth) {
        // Inserir página
        $pageId = $this->db->insert(
            "INSERT INTO pages (analysis_id, url, status_code, content_type, page_size, load_time, 
                               response_headers, redirect_url, depth, discovered_at, analyzed_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
            [
                $this->analysisId,
                $url,
                $pageData['status_code'],
                $pageData['content_type'],
                $pageData['page_size'],
                $pageData['load_time'],
                $pageData['headers'],
                $pageData['redirect_url'],
                $depth
            ]
        );
        
        // Se for HTML, extrair e salvar conteúdo
        if (stripos($pageData['content_type'], 'text/html') !== false && $pageData['status_code'] === 200) {
            $this->savePageContent($pageId, $pageData['content']);
        }
        
        return $pageId;
    }
    
    /**
     * Extrai e salva conteúdo da página HTML
     */
    private function savePageContent($pageId, $html) {
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        $xpath = new DOMXPath($dom);
        
        // Extrair elementos básicos
        $title = $this->extractTitle($xpath);
        $metaDescription = $this->extractMetaDescription($xpath);
        $h1 = $this->extractH1($xpath);
        $headings = $this->extractHeadings($xpath);
        $wordCount = $this->countWords($dom);
        $images = $this->extractImages($pageId, $xpath);
        
        // Calcular densidade de palavras-chave
        $keywordDensity = $this->calculateKeywordDensity($title, $h1, $dom);
        
        // Analisar robots meta
        $robotsMeta = $this->extractRobotsMeta($xpath);
        
        // Salvar no banco
        $this->db->insert(
            "INSERT INTO page_content (page_id, title, title_length, meta_description, meta_description_length,
                                     h1, h1_count, h2_count, h3_count, h4_count, h5_count, h6_count,
                                     word_count, image_count, images_without_alt, keyword_density, main_keywords)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $pageId,
                $title,
                strlen($title),
                $metaDescription,
                strlen($metaDescription),
                $h1,
                $headings['h1'],
                $headings['h2'],
                $headings['h3'],
                $headings['h4'],
                $headings['h5'],
                $headings['h6'],
                $wordCount,
                $images['total'],
                $images['without_alt'],
                json_encode($keywordDensity),
                json_encode($this->extractMainKeywords($title, $h1))
            ]
        );
        
        // Atualizar metadados robots na tabela pages
        if ($robotsMeta) {
            $this->db->update(
                "UPDATE pages SET robots_meta = ?, is_indexable = ? WHERE id = ?",
                [$robotsMeta, $this->isIndexable($robotsMeta) ? 1 : 0, $pageId]
            );
        }
    }
    
    /**
     * Extrai links da página
     */
    private function extractLinks($pageId, $currentUrl, $html, $domain, $currentDepth) {
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        $xpath = new DOMXPath($dom);
        
        $links = $xpath->query('//a[@href]');
        $linkCount = 0;
        
        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            $anchorText = trim($link->textContent);
            $rel = $link->getAttribute('rel');
            
            if (empty($href) || $href === '#') {
                continue;
            }
            
            // Converter URL relativa para absoluta
            $absoluteUrl = $this->resolveUrl($href, $currentUrl);
            
            if (!$absoluteUrl) {
                continue;
            }
            
            // Determinar tipo de link
            $linkType = $this->determineLinkType($absoluteUrl, $domain);
            $isFollow = !preg_match('/nofollow/i', $rel);
            
            // Salvar link no banco
            $this->db->insert(
                "INSERT INTO links (source_page_id, target_url, anchor_text, link_type, is_follow, position)
                 VALUES (?, ?, ?, ?, ?, ?)",
                [$pageId, $absoluteUrl, $anchorText, $linkType, $isFollow ? 1 : 0, $linkCount]
            );
            
            // Adicionar à fila se for interno e follow
            if ($linkType === 'internal' && $isFollow && $currentDepth < $this->options['max_depth']) {
                $this->addUrlToQueue($absoluteUrl, $currentDepth + 1);
            }
            
            $linkCount++;
        }
        
        // Atualizar contadores de links na página
        $internalLinks = $this->db->selectOne(
            "SELECT COUNT(*) as count FROM links WHERE source_page_id = ? AND link_type = 'internal'",
            [$pageId]
        );
        
        $externalLinks = $this->db->selectOne(
            "SELECT COUNT(*) as count FROM links WHERE source_page_id = ? AND link_type = 'external'",
            [$pageId]
        );
        
        $this->db->update(
            "UPDATE page_content SET internal_links_count = ?, external_links_count = ? WHERE page_id = ?",
            [$internalLinks['count'], $externalLinks['count'], $pageId]
        );
    }
    
    /**
     * Analisa robots.txt
     */
    private function analyzeRobotsTxt($baseUrl) {
        $robotsUrl = $baseUrl . '/robots.txt';
        $pageData = $this->fetchPage($robotsUrl);
        
        $isAccessible = $pageData && $pageData['status_code'] === 200;
        $content = $isAccessible ? $pageData['content'] : null;
        $errorMessage = !$isAccessible ? 'Arquivo não encontrado ou inacessível' : null;
        
        // Salvar análise do robots.txt
        $this->db->insert(
            "INSERT INTO robots_analysis (analysis_id, robots_url, content, is_accessible, error_message)
             VALUES (?, ?, ?, ?, ?)",
            [$this->analysisId, $robotsUrl, $content, $isAccessible ? 1 : 0, $errorMessage]
        );
        
        if ($isAccessible && $content) {
            $this->parseRobotsRules($content);
            $sitemaps = $this->extractSitemapsFromRobots($content);
            
            // Atualizar com dados parseados
            $this->db->update(
                "UPDATE robots_analysis SET sitemap_urls = ? WHERE analysis_id = ?",
                [json_encode($sitemaps), $this->analysisId]
            );
        }
        
        $this->helper->addLog($this->analysisId, 'robots_analysis', 
            $isAccessible ? 'Robots.txt analisado com sucesso' : 'Robots.txt não encontrado');
    }
    
    /**
     * Analisa sitemap.xml
     */
    private function analyzeSitemap($baseUrl) {
        $sitemapUrls = [
            $baseUrl . '/sitemap.xml',
            $baseUrl . '/sitemap_index.xml',
            $baseUrl . '/sitemaps.xml'
        ];
        
        foreach ($sitemapUrls as $sitemapUrl) {
            $pageData = $this->fetchPage($sitemapUrl);
            
            if ($pageData && $pageData['status_code'] === 200) {
                $this->parseSitemap($sitemapUrl, $pageData['content']);
                break;
            }
        }
    }
    
    /**
     * Faz parse do sitemap XML
     */
    private function parseSitemap($sitemapUrl, $content) {
        $urls = [];
        $totalUrls = 0;
        $validUrls = 0;
        
        try {
            $xml = new SimpleXMLElement($content);
            
            // Verificar se é sitemap index
            if (isset($xml->sitemap)) {
                foreach ($xml->sitemap as $sitemap) {
                    $this->parseSitemap((string)$sitemap->loc, file_get_contents((string)$sitemap->loc));
                }
            } else {
                // Sitemap normal
                foreach ($xml->url as $url) {
                    $loc = (string)$url->loc;
                    $lastmod = isset($url->lastmod) ? (string)$url->lastmod : null;
                    
                    $urls[] = [
                        'loc' => $loc,
                        'lastmod' => $lastmod,
                        'priority' => isset($url->priority) ? (float)$url->priority : null,
                        'changefreq' => isset($url->changefreq) ? (string)$url->changefreq : null
                    ];
                    
                    $totalUrls++;
                    if (filter_var($loc, FILTER_VALIDATE_URL)) {
                        $validUrls++;
                        $this->sitemapUrls[] = $loc;
                    }
                }
            }
            
            // Salvar análise do sitemap
            $this->db->insert(
                "INSERT INTO sitemap_analysis (analysis_id, sitemap_url, type, is_accessible, total_urls, valid_urls, urls)
                 VALUES (?, ?, 'xml', 1, ?, ?, ?)",
                [$this->analysisId, $sitemapUrl, $totalUrls, $validUrls, json_encode($urls)]
            );
            
            $this->helper->addLog($this->analysisId, 'sitemap_analysis', 
                "Sitemap analisado: $validUrls URLs válidas de $totalUrls totais");
            
        } catch (Exception $e) {
            $this->helper->addLog($this->analysisId, 'sitemap_error', 
                "Erro ao processar sitemap: " . $e->getMessage(), 'warning');
        }
    }
    
    // Métodos auxiliares de extração de conteúdo
    
    private function extractTitle($xpath) {
        $titles = $xpath->query('//title');
        return $titles->length > 0 ? trim($titles->item(0)->textContent) : '';
    }
    
    private function extractMetaDescription($xpath) {
        $metas = $xpath->query('//meta[@name="description"]/@content');
        return $metas->length > 0 ? trim($metas->item(0)->nodeValue) : '';
    }
    
    private function extractH1($xpath) {
        $h1s = $xpath->query('//h1');
        return $h1s->length > 0 ? trim($h1s->item(0)->textContent) : '';
    }
    
    private function extractHeadings($xpath) {
        $headings = [];
        for ($i = 1; $i <= 6; $i++) {
            $headings["h$i"] = $xpath->query("//h$i")->length;
        }
        return $headings;
    }
    
    private function extractRobotsMeta($xpath) {
        $metas = $xpath->query('//meta[@name="robots"]/@content');
        return $metas->length > 0 ? trim($metas->item(0)->nodeValue) : null;
    }
    
    private function extractImages($pageId, $xpath) {
        $images = $xpath->query('//img[@src]');
        $totalImages = $images->length;
        $imagesWithoutAlt = 0;
        
        foreach ($images as $img) {
            $src = $img->getAttribute('src');
            $alt = $img->getAttribute('alt');
            $title = $img->getAttribute('title');
            $loading = $img->getAttribute('loading');
            
            if (empty($alt)) {
                $imagesWithoutAlt++;
            }
            
            // Salvar dados da imagem
            $this->db->insert(
                "INSERT INTO images (page_id, src, alt, title, loading_attribute, is_lazy_loaded)
                 VALUES (?, ?, ?, ?, ?, ?)",
                [$pageId, $src, $alt, $title, $loading, $loading === 'lazy' ? 1 : 0]
            );
        }
        
        return [
            'total' => $totalImages,
            'without_alt' => $imagesWithoutAlt
        ];
    }
    
    private function countWords($dom) {
        $text = $dom->textContent;
        $text = preg_replace('/\s+/', ' ', $text);
        $words = explode(' ', trim($text));
        return count(array_filter($words));
    }
    
    private function calculateKeywordDensity($title, $h1, $dom) {
        $text = strtolower($title . ' ' . $h1 . ' ' . $dom->textContent);
        $words = preg_split('/\s+/', $text);
        $words = array_filter($words, function($word) {
            return strlen($word) > 3;
        });
        
        $wordCount = array_count_values($words);
        arsort($wordCount);
        
        $total = count($words);
        $density = [];
        
        foreach (array_slice($wordCount, 0, 10, true) as $word => $count) {
            $density[$word] = round(($count / $total) * 100, 2);
        }
        
        return $density;
    }
    
    private function extractMainKeywords($title, $h1) {
        $text = strtolower($title . ' ' . $h1);
        $words = preg_split('/\s+/', $text);
        $words = array_filter($words, function($word) {
            return strlen($word) > 3;
        });
        
        return array_slice(array_keys(array_count_values($words)), 0, 5);
    }
    
    private function isIndexable($robotsMeta) {
        if (!$robotsMeta) return true;
        return !preg_match('/noindex/i', $robotsMeta);
    }
    
    private function resolveUrl($href, $base) {
        // URL absoluta
        if (preg_match('/^https?:\/\//', $href)) {
            return $href;
        }
        
        // URL relativa
        $baseParts = parse_url($base);
        $scheme = $baseParts['scheme'];
        $host = $baseParts['host'];
        $port = isset($baseParts['port']) ? ':' . $baseParts['port'] : '';
        
        if ($href[0] === '/') {
            return "$scheme://$host$port$href";
        }
        
        $path = isset($baseParts['path']) ? dirname($baseParts['path']) : '';
        return "$scheme://$host$port$path/$href";
    }
    
    private function determineLinkType($url, $domain) {
        $parsed = parse_url($url);
        
        if (!$parsed || !isset($parsed['host'])) {
            return 'internal';
        }
        
        if ($parsed['host'] === $domain) {
            return 'internal';
        }
        
        if (preg_match('/^mailto:/', $url)) {
            return 'mailto';
        }
        
        if (preg_match('/^tel:/', $url)) {
            return 'tel';
        }
        
        return 'external';
    }
    
    private function parseRobotsRules($content) {
        $lines = explode("\n", $content);
        $currentUserAgent = '*';
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            if (empty($line) || $line[0] === '#') {
                continue;
            }
            
            if (preg_match('/^User-agent:\s*(.+)$/i', $line, $matches)) {
                $currentUserAgent = trim($matches[1]);
            } elseif (preg_match('/^Disallow:\s*(.+)$/i', $line, $matches)) {
                $this->robotsRules[$currentUserAgent]['disallow'][] = trim($matches[1]);
            } elseif (preg_match('/^Allow:\s*(.+)$/i', $line, $matches)) {
                $this->robotsRules[$currentUserAgent]['allow'][] = trim($matches[1]);
            }
        }
    }
    
    private function extractSitemapsFromRobots($content) {
        $sitemaps = [];
        $lines = explode("\n", $content);
        
        foreach ($lines as $line) {
            if (preg_match('/^Sitemap:\s*(.+)$/i', trim($line), $matches)) {
                $sitemaps[] = trim($matches[1]);
            }
        }
        
        return $sitemaps;
    }
    
    private function isAllowedByRobots($url) {
        if (empty($this->robotsRules)) {
            return true;
        }
        
        $path = parse_url($url, PHP_URL_PATH) ?: '/';
        
        // Verificar regras para o user agent específico e *
        $agents = [$this->userAgent, '*'];
        
        foreach ($agents as $agent) {
            if (!isset($this->robotsRules[$agent])) {
                continue;
            }
            
            $rules = $this->robotsRules[$agent];
            
            // Verificar Allow primeiro
            if (isset($rules['allow'])) {
                foreach ($rules['allow'] as $allowPath) {
                    if ($this->matchesRobotsPattern($path, $allowPath)) {
                        return true;
                    }
                }
            }
            
            // Verificar Disallow
            if (isset($rules['disallow'])) {
                foreach ($rules['disallow'] as $disallowPath) {
                    if ($this->matchesRobotsPattern($path, $disallowPath)) {
                        return false;
                    }
                }
            }
        }
        
        return true;
    }
    
    private function matchesRobotsPattern($path, $pattern) {
        $pattern = str_replace('*', '.*', preg_quote($pattern, '/'));
        return preg_match('/^' . $pattern . '/i', $path);
    }
}
?>
