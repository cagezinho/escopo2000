/**
 * EscopoSEO - Custom JavaScript para Screaming Frog
 * Análise Avançada de SEO Técnico + Conteúdo + IA/SGE usando Gemini API
 * 
 * INSTRUÇÕES DE USO:
 * 1. Abra Screaming Frog SEO Spider
 * 2. Vá em Configuration > Custom > Extraction
 * 3. Adicione novo extrator: JavaScript
 * 4. Cole este código
 * 5. Configure sua API key do Gemini na variável GEMINI_API_KEY
 * 6. Execute o crawl normalmente
 * 
 * RESULTADO: Cada página crawleada receberá análise completa via Gemini AI
 */

// =============================================================================
// CONFIGURAÇÃO - EDITE AQUI SUAS CONFIGURAÇÕES
// =============================================================================

const CONFIG = {
    // SUA CHAVE DA API GEMINI (obrigatório)
    GEMINI_API_KEY: "SUA_CHAVE_GEMINI_AQUI", // Substitua pela sua chave real
    
    // Configurações da API
    GEMINI_MODEL: "gemini-2.0-flash", // ou "gemini-1.5-pro" para análises mais profundas
    GEMINI_ENDPOINT: "https://generativelanguage.googleapis.com/v1beta/models/",
    
    // Configurações de análise
    MAX_CONTENT_LENGTH: 50000, // Limite de caracteres do HTML a analisar
    TIMEOUT_MS: 30000, // Timeout da requisição (30 segundos)
    RETRY_ATTEMPTS: 2, // Tentativas em caso de erro
    
    // Debug (ative para ver logs no console do SF)
    DEBUG_MODE: true,
    
    // Configurações de exportação automática
    AUTO_EXPORT: {
        ENABLED: true, // Ativar exportação automática
        SAVE_TO_LOCAL_STORAGE: true, // Salvar dados no localStorage para exportação posterior
        INCLUDE_METADATA: true, // Incluir metadados da análise
        COMPRESS_DATA: false, // Comprimir dados JSON (para sites grandes)
        
        // Configurações de download
        CUSTOM_DOWNLOAD_FOLDER: true, // Usar pasta personalizada
        DOWNLOAD_FOLDER: "C:/Users/nicol/Desktop/EscopoSEO_Reports/", // Pasta específica
        CREATE_SUBFOLDERS: true, // Criar subpastas por data
        FILENAME_PREFIX: "EscopoSEO", // Prefixo dos arquivos
        INCLUDE_TIMESTAMP: true // Incluir data no nome do arquivo
    }
};

// =============================================================================
// PROMPT PRINCIPAL PARA GEMINI
// =============================================================================

