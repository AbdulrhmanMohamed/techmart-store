<?php
// Vercel-specific database configuration - JSON only
require_once __DIR__ . '/json_database.php';

// Force JSON database usage for Vercel deployment
$useJsonDb = true;

// Create JSON database instance
$jsonDb = new JsonDatabase(__DIR__ . '/../data/');

// Create a PDO-like wrapper for compatibility
class VercelJsonPDO {
    private $jsonDb;
    
    public function __construct($jsonDb) {
        $this->jsonDb = $jsonDb;
    }
    
    public function query($sql) {
        // Parse basic SQL queries and convert to JSON operations
        $sql = trim($sql);
        
        if (preg_match('/^SELECT\s+(.+?)\s+FROM\s+(\w+)(?:\s+WHERE\s+(.+?))?(?:\s+ORDER\s+BY\s+(.+?))?(?:\s+LIMIT\s+(\d+))?$/i', $sql, $matches)) {
            $table = $matches[2];
            $where = isset($matches[3]) ? $matches[3] : null;
            $orderBy = isset($matches[4]) ? $matches[4] : null;
            $limit = isset($matches[5]) ? intval($matches[5]) : null;
            
            $data = $this->jsonDb->select($table, [], $where);
            
            // Apply ordering
            if ($orderBy) {
                $this->applyOrdering($data, $orderBy);
            }
            
            // Apply limit
            if ($limit) {
                $data = array_slice($data, 0, $limit);
            }
            
            return new VercelJsonResult($data);
        }
        
        return new VercelJsonResult([]);
    }
    
    public function prepare($sql) {
        return new VercelJsonStatement($this->jsonDb, $sql);
    }
    
    private function applyOrdering(&$data, $orderBy) {
        if (preg_match('/(\w+)\s+(ASC|DESC)/i', $orderBy, $matches)) {
            $field = $matches[1];
            $direction = strtoupper($matches[2]);
            
            usort($data, function($a, $b) use ($field, $direction) {
                $aVal = isset($a[$field]) ? $a[$field] : '';
                $bVal = isset($b[$field]) ? $b[$field] : '';
                
                if ($direction === 'DESC') {
                    return $bVal <=> $aVal;
                }
                return $aVal <=> $bVal;
            });
        }
    }
    
    public function lastInsertId() {
        return $this->jsonDb->getLastInsertId();
    }
}

class VercelJsonResult {
    private $data;
    private $index = 0;
    
    public function __construct($data) {
        $this->data = $data;
    }
    
    public function fetch($mode = null) {
        if ($this->index < count($this->data)) {
            return $this->data[$this->index++];
        }
        return false;
    }
    
    public function fetchAll($mode = null) {
        return $this->data;
    }
    
    public function rowCount() {
        return count($this->data);
    }
}

class VercelJsonStatement {
    private $jsonDb;
    private $sql;
    private $params = [];
    
    public function __construct($jsonDb, $sql) {
        $this->jsonDb = $jsonDb;
        $this->sql = $sql;
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
        
        $sql = $this->sql;
        
        // Replace placeholders with actual values
        foreach ($this->params as $key => $value) {
            if (is_string($key)) {
                $sql = str_replace($key, "'" . addslashes($value) . "'", $sql);
            }
        }
        
        // Handle INSERT queries
        if (preg_match('/^INSERT\s+INTO\s+(\w+)\s*\(([^)]+)\)\s*VALUES\s*\(([^)]+)\)/i', $sql, $matches)) {
            $table = $matches[1];
            $columns = array_map('trim', explode(',', $matches[2]));
            $values = array_map('trim', explode(',', $matches[3]));
            
            $data = [];
            for ($i = 0; $i < count($columns); $i++) {
                $value = isset($values[$i]) ? trim($values[$i], "'\"") : '';
                $data[$columns[$i]] = $value;
            }
            
            return $this->jsonDb->insert($table, $data);
        }
        
        // Handle UPDATE queries
        if (preg_match('/^UPDATE\s+(\w+)\s+SET\s+(.+?)\s+WHERE\s+(.+)$/i', $sql, $matches)) {
            $table = $matches[1];
            $setClause = $matches[2];
            $whereClause = $matches[3];
            
            $data = [];
            $setPairs = explode(',', $setClause);
            foreach ($setPairs as $pair) {
                if (preg_match('/(\w+)\s*=\s*(.+)/', trim($pair), $setMatches)) {
                    $data[trim($setMatches[1])] = trim($setMatches[2], "'\"");
                }
            }
            
            return $this->jsonDb->update($table, $data, $whereClause);
        }
        
        // Handle DELETE queries
        if (preg_match('/^DELETE\s+FROM\s+(\w+)\s+WHERE\s+(.+)$/i', $sql, $matches)) {
            $table = $matches[1];
            $whereClause = $matches[2];
            
            return $this->jsonDb->delete($table, $whereClause);
        }
        
        return true;
    }
    
    public function fetch($mode = null) {
        return false;
    }
    
    public function fetchAll($mode = null) {
        return [];
    }
    
    public function rowCount() {
        return 0;
    }
}

// Create the PDO-like instance
$pdo = new VercelJsonPDO($jsonDb);

// Set global flag for JSON database usage
$GLOBALS['use_json_db'] = true;
$GLOBALS['json_db'] = $jsonDb;
$GLOBALS['pdo'] = $pdo;
?>