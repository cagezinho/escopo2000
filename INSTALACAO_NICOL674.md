# 🚀 Instalação EscopoSEO - nicol674_escopo

## ✅ Suas Configurações

- **Usuário:** `nicol674_escopo`
- **Senha:** `tzwg50$xprWm`
- **Banco:** `nicol674_escopo`
- **Host:** `localhost`

## 📋 Passo a Passo para Instalação

### 1. Verificar se o banco existe
Certifique-se de que o banco `nicol674_escopo` foi criado no painel de controle da hospedagem.

### 2. Fazer upload dos arquivos
Faça upload de todos os arquivos do projeto para o diretório public_html ou pasta do seu domínio.

### 3. Executar instalação
Acesse pelo navegador:
```
https://seudominio.com/install_nicol674.php
```

### 4. Clique em "Instalar EscopoSEO Agora"
O script irá:
- ✅ Conectar ao banco automaticamente
- ✅ Criar todas as 14 tabelas necessárias
- ✅ Testar a funcionalidade
- ✅ Confirmar que tudo está funcionando

### 5. Acessar a ferramenta
Após a instalação, acesse:
```
https://seudominio.com/index.html
```

## 🔧 Arquivos Já Configurados

1. **`config/database_shared_hosting.php`** - Configurado com suas credenciais
2. **`database/schema_no_create_db.sql`** - Schema ajustado para seu banco
3. **`env_shared_hosting.example`** - Configurações otimizadas
4. **`install_nicol674.php`** - Instalador personalizado

## 🎯 Se Algo Der Errado

### Erro de Conexão?
- Verifique se o banco `nicol674_escopo` existe
- Confirme se as credenciais estão corretas no painel da hospedagem

### Erro de Permissões?
- Certifique-se de que o usuário `nicol674_escopo` tem permissões completas no banco
- Verifique se a pasta `logs/` tem permissão de escrita

### Problemas com Upload?
- Mantenha a estrutura de pastas:
  ```
  /
  ├── api/
  ├── classes/
  ├── config/
  ├── database/
  ├── js/
  ├── logs/
  ├── index.html
  ├── install_nicol674.php
  └── ...outros arquivos
  ```

## 🧪 Testar Instalação

Após instalar, teste:

1. **Conexão:** `https://seudominio.com/test_connection.php`
2. **Interface:** `https://seudominio.com/index.html`
3. **Primeira análise:** Teste com uma URL simples

## ⚙️ Configurações Otimizadas

O sistema está configurado para hospedagem compartilhada:

- **Max páginas por análise:** 50-200
- **Delay entre requisições:** 2 segundos
- **Timeout:** 30 segundos
- **Memória:** 256MB
- **Cache:** Habilitado (2 horas)

## 📞 Suporte

Se precisar de ajuda:

1. Verifique os logs em: `logs/`
2. Execute: `test_connection.php`
3. Consulte o README.md principal

---

**🎉 Suas configurações estão prontas!** 
Basta executar `install_nicol674.php` e começar a usar o EscopoSEO.