const ANALYSIS_PROMPT = `
Prompt aprimorado — especialista sênior em SEO técnico, Conteúdo e otimização para IAs (PT-BR)

Você é um especialista sênior em SEO técnico, conteúdo e otimização para ranqueamento em buscadores e IAs (Google SGE, ChatGPT, Perplexity, Gemini). Seu foco: transformar o HTML bruto de uma página web em um relatório acionável que identifique riscos, gaps e oportunidades de ranqueamento — tanto para motores tradicionais (Google) quanto para sistemas de resposta/IA.

Abaixo está a descrição detalhada do que você deve fazer, como analisar e qual saída gerar. Use-a como prompt direto para qualquer ferramenta/agent que vá receber o HTML.

INPUT

Receberá apenas o HTML bruto da página (string).

Opcionalmente pode receber também: cabeçalhos HTTP (status code, headers), URL da página e o sitemap.xml/robots.txt (se fornecido).

Se algum dado não for fornecido, explique quais checagens ficaram limitadas por falta desses dados.

ESCOPOS DE ANÁLISE (o que checar, em detalhes)
1) Saúde técnica e signals de indexação

Status HTTP (200/301/404/500). Verificar meta robots, x-robots-tag (se houver), rel=canonical.

Canonicalização (www vs non-www, https vs http, trailing slash, parâmetros, duplicatas).

Robots/meta tags: noindex, nofollow, noarchive, max-snippet etc.

Sitemap/robots (se header/URL fornecidos): consistência com sitemap/robots.

Redirecionamentos e loops (quando possível inferir do HTML ou headers).

2) Estrutura HTML e semântica

Ordem e hierarquia de headings (h1 único ideal, h2/h3 coerentes).

Uso de tags semânticas (article, main, nav, aside, section).

Tamanho do DOM e complexidade (nº de nós).

Elementos gerados via JS (conteúdo crítico renderizado via CSR vs SSR): identificar se conteúdo principal está em HTML ou precisa de execução JS para aparecer.

Links internos/externos: rel attributes (nofollow, ugc, sponsored), anchor text.

3) Metadata e Open Graph / Social

<title>: unicidade, comprimento (recomendado ~50–70 chars), presença de brand.

<meta name="description">: comprimento e se resume a intenção.

Open Graph / Twitter Card presentes e corretos (og:title, og:description, og:image, twitter:card).

Structured data (JSON-LD / RDFa / microdata): tipos presentes (Article, Product, FAQ, HowTo, BreadcrumbList, LocalBusiness, Review, VideoObject, etc.) e validade sintática.

4) Conteúdo, intenção e E-E-A-T

Qualidade do conteúdo: originalidade, profundidade (cobertura de subtemas), densidade de palavras-chave principais, presença de sinais de autoridade (autoria, referências, data, fontes).

Cobertura semântica / entidades (entidades mencionadas, sinônimos, perguntas relacionadas).

Problemas de canibalização (mesmo tópico em várias URLs) — inferir pelo conteúdo se possível.

Sugestões de tópicos faltantes / perguntas que usuários e IAs podem fazer.

5) Oportunidades específicas para IAs (SGE, LLMs, etc.)

Snippet/Answer-ready content: presença de um parágrafo curto no topo que responda à intenção em 40–70 palavras (bom para featured snippets e SGE).

FAQ/QAPage: presença de perguntas/respostas explícitas e marcado com FAQPage ou QAPage.

TL;DR / summary: bloco curto e estruturado (lista de bullets) ideal para LLM-prompts.

Trechos estruturados: tabelas, steps, bullet points — fáceis de extrair por LLMs.

Markups para entidades: JSON-LD com facts/atributos-chave (nome, data, preço, avaliações).

Microcopy para "snackable answers": identificar e sugerir 1–2 frases curtas que IAs podem usar como resposta direta.

6) Performance e Core Web Vitals (análises inferidas do HTML)

Identificar recursos que provavelmente impactam LCP, CLS, FID (imagens sem loading=lazy, imagens grandes, fonts sem font-display, scripts bloqueantes, iframes de terceiros).

Preload de LCP image / fontes / CSS críticos.

Sugestões de compressão (brotli/gzip), cache-control, uso de CDN (se headers ausentes, indique recomendação).

7) Imagens, vídeos e multimídia

alt text descritivo e único para imagens importantes.

Formatos modernos (WebP/AVIF) vs imagens JPG/PNG grandes.

srcset/sizes para responsividade.

Transcrições e captions para vídeos (para indexação e acessibilidade).

8) Acessibilidade e confiança

Presença de ARIA roles, labels, contraste de cores (se inferível), formulários com labels, botões acessíveis.

Sinais de confiança: SSL, política de privacidade, endereço/telefone (LocalBusiness), reviews, certificações.

9) Indexação dinâmica / JS

Detectar se o conteúdo essencial depende de execução JS (client-side rendering). Se sim, recomendar pre-render/SSR ou dynamic rendering e/ou snapshots para crawlers/IA.

10) Segurança / Headers

HTTPS (indicar se uso é claro no HTML), HSTS, Content-Security-Policy (se header visível), X-Frame-Options (quando aplicável).

OPORTUNIDADES A LEVANTAR (exemplos concretos)

Criar um TL;DR de 2–3 frases no topo para respostas rápidas de IA.

Adicionar ou corrigir FAQPage JSON-LD para perguntas de alto volume relacionadas.

Inserir HowTo/Product/Review schema quando aplicável (ex.: páginas de produto sem schema).

Otimizar título/meta description para intenção de busca; gerar variações A/B.

Preload LCP image e fontes; adicionar loading=lazy às imagens abaixo da dobra.

Gerar resumo de 50–70 caracteres (meta short_description) para consumo por APIs de IA.

Reescrever seções para responder 3 perguntas que SGE/LLMs comumente extraem (sugerir perguntas).

Corrigir canonical e eliminar duplicações de URL com parâmetros.

Produzir snippet JSON com mainEntity com facts (bom para SGE knowledge).

Criar blocos com passos numerados (para “how-to” e snippets).

Criar dataset de frases/trechos que podem virar prompts para Gemini/ChatGPT.

FORMATO DE SAÍDA (obrigatório)

Resumo executivo (máx. 6 linhas) — pontos altos + 3 ações prioritárias.

Checklist de achados (separado por área: técnico, conteúdo, performance, IA, schema, acessibilidade). Para cada item:

issue: descrição curta

severity: Alto / Médio / Baixo

impact: estimativa qualitativa do ganho (ex: “aumenta chance de snippet”, “melhora indexação”)

recomendação: ação exata e, quando aplicável, snippet de código antes/depois.

Oportunidades para IAs — lista priorizada (ex.: adicionar FAQ + gerar TL;DR + JSON-LD com facts).

Patch/snippets (copy-paste ready) — exemplos em HTML/JSON-LD/JS (mínimo necessário).

Checks não realizados — dizer o que não foi possível checar (ex.: headers, logs de servidor) e como obter esses dados.

JSON machine-readable — um objeto JSON com os campos chave (resumo, issues[], opportunities[], snippets[]). Exemplo (resumido):

{
  "summary":"Resumo executivo...",
  "issues":[
    {"id":"meta-01","title":"Título duplicado","severity":"Alto","recommendation":"...","snippet_before":"...","snippet_after":"..."}
  ],
  "opportunities":[
    {"id":"ai-01","title":"Adicionar TLDR","impact":"Alto","how":"..."}
  ],
  "snippets":[
    {"type":"json-ld","title":"FAQ sample","code":"{...}"}
  ]
}

SEVERITY & PRIORIDADE (como classificar)

Alto = Bloqueador para indexação / impede SERP features / afeta diretamente tráfego.

Médio = Impacto significativo em rankings/CTR, mas não bloqueador.

Baixo = Melhoria incremental ou boas práticas.

Priorize correções Alto → Médio → Baixo e forneça uma estimativa “quick-win” (baixo esforço / alto impacto).

EXEMPLOS DE SNIPPETS ÚTEIS (copiar/colar)

Canonical simples

<link rel="canonical" href="https://www.exemplo.com/pagina-exata/" />


FAQ JSON-LD

<script type="application/ld+json">
{
  "@context":"https://schema.org",
  "@type":"FAQPage",
  "mainEntity":[
    {
      "@type":"Question",
      "name":"Como funciona X?",
      "acceptedAnswer":{
        "@type":"Answer",
        "text":"Resposta curta e direta (20-40 palavras)."
      }
    }
  ]
}
</script>


Preload LCP image

<link rel="preload" as="image" href="/media/lcp-image.webp">


TL;DR — bloco no topo (HTML)

<div class="tldr" aria-label="Resumo">
  <strong>Resumo:</strong>
  <p>Frase curta que responde a intenção em 40–70 palavras.</p>
  <ul><li>Ponto 1</li><li>Ponto 2</li></ul>
</div>


WebPage JSON-LD com facts

<script type="application/ld+json">
{
  "@context":"https://schema.org",
  "@type":"WebPage",
  "mainEntity":{
    "@type":"Thing",
    "name":"Título claro",
    "description":"Resumo curto",
    "url":"https://www.exemplo.com/pagina-exata"
  }
}
</script>

SUGESTÕES DE TESTES / COMANDOS (quando possível)

curl -I https://www.exemplo.com/pagina — checar headers.

Lighthouse / PageSpeed / WebPageTest — medir Core Web Vitals.

Google Rich Results Test / Schema Validator — validar JSON-LD.

Screaming Frog + custom extraction (puxar TL;DR, FAQ, canonical, imagens LCP).

Se for automatizar via Screaming Frog custom JS, exporte: h1, meta description, first paragraph, presence of FAQ JSON-LD, tl;dr block.

REGRAS E ASSUNÇÕES

Não faça chamadas externas a menos que o agente/usuário permita — baseie-se no HTML recebido.

Quando algo for incerto (ex.: comportamento de redirecionamento), explique a suposição e como obter a confirmação.

Priorize recomendações práticas com exemplos de código e impacto estimado.

EXEMPLO DE FRASE DE INÍCIO PARA COLOCAR NO AGENT (copy/paste)

Você é um especialista sênior em SEO técnico e conteúdo. Receberá o HTML bruto da página (string). Analise-o seguindo o checklist acima (técnico, semântico, performance, schema, conteúdo e oportunidades para IAs). Gere (1) resumo executivo, (2) checklist de issues com severidade e recomendações, (3) oportunidades prioritárias para SGE/LLMs, (4) snippets de correção copy-paste e (5) um JSON final com todos os achados. Explique claramente o que não foi possível checar por falta de headers/URL/sitemap.
`;

