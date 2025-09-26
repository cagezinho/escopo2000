/**
 * EscopoSEO Auto Downloader
 * Script automatizado para baixar relatórios CSV do EscopoSEO
 * Inspirado no sistema de SERP automation
 * 
 * COMO USAR:
 * 1. Configure seu token de acesso (opcional)
 * 2. Execute este script em uma página com dados do EscopoSEO
 * 3. O script detectará automaticamente os dados e fará o download
 */

// =============================================================================
// CONFIGURAÇÕES
// =============================================================================
const CONFIG = {
    // Token de acesso (opcional para API externa)
    ESCOPO_TOKEN: "", 
    
    // Configurações de download
    AUTO_DOWNLOAD: true,
    FILENAME_PREFIX: "EscopoSEO",
    INCLUDE_TIMESTAMP: true,
    
    // Configurações de API (opcional)
    API_ENDPOINT: "https://api.escopo-seo.com/v1/reports",
    SEND_TO_API: false,
    
    // Debug
    DEBUG: true
};

// =============================================================================
// DETECTOR DE PÁGINA ESCOPO
// =============================================================================
const EscopoDetector = {
    // Verifica se estamos em uma página com dados do EscopoSEO
    isEscopoPage() {
        return this.detectScreamingFrogData() || this.detectLocalStorageData() || this.detectDOMData();
    },

    // Detecta dados do Screaming Frog
    detectScreamingFrogData() {
        // Verifica se há indicadores do Screaming Frog na página
        const indicators = [
            'screaming-frog',
            'seo-spider',
            'custom-extraction',
            'escopeseo',
            'gemini-analysis'
        ];
        
        const pageText = document.body.innerText.toLowerCase();
        return indicators.some(indicator => pageText.includes(indicator));
    },

    // Detecta dados no localStorage
    detectLocalStorageData() {
        try {
            const keys = Object.keys(localStorage);
            const escopoKeys = keys.filter(key => 
                key.includes('escopo') || 
                key.includes('seo') || 
                key.includes('analysis') ||
                key.includes('gemini')
            );
            return escopoKeys.length > 0;
        } catch (e) {
            return false;
        }
    },

    // Detecta dados no DOM
    detectDOMData() {
        const selectors = [
            '[data-escopo]',
            '[data-seo-analysis]',
            '.escopo-data',
            '.seo-report',
            '#escopo-results'
        ];
        
        return selectors.some(selector => document.querySelector(selector));
    }
};

