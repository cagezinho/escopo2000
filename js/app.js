// Variáveis globais
let analysisData = null;
let currentAnalysisId = null;

// Inicialização
document.addEventListener('DOMContentLoaded', function() {
    initializeEventListeners();
});

function initializeEventListeners() {
    const urlForm = document.getElementById('urlForm');
    const filters = ['technicalFilter', 'statusFilter', 'issueFilter', 'contentFilter', 'contentIssueFilter'];
    
    urlForm.addEventListener('submit', handleFormSubmit);
    
    // Event listeners para filtros
    filters.forEach(filterId => {
        const element = document.getElementById(filterId);
        if (element) {
            element.addEventListener('input', applyFilters);
            element.addEventListener('change', applyFilters);
        }
    });
}

async function handleFormSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const url = formData.get('url');
    const maxPages = formData.get('maxPages');
    const respectRobots = formData.get('respectRobots') === 'on';
    const includeExternal = formData.get('includeExternal') === 'on';
    
    if (!isValidUrl(url)) {
        showAlert('Por favor, insira uma URL válida.', 'error');
        return;
    }
    
    await startAnalysis({
        url,
        maxPages: parseInt(maxPages),
        respectRobots,
        includeExternal
    });
}

function isValidUrl(string) {
    try {
        new URL(string);
        return true;
    } catch (_) {
        return false;
    }
}

async function startAnalysis(params) {
    showProgressSection();
    disableForm();
    
    try {
        // Iniciar análise no backend
        const response = await fetch('api/start_analysis.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(params)
        });
        
        const result = await response.json();
        
        if (result.success) {
            currentAnalysisId = result.analysis_id;
            await monitorProgress();
        } else {
            throw new Error(result.message || 'Erro ao iniciar análise');
        }
    } catch (error) {
        console.error('Erro na análise:', error);
        showAlert('Erro ao iniciar análise: ' + error.message, 'error');
        enableForm();
        hideProgressSection();
    }
}

async function monitorProgress() {
    const interval = setInterval(async () => {
        try {
            const response = await fetch(`api/get_progress.php?analysis_id=${currentAnalysisId}`);
            const progress = await response.json();
            
            updateProgress(progress);
            
            if (progress.completed) {
                clearInterval(interval);
                await loadResults();
                enableForm();
                hideProgressSection();
                showResults();
            }
        } catch (error) {
            console.error('Erro ao monitorar progresso:', error);
            clearInterval(interval);
            enableForm();
            hideProgressSection();
        }
    }, 2000); // Verificar a cada 2 segundos
}

function updateProgress(progress) {
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');
    const progressPercent = document.getElementById('progressPercent');
    const progressDetails = document.getElementById('progressDetails');
    
    const percentage = Math.round((progress.current / progress.total) * 100);
    
    progressBar.style.width = percentage + '%';
    progressPercent.textContent = percentage + '%';
    progressText.textContent = progress.message || 'Analisando...';
    
    // Atualizar detalhes
    if (progress.details) {
        progressDetails.innerHTML = progress.details.map(detail => 
            `<div class="flex items-center">
                <i class="fas fa-${detail.icon || 'info-circle'} mr-2 text-${detail.color || 'blue'}-600"></i>
                ${detail.text}
            </div>`
        ).join('');
    }
}

async function loadResults() {
    try {
        const response = await fetch(`api/get_results.php?analysis_id=${currentAnalysisId}`);
        analysisData = await response.json();
        
        if (analysisData.success) {
            renderOverview();
            renderTechnicalTab();
            renderContentTab();
            renderAITab();
            updateResultsInfo();
        } else {
            throw new Error(analysisData.message || 'Erro ao carregar resultados');
        }
    } catch (error) {
        console.error('Erro ao carregar resultados:', error);
        showAlert('Erro ao carregar resultados: ' + error.message, 'error');
    }
}

function renderOverview() {
    const data = analysisData.data;
    
    // Atualizar cards de resumo
    document.getElementById('pagesOk').textContent = data.summary.pages_ok;
    document.getElementById('warnings').textContent = data.summary.warnings;
    document.getElementById('errors').textContent = data.summary.errors;
    document.getElementById('opportunities').textContent = data.summary.opportunities;
    
    // Renderizar gráficos
    renderStatusChart(data.charts.status_distribution);
    renderPerformanceChart(data.charts.performance_distribution);
}