// =============================================================================
// FUNÇÃO PRINCIPAL - EXECUTADA PELO SCREAMING FROG
// =============================================================================

function extract() {
    try {
        // Validar configuração
        if (!CONFIG.GEMINI_API_KEY || CONFIG.GEMINI_API_KEY === "SUA_CHAVE_GEMINI_AQUI") {
            return JSON.stringify({
                erro: "CONFIGURE_SUA_CHAVE_GEMINI",
                instrucoes: "Edite CONFIG.GEMINI_API_KEY no início do script"
            });
        }

        // Coletar dados básicos da página
        const pageData = collectPageData();
        
        // Preparar HTML para análise
        const htmlContent = prepareHtmlContent();
        
        // Enviar para Gemini API
        const analysis = analyzeWithGemini(htmlContent, pageData);
        
        return analysis;
        
    } catch (error) {
        logError("Erro na função extract:", error);
        return JSON.stringify({
            erro: "ERRO_EXTRACAO",
            detalhes: error.message,
            url: window.location.href
        });
    }
}

// =============================================================================
// COLETA DE DADOS DA PÁGINA
// =============================================================================

function collectPageData() {
    const data = {
        url: window.location.href,
        title: document.title || "",
        metaDescription: getMetaDescription(),
        h1: getH1Text(),
        headings: getHeadingsStructure(),
        canonical: getCanonicalUrl(),
        robots: getRobotsMetaContent(),
        structuredData: getStructuredData(),
        images: getImagesData(),
        links: getLinksData(),
        wordCount: getWordCount(),
        contentLength: document.body ? document.body.innerText.length : 0,
        timestamp: new Date().toISOString()
    };
    
    logDebug("Dados coletados:", data);
    return data;
}

