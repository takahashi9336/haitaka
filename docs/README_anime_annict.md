# アニメアプリ（Annict OAuth）セットアップ

## 概要

Annict API を OAuth 認証で利用し、アニメの視聴状況を管理するアプリです。

## 1. Annict で OAuth クライアントを作成

1. [Annict アプリケーション設定](https://annict.com/settings/apps) にアクセス
2. 「OAuth クライアントアプリケーションを作成」をクリック
3. 以下を入力:
   - **名前**: 任意（例: MyPlatform）
   - **コールバック URI**: `https://あなたのドメイン/anime/oauth_callback.php`
   - 例: `https://example.com/anime/oauth_callback.php`
4. 作成後、**Client ID** と **Client secret** をメモ

## 2. .env に追加

```env
# Annict OAuth
ANNICT_CLIENT_ID=あなたのClient_ID
ANNICT_CLIENT_SECRET=あなたのClient_Secret
ANNICT_REDIRECT_URI=https://あなたのドメイン/anime/oauth_callback.php
```

`ANNICT_REDIRECT_URI` は Annict に登録したコールバック URI と**完全一致**させる必要があります。

## 3. データベースマイグレーション

以下の SQL を実行してください:

```bash
mysql -u USER -p DATABASE < private/migrations/done/create_anime_tables.sql
```

または phpMyAdmin 等で `create_anime_tables.sql` の内容を実行。

## 4. ベータ利用者（本展開前）

`.env` の `ANIME_BETA_ID_NAMES` に、アニメ画面へのアクセスを許可するユーザーの `id_name` をカンマ区切りで指定:

```env
ANIME_BETA_ID_NAMES=takahashi,user2
```

本展開時は sys_apps と sys_role_apps に `anime` を登録し、この制限を解除。

## 5. 利用フロー

1. ダッシュボードから「アニメ」をクリック（ANIME_BETA_ID_NAMES に含まれるユーザーのみ表示）
2. 「Annict で連携する」をクリック
3. Annict の認可画面で許可
4. コールバック後、Annict に登録済みの視聴状況がダッシュボードに表示される
