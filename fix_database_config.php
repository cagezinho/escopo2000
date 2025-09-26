<?php
/**
 * Script para corrigir todas as referências do banco de dados
 * Troca database.php por database_shared_hosting.php em todos os arquivos
 */

echo "🔧 Corrigindo configurações do banco de dados...\n\n";

$files = [
    'api/start_analysis.php',
    'api/process_analysis.php', 
    'api/get_progress.php',
    'api/get_results.php',
    'api/export.php',
    'test_connection.php',
    'install.php'
];

$replacements = [
    "require_once '../config/database.php'" => "require_once '../config/database_shared_hosting.php'",
    "require_once __DIR__ . '/../config/database.php'" => "require_once __DIR__ . '/../config/database_shared_hosting.php'",
    "require_once 'config/database.php'" => "require_once 'config/database_shared_hosting.php'"
];

$fixedFiles = 0;

foreach ($files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $originalContent = $content;
        
        foreach ($replacements as $search => $replace) {
            $content = str_replace($search, $replace, $content);
        }
        
        if ($content !== $originalContent) {
            file_put_contents($file, $content);
            echo "✅ Corrigido: $file\n";
            $fixedFiles++;
        } else {
            echo "⚪ Já correto: $file\n";
        }
    } else {
        echo "❌ Não encontrado: $file\n";
    }
}

echo "\n🎉 Correção concluída!\n";
echo "📊 Arquivos corrigidos: $fixedFiles\n\n";

echo "🔍 Testando conexão com o banco...\n";

try {
    require_once 'config/database_shared_hosting.php';
    $db = Database::getInstance();
    $pdo = $db->connect();
    
    $result = $pdo->query("SELECT 'Conexão OK' as status, DATABASE() as banco, USER() as usuario")->fetch();
    
    echo "✅ Conexão estabelecida!\n";
    echo "📊 Banco: " . $result['banco'] . "\n";
    echo "👤 Usuário: " . $result['usuario'] . "\n";
    
} catch (Exception $e) {
    echo "❌ Erro de conexão: " . $e->getMessage() . "\n";
    echo "\n💡 Verifique:\n";
    echo "   - Se o banco 'nicol674_escopo' existe\n";
    echo "   - Se as credenciais estão corretas\n";
    echo "   - Se o usuário tem permissões no banco\n";
}

echo "\n🚀 Agora você pode testar a interface em index.html\n";
?>
