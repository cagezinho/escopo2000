<?php
/**
 * Script de Instalação - EscopoSEO
 * Execute este arquivo para configurar o banco de dados
 */

// Incluir configuração do banco
require_once 'config/database.php';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação - EscopoSEO</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-2xl w-full mx-4">
        <div class="bg-white rounded-lg shadow-md p-8">
            <h1 class="text-3xl font-bold text-center text-gray-900 mb-8">
                🔍 EscopoSEO - Instalação
            </h1>
            
            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                try {
                    $db = Database::getInstance();
                    
                    // Verificar conexão
                    $pdo = $db->connect();
                    echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">';
                    echo '✅ Conexão com o banco de dados estabelecida com sucesso!';
                    echo '</div>';
                    
                    // Verificar estrutura do banco
                    $structure = $db->checkDatabaseStructure();
                    
                    if (!$structure['all_exist']) {
                        echo '<div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">';
                        echo '⚠️ Algumas tabelas estão faltando. Criando estrutura do banco...';
                        echo '</div>';
                        
                        // Criar banco de dados
                        $db->createDatabase();
                        
                        echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">';
                        echo '✅ Estrutura do banco de dados criada com sucesso!';
                        echo '</div>';
                    } else {
                        echo '<div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-4">';
                        echo '📋 Estrutura do banco já existe e está atualizada.';
                        echo '</div>';
                    }
                    
                    // Verificar estrutura final
                    $finalStructure = $db->checkDatabaseStructure();
                    
                    if ($finalStructure['all_exist']) {
                        echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">';
                        echo '<h3 class="font-bold">🎉 Instalação Concluída!</h3>';
                        echo '<p>O EscopoSEO está pronto para uso. Você pode:</p>';
                        echo '<ul class="list-disc list-inside mt-2">';
                        echo '<li>Acessar a <a href="index.html" class="text-blue-600 underline">interface principal</a></li>';
                        echo '<li>Iniciar uma análise de SEO</li>';
                        echo '<li>Configurar as variáveis de ambiente em um arquivo .env</li>';
                        echo '</ul>';
                        echo '</div>';
                        
                        echo '<div class="bg-gray-100 border border-gray-300 text-gray-700 px-4 py-3 rounded">';
                        echo '<h4 class="font-bold">Tabelas criadas:</h4>';
                        echo '<div class="grid grid-cols-2 gap-2 mt-2 text-sm">';
                        foreach ($finalStructure['existing_tables'] as $table) {
                            echo '<div>✓ ' . $table . '</div>';
                        }
                        echo '</div>';
                        echo '</div>';
                    } else {
                        echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">';
                        echo '❌ Erro na instalação. Algumas tabelas não foram criadas:';
                        echo '<ul class="list-disc list-inside mt-2">';
                        foreach ($finalStructure['missing_tables'] as $table) {
                            echo '<li>' . $table . '</li>';
                        }
                        echo '</ul>';
                        echo '</div>';
                    }
                    
                } catch (Exception $e) {
                    echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">';
                    echo '<h3 class="font-bold">❌ Erro na Instalação</h3>';
                    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
                    echo '<p class="mt-2 text-sm">Verifique as configurações do banco de dados no arquivo config/database.php</p>';
                    echo '</div>';
                }
            } else {
                ?>
                
                <div class="space-y-6">
                    <div class="text-center">
                        <p class="text-gray-600 mb-6">
                            Este script irá configurar o banco de dados MySQL para o EscopoSEO.
                        </p>
                    </div>
                    
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h3 class="font-bold text-blue-900 mb-2">📋 Pré-requisitos</h3>
                        <ul class="text-blue-800 text-sm space-y-1">
                            <li>• MySQL ou MariaDB instalado e rodando</li>
                            <li>• PHP 7.4 ou superior com extensões PDO e PDO_MySQL</li>
                            <li>• Usuário do banco com permissões para criar tabelas</li>
                            <li>• Configurações do banco definidas em config/database.php</li>
                        </ul>
                    </div>
                    
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <h3 class="font-bold text-yellow-900 mb-2">⚙️ Configuração</h3>
                        <p class="text-yellow-800 text-sm">
                            Antes de prosseguir, verifique se as configurações do banco de dados 
                            estão corretas no arquivo <code class="bg-yellow-200 px-1 rounded">config/database.php</code>
                            ou crie um arquivo <code class="bg-yellow-200 px-1 rounded">.env</code> com suas configurações.
                        </p>
                    </div>
                    
                    <form method="POST" class="text-center">
                        <button type="submit" 
                                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-lg transition duration-200">
                            🚀 Instalar EscopoSEO
                        </button>
                    </form>
                    
                    <div class="text-center text-sm text-gray-500">
                        <p>
                            A instalação criará automaticamente todas as tabelas necessárias.<br>
                            Este processo é seguro e pode ser executado múltiplas vezes.
                        </p>
                    </div>
                </div>
                
                <?php
            }
            ?>
        </div>
        
        <div class="text-center mt-6 text-sm text-gray-500">
            <p>EscopoSEO - Ferramenta de Análise Técnica de SEO</p>
            <p>Desenvolvido com PHP, JavaScript e MySQL</p>
        </div>
    </div>
</body>
</html>
