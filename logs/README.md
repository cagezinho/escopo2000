# Logs da Aplicação EscopoSEO

Este diretório contém os logs da aplicação.

## Arquivos de Log

- `app.log` - Log principal da aplicação
- `crawler.log` - Logs específicos do crawler
- `errors.log` - Logs de erro

## Configuração

Os logs são configurados no arquivo `env.example` ou nas configurações da aplicação.

## Limpeza

Recomenda-se limpar logs antigos periodicamente para economizar espaço em disco.

```bash
# Limpar logs mais antigos que 30 dias
find logs/ -name "*.log" -mtime +30 -delete
```
