<?php

namespace App\Services;

use App\Models\Discussion;
use App\Models\User;

/**
 * Service tipis untuk membentuk template email notifikasi.
 * Memakai MailService di belakang, agar provider mail mudah diganti dari Admin.
 */
class NotificationService
{
    public function __construct(protected MailService $mail) {}

    public function notifyTeacherNewQuestion(Discussion $discussion): bool
    {
        $teacher = $discussion->material?->teacher ?? $discussion->classroom?->teacher;
        if (! $teacher || ! $teacher->email) return false;

        $title = $discussion->material?->title ?? $discussion->classroom?->name ?? 'Diskusi';
        $url = url('/teacher/materials/'.($discussion->material_id ?? ''));

        return $this->mail->sendHtml(
            [$teacher->email => $teacher->name],
            "Pertanyaan baru di: {$title}",
            $this->renderTemplate(
                heading: 'Ada pertanyaan baru dari siswa',
                body: "<p><strong>{$discussion->user->name}</strong> menanyakan sesuatu di materi <em>".e($title)."</em>:</p>".
                      "<blockquote style='margin:16px 0;padding:12px 16px;background:#f0fdf4;border-left:4px solid #10b981;border-radius:8px'>".nl2br(e($discussion->body))."</blockquote>",
                cta: ['Buka Diskusi', $url],
            ),
        );
    }

    public function notifyStudentReply(Discussion $reply): bool
    {
        $parent = $reply->parent;
        if (! $parent || ! $parent->user || ! $parent->user->email) return false;

        $title = $reply->material?->title ?? $reply->classroom?->name ?? 'Diskusi';
        $url = url('/student/materials/'.($reply->material_id ?? ''));

        return $this->mail->sendHtml(
            [$parent->user->email => $parent->user->name],
            "Guru membalas pertanyaan Anda: {$title}",
            $this->renderTemplate(
                heading: 'Guru sudah menjawab pertanyaan Anda',
                body: "<p><strong>".e($reply->user->name)."</strong> membalas:</p>".
                      "<blockquote style='margin:16px 0;padding:12px 16px;background:#ecfeff;border-left:4px solid #06b6d4;border-radius:8px'>".nl2br(e($reply->body))."</blockquote>",
                cta: ['Lihat Jawaban', $url],
            ),
        );
    }

    public function notifyExamGraded(User $student, string $examTitle, int $score, string $url): bool
    {
        return $this->mail->sendHtml(
            [$student->email => $student->name],
            "Hasil ujian: {$examTitle}",
            $this->renderTemplate(
                heading: 'Ujian Anda telah dinilai',
                body: "<p>Hasil ujian <strong>".e($examTitle)."</strong>:</p>".
                      "<p style='font-size:42px;font-weight:800;color:#10b981;margin:8px 0'>{$score}<span style='font-size:18px;color:#94a3b8'>/100</span></p>",
                cta: ['Lihat Detail', $url],
            ),
        );
    }

    /**
     * Kirim kode OTP verifikasi email (untuk pendaftaran akun baru).
     */
    public function sendOtpCode(string $email, string $name, string $code, int $ttlMinutes = 10): bool
    {
        $appName = e(config('app.name', 'Eko-Scribe'));
        $codeHtml = "<div style='font-size:32px;font-weight:800;letter-spacing:10px;color:#047857;background:#ecfdf5;padding:14px 20px;border-radius:12px;text-align:center;font-family:Menlo,Consolas,monospace'>".e($code)."</div>";

        return $this->mail->sendHtml(
            [$email => $name],
            "Kode Verifikasi {$appName}",
            $this->renderTemplate(
                heading: 'Kode verifikasi pendaftaran Anda',
                body: "<p>Halo <strong>".e($name)."</strong>,</p>".
                      "<p>Berikut kode OTP untuk menyelesaikan pendaftaran akun Anda di {$appName}:</p>".
                      $codeHtml.
                      "<p style='margin-top:16px;color:#64748b;font-size:13px'>Kode berlaku <strong>{$ttlMinutes} menit</strong>. Jangan bagikan kode ini ke siapa pun.</p>".
                      "<p style='color:#64748b;font-size:13px'>Jika Anda tidak meminta pendaftaran, abaikan email ini.</p>",
            ),
        );
    }

    protected function renderTemplate(string $heading, string $body, ?array $cta = null): string
    {
        $appName = e(config('app.name', 'Eko-Scribe'));
        $btn = '';
        if ($cta) {
            [$label, $url] = $cta;
            $btn = "<p style='margin:24px 0'><a href='".e($url)."' style='display:inline-block;padding:12px 22px;background:linear-gradient(135deg,#10b981,#0d9488);color:#fff;text-decoration:none;border-radius:10px;font-weight:600'>".e($label)."</a></p>";
        }

        return <<<HTML
<!DOCTYPE html>
<html><body style="margin:0;padding:0;background:#f0fdf4;font-family:Inter,Segoe UI,Arial,sans-serif;color:#1e293b">
  <div style="max-width:560px;margin:32px auto;background:#fff;border-radius:18px;overflow:hidden;box-shadow:0 8px 24px rgba(16,185,129,.15)">
    <div style="padding:20px 24px;background:linear-gradient(135deg,#10b981,#0d9488);color:#fff;font-weight:700;font-size:18px">{$appName}</div>
    <div style="padding:24px">
      <h1 style="font-size:20px;margin:0 0 12px">{$heading}</h1>
      <div style="line-height:1.6;color:#334155;font-size:15px">{$body}</div>
      {$btn}
      <p style="font-size:12px;color:#94a3b8;margin-top:24px">Email ini dikirim otomatis dari {$appName}. Mohon jangan dibalas.</p>
    </div>
  </div>
</body></html>
HTML;
    }
}
