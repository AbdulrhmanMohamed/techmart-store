<?php

class JsonDatabase {
    private $dataPath;
    
    public function __construct($dataPath = null) {
        $this->dataPath = $dataPath ?: __DIR__ . '/../data/';
    }
    
    private function getFilePath($table) {
        return $this->dataPath . $table . '.json';
    }
    
    private function readFile($table) {
        $filePath = $this->getFilePath($table);
        if (!file_exists($filePath)) {
            return [];
        }
        $content = file_get_contents($filePath);
        return json_decode($content, true) ?: [];
    }
    
    private function writeFile($table, $data) {
        $filePath = $this->getFilePath($table);
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
    }
    
    public function select($table, $conditions = [], $limit = null, $orderBy = null) {
        $data = $this->readFile($table);
        
        // Apply conditions
        if (!empty($conditions)) {
            $data = array_filter($data, function($row) use ($conditions) {
                foreach ($conditions as $field => $value) {
                    if (is_array($value)) {
                        // Handle IN conditions
                        if (!in_array($row[$field], $value)) {
                            return false;
                        }
                    } else {
                        if ($row[$field] != $value) {
                            return false;
                        }
                    }
                }
                return true;
            });
        }
        
        // Apply ordering
        if ($orderBy) {
            $field = $orderBy['field'];
            $direction = $orderBy['direction'] ?? 'ASC';
            usort($data, function($a, $b) use ($field, $direction) {
                if ($direction === 'DESC') {
                    return $b[$field] <=> $a[$field];
                }
                return $a[$field] <=> $b[$field];
            });
        }
        
        // Apply limit
        if ($limit) {
            $data = array_slice($data, 0, $limit);
        }
        
        return $data;
    }
    
    public function selectOne($table, $conditions = []) {
        $results = $this->select($table, $conditions, 1);
        return !empty($results) ? $results[0] : null;
    }
    
    public function insert($table, $data) {
        $allData = $this->readFile($table);
        
        // Auto-increment ID
        $maxId = 0;
        foreach ($allData as $row) {
            if (isset($row['id']) && $row['id'] > $maxId) {
                $maxId = $row['id'];
            }
        }
        $data['id'] = $maxId + 1;
        
        // Add timestamps
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        if (!isset($data['updated_at'])) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        
        $allData[] = $data;
        $this->writeFile($table, $allData);
        
        return $data['id'];
    }
    
    public function update($table, $data, $conditions) {
        $allData = $this->readFile($table);
        $updated = false;
        
        foreach ($allData as &$row) {
            $match = true;
            foreach ($conditions as $field => $value) {
                if ($row[$field] != $value) {
                    $match = false;
                    break;
                }
            }
            
            if ($match) {
                foreach ($data as $field => $value) {
                    $row[$field] = $value;
                }
                $row['updated_at'] = date('Y-m-d H:i:s');
                $updated = true;
            }
        }
        
        if ($updated) {
            $this->writeFile($table, $allData);
        }
        
        return $updated;
    }
    
    public function delete($table, $conditions) {
        $allData = $this->readFile($table);
        $originalCount = count($allData);
        
        $allData = array_filter($allData, function($row) use ($conditions) {
            // Check if ALL conditions match (AND logic)
            foreach ($conditions as $field => $value) {
                if ($row[$field] != $value) {
                    return true; // Keep this row (condition doesn't match)
                }
            }
            return false; // Remove this row (all conditions match)
        });
        
        // Re-index array
        $allData = array_values($allData);
        
        $this->writeFile($table, $allData);
        
        return $originalCount - count($allData);
    }
    
    public function count($table, $conditions = []) {
        $data = $this->select($table, $conditions);
        return count($data);
    }
    
    // Helper method for search functionality
    public function search($table, $searchField, $searchTerm, $limit = null) {
        $data = $this->readFile($table);
        
        $results = array_filter($data, function($row) use ($searchField, $searchTerm) {
            return stripos($row[$searchField], $searchTerm) !== false;
        });
        
        if ($limit) {
            $results = array_slice($results, 0, $limit);
        }
        
        return array_values($results);
    }
    
    // Helper method for pagination
    public function paginate($table, $conditions = [], $page = 1, $perPage = 10, $orderBy = null) {
        $allData = $this->select($table, $conditions, null, $orderBy);
        $total = count($allData);
        $offset = ($page - 1) * $perPage;
        $data = array_slice($allData, $offset, $perPage);
        
        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }
    
    // Join functionality for related data
    public function join($mainTable, $joinTable, $mainKey, $joinKey, $conditions = []) {
        $mainData = $this->select($mainTable, $conditions);
        $joinData = $this->readFile($joinTable);
        
        // Create lookup array for join table
        $joinLookup = [];
        foreach ($joinData as $row) {
            $joinLookup[$row[$joinKey]] = $row;
        }
        
        // Add joined data to main data
        foreach ($mainData as &$row) {
            if (isset($joinLookup[$row[$mainKey]])) {
                $row['joined_' . $joinTable] = $joinLookup[$row[$mainKey]];
            }
        }
        
        return $mainData;
    }
}

// Global instance
$jsonDb = new JsonDatabase();