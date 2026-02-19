<?php

namespace App\Hinata\Controller;

use Core\Auth;
use Core\Database;
use Core\MediaAssetModel;
use Core\Logger;

/**
 * メディア管理コントローラ
 * 物理パス: haitaka/private/apps/Hinata/Controller/MediaController.php
 */
class MediaController {
    
    /**
     * カテゴリ定義（hn_media_metadata.category のバリエーション）
     */
    private const CATEGORIES = [
        'CM' => 'CM',
        'Hinareha' => 'Hinareha',
        'Live' => 'Live',
        'MV' => 'MV',
        'SelfIntro' => 'SelfIntro',
        'SoloPV' => 'SoloPV',
        'Special' => 'Special',
        'Teaser' => 'Teaser',
        'Trailer' => 'Trailer',
        'Variety' => 'Variety',
    ];

    /**
     * カテゴリ一覧取得（DB優先、無ければ定数フォールバック）
     * 戻り値: ['name' => 'name', ...] 形式
     */
    private function getMediaCategories(): array {
        try {
            $pdo = Database::connect();
            $stmt = $pdo->query("SELECT name FROM hn_media_categories ORDER BY sort_order ASC, name ASC");
            $rows = $stmt ? $stmt->fetchAll(\PDO::FETCH_COLUMN) : [];
            if (!empty($rows)) {
                $assoc = [];
                foreach ($rows as $name) {
                    $assoc[$name] = $name;
                }
                return $assoc;
            }
        } catch (\Throwable $e) {
            // テーブル未作成等は定数にフォールバック
        }
        return self::CATEGORIES;
    }

    /**
     * 動画一覧画面の表示
     */
    public function list(): void {
        $auth = new Auth();
        $auth->requireLogin();

        $memberModel = new \App\Hinata\Model\MemberModel();
        $categories = $this->getMediaCategories();
        $members = $memberModel->getAllWithColors();
        $user = $_SESSION['user'];
        require_once __DIR__ . '/../Views/media_list.php';
    }

