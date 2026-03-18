<?php

namespace App\Anime\Service;

use Core\Database;
use Core\Logger;

/**
 * Annict OAuth 認証サービス
 * 物理パス: haitaka/private/apps/Anime/Service/AnnictOAuthService.php
 */
class AnnictOAuthService {

    private const BASE_URL = 'https://api.annict.com';
    // Annict のアプリ設定側で許可しているスコープと一致させる必要がある
    // 現状は「作品検索などの読み取り」のみで十分なため read のみにしておく
    private const SCOPE = 'read';

    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;

    public function __construct() {
        \Core\Database::connect();
        $this->clientId = $_ENV['ANNICT_CLIENT_ID'] ?? '';
        $this->clientSecret = $_ENV['ANNICT_CLIENT_SECRET'] ?? '';
        $this->redirectUri = $_ENV['ANNICT_REDIRECT_URI'] ?? '';
    }

    public function isConfigured(): bool {
        return !empty($this->clientId) && !empty($this->clientSecret) && !empty($this->redirectUri);
    }

    /**
     * 認可リクエストURLを生成
     */
    public function getAuthorizeUrl(string $state = ''): string {
        $params = [
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'redirect_uri' => $this->redirectUri,
            'scope' => self::SCOPE,
        ];
        if ($state !== '') {
            $params['state'] = $state;
        }
        return self::BASE_URL . '/oauth/authorize?' . http_build_query($params);
    }

    /**
     * 認可コードをアクセストークンに交換し、DB に保存
     * @return array{success: bool, message?: string}
     */
    public function exchangeCodeAndSave(int $userId, string $code): array {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'Annict OAuth が未設定です'];
        }

        $url = self::BASE_URL . '/oauth/token';
        $postData = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectUri,
            'code' => $code,
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_TIMEOUT => 15,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            Logger::error('Annict OAuth token exchange failed: curl error');
            return ['success' => false, 'message' => 'Annict サーバーに接続できません'];
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Logger::error('Annict OAuth JSON parse error: ' . json_last_error_msg());
            return ['success' => false, 'message' => 'レスポンスの解析に失敗しました'];
        }

        if ($httpCode !== 200 || empty($data['access_token'])) {
            $msg = $data['error_description'] ?? $data['error'] ?? 'トークン取得に失敗しました';
            Logger::error('Annict OAuth token error: ' . $msg);
            return ['success' => false, 'message' => $msg];
        }

        $scope = $data['scope'] ?? self::SCOPE;
        $accessToken = $data['access_token'];

        try {
            $pdo = Database::connect();
            $stmt = $pdo->prepare(
                "INSERT INTO an_annict_tokens (user_id, access_token, scope) VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE access_token = VALUES(access_token), scope = VALUES(scope), updated_at = NOW()"
            );
            $stmt->execute([$userId, $accessToken, $scope]);
        } catch (\Throwable $e) {
            Logger::errorWithContext('Annict token save error', $e);
            return ['success' => false, 'message' => 'トークンの保存に失敗しました'];
        }

        return ['success' => true];
    }

    /**
     * ユーザーの Annict アクセストークンを取得
     */
    public static function getTokenForUser(int $userId): ?string {
        try {
            $pdo = Database::connect();
            $stmt = $pdo->prepare("SELECT access_token FROM an_annict_tokens WHERE user_id = ?");
            $stmt->execute([$userId]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $row ? $row['access_token'] : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * ユーザーの Annict 連携を解除
     */
    public static function revokeToken(int $userId): bool {
        try {
            $pdo = Database::connect();
            $stmt = $pdo->prepare("DELETE FROM an_annict_tokens WHERE user_id = ?");
            $stmt->execute([$userId]);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
