<?php
declare(strict_types=1);

namespace Forum\Models;

use PDO;

class Discussion
{
    public function __construct(private PDO $pdo) {}

    public function all(): array
    {
        $stmt = $this->pdo->query(
            'SELECT d.*, u.username
             FROM discussions d
             JOIN users u ON u.id = d.user_id
             ORDER BY d.created_at DESC
             LIMIT 100'
        );
        return $stmt->fetchAll();
    }

    public function find(int $id): array|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT d.*, u.username
             FROM discussions d
             JOIN users u ON u.id = d.user_id
             WHERE d.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
}

