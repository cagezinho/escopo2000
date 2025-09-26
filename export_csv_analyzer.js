/**
 * EscopoSEO - Exportador CSV para Resultados da Análise IA
 * 
 * INSTRUÇÕES DE USO:
 * 1. Execute o crawl normal com o script principal
 * 2. Quando terminar, vá em Bulk Export > Custom > JavaScript 
 * 3. Marque "EscopoSEO AI Analysis"
 * 4. Exporte para CSV
 * 5. Execute este script para processar e organizar os dados
 */

// =============================================================================
// CONFIGURAÇÃO DE EXPORTAÇÃO
// =============================================================================

const EXPORT_CONFIG = {
    // Pasta onde salvar os arquivos (ajuste para seu caminho)
    OUTPUT_FOLDER: "C:/Users/nicol/Desktop/EscopoSEO_Reports/",
    
    // Nome base dos arquivos
    FILE_PREFIX: "EscopoSEO_Analysis_",
    
    // Incluir timestamp no nome do arquivo
    INCLUDE_TIMESTAMP: true,
    
    // Separar por categorias em arquivos diferentes
    SEPARATE_BY_CATEGORY: true,
    
    // Filtros de prioridade (opcional)
    PRIORITY_FILTER: [], // ["Alta", "Média"] ou [] para todos
    
    // Scores mínimos para incluir (opcional)  
    MIN_SCORES: {
        seo: 0,      // Incluir todos
        conteudo: 0, // Incluir todos
        ia: 0        // Incluir todos
    }
};

// =============================================================================
// FUNÇÃO PRINCIPAL DE PROCESSAMENTO
// =============================================================================

function processAndExportResults(rawData) {
    console.log("🔄 Iniciando processamento dos resultados da IA...");
    
    try {
        // Processar dados brutos do Screaming Frog
        const processedData = parseScreamingFrogData(rawData);
        
        // Gerar relatórios
        const reports = generateReports(processedData);
        
        // Exportar para CSV
        exportToCSV(reports);
        
        console.log("✅ Exportação concluída com sucesso!");
        
        return {
            success: true,
            files_created: reports.length,
            output_folder: EXPORT_CONFIG.OUTPUT_FOLDER
        };
        
    } catch (error) {
        console.error("❌ Erro no processamento:", error);
        return { success: false, error: error.message };
    }
}

// =============================================================================
// PROCESSAMENTO DOS DADOS
// =============================================================================

function parseScreamingFrogData(rawData) {
    const processedPages = [];
    
    // Assumindo que rawData é um array de resultados do Screaming Frog
    rawData.forEach(row => {
        try {
            // O resultado da IA vem na coluna "Custom JavaScript"
            const aiResult = JSON.parse(row.aiAnalysis || row.customJavaScript || "{}");
            
            if (aiResult.url_analisada) {
                const processedPage = {
                    url: aiResult.url_analisada,
                    priority: aiResult.prioridade_geral || "Não definida",
                    scores: {
                        seo: aiResult.score_seo || 0,
                        conteudo: aiResult.score_conteudo || 0,
                        ia: aiResult.score_ia || 0,
                        media: Math.round(((aiResult.score_seo || 0) + (aiResult.score_conteudo || 0) + (aiResult.score_ia || 0)) / 3)
                    },
                    issues: {
                        seo_tecnico: aiResult.seo_tecnico || [],
                        seo_conteudo: aiResult.seo_conteudo || [],
                        seo_ia_sge: aiResult.seo_ia_sge || []
                    },
                    resumo: aiResult.resumo_executivo || "Não disponível",
                    analyzed_at: aiResult.metadata?.analyzed_at || new Date().toISOString(),
                    
                    // Dados adicionais do Screaming Frog (se disponíveis)
                    status_code: row.statusCode || row["Status Code"] || "",
                    title: row.title || row["Title 1"] || "",
                    meta_description: row.metaDescription || row["Meta Description 1"] || "",
                    h1: row.h1 || row["H1-1"] || "",
                    word_count: row.wordCount || row["Word Count"] || 0
                };
                
                // Aplicar filtros se configurados
                if (shouldIncludePage(processedPage)) {
                    processedPages.push(processedPage);
                }
            }
        } catch (error) {
            console.warn(`⚠️  Erro ao processar linha: ${error.message}`);
        }
    });
    
    console.log(`📊 Processadas ${processedPages.length} páginas com análise IA`);
    return processedPages;
}

