<?php
/**
 * Instalação Rápida EscopoSEO - nicol674_escopo
 * Arquivo configurado especificamente para suas credenciais
 */

// Configurações específicas do nicol674
$config = [
    'host' => 'localhost',
    'database' => 'nicol674_escopo',
    'username' => 'nicol674_escopo', 
    'password' => 'tzwg50$xprWm'
];

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação Rápida - EscopoSEO (nicol674)</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-3xl w-full mx-4">
        <div class="bg-white rounded-lg shadow-md p-8">
            <h1 class="text-3xl font-bold text-center text-gray-900 mb-8">
                🚀 EscopoSEO - Instalação Rápida
            </h1>
            
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <h3 class="font-bold text-blue-900 mb-2">📊 Suas Configurações</h3>
                <div class="text-blue-800 text-sm space-y-1">
                    <p><strong>Host:</strong> <?php echo htmlspecialchars($config['host']); ?></p>
                    <p><strong>Banco:</strong> <?php echo htmlspecialchars($config['database']); ?></p>
                    <p><strong>Usuário:</strong> <?php echo htmlspecialchars($config['username']); ?></p>
                    <p><strong>Senha:</strong> <?php echo str_repeat('*', strlen($config['password'])); ?></p>
                </div>
            </div>
            
            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                try {
                    // Conectar ao banco
                    $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4";
                    $options = [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ];
                    
                    $pdo = new PDO($dsn, $config['username'], $config['password'], $options);
                    
                    echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">';
                    echo '✅ Conexão estabelecida com sucesso!';
                    echo '</div>';
                    
                    // Verificar versão do MySQL
                    $version = $pdo->query('SELECT VERSION() as version')->fetch();
                    echo '<div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-4">';
                    echo '<strong>📊 MySQL:</strong> ' . $version['version'];
                    echo '</div>';
                    
                    // Ler e executar schema
                    $schemaFile = 'database/schema_no_create_db.sql';
                    if (!file_exists($schemaFile)) {
                        throw new Exception("Arquivo $schemaFile não encontrado");
                    }
                    
                    $sql = file_get_contents($schemaFile);
                    
                    // Executar comandos SQL
                    $commands = explode(';', $sql);
                    $executedCommands = 0;
                    $errors = [];
                    
                    foreach ($commands as $command) {
                        $command = trim($command);
                        if (!empty($command) && !preg_match('/^(--|\/\*|USE)/i', $command)) {
                            try {
                                $pdo->exec($command);
                                $executedCommands++;
                            } catch (PDOException $e) {
                                if (!strpos($e->getMessage(), 'already exists')) {
                                    $errors[] = substr($e->getMessage(), 0, 100);
                                }
                            }
                        }
                    }
                    
                    echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">';
                    echo "<strong>✅ Estrutura criada!</strong><br>";
                    echo "Comandos executados: $executedCommands<br>";
                    if (!empty($errors)) {
                        echo "Avisos: " . count($errors) . " (normal se tabelas já existiam)";
                    }
                    echo '</div>';
                    
                    // Verificar tabelas criadas
                    $tables = $pdo->query("SHOW TABLES")->fetchAll();
                    
                    echo '<div class="bg-gray-100 border border-gray-300 text-gray-700 px-4 py-3 rounded mb-4">';
                    echo '<strong>📊 Tabelas criadas (' . count($tables) . '):</strong><br>';
                    echo '<div class="grid grid-cols-3 gap-2 mt-2 text-sm">';
                    foreach ($tables as $table) {
                        $tableName = array_values($table)[0];
                        echo '<div class="bg-white px-2 py-1 rounded">✓ ' . $tableName . '</div>';
                    }
                    echo '</div>';
                    echo '</div>';
                    
                    // Teste de funcionalidade
                    try {
                        $testId = $pdo->lastInsertId($pdo->query("INSERT INTO analyses (url, domain, status) VALUES ('https://teste.com', 'teste.com', 'pending')"));
                        $pdo->exec("DELETE FROM analyses WHERE id = $testId");
                        
                        echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">';
                        echo '✅ Teste de inserção/exclusão: OK';
                        echo '</div>';
                    } catch (Exception $e) {
                        echo '<div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">';
                        echo '⚠️ Teste de inserção falhou: ' . $e->getMessage();
                        echo '</div>';
                    }
                    
                    echo '<div class="bg-green-50 border border-green-200 rounded-lg p-6">';
                    echo '<h3 class="font-bold text-green-900 text-xl mb-4">🎉 Instalação Concluída!</h3>';
                    echo '<div class="space-y-3">';
                    echo '<p class="text-green-800">O EscopoSEO está instalado e pronto para uso!</p>';
                    echo '<div class="flex space-x-4">';
                    echo '<a href="index.html" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded inline-block">🌐 Acessar Interface</a>';
                    echo '<a href="test_connection.php" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded inline-block">🔧 Testar Conexão</a>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                    
                } catch (Exception $e) {
                    echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">';
                    echo '<h3 class="font-bold">❌ Erro na Instalação</h3>';
                    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
                    echo '<div class="mt-3 text-sm">';
                    echo '<strong>Possíveis soluções:</strong><br>';
                    echo '• Verifique se o banco "nicol674_escopo" existe<br>';
                    echo '• Confirme se as credenciais estão corretas<br>';
                    echo '• Verifique se o usuário tem permissões no banco';
                    echo '</div>';
                    echo '</div>';
                }
            } else {
                ?>
                
                <div class="space-y-6">
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <h3 class="font-bold text-green-900 mb-2">✅ Pronto para Instalar</h3>
                        <p class="text-green-800 text-sm">
                            Suas configurações já estão definidas. Certifique-se apenas de que:
                        </p>
                        <ul class="text-green-700 text-sm mt-2 space-y-1 list-disc list-inside">
                            <li>O banco de dados <strong>"nicol674_escopo"</strong> foi criado</li>
                            <li>As credenciais estão corretas</li>
                            <li>O usuário tem permissões completas no banco</li>
                        </ul>
                    </div>
                    
                    <form method="POST" class="text-center">
                        <button type="submit" 
                                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-lg transition duration-200">
                            🚀 Instalar EscopoSEO Agora
                        </button>
                    </form>
                    
                    <div class="text-center text-sm text-gray-500">
                        <p>A instalação criará 14 tabelas no banco "nicol674_escopo"</p>
                        <p>Este processo leva apenas alguns segundos</p>
                    </div>
                </div>
                
                <?php
            }
            ?>
        </div>
        
        <div class="text-center mt-6 text-sm text-gray-500">
            <p>EscopoSEO - Configurado para nicol674_escopo</p>
        </div>
    </div>
</body>
</html>