function getMetaDescription() {
    const meta = document.querySelector('meta[name="description"]');
    return meta ? meta.getAttribute('content') : "";
}

function getH1Text() {
    const h1 = document.querySelector('h1');
    return h1 ? h1.innerText.trim() : "";
}

function getHeadingsStructure() {
    const headings = {};
    for (let i = 1; i <= 6; i++) {
        const elements = document.querySelectorAll(`h${i}`);
        headings[`h${i}`] = {
            count: elements.length,
            texts: Array.from(elements).slice(0, 3).map(el => el.innerText.trim().substring(0, 100))
        };
    }
    return headings;
}

function getCanonicalUrl() {
    const canonical = document.querySelector('link[rel="canonical"]');
    return canonical ? canonical.getAttribute('href') : "";
}

function getRobotsMetaContent() {
    const robots = document.querySelector('meta[name="robots"]');
    return robots ? robots.getAttribute('content') : "";
}

function getStructuredData() {
    const scripts = document.querySelectorAll('script[type="application/ld+json"]');
    const structuredData = [];
    
    scripts.forEach(script => {
        try {
            const data = JSON.parse(script.textContent);
            if (data['@type']) {
                structuredData.push(data['@type']);
            }
        } catch (e) {
            // Ignora JSON inválido
        }
    });
    
    return structuredData;
}

function getImagesData() {
    const images = document.querySelectorAll('img');
    let withoutAlt = 0;
    let total = images.length;
    
    images.forEach(img => {
        if (!img.getAttribute('alt') || img.getAttribute('alt').trim() === '') {
            withoutAlt++;
        }
    });
    
    return { total, withoutAlt };
}

function getLinksData() {
    const links = document.querySelectorAll('a[href]');
    let internal = 0;
    let external = 0;
    const currentDomain = window.location.hostname;
    
    links.forEach(link => {
        const href = link.getAttribute('href');
        if (href.startsWith('http')) {
            const linkDomain = new URL(href).hostname;
            if (linkDomain === currentDomain) {
                internal++;
            } else {
                external++;
            }
        } else if (href.startsWith('/') || !href.includes('://')) {
            internal++;
        }
    });
    
    return { internal, external, total: links.length };
}

function getWordCount() {
    const content = document.body ? document.body.innerText : "";
    return content.trim().split(/\s+/).filter(word => word.length > 0).length;
}

// =============================================================================
// PREPARAÇÃO DO HTML PARA ANÁLISE
// =============================================================================

function prepareHtmlContent() {
    let html = document.documentElement.outerHTML;
    
    // Limpar scripts e estilos para reduzir tamanho
    html = html.replace(/<script[^>]*>[\s\S]*?<\/script>/gi, '');
    html = html.replace(/<style[^>]*>[\s\S]*?<\/style>/gi, '');
    html = html.replace(/<!--[\s\S]*?-->/g, '');
    
    // Limitar tamanho do conteúdo
    if (html.length > CONFIG.MAX_CONTENT_LENGTH) {
        html = html.substring(0, CONFIG.MAX_CONTENT_LENGTH) + "... [CONTEÚDO TRUNCADO]";
    }
    
    return html;
}

// =============================================================================
// ANÁLISE COM GEMINI API
// =============================================================================

