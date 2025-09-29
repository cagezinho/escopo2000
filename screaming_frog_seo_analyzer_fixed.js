/**
 * 🚀 EscopoSEO - Analisador SEO com IA para Screaming Frog
 * Versão Corrigida - Funciona com Gemini API
 * 
 * INSTRUÇÕES DE USO:
 * 1. Abra Screaming Frog SEO Spider
 * 2. Vá em Configuration > Custom > Extraction
 * 3. Adicione novo extrator: JavaScript
 * 4. Cole este código
 * 5. Configure sua API key do Gemini abaixo
 * 6. Execute o crawl normalmente
 */

// =============================================================================
// CONFIGURAÇÃO - EDITE AQUI SUA API KEY
// =============================================================================

const CONFIG = {
    // SUA CHAVE DA API GEMINI (obrigatório)
    GEMINI_API_KEY: "AIzaSyCFxI43LRVSKIICxxGZHLBTKPrmYO2YNMo",
    
    // Configurações da API
    GEMINI_MODEL: "gemini-2.0-flash-exp",
    GEMINI_ENDPOINT: "https://generativelanguage.googleapis.com/v1beta/models/",
    
    // Configurações de análise
    MAX_CONTENT_LENGTH: 30000,
    TIMEOUT_MS: 25000,
    RETRY_ATTEMPTS: 2,
    
    // Debug
    DEBUG_MODE: true,
    
    // Análise básica quando API falha
    FALLBACK_TO_BASIC: true
};

// =============================================================================
// PROMPT OTIMIZADO PARA GEMINI
// =============================================================================

const PROMPT_GEMINI = `
Você é um especialista em SEO técnico e otimização para IA. Analise esta página web e retorne um JSON com exatamente esta estrutura:

{
  "url": "URL da página",
  "score_seo": 85,
  "score_conteudo": 75,
  "score_ia": 80,
  "prioridade": "Alta/Média/Baixa",
  "problemas_seo": [
    "Lista de problemas SEO técnicos encontrados"
  ],
  "problemas_conteudo": [
    "Lista de problemas de conteúdo encontrados"
  ],
  "oportunidades_ia": [
    "Lista de oportunidades para otimização para IA/SGE"
  ],
  "acoes_prioritarias": [
    "3 ações mais importantes para implementar"
  ],
  "resumo": "Resumo executivo em 1-2 frases"
}

Analise especificamente:
- SEO Técnico: title, meta description, headings, canonical, robots, imagens sem alt
- Conteúdo: qualidade, estrutura, densidade de palavras-chave, E-E-A-T
- IA/SGE: potencial para featured snippets, FAQ, dados estruturados, format de respostas

Dê scores de 0-100 e classifique prioridade baseada na média dos scores (0-50=Alta, 51-75=Média, 76-100=Baixa).

Retorne APENAS o JSON válido, sem texto adicional.
`;

// =============================================================================
// FUNÇÃO PRINCIPAL - EXECUTADA PELO SCREAMING FROG
// =============================================================================

function extract() {
    try {
        logDebug("🚀 Iniciando análise EscopoSEO...");
        
        // Coletar dados básicos da página
        const pageData = collectBasicPageData();
        logDebug("📊 Dados coletados:", pageData);
        
        // Verificar se tem API key configurada
        if (!CONFIG.GEMINI_API_KEY || CONFIG.GEMINI_API_KEY === "SUA_CHAVE_GEMINI_AQUI") {
            logDebug("⚠️ API Key não configurada, usando análise básica");
            const basicResult = performBasicAnalysis(pageData);
            return seoSpider.data(basicResult);
        }
        
        // Tentar análise com Gemini
        try {
            const geminiResult = analyzeWithGemini(pageData);
            logDebug("✅ Análise com Gemini concluída");
            return seoSpider.data(geminiResult);
        } catch (error) {
            logError("❌ Erro na análise Gemini:", error);
            
            if (CONFIG.FALLBACK_TO_BASIC) {
                logDebug("🔄 Usando análise básica como fallback");
                const fallbackResult = performBasicAnalysis(pageData);
                return seoSpider.data(fallbackResult);
            } else {
                const errorResult = JSON.stringify({
                    url: pageData.url,
                    erro: "FALHA_GEMINI",
                    detalhes: error.message,
                    timestamp: new Date().toISOString()
                });
                return seoSpider.data(errorResult);
            }
        }
        
    } catch (error) {
        logError("❌ Erro crítico na função extract:", error);
        const criticalError = JSON.stringify({
            erro: "ERRO_CRITICO",
            detalhes: error.message,
            url: typeof window !== 'undefined' ? window.location.href : 'N/A',
            timestamp: new Date().toISOString()
        });
        return seoSpider.data(criticalError);
    }
}