function shouldIncludePage(page) {
    // Filtro por prioridade
    if (EXPORT_CONFIG.PRIORITY_FILTER.length > 0) {
        if (!EXPORT_CONFIG.PRIORITY_FILTER.includes(page.priority)) {
            return false;
        }
    }
    
    // Filtro por scores mínimos
    if (page.scores.seo < EXPORT_CONFIG.MIN_SCORES.seo ||
        page.scores.conteudo < EXPORT_CONFIG.MIN_SCORES.conteudo ||
        page.scores.ia < EXPORT_CONFIG.MIN_SCORES.ia) {
        return false;
    }
    
    return true;
}

// =============================================================================
// GERAÇÃO DE RELATÓRIOS
// =============================================================================

function generateReports(processedPages) {
    const reports = [];
    
    if (EXPORT_CONFIG.SEPARATE_BY_CATEGORY) {
        // Relatório separado por categoria
        reports.push(generateSummaryReport(processedPages));
        reports.push(generateTechnicalIssuesReport(processedPages));
        reports.push(generateContentIssuesReport(processedPages));
        reports.push(generateAIOpportunitiesReport(processedPages));
        reports.push(generatePriorityReport(processedPages));
    } else {
        // Relatório único consolidado
        reports.push(generateConsolidatedReport(processedPages));
    }
    
    return reports;
}

function generateSummaryReport(pages) {
    const csvRows = [];
    
    // Header
    csvRows.push([
        "URL",
        "Prioridade",
        "Score SEO",
        "Score Conteúdo", 
        "Score IA",
        "Score Médio",
        "Status Code",
        "Título",
        "Total de Issues",
        "Resumo Executivo",
        "Data Análise"
    ]);
    
    // Data rows
    pages.forEach(page => {
        const totalIssues = page.issues.seo_tecnico.length + 
                           page.issues.seo_conteudo.length + 
                           page.issues.seo_ia_sge.length;
        
        csvRows.push([
            page.url,
            page.priority,
            page.scores.seo,
            page.scores.conteudo,
            page.scores.ia,
            page.scores.media,
            page.status_code,
            `"${page.title}"`,
            totalIssues,
            `"${page.resumo}"`,
            formatDate(page.analyzed_at)
        ]);
    });
    
    return {
        name: "Resumo_Geral",
        data: csvRows
    };
}

function generateTechnicalIssuesReport(pages) {
    const csvRows = [];
    
    // Header
    csvRows.push([
        "URL",
        "Prioridade",
        "Score SEO",
        "Problema/Oportunidade SEO Técnico",
        "Categoria",
        "Título da Página",
        "Status Code"
    ]);
    
    // Data rows
    pages.forEach(page => {
        page.issues.seo_tecnico.forEach(issue => {
            csvRows.push([
                page.url,
                page.priority,
                page.scores.seo,
                `"${issue}"`,
                "SEO Técnico",
                `"${page.title}"`,
                page.status_code
            ]);
        });
    });
    
    return {
        name: "Issues_SEO_Tecnico",
        data: csvRows
    };
}

function generateContentIssuesReport(pages) {
    const csvRows = [];
    
    // Header
    csvRows.push([
        "URL",
        "Prioridade", 
        "Score Conteúdo",
        "Problema/Oportunidade de Conteúdo",
        "Categoria",
        "Título da Página",
        "Contagem de Palavras",
        "H1"
    ]);
    
    // Data rows
    pages.forEach(page => {
        page.issues.seo_conteudo.forEach(issue => {
            csvRows.push([
                page.url,
                page.priority,
                page.scores.conteudo,
                `"${issue}"`,
                "SEO Conteúdo",
                `"${page.title}"`,
                page.word_count,
                `"${page.h1}"`
            ]);
        });
    });
    
    return {
        name: "Issues_Conteudo",
        data: csvRows
    };
}

