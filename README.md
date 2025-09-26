# 🔍 EscopoSEO - Ferramenta de Análise Técnica de SEO

Uma ferramenta completa para análise de SEO técnico, conteúdo e otimização para IA (SGE - Search Generative Experience), desenvolvida em PHP, JavaScript e MySQL.

## 🚀 Funcionalidades Principais

### 📊 Análise Técnica de SEO
- **Rastreamento Completo**: Crawler que analisa todas as páginas internas do site
- **Status HTTP**: Identificação de erros 404, 500, redirecionamentos
- **Performance**: Medição de tempo de carregamento e tamanho das páginas
- **Títulos e Meta Descriptions**: Detecção de duplicatas, ausentes ou mal otimizados
- **Links Quebrados**: Identificação de links internos e externos que não funcionam
- **Robots.txt e Sitemap**: Análise de compliance e inconsistências
- **Canonical Tags**: Verificação de implementação adequada

### 📝 Análise de Conteúdo
- **Estrutura de Headings**: Verificação de H1, H2, H3 e hierarquia
- **Densidade de Palavras-chave**: Análise de keyword stuffing
- **Conteúdo Insuficiente**: Identificação de páginas com pouco texto
- **Páginas Órfãs**: Detecção de páginas sem links internos
- **Potencial para Featured Snippets**: Identificação de oportunidades
- **Análise de Imagens**: Verificação de ALT text e otimização

### 🤖 Otimização para IA e SGE
- **Análise E-E-A-T**: Experience, Expertise, Authoritativeness, Trustworthiness
- **Dados Estruturados**: Verificação de schema.org (FAQ, HowTo, Article)
- **Conteúdo Q&A**: Identificação de potencial para perguntas e respostas
- **Formato Conversacional**: Análise para otimização em assistentes de IA
- **Conteúdo Estruturado**: Listas, tabelas, comparativos

### 📈 Recursos Avançados
- **Clusterização de Conteúdo**: Agrupamento automático por tópicos
- **Priorização de Issues**: Sistema de scoring por impacto e esforço
- **Comparativos**: Análise de padrões entre páginas similares
- **Exportação**: Relatórios em CSV, Excel e PDF
- **Interface Responsiva**: Dashboard moderno com Tailwind CSS

## 🛠️ Tecnologias Utilizadas

- **Backend**: PHP 7.4+ com PDO
- **Frontend**: JavaScript (Vanilla) + Tailwind CSS
- **Banco de Dados**: MySQL 5.7+ / MariaDB 10.3+
- **Charts**: Chart.js
- **Icons**: Font Awesome
- **Crawler**: cURL com suporte a robots.txt e sitemaps

## 📋 Requisitos do Sistema

- **PHP**: 7.4 ou superior
- **MySQL**: 5.7 ou superior (ou MariaDB 10.3+)
- **Extensões PHP**:
  - PDO
  - PDO_MySQL
  - cURL
  - DOM
  - SimpleXML
  - JSON
  - mbstring
- **Servidor Web**: Apache ou Nginx
- **Memória**: Mínimo 256MB (recomendado 512MB+)

## 🚀 Instalação

### 1. Download e Configuração
```bash
# Clone ou baixe os arquivos do projeto
# Certifique-se de que todos os arquivos estão no diretório web

# Configurar permissões (Linux/Mac)
chmod 755 api/
chmod 644 *.php *.html *.css *.js
```

### 2. Configuração do Banco de Dados

Crie um banco MySQL e configure as credenciais:

**Opção A: Arquivo .env**
```env
DB_HOST=localhost
DB_DATABASE=escopo_seo
DB_USERNAME=seu_usuario
DB_PASSWORD=sua_senha
```

**Opção B: Editar config/database.php**
```php
private $host = 'localhost';
private $database = 'escopo_seo';
private $username = 'seu_usuario';
private $password = 'sua_senha';
```

### 3. Executar Instalação

Acesse via navegador:
```
http://seu-dominio/install.php
```

Ou execute via linha de comando:
```bash
php install.php
```

### 4. Verificar Instalação

Acesse a interface principal:
```
http://seu-dominio/index.html
```

## 📖 Como Usar

### 1. Iniciar Análise
1. Acesse a interface web
2. Insira a URL do site a ser analisado
3. Configure opções:
   - Máximo de páginas (50-1000)
   - Respeitar robots.txt
   - Incluir links externos
4. Clique em "Iniciar Análise"

### 2. Acompanhar Progresso
- A barra de progresso mostra o status em tempo real
- Logs detalhados indicam o que está sendo processado
- Estimativa de tempo de conclusão

### 3. Visualizar Resultados
- **Visão Geral**: Resumo com gráficos e métricas principais
- **SEO Técnico**: Problemas técnicos e oportunidades
- **Conteúdo**: Análise editorial e estrutural
- **IA & SGE**: Otimizações para inteligência artificial

### 4. Exportar Relatórios
- **CSV**: Para análise em planilhas
- **Excel**: Formatado com cores e destaque
- **PDF**: Relatório executivo completo

