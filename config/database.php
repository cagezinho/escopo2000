<?php
/**
 * Configuração do Banco de Dados - EscopoSEO
 */

class Database {
    private $host = 'localhost';
    private $database = 'escopo_seo';
    private $username = 'root'; // Altere conforme sua configuração
    private $password = '';     // Altere conforme sua configuração
    private $charset = 'utf8mb4';
    private $pdo = null;
    
    // Configurações de conexão
    private $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];
    
    /**
     * Construtor - carrega configurações do ambiente se disponível
     */
    public function __construct() {
        // Carregar configurações do arquivo .env se existir
        $this->loadEnvironmentConfig();
    }
    
    /**
     * Carrega configurações do arquivo .env
     */
    private function loadEnvironmentConfig() {
        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '=') !== false && substr($line, 0, 1) !== '#') {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value, '"\'');
                    
                    switch ($key) {
                        case 'DB_HOST':
                            $this->host = $value;
                            break;
                        case 'DB_DATABASE':
                            $this->database = $value;
                            break;
                        case 'DB_USERNAME':
                            $this->username = $value;
                            break;
                        case 'DB_PASSWORD':
                            $this->password = $value;
                            break;
                    }
                }
            }
        }
    }
    
    /**
     * Estabelece conexão com o banco de dados
     */
    public function connect() {
        if ($this->pdo === null) {
            try {
                $dsn = "mysql:host={$this->host};dbname={$this->database};charset={$this->charset}";
                $this->pdo = new PDO($dsn, $this->username, $this->password, $this->options);
                
                // Configurar timezone
                $this->pdo->exec("SET time_zone = '+00:00'");
                
                return $this->pdo;
            } catch (PDOException $e) {
                throw new Exception("Erro de conexão com o banco de dados: " . $e->getMessage());
            }
        }
        
        return $this->pdo;
    }
    
    /**
     * Retorna instância PDO existente ou cria nova conexão
     */
    public function getPDO() {
        return $this->connect();
    }
    
    /**
     * Inicia transação
     */
    public function beginTransaction() {
        return $this->connect()->beginTransaction();
    }
    
    /**
     * Confirma transação
     */
    public function commit() {
        return $this->pdo->commit();
    }
    
    /**
     * Desfaz transação
     */
    public function rollback() {
        return $this->pdo->rollback();
    }
    
    /**
     * Executa query preparada
     */
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new Exception("Erro ao executar query: " . $e->getMessage() . " SQL: " . $sql);
        }
    }
    
    /**
     * Executa SELECT e retorna todos os resultados
     */
    public function select($sql, $params = []) {
        $stmt = $this->execute($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Executa SELECT e retorna apenas o primeiro resultado
     */
    public function selectOne($sql, $params = []) {
        $stmt = $this->execute($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * Executa INSERT e retorna o ID inserido
     */
    public function insert($sql, $params = []) {
        $this->execute($sql, $params);
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Executa UPDATE ou DELETE e retorna número de linhas afetadas
     */
    public function update($sql, $params = []) {
        $stmt = $this->execute($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Verifica se as tabelas existem no banco
     */
    public function checkDatabaseStructure() {
        try {
            $tables = [
                'analyses', 'pages', 'page_content', 'links', 'images',
                'structured_data', 'issues', 'robots_analysis', 'sitemap_analysis',
                'performance_metrics', 'eat_analysis', 'content_clusters',
                'page_clusters', 'analysis_logs'
            ];
            
            $existingTables = [];
            $result = $this->select("SHOW TABLES");
            
            foreach ($result as $row) {
                $existingTables[] = array_values($row)[0];
            }
            
            $missingTables = array_diff($tables, $existingTables);
            
            return [
                'all_exist' => empty($missingTables),
                'existing_tables' => $existingTables,
                'missing_tables' => $missingTables
            ];
            
        } catch (Exception $e) {
            return [
                'all_exist' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Executa o script de criação do banco
     */
    public function createDatabase() {
        try {
            $sqlFile = __DIR__ . '/../database/schema.sql';
            if (!file_exists($sqlFile)) {
                throw new Exception("Arquivo schema.sql não encontrado");
            }
            
            $sql = file_get_contents($sqlFile);
            
            // Separar comandos SQL por ';'
            $commands = array_filter(array_map('trim', explode(';', $sql)));
            
            foreach ($commands as $command) {
                if (!empty($command)) {
                    $this->pdo->exec($command);
                }
            }
            
            return true;
            
        } catch (Exception $e) {
            throw new Exception("Erro ao criar banco de dados: " . $e->getMessage());
        }
    }
    
    /**
     * Fecha conexão
     */
    public function close() {
        $this->pdo = null;
    }
    
    /**
     * Método estático para obter instância singleton
     */
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}

/**
 * Classe helper para operações comuns do banco
 */
class DatabaseHelper {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Cria nova análise
     */
    public function createAnalysis($url, $domain, $maxPages = 100, $respectRobots = true, $includeExternal = false) {
        $sql = "INSERT INTO analyses (url, domain, max_pages, respect_robots, include_external, status, start_time) 
                VALUES (?, ?, ?, ?, ?, 'pending', NOW())";
        
        return $this->db->insert($sql, [$url, $domain, $maxPages, $respectRobots ? 1 : 0, $includeExternal ? 1 : 0]);
    }
    
    /**
     * Atualiza status da análise
     */
    public function updateAnalysisStatus($analysisId, $status, $progress = null) {
        $sql = "UPDATE analyses SET status = ?, updated_at = NOW()";
        $params = [$status];
        
        if ($progress !== null) {
            $sql .= ", progress = ?";
            $params[] = $progress;
        }
        
        if ($status === 'running' && $progress === 0) {
            $sql .= ", start_time = NOW()";
        } elseif ($status === 'completed') {
            $sql .= ", end_time = NOW(), progress = 100";
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $analysisId;
        
        return $this->db->update($sql, $params);
    }
    
    /**
     * Adiciona página à análise
     */
    public function addPage($analysisId, $url, $statusCode = null, $isInternal = true, $depth = 0) {
        $sql = "INSERT INTO pages (analysis_id, url, status_code, is_internal, depth, discovered_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE updated_at = NOW()";
        
        return $this->db->insert($sql, [$analysisId, $url, $statusCode, $isInternal ? 1 : 0, $depth]);
    }
    
    /**
     * Adiciona log de progresso
     */
    public function addLog($analysisId, $step, $message, $level = 'info', $data = null) {
        $sql = "INSERT INTO analysis_logs (analysis_id, step, message, level, data) VALUES (?, ?, ?, ?, ?)";
        return $this->db->insert($sql, [$analysisId, $step, $message, $level, $data ? json_encode($data) : null]);
    }
    
    /**
     * Obtém análise por ID
     */
    public function getAnalysis($analysisId) {
        $sql = "SELECT * FROM analyses WHERE id = ?";
        return $this->db->selectOne($sql, [$analysisId]);
    }
    
    /**
     * Obtém páginas de uma análise
     */
    public function getPages($analysisId, $limit = null, $offset = 0) {
        $sql = "SELECT p.*, pc.title, pc.h1, pc.word_count, pc.meta_description 
                FROM pages p 
                LEFT JOIN page_content pc ON p.id = pc.page_id 
                WHERE p.analysis_id = ? 
                ORDER BY p.discovered_at ASC";
        
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            return $this->db->select($sql, [$analysisId, $limit, $offset]);
        }
        
        return $this->db->select($sql, [$analysisId]);
    }
    
    /**
     * Obtém estatísticas da análise
     */
    public function getAnalysisStats($analysisId) {
        $stats = [];
        
        // Total de páginas
        $result = $this->db->selectOne("SELECT COUNT(*) as total FROM pages WHERE analysis_id = ?", [$analysisId]);
        $stats['total_pages'] = $result['total'];
        
        // Distribuição por status
        $result = $this->db->select("SELECT status_code, COUNT(*) as count FROM pages WHERE analysis_id = ? GROUP BY status_code", [$analysisId]);
        $stats['status_distribution'] = [];
        foreach ($result as $row) {
            $stats['status_distribution'][$row['status_code']] = $row['count'];
        }
        
        // Problemas por categoria
        $result = $this->db->select("SELECT category, severity, COUNT(*) as count FROM issues WHERE analysis_id = ? GROUP BY category, severity", [$analysisId]);
        $stats['issues_by_category'] = [];
        foreach ($result as $row) {
            if (!isset($stats['issues_by_category'][$row['category']])) {
                $stats['issues_by_category'][$row['category']] = [];
            }
            $stats['issues_by_category'][$row['category']][$row['severity']] = $row['count'];
        }
        
        return $stats;
    }
}
?>