function renderStatusChart(statusData) {
    const ctx = document.getElementById('statusChart').getContext('2d');
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: Object.keys(statusData),
            datasets: [{
                data: Object.values(statusData),
                backgroundColor: [
                    '#10b981', // 200 - Verde
                    '#f59e0b', // 301 - Amarelo
                    '#ef4444', // 404 - Vermelho
                    '#8b5cf6', // 500 - Roxo
                    '#6b7280'  // Outros - Cinza
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

function renderPerformanceChart(performanceData) {
    const ctx = document.getElementById('performanceChart').getContext('2d');
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['< 1s', '1-2s', '2-3s', '3-5s', '> 5s'],
            datasets: [{
                label: 'Páginas',
                data: performanceData,
                backgroundColor: [
                    '#10b981',
                    '#84cc16',
                    '#f59e0b',
                    '#f97316',
                    '#ef4444'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

function renderTechnicalTab() {
    const data = analysisData.data;
    
    // Renderizar resumo técnico
    renderTechnicalSummary(data.technical.summary);
    
    // Renderizar tabela de páginas
    renderTechnicalTable(data.technical.pages);
}

function renderTechnicalSummary(summary) {
    const container = document.getElementById('technicalSummary');
    
    const issues = [
        { 
            title: 'Títulos Duplicados', 
            count: summary.duplicate_titles, 
            description: 'Páginas com títulos idênticos que podem causar confusão nos resultados de busca.',
            severity: 'high'
        },
        { 
            title: 'Meta Descriptions Ausentes', 
            count: summary.missing_meta_descriptions, 
            description: 'Páginas sem meta description podem ter snippets inadequados nos resultados.',
            severity: 'medium'
        },
        { 
            title: 'Erros 404', 
            count: summary.not_found_pages, 
            description: 'Páginas que retornam erro 404 e prejudicam a experiência do usuário.',
            severity: 'high'
        },
        { 
            title: 'Páginas Lentas (>3s)', 
            count: summary.slow_pages, 
            description: 'Páginas com tempo de carregamento superior a 3 segundos.',
            severity: 'medium'
        },
        { 
            title: 'Links Quebrados', 
            count: summary.broken_links, 
            description: 'Links internos ou externos que não funcionam corretamente.',
            severity: 'high'
        },
        { 
            title: 'Páginas sem Canonical', 
            count: summary.missing_canonical, 
            description: 'Páginas que deveriam ter canonical tag para evitar conteúdo duplicado.',
            severity: 'low'
        }
    ];
    
    container.innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            ${issues.map(issue => `
                <div class="border rounded-lg p-4 ${getSeverityClass(issue.severity)}">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="font-medium text-gray-900">${issue.title}</h4>
                        <span class="text-2xl font-bold ${getSeverityTextClass(issue.severity)}">${issue.count}</span>
                    </div>
                    <p class="text-sm text-gray-600">${issue.description}</p>
                    ${issue.count > 0 ? `
                        <button onclick="filterByIssue('${issue.title.toLowerCase().replace(/\s+/g, '_')}')" 
                                class="mt-2 text-sm text-blue-600 hover:text-blue-800">
                            Ver páginas →
                        </button>
                    ` : ''}
                </div>
            `).join('')}
        </div>
    `;
}

function renderTechnicalTable(pages) {
    const tbody = document.getElementById('technicalTableBody');
    
    tbody.innerHTML = pages.map(page => `
        <tr class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900 max-w-xs truncate" title="${page.url}">
                    ${page.url}
                </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getStatusClass(page.status_code)}">
                    ${page.status_code}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                ${page.load_time}ms
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                ${formatBytes(page.page_size)}
            </td>
            <td class="px-6 py-4">
                <div class="flex flex-wrap gap-1">
                    ${page.issues.map(issue => `
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            ${issue}
                        </span>
                    `).join('')}
                </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <button onclick="showPageDetails('${page.url}')" class="text-blue-600 hover:text-blue-900">
                    Detalhes
                </button>
            </td>
        </tr>
    `).join('');
}

function renderContentTab() {
    const data = analysisData.data;
    
    renderContentOpportunities(data.content.opportunities);
    renderContentTable(data.content.pages);
}

function renderContentOpportunities(opportunities) {
    const container = document.getElementById('contentOpportunities');
    
    const contentIssues = [
        {
            title: 'Páginas sem H1',
            count: opportunities.pages_without_h1,
            description: 'Páginas que não possuem tag H1 ou possuem H1 genérico.',
            impact: 'Alto'
        },
        {
            title: 'Conteúdo Insuficiente',
            count: opportunities.short_content_pages,
            description: 'Páginas com menos de 300 palavras podem ser consideradas thin content.',
            impact: 'Médio'
        },
        {
            title: 'Títulos Genéricos',
            count: opportunities.generic_titles,
            description: 'Títulos que não refletem a intenção de busca clara.',
            impact: 'Alto'
        },
        {
            title: 'Páginas Órfãs',
            count: opportunities.orphan_pages,
            description: 'Páginas no sitemap mas sem links internos apontando para elas.',
            impact: 'Médio'
        },
        {
            title: 'Potencial para Featured Snippets',
            count: opportunities.featured_snippet_potential,
            description: 'Páginas que poderiam ser otimizadas para featured snippets.',
            impact: 'Alto'
        }
    ];
    
    container.innerHTML = `
        <div class="space-y-4">
            ${contentIssues.map(issue => `
                <div class="border-l-4 border-blue-500 bg-blue-50 p-4">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="text-lg font-medium text-blue-900">${issue.title}</h4>
                        <div class="flex items-center space-x-2">
                            <span class="text-2xl font-bold text-blue-800">${issue.count}</span>
                            <span class="text-xs px-2 py-1 bg-blue-200 text-blue-800 rounded-full">
                                Impacto ${issue.impact}
                            </span>
                        </div>
                    </div>
                    <p class="text-blue-700">${issue.description}</p>
                </div>
            `).join('')}
        </div>
    `;
}

function renderContentTable(pages) {
    const tbody = document.getElementById('contentTableBody');
    
    tbody.innerHTML = pages.map(page => `
        <tr class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900 max-w-xs truncate" title="${page.url}">
                    ${page.url}
                </div>
            </td>
            <td class="px-6 py-4">
                <div class="text-sm text-gray-900 max-w-sm truncate" title="${page.title}">
                    ${page.title || '<em>Sem título</em>'}
                </div>
                <div class="text-xs text-gray-500">
                    ${page.title_length} caracteres
                </div>
            </td>
            <td class="px-6 py-4">
                <div class="text-sm text-gray-900 max-w-sm truncate" title="${page.h1}">
                    ${page.h1 || '<em>Sem H1</em>'}
                </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                ${page.word_count}
            </td>
            <td class="px-6 py-4">
                <div class="flex flex-wrap gap-1">
                    ${page.content_opportunities.map(opp => `
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                            ${opp}
                        </span>
                    `).join('')}
                </div>
            </td>
        </tr>
    `).join('');
}

function renderAITab() {
    const data = analysisData.data;
    
    renderAIOpportunities(data.ai.opportunities);
    renderEATAnalysis(data.ai.eat_analysis);
    renderStructuredDataAnalysis(data.ai.structured_data);
}

function renderAIOpportunities(opportunities) {
    const container = document.getElementById('aiOpportunities');
    
    container.innerHTML = `
        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-gradient-to-r from-purple-50 to-indigo-50 p-6 rounded-lg border">
                    <h4 class="text-lg font-semibold text-purple-900 mb-3 flex items-center">
                        <i class="fas fa-question-circle mr-2"></i>
                        Conteúdo Q&A
                    </h4>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-purple-700">Páginas com FAQ:</span>
                            <span class="font-bold text-purple-900">${opportunities.pages_with_faq}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-purple-700">Potencial para FAQ:</span>
                            <span class="font-bold text-purple-900">${opportunities.faq_potential}</span>
                        </div>
                        <div class="text-sm text-purple-600 mt-3">
                            ${opportunities.faq_recommendations.join('. ')}
                        </div>
                    </div>
                </div>
                
                <div class="bg-gradient-to-r from-green-50 to-teal-50 p-6 rounded-lg border">
                    <h4 class="text-lg font-semibold text-green-900 mb-3 flex items-center">
                        <i class="fas fa-list-ol mr-2"></i>
                        Conteúdo Estruturado
                    </h4>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-green-700">Listas/Tutoriais:</span>
                            <span class="font-bold text-green-900">${opportunities.howto_content}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-green-700">Tabelas:</span>
                            <span class="font-bold text-green-900">${opportunities.table_content}</span>
                        </div>
                        <div class="text-sm text-green-600 mt-3">
                            ${opportunities.structured_recommendations.join('. ')}
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="bg-blue-50 p-6 rounded-lg border">
                <h4 class="text-lg font-semibold text-blue-900 mb-3 flex items-center">
                    <i class="fas fa-robot mr-2"></i>
                    Potencial para Ranqueamento em IA
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-800">${opportunities.direct_answer_potential}</div>
                        <div class="text-sm text-blue-600">Respostas Diretas</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-800">${opportunities.comparison_potential}</div>
                        <div class="text-sm text-blue-600">Comparativos</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-800">${opportunities.list_potential}</div>
                        <div class="text-sm text-blue-600">Listas/Rankings</div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function renderEATAnalysis(eatData) {
    const container = document.getElementById('eatAnalysis');
    
    const eatScore = calculateEATScore(eatData);
    
    container.innerHTML = `
        <div class="space-y-6">
            <div class="text-center p-6 bg-gray-50 rounded-lg">
                <div class="text-4xl font-bold mb-2 ${getScoreColor(eatScore)}">${eatScore}/100</div>
                <div class="text-lg text-gray-600">Pontuação E-E-A-T Geral</div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="text-center p-4 border rounded-lg">
                    <div class="text-2xl font-bold text-blue-600">${eatData.experience_score}/25</div>
                    <div class="text-sm text-gray-600">Experiência</div>
                </div>
                <div class="text-center p-4 border rounded-lg">
                    <div class="text-2xl font-bold text-green-600">${eatData.expertise_score}/25</div>
                    <div class="text-sm text-gray-600">Expertise</div>
                </div>
                <div class="text-center p-4 border rounded-lg">
                    <div class="text-2xl font-bold text-purple-600">${eatData.authoritativeness_score}/25</div>
                    <div class="text-sm text-gray-600">Autoridade</div>
                </div>
                <div class="text-center p-4 border rounded-lg">
                    <div class="text-2xl font-bold text-red-600">${eatData.trustworthiness_score}/25</div>
                    <div class="text-sm text-gray-600">Confiabilidade</div>
                </div>
            </div>
            
            <div class="space-y-4">
                <h5 class="font-semibold text-gray-800">Recomendações para Melhorar E-E-A-T:</h5>
                <ul class="space-y-2">
                    ${eatData.recommendations.map(rec => `
                        <li class="flex items-start">
                            <i class="fas fa-lightbulb text-yellow-500 mr-2 mt-1"></i>
                            <span class="text-gray-700">${rec}</span>
                        </li>
                    `).join('')}
                </ul>
            </div>
        </div>
    `;
}

function renderStructuredDataAnalysis(structuredData) {
    const container = document.getElementById('structuredDataAnalysis');
    
    container.innerHTML = `
        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="text-center p-4 bg-green-50 rounded-lg border border-green-200">
                    <div class="text-2xl font-bold text-green-600">${structuredData.total_pages_with_schema}</div>
                    <div class="text-sm text-green-700">Páginas com Schema</div>
                </div>
                <div class="text-center p-4 bg-blue-50 rounded-lg border border-blue-200">
                    <div class="text-2xl font-bold text-blue-600">${structuredData.faq_schema_count}</div>
                    <div class="text-sm text-blue-700">FAQ Schema</div>
                </div>
                <div class="text-center p-4 bg-purple-50 rounded-lg border border-purple-200">
                    <div class="text-2xl font-bold text-purple-600">${structuredData.article_schema_count}</div>
                    <div class="text-sm text-purple-700">Article Schema</div>
                </div>
                <div class="text-center p-4 bg-orange-50 rounded-lg border border-orange-200">
                    <div class="text-2xl font-bold text-orange-600">${structuredData.howto_schema_count}</div>
                    <div class="text-sm text-orange-700">HowTo Schema</div>
                </div>
            </div>
            
            <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                <h5 class="font-semibold text-yellow-800 mb-2">Oportunidades de Dados Estruturados:</h5>
                <ul class="space-y-1 text-yellow-700">
                    ${structuredData.opportunities.map(opp => `
                        <li class="flex items-start">
                            <i class="fas fa-star text-yellow-500 mr-2 mt-1"></i>
                            ${opp}
                        </li>
                    `).join('')}
                </ul>
            </div>
        </div>
    `;
}

// Funções utilitárias
function switchTab(tabName) {
    // Remover classe ativa de todas as abas
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('border-blue-600', 'text-blue-600');
        btn.classList.add('border-transparent', 'text-gray-500');
    });
    
    // Ativar aba selecionada
    document.getElementById(tabName + '-tab').classList.add('active');
    document.querySelector(`[data-tab="${tabName}"]`).classList.remove('border-transparent', 'text-gray-500');
    document.querySelector(`[data-tab="${tabName}"]`).classList.add('border-blue-600', 'text-blue-600');
}

function applyFilters() {
    // Implementar filtros específicos para cada aba
    const activeTab = document.querySelector('.tab-content.active').id;
    
    if (activeTab === 'technical-tab') {
        filterTechnicalTable();
    } else if (activeTab === 'content-tab') {
        filterContentTable();
    }
}

function filterTechnicalTable() {
    const textFilter = document.getElementById('technicalFilter').value.toLowerCase();
    const statusFilter = document.getElementById('statusFilter').value;
    const issueFilter = document.getElementById('issueFilter').value;
    
    const rows = document.querySelectorAll('#technicalTableBody tr');
    
    rows.forEach(row => {
        const url = row.cells[0].textContent.toLowerCase();
        const status = row.cells[1].textContent.trim();
        const issues = row.cells[4].textContent.toLowerCase();
        
        let show = true;
        
        if (textFilter && !url.includes(textFilter)) show = false;
        if (statusFilter && !status.includes(statusFilter)) show = false;
        if (issueFilter && !issues.includes(issueFilter.replace('_', ' '))) show = false;
        
        row.style.display = show ? '' : 'none';
    });
}

function filterContentTable() {
    const textFilter = document.getElementById('contentFilter').value.toLowerCase();
    const issueFilter = document.getElementById('contentIssueFilter').value;
    
    const rows = document.querySelectorAll('#contentTableBody tr');
    
    rows.forEach(row => {
        const url = row.cells[0].textContent.toLowerCase();
        const title = row.cells[1].textContent.toLowerCase();
        const opportunities = row.cells[4].textContent.toLowerCase();
        
        let show = true;
        
        if (textFilter && !url.includes(textFilter) && !title.includes(textFilter)) show = false;
        if (issueFilter && !opportunities.includes(issueFilter.replace('_', ' '))) show = false;
        
        row.style.display = show ? '' : 'none';
    });
}

function showPageDetails(url) {
    const pageData = analysisData.data.technical.pages.find(p => p.url === url) ||
                    analysisData.data.content.pages.find(p => p.url === url);
    
    if (!pageData) return;
    
    const modal = document.getElementById('pageModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalContent = document.getElementById('modalContent');
    
    modalTitle.textContent = 'Detalhes: ' + url;
    modalContent.innerHTML = `
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div><strong>Status HTTP:</strong> ${pageData.status_code || 'N/A'}</div>
                <div><strong>Tempo de Carregamento:</strong> ${pageData.load_time || 'N/A'}ms</div>
                <div><strong>Tamanho da Página:</strong> ${formatBytes(pageData.page_size || 0)}</div>
                <div><strong>Palavras:</strong> ${pageData.word_count || 'N/A'}</div>
            </div>
            <div>
                <strong>Título:</strong>
                <div class="mt-1 p-2 bg-gray-50 rounded text-sm">${pageData.title || 'Não definido'}</div>
            </div>
            <div>
                <strong>Meta Description:</strong>
                <div class="mt-1 p-2 bg-gray-50 rounded text-sm">${pageData.meta_description || 'Não definida'}</div>
            </div>
            <div>
                <strong>H1:</strong>
                <div class="mt-1 p-2 bg-gray-50 rounded text-sm">${pageData.h1 || 'Não definido'}</div>
            </div>
            ${pageData.issues && pageData.issues.length > 0 ? `
                <div>
                    <strong>Problemas Identificados:</strong>
                    <ul class="mt-1 space-y-1">
                        ${pageData.issues.map(issue => `<li class="text-red-600">• ${issue}</li>`).join('')}
                    </ul>
                </div>
            ` : ''}
        </div>
    `;
    
    modal.classList.remove('hidden');
}

function closeModal() {
    document.getElementById('pageModal').classList.add('hidden');
}

async function exportData(format) {
    if (!analysisData) {
        showAlert('Nenhum dado para exportar', 'warning');
        return;
    }
    
    try {
        const response = await fetch('api/export.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                analysis_id: currentAnalysisId,
                format: format
            })
        });
        
        if (response.ok) {
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `analise_seo_${new Date().toISOString().split('T')[0]}.${format}`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
            
            showAlert(`Relatório exportado em ${format.toUpperCase()}`, 'success');
        } else {
            throw new Error('Erro ao exportar dados');
        }
    } catch (error) {
        console.error('Erro na exportação:', error);
        showAlert('Erro ao exportar: ' + error.message, 'error');
    }
}

// Funções de UI
function showProgressSection() {
    document.getElementById('progressSection').classList.remove('hidden');
}

function hideProgressSection() {
    document.getElementById('progressSection').classList.add('hidden');
}

function showResults() {
    document.getElementById('resultsSection').classList.remove('hidden');
    switchTab('overview');
}

function disableForm() {
    document.getElementById('analyzeBtn').disabled = true;
    document.getElementById('analyzeBtn').innerHTML = '<div class="loading-spinner mr-2"></div>Analisando...';
}

function enableForm() {
    document.getElementById('analyzeBtn').disabled = false;
    document.getElementById('analyzeBtn').innerHTML = '<i class="fas fa-search mr-2"></i>Iniciar Análise';
}

function updateResultsInfo() {
    const data = analysisData.data;
    document.getElementById('totalPages').textContent = data.summary.total_pages;
    document.getElementById('analysisTime').textContent = formatDuration(data.summary.analysis_time);
}

function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `fixed top-4 right-4 p-4 rounded-md shadow-lg z-50 ${getAlertClass(type)}`;
    alertDiv.innerHTML = `
        <div class="flex items-center">
            <i class="fas fa-${getAlertIcon(type)} mr-3"></i>
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-lg">&times;</button>
        </div>
    `;
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        if (alertDiv.parentElement) {
            alertDiv.remove();
        }
    }, 5000);
}

// Funções utilitárias para classes CSS
function getStatusClass(status) {
    if (status >= 200 && status < 300) return 'bg-green-100 text-green-800';
    if (status >= 300 && status < 400) return 'bg-yellow-100 text-yellow-800';
    if (status >= 400 && status < 500) return 'bg-red-100 text-red-800';
    if (status >= 500) return 'bg-purple-100 text-purple-800';
    return 'bg-gray-100 text-gray-800';
}

function getSeverityClass(severity) {
    switch (severity) {
        case 'high': return 'border-red-200 bg-red-50';
        case 'medium': return 'border-yellow-200 bg-yellow-50';
        case 'low': return 'border-blue-200 bg-blue-50';
        default: return 'border-gray-200 bg-gray-50';
    }
}

function getSeverityTextClass(severity) {
    switch (severity) {
        case 'high': return 'text-red-600';
        case 'medium': return 'text-yellow-600';
        case 'low': return 'text-blue-600';
        default: return 'text-gray-600';
    }
}

function getScoreColor(score) {
    if (score >= 80) return 'text-green-600';
    if (score >= 60) return 'text-yellow-600';
    return 'text-red-600';
}

function getAlertClass(type) {
    switch (type) {
        case 'success': return 'bg-green-100 border border-green-400 text-green-700';
        case 'error': return 'bg-red-100 border border-red-400 text-red-700';
        case 'warning': return 'bg-yellow-100 border border-yellow-400 text-yellow-700';
        default: return 'bg-blue-100 border border-blue-400 text-blue-700';
    }
}

function getAlertIcon(type) {
    switch (type) {
        case 'success': return 'check-circle';
        case 'error': return 'exclamation-circle';
        case 'warning': return 'exclamation-triangle';
        default: return 'info-circle';
    }
}

// Funções de formatação
function formatBytes(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function formatDuration(seconds) {
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;
    if (minutes > 0) {
        return `${minutes}m ${remainingSeconds}s`;
    }
    return `${seconds}s`;
}

function calculateEATScore(eatData) {
    return eatData.experience_score + eatData.expertise_score + 
           eatData.authoritativeness_score + eatData.trustworthiness_score;
}
