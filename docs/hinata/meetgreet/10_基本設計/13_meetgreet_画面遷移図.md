# ミーグリ（お話し会）& ネタ帳 画面遷移図

## 1. ミーグリ予定・レポ 画面遷移フロー

```mermaid
stateDiagram-v2
    [*] --> HinataTop : /hinata/

    HinataTop --> MeetGreetIndex : サイドバー「ミーグリ予定」

    state MeetGreetIndex {
        [*] --> SlotList
        SlotList --> ImportModal : 「+ 予定を追加」ボタン
        ImportModal --> TextImport : 「テキストで一括追加」タブ
        ImportModal --> ManualAdd : 「手動で1件追加」タブ
        TextImport --> Preview : 「取り込む」ボタン
        Preview --> SlotList : 「この内容で登録する」→ reload
        ManualAdd --> SlotList : 「登録する」→ reload
        SlotList --> FilterFuture : 「今後の予定」フィルタ
        SlotList --> FilterPast : 「過去の予定」フィルタ
        SlotList --> FilterAll : 「すべて」フィルタ
        SlotList --> LinkEvent : 「イベント紐付け」
    }

    MeetGreetIndex --> ReportPage : スロット行「レポ」リンク\n?slot_id=N
    MeetGreetIndex --> ReportNewForm : 直接レポ新規\n?event_id=N (slot_idなし)

    state ReportNewForm {
        [*] --> SelectMember
        SelectMember --> SelectDate
        SelectDate --> SelectEvent
        SelectEvent --> SelectRounds : mg_rounds > 0
        SelectEvent --> InputSlotName : mg_rounds = 0
        SelectRounds --> RegisterSchedule : 「参加予定を登録」→ MeetGreetIndex
        InputSlotName --> CreateAndGo : 「作成してレポを書く」→ ReportPage
    }

    state ReportPage {
        [*] --> ReportList
        ReportList --> MemoSection : メモ展開/折りたたみ
        ReportList --> AddReportModal : 「+ レポ追加」
        AddReportModal --> ReportList : 作成完了 → reload
        ReportList --> ChatInput : メッセージ入力
        ChatInput --> ReportList : 送信 → チャット描画更新
        ReportList --> EditMsgModal : メッセージクリック
        EditMsgModal --> ReportList : 保存/削除
        ReportList --> MetaModal : 歯車アイコン
        MetaModal --> ReportList : 保存 → reload
        ReportList --> HcOverlay : 拡大表示アイコン
        HcOverlay --> ReportList : 閉じる
        ReportList --> AvatarUpload : アバタークリック
        AvatarUpload --> ReportList : アップロード完了
    }

    ReportPage --> MeetGreetIndex : 戻る矢印
    ReportNewForm --> MeetGreetIndex : 戻るリンク
```

## 2. ネタ帳 画面遷移フロー

```mermaid
stateDiagram-v2
    [*] --> HinataTop : /hinata/

    HinataTop --> TalkIndex : サイドバー「ネタ帳」

    state TalkIndex {
        [*] --> NetaView
        NetaView --> MemberSelect : メンバー横スクロール選択
        MemberSelect --> NetaView : 選択メンバーのネタをフィルタ表示

        NetaView --> MemberFilter : メンバーフィルタ\n(すべて/推し/気になる/ネタあり)
        MemberFilter --> NetaView

        NetaView --> MemoQuickFilter : ネタフィルタ\n(未使用/質問/感想/ネタ/お気に入り/すべて)
        MemoQuickFilter --> NetaView

        NetaView --> NetaForm : 「+ 追加」ボタン or 編集アイコン
        NetaForm --> NetaView : 保存 → reload

        NetaView --> ToggleStatus : 使用済みトグル
        ToggleStatus --> NetaView : 即時UI更新

        NetaView --> ToggleFavorite : お気に入りトグル
        ToggleFavorite --> NetaView : 即時UI更新

        NetaView --> RegisteredMemberClick : 右サイドバー メンバークリック
        RegisteredMemberClick --> MemberSelect : メンバー選択と連動
    }

    TalkIndex --> HinataTop : 戻る矢印
```

## 3. 画面間の遷移パラメータ

| 遷移元 | 遷移先 | パラメータ | 説明 |
| :--- | :--- | :--- | :--- |
| ミーグリ予定一覧 | レポページ | `?slot_id={id}` | スロットIDを指定してレポを表示 |
| ミーグリ予定一覧 | レポ新規作成 | `?event_id={id}` | イベントIDを指定して新規作成フォームを表示 |
| レポ新規作成 | レポページ | `?slot_id={id}` | スロット作成後にレポページへリダイレクト |
| レポ新規作成 | ミーグリ予定一覧 | (なし) | 参加予定登録後にリダイレクト |
| ダッシュボード等 | ミーグリ予定一覧 | `?focus_slot_id={id}` | 特定スロットにフォーカスしてスクロール |
| ダッシュボード等 | ミーグリ予定一覧 | `?focus_event_date={date}` | 特定日付のアコーディオンを展開 |