## 🔧 Configurações Avançadas

### Variáveis de Ambiente (.env)
```env
# Crawler
CRAWLER_USER_AGENT="EscopoSEO Bot 1.0"
CRAWLER_DELAY=1
CRAWLER_TIMEOUT=30
MAX_CONCURRENT_REQUESTS=5

# Limites
MAX_PAGES_DEFAULT=100
MAX_PAGES_LIMIT=1000

# Cache
CACHE_ENABLED=true
CACHE_TTL=3600

# Logs
LOG_LEVEL=info
LOG_FILE=logs/app.log
```

### Otimização de Performance

**Para sites grandes (>500 páginas):**
1. Aumente o `memory_limit` do PHP para 1GB+
2. Configure `max_execution_time=0` ou execute via CLI
3. Use processamento em background com queue jobs
4. Configure cache de resultados

**Processamento em Background:**
```php
// Em config/database.php
define('ENABLE_BACKGROUND_PROCESSING', true);
```

## 📊 Estrutura do Banco de Dados

### Tabelas Principais
- `analyses`: Informações das análises executadas
- `pages`: Páginas descobertas e seus metadados
- `page_content`: Conteúdo extraído (títulos, H1, texto)
- `links`: Links internos e externos encontrados
- `images`: Imagens e seus atributos ALT
- `issues`: Problemas e oportunidades identificados
- `structured_data`: Dados estruturados (schema.org)
- `eat_analysis`: Análise E-E-A-T por página

### Relacionamentos
```
analyses (1) -> (N) pages
pages (1) -> (1) page_content
pages (1) -> (N) links
pages (1) -> (N) images
pages (1) -> (N) issues
pages (1) -> (N) structured_data
```

## 🔍 Exemplos de Análises

### Problemas Técnicos Detectados
- Títulos duplicados em 15 páginas
- 8 páginas retornando erro 404
- 23 páginas com tempo de carregamento > 3s
- 45 links internos quebrados
- 12 páginas sem meta description

### Oportunidades de Conteúdo
- 67 páginas com potencial para featured snippets
- 34 páginas sem H1 adequado
- 89 páginas com conteúdo insuficiente (<300 palavras)
- 23 páginas órfãs (sem links internos)

### Otimizações para IA
- 12 páginas com potencial FAQ schema
- 45 páginas adequadas para respostas diretas
- 23 tutoriais que podem implementar HowTo schema
- Score E-E-A-T médio: 67/100

## 🛡️ Segurança

### Medidas Implementadas
- Validação de URLs de entrada
- Sanitização de dados de banco
- Proteção contra SQL Injection (PDO Prepared Statements)
- Headers CORS configurados
- Rate limiting no crawler

### Recomendações
- Execute em servidor com HTTPS
- Configure firewall para proteger banco de dados
- Use usuário de banco com permissões limitadas
- Mantenha PHP e MySQL atualizados
- Configure backups regulares

## 🐛 Troubleshooting

### Problemas Comuns

**"Erro de conexão com banco de dados"**
- Verifique credenciais em config/database.php
- Confirme que MySQL está rodando
- Teste conexão manual com mysql-client

**"Tempo limite na análise"**
- Aumente `max_execution_time` no PHP
- Execute via linha de comando
- Reduza número máximo de páginas

**"Erro 404 nas APIs"**
- Verifique se mod_rewrite está habilitado
- Confirme estrutura de diretórios
- Teste acesso direto aos arquivos PHP

**"Interface não carrega"**
- Verifique console do navegador
- Confirme se Tailwind CSS está carregando
- Teste em modo incógnito

### Logs e Debug
```bash
# Verificar logs de erro do PHP
tail -f /var/log/apache2/error.log

# Logs da aplicação
tail -f logs/app.log

# Debug de análise específica
SELECT * FROM analysis_logs WHERE analysis_id = X ORDER BY created_at DESC;
```

## 🔄 Atualizações

### Versioning
- v1.0: Release inicial com funcionalidades básicas
- v1.1: Melhorias em performance e exportação
- v1.2: Análise E-E-A-T e otimizações para IA

### Roadmap
- [ ] Integração com Google Search Console
- [ ] Análise de Core Web Vitals
- [ ] Suporte a múltiplos idiomas
- [ ] API REST completa
- [ ] Dashboard administrativo
- [ ] Agendamento de análises automáticas

## 📞 Suporte

Para dúvidas, problemas ou sugestões:

1. **Documentação**: Consulte este README
2. **Issues**: Verifique problemas conhecidos
3. **Logs**: Analise logs de erro da aplicação
4. **Teste**: Execute install.php para verificar configuração

## 📄 Licença

Este projeto é open source e está disponível sob a licença MIT.

## 🤝 Contribuições

Contribuições são bem-vindas! Areas de interesse:
- Melhorias no crawler
- Novas análises SEO
- Otimizações de performance
- Interface de usuário
- Documentação

---

**EscopoSEO** - Desenvolvido para profissionais de SEO que precisam de análises técnicas detalhadas e otimização para a era da Inteligência Artificial.
