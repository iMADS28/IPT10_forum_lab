<?php
declare(strict_types=1);

namespace Forum\Models;

use PDO;

class Comment
{
    public function __construct(private PDO $pdo) {}

    /**
     * Fetch all comments for a discussion (flat list).
     */
    public function allForDiscussion(int $discussionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.*, u.username
             FROM comments c
             JOIN users u ON u.id = c.user_id
             WHERE c.discussion_id = ?
             ORDER BY c.id ASC'
        );
        $stmt->execute([$discussionId]);
        return $stmt->fetchAll();
    }

    /**
     * Build a tree from a flat list.
     * Returns array of root nodes, each with a 'children' key.
     * Depth is calculated (not stored). Indentation capped at 8.
     * Traversal limited to 1000 nodes.
     */
    public function buildTree(array $flat): array
    {
        $map = [];
        foreach ($flat as $row) {
            $row['children'] = [];
            $row['depth']    = 0;
            $map[$row['id']] = $row;
        }

        $roots   = [];
        $visited = 0;
        $limit   = 1000;

        foreach ($map as $id => &$node) {
            if ($visited++ >= $limit) {
                break;
            }
            $parentId = $node['parent_id'];
            if ($parentId === null || !isset($map[$parentId])) {
                $roots[] = &$node;
            } else {
                $map[$parentId]['children'][] = &$node;
            }
        }
        unset($node);

        // Calculate depth recursively
        $this->assignDepth($roots, 0);

        return $roots;
    }

    private function assignDepth(array &$nodes, int $depth): void
    {
        foreach ($nodes as &$node) {
            $node['depth']          = $depth;
            $node['indent']         = min($depth, 8);          // cap at 8
            $node['indent_px']      = min($depth, 8) * 32;     // 32 px per level
            $node['depth_label']    = $depth;
            $node['has_children']   = !empty($node['children']);
            if (!empty($node['children'])) {
                $this->assignDepth($node['children'], $depth + 1);
            }
        }
        unset($node);
    }

    public function create(int $userId, int $discussionId, ?int $parentId, string $body): int
    {
        // Verify parent belongs to same discussion
        if ($parentId !== null) {
            $stmt = $this->pdo->prepare(
                'SELECT id FROM comments WHERE id = ? AND discussion_id = ? LIMIT 1'
            );
            $stmt->execute([$parentId, $discussionId]);
            if (!$stmt->fetch()) {
                throw new \InvalidArgumentException('Invalid parent_id for this discussion.');
            }
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO comments (user_id, discussion_id, parent_id, body)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $discussionId, $parentId, $body]);
        return (int) $this->pdo->lastInsertId();
    }
}