    /**
     * 動画一覧取得API（無限スクロール対応）
     * GET: ?offset=0&limit=25&category=&sort=newest
     * Response: { status: 'success', data: [...], has_more: bool }
     */
    public function loadMore(): void {
        header('Content-Type: application/json');
        
        try {
            $offset = (int)($_GET['offset'] ?? 0);
            $limit = (int)($_GET['limit'] ?? 25);
            $category = $_GET['category'] ?? '';
            $sort = $_GET['sort'] ?? 'newest';
            $memberId = (int)($_GET['member_id'] ?? 0);
            $generation = $_GET['generation'] ?? '';

            // limit の上限チェック
            if ($limit > 100) {
                $limit = 100;
            }

            $pdo = Database::connect();
            
            // WHERE条件構築
            $where = [];
            $params = [];
            
            if (!empty($category)) {
                $where[] = "hmeta.category = :category";
                $params['category'] = $category;
            }
            if ($memberId > 0) {
                $where[] = "EXISTS (SELECT 1 FROM hn_media_members hmm WHERE hmm.media_meta_id = hmeta.id AND hmm.member_id = :member_id)";
                $params['member_id'] = $memberId;
            }
            if ($generation !== '' && $generation !== null) {
                $where[] = "EXISTS (SELECT 1 FROM hn_media_members hmm2 JOIN hn_members m ON hmm2.member_id = m.id WHERE hmm2.media_meta_id = hmeta.id AND m.generation = :generation)";
                $params['generation'] = $generation;
            }
            
            $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
            
            // ORDER BY: アップロード日（upload_date）を優先、NULL の場合は登録日（created_at）で補完
            $orderBy = match($sort) {
                'oldest' => 'COALESCE(ma.upload_date, ma.created_at) ASC',
                'title' => 'ma.title ASC',
                default => 'COALESCE(ma.upload_date, ma.created_at) DESC',  // newest（デフォルト）
            };

            // メイン取得（limit+1件取得して has_more を判定）
            // 日付系は将来的に com_media_assets.upload_date を主体に扱う想定
            $sql = "SELECT 
                        hmeta.id as meta_id,
                        hmeta.category,
                        hmeta.release_date,
                        ma.id as asset_id,
                        ma.platform,
                        ma.media_key,
                        ma.sub_key,
                        ma.title,
                        ma.thumbnail_url,
                        ma.description,
                        ma.upload_date,
                        ma.created_at
                    FROM hn_media_metadata hmeta
                    JOIN com_media_assets ma ON hmeta.asset_id = ma.id
                    {$whereClause}
                    ORDER BY {$orderBy}
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':limit', $limit + 1, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            
            $stmt->execute();
            $results = $stmt->fetchAll();
            
            // has_more 判定
            $hasMore = count($results) > $limit;
            if ($hasMore) {
                array_pop($results);  // 余分な1件を削除
            }

            // thumbnail_url が空のとき、プラットフォームから表示用URLを補完（DBは無理に設定しなくてよい）
            $this->fillThumbnailUrlFromPlatform($results);

            echo json_encode([
                'status' => 'success',
                'data' => $results,
                'has_more' => $hasMore,
            ]);
        } catch (\Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * インポート画面の表示
     */
    public function import(): void {
        $auth = new Auth();
        // 日向坂ポータル管理者（admin / hinata_admin）のみ
        $auth->requireHinataAdmin('/hinata/');

        $categories = $this->getMediaCategories();
        $user = $_SESSION['user'];
        require_once __DIR__ . '/../Views/media_import.php';
    }

    /**
     * 動画・メンバー紐付け管理画面（管理者専用）
     */
    public function mediaMemberAdmin(): void {
        $auth = new Auth();
        // 日向坂ポータル管理者（admin / hinata_admin）のみ
        $auth->requireHinataAdmin('/hinata/');
        $memberModel = new \App\Hinata\Model\MemberModel();
        $categories = $this->getMediaCategories();
        $members = $memberModel->getAllWithColors();
        $user = $_SESSION['user'];
        require_once __DIR__ . '/../Views/media_member_admin.php';
    }

    /**
     * 動画設定管理画面（カテゴリ変更など）（管理者専用）
     */
    public function mediaSettingsAdmin(): void {
        $auth = new Auth();
        $auth->requireHinataAdmin('/hinata/');
        $categories = $this->getMediaCategories();
        $user = $_SESSION['user'];
        require_once __DIR__ . '/../Views/media_settings_admin.php';
    }

    /**
     * 動画メタデータ更新API（カテゴリ変更など）
     * POST: { meta_id: int, category: string }
     */
    public function updateMetadata(): void {
        header('Content-Type: application/json');
        try {
            $auth = new Auth();
            if (!$auth->check() || !$auth->isHinataAdmin()) {
                echo json_encode(['status' => 'error', 'message' => '権限がありません']);
                return;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?: [];
            $metaId = (int)($input['meta_id'] ?? 0);
            $category = trim($input['category'] ?? '');
            if (!$metaId) {
                echo json_encode(['status' => 'error', 'message' => 'meta_id required']);
                return;
            }
            $allowed = $this->getMediaCategories();
            if (!array_key_exists($category, $allowed) && $category !== '') {
                echo json_encode(['status' => 'error', 'message' => '無効なカテゴリです']);
                return;
            }
            $mediaModel = new MediaAssetModel();
            $ok = $mediaModel->updateMetadataCategory($metaId, $category === '' ? null : $category);
            if (!$ok) {
                echo json_encode(['status' => 'error', 'message' => '更新に失敗しました']);
                return;
            }
            Logger::info("hn_media_metadata update meta_id={$metaId} category={$category} by=" . ($_SESSION['user']['id_name'] ?? 'guest'));
            echo json_encode(['status' => 'success', 'category' => $category ?: null]);
        } catch (\Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * カテゴリ一覧取得API
     * GET
     */
    public function listMediaCategories(): void {
        header('Content-Type: application/json');
        try {
            $auth = new Auth();
            if (!$auth->check()) {
                echo json_encode(['status' => 'error', 'message' => '権限がありません']);
                return;
            }
            $categories = $this->getMediaCategories();
            $list = array_keys($categories);
            echo json_encode(['status' => 'success', 'data' => $list]);
        } catch (\Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * カテゴリ新規作成API
     * POST: { name: string }
     */
    public function createMediaCategory(): void {
        header('Content-Type: application/json');
        try {
            $auth = new Auth();
            if (!$auth->check() || !$auth->isHinataAdmin()) {
                echo json_encode(['status' => 'error', 'message' => '権限がありません']);
                return;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?: [];
            $name = trim($input['name'] ?? '');
            if ($name === '') {
                echo json_encode(['status' => 'error', 'message' => 'カテゴリ名を入力してください']);
                return;
            }
            if (strlen($name) > 64) {
                echo json_encode(['status' => 'error', 'message' => 'カテゴリ名は64文字以内で入力してください']);
                return;
            }
            $pdo = Database::connect();
            $maxOrder = $pdo->query("SELECT COALESCE(MAX(sort_order), 0) FROM hn_media_categories")->fetchColumn();
            $stmt = $pdo->prepare("INSERT INTO hn_media_categories (name, sort_order) VALUES (:name, :sort_order)");
            $stmt->execute(['name' => $name, 'sort_order' => (int)$maxOrder + 1]);
            Logger::info("hn_media_categories create name={$name} by=" . ($_SESSION['user']['id_name'] ?? 'guest'));
            echo json_encode(['status' => 'success', 'name' => $name]);
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                echo json_encode(['status' => 'error', 'message' => '同じ名前のカテゴリが既に存在します']);
            } else {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
        } catch (\Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * カテゴリ名称変更API
     * POST: { old_name: string, new_name: string }
     */
    public function renameMediaCategory(): void {
        header('Content-Type: application/json');
        try {
            $auth = new Auth();
            if (!$auth->check() || !$auth->isHinataAdmin()) {
                echo json_encode(['status' => 'error', 'message' => '権限がありません']);
                return;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?: [];
            $oldName = trim($input['old_name'] ?? '');
            $newName = trim($input['new_name'] ?? '');
            if ($oldName === '' || $newName === '') {
                echo json_encode(['status' => 'error', 'message' => '旧名称と新名称を入力してください']);
                return;
            }
            if ($oldName === $newName) {
                echo json_encode(['status' => 'success', 'name' => $newName]);
                return;
            }
            if (strlen($newName) > 64) {
                echo json_encode(['status' => 'error', 'message' => 'カテゴリ名は64文字以内で入力してください']);
                return;
            }
            $pdo = Database::connect();
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("UPDATE hn_media_categories SET name = :new_name WHERE name = :old_name");
                $stmt->execute(['old_name' => $oldName, 'new_name' => $newName]);
                if ($stmt->rowCount() === 0) {
                    $pdo->rollBack();
                    echo json_encode(['status' => 'error', 'message' => '指定されたカテゴリが見つかりません']);
                    return;
                }
                $stmt = $pdo->prepare("UPDATE hn_media_metadata SET category = :new_name WHERE category = :old_name");
                $stmt->execute(['old_name' => $oldName, 'new_name' => $newName]);
                $pdo->commit();
                Logger::info("hn_media_categories rename {$oldName}->{$newName} by=" . ($_SESSION['user']['id_name'] ?? 'guest'));
                echo json_encode(['status' => 'success', 'name' => $newName]);
            } catch (\Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                echo json_encode(['status' => 'error', 'message' => '同じ名前のカテゴリが既に存在します']);
            } else {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
        } catch (\Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * 動画・楽曲紐付け管理画面（管理者専用）
     */
    public function mediaSongAdmin(): void {
        $auth = new Auth();
        // 日向坂ポータル管理者（admin / hinata_admin）のみ
        $auth->requireHinataAdmin('/hinata/');
        $releaseModel = new \App\Hinata\Model\ReleaseModel();
        $categories = $this->getMediaCategories();
        $releases = $releaseModel->getAllReleases();
        $releasesWithSongs = [];
        foreach ($releases as $r) {
            $full = $releaseModel->getReleaseWithSongs((int)$r['id']);
            if ($full) {
                $releasesWithSongs[] = $full;
            }
        }
        $trackTypesDisplay = \App\Hinata\Model\SongModel::TRACK_TYPES_DISPLAY;
        $user = $_SESSION['user'];
        require_once __DIR__ . '/../Views/media_song_admin.php';
    }

    /**
     * 紐付け管理用：動画一覧取得API
     * GET: ?q=&category=&limit=100
     */
    public function listMediaForLink(): void {
        header('Content-Type: application/json');
        try {
            $auth = new Auth();
            if (!$auth->check() || !$auth->isHinataAdmin()) {
                echo json_encode(['status' => 'error', 'message' => '権限がありません']);
                return;
            }
            $q = trim($_GET['q'] ?? '');
            $category = $_GET['category'] ?? '';
            $unlinkedOnly = !empty($_GET['unlinked_only']);
            $linkType = $_GET['link_type'] ?? 'song';
            $limit = min((int)($_GET['limit'] ?? 100), 200);
            $pdo = Database::connect();
            $where = [];
            $params = [];
            if ($category === '__unset__') {
                $where[] = '(hmeta.category IS NULL OR hmeta.category = \'\')';
            } elseif ($category !== '') {
                $where[] = 'hmeta.category = :category';
                $params['category'] = $category;
            }
            if (!empty($q)) {
                $where[] = '(ma.title LIKE :q OR ma.media_key LIKE :q2)';
                $params['q'] = '%' . $q . '%';
                $params['q2'] = '%' . $q . '%';
            }
            if ($unlinkedOnly) {
                if ($linkType === 'member') {
                    $where[] = 'NOT EXISTS (SELECT 1 FROM hn_media_members hmm WHERE hmm.media_meta_id = hmeta.id)';
                } else {
                    $where[] = 'NOT EXISTS (SELECT 1 FROM hn_song_media_links l WHERE l.media_meta_id = hmeta.id)';
                }
            }
            $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
            $sql = "SELECT 
                        hmeta.id as meta_id, 
                        hmeta.category, 
                        hmeta.release_date,
                        ma.id as asset_id, 
                        ma.platform, 
                        ma.media_key, 
                        ma.title, 
                        ma.thumbnail_url,
                        ma.description,
                        ma.upload_date
                    FROM hn_media_metadata hmeta
                    JOIN com_media_assets ma ON hmeta.asset_id = ma.id
                    {$whereClause}
                    ORDER BY 
                        COALESCE(ma.upload_date, ma.created_at) DESC,
                        ma.title ASC
                    LIMIT :limit";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            foreach ($params as $k => $v) {
                $stmt->bindValue(":$k", $v);
            }
            $stmt->execute();
            $data = $stmt->fetchAll();
            $this->fillThumbnailUrlFromPlatform($data);
            echo json_encode(['status' => 'success', 'data' => $data]);
        } catch (\Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * thumbnail_url が空の行について、platform + media_key から表示用URLを補完する。
     * DBの thumbnail_url は任意（無理に設定しなくてよい）。YouTube は media_key から常に生成可能。
     */
    private function fillThumbnailUrlFromPlatform(array &$rows): void {
        foreach ($rows as &$row) {
            if (!empty($row['thumbnail_url'])) {
                continue;
            }
            if (($row['platform'] ?? '') === 'youtube' && !empty($row['media_key'])) {
                $row['thumbnail_url'] = 'https://img.youtube.com/vi/' . $row['media_key'] . '/mqdefault.jpg';
            }
        }
        unset($row);
    }

    /**
     * 紐付け管理用：動画に紐づくメンバー一覧取得API
     * GET: ?meta_id=123
     */
    public function getMediaMembers(): void {
        header('Content-Type: application/json');
        try {
            $auth = new Auth();
            if (!$auth->check() || !$auth->isHinataAdmin()) {
                echo json_encode(['status' => 'error', 'message' => '権限がありません']);
                return;
            }
            $metaId = (int)($_GET['meta_id'] ?? 0);
            if (!$metaId) {
                echo json_encode(['status' => 'error', 'message' => 'meta_id required']);
                return;
            }
            $pdo = Database::connect();
            $stmt = $pdo->prepare("
                SELECT m.id, m.name, m.kana, m.generation, m.is_active
                FROM hn_media_members hmm
                JOIN hn_members m ON hmm.member_id = m.id
                WHERE hmm.media_meta_id = :meta_id
                ORDER BY m.generation ASC, m.kana ASC
            ");
            $stmt->execute(['meta_id' => $metaId]);
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
        } catch (\Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * 紐付け管理用：動画・メンバー紐付け保存API
     * POST: { meta_id: int, member_ids: int[] }
     */
    public function saveMediaMembers(): void {
        header('Content-Type: application/json');
        try {
            $auth = new Auth();
            if (!$auth->check() || !$auth->isHinataAdmin()) {
                echo json_encode(['status' => 'error', 'message' => '権限がありません']);
                return;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?: [];
            $metaId = (int)($input['meta_id'] ?? 0);
            $memberIds = $input['member_ids'] ?? [];
            if (!is_array($memberIds)) {
                $memberIds = [];
            }
            $memberIds = array_map('intval', $memberIds);
            $memberIds = array_filter($memberIds, fn($v) => $v > 0);
            $memberIds = array_values(array_unique($memberIds));

            if (!$metaId) {
                echo json_encode(['status' => 'error', 'message' => 'meta_id required']);
                return;
            }

            $pdo = Database::connect();
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('DELETE FROM hn_media_members WHERE media_meta_id = ?');
            $stmt->execute([$metaId]);
            $stmt = $pdo->prepare('INSERT INTO hn_media_members (media_meta_id, member_id, update_user) VALUES (?, ?, ?)');
            foreach ($memberIds as $mid) {
                $stmt->execute([$metaId, $mid, $_SESSION['user']['id_name'] ?? '']);
            }
            $pdo->commit();
            Logger::info("hn_media_members save meta_id={$metaId} count=" . count($memberIds) . " by=" . ($_SESSION['user']['id_name'] ?? 'guest'));
            echo json_encode(['status' => 'success', 'saved' => count($memberIds)]);
        } catch (\Exception $e) {
            $pdo = Database::connect();
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * 動画・楽曲紐付け用：指定動画に紐づく楽曲を1件取得
     * GET: ?meta_id=123
     */
    public function getMediaLinkedSong(): void {
        header('Content-Type: application/json');
        try {
            $auth = new Auth();
            if (!$auth->check() || !$auth->isHinataAdmin()) {
                echo json_encode(['status' => 'error', 'message' => '権限がありません']);
                return;
            }
            $metaId = (int)($_GET['meta_id'] ?? 0);
            if (!$metaId) {
                echo json_encode(['status' => 'error', 'message' => 'meta_id required']);
                return;
            }
            $pdo = Database::connect();
            $stmt = $pdo->prepare("
                SELECT s.id, s.release_id, s.title, s.track_type, s.track_number,
                       r.title as release_title, r.release_number
                FROM hn_song_media_links l
                JOIN hn_songs s ON s.id = l.song_id
                JOIN hn_releases r ON s.release_id = r.id
                WHERE l.media_meta_id = :meta_id
                LIMIT 1
            ");
            $stmt->execute(['meta_id' => $metaId]);
            $song = $stmt->fetch(\PDO::FETCH_ASSOC);
            echo json_encode(['status' => 'success', 'data' => $song ?: null]);
        } catch (\Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * 動画・楽曲紐付け保存API
     * POST: { meta_id: int, song_id: int|null }  song_id=null で紐付け解除
     */
    public function saveMediaSongLink(): void {
        header('Content-Type: application/json');
        try {
            $auth = new Auth();
            if (!$auth->check() || !$auth->isHinataAdmin()) {
                echo json_encode(['status' => 'error', 'message' => '権限がありません']);
                return;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?: [];
            $metaId = (int)($input['meta_id'] ?? 0);
            $songId = isset($input['song_id']) ? (int)$input['song_id'] : null;
            if ($songId !== null && $songId <= 0) {
                $songId = null;
            }

            if (!$metaId) {
                echo json_encode(['status' => 'error', 'message' => 'meta_id required']);
                return;
            }

            $pdo = Database::connect();
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('DELETE FROM hn_song_media_links WHERE media_meta_id = ?');
            $stmt->execute([$metaId]);
            if ($songId !== null) {
                $stmt = $pdo->prepare('INSERT INTO hn_song_media_links (song_id, media_meta_id, update_user) VALUES (?, ?, ?)');
                $stmt->execute([$songId, $metaId, $_SESSION['user']['id_name'] ?? '']);
            }
            $pdo->commit();
            Logger::info("hn_song_media_links save meta_id={$metaId} song_id=" . ($songId ?? 'null') . " by=" . ($_SESSION['user']['id_name'] ?? 'guest'));
            echo json_encode(['status' => 'success']);
        } catch (\Exception $e) {
            $pdo = Database::connect();
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * プレビューAPI
     * POST: { raw_input: string, default_category: string }
     * Response: { status: 'success', data: [...], releases: [...] }
     */
    public function preview(): void {
        header('Content-Type: application/json');
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $rawInput = $input['raw_input'] ?? '';
            $defaultCategory = $input['default_category'] ?? 'MV';

            if (empty($rawInput)) {
                throw new \Exception('入力データが空です');
            }

            // カテゴリ検証
            $allowed = $this->getMediaCategories();
            if (!isset($allowed[$defaultCategory])) {
                throw new \Exception('無効なカテゴリです');
            }

            $lines = array_filter(array_map('trim', explode("\n", $rawInput)));
            $results = [];
            $mediaModel = new MediaAssetModel();

            foreach ($lines as $line) {
                $parsed = $this->parseLine($line, $defaultCategory, $mediaModel);
                if ($parsed) {
                    $results[] = $parsed;
                }
            }

            // リリース一覧を取得（楽曲情報登録用）
            $releaseModel = new \App\Hinata\Model\ReleaseModel();
            $releases = $releaseModel->getAllReleases();

            echo json_encode([
                'status' => 'success', 
                'data' => $results,
                'releases' => $releases,
                'track_types' => \App\Hinata\Model\SongModel::TRACK_TYPES
            ]);
        } catch (\Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * 一括保存API
     * POST: { items: [ { url, title, category, release_date, status, song_info?: {...} }, ... ] }
     */
    public function bulkSave(): void {
        header('Content-Type: application/json');
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $items = $input['items'] ?? [];

            if (empty($items)) {
                throw new \Exception('保存するデータがありません');
            }

            $pdo = Database::connect();
            $pdo->beginTransaction();

            $mediaModel = new MediaAssetModel();
            $songModel = new \App\Hinata\Model\SongModel();
            $saved = 0;
            $skipped = 0;

            foreach ($items as $item) {
                // Registered または Error ステータスはスキップ
                if (in_array($item['status'] ?? '', ['Registered', 'Error'])) {
                    $skipped++;
                    continue;
                }

                $parsed = $mediaModel->parseUrl($item['url'] ?? '');
                if (!$parsed) {
                    $skipped++;
                    continue;
                }

                // Asset 登録
                $assetId = $mediaModel->findOrCreateAsset(
                    $parsed['platform'],
                    $parsed['media_key'],
                    $parsed['sub_key'],
                    $item['title'] ?? '',
                    null, // サムネイルURLは自動生成
                    $uploadDate
                );

                if (!$assetId) {
                    $skipped++;
                    continue;
                }

                // Metadata 登録
                $category = $item['category'] ?? 'MV';
                // CSV上の「公開日」は動画のアップロード日時として扱う
                $uploadDate = !empty($item['release_date']) ? $item['release_date'] : null;
                
                $metaId = $mediaModel->findOrCreateMetadata(
                    $assetId,
                    $category
                );

                if (!$metaId) {
                    $skipped++;
                    continue;
                }

                // 楽曲情報の登録（カテゴリが MV の場合）
                if ($category === 'MV') {
                    // CSV入力のrelease_idを優先、なければsong_infoから取得
                    $releaseId = $item['release_id'] ?? null;
                    $songInfo = $item['song_info'] ?? [];
                    
                    if (empty($releaseId) && !empty($songInfo['release_id'])) {
                        $releaseId = $songInfo['release_id'];
                    }
                    
                    // release_id が指定されている場合のみ楽曲登録
                    if (!empty($releaseId)) {
                        // 既存の楽曲があるかチェック（media_meta_id で一意）
                        $existingSong = $pdo->prepare("
                            SELECT id FROM hn_songs WHERE media_meta_id = ?
                        ");
                        $existingSong->execute([$metaId]);
                        
                        if (!$existingSong->fetchColumn()) {
                            // 楽曲新規登録
                            $songModel->create([
                                'release_id' => (int)$releaseId,
                                'media_meta_id' => $metaId,
                                'title' => $songInfo['title'] ?? $item['title'],
                                'track_type' => $songInfo['track_type'] ?? 'title',  // MVはデフォルトで表題曲
                                'track_number' => $songInfo['track_number'] ?? null,
                            ]);
                        }
                    }
                }

                $saved++;
            }

            $pdo->commit();
            Logger::info("media bulkSave saved={$saved} skipped={$skipped} by=" . ($_SESSION['user']['id_name'] ?? 'guest'));

            echo json_encode([
                'status' => 'success',
                'saved' => $saved,
                'skipped' => $skipped,
                'message' => "{$saved}件を登録しました。{$skipped}件をスキップしました。"
            ]);
        } catch (\Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * 1行のCSV/TSVデータを解析
     */
    private function parseLine(string $line, string $defaultCategory, MediaAssetModel $mediaModel): ?array {
        // タブ区切りを優先、なければカンマ区切り
        $parts = preg_split('/\t/', $line);
        if (count($parts) === 1) {
            $parts = preg_split('/,/', $line);
        }

        $url = trim($parts[0] ?? '');
        $title = trim($parts[1] ?? '');
        $singleNumber = trim($parts[2] ?? '');  // シングル番号
        $releaseDate = trim($parts[3] ?? '');
        $category = trim($parts[4] ?? '') ?: $defaultCategory;

        // URL必須チェック
        if (empty($url)) {
            return null;
        }

        // タイトル必須チェック
        if (empty($title)) {
            return [
                'url' => $url,
                'title' => '',
                'category' => $category,
                'release_date' => $releaseDate,
                'status' => 'Error',
                'message' => 'タイトルが必要です',
                'thumbnail' => '',
                'video_key' => '',
            ];
        }

        // URL解析
        $parsed = $mediaModel->parseUrl($url);
        if (!$parsed) {
            return [
                'url' => $url,
                'title' => $title,
                'category' => $category,
                'release_date' => $releaseDate,
                'status' => 'Error',
                'message' => '対応していないURLです',
                'thumbnail' => '',
                'video_key' => '',
            ];
        }

        // サムネイル生成（YouTubeのみ）
        $thumbnail = '';
        if ($parsed['platform'] === 'youtube') {
            $thumbnail = "https://img.youtube.com/vi/{$parsed['media_key']}/default.jpg";
        }

        // 重複チェック
        $status = $this->checkDuplicateStatus($parsed, $category);

        // シングル番号からリリースIDを検索
        $releaseId = null;
        if (!empty($singleNumber) && $category === 'MV') {
            $releaseId = $this->findReleaseIdBySingleNumber($singleNumber);
        }

        return [
            'url' => $url,
            'title' => $title,
            'single_number' => $singleNumber,
            'release_id' => $releaseId,
            'category' => $category,
            'release_date' => $releaseDate,
            'status' => $status,
            'message' => $this->getStatusMessage($status),
            'thumbnail' => $thumbnail,
            'video_key' => $parsed['media_key'],
        ];
    }

    /**
     * シングル番号からリリースIDを検索
     * 
     * @param string $singleNumber "1", "2", "1st", "2nd" などの形式に対応
     * @return int|null リリースID
     */
    private function findReleaseIdBySingleNumber(string $singleNumber): ?int {
        $pdo = Database::connect();
        
        // 数字のみ抽出（"1st" → "1", "2nd" → "2"）
        $number = preg_replace('/[^0-9]/', '', $singleNumber);
        
        if (empty($number)) {
            return null;
        }
        
        // release_number から検索（前方一致 or LIKE検索）
        // "1st", "1stシングル", "1" などに対応
        $stmt = $pdo->prepare("
            SELECT id FROM hn_releases 
            WHERE release_number LIKE :pattern1 
               OR release_number LIKE :pattern2
               OR release_number = :exact
            LIMIT 1
        ");
        
        $stmt->execute([
            'pattern1' => $number . '%',      // "1%" → "1st", "1stシングル"
            'pattern2' => '%' . $number . '%', // "%1%" → "第1弾"など
            'exact' => $number,                // "1"
        ]);
        
        $releaseId = $stmt->fetchColumn();
        return $releaseId ? (int)$releaseId : null;
    }

    /**
     * 重複ステータスをチェック
     * New: 完全新規
     * Linked: 素材は存在するが、日向坂メタデータは未登録
     * Registered: 日向坂メタデータとして登録済み
     */
    private function checkDuplicateStatus(array $parsed, string $category): string {
        $pdo = Database::connect();

        // 1. com_media_assets にあるか
        $stmt = $pdo->prepare("
            SELECT id FROM com_media_assets 
            WHERE platform = :platform AND media_key = :media_key
        ");
        $stmt->execute([
            'platform' => $parsed['platform'],
            'media_key' => $parsed['media_key'],
        ]);
        $assetId = $stmt->fetchColumn();

        if (!$assetId) {
            return 'New'; // 完全新規
        }

        // 2. hn_media_metadata に登録済みか（asset_id で一意性保証）
        $stmt = $pdo->prepare("
            SELECT id FROM hn_media_metadata 
            WHERE asset_id = :asset_id
        ");
        $stmt->execute(['asset_id' => $assetId]);
        $metaId = $stmt->fetchColumn();

        if ($metaId) {
            return 'Registered'; // 日向坂メタデータとして登録済み
        }

        return 'Linked'; // 素材はあるが日向坂メタデータは未登録
    }

    /**
     * ステータスメッセージ取得
     */
    private function getStatusMessage(string $status): string {
        return match($status) {
            'New' => '新規登録',
            'Linked' => '素材あり（メタデータ新規）',
            'Registered' => '登録済み（スキップ）',
            'Error' => 'エラー',
            default => '',
        };
    }
}
