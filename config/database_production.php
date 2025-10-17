<?php
// Production Database configuration with environment variables
$host = $_ENV['DB_HOST'] ?? $_ENV['MYSQLHOST'] ?? 'localhost';
$dbname = $_ENV['DB_NAME'] ?? $_ENV['MYSQLDATABASE'] ?? 'phpstore';
$username = $_ENV['DB_USER'] ?? $_ENV['MYSQLUSER'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? $_ENV['MYSQLPASSWORD'] ?? '';
$port = $_ENV['DB_PORT'] ?? $_ENV['MYSQLPORT'] ?? 3306;

// Railway MySQL connection URL parsing
if (isset($_ENV['DATABASE_URL'])) {
    $url = parse_url($_ENV['DATABASE_URL']);
    $host = $url['host'];
    $port = $url['port'];
    $dbname = ltrim($url['path'], '/');
    $username = $url['user'];
    $password = $url['pass'];
}

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false, // For cloud databases
    ];
    
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // Log successful connection
    error_log("MySQL connection successful to: $host:$port/$dbname");
    
} catch (PDOException $e) {
    // Fallback to JSON database if MySQL is not available
    error_log("MySQL connection failed: " . $e->getMessage());
    error_log("Falling back to JSON database");
    
    require_once __DIR__ . '/json_database.php';
    
    // Create a wrapper class that mimics PDO methods for JSON database
    class JsonPDOWrapper {
        private $jsonDb;
        
        public function __construct($jsonDb) {
            $this->jsonDb = $jsonDb;
        }
        
        public function prepare($query) {
            return new JsonStatementWrapper($this->jsonDb, $query);
        }
        
        public function query($query) {
            return new JsonStatementWrapper($this->jsonDb, $query);
        }
        
        public function lastInsertId() {
            return $this->lastId ?? null;
        }
        
        public function setLastInsertId($id) {
            $this->lastId = $id;
        }
    }
    
    class JsonStatementWrapper {
        private $jsonDb;
        private $query;
        private $params = [];
        
        public function __construct($jsonDb, $query) {
            $this->jsonDb = $jsonDb;
            $this->query = $query;
        }
        
        public function bindParam($param, &$value, $type = null) {
            $this->params[$param] = $value;
        }
        
        public function bindValue($param, $value, $type = null) {
            $this->params[$param] = $value;
        }
        
        public function execute($params = null) {
            if ($params) {
                $this->params = array_merge($this->params, $params);
            }
            
            // Parse the query and execute appropriate JSON database operation
            $query = strtolower(trim($this->query));
            
            if (strpos($query, 'select') === 0) {
                return $this->executeSelect();
            } elseif (strpos($query, 'insert') === 0) {
                return $this->executeInsert();
            } elseif (strpos($query, 'update') === 0) {
                return $this->executeUpdate();
            } elseif (strpos($query, 'delete') === 0) {
                return $this->executeDelete();
            }
            
            return true;
        }
        
        private function executeSelect() {
            // Simple table extraction from query
            if (preg_match('/from\s+(\w+)/i', $this->query, $matches)) {
                $table = $matches[1];
                $conditions = [];
                
                // Extract WHERE conditions
                if (preg_match('/where\s+(.+?)(?:\s+order|\s+limit|$)/i', $this->query, $whereMatches)) {
                    $whereClause = $whereMatches[1];
                    // Simple parsing for basic conditions
                    if (preg_match_all('/(\w+)\s*=\s*[:\?](\w+)/i', $whereClause, $condMatches)) {
                        for ($i = 0; $i < count($condMatches[1]); $i++) {
                            $field = $condMatches[1][$i];
                            $param = $condMatches[2][$i];
                            if (isset($this->params[":$param"]) || isset($this->params[$param])) {
                                $conditions[$field] = $this->params[":$param"] ?? $this->params[$param];
                            }
                        }
                    }
                }
                
                $this->results = $this->jsonDb->select($table, $conditions);
                return true;
            }
            return false;
        }
        
        private function executeInsert() {
            if (preg_match('/insert\s+into\s+(\w+)/i', $this->query, $matches)) {
                $table = $matches[1];
                
                // Extract field names and values
                if (preg_match('/\(([^)]+)\)\s*values\s*\(([^)]+)\)/i', $this->query, $valueMatches)) {
                    $fields = array_map('trim', explode(',', $valueMatches[1]));
                    $placeholders = array_map('trim', explode(',', $valueMatches[2]));
                    
                    $data = [];
                    for ($i = 0; $i < count($fields); $i++) {
                        $field = $fields[$i];
                        $placeholder = $placeholders[$i];
                        
                        if (strpos($placeholder, ':') === 0 || strpos($placeholder, '?') === 0) {
                            $paramKey = str_replace([':', '?'], '', $placeholder);
                            if (isset($this->params[":$paramKey"]) || isset($this->params[$paramKey])) {
                                $data[$field] = $this->params[":$paramKey"] ?? $this->params[$paramKey];
                            }
                        }
                    }
                    
                    $id = $this->jsonDb->insert($table, $data);
                    if ($pdo = $GLOBALS['pdo'] ?? null) {
                        if ($pdo instanceof JsonPDOWrapper) {
                            $pdo->setLastInsertId($id);
                        }
                    }
                    return true;
                }
            }
            return false;
        }
        
        private function executeUpdate() {
            if (preg_match('/update\s+(\w+)\s+set\s+(.+?)(?:\s+where\s+(.+?))?(?:\s+order|\s+limit|$)/i', $this->query, $matches)) {
                $table = $matches[1];
                $setClause = $matches[2];
                $whereClause = $matches[3] ?? '';
                
                // Parse SET clause
                $data = [];
                if (preg_match_all('/(\w+)\s*=\s*[:\?](\w+)/i', $setClause, $setMatches)) {
                    for ($i = 0; $i < count($setMatches[1]); $i++) {
                        $field = $setMatches[1][$i];
                        $param = $setMatches[2][$i];
                        if (isset($this->params[":$param"]) || isset($this->params[$param])) {
                            $data[$field] = $this->params[":$param"] ?? $this->params[$param];
                        }
                    }
                }
                
                // Parse WHERE clause
                $conditions = [];
                if ($whereClause && preg_match_all('/(\w+)\s*=\s*[:\?](\w+)/i', $whereClause, $whereMatches)) {
                    for ($i = 0; $i < count($whereMatches[1]); $i++) {
                        $field = $whereMatches[1][$i];
                        $param = $whereMatches[2][$i];
                        if (isset($this->params[":$param"]) || isset($this->params[$param])) {
                            $conditions[$field] = $this->params[":$param"] ?? $this->params[$param];
                        }
                    }
                }
                
                return $this->jsonDb->update($table, $data, $conditions);
            }
            return false;
        }
        
        private function executeDelete() {
            if (preg_match('/delete\s+from\s+(\w+)(?:\s+where\s+(.+?))?(?:\s+order|\s+limit|$)/i', $this->query, $matches)) {
                $table = $matches[1];
                $whereClause = $matches[2] ?? '';
                
                $conditions = [];
                if ($whereClause && preg_match_all('/(\w+)\s*=\s*[:\?](\w+)/i', $whereClause, $whereMatches)) {
                    for ($i = 0; $i < count($whereMatches[1]); $i++) {
                        $field = $whereMatches[1][$i];
                        $param = $whereMatches[2][$i];
                        if (isset($this->params[":$param"]) || isset($this->params[$param])) {
                            $conditions[$field] = $this->params[":$param"] ?? $this->params[$param];
                        }
                    }
                }
                
                return $this->jsonDb->delete($table, $conditions);
            }
            return false;
        }
        
        public function fetch($mode = PDO::FETCH_ASSOC) {
            if (isset($this->results) && is_array($this->results)) {
                return array_shift($this->results);
            }
            return false;
        }
        
        public function fetchAll($mode = PDO::FETCH_ASSOC) {
            return $this->results ?? [];
        }
        
        public function rowCount() {
            return count($this->results ?? []);
        }
    }
    
    // Initialize JSON database
    $jsonDb = new JsonDatabase();
    $pdo = new JsonPDOWrapper($jsonDb);
}

// Make database connection available globally
$GLOBALS['pdo'] = $pdo;
?>