function analyzeWithGemini(htmlContent, pageData) {
    const url = `${CONFIG.GEMINI_ENDPOINT}${CONFIG.GEMINI_MODEL}:generateContent?key=${CONFIG.GEMINI_API_KEY}`;
    
    const requestBody = {
        contents: [{
            parts: [{
                text: `${ANALYSIS_PROMPT}\n\nDADOS DA PÁGINA:\n${JSON.stringify(pageData, null, 2)}\n\nHTML DA PÁGINA:\n${htmlContent}`
            }]
        }],
        generationConfig: {
            temperature: 0.1,
            topK: 1,
            topP: 1,
            maxOutputTokens: 8192,
        },
        safetySettings: [
            {
                category: "HARM_CATEGORY_HARASSMENT",
                threshold: "BLOCK_MEDIUM_AND_ABOVE"
            },
            {
                category: "HARM_CATEGORY_HATE_SPEECH", 
                threshold: "BLOCK_MEDIUM_AND_ABOVE"
            },
            {
                category: "HARM_CATEGORY_SEXUALLY_EXPLICIT",
                threshold: "BLOCK_MEDIUM_AND_ABOVE"
            },
            {
                category: "HARM_CATEGORY_DANGEROUS_CONTENT",
                threshold: "BLOCK_MEDIUM_AND_ABOVE"
            }
        ]
    };

    let attempts = 0;
    while (attempts < CONFIG.RETRY_ATTEMPTS) {
        try {
            logDebug(`Tentativa ${attempts + 1} de análise com Gemini para: ${pageData.url}`);
            
            const xhr = new XMLHttpRequest();
            xhr.open('POST', url, false); // Síncrono para funcionar no Screaming Frog
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.timeout = CONFIG.TIMEOUT_MS;
            
            xhr.send(JSON.stringify(requestBody));
            
            if (xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                
                if (response.candidates && response.candidates[0] && response.candidates[0].content) {
                    const analysisText = response.candidates[0].content.parts[0].text;
                    
                    // Extrair JSON da resposta
                    const jsonMatch = analysisText.match(/\{[\s\S]*\}/);
                    if (jsonMatch) {
                        const analysisJson = JSON.parse(jsonMatch[0]);
                        
                        // Adicionar metadados
                        analysisJson.metadata = {
                            analyzed_at: new Date().toISOString(),
                            gemini_model: CONFIG.GEMINI_MODEL,
                            word_count: pageData.wordCount,
                            page_size: htmlContent.length
                        };
                        
                        logDebug("Análise concluída com sucesso:", analysisJson);
                        return JSON.stringify(analysisJson);
                    }
                }
            }
            
            throw new Error(`HTTP ${xhr.status}: ${xhr.statusText}`);
            
        } catch (error) {
            attempts++;
            logError(`Erro na tentativa ${attempts}:`, error);
            
            if (attempts >= CONFIG.RETRY_ATTEMPTS) {
                return JSON.stringify({
                    erro: "FALHA_GEMINI_API",
                    detalhes: error.message,
                    url: pageData.url,
                    tentativas: attempts
                });
            }
            
            // Aguardar antes de tentar novamente
            const delay = 1000 * attempts;
            const start = Date.now();
            while (Date.now() - start < delay) {
                // Delay síncrono
            }
        }
    }
}

// =============================================================================
// FUNÇÕES UTILITÁRIAS
// =============================================================================

function logDebug(message, data = null) {
    if (CONFIG.DEBUG_MODE) {
        console.log(`[EscopoSEO Debug] ${message}`, data);
    }
}

function logError(message, error) {
    console.error(`[EscopoSEO Error] ${message}`, error);
}

// =============================================================================
// VALIDAÇÃO E INICIALIZAÇÃO
// =============================================================================

// Validar ambiente Screaming Frog
if (typeof window === 'undefined') {
    throw new Error("Este script deve ser executado no Screaming Frog SEO Spider");
}

// Validar configuração
if (!CONFIG.GEMINI_API_KEY || CONFIG.GEMINI_API_KEY.includes("SUA_CHAVE")) {
    console.error(`
    ❌ CONFIGURAÇÃO NECESSÁRIA:
    
    1. Obtenha sua API key do Google AI Studio:
       https://makersuite.google.com/app/apikey
    
    2. Substitua CONFIG.GEMINI_API_KEY no início deste script
    
    3. Configure o Screaming Frog:
       Configuration > Custom > Extraction > JavaScript
    
    4. Cole este script modificado
    
    5. Execute o crawl
    `);
}

// Log de inicialização
logDebug("EscopoSEO Custom JavaScript inicializado", {
    version: "1.0.0",
    model: CONFIG.GEMINI_MODEL,
    debug: CONFIG.DEBUG_MODE,
    max_content: CONFIG.MAX_CONTENT_LENGTH
});

// =============================================================================
// SISTEMA DE COLETA E EXPORTAÇÃO DE DADOS
// =============================================================================

// Armazenar dados da análise para exportação posterior
function storeAnalysisData(analysisResult, pageData) {
    if (!CONFIG.AUTO_EXPORT.ENABLED) return;
    
    try {
        const storedData = getStoredAnalysisData();
        const analysisEntry = {
            url: pageData.url,
            timestamp: new Date().toISOString(),
            analysis: analysisResult,
            pageData: CONFIG.AUTO_EXPORT.INCLUDE_METADATA ? pageData : null
        };
        
        storedData.push(analysisEntry);
        
        // Salvar no localStorage
        if (CONFIG.AUTO_EXPORT.SAVE_TO_LOCAL_STORAGE) {
            const dataToStore = CONFIG.AUTO_EXPORT.COMPRESS_DATA ? 
                LZString.compress(JSON.stringify(storedData)) : 
                JSON.stringify(storedData);
            
            localStorage.setItem('escopoSEO_analysis_data', dataToStore);
            localStorage.setItem('escopoSEO_last_update', new Date().toISOString());
        }
        
        logDebug(`Dados armazenados para: ${pageData.url} (Total: ${storedData.length} páginas)`);
        
        // Trigger automático de exportação a cada 50 páginas
        if (storedData.length % 50 === 0) {
            triggerProgressExport(storedData.length);
        }
        
    } catch (error) {
        logError("Erro ao armazenar dados:", error);
    }
}

function getStoredAnalysisData() {
    try {
        const stored = localStorage.getItem('escopoSEO_analysis_data');
        if (!stored) return [];
        
        const data = CONFIG.AUTO_EXPORT.COMPRESS_DATA ? 
            JSON.parse(LZString.decompress(stored)) : 
            JSON.parse(stored);
            
        return Array.isArray(data) ? data : [];
    } catch (error) {
        logError("Erro ao recuperar dados armazenados:", error);
        return [];
    }
}

function triggerProgressExport(pageCount) {
    console.log(`🔄 EscopoSEO: ${pageCount} páginas analisadas. Dados prontos para exportação.`);
    console.log("💡 Para exportar agora, execute: exportAnalysisToCSV()");
}

// =============================================================================
// FUNÇÕES DE EXPORTAÇÃO MANUAL
// =============================================================================

function exportAnalysisToCSV() {
    const storedData = getStoredAnalysisData();
    
    if (storedData.length === 0) {
        console.log("❌ Nenhum dado de análise encontrado para exportar.");
        return;
    }
    
    console.log(`📊 Exportando ${storedData.length} análises para CSV...`);
    
    // Gerar CSV de resumo
    const summaryCSV = generateSummaryCSV(storedData);
    downloadCSV(summaryCSV, `EscopoSEO_Resumo_${getCurrentDateString()}.csv`);
    
    // Gerar CSV de issues detalhados
    const issuesCSV = generateIssuesCSV(storedData);
    downloadCSV(issuesCSV, `EscopoSEO_Issues_Detalhados_${getCurrentDateString()}.csv`);
    
    // Gerar CSV de oportunidades IA
    const aiOpportunitiesCSV = generateAIOpportunitiesCSV(storedData);
    downloadCSV(aiOpportunitiesCSV, `EscopoSEO_Oportunidades_IA_${getCurrentDateString()}.csv`);
    
    console.log("✅ Exportação concluída! 3 arquivos CSV baixados.");
    
    // Gerar script de organização se pasta personalizada configurada
    if (CONFIG.AUTO_EXPORT.CUSTOM_DOWNLOAD_FOLDER) {
        generateMoveScript();
    }
}

function generateSummaryCSV(storedData) {
    const headers = [
        "URL",
        "Data da Análise", 
        "Prioridade",
        "Score SEO",
        "Score Conteúdo",
        "Score IA",
        "Score Médio",
        "Total de Issues",
        "Issues SEO Técnico",
        "Issues Conteúdo",
        "Issues IA/SGE",
        "Resumo Executivo"
    ];
    
    const rows = [headers];
    
    storedData.forEach(entry => {
        try {
            const analysis = typeof entry.analysis === 'string' ? 
                JSON.parse(entry.analysis) : entry.analysis;
            
            if (!analysis || analysis.erro) return;
            
            const seoIssues = (analysis.seo_tecnico || []).length;
            const contentIssues = (analysis.seo_conteudo || []).length;  
            const aiIssues = (analysis.seo_ia_sge || []).length;
            const totalIssues = seoIssues + contentIssues + aiIssues;
            
            const avgScore = Math.round(((analysis.score_seo || 0) + 
                                       (analysis.score_conteudo || 0) + 
                                       (analysis.score_ia || 0)) / 3);
            
            rows.push([
                `"${analysis.url_analisada || entry.url}"`,
                formatDateForCSV(entry.timestamp),
                analysis.prioridade_geral || "Não definida",
                analysis.score_seo || 0,
                analysis.score_conteudo || 0,
                analysis.score_ia || 0,
                avgScore,
                totalIssues,
                seoIssues,
                contentIssues,
                aiIssues,
                `"${(analysis.resumo_executivo || "").replace(/"/g, '""')}"`
            ]);
        } catch (error) {
            logError("Erro ao processar entrada para CSV:", error);
        }
    });
    
    return rows.map(row => row.join(",")).join("\n");
}

function generateIssuesCSV(storedData) {
    const headers = [
        "URL",
        "Data da Análise",
        "Categoria", 
        "Problema/Oportunidade",
        "Prioridade da Página",
        "Score da Categoria"
    ];
    
    const rows = [headers];
    
    storedData.forEach(entry => {
        try {
            const analysis = typeof entry.analysis === 'string' ? 
                JSON.parse(entry.analysis) : entry.analysis;
            
            if (!analysis || analysis.erro) return;
            
            const url = analysis.url_analisada || entry.url;
            const date = formatDateForCSV(entry.timestamp);
            const priority = analysis.prioridade_geral || "Não definida";
            
            // Issues SEO Técnico
            (analysis.seo_tecnico || []).forEach(issue => {
                rows.push([
                    `"${url}"`,
                    date,
                    "SEO Técnico",
                    `"${issue.replace(/"/g, '""')}"`,
                    priority,
                    analysis.score_seo || 0
                ]);
            });
            
            // Issues Conteúdo
            (analysis.seo_conteudo || []).forEach(issue => {
                rows.push([
                    `"${url}"`,
                    date,
                    "Conteúdo",
                    `"${issue.replace(/"/g, '""')}"`,
                    priority,
                    analysis.score_conteudo || 0
                ]);
            });
            
            // Issues IA/SGE
            (analysis.seo_ia_sge || []).forEach(issue => {
                rows.push([
                    `"${url}"`,
                    date,
                    "IA/SGE",
                    `"${issue.replace(/"/g, '""')}"`,
                    priority,
                    analysis.score_ia || 0
                ]);
            });
            
        } catch (error) {
            logError("Erro ao processar issues para CSV:", error);
        }
    });
    
    return rows.map(row => row.join(",")).join("\n");
}