// =============================================================================
// EXTRATOR DE DADOS
// =============================================================================
const DataExtractor = {
    // Extrai todos os dados disponíveis
    extractAllData() {
        let data = {
            url: window.location.href,
            domain: this.getDomain(),
            timestamp: this.getFormattedDate(),
            analyses: [],
            metadata: this.extractMetadata()
        };

        // Tenta diferentes métodos de extração
        data.analyses = [
            ...this.extractFromLocalStorage(),
            ...this.extractFromDOM(),
            ...this.extractFromScreamingFrogFormat()
        ];

        return data;
    },

    // Obtém o domínio limpo
    getDomain() {
        let domain = window.location.hostname;
        return domain.replace(/^www\./, '');
    },

    // Extrai dados do localStorage
    extractFromLocalStorage() {
        const analyses = [];
        try {
            const keys = Object.keys(localStorage);
            
            keys.forEach(key => {
                if (key.includes('escopo') || key.includes('seo') || key.includes('analysis')) {
                    try {
                        const data = JSON.parse(localStorage.getItem(key));
                        if (data && typeof data === 'object') {
                            analyses.push({
                                source: 'localStorage',
                                key: key,
                                data: data
                            });
                        }
                    } catch (e) {
                        // Dados não são JSON válido
                    }
                }
            });
        } catch (e) {
            console.log('Erro ao acessar localStorage:', e);
        }
        
        return analyses;
    },

    // Extrai dados do DOM
    extractFromDOM() {
        const analyses = [];
        
        // Procura por elementos com dados do EscopoSEO
        const dataElements = document.querySelectorAll('[data-escopo], [data-seo-analysis], .escopo-data');
        
        dataElements.forEach((element, index) => {
            const analysis = {
                source: 'DOM',
                element: element.tagName,
                index: index,
                data: {}
            };

            // Extrai atributos data-*
            for (let attr of element.attributes) {
                if (attr.name.startsWith('data-')) {
                    try {
                        analysis.data[attr.name] = JSON.parse(attr.value);
                    } catch (e) {
                        analysis.data[attr.name] = attr.value;
                    }
                }
            }

            // Extrai conteúdo do elemento
            if (element.innerText) {
                analysis.data.content = element.innerText;
            }

            analyses.push(analysis);
        });

        return analyses;
    },

    // Extrai dados no formato do Screaming Frog
    extractFromScreamingFrogFormat() {
        const analyses = [];
        
        // Procura por tabelas que podem conter dados do Screaming Frog
        const tables = document.querySelectorAll('table');
        
        tables.forEach((table, tableIndex) => {
            const headers = Array.from(table.querySelectorAll('th')).map(th => th.innerText);
            
            // Verifica se é uma tabela de dados SEO
            const isSeoTable = headers.some(header => 
                header.toLowerCase().includes('url') ||
                header.toLowerCase().includes('title') ||
                header.toLowerCase().includes('analysis') ||
                header.toLowerCase().includes('score')
            );

            if (isSeoTable) {
                const rows = table.querySelectorAll('tbody tr');
                
                rows.forEach((row, rowIndex) => {
                    const cells = Array.from(row.querySelectorAll('td'));
                    const rowData = {};
                    
                    cells.forEach((cell, cellIndex) => {
                        if (headers[cellIndex]) {
                            rowData[headers[cellIndex]] = cell.innerText;
                        }
                    });

                    analyses.push({
                        source: 'table',
                        tableIndex: tableIndex,
                        rowIndex: rowIndex,
                        data: rowData
                    });
                });
            }
        });

        return analyses;
    },

    // Extrai metadados da página
    extractMetadata() {
        return {
            title: document.title,
            description: this.getMetaDescription(),
            url: window.location.href,
            domain: this.getDomain(),
            timestamp: this.getFormattedDate(),
            userAgent: navigator.userAgent
        };
    },

    // Obtém meta description
    getMetaDescription() {
        const metaDesc = document.querySelector('meta[name="description"]');
        return metaDesc ? metaDesc.getAttribute('content') : '';
    },

    // Formata data atual
    getFormattedDate() {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        return `${year}-${month}-${day}_${hours}-${minutes}`;
    }
};