// =============================================================================
// COLETA DE DADOS DA PÁGINA
// =============================================================================

function collectBasicPageData() {
    try {
        const url = window.location.href;
        const title = document.title || "";
        const metaDesc = getMetaDescription();
        const h1 = getH1Text();
        
        // Coletar headings
        const headings = {
            h1: document.querySelectorAll('h1').length,
            h2: document.querySelectorAll('h2').length,
            h3: document.querySelectorAll('h3').length
        };
        
        // Coletar dados de imagens
        const images = document.querySelectorAll('img');
        const imagesData = {
            total: images.length,
            withoutAlt: Array.from(images).filter(img => !img.alt || img.alt.trim() === '').length
        };
        
        // Coletar links
        const links = document.querySelectorAll('a[href]');
        const currentDomain = window.location.hostname;
        let internalLinks = 0;
        let externalLinks = 0;
        
        Array.from(links).forEach(link => {
            const href = link.getAttribute('href');
            if (href && href.startsWith('http')) {
                try {
                    const linkDomain = new URL(href).hostname;
                    if (linkDomain === currentDomain) {
                        internalLinks++;
                    } else {
                        externalLinks++;
                    }
                } catch (e) {
                    // URL inválida, ignora
                }
            } else if (href && (href.startsWith('/') || !href.includes('://'))) {
                internalLinks++;
            }
        });
        
        // Contar palavras
        const bodyText = document.body ? document.body.innerText : "";
        const wordCount = bodyText.trim().split(/\s+/).filter(word => word.length > 0).length;
        
        // Verificar canonical
        const canonical = document.querySelector('link[rel="canonical"]');
        const canonicalUrl = canonical ? canonical.getAttribute('href') : "";
        
        // Verificar robots
        const robots = document.querySelector('meta[name="robots"]');
        const robotsContent = robots ? robots.getAttribute('content') : "";
        
        // Dados estruturados
        const structuredData = document.querySelectorAll('script[type="application/ld+json"]').length;
        
        // Preparar HTML limpo para análise
        let htmlContent = document.documentElement.outerHTML;
        htmlContent = htmlContent.replace(/<script[^>]*>[\s\S]*?<\/script>/gi, '');
        htmlContent = htmlContent.replace(/<style[^>]*>[\s\S]*?<\/style>/gi, '');
        htmlContent = htmlContent.replace(/<!--[\s\S]*?-->/g, '');
        
        if (htmlContent.length > CONFIG.MAX_CONTENT_LENGTH) {
            htmlContent = htmlContent.substring(0, CONFIG.MAX_CONTENT_LENGTH) + "... [TRUNCADO]";
        }
        
        return {
            url: url,
            title: title,
            metaDescription: metaDesc,
            h1: h1,
            headings: headings,
            images: imagesData,
            links: { internal: internalLinks, external: externalLinks },
            wordCount: wordCount,
            canonical: canonicalUrl,
            robots: robotsContent,
            structuredData: structuredData,
            htmlContent: htmlContent,
            timestamp: new Date().toISOString()
        };
        
    } catch (error) {
        logError("Erro ao coletar dados da página:", error);
        return {
            url: typeof window !== 'undefined' ? window.location.href : 'N/A',
            title: "",
            metaDescription: "",
            h1: "",
            headings: { h1: 0, h2: 0, h3: 0 },
            images: { total: 0, withoutAlt: 0 },
            links: { internal: 0, external: 0 },
            wordCount: 0,
            canonical: "",
            robots: "",
            structuredData: 0,
            htmlContent: "",
            timestamp: new Date().toISOString()
        };
    }
}

