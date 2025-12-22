<?php

namespace BalikPro\Models;

use BalikPro\Utils\Database;

abstract class BaseModel
{
    protected $pdo;
    protected $table;
    protected $logger;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();

        // 🔑 КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ:
        // Все fetch() и fetchAll() теперь возвращают массивы вида ['col' => value]
        $this->pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

        $this->logger = new \BalikPro\Utils\Logger('models.log');
    }

    protected function generateUuid(): string
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    public function findById(int $id): ?array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch();
            
            return $result ?: null;
        } catch (\PDOException $e) {
            $this->logger->error("Error in findById: " . $e->getMessage());
            return null;
        }
    }

    public function findByUuid(string $uuid): ?array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE uuid = ?");
            $stmt->execute([$uuid]);
            $result = $stmt->fetch();
            
            return $result ?: null;
        } catch (\PDOException $e) {
            $this->logger->error("Error in findByUuid: " . $e->getMessage());
            return null;
        }
    }

    public function findAll(array $conditions = [], int $limit = null, int $offset = 0): array
    {
        try {
            $whereClause = '';
            $params = [];

            if (!empty($conditions)) {
                $whereParts = [];
                foreach ($conditions as $field => $value) {
                    $whereParts[] = "$field = ?";
                    $params[] = $value;
                }
                $whereClause = 'WHERE ' . implode(' AND ', $whereParts);
            }

            $limitClause = $limit ? "LIMIT $limit OFFSET $offset" : '';
            
            $sql = "SELECT * FROM {$this->table} $whereClause ORDER BY id DESC $limitClause";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            $this->logger->error("Error in findAll: " . $e->getMessage());
            return [];
        }
    }

    public function create(array $data): ?int
    {
        try {
            if (!isset($data['uuid'])) {
                $data['uuid'] = $this->generateUuid();
            }

            $fields = implode(', ', array_keys($data));
            $placeholders = ':' . implode(', :', array_keys($data));
            
            $sql = "INSERT INTO {$this->table} ($fields) VALUES ($placeholders)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($data);
            
            return $this->pdo->lastInsertId();
        } catch (\PDOException $e) {
            $this->logger->error("Error in create: " . $e->getMessage());
            return null;
        }
    }

    public function update(int $id, array $data): bool
    {
        try {
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            $setParts = [];
            foreach (array_keys($data) as $field) {
                $setParts[] = "$field = :$field";
            }
            $setClause = implode(', ', $setParts);
            
            $sql = "UPDATE {$this->table} SET $setClause WHERE id = :id";
            $data['id'] = $id;
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($data);
        } catch (\PDOException $e) {
            $this->logger->error("Error in update: " . $e->getMessage());
            return false;
        }
    }

    public function delete(int $id): bool
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (\PDOException $e) {
            $this->logger->error("Error in delete: " . $e->getMessage());
            return false;
        }
    }

    public function count(array $conditions = []): int
    {
        try {
            $whereClause = '';
            $params = [];

            if (!empty($conditions)) {
                $whereParts = [];
                foreach ($conditions as $field => $value) {
                    $whereParts[] = "$field = ?";
                    $params[] = $value;
                }
                $whereClause = 'WHERE ' . implode(' AND ', $whereParts);
            }

            $sql = "SELECT COUNT(*) FROM {$this->table} $whereClause";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return (int)$stmt->fetchColumn();
        } catch (\PDOException $e) {
            $this->logger->error("Error in count: " . $e->getMessage());
            return 0;
        }
    }
}