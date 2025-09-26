/**
 * 🚀 EscopoSEO Auto Downloader - Versão Completa
 * 
 * COMO USAR (igual ao exemplo do SAPO):
 * 1. Copie todo este código
 * 2. Cole no console da página com dados do EscopoSEO
 * 3. Pressione Enter
 * 4. O download acontece automaticamente!
 * 
 * Ou execute manualmente: downloadEscopoData()
 */

// =============================================================================
// ⚙️ CONFIGURAÇÕES (Edite aqui se necessário)
// =============================================================================

const CONFIG = {
    // Token para API externa (opcional)
    TOKEN: "",
    
    // Configurações de download
    AUTO_DOWNLOAD: true,           // Download automático ao detectar dados
    FILENAME_PREFIX: "EscopoSEO",  // Prefixo dos arquivos
    INCLUDE_TIMESTAMP: true,       // Incluir data/hora no nome
    
    // Debug (true = mostrar logs, false = silencioso)
    DEBUG: true,
    
    // API externa (opcional)
    SEND_TO_API: false,
    API_ENDPOINT: "https://api.exemplo.com/reports"
};

// =============================================================================
// 🔍 DETECTOR DE DADOS ESCOPO
// =============================================================================

const detector = {
    // Verifica se há dados do EscopoSEO na página
    temDadosEscopo() {
        return this.temDadosLocalStorage() || 
               this.temDadosDOM() || 
               this.temTabelasSEO() ||
               this.temIndicadoresScreamingFrog();
    },

    // Verifica localStorage
    temDadosLocalStorage() {
        try {
            const keys = Object.keys(localStorage);
            return keys.some(key => 
                key.includes('escopo') || 
                key.includes('seo') || 
                key.includes('analysis') ||
                key.includes('gemini') ||
                key.includes('screaming')
            );
        } catch (e) {
            return false;
        }
    },

    // Verifica DOM
    temDadosDOM() {
        const selectors = [
            '[data-escopo]',
            '[data-seo-analysis]',
            '.escopo-data',
            '.seo-report',
            '#escopo-results',
            '[data-gemini]'
        ];
        return selectors.some(selector => document.querySelector(selector));
    },

    // Verifica tabelas com dados SEO
    temTabelasSEO() {
        const tables = document.querySelectorAll('table');
        for (let table of tables) {
            const headers = Array.from(table.querySelectorAll('th')).map(th => th.innerText.toLowerCase());
            if (headers.some(h => h.includes('url') || h.includes('title') || h.includes('seo') || h.includes('score'))) {
                return true;
            }
        }
        return false;
    },

    // Verifica indicadores do Screaming Frog
    temIndicadoresScreamingFrog() {
        const texto = document.body.innerText.toLowerCase();
        const indicadores = ['screaming-frog', 'seo-spider', 'custom-extraction', 'gemini-analysis'];
        return indicadores.some(ind => texto.includes(ind));
    }
};

// =============================================================================
// 📊 EXTRATOR DE DADOS
// =============================================================================