function getMetaDescription() {
    const meta = document.querySelector('meta[name="description"]');
    return meta ? meta.getAttribute('content') : "";
}

function getH1Text() {
    const h1 = document.querySelector('h1');
    return h1 ? h1.innerText.trim() : "";
}

// =============================================================================
// ANÁLISE COM GEMINI API
// =============================================================================

function analyzeWithGemini(pageData) {
    const url = `${CONFIG.GEMINI_ENDPOINT}${CONFIG.GEMINI_MODEL}:generateContent?key=${CONFIG.GEMINI_API_KEY}`;
    
    const prompt = `${PROMPT_GEMINI}

DADOS DA PÁGINA:
URL: ${pageData.url}
Título: ${pageData.title}
Meta Description: ${pageData.metaDescription}
H1: ${pageData.h1}
Contagem de palavras: ${pageData.wordCount}
H1s: ${pageData.headings.h1}, H2s: ${pageData.headings.h2}, H3s: ${pageData.headings.h3}
Imagens total: ${pageData.images.total}, Sem ALT: ${pageData.images.withoutAlt}
Links internos: ${pageData.links.internal}, externos: ${pageData.links.external}
Canonical: ${pageData.canonical || 'Ausente'}
Robots: ${pageData.robots || 'Ausente'}
Dados estruturados: ${pageData.structuredData} scripts

HTML (primeiros ${CONFIG.MAX_CONTENT_LENGTH} chars):
${pageData.htmlContent}`;

    const requestBody = {
        contents: [{
            parts: [{ text: prompt }]
        }],
        generationConfig: {
            temperature: 0.1,
            topK: 1,
            topP: 1,
            maxOutputTokens: 4096
        }
    };

    let attempts = 0;
    while (attempts < CONFIG.RETRY_ATTEMPTS) {
        try {
            logDebug(`Tentativa ${attempts + 1} de análise com Gemini`);
            
            const xhr = new XMLHttpRequest();
            xhr.open('POST', url, false); // Síncrono para o Screaming Frog
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.timeout = CONFIG.TIMEOUT_MS;
            
            xhr.send(JSON.stringify(requestBody));
            
            if (xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                
                if (response.candidates && response.candidates[0] && response.candidates[0].content) {
                    const responseText = response.candidates[0].content.parts[0].text;
                    
                    // Tentar extrair JSON da resposta
                    const jsonMatch = responseText.match(/\{[\s\S]*\}/);
                    if (jsonMatch) {
                        try {
                            const analysisJson = JSON.parse(jsonMatch[0]);
                            
                            // Adicionar metadados
                            analysisJson.metadata = {
                                analyzed_at: new Date().toISOString(),
                                gemini_model: CONFIG.GEMINI_MODEL,
                                word_count: pageData.wordCount,
                                analysis_type: "gemini_api"
                            };
                            
                            // Preparar dados para download
                            prepareDownloadData(analysisJson, pageData);
                            
                            return JSON.stringify(analysisJson);
                        } catch (jsonError) {
                            logError("Erro ao parsear JSON da resposta Gemini:", jsonError);
                            throw new Error("Resposta Gemini não contém JSON válido");
                        }
                    } else {
                        logError("Resposta Gemini não contém JSON:", responseText);
                        throw new Error("Resposta Gemini sem JSON válido");
                    }
                } else {
                    throw new Error("Resposta Gemini inválida ou vazia");
                }
            } else {
                throw new Error(`HTTP ${xhr.status}: ${xhr.statusText || 'Erro na requisição'}`);
            }
            
        } catch (error) {
            attempts++;
            logError(`Erro na tentativa ${attempts}:`, error);
            
            if (attempts >= CONFIG.RETRY_ATTEMPTS) {
                throw error;
            }
            
            // Delay antes de tentar novamente
            const delay = 1000 * attempts;
            const start = Date.now();
            while (Date.now() - start < delay) {
                // Delay síncrono
            }
        }
    }
}

