<?php

namespace App\Movie\Service;

use Core\Logger;
use App\Movie\Model\TmdbApiClient;

/**
 * TMDB credits を mv_movie_credits に保存するサービス
 */
class MovieCreditsService {
    // 作品ごとにDBへ保存する人数（ユーザー集計のランキング上位は別レイヤで算出）
    public const SAVE_CAST_LIMIT = 20;
    public const SAVE_DIRECTOR_LIMIT = 10;
    public const SAVE_WRITER_LIMIT = 10;

    /**
     * credits が既に保存済みか
     */
    public static function hasCreditsForMovie(\PDO $pdo, int $movieId): bool {
        try {
            $stmt = $pdo->prepare("SELECT 1 FROM mv_movie_credits WHERE movie_id = ? LIMIT 1");
            $stmt->execute([$movieId]);
            return (bool)$stmt->fetchColumn();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * TMDB ID を元に credits を取得し、DBへ保存（保存済みならスキップ可）
     */
    public static function ensureCreditsByTmdbId(\PDO $pdo, int $movieId, int $tmdbMovieId, bool $skipIfExists = true): array {
        if ($movieId <= 0 || $tmdbMovieId <= 0) {
            return ['ok' => false, 'skipped' => false, 'message' => 'movie_id/tmdb_id が不正です'];
        }

        if ($skipIfExists && self::hasCreditsForMovie($pdo, $movieId)) {
            return ['ok' => true, 'skipped' => true, 'saved' => 0];
        }

        $tmdb = new TmdbApiClient();
        if (!$tmdb->isConfigured()) {
            return ['ok' => false, 'skipped' => false, 'message' => 'TMDB未設定'];
        }

        $detail = $tmdb->getMovieDetail($tmdbMovieId);
        if (!$detail || !is_array($detail)) {
            return ['ok' => false, 'skipped' => false, 'message' => 'TMDB取得に失敗しました'];
        }

        return self::saveFromTmdbDetail($pdo, $movieId, $tmdbMovieId, $detail);
    }

    /**
     * TMDB詳細レスポンス（credits含む）から保存する
     * @return array{ok:bool, skipped?:bool, saved?:int, cast?:string[], directors?:string[], writers?:string[], message?:string}
     */
    public static function saveFromTmdbDetail(\PDO $pdo, int $movieId, int $tmdbMovieId, array $detail): array {
        $credits = $detail['credits'] ?? null;
        if (!is_array($credits)) {
            return ['ok' => false, 'skipped' => false, 'message' => 'credits が取得できませんでした'];
        }

        $cast = self::pickTopCast($credits, self::SAVE_CAST_LIMIT);
        $directors = self::pickTopCrewByJob($credits, ['Director'], self::SAVE_DIRECTOR_LIMIT);
        $writers = self::pickTopCrewByJob($credits, ['Writer', 'Screenplay', 'Story'], self::SAVE_WRITER_LIMIT);

        $pdo->beginTransaction();
        try {
            $pdo->prepare("DELETE FROM mv_movie_credits WHERE movie_id = ?")->execute([$movieId]);

            $ins = $pdo->prepare("
                INSERT INTO mv_movie_credits
                  (movie_id, tmdb_movie_id, role_kind, rank_no, person_tmdb_id, person_name, character_name, job_name, department)
                VALUES
                  (:movie_id, :tmdb_movie_id, :role_kind, :rank_no, :person_tmdb_id, :person_name, :character_name, :job_name, :department)
            ");

            $saved = 0;
            $rank = 0;
            foreach ($cast as $c) {
                $rank++;
                $ins->execute([
                    'movie_id' => $movieId,
                    'tmdb_movie_id' => $tmdbMovieId,
                    'role_kind' => 'cast',
                    'rank_no' => $rank,
                    'person_tmdb_id' => (int)($c['id'] ?? 0),
                    'person_name' => (string)($c['name'] ?? ''),
                    'character_name' => (string)($c['character'] ?? '') ?: null,
                    'job_name' => null,
                    'department' => null,
                ]);
                $saved++;
            }

            $rank = 0;
            foreach ($directors as $d) {
                $rank++;
                $ins->execute([
                    'movie_id' => $movieId,
                    'tmdb_movie_id' => $tmdbMovieId,
                    'role_kind' => 'director',
                    'rank_no' => $rank,
                    'person_tmdb_id' => (int)($d['id'] ?? 0),
                    'person_name' => (string)($d['name'] ?? ''),
                    'character_name' => null,
                    'job_name' => (string)($d['job'] ?? '') ?: null,
                    'department' => (string)($d['department'] ?? '') ?: null,
                ]);
                $saved++;
            }

            $rank = 0;
            foreach ($writers as $w) {
                $rank++;
                $ins->execute([
                    'movie_id' => $movieId,
                    'tmdb_movie_id' => $tmdbMovieId,
                    'role_kind' => 'writer',
                    'rank_no' => $rank,
                    'person_tmdb_id' => (int)($w['id'] ?? 0),
                    'person_name' => (string)($w['name'] ?? ''),
                    'character_name' => null,
                    'job_name' => (string)($w['job'] ?? '') ?: null,
                    'department' => (string)($w['department'] ?? '') ?: null,
                ]);
                $saved++;
            }

            $pdo->commit();

            return [
                'ok' => true,
                'skipped' => false,
                'saved' => $saved,
                'cast' => array_map(fn($c) => (string)($c['name'] ?? ''), $cast),
                'directors' => array_map(fn($d) => (string)($d['name'] ?? ''), $directors),
                'writers' => array_map(fn($w) => (string)($w['name'] ?? ''), $writers),
            ];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            Logger::errorWithContext('Movie credits save error', $e);
            return ['ok' => false, 'skipped' => false, 'message' => 'credits保存に失敗しました'];
        }
    }

    private static function uniqTopByPersonId(array $items, int $limit): array {
        $out = [];
        $seen = [];
        foreach ($items as $it) {
            $id = (int)($it['id'] ?? 0);
            if ($id <= 0) continue;
            if (isset($seen[$id])) continue;
            $seen[$id] = true;
            $out[] = $it;
            if (count($out) >= $limit) break;
        }
        return $out;
    }

    private static function pickTopCast(array $credits, int $limit): array {
        $cast = $credits['cast'] ?? [];
        if (!is_array($cast)) return [];
        usort($cast, fn($a, $b) => ((int)($a['order'] ?? 9999)) <=> ((int)($b['order'] ?? 9999)));
        return self::uniqTopByPersonId($cast, $limit);
    }

    private static function pickTopCrewByJob(array $credits, array $jobs, int $limit): array {
        $crew = $credits['crew'] ?? [];
        if (!is_array($crew)) return [];
        $jobSet = array_fill_keys($jobs, true);
        $filtered = [];
        foreach ($crew as $c) {
            $job = (string)($c['job'] ?? '');
            if ($job === '' || !isset($jobSet[$job])) continue;
            $filtered[] = $c;
        }
        return self::uniqTopByPersonId($filtered, $limit);
    }
}

