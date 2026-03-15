<?php

namespace App\Entame\Controller;

use App\Movie\Model\UserMovieModel;
use App\Drama\Model\UserDramaModel;
use App\Anime\Model\UserWorkModel;
use Core\Auth;
use Core\Logger;

class EntameController {
    public function dashboard(): void {
        $auth = new Auth();
        $auth->requireLogin();

        $user = $_SESSION['user'];
        $userId = (int)($user['id'] ?? 0);

        try {
            $movieModel = new UserMovieModel();
            $dramaModel = new UserDramaModel();
            $animeModel = new UserWorkModel();

            $movieWatchlist = $movieModel->countByStatus('watchlist');
            $movieWatched   = $movieModel->countByStatus('watched');
            $movieRuntime   = $movieModel->getTotalWatchedRuntime();

            $dramaWanna  = $dramaModel->countByStatus('wanna_watch');
            $dramaWatching = $dramaModel->countByStatus('watching');
            $dramaWatched  = $dramaModel->countByStatus('watched');
            $dramaRuntime  = $dramaModel->getTotalWatchedRuntime();

            $animeStats = $userId > 0 ? $animeModel->getStatsByUser($userId) : ['wanna_watch' => 0, 'watching' => 0, 'watched' => 0];

            $totalWatchedCount =
                $movieWatched +
                $dramaWatched +
                ($animeStats['watched'] ?? 0);

            $totalWatchlistCount =
                $movieWatchlist +
                $dramaWanna +
                ($animeStats['wanna_watch'] ?? 0);

            $totalRuntime = $movieRuntime + $dramaRuntime;

            $entameStats = [
                'movie' => [
                    'watchlist' => $movieWatchlist,
                    'watched'   => $movieWatched,
                    'runtime'   => $movieRuntime,
                ],
                'drama' => [
                    'wanna_watch' => $dramaWanna,
                    'watching'    => $dramaWatching,
                    'watched'     => $dramaWatched,
                    'runtime'     => $dramaRuntime,
                ],
                'anime' => $animeStats,
            ];
        } catch (\Exception $e) {
            Logger::errorWithContext('Entame dashboard error', $e);
            $totalWatchedCount = $totalWatchlistCount = $totalRuntime = 0;
            $entameStats = [
                'movie' => ['watchlist' => 0, 'watched' => 0, 'runtime' => 0],
                'drama' => ['wanna_watch' => 0, 'watching' => 0, 'watched' => 0, 'runtime' => 0],
                'anime' => ['wanna_watch' => 0, 'watching' => 0, 'watched' => 0],
            ];
        }

        require_once __DIR__ . '/../Views/entame_dashboard.php';
    }
}