// =============================================================================
// ANÁLISE BÁSICA (FALLBACK)
// =============================================================================

function performBasicAnalysis(pageData) {
    let seoScore = 100;
    let contentScore = 100;
    let aiScore = 100;
    
    const problemas_seo = [];
    const problemas_conteudo = [];
    const oportunidades_ia = [];
    const acoes_prioritarias = [];
    
    // Análise SEO Técnico
    if (!pageData.title) {
        problemas_seo.push("Título ausente");
        seoScore -= 25;
    } else if (pageData.title.length < 30) {
        problemas_seo.push(`Título muito curto (${pageData.title.length} chars)`);
        seoScore -= 15;
    } else if (pageData.title.length > 60) {
        problemas_seo.push(`Título muito longo (${pageData.title.length} chars)`);
        seoScore -= 10;
    }
    
    if (!pageData.metaDescription) {
        problemas_seo.push("Meta description ausente");
        seoScore -= 20;
    } else if (pageData.metaDescription.length < 120) {
        problemas_seo.push(`Meta description curta (${pageData.metaDescription.length} chars)`);
        seoScore -= 10;
    }
    
    if (!pageData.h1) {
        problemas_seo.push("H1 ausente");
        seoScore -= 15;
    }
    
    if (pageData.headings.h1 > 1) {
        problemas_seo.push(`Múltiplos H1 (${pageData.headings.h1})`);
        seoScore -= 10;
    }
    
    if (pageData.images.withoutAlt > 0) {
        problemas_seo.push(`${pageData.images.withoutAlt} imagens sem ALT text`);
        seoScore -= Math.min(20, pageData.images.withoutAlt * 5);
    }
    
    if (!pageData.canonical) {
        problemas_seo.push("URL canônica ausente");
        seoScore -= 5;
    }
    
    // Análise de Conteúdo
    if (pageData.wordCount < 300) {
        problemas_conteudo.push(`Conteúdo insuficiente (${pageData.wordCount} palavras)`);
        contentScore -= 25;
    }
    
    if (pageData.headings.h2 === 0) {
        problemas_conteudo.push("Sem subtítulos H2");
        contentScore -= 15;
    }
    
    if (pageData.links.internal < 3) {
        problemas_conteudo.push(`Poucos links internos (${pageData.links.internal})`);
        contentScore -= 10;
    }
    
    // Análise IA/SGE
    if (pageData.structuredData === 0) {
        oportunidades_ia.push("Implementar dados estruturados schema.org");
        aiScore -= 20;
    }
    
    if (!pageData.title.toLowerCase().includes('como') && 
        !pageData.title.toLowerCase().includes('o que') &&
        !pageData.h1.toLowerCase().includes('como')) {
        oportunidades_ia.push("Otimizar para perguntas diretas");
        aiScore -= 15;
    }
    
    if (pageData.headings.h2 < 3) {
        oportunidades_ia.push("Melhorar estrutura para featured snippets");
        aiScore -= 10;
    }
    
    // Ações prioritárias
    if (problemas_seo.length > 0) acoes_prioritarias.push("Corrigir problemas técnicos de SEO");
    if (problemas_conteudo.length > 0) acoes_prioritarias.push("Melhorar qualidade e estrutura do conteúdo");
    if (oportunidades_ia.length > 0) acoes_prioritarias.push("Implementar otimizações para IA/SGE");
    
    const avgScore = Math.round((seoScore + contentScore + aiScore) / 3);
    let prioridade = "Baixa";
    if (avgScore < 50) prioridade = "Alta";
    else if (avgScore < 75) prioridade = "Média";
    
    const resultado = {
        url: pageData.url,
        score_seo: Math.max(0, seoScore),
        score_conteudo: Math.max(0, contentScore),
        score_ia: Math.max(0, aiScore),
        prioridade: prioridade,
        problemas_seo: problemas_seo,
        problemas_conteudo: problemas_conteudo,
        oportunidades_ia: oportunidades_ia,
        acoes_prioritarias: acoes_prioritarias.slice(0, 3),
        resumo: `Análise básica: ${problemas_seo.length + problemas_conteudo.length + oportunidades_ia.length} problemas identificados`,
        metadata: {
            analyzed_at: new Date().toISOString(),
            analysis_type: "basic_fallback",
            word_count: pageData.wordCount
        }
    };
    
    // Preparar dados para download
    prepareDownloadData(resultado, pageData);
    
    return JSON.stringify(resultado);
}

