<?php

namespace Core;

class Bootstrap {
    public static function registerErrorHandlers(): void {
        set_error_handler([self::class, 'errorHandler']);
        set_exception_handler([self::class, 'exceptionHandler']);
        register_shutdown_function([self::class, 'shutdownHandler']);
    }

    public static function errorHandler(int $severity, string $message, string $file, int $line): bool {
        if (!(error_reporting() & $severity)) {
            return false;
        }
        $label = self::severityLabel($severity);
        Logger::error("$label: $message in $file:$line");
        return false;
    }

    public static function exceptionHandler(\Throwable $e): void {
        Logger::errorWithContext('Uncaught exception', $e);

        if (php_sapi_name() === 'cli') {
            fwrite(STDERR, 'Error: ' . $e->getMessage() . PHP_EOL);
            exit(1);
        }

        $isApi = false;
        if (!headers_sent()) {
            $uri = $_SERVER['REQUEST_URI'] ?? '';
            $isApi = strpos($uri, '/api/') !== false || preg_match('#\.(json|api)$#', $uri);
        }

        if ($isApi && !headers_sent()) {
            header('Content-Type: application/json; charset=utf-8', true, 500);
            echo json_encode([
                'status' => 'error',
                'message' => 'サーバーエラーが発生しました',
            ], JSON_UNESCAPED_UNICODE);
        } else {
            if (!headers_sent()) {
                http_response_code(500);
                header('Content-Type: text/html; charset=utf-8');
            }
            echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>エラー</title></head>';
            echo '<body><h1>エラーが発生しました</h1><p>しばらくしてから再度お試しください。</p></body></html>';
        }
    }

    public static function shutdownHandler(): void {
        $err = error_get_last();
        if ($err === null || !in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            return;
        }
        Logger::error("Fatal: {$err['message']} in {$err['file']}:{$err['line']}");
    }

    private static function severityLabel(int $severity): string {
        $map = [
            E_WARNING => 'Warning',
            E_NOTICE => 'Notice',
            E_USER_ERROR => 'User Error',
            E_USER_WARNING => 'User Warning',
            E_USER_NOTICE => 'User Notice',
            E_STRICT => 'Strict',
            E_DEPRECATED => 'Deprecated',
            E_USER_DEPRECATED => 'User Deprecated',
        ];
        return $map[$severity] ?? 'Error';
    }
}