function generateAIOpportunitiesReport(pages) {
    const csvRows = [];
    
    // Header
    csvRows.push([
        "URL",
        "Prioridade",
        "Score IA",
        "Oportunidade IA/SGE",
        "Categoria",
        "Título da Página",
        "Potencial Featured Snippet",
        "Otimização Sugerida"
    ]);
    
    // Data rows
    pages.forEach(page => {
        page.issues.seo_ia_sge.forEach(issue => {
            const hasFeaturedSnippetPotential = issue.toLowerCase().includes('featured snippet') || 
                                              issue.toLowerCase().includes('tabela') ||
                                              issue.toLowerCase().includes('lista');
            
            const optimization = categorizaAIOptimization(issue);
            
            csvRows.push([
                page.url,
                page.priority,
                page.scores.ia,
                `"${issue}"`,
                "IA/SGE",
                `"${page.title}"`,
                hasFeaturedSnippetPotential ? "Alto" : "Médio",
                optimization
            ]);
        });
    });
    
    return {
        name: "Oportunidades_IA_SGE",
        data: csvRows
    };
}

function generatePriorityReport(pages) {
    // Ordenar por prioridade e score médio
    const sortedPages = [...pages].sort((a, b) => {
        const priorityOrder = { "Alta": 3, "Média": 2, "Baixa": 1 };
        if (priorityOrder[a.priority] !== priorityOrder[b.priority]) {
            return priorityOrder[b.priority] - priorityOrder[a.priority];
        }
        return a.scores.media - b.scores.media; // Menor score = maior prioridade
    });
    
    const csvRows = [];
    
    // Header
    csvRows.push([
        "Ranking",
        "URL",
        "Prioridade",
        "Score Médio",
        "Principal Problema SEO",
        "Principal Problema Conteúdo", 
        "Principal Oportunidade IA",
        "Ação Recomendada",
        "Impacto Estimado",
        "Esforço Estimado"
    ]);
    
    // Data rows
    sortedPages.forEach((page, index) => {
        const mainSeoIssue = page.issues.seo_tecnico[0] || "Nenhum problema identificado";
        const mainContentIssue = page.issues.seo_conteudo[0] || "Nenhum problema identificado";
        const mainAIOpportunity = page.issues.seo_ia_sge[0] || "Nenhuma oportunidade identificada";
        
        const recommendedAction = getRecommendedAction(page);
        const estimatedImpact = getEstimatedImpact(page);
        const estimatedEffort = getEstimatedEffort(page);
        
        csvRows.push([
            index + 1,
            page.url,
            page.priority,
            page.scores.media,
            `"${mainSeoIssue}"`,
            `"${mainContentIssue}"`,
            `"${mainAIOpportunity}"`,
            `"${recommendedAction}"`,
            estimatedImpact,
            estimatedEffort
        ]);
    });
    
    return {
        name: "Plano_Acao_Priorizado",
        data: csvRows
    };
}

function generateConsolidatedReport(pages) {
    const csvRows = [];
    
    // Header expandido
    csvRows.push([
        "URL",
        "Prioridade",
        "Score SEO",
        "Score Conteúdo",
        "Score IA",
        "Score Médio",
        "Issues SEO Técnico",
        "Issues Conteúdo", 
        "Oportunidades IA",
        "Resumo Executivo",
        "Título",
        "Status Code",
        "Palavras",
        "Data Análise"
    ]);
    
    // Data rows
    pages.forEach(page => {
        csvRows.push([
            page.url,
            page.priority,
            page.scores.seo,
            page.scores.conteudo,
            page.scores.ia,
            page.scores.media,
            `"${page.issues.seo_tecnico.join(' | ')}"`,
            `"${page.issues.seo_conteudo.join(' | ')}"`,
            `"${page.issues.seo_ia_sge.join(' | ')}"`,
            `"${page.resumo}"`,
            `"${page.title}"`,
            page.status_code,
            page.word_count,
            formatDate(page.analyzed_at)
        ]);
    });
    
    return {
        name: "Relatorio_Consolidado",
        data: csvRows
    };
}

// =============================================================================
// EXPORTAÇÃO PARA CSV
// =============================================================================

