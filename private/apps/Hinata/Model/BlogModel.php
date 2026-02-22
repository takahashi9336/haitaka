<?php

namespace App\Hinata\Model;

use Core\BaseModel;

/**
 * ブログ記事モデル
 * 物理パス: haitaka/private/apps/Hinata/Model/BlogModel.php
 */
class BlogModel extends BaseModel
{
    protected string $table = 'hn_blog_posts';
    protected array $fields = [
        'id', 'member_id', 'article_id', 'title',
        'body_html', 'body_text', 'thumbnail_url',
        'published_at', 'detail_url',
        'created_at', 'updated_at',
    ];
    protected bool $isUserIsolated = false;

    /**
     * article_id で UPSERT (新規なら INSERT、既存なら UPDATE)
     * @return string 'inserted' | 'updated' | 'skipped'
     */
    public function upsertArticle(array $data): string
    {
        $existing = $this->findByArticleId($data['article_id']);
        if ($existing) {
            $this->pdo->prepare(
                "UPDATE {$this->table} SET
                    member_id     = :member_id,
                    title         = :title,
                    body_html     = :body_html,
                    body_text     = :body_text,
                    thumbnail_url = :thumbnail_url,
                    published_at  = :published_at,
                    detail_url    = :detail_url
                 WHERE article_id = :article_id"
            )->execute([
                'member_id'     => $data['member_id'] ?? null,
                'title'         => $data['title'] ?? '',
                'body_html'     => $data['body_html'] ?? null,
                'body_text'     => $data['body_text'] ?? null,
                'thumbnail_url' => $data['thumbnail_url'] ?? null,
                'published_at'  => $data['published_at'],
                'detail_url'    => $data['detail_url'],
                'article_id'    => $data['article_id'],
            ]);
            return 'updated';
        }

        $this->pdo->prepare(
            "INSERT INTO {$this->table}
                (member_id, article_id, title, body_html, body_text, thumbnail_url, published_at, detail_url)
             VALUES
                (:member_id, :article_id, :title, :body_html, :body_text, :thumbnail_url, :published_at, :detail_url)"
        )->execute([
            'member_id'     => $data['member_id'] ?? null,
            'article_id'    => $data['article_id'],
            'title'         => $data['title'] ?? '',
            'body_html'     => $data['body_html'] ?? null,
            'body_text'     => $data['body_text'] ?? null,
            'thumbnail_url' => $data['thumbnail_url'] ?? null,
            'published_at'  => $data['published_at'],
            'detail_url'    => $data['detail_url'],
        ]);
        return 'inserted';
    }

    public function findByArticleId(int $articleId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM {$this->table} WHERE article_id = :aid LIMIT 1"
        );
        $stmt->execute(['aid' => $articleId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * 特定メンバーの最新ブログ取得
     */
    public function getLatestByMember(int $memberId, int $limit = 5): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT bp.*, m.name AS member_name
             FROM {$this->table} bp
             LEFT JOIN hn_members m ON m.id = bp.member_id
             WHERE bp.member_id = :mid
             ORDER BY bp.published_at DESC
             LIMIT :lim"
        );
        $stmt->bindValue('mid', $memberId, \PDO::PARAM_INT);
        $stmt->bindValue('lim', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * 全体の最新ブログ取得
     */
    public function getLatestAll(int $limit = 10): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT bp.*, m.name AS member_name
             FROM {$this->table} bp
             LEFT JOIN hn_members m ON m.id = bp.member_id
             ORDER BY bp.published_at DESC
             LIMIT :lim"
        );
        $stmt->bindValue('lim', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * 推しメンバーの最新ブログ取得
     */
    public function getLatestForOshi(array $memberIds, int $limit = 10): array
    {
        if (empty($memberIds)) return [];

        $placeholders = implode(',', array_fill(0, count($memberIds), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT bp.*, m.name AS member_name
             FROM {$this->table} bp
             LEFT JOIN hn_members m ON m.id = bp.member_id
             WHERE bp.member_id IN ({$placeholders})
             ORDER BY bp.published_at DESC
             LIMIT ?"
        );
        $params = array_values($memberIds);
        $params[] = $limit;
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * メンバー名 → member_id のマッピングを取得
     * official_blog_ct カラム経由で逆引きもサポート
     */
    public function getMemberNameMap(): array
    {
        $stmt = $this->pdo->query(
            "SELECT id, name, official_blog_ct FROM hn_members WHERE is_active = 1"
        );
        $rows = $stmt->fetchAll();
        $map = [];
        foreach ($rows as $r) {
            $normalized = str_replace([' ', '　'], '', $r['name']);
            $map[$normalized] = (int)$r['id'];
        }
        return $map;
    }

    /**
     * official_blog_ct → member_id マッピング
     */
    public function getCtToMemberIdMap(): array
    {
        $stmt = $this->pdo->query(
            "SELECT id, official_blog_ct FROM hn_members WHERE official_blog_ct IS NOT NULL"
        );
        $rows = $stmt->fetchAll();
        $map = [];
        foreach ($rows as $r) {
            $map[(int)$r['official_blog_ct']] = (int)$r['id'];
        }
        return $map;
    }
}
