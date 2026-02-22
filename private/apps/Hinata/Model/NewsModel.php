<?php

namespace App\Hinata\Model;

use Core\BaseModel;

/**
 * ニュースモデル
 */
class NewsModel extends BaseModel
{
    protected string $table = 'hn_news';
    protected array $fields = [
        'id', 'article_code', 'published_date', 'category',
        'title', 'detail_url', 'created_at', 'updated_at',
    ];
    protected bool $isUserIsolated = false;

    public function upsertNews(array $data): string
    {
        $existing = $this->findByCode($data['article_code']);
        if ($existing) {
            $this->pdo->prepare(
                "UPDATE {$this->table} SET
                    published_date = :published_date,
                    category       = :category,
                    title          = :title,
                    detail_url     = :detail_url
                 WHERE article_code = :article_code"
            )->execute([
                'published_date' => $data['published_date'],
                'category'       => $data['category'] ?? '',
                'title'          => $data['title'] ?? '',
                'detail_url'     => $data['detail_url'],
                'article_code'   => $data['article_code'],
            ]);
            return 'updated';
        }

        $this->pdo->prepare(
            "INSERT INTO {$this->table}
                (article_code, published_date, category, title, detail_url)
             VALUES
                (:article_code, :published_date, :category, :title, :detail_url)"
        )->execute([
            'article_code'   => $data['article_code'],
            'published_date' => $data['published_date'],
            'category'       => $data['category'] ?? '',
            'title'          => $data['title'] ?? '',
            'detail_url'     => $data['detail_url'],
        ]);
        return 'inserted';
    }

    public function findByCode(string $code): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE article_code = :c LIMIT 1");
        $stmt->execute(['c' => $code]);
        return $stmt->fetch() ?: null;
    }

    public function getIdByCode(string $code): ?int
    {
        $stmt = $this->pdo->prepare("SELECT id FROM {$this->table} WHERE article_code = :c LIMIT 1");
        $stmt->execute(['c' => $code]);
        $row = $stmt->fetch();
        return $row ? (int)$row['id'] : null;
    }

    /**
     * メンバー紐付けを設定 (既存を削除して再挿入)
     */
    public function setMembers(int $newsId, array $memberIds): void
    {
        $this->pdo->prepare("DELETE FROM hn_news_members WHERE news_id = ?")->execute([$newsId]);
        if (empty($memberIds)) return;
        $stmt = $this->pdo->prepare("INSERT IGNORE INTO hn_news_members (news_id, member_id) VALUES (?, ?)");
        foreach ($memberIds as $mid) {
            $stmt->execute([$newsId, $mid]);
        }
    }

    /**
     * 特定メンバーの最新ニュース取得
     */
    public function getLatestByMember(int $memberId, int $limit = 5): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT n.*
             FROM {$this->table} n
             JOIN hn_news_members nm ON nm.news_id = n.id
             WHERE nm.member_id = :mid
             ORDER BY n.published_date DESC, n.id DESC
             LIMIT :lim"
        );
        $stmt->bindValue('mid', $memberId, \PDO::PARAM_INT);
        $stmt->bindValue('lim', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * メンバー名マップ取得 (スペース除去済み名前 → member_id)
     */
    public function getMemberNameMap(): array
    {
        $stmt = $this->pdo->query("SELECT id, name FROM hn_members WHERE is_active = 1");
        $map = [];
        foreach ($stmt->fetchAll() as $r) {
            $normalized = str_replace([' ', '　'], '', $r['name']);
            $map[$normalized] = (int)$r['id'];
        }
        return $map;
    }

    /**
     * タイトルからメンバー名を検出し member_id 配列を返す
     */
    public function detectMembers(string $title, array $nameMap): array
    {
        $normalizedTitle = str_replace([' ', '　'], '', $title);
        $ids = [];
        foreach ($nameMap as $name => $id) {
            if (mb_strpos($normalizedTitle, $name) !== false) {
                $ids[] = $id;
            }
        }
        return array_unique($ids);
    }
}
