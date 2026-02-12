# Quick Memo System Design (Google Keep Style)

## 1. 目的とコンセプト (Context & Concept)

ユーザーが何かを思いついた際、思考を妨げずに「雑に」記録できる場所をダッシュボードに提供する。

- **即時性**: タイトル入力すら不要で、本文のみで即登録可能とする。
    
- **視覚的整理**: 将来的にGoogle Keepのようなカード色変更やピン留めに対応できる拡張性を持たせる。
    
- **厳格な隔離**: `BaseModel` の仕組みを利用し、ユーザー間でメモが混ざらないようにする。
    
- 非同期で保存できるようにして。登録後は入力欄をクリアすること。
    

## 2. データベース設計 (Schema Design)

### 2.1 nt_notes (メモ管理テーブル)

汎用的なメモデータを格納する。

```
CREATE TABLE `nt_notes` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'BaseModelでの隔離に必須',
  `title` varchar(255) DEFAULT NULL COMMENT '省略可能（雑なメモ用）',
  `content` text NOT NULL COMMENT 'メモ本文（Markdown対応を想定）',
  `bg_color` varchar(20) DEFAULT '#ffffff' COMMENT 'カードの背景色（Keep風）',
  `is_pinned` tinyint(1) DEFAULT 0 COMMENT 'トップに固定フラグ',
  `status` varchar(20) DEFAULT 'active' COMMENT 'active, archived, trash',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_status` (`user_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```

## 3. アプリケーション構造 (Application Structure)

### 3.1 Model層

- **App\Note\Model\NoteModel**: `Core\BaseModel` を継承。
    
- **設定**: `protected bool $isUserIsolated = true;` を設定し、クエリに自動で `user_id` が付加されるようにする。
    

### 3.2 Controller / API層

- **App\Note\Controller\NoteController**:
    
    - `store()`: ダッシュボードからの新規保存。タイトルが空の場合は本文の先頭30文字を自動でタイトルに設定。
        
    - `index()`: メモ一覧・詳細編集画面用。
        
- **APIエントリポイント**: `www/note/api/save.php` 等を作成。
    

### 3.3 UI/UX (Dashboard Integration)

- **場所**: `www/index.php` (ダッシュボード) の最上部。
    
- **入力形式**: 本文のみの1エリア（Focus時にボタンが表示される動的なUIが望ましい）。
    
- **非同期通信**: `core.js` の `App.post` を使用してリロードなしで登録。
    

## 5. 拡張ロードマップ

- メモを参照する画面を実装する際は、「Google Keep」を全面的に真似する。
    
- **Card View**: 別画面でメモをタイル状（Masonryレイアウト）に表示。
    
- **Color Picker**: 保存時に背景色を選択できる機能。
    
- **Markdown Preview**: メモ内容をMarkdownとしてレンダリング。