function exportToCSV(reports) {
    const timestamp = EXPORT_CONFIG.INCLUDE_TIMESTAMP ? 
        "_" + new Date().toISOString().replace(/[:.]/g, "-").split("T")[0] : "";
    
    reports.forEach(report => {
        const filename = `${EXPORT_CONFIG.FILE_PREFIX}${report.name}${timestamp}.csv`;
        const filepath = EXPORT_CONFIG.OUTPUT_FOLDER + filename;
        
        // Converter array para CSV
        const csvContent = report.data.map(row => row.join(",")).join("\n");
        
        // Simular salvamento (em ambiente real, usaria Node.js fs)
        console.log(`📄 Gerando: ${filename}`);
        console.log(`📁 Caminho: ${filepath}`);
        console.log(`📊 Linhas: ${report.data.length - 1} (+ header)`);
        
        // Em ambiente real, salvaria assim:
        // require('fs').writeFileSync(filepath, csvContent, 'utf8');
        
        // Para o navegador, criar download
        downloadCSV(csvContent, filename);
    });
}

function downloadCSV(csvContent, filename) {
    // Criar blob com BOM para UTF-8
    const BOM = "\uFEFF";
    const blob = new Blob([BOM + csvContent], { type: 'text/csv;charset=utf-8;' });
    
    // Criar link de download
    const link = document.createElement("a");
    const url = URL.createObjectURL(blob);
    
    link.setAttribute("href", url);
    link.setAttribute("download", filename);
    link.style.visibility = 'hidden';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// =============================================================================
// FUNÇÕES AUXILIARES
// =============================================================================

function categorizaAIOptimization(issue) {
    if (issue.toLowerCase().includes('faq')) return "Implementar FAQ Schema";
    if (issue.toLowerCase().includes('tabela')) return "Criar tabela para featured snippet";
    if (issue.toLowerCase().includes('pergunta')) return "Otimizar para Q&A";
    if (issue.toLowerCase().includes('lista')) return "Estruturar em listas";
    if (issue.toLowerCase().includes('comparação')) return "Adicionar comparativos";
    return "Otimização geral para IA";
}

function getRecommendedAction(page) {
    if (page.scores.seo < 50) return "Priorizar correções técnicas";
    if (page.scores.conteudo < 50) return "Expandir e melhorar conteúdo";
    if (page.scores.ia < 50) return "Otimizar para IA/SGE";
    return "Otimizações menores";
}

function getEstimatedImpact(page) {
    if (page.priority === "Alta" && page.scores.media < 50) return "Alto";
    if (page.priority === "Alta" || page.scores.media < 70) return "Médio";
    return "Baixo";
}

function getEstimatedEffort(page) {
    const totalIssues = page.issues.seo_tecnico.length + 
                       page.issues.seo_conteudo.length + 
                       page.issues.seo_ia_sge.length;
    
    if (totalIssues > 8) return "Alto";
    if (totalIssues > 4) return "Médio";
    return "Baixo";
}

function formatDate(isoString) {
    return new Date(isoString).toLocaleDateString('pt-BR') + " " + 
           new Date(isoString).toLocaleTimeString('pt-BR');
}

// =============================================================================
// INICIALIZAÇÃO E INTERFACE
// =============================================================================

// Para uso no navegador com dados do Screaming Frog
function processScreamingFrogExport(csvData) {
    console.log("🚀 EscopoSEO - Processador de Exportação CSV");
    console.log("📁 Pasta de destino:", EXPORT_CONFIG.OUTPUT_FOLDER);
    
    const result = processAndExportResults(csvData);
    
    if (result.success) {
        console.log(`✅ ${result.files_created} arquivos gerados com sucesso!`);
        console.log("📊 Abra os CSV no Excel para análise detalhada");
    } else {
        console.error("❌ Erro:", result.error);
    }
    
    return result;
}

// Export para Node.js se disponível
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { 
        processAndExportResults,
        processScreamingFrogExport,
        EXPORT_CONFIG
    };
}

console.log("📋 EscopoSEO CSV Exporter carregado!");
console.log("💡 Use: processScreamingFrogExport(dadosDoCSV)");
