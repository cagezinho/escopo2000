<?php
/**
 * Script de Instalação para Hospedagem Compartilhada - EscopoSEO
 * Use este arquivo quando não tiver permissão para criar bancos de dados
 */

// Incluir configuração do banco para hospedagem compartilhada
require_once 'config/database_shared_hosting.php';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação Hospedagem Compartilhada - EscopoSEO</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-4xl w-full mx-4">
        <div class="bg-white rounded-lg shadow-md p-8">
            <h1 class="text-3xl font-bold text-center text-gray-900 mb-8">
                🔍 EscopoSEO - Instalação para Hospedagem Compartilhada
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
                    
                    // Mostrar informações do banco
                    $dbInfo = $pdo->query("SELECT DATABASE() as db_name, USER() as user_name, VERSION() as version")->fetch();
                    echo '<div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-4">';
                    echo '<strong>📊 Informações do Banco:</strong><br>';
                    echo 'Banco: ' . $dbInfo['db_name'] . '<br>';
                    echo 'Usuário: ' . $dbInfo['user_name'] . '<br>';
                    echo 'Versão MySQL: ' . $dbInfo['version'];
                    echo '</div>';
                    
                    // Verificar estrutura do banco
                    $structure = $db->checkDatabaseStructure();
                    
                    if (!$structure['all_exist']) {
                        echo '<div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">';
                        echo '⚠️ Algumas tabelas estão faltando. Criando estrutura do banco...';
                        echo '</div>';
                        
                        // Criar estrutura do banco
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
                        echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">';
                        echo '<h3 class="font-bold text-lg mb-2">🎉 Instalação Concluída!</h3>';
                        echo '<p class="mb-4">O EscopoSEO está pronto para uso em sua hospedagem compartilhada!</p>';
                        echo '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">';
                        echo '<div class="bg-white p-4 rounded border">';
                        echo '<h4 class="font-semibold text-green-800 mb-2">✅ Próximos Passos:</h4>';
                        echo '<ul class="text-sm space-y-1">';
                        echo '<li>• <a href="index.html" class="text-blue-600 underline">Acessar interface principal</a></li>';
                        echo '<li>• <a href="test_connection.php" class="text-blue-600 underline">Testar conexão</a></li>';
                        echo '<li>• Fazer primeira análise de SEO</li>';
                        echo '<li>• Configurar limites conforme seu plano</li>';
                        echo '</ul>';
                        echo '</div>';
                        echo '<div class="bg-white p-4 rounded border">';
                        echo '<h4 class="font-semibold text-green-800 mb-2">⚙️ Configurações:</h4>';
                        echo '<ul class="text-sm space-y-1">';
                        echo '<li>• Crie arquivo .env baseado em env_shared_hosting.example</li>';
                        echo '<li>• Ajuste limites de páginas conforme sua hospedagem</li>';
                        echo '<li>• Configure CRAWLER_DELAY para evitar sobrecarga</li>';
                        echo '<li>• Monitore logs de erro da hospedagem</li>';
                        echo '</ul>';
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                        
                        echo '<div class="bg-gray-100 border border-gray-300 text-gray-700 px-4 py-3 rounded">';
                        echo '<h4 class="font-bold mb-2">📊 Tabelas criadas (' . count($finalStructure['existing_tables']) . '):</h4>';
                        echo '<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2 text-sm">';
                        foreach ($finalStructure['existing_tables'] as $table) {
                            echo '<div class="bg-white px-2 py-1 rounded">✓ ' . $table . '</div>';
                        }
                        echo '</div>';
                        echo '</div>';
                        
                        // Teste básico de funcionalidade
                        echo '<div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">';
                        echo '<h4 class="font-bold text-blue-900 mb-2">🧪 Teste de Funcionalidades:</h4>';
                        
                        $tests = [
                            'Inserção de dados' => testInsert($db),
                            'Consulta de dados' => testSelect($db),
                            'APIs disponíveis' => testAPIs(),
                            'Permissões de arquivo' => testFilePermissions()
                        ];
                        
                        echo '<div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">';
                        foreach ($tests as $test => $result) {
                            $status = $result ? '✅' : '❌';
                            $color = $result ? 'text-green-700' : 'text-red-700';
                            echo "<div class='$color'>$status $test</div>";
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
                        if (isset($finalStructure['error'])) {
                            echo '<p class="mt-2 text-sm">Erro: ' . $finalStructure['error'] . '</p>';
                        }
                        echo '</div>';
                    }
                    
                } catch (Exception $e) {
                    echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">';
                    echo '<h3 class="font-bold">❌ Erro na Instalação</h3>';
                    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
                    echo '<div class="mt-4 p-3 bg-red-50 rounded text-sm">';
                    echo '<strong>💡 Possíveis soluções:</strong><br>';
                    echo '• Verifique se criou o banco de dados no painel de controle<br>';
                    echo '• Confirme usuário e senha do banco<br>';
                    echo '• Verifique se o nome do banco está correto<br>';
                    echo '• Edite config/database_shared_hosting.php com suas credenciais<br>';
                    echo '• Ou crie arquivo .env baseado em env_shared_hosting.example';
                    echo '</div>';
                    echo '</div>';
                }
            } else {
                ?>
                
                <div class="space-y-6">
                    <div class="text-center">
                        <p class="text-gray-600 mb-6">
                            Este script configurará o EscopoSEO em sua hospedagem compartilhada.<br>
                            Certifique-se de ter criado o banco de dados no painel de controle.
                        </p>
                    </div>
                    
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <h3 class="font-bold text-red-900 mb-2">🚨 IMPORTANTE - Configure antes de continuar</h3>
                        <div class="text-red-800 text-sm space-y-2">
                            <p><strong>1. Edite o arquivo:</strong> <code class="bg-red-200 px-1 rounded">config/database_shared_hosting.php</code></p>
                            <p><strong>2. Altere estas linhas:</strong></p>
                            <div class="bg-red-100 p-2 rounded font-mono text-xs">
                                private $database = '<span class="text-red-600">SEU_BANCO_AQUI</span>';<br>
                                private $username = '<span class="text-red-600">SEU_USUARIO_AQUI</span>';<br>
                                private $password = '<span class="text-red-600">SUA_SENHA_AQUI</span>';
                            </div>
                            <p><strong>3. Ou crie arquivo .env:</strong> copie env_shared_hosting.example para .env e configure</p>
                        </div>
                    </div>
                    
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h3 class="font-bold text-blue-900 mb-2">📋 Pré-requisitos</h3>
                        <ul class="text-blue-800 text-sm space-y-1">
                            <li>• Banco MySQL criado no painel de controle da hospedagem</li>
                            <li>• Usuário do banco com permissões completas no banco criado</li>
                            <li>• PHP 7.4+ com extensões PDO e PDO_MySQL</li>
                            <li>• Pelo menos 256MB de memória PHP disponível</li>
                            <li>• Configurações do banco ajustadas no arquivo acima</li>
                        </ul>
                    </div>
                    
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <h3 class="font-bold text-yellow-900 mb-2">⚙️ Configurações Recomendadas</h3>
                        <div class="text-yellow-800 text-sm space-y-2">
                            <p><strong>Para hospedagem compartilhada, recomendamos:</strong></p>
                            <ul class="list-disc list-inside space-y-1 ml-4">
                                <li>Máximo de 50-100 páginas por análise</li>
                                <li>Delay de 2-3 segundos entre requisições</li>
                                <li>Timeout de 30 segundos</li>
                                <li>Processamento síncrono (não background)</li>
                                <li>Cache habilitado</li>
                            </ul>
                        </div>
                    </div>
                    
                    <form method="POST" class="text-center">
                        <button type="submit" 
                                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-lg transition duration-200">
                            🚀 Instalar EscopoSEO
                        </button>
                    </form>
                    
                    <div class="text-center text-sm text-gray-500">
                        <p>
                            A instalação criará 14 tabelas necessárias para o funcionamento.<br>
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
            <p>Versão otimizada para hospedagem compartilhada</p>
        </div>
    </div>
</body>
</html>

<?php
// Funções de teste

function testInsert($db) {
    try {
        $id = $db->insert("INSERT INTO analyses (url, domain, status) VALUES (?, ?, ?)", 
                          ['https://teste.com', 'teste.com', 'pending']);
        if ($id) {
            $db->execute("DELETE FROM analyses WHERE id = ?", [$id]);
            return true;
        }
    } catch (Exception $e) {
        return false;
    }
    return false;
}

function testSelect($db) {
    try {
        $result = $db->select("SELECT 1 as test");
        return !empty($result);
    } catch (Exception $e) {
        return false;
    }
}

function testAPIs() {
    $apis = ['api/start_analysis.php', 'api/get_progress.php', 'api/get_results.php', 'api/export.php'];
    foreach ($apis as $api) {
        if (!file_exists($api)) {
            return false;
        }
    }
    return true;
}

function testFilePermissions() {
    return is_writable('logs/');
}
?>
