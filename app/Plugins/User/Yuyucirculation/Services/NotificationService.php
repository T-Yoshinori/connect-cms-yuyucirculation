<?php

namespace App\Plugins\User\Yuyucirculation\Services;

use App\Mail\ConnectMail;
use App\Models\User\YuyuCirculation\YuyuCirculationDocument;
use App\Models\User\YuyuCirculation\YuyuCirculationNotification;
use App\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * アプリ内通知を保存し、メールアドレスがあるユーザーへ同じ内容を送信する。
     * メール障害によってワークフロー本体を失敗させない。
     */
    public function notify($user_ids, YuyuCirculationDocument $document, string $type, string $message, string $detail_url, bool $send_mail = true): void
    {
        $users = User::whereIn('id', collect($user_ids)->filter()->unique())
            ->where('status', 0)
            ->get();

        foreach ($users as $user) {
            $notification = YuyuCirculationNotification::create([
                'user_id' => $user->id,
                'document_id' => $document->id,
                'notification_type' => $type,
                'title' => $document->title,
                'message' => $message,
                'detail_url' => $detail_url,
            ]);

            if (!$send_mail || empty($user->email)) {
                continue;
            }

            try {
                $body = $user->name . " 様\n\n"
                    . $message . "\n\n"
                    . "件名: " . $document->title . "\n"
                    . "詳細: " . $detail_url . "\n";

                Mail::to(trim($user->email))->send(new ConnectMail(
                    ['subject' => '【回覧・決裁】' . $message, 'template' => 'mail.send'],
                    ['content' => $body]
                ));

                $notification->mail_sent_at = now();
                $notification->mail_error = null;
                $notification->save();
            } catch (\Throwable $e) {
                $notification->mail_error = mb_substr($e->getMessage(), 0, 2000);
                $notification->save();
                Log::warning('YuyuCirculation notification mail failed.', [
                    'notification_id' => $notification->id,
                    'user_id' => $user->id,
                    'document_id' => $document->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
