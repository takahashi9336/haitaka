import json
import subprocess
import sys
import urllib.parse
from datetime import datetime
from pathlib import Path
from typing import Any, Dict, List

import urllib.request
import urllib.error


CONFIG_DIR = Path.home() / ".hinata_tiktok_client"
CONFIG_PATH = CONFIG_DIR / "config.json"


def should_run_now() -> bool:
    """
    9:00, 12:00, 15:00, 18:00, 21:00, 24:00（=翌日0:00）だけ実行する。
    タスクスケジューラは毎時起動想定。
    """
    now = datetime.now()
    h = now.hour

    # 24:00 相当（翌日0:00）
    if h == 0:
        return True

    if not (9 <= h < 24):
        return False

    return (h - 9) % 3 == 0


def load_config() -> Dict[str, Any]:
    if not CONFIG_PATH.exists():
        return {
            "tiktok_account": "",
            "limit": 10,
            "hinata_endpoint": "https://example.com/hinata/batch/tiktok_client_import",
            "token": "kj3hF8s9sdf0a9sdf0as9df",
            "yt_dlp_path": "yt-dlp",
        }
    try:
        with CONFIG_PATH.open("r", encoding="utf-8") as f:
            return json.load(f)
    except Exception:
        return {
            "tiktok_account": "",
            "limit": 10,
            "hinata_endpoint": "https://example.com/hinata/batch/tiktok_client_import",
            "token": "kj3hF8s9sdf0a9sdf0as9df",
            "yt_dlp_path": "yt-dlp",
        }


def save_config(cfg: Dict[str, Any]) -> None:
    CONFIG_DIR.mkdir(parents=True, exist_ok=True)
    with CONFIG_PATH.open("w", encoding="utf-8") as f:
        json.dump(cfg, f, ensure_ascii=False, indent=2)


def parse_custom_url(url: str) -> Dict[str, Any]:
    """
    カスタムURLスキーム（例: hinata-tiktok://run?account=...&limit=10）からパラメータを取得。
    通常のコマンドライン引数でも上書き可能にしておく。
    """
    result: Dict[str, Any] = {}
    try:
        parsed = urllib.parse.urlparse(url)
        query = urllib.parse.parse_qs(parsed.query)
        if "account" in query and query["account"]:
            result["tiktok_account"] = query["account"][0]
        if "limit" in query and query["limit"]:
            try:
                result["limit"] = int(query["limit"][0])
            except ValueError:
                pass
    except Exception:
        pass
    return result


def run_yt_dlp(yt_dlp_path: str, account: str, limit: int) -> List[str]:
    """
    yt-dlp を呼び出して TikTok ユーザーの直近 N 件の動画URL一覧を取得する。
    """
    if not account:
        raise ValueError("tiktok_account が設定されていません")

    base_url = f"https://www.tiktok.com/@{account}"
    cmd = [
        yt_dlp_path,
        "--flat-playlist",
        "--print",
        "https://www.tiktok.com/@%(uploader_id)s/video/%(id)s",
        "--playlist-end",
        str(limit),
        base_url,
    ]

    proc = subprocess.run(
        cmd,
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE,
        text=True,
        encoding="utf-8",
    )
    if proc.returncode != 0:
        raise RuntimeError(f"yt-dlp failed: {proc.stderr.strip()}")

    lines = [line.strip() for line in proc.stdout.splitlines() if line.strip()]
    return lines


def post_to_hinata(endpoint: str, token: str, account: str, urls: List[str]) -> Dict[str, Any]:
    body = {
        "token": token,
        "account": account,
        "urls": urls,
        "category": "Special",
    }
    data = json.dumps(body).encode("utf-8")
    req = urllib.request.Request(
        endpoint,
        data=data,
        headers={
            "Content-Type": "application/json; charset=utf-8",
            "X-Hinata-Tiktok-Token": token,
        },
        method="POST",
    )
    try:
        with urllib.request.urlopen(req, timeout=15) as resp:
            resp_body = resp.read().decode("utf-8")
    except urllib.error.HTTPError as e:
        # サーバ側のエラーレスポンス本文も取得して返す
        body = e.read().decode("utf-8", errors="replace")
        try:
            data = json.loads(body)
        except Exception:
            data = {"status": "error", "message": f"HTTP {e.code}", "raw": body}
        data.setdefault("http_status", e.code)
        return data

    try:
        return json.loads(resp_body)
    except Exception:
        return {"status": "error", "message": "Invalid JSON response", "raw": resp_body}


def main(argv: List[str]) -> int:
    if not should_run_now():
        print("skip: outside 9-24 3-hour schedule")
        return 0

    cfg = load_config()

    # カスタムURLスキームで起動された場合: 第一引数にURLが入る前提
    if len(argv) >= 2 and argv[1].startswith("hinata-tiktok://"):
        override = parse_custom_url(argv[1])
        cfg.update(override)

    # コマンドライン引数での上書き（簡易）
    for arg in argv[1:]:
        if arg.startswith("--account="):
            cfg["tiktok_account"] = arg.split("=", 1)[1]
        elif arg.startswith("--limit="):
            try:
                cfg["limit"] = int(arg.split("=", 1)[1])
            except ValueError:
                pass

    # 必須設定チェック
    account = cfg.get("tiktok_account", "")
    limit = int(cfg.get("limit", 10) or 10)
    endpoint = cfg.get("hinata_endpoint", "")
    token = cfg.get("token", "kj3hF8s9sdf0a9sdf0as9df")
    yt_dlp_path = cfg.get("yt_dlp_path", "yt-dlp")

    if not endpoint or not token:
        print("ERROR: hinata_endpoint または token が未設定です。", file=sys.stderr)
        print(f"設定ファイルを編集してください: {CONFIG_PATH}", file=sys.stderr)
        save_config(cfg)
        return 1

    if not account:
        print("ERROR: tiktok_account が未設定です。", file=sys.stderr)
        print(f"設定ファイルを編集してください: {CONFIG_PATH}", file=sys.stderr)
        save_config(cfg)
        return 1

    # 設定ファイルを保存（初回起動時に雛形を作るため）
    save_config(cfg)

    try:
        urls = run_yt_dlp(yt_dlp_path, account, limit)
        if not urls:
            print("WARNING: yt-dlp からURLが取得できませんでした。", file=sys.stderr)
            return 1
        print(f"取得URL件数: {len(urls)}")
        resp = post_to_hinata(endpoint, token, account, urls)
        print("Hinata response:", json.dumps(resp, ensure_ascii=False, indent=2))
        return 0 if resp.get("status") == "success" else 1
    except Exception as e:
        print(f"ERROR: {e}", file=sys.stderr)
        return 1


if __name__ == "__main__":
    sys.exit(main(sys.argv))

