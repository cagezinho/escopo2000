<?php
/**
 * Teste de Conexão - EscopoSEO
 * Script simples para testar a conexão com o banco de dados
 */

// Incluir configuração
require_once 'config/database_shared_hosting.php';

// Headers
header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Conexão - EscopoSEO</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background-color: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #008000; background-color: #f0fff0; padding: 15px; border-radius: 4px; border-left: 4px solid #008000; margin: 10px 0; }
        .error { color: #d00; background-color: #fff0f0; padding: 15px; border-radius: 4px; border-left: 4px solid #d00; margin: 10px 0; }
        .info { color: #0066cc; background-color: #f0f8ff; padding: 15px; border-radius: 4px; border-left: 4px solid #0066cc; margin: 10px 0; }
        h1 { color: #333; }
        h2 { color: #666; margin-top: 30px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0; }
        .metric { background-color: #f8f9fa; padding: 15px; border-radius: 4px; text-align: center; }
        .metric-value { font-size: 24px; font-weight: bold; color: #0066cc; }
        .metric-label { font-size: 14px; color: #666; margin-top: 5px; }
        pre { background-color: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; }
        .button { background-color: #0066cc; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; margin: 10px 5px 10px 0; }
        .button:hover { background-color: #0052a3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 EscopoSEO - Teste de Conexão</h1>
        
        <?php
        try {
            // Testar conexão
            $db = Database::getInstance();
            $pdo = $db->connect();
            
            echo '<div class="success">';
            echo '<strong>✅ Conexão Estabelecida!</strong><br>';
            echo 'Conexão com o banco de dados foi estabelecida com sucesso.';
            echo '</div>';
            
            // Informações do banco
            $version = $pdo->query('SELECT VERSION() as version')->fetch();
            echo '<div class="info">';
            echo '<strong>📊 Informações do Banco:</strong><br>';
            echo 'Versão do MySQL/MariaDB: ' . $version['version'];
            echo '</div>';
            
            // Verificar estrutura
            $structure = $db->checkDatabaseStructure();
            
            if ($structure['all_exist']) {
                echo '<div class="success">';
                echo '<strong>✅ Estrutura do Banco OK!</strong><br>';
                echo 'Todas as tabelas necessárias estão presentes.';
                echo '</div>';
                
                // Estatísticas das tabelas
                echo '<h2>📈 Estatísticas das Tabelas</h2>';
                echo '<div class="grid">';
                
                $tables = ['analyses', 'pages', 'page_content', 'links', 'images', 'issues'];
                foreach ($tables as $table) {
                    try {
                        $result = $pdo->query("SELECT COUNT(*) as count FROM $table")->fetch();
                        echo '<div class="metric">';
                        echo '<div class="metric-value">' . number_format($result['count']) . '</div>';
                        echo '<div class="metric-label">' . ucfirst($table) . '</div>';
                        echo '</div>';
                    } catch (Exception $e) {
                        echo '<div class="metric">';
                        echo '<div class="metric-value">❌</div>';
                        echo '<div class="metric-label">' . ucfirst($table) . '</div>';
                        echo '</div>';
                    }
                }
                echo '</div>';
                
                // Verificar análises recentes
                try {
                    $recentAnalyses = $pdo->query(
                        "SELECT url, status, created_at FROM analyses ORDER BY created_at DESC LIMIT 5"
                    )->fetchAll();
                    
                    if (!empty($recentAnalyses)) {
                        echo '<h2>🕒 Análises Recentes</h2>';
                        echo '<table style="width: 100%; border-collapse: collapse;">';
                        echo '<tr style="background-color: #f8f9fa;">';
                        echo '<th style="border: 1px solid #ddd; padding: 8px; text-align: left;">URL</th>';
                        echo '<th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Status</th>';
                        echo '<th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Data</th>';
                        echo '</tr>';
                        
                        foreach ($recentAnalyses as $analysis) {
                            echo '<tr>';
                            echo '<td style="border: 1px solid #ddd; padding: 8px;">' . htmlspecialchars($analysis['url']) . '</td>';
                            echo '<td style="border: 1px solid #ddd; padding: 8px;">' . $analysis['status'] . '</td>';
                            echo '<td style="border: 1px solid #ddd; padding: 8px;">' . date('d/m/Y H:i', strtotime($analysis['created_at'])) . '</td>';
                            echo '</tr>';
                        }
                        echo '</table>';
                    }
                } catch (Exception $e) {
                    // Tabela pode estar vazia, não é erro crítico
                }
                
            } else {
                echo '<div class="error">';
                echo '<strong>❌ Estrutura Incompleta!</strong><br>';
                echo 'Algumas tabelas estão faltando:<br>';
                echo '<ul>';
                foreach ($structure['missing_tables'] as $table) {
                    echo '<li>' . $table . '</li>';
                }
                echo '</ul>';
                echo '</div>';
            }
            
            // Informações de configuração PHP
            echo '<h2>⚙️ Configuração PHP</h2>';
            echo '<div class="grid">';
            
            $phpInfo = [
                'Versão PHP' => PHP_VERSION,
                'Memory Limit' => ini_get('memory_limit'),
                'Max Execution Time' => ini_get('max_execution_time') . 's',
                'Upload Max Size' => ini_get('upload_max_filesize'),
                'cURL Enabled' => extension_loaded('curl') ? 'Sim' : 'Não',
                'PDO MySQL' => extension_loaded('pdo_mysql') ? 'Sim' : 'Não'
            ];
            
            foreach ($phpInfo as $label => $value) {
                echo '<div class="metric">';
                echo '<div class="metric-value" style="font-size: 16px;">' . $value . '</div>';
                echo '<div class="metric-label">' . $label . '</div>';
                echo '</div>';
            }
            echo '</div>';
            
            // Teste rápido de APIs
            echo '<h2>🔗 Teste de APIs</h2>';
            $apiTests = [
                'start_analysis.php' => 'POST',
                'get_progress.php' => 'GET',
                'get_results.php' => 'GET',
                'export.php' => 'POST'
            ];
            
            foreach ($apiTests as $api => $method) {
                $apiPath = 'api/' . $api;
                if (file_exists($apiPath)) {
                    echo '<span style="color: green;">✅ ' . $api . '</span> ';
                } else {
                    echo '<span style="color: red;">❌ ' . $api . '</span> ';
                }
            }
            
        } catch (Exception $e) {
            echo '<div class="error">';
            echo '<strong>❌ Erro de Conexão!</strong><br>';
            echo htmlspecialchars($e->getMessage());
            echo '</div>';
            
            echo '<div class="info">';
            echo '<strong>💡 Possíveis Soluções:</strong><br>';
            echo '<ul>';
            echo '<li>Verificar se o MySQL está rodando</li>';
            echo '<li>Conferir credenciais em config/database.php</li>';
            echo '<li>Verificar se o banco de dados existe</li>';
            echo '<li>Testar conexão manual com mysql client</li>';
            echo '</ul>';
            echo '</div>';
        }
        ?>
        
        <h2>🚀 Próximos Passos</h2>
        <div class="info">
            <strong>Se tudo estiver funcionando:</strong><br>
            <a href="index.html" class="button">🌐 Acessar Interface Principal</a>
            <a href="install.php" class="button">⚙️ Executar Instalação</a>
        </div>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center; color: #666; font-size: 14px;">
            <p>EscopoSEO - Ferramenta de Análise Técnica de SEO</p>
            <p>Para suporte, consulte o arquivo README.md</p>
        </div>
    </div>
</body>
</html>