// =============================================================================
// SISTEMA DE DOWNLOAD AUTOMÁTICO
// =============================================================================

function prepareDownloadData(analysisResult, pageData) {
    try {
        // Armazenar dados para download posterior
        if (typeof localStorage !== 'undefined') {
            const storedData = getStoredAnalysisData();
            
            const entry = {
                url: pageData.url,
                timestamp: new Date().toISOString(),
                analysis: analysisResult,
                pageData: {
                    title: pageData.title,
                    wordCount: pageData.wordCount,
                    domain: new URL(pageData.url).hostname
                }
            };
            
            storedData.push(entry);
            localStorage.setItem('escopoSEO_analysis_data', JSON.stringify(storedData));
            localStorage.setItem('escopoSEO_last_update', new Date().toISOString());
            
            logDebug(`💾 Dados armazenados: ${storedData.length} páginas total`);
            
            // Trigger download a cada 25 páginas ou no final
            if (storedData.length % 25 === 0) {
                setTimeout(() => triggerBatchDownload(storedData.length), 100);
            }
        }
    } catch (error) {
        logError("Erro ao preparar dados para download:", error);
    }
}

function getStoredAnalysisData() {
    try {
        const stored = localStorage.getItem('escopoSEO_analysis_data');
        return stored ? JSON.parse(stored) : [];
    } catch (error) {
        return [];
    }
}

function triggerBatchDownload(pageCount) {
    try {
        logDebug(`📊 ${pageCount} páginas analisadas - preparando download...`);
        
        const storedData = getStoredAnalysisData();
        if (storedData.length === 0) return;
        
        // Gerar CSV
        const csvContent = generateCSV(storedData);
        const timestamp = new Date().toISOString().slice(0, 16).replace(/[:T]/g, '_');
        const domain = storedData[0]?.pageData?.domain || 'unknown';
        const filename = `EscopoSEO_${domain}_${timestamp}.csv`;
        
        // Fazer download
        downloadCSV(csvContent, filename);
        
        logDebug(`✅ Download iniciado: ${filename}`);
        
    } catch (error) {
        logError("Erro no download automático:", error);
    }
}

function generateCSV(storedData) {
    const headers = [
        "URL",
        "Data da Análise",
        "Score SEO",
        "Score Conteúdo", 
        "Score IA",
        "Prioridade",
        "Problemas SEO",
        "Problemas Conteúdo",
        "Oportunidades IA",
        "Ações Prioritárias",
        "Resumo"
    ];
    
    let csv = headers.map(h => `"${h}"`).join(',') + '\n';
    
    storedData.forEach(entry => {
        try {
            const analysis = entry.analysis;
            
            const row = [
                analysis.url || entry.url,
                formatDateForCSV(entry.timestamp),
                analysis.score_seo || 0,
                analysis.score_conteudo || 0,
                analysis.score_ia || 0,
                analysis.prioridade || 'N/A',
                (analysis.problemas_seo || []).join('; '),
                (analysis.problemas_conteudo || []).join('; '),
                (analysis.oportunidades_ia || []).join('; '),
                (analysis.acoes_prioritarias || []).join('; '),
                analysis.resumo || ''
            ];
            
            csv += row.map(field => `"${String(field).replace(/"/g, '""')}"`).join(',') + '\n';
            
        } catch (error) {
            logError("Erro ao processar entrada CSV:", error);
        }
    });
    
    return csv;
}

