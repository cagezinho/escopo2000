# 🚀 EscopoSEO Auto Downloader

## 📋 Descrição

Script automatizado para detectar e baixar dados do EscopoSEO diretamente de páginas web. Funciona de forma similar ao exemplo que você forneceu, mas focado na extração e download de relatórios CSV do seu sistema de análise SEO.

## ✨ Funcionalidades

- ✅ **Detecção Automática** - Identifica páginas com dados do EscopoSEO
- ✅ **Extração Inteligente** - Coleta dados de múltiplas fontes (DOM, localStorage, tabelas)
- ✅ **Download Automático** - Gera e baixa arquivos CSV automaticamente
- ✅ **Backup Local** - Salva dados no localStorage como backup
- ✅ **API Integration** - Opção de enviar dados para API externa
- ✅ **Configurável** - Múltiplas opções de configuração
- ✅ **Debug Mode** - Logs detalhados para troubleshooting

## 🛠️ Como Usar

### 1. **Configuração Básica**

```javascript
// Edite as configurações no início do arquivo
const CONFIG = {
    ESCOPO_TOKEN: "seu_token_aqui", // Opcional
    AUTO_DOWNLOAD: true,            // Download automático
    FILENAME_PREFIX: "EscopoSEO",   // Prefixo dos arquivos
    DEBUG: true                     // Mostrar logs
};
```

### 2. **Carregamento do Script**

#### **Opção A: Console do Browser**
1. Abra a página com dados do EscopoSEO
2. Pressione `F12` para abrir DevTools
3. Vá na aba `Console`
4. Cole todo o conteúdo do arquivo `escopo_auto_downloader.js`
5. Pressione `Enter`

#### **Opção B: Bookmarklet**
1. Crie um novo bookmark
2. Cole o script minificado como URL do bookmark
3. Clique no bookmark quando estiver na página com dados

#### **Opção C: Extensão de Browser**
1. Use uma extensão como "User JavaScript and CSS"
2. Configure para carregar o script automaticamente em páginas específicas

### 3. **Execução**

#### **Automática:**
- O script roda automaticamente quando detecta dados do EscopoSEO
- Download é feito automaticamente se `AUTO_DOWNLOAD: true`

#### **Manual:**
```javascript
// Executar download manual
downloadEscopoData()

// Ou usando o objeto principal
EscopoAutoDownloader.manualRun()
```

## ⚙️ Configurações Avançadas

### **Configuração durante execução:**
```javascript
// Alterar configurações
configureEscopo({
    AUTO_DOWNLOAD: false,
    SEND_TO_API: true,
    ESCOPO_TOKEN: "novo_token",
    DEBUG: false
});

// Ver configurações atuais
EscopoAutoDownloader.getConfig()
```

### **Configurações disponíveis:**

| Configuração | Tipo | Default | Descrição |
|-------------|------|---------|-----------|
| `ESCOPO_TOKEN` | string | "" | Token para API externa |
| `AUTO_DOWNLOAD` | boolean | true | Download automático de CSV |
| `FILENAME_PREFIX` | string | "EscopoSEO" | Prefixo dos arquivos |
| `INCLUDE_TIMESTAMP` | boolean | true | Incluir timestamp no nome |
| `API_ENDPOINT` | string | URL | Endpoint da API externa |
| `SEND_TO_API` | boolean | false | Enviar dados para API |
| `DEBUG` | boolean | true | Mostrar logs de debug |

## 📊 Tipos de Dados Detectados

### **1. localStorage**
- Dados salvos pelo Screaming Frog
- Análises em cache
- Configurações do EscopoSEO

### **2. DOM Elements**
- Elementos com `data-escopo`
- Elementos com `data-seo-analysis`
- Classes `.escopo-data`, `.seo-report`

### **3. Tabelas HTML**
- Tabelas com dados de URL, títulos, análises
- Formato de exportação do Screaming Frog
- Relatórios em formato tabular

### **4. Metadados da Página**
- URL atual
- Título e description
- Informações do domínio
- Timestamp da extração

## 📁 Formato dos Arquivos

### **Nome do Arquivo:**
```
EscopoSEO_dominio.com_2024-01-15_14-30.csv
```

### **Estrutura CSV:**
```csv
"url","domain","timestamp","source","title","analysis_score","recommendations"
"https://exemplo.com","exemplo.com","2024-01-15_14-30","localStorage","Título da Página","85","Otimizar meta description"
```

## 🔍 Troubleshooting

### **Script não detecta dados:**
```javascript
// Verificar detecção manual
EscopoUtils.detector.isEscopoPage()
EscopoUtils.detector.detectLocalStorageData()
EscopoUtils.detector.detectDOMData()
```

### **Ver dados extraídos:**
```javascript
// Extrair dados manualmente
const data = EscopoUtils.extractor.extractAllData();
console.log(data);
```

### **Testar download:**
```javascript
// Testar geração de CSV
const data = EscopoUtils.extractor.extractAllData();
const csv = EscopoUtils.csvFormatter.convertToCSV(data);
console.log(csv);
```

### **Erros comuns:**

| Erro | Causa | Solução |
|------|-------|---------|
| "Página não contém dados" | Dados não detectados | Verifique se há dados do EscopoSEO na página |
| "Erro no download" | Bloqueio do browser | Permita downloads automáticos |
| "API Error" | Token inválido | Verifique o token e endpoint da API |

## 🚀 Exemplos de Uso

### **1. Análise Completa de Site:**
```javascript
// Configurar para análise completa
configureEscopo({
    AUTO_DOWNLOAD: true,
    SEND_TO_API: true,
    DEBUG: true
});

// Navegar pelas páginas do site
// Script detecta e baixa automaticamente
```

### **2. Coleta Manual de Dados:**
```javascript
// Desabilitar auto download
configureEscopo({ AUTO_DOWNLOAD: false });

// Coletar dados
const result = await downloadEscopoData();
console.log(result.csvContent);
```

### **3. Monitoramento Contínuo:**
```javascript
// Executar a cada 5 minutos
setInterval(() => {
    downloadEscopoData();
}, 5 * 60 * 1000);
```

## 🔧 Integração com APIs

### **Configurar API externa:**
```javascript
configureEscopo({
    SEND_TO_API: true,
    API_ENDPOINT: "https://sua-api.com/reports",
    ESCOPO_TOKEN: "seu_token_aqui"
});
```

### **Formato de envio para API:**
```json
{
    "domain": "exemplo.com",
    "url": "https://exemplo.com/pagina",
    "timestamp": "2024-01-15_14-30",
    "analyses": [...],
    "metadata": {...},
    "source": "auto-downloader"
}
```

## 📈 Casos de Uso

1. **Auditoria SEO Automatizada** - Coleta dados de múltiplas páginas
2. **Monitoramento Contínuo** - Execução agendada para acompanhar mudanças
3. **Relatórios Consolidados** - Unificação de dados de diferentes fontes
4. **Backup Automático** - Preservação de análises importantes
5. **Integração com Ferramentas** - Envio automático para dashboards

Este script oferece uma solução robusta e flexível para automatizar a coleta e download de dados do EscopoSEO, similar ao sistema de SERP automation que você mostrou como exemplo.