const extrator = {
    // Extrai todos os dados disponíveis
    extrairTodos() {
        const agora = new Date();
        const timestamp = this.formatarData(agora);
        
        let dados = {
            url: window.location.href,
            domain: this.obterDominio(),
            timestamp: timestamp,
            metadata: this.extrairMetadados(),
            analyses: []
        };

        // Coleta de diferentes fontes
        dados.analyses = [
            ...this.extrairLocalStorage(),
            ...this.extrairDOM(),
            ...this.extrairTabelas()
        ];

        if (CONFIG.DEBUG) {
            console.log("📊 Dados extraídos:", dados);
        }

        return dados;
    },

    // Extrai dados do localStorage
    extrairLocalStorage() {
        const analyses = [];
        try {
            const keys = Object.keys(localStorage);
            
            keys.forEach(key => {
                if (key.includes('escopo') || key.includes('seo') || key.includes('analysis') || key.includes('gemini')) {
                    try {
                        const data = JSON.parse(localStorage.getItem(key));
                        if (data && typeof data === 'object') {
                            analyses.push({
                                fonte: 'localStorage',
                                chave: key,
                                dados: data
                            });
                        }
                    } catch (e) {
                        // Não é JSON válido, ignora
                    }
                }
            });
        } catch (e) {
            if (CONFIG.DEBUG) console.log('Erro localStorage:', e);
        }
        
        return analyses;
    },

    // Extrai dados do DOM
    extrairDOM() {
        const analyses = [];
        const elementos = document.querySelectorAll('[data-escopo], [data-seo-analysis], .escopo-data, .seo-report');
        
        elementos.forEach((elemento, index) => {
            const analysis = {
                fonte: 'DOM',
                elemento: elemento.tagName,
                index: index,
                dados: {}
            };

            // Atributos data-*
            for (let attr of elemento.attributes) {
                if (attr.name.startsWith('data-')) {
                    try {
                        analysis.dados[attr.name] = JSON.parse(attr.value);
                    } catch (e) {
                        analysis.dados[attr.name] = attr.value;
                    }
                }
            }

            // Conteúdo do elemento
            if (elemento.innerText) {
                analysis.dados.conteudo = elemento.innerText.substring(0, 500); // Limita tamanho
            }

            analyses.push(analysis);
        });

        return analyses;
    },

    // Extrai dados de tabelas
    extrairTabelas() {
        const analyses = [];
        const tabelas = document.querySelectorAll('table');
        
        tabelas.forEach((tabela, tabelaIndex) => {
            const headers = Array.from(tabela.querySelectorAll('th')).map(th => th.innerText);
            
            // Verifica se é tabela SEO
            const ehTabelaSEO = headers.some(header => {
                const h = header.toLowerCase();
                return h.includes('url') || h.includes('title') || h.includes('seo') || h.includes('score') || h.includes('analysis');
            });

            if (ehTabelaSEO) {
                const linhas = tabela.querySelectorAll('tbody tr');
                
                linhas.forEach((linha, linhaIndex) => {
                    const celulas = Array.from(linha.querySelectorAll('td'));
                    const dadosLinha = {};
                    
                    celulas.forEach((celula, celulaIndex) => {
                        if (headers[celulaIndex]) {
                            dadosLinha[headers[celulaIndex]] = celula.innerText;
                        }
                    });

                    if (Object.keys(dadosLinha).length > 0) {
                        analyses.push({
                            fonte: 'tabela',
                            tabelaIndex: tabelaIndex,
                            linhaIndex: linhaIndex,
                            dados: dadosLinha
                        });
                    }
                });
            }
        });

        return analyses;
    },

    // Extrai metadados básicos
    extrairMetadados() {
        const metaDesc = document.querySelector('meta[name="description"]');
        return {
            titulo: document.title,
            descricao: metaDesc ? metaDesc.getAttribute('content') : '',
            url: window.location.href,
            dominio: this.obterDominio(),
            userAgent: navigator.userAgent,
            timestamp: this.formatarData(new Date())
        };
    },

    // Obtém domínio limpo
    obterDominio() {
        return window.location.hostname.replace(/^www\./, '');
    },

    // Formata data para timestamp
    formatarData(data) {
        const ano = data.getFullYear();
        const mes = String(data.getMonth() + 1).padStart(2, '0');
        const dia = String(data.getDate()).padStart(2, '0');
        const hora = String(data.getHours()).padStart(2, '0');
        const min = String(data.getMinutes()).padStart(2, '0');
        return `${ano}-${mes}-${dia}_${hora}-${min}`;
    }
};

// =============================================================================
// 📄 FORMATADOR CSV
// =============================================================================