// =============================================================================
// FORMATADOR CSV
// =============================================================================
const CSVFormatter = {
    // Converte dados para formato CSV
    convertToCSV(data) {
        if (!data.analyses || data.analyses.length === 0) {
            return this.createBasicCSV(data);
        }

        // Coleta todos os campos únicos
        const allFields = new Set(['url', 'domain', 'timestamp', 'source']);
        
        data.analyses.forEach(analysis => {
            if (analysis.data && typeof analysis.data === 'object') {
                Object.keys(analysis.data).forEach(key => allFields.add(key));
            }
        });

        const headers = Array.from(allFields);
        let csvContent = headers.map(header => `"${header}"`).join(',') + '\n';

        // Adiciona cada análise como uma linha
        data.analyses.forEach(analysis => {
            const row = headers.map(header => {
                let value = '';
                
                if (header === 'url') value = data.url;
                else if (header === 'domain') value = data.domain;
                else if (header === 'timestamp') value = data.timestamp;
                else if (header === 'source') value = analysis.source;
                else if (analysis.data && analysis.data[header] !== undefined) {
                    value = typeof analysis.data[header] === 'object' 
                        ? JSON.stringify(analysis.data[header])
                        : String(analysis.data[header]);
                }
                
                // Escapa aspas duplas
                value = String(value).replace(/"/g, '""');
                return `"${value}"`;
            });
            
            csvContent += row.join(',') + '\n';
        });

        return csvContent;
    },

    // Cria CSV básico quando não há dados específicos
    createBasicCSV(data) {
        const headers = ['url', 'domain', 'title', 'description', 'timestamp'];
        let csvContent = headers.map(h => `"${h}"`).join(',') + '\n';
        
        const row = [
            data.url,
            data.domain,
            data.metadata.title,
            data.metadata.description,
            data.timestamp
        ].map(value => `"${String(value).replace(/"/g, '""')}"`);
        
        csvContent += row.join(',') + '\n';
        return csvContent;
    }
};

// =============================================================================
// DOWNLOAD MANAGER
// =============================================================================
const DownloadManager = {
    // Executa o download do arquivo CSV
    downloadCSV(csvContent, filename) {
        try {
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            
            if (link.download !== undefined) {
                const url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', filename);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);
                return true;
            }
            return false;
        } catch (error) {
            console.error('Erro no download:', error);
            return false;
        }
    },

    // Gera nome do arquivo
    generateFilename(domain, timestamp) {
        return `EscopoSEO_${domain}_${timestamp}.csv`;
    },

    // Salva dados no localStorage para uso posterior
    saveToLocalStorage(data, key = null) {
        try {
            const storageKey = key || `escopo_backup_${Date.now()}`;
            localStorage.setItem(storageKey, JSON.stringify(data));
            return storageKey;
        } catch (error) {
            console.error('Erro ao salvar no localStorage:', error);
            return null;
        }
    }
};

// =============================================================================
// API CLIENT (para envio opcional dos dados)
// =============================================================================
const APIClient = {
    // Envia dados para API externa (opcional)
    async sendToAPI(data, token = CONFIG.ESCOPO_TOKEN) {
        if (!CONFIG.SEND_TO_API || !token) {
            if (CONFIG.DEBUG) console.log("📡 Envio para API desabilitado ou token não configurado");
            return null;
        }
        
        try {
            const response = await fetch(CONFIG.API_ENDPOINT, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`,
                    'X-Escopo-Client': 'Auto-Downloader',
                    'X-Escopo-Version': '1.0'
                },
                body: JSON.stringify({
                    domain: data.domain,
                    url: data.url,
                    timestamp: data.timestamp,
                    analyses: data.analyses,
                    metadata: data.metadata,
                    source: 'auto-downloader'
                })
            });

            if (!response.ok) {
                throw new Error(`API Error: ${response.status} - ${response.statusText}`);
            }

            const result = await response.json();
            if (CONFIG.DEBUG) console.log("✅ Dados enviados para API com sucesso");
            return result;
            
        } catch (error) {
            console.error('❌ Erro no envio para API:', error);
            return null;
        }
    }
};

// =============================================================================
// CONTROLADOR PRINCIPAL
// =============================================================================
const EscopoAutoDownloader = {
    // Inicializa o sistema
    async init() {
        if (CONFIG.DEBUG) console.log("🚀 EscopoSEO Auto Downloader iniciado...");
        
        try {
            // Verifica se estamos na página correta
            if (!EscopoDetector.isEscopoPage()) {
                if (CONFIG.DEBUG) console.log("❌ Página não contém dados do EscopoSEO");
                return { success: false, message: "Página não contém dados do EscopoSEO" };
            }

            if (CONFIG.DEBUG) console.log("✅ Dados do EscopoSEO detectados!");
            
            // Extrai os dados
            const data = DataExtractor.extractAllData();
            if (CONFIG.DEBUG) console.log("📊 Dados extraídos:", data);

            // Verifica se há dados para processar
            if (!data.analyses || data.analyses.length === 0) {
                if (CONFIG.DEBUG) console.log("⚠️ Nenhuma análise encontrada, gerando relatório básico");
            }

            // Converte para CSV
            const csvContent = CSVFormatter.convertToCSV(data);
            if (CONFIG.DEBUG) console.log("📄 CSV gerado");

            // Faz o download se habilitado
            let downloadSuccess = false;
            if (CONFIG.AUTO_DOWNLOAD) {
                const filename = DownloadManager.generateFilename(data.domain, data.timestamp);
                downloadSuccess = DownloadManager.downloadCSV(csvContent, filename);
                
                if (downloadSuccess) {
                    if (CONFIG.DEBUG) console.log(`✅ Download realizado: ${filename}`);
                } else {
                    console.error("❌ Erro no download");
                }
            }

            // Salva backup no localStorage
            const backupKey = DownloadManager.saveToLocalStorage(data);
            if (backupKey && CONFIG.DEBUG) {
                console.log(`💾 Backup salvo: ${backupKey}`);
            }

            // Envia para API se habilitado
            let apiResponse = null;
            if (CONFIG.SEND_TO_API) {
                if (CONFIG.DEBUG) console.log("📡 Enviando para API...");
                apiResponse = await APIClient.sendToAPI(data);
            }

            if (CONFIG.DEBUG) console.log("🎉 Processo concluído!");
            
            return {
                success: true,
                data: data,
                csvContent: csvContent,
                downloadSuccess: downloadSuccess,
                backupKey: backupKey,
                apiResponse: apiResponse
            };
            
        } catch (error) {
            console.error("❌ Erro durante execução:", error);
            return { success: false, error: error.message };
        }
    },

    // Executa automaticamente quando a página carrega
    autoRun() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.init());
        } else {
            // Aguarda um pouco para garantir que a página carregou completamente
            setTimeout(() => this.init(), 1000);
        }
    },

    // Força execução manual
    async manualRun() {
        return await this.init();
    },

    // Configura o sistema
    configure(newConfig) {
        Object.assign(CONFIG, newConfig);
        if (CONFIG.DEBUG) console.log("⚙️ Configurações atualizadas:", CONFIG);
    },

    // Obtém as configurações atuais
    getConfig() {
        return { ...CONFIG };
    }
};

// =============================================================================
// AUTO-EXECUÇÃO E FUNÇÕES GLOBAIS
// =============================================================================

// Executa automaticamente quando habilitado
if (CONFIG.AUTO_DOWNLOAD) {
    EscopoAutoDownloader.autoRun();
}

// Disponibiliza funções globais para uso manual
window.EscopoAutoDownloader = EscopoAutoDownloader;
window.downloadEscopoData = () => EscopoAutoDownloader.manualRun();
window.configureEscopo = (newConfig) => EscopoAutoDownloader.configure(newConfig);

// Funções utilitárias expostas
window.EscopoUtils = {
    detector: EscopoDetector,
    extractor: DataExtractor,
    csvFormatter: CSVFormatter,
    downloadManager: DownloadManager,
    apiClient: APIClient
};

// Log de inicialização
if (CONFIG.DEBUG) {
    console.log(`
🚀 EscopoSEO Auto Downloader v1.0 Carregado
📋 Comandos disponíveis:
   - downloadEscopoData() - Executa download manual
   - configureEscopo({...}) - Altera configurações
   - EscopoAutoDownloader.getConfig() - Ver configurações

🔧 Estado atual:
   - Auto Download: ${CONFIG.AUTO_DOWNLOAD ? '✅' : '❌'}
   - API Envio: ${CONFIG.SEND_TO_API ? '✅' : '❌'}
   - Token: ${CONFIG.ESCOPO_TOKEN ? '✅' : '❌'}
   - Debug: ${CONFIG.DEBUG ? '✅' : '❌'}
`);
}