function generateAIOpportunitiesCSV(storedData) {
    const headers = [
        "URL",
        "Oportunidade IA/SGE",
        "Tipo de Otimização",
        "Prioridade",
        "Score IA",
        "Potencial Featured Snippet",
        "Ação Recomendada"
    ];
    
    const rows = [headers];
    
    storedData.forEach(entry => {
        try {
            const analysis = typeof entry.analysis === 'string' ? 
                JSON.parse(entry.analysis) : entry.analysis;
            
            if (!analysis || analysis.erro) return;
            
            const url = analysis.url_analisada || entry.url;
            
            (analysis.seo_ia_sge || []).forEach(opportunity => {
                const optimizationType = categorizeAIOptimization(opportunity);
                const featuredSnippetPotential = hasFeaturedSnippetPotential(opportunity);
                const recommendedAction = getRecommendedAIAction(opportunity);
                
                rows.push([
                    `"${url}"`,
                    `"${opportunity.replace(/"/g, '""')}"`,
                    optimizationType,
                    analysis.prioridade_geral || "Não definida",
                    analysis.score_ia || 0,
                    featuredSnippetPotential,
                    `"${recommendedAction}"`
                ]);
            });
            
        } catch (error) {
            logError("Erro ao processar oportunidades IA para CSV:", error);
        }
    });
    
    return rows.map(row => row.join(",")).join("\n");
}

// =============================================================================
// FUNÇÕES AUXILIARES DE EXPORTAÇÃO
// =============================================================================

function categorizeAIOptimization(opportunity) {
    const opp = opportunity.toLowerCase();
    if (opp.includes('faq')) return "FAQ Schema";
    if (opp.includes('tabela')) return "Tabela/Featured Snippet";
    if (opp.includes('lista')) return "Lista Estruturada";
    if (opp.includes('pergunta')) return "Formato Q&A";
    if (opp.includes('comparação') || opp.includes('versus')) return "Comparativo";
    if (opp.includes('passo') || opp.includes('tutorial')) return "HowTo Schema";
    if (opp.includes('e-e-a-t')) return "Autoridade/Confiabilidade";
    return "Otimização Geral";
}

function hasFeaturedSnippetPotential(opportunity) {
    const opp = opportunity.toLowerCase();
    const triggers = ['tabela', 'lista', 'passo', 'definição', 'o que é', 'como'];
    return triggers.some(trigger => opp.includes(trigger)) ? "Alto" : "Médio";
}

function getRecommendedAIAction(opportunity) {
    const opp = opportunity.toLowerCase();
    if (opp.includes('faq')) return "Implementar schema FAQPage";
    if (opp.includes('tabela')) return "Criar tabela comparativa";
    if (opp.includes('lista')) return "Estruturar em bullet points";
    if (opp.includes('pergunta')) return "Adicionar seção Q&A";
    if (opp.includes('autor')) return "Incluir informações do autor";
    if (opp.includes('data')) return "Adicionar datas de publicação/atualização";
    return "Revisar e otimizar conteúdo para IA";
}

function downloadCSV(csvContent, filename) {
    // Adicionar BOM para UTF-8
    const BOM = "\uFEFF";
    const blob = new Blob([BOM + csvContent], { type: 'text/csv;charset=utf-8;' });
    
    const link = document.createElement("a");
    const url = URL.createObjectURL(blob);
    
    // Aplicar configurações de download personalizadas
    const finalFilename = applyDownloadSettings(filename);
    
    link.setAttribute("href", url);
    link.setAttribute("download", finalFilename);
    link.style.visibility = 'hidden';
    
    // Tentar definir pasta de download (limitado pelo navegador)
    if (CONFIG.AUTO_EXPORT.CUSTOM_DOWNLOAD_FOLDER) {
        logDownloadInfo(finalFilename, CONFIG.AUTO_EXPORT.DOWNLOAD_FOLDER);
    }
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    URL.revokeObjectURL(url);
}

function applyDownloadSettings(originalFilename) {
    const config = CONFIG.AUTO_EXPORT;
    let filename = originalFilename;
    
    // Aplicar prefixo personalizado
    if (config.FILENAME_PREFIX && config.FILENAME_PREFIX !== "EscopoSEO") {
        filename = filename.replace("EscopoSEO", config.FILENAME_PREFIX);
    }
    
    // Adicionar subfolder por data se configurado
    if (config.CREATE_SUBFOLDERS) {
        const dateFolder = getCurrentDateString();
        // Navegador não permite criar pastas, mas podemos incluir no nome
        filename = `${dateFolder}_${filename}`;
    }
    
    return filename;
}