function downloadCSV(csvContent, filename) {
    try {
        const BOM = "\uFEFF";
        const blob = new Blob([BOM + csvContent], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        
        const link = document.createElement("a");
        link.setAttribute("href", url);
        link.setAttribute("download", filename);
        link.style.visibility = 'hidden';
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        URL.revokeObjectURL(url);
        
        logDebug(`📁 Arquivo baixado: ${filename}`);
        
    } catch (error) {
        logError("Erro no download CSV:", error);
    }
}

function formatDateForCSV(isoString) {
    const date = new Date(isoString);
    return date.toLocaleDateString('pt-BR') + " " + date.toLocaleTimeString('pt-BR');
}

// =============================================================================
// FUNÇÕES UTILITÁRIAS
// =============================================================================

function logDebug(message, data = null) {
    if (CONFIG.DEBUG_MODE) {
        console.log(`[EscopoSEO] ${message}`, data || '');
    }
}

function logError(message, error) {
    console.error(`[EscopoSEO Error] ${message}`, error);
}

// =============================================================================
// FUNÇÕES GLOBAIS PARA CONTROLE MANUAL
// =============================================================================

// Disponibilizar funções globais (se não estamos no Screaming Frog)
if (typeof window !== 'undefined' && typeof seoSpider === 'undefined') {
    window.exportEscopoCSV = function() {
        try {
            const storedData = getStoredAnalysisData();
            if (storedData.length === 0) {
                console.log("❌ Nenhum dado encontrado para exportar");
                return;
            }
            
            const csvContent = generateCSV(storedData);
            const timestamp = new Date().toISOString().slice(0, 16).replace(/[:T]/g, '_');
            const filename = `EscopoSEO_Manual_Export_${timestamp}.csv`;
            
            downloadCSV(csvContent, filename);
            console.log(`✅ Export manual concluído: ${filename}`);
            
        } catch (error) {
            console.error("❌ Erro no export manual:", error);
        }
    };
    
    window.clearEscopoData = function() {
        localStorage.removeItem('escopoSEO_analysis_data');
        localStorage.removeItem('escopoSEO_last_update');
        console.log("🗑️ Dados limpos com sucesso");
    };
    
    window.getEscopoStats = function() {
        const data = getStoredAnalysisData();
        console.log(`📊 EscopoSEO Status:
- Total de páginas: ${data.length}
- Última atualização: ${localStorage.getItem('escopoSEO_last_update') || 'Nunca'}
- Para exportar: exportEscopoCSV()
- Para limpar: clearEscopoData()`);
        return data.length;
    };
}

// Log de inicialização
logDebug("🚀 EscopoSEO Analyzer inicializado", {
    version: "2.0.0",
    model: CONFIG.GEMINI_MODEL,
    api_configured: !!CONFIG.GEMINI_API_KEY && CONFIG.GEMINI_API_KEY !== "SUA_CHAVE_GEMINI_AQUI",
    fallback_enabled: CONFIG.FALLBACK_TO_BASIC
});

// =============================================================================
// VALIDAÇÃO FINAL
// =============================================================================

// Verificar se estamos no ambiente correto
if (typeof seoSpider === 'undefined' && typeof window !== 'undefined') {
    console.log(`
🚀 EscopoSEO Analyzer carregado no BROWSER

📋 Comandos disponíveis:
• getEscopoStats() - Ver estatísticas
• exportEscopoCSV() - Exportar dados manualmente
• clearEscopoData() - Limpar dados armazenados

⚠️ Para usar no Screaming Frog:
Configuration → Custom → Extraction → JavaScript
    `);
}