const csvFormatter = {
    // Converte dados para CSV
    paraCSV(dados) {
        if (!dados.analyses || dados.analyses.length === 0) {
            return this.csvBasico(dados);
        }

        // Coleta todos os campos únicos
        const campos = new Set(['url', 'dominio', 'timestamp', 'fonte']);
        
        dados.analyses.forEach(analysis => {
            if (analysis.dados && typeof analysis.dados === 'object') {
                this.adicionarCampos(analysis.dados, campos, '');
            }
        });

        const headers = Array.from(campos);
        let csv = headers.map(h => `"${h}"`).join(',') + '\n';

        // Adiciona linhas de dados
        dados.analyses.forEach(analysis => {
            const linha = headers.map(header => {
                let valor = '';
                
                switch (header) {
                    case 'url': valor = dados.url; break;
                    case 'dominio': valor = dados.domain; break;
                    case 'timestamp': valor = dados.timestamp; break;
                    case 'fonte': valor = analysis.fonte; break;
                    default:
                        valor = this.obterValorAninhado(analysis.dados, header);
                }
                
                // Escapa aspas e converte para string
                valor = String(valor || '').replace(/"/g, '""');
                return `"${valor}"`;
            });
            
            csv += linha.join(',') + '\n';
        });

        return csv;
    },

    // CSV básico quando não há análises específicas
    csvBasico(dados) {
        const headers = ['url', 'dominio', 'titulo', 'descricao', 'timestamp'];
        let csv = headers.map(h => `"${h}"`).join(',') + '\n';
        
        const linha = [
            dados.url,
            dados.domain,
            dados.metadata.titulo,
            dados.metadata.descricao,
            dados.timestamp
        ].map(valor => `"${String(valor || '').replace(/"/g, '""')}"`);
        
        csv += linha.join(',') + '\n';
        return csv;
    },

    // Adiciona campos de objeto aninhado
    adicionarCampos(obj, campos, prefixo) {
        Object.keys(obj).forEach(key => {
            const nomeCompleto = prefixo ? `${prefixo}.${key}` : key;
            
            if (typeof obj[key] === 'object' && obj[key] !== null && !Array.isArray(obj[key])) {
                this.adicionarCampos(obj[key], campos, nomeCompleto);
            } else {
                campos.add(nomeCompleto);
            }
        });
    },

    // Obtém valor de campo aninhado
    obterValorAninhado(obj, caminho) {
        const partes = caminho.split('.');
        let atual = obj;
        
        for (let parte of partes) {
            if (atual && typeof atual === 'object' && parte in atual) {
                atual = atual[parte];
            } else {
                return '';
            }
        }
        
        return typeof atual === 'object' ? JSON.stringify(atual) : atual;
    }
};

// =============================================================================
// ⬇️ GERENCIADOR DE DOWNLOAD
// =============================================================================

const downloadManager = {
    // Executa download do CSV
    baixarCSV(conteudoCSV, nomeArquivo) {
        try {
            const blob = new Blob([conteudoCSV], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            
            if (link.download !== undefined) {
                const url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', nomeArquivo);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);
                
                if (CONFIG.DEBUG) {
                    console.log(`✅ Download realizado: ${nomeArquivo}`);
                }
                return true;
            }
            return false;
        } catch (error) {
            console.error('❌ Erro no download:', error);
            return false;
        }
    },

    // Gera nome do arquivo
    gerarNomeArquivo(dominio, timestamp) {
        let nome = CONFIG.FILENAME_PREFIX;
        
        if (dominio) {
            nome += `_${dominio}`;
        }
        
        if (CONFIG.INCLUDE_TIMESTAMP && timestamp) {
            nome += `_${timestamp}`;
        }
        
        return `${nome}.csv`;
    },

    // Salva backup no localStorage
    salvarBackup(dados, chave = null) {
        try {
            const chaveStorage = chave || `escopo_backup_${Date.now()}`;
            localStorage.setItem(chaveStorage, JSON.stringify(dados));
            
            if (CONFIG.DEBUG) {
                console.log(`💾 Backup salvo: ${chaveStorage}`);
            }
            return chaveStorage;
        } catch (error) {
            console.error('❌ Erro ao salvar backup:', error);
            return null;
        }
    }
};

// =============================================================================
// 🌐 CLIENTE API (Opcional)
// =============================================================================

const apiClient = {
    // Envia dados para API externa
    async enviarParaAPI(dados) {
        if (!CONFIG.SEND_TO_API || !CONFIG.TOKEN) {
            if (CONFIG.DEBUG) {
                console.log("📡 Envio para API desabilitado ou token não configurado");
            }
            return null;
        }
        
        try {
            const response = await fetch(CONFIG.API_ENDPOINT, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${CONFIG.TOKEN}`,
                    'X-Escopo-Client': 'Auto-Downloader'
                },
                body: JSON.stringify({
                    dominio: dados.domain,
                    url: dados.url,
                    timestamp: dados.timestamp,
                    analyses: dados.analyses,
                    metadata: dados.metadata,
                    fonte: 'auto-downloader'
                })
            });

            if (!response.ok) {
                throw new Error(`API Error: ${response.status}`);
            }

            const resultado = await response.json();
            
            if (CONFIG.DEBUG) {
                console.log("✅ Dados enviados para API com sucesso");
            }
            return resultado;
            
        } catch (error) {
            console.error('❌ Erro no envio para API:', error);
            return null;
        }
    }
};

// =============================================================================
// 🎯 FUNÇÃO PRINCIPAL
// =============================================================================

async function downloadEscopoData() {
    if (CONFIG.DEBUG) {
        console.log("🚀 EscopoSEO Auto Downloader iniciado...");
    }

    try {
        // 1. Verifica se há dados para extrair
        if (!detector.temDadosEscopo()) {
            const msg = "❌ Nenhum dado do EscopoSEO encontrado nesta página";
            if (CONFIG.DEBUG) console.log(msg);
            return { sucesso: false, mensagem: msg };
        }

        if (CONFIG.DEBUG) {
            console.log("✅ Dados do EscopoSEO detectados!");
        }

        // 2. Extrai os dados
        const dados = extrator.extrairTodos();
        
        if (!dados.analyses || dados.analyses.length === 0) {
            if (CONFIG.DEBUG) {
                console.log("⚠️ Nenhuma análise específica encontrada, gerando relatório básico");
            }
        }

        // 3. Converte para CSV
        const csvContent = csvFormatter.paraCSV(dados);
        
        if (CONFIG.DEBUG) {
            console.log("📄 CSV gerado com sucesso");
        }

        // 4. Faz o download
        const nomeArquivo = downloadManager.gerarNomeArquivo(dados.domain, dados.timestamp);
        const downloadSucesso = downloadManager.baixarCSV(csvContent, nomeArquivo);

        // 5. Salva backup
        const chaveBackup = downloadManager.salvarBackup(dados);

        // 6. Envia para API (se configurado)
        let respostaAPI = null;
        if (CONFIG.SEND_TO_API) {
            if (CONFIG.DEBUG) console.log("📡 Enviando para API...");
            respostaAPI = await apiClient.enviarParaAPI(dados);
        }

        // 7. Resultado final
        if (CONFIG.DEBUG) {
            console.log("🎉 Processo concluído!");
        }

        return {
            sucesso: true,
            dados: dados,
            csvContent: csvContent,
            nomeArquivo: nomeArquivo,
            downloadSucesso: downloadSucesso,
            chaveBackup: chaveBackup,
            respostaAPI: respostaAPI
        };

    } catch (error) {
        console.error("❌ Erro durante execução:", error);
        return { sucesso: false, erro: error.message };
    }
}

// =============================================================================
// 🚀 AUTO-EXECUÇÃO E FUNÇÕES GLOBAIS
// =============================================================================

// Torna função global para uso manual
window.downloadEscopoData = downloadEscopoData;

// Função para configurar
window.configurarEscopo = function(novasConfigs) {
    Object.assign(CONFIG, novasConfigs);
    if (CONFIG.DEBUG) {
        console.log("⚙️ Configurações atualizadas:", CONFIG);
    }
};

// Função para ver configurações
window.verConfiguracoes = function() {
    console.log("🔧 Configurações atuais:", CONFIG);
    return CONFIG;
};

// Auto-execução se habilitado
if (CONFIG.AUTO_DOWNLOAD) {
    // Aguarda página carregar
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', downloadEscopoData);
    } else {
        setTimeout(downloadEscopoData, 1000); // Aguarda 1 segundo
    }
}

// Log de inicialização
if (CONFIG.DEBUG) {
    console.log(`
🚀 EscopoSEO Auto Downloader CARREGADO!

📋 Como usar:
   • downloadEscopoData() - Executar download
   • configurarEscopo({}) - Alterar configurações  
   • verConfiguracoes() - Ver configurações atuais

🔧 Status:
   • Auto Download: ${CONFIG.AUTO_DOWNLOAD ? '✅ ATIVO' : '❌ INATIVO'}
   • Debug: ${CONFIG.DEBUG ? '✅ ATIVO' : '❌ INATIVO'}
   • API: ${CONFIG.SEND_TO_API ? '✅ ATIVO' : '❌ INATIVO'}
   • Token: ${CONFIG.TOKEN ? '✅ CONFIGURADO' : '❌ NÃO CONFIGURADO'}

🎯 Para usar: Copie e cole este código no console de qualquer página com dados do EscopoSEO!
`);
}
