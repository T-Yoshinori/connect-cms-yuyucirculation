# 回覧・決裁プラグイン 基本設計

## 1. 目的
NetCommons3 の回覧板を参考に、Connect-CMS 1.44.1 上で通常回覧、回答付き回覧、多段階承認、差戻し、最終決裁、決裁後回覧、確認履歴を一体で扱うユーザープラグインを実装する。

## 2. 基本方針
- Connect-CMS 標準の users を利用し、独自ユーザーマスタは持たない。
- 所属事業所は sections / user_sections を利用する。1ユーザー1所属を前提とする。
- 主任、管理者、事務局長等の役職は groups / group_users の通常 Group で表現する。
- group_users.group_role は Connect-CMS 標準のグループ内役割用であり、決裁役職には使用しない。
- 承認者は Section × Group で解決する。
- 決裁ルートはブラウザーから管理する決裁テンプレートDBを正本とする。
- 申請時に実際の承認者 user_id を確定し、進行中案件では人事異動によって変更しない。
- 決裁後回覧対象者は最終決裁時に user_id へ展開し固定する。
- 「決裁済み」と「案件完了」を分離する。決裁後回覧ありの場合、全対象者確認後を案件完了とする。

## 3. ダッシュボード
通常画面は利用者視点で次の2方向を中心に構成する。

### 自分が対応するもの
- 承認待ち
- 通常回覧未確認
- 決裁後回覧未確認
- 処理済み履歴

### 自分が申請・発信したもの
- 決裁中
- 差戻し
- 決裁済み・回覧中
- 通常回覧中
- 完了

詳細画面では本文、添付、承認状況、現在の処理者、決裁結果、回覧進捗、未確認者、履歴を表示する。受信者には権限のある処理（承認・差戻し・確認）のみ表示する。

## 4. 承認ルート
テンプレートSTEPは section_mode / section_id / group_id で定義する。

section_mode:
- applicant_section: 申請者と同じ所属
- fixed_section: 指定所属
- none: 所属条件なし

action_type:
- approval: 承認
- decision: 最終決裁

例：
1. applicant_section × 主任 Group
2. applicant_section × 管理者 Group
3. 法人本部 Section × 事務局長 Group

承認者が0人または複数人で一意に決まらない場合、初期版では申請開始をエラーとする。申請者本人が承認者の場合は skipped として履歴を残す。

## 5. 決裁後回覧
テンプレート単位で有効・無効を設定する。初期版の対象指定は次の4種とする。
- applicant_section: 申請者と同じ所属
- fixed_section: 指定所属
- fixed_group: 指定グループ
- fixed_user: 指定ユーザー

複数条件を指定できる。最終決裁時に対象ユーザーを展開し、document_id + user_id で重複を除去する。

## 6. 状態遷移
### 決裁後回覧なし
draft → submitted → in_approval → decided → completed

### 決裁後回覧あり
draft → submitted → in_approval → decided → in_circulation → completed

approval_status は none/draft/submitted/in_progress/returned/rejected/decided、circulation_status は none/pending/in_progress/completed を基本とする。

## 7. 差戻し
初期版では申請者への差戻しに限定する。差戻し理由を必須とし、修正後に再申請できる。履歴は上書きせず原則 INSERT のみとする。最終決裁後の差戻しは行わない。

## 8. テーブル
Connect-CMS 標準テーブルは変更しない。

プラグイン専用テーブル:
1. yuyu_circulations - バケツ単位設定
2. yuyu_circulation_frames - フレーム関連
3. yuyu_circulation_templates - 決裁テンプレート
4. yuyu_circulation_template_steps - 承認ルート定義
5. yuyu_circulation_template_targets - 決裁後回覧先定義
6. yuyu_circulation_documents - 実案件
7. yuyu_circulation_steps - 実承認STEP
8. yuyu_circulation_targets - 実回覧対象者
9. yuyu_circulation_histories - 操作履歴
10. yuyu_circulation_files - 添付ファイル
11. yuyu_circulation_questions - 回答付き回覧の質問
12. yuyu_circulation_choices - 選択肢
13. yuyu_circulation_answers - 回答
14. yuyu_circulation_notifications - ユーザー別のアプリ内通知・既読・メール送信結果

本体 users/sections/groups への参照はID保持を基本とし、過去履歴保全のため cascade delete は使用しない。

## 9. 主要インデックス
- yuyu_circulation_templates: unique(circulation_id, template_code)
- yuyu_circulation_template_steps: index(template_id, step_no)
- yuyu_circulation_documents: index(circulation_id, applicant_user_id), status系index
- yuyu_circulation_steps: index(document_id, step_no), index(approver_user_id, status)
- yuyu_circulation_targets: unique(document_id, user_id), index(user_id, status)
- yuyu_circulation_histories: index(document_id, created_at)
- yuyu_circulation_notifications: index(user_id, read_at), index(document_id, notification_type)

## 10. 新着表示モード
ポータルページへの配置を想定し、通常のダッシュボードとは別に、ログインユーザーに関係する回覧・決裁の新着・要対応案件だけをコンパクトに表示するフレームモードを設ける。

新着通知モードでは、通知フレームに選択されたバケツだけに限定せず、ログインユーザー宛ての通知を回覧・決裁バケツ横断で表示する。通知には発生元の詳細URLを保存し、別ページ・別フレームで発生した案件にも直接遷移できるようにする。

対象例:
- 自分の承認・決裁待ち
- 自分への回覧未確認
- 自分の申請の差戻し
- 自分の申請の決裁完了等

件名から案件詳細へ直接遷移し、詳細を開いた時点で同一案件の未読通知を既読にする。新着通知モードには未読通知だけを表示し、既読になった通知は一覧から除外する。

通知イベントは承認依頼、回覧受信、差戻し、決裁完了とする。アプリ内通知は常に保存する。回覧・決裁設定でメール通知が有効で、対象ユーザーにメールアドレスが設定されている場合は同じ内容をメール送信する。メール送信に失敗してもワークフロー処理は取り消さず、通知レコードにエラーを記録する。

## 11. 添付資料
申請・回覧には添付資料を扱えるようにする。既存の yuyu_circulation_files を案件添付の基本テーブルとする。

初期版では、案件ごとにファイルをアップロードして添付する方式を基本とする。これにより、申請時点の資料を案件とともに固定して保存でき、共有フォルダ側でファイルが移動・上書き・削除されても過去の決裁記録が失われない。

共有フォルダ等の既存資料を参照する必要がある場合は、単なるサーバーファイルパスの直接指定ではなく、URLまたは将来の共有ファイル機能との参照連携として別途検討する。案件添付と共有資料参照は併用可能な設計とする。

添付ファイルの実装時には、アップロード権限、閲覧権限、許可拡張子・MIME type、容量上限、削除・差替え時の履歴保持を定める。

## 12. 実装方式
DBプラグインの組み合わせではなく専用ユーザープラグインとして実装する。DBプラグインは一覧、詳細、入力、添付、検索、ページネーション等の参考実装として利用する。

開発基盤は Connect-CMS 1.44.1。GitHub を正本とし、設計変更 → design.md 更新 → feature branch → PR → テスト環境確認 → merge の順で進める。

## 13. 初期版で扱わないもの
- 金額等による条件分岐
- 並列承認
- 複数人全員承認・いずれか1人承認
- 代理承認
- エスカレーション
- 承認途中のルート変更
- 電子署名
- 高度なフォームビルダー

必要性が確認された段階で追加する。