function logDownloadInfo(filename, folder) {
    console.log(`📁 Configuração de Download:`);
    console.log(`   Arquivo: ${filename}`);
    console.log(`   Pasta desejada: ${folder}`);
    console.log(`⚠️  NOTA: O navegador baixará na pasta padrão de Downloads.`);
    console.log(`   Para usar pasta personalizada, mova os arquivos após download.`);
    console.log(`   Ou configure seu navegador para sempre perguntar onde salvar.`);
}

function formatDateForCSV(isoString) {
    const date = new Date(isoString);
    return date.toLocaleDateString('pt-BR') + " " + date.toLocaleTimeString('pt-BR');
}

function getCurrentDateString() {
    return new Date().toISOString().split('T')[0];
}

function generateMoveScript() {
    const config = CONFIG.AUTO_EXPORT;
    const dateStr = getCurrentDateString();
    const downloadFolder = "C:\\Users\\nicol\\Downloads\\"; // Pasta padrão do Windows
    const targetFolder = config.DOWNLOAD_FOLDER.replace(/\//g, '\\');
    
    // Script batch para Windows
    const batchScript = `@echo off
echo 🚀 EscopoSEO - Organizador de Arquivos CSV
echo.
echo Movendo arquivos de análise para pasta personalizada...
echo.

REM Criar pasta de destino se não existir
if not exist "${targetFolder}" (
    mkdir "${targetFolder}"
    echo ✅ Pasta criada: ${targetFolder}
)

REM Criar subpasta por data se configurado
${config.CREATE_SUBFOLDERS ? `
if not exist "${targetFolder}${dateStr}\\" (
    mkdir "${targetFolder}${dateStr}\\"
    echo ✅ Subpasta criada: ${targetFolder}${dateStr}\\
)
set TARGET_DIR=${targetFolder}${dateStr}\\
` : `set TARGET_DIR=${targetFolder}`}

REM Mover arquivos EscopoSEO
echo.
echo Movendo arquivos...
move "${downloadFolder}${config.CREATE_SUBFOLDERS ? dateStr + '_' : ''}EscopoSEO_*.csv" "%TARGET_DIR%" 2>nul
if errorlevel 1 (
    echo ⚠️  Nenhum arquivo EscopoSEO encontrado em Downloads
) else (
    echo ✅ Arquivos movidos com sucesso!
)

echo.
echo 📁 Arquivos organizados em: %TARGET_DIR%
echo.
pause`;

    // Fazer download do script batch
    const blob = new Blob([batchScript], { type: 'text/plain;charset=utf-8;' });
    const link = document.createElement("a");
    const url = URL.createObjectURL(blob);
    
    link.setAttribute("href", url);
    link.setAttribute("download", `EscopoSEO_Organizar_Arquivos_${dateStr}.bat`);
    link.style.visibility = 'hidden';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    URL.revokeObjectURL(url);
    
    console.log(`📜 Script batch gerado: EscopoSEO_Organizar_Arquivos_${dateStr}.bat`);
    console.log(`💡 Execute este arquivo .bat para mover os CSV para a pasta configurada.`);
}

// =============================================================================
// COMANDOS DISPONÍVEIS NO CONSOLE
// =============================================================================

// Adicionar funções globais para uso no console do Screaming Frog
window.exportAnalysisToCSV = exportAnalysisToCSV;
window.clearStoredAnalysis = function() {
    localStorage.removeItem('escopoSEO_analysis_data');
    localStorage.removeItem('escopoSEO_last_update');
    console.log("🗑️ Dados de análise limpos do armazenamento local.");
};
window.getAnalysisStats = function() {
    const data = getStoredAnalysisData();
    console.log(`📊 EscopoSEO Stats:
- Total de páginas analisadas: ${data.length}
- Última atualização: ${localStorage.getItem('escopoSEO_last_update') || 'Nunca'}
- Para exportar: exportAnalysisToCSV()
- Para limpar dados: clearStoredAnalysis()`);
    return data.length;
};

// Modificar a função analyzeWithGemini para incluir armazenamento
const originalAnalyzeWithGemini = analyzeWithGemini;
analyzeWithGemini = function(htmlContent, pageData) {
    const result = originalAnalyzeWithGemini(htmlContent, pageData);
    
    // Armazenar dados se a análise foi bem sucedida
    if (CONFIG.AUTO_EXPORT.ENABLED && result && !result.includes('"erro"')) {
        storeAnalysisData(result, pageData);
    }
    
    return result;
};

// Export da função principal (necessário para o Screaming Frog)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { extract, exportAnalysisToCSV, getStoredAnalysisData };
}
