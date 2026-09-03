<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    private array $blockedPersonalDataKeywords = [
        'ข้อมูลส่วนตัว',
        'ข้อมูลบุคคล',
        'ข้อมูลผู้ใช้',
        'ข้อมูลคน',
        'รายชื่อ',
        'ชื่อ นามสกุล',
        'ชื่ออาจารย์',
        'ชื่อบุคลากร',
        'นามสกุล',
        'เบอร์โทร',
        'โทรศัพท์',
        'อีเมล',
        'email',
        'เลขบัตร',
        'บัตรประชาชน',
        'รหัสบัตร',
        'รหัสนักศึกษา',
        'รหัสบุคลากร',
        'citizen',
        'id card',
        'database',
        'ตาราง users',
        'users table',
        'expert2.users',
        'lrdsystem2.users',
        'researchers',
    ];

    public function chat(string $message, ?array $history = null, ?string $model = null): ?string
    {
        $resolvedModel = $this->resolveModel($model);

        try {
            if ($this->asksForPersonalOrDatabaseData($message)) {
                return 'ขออภัยครับ ฉันไม่สามารถเปิดเผยหรือค้นหาข้อมูลส่วนตัว ข้อมูลผู้ใช้ หรือข้อมูลของบุคคลในฐานข้อมูลได้ แต่สามารถช่วยตอบคำถามทั่วไปหรืออธิบายระบบในภาพรวมได้ครับ';
            }

            $messages = [
                [
                    'role' => 'system',
                    'content' => $this->buildSystemPrompt(),
                ],
            ];

            if ($history && is_array($history)) {
                foreach ($history as $item) {
                    $role = ($item['role'] ?? '') === 'assistant' ? 'assistant' : 'user';
                    $content = $this->sanitizeText((string) ($item['content'] ?? ''));

                    if ($content !== '' && ! $this->asksForPersonalOrDatabaseData($content)) {
                        $messages[] = [
                            'role' => $role,
                            'content' => $content,
                        ];
                    }
                }
            }

            $messages[] = [
                'role' => 'user',
                'content' => $this->sanitizeText($message),
            ];

            $response = Http::withToken($this->apiKey())
                ->acceptJson()
                ->withoutVerifying()
                ->timeout(30)
                ->post($this->chatCompletionsUrl(), [
                    'model' => $resolvedModel,
                    'messages' => $messages,
                    'temperature' => 0.7,
                    'max_tokens' => 1024,
                    'stream' => false,
                ]);

            if (! $response->successful()) {
                Log::error('URU AI Space API Response', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $data = $response->json();
            $reply = $data['choices'][0]['message']['content'] ?? null;

            if (! is_string($reply) || trim($reply) === '') {
                return null;
            }

            if ($this->containsPersonalDataLeak($reply)) {
                return 'ขออภัยครับ ฉันไม่สามารถเปิดเผยข้อมูลส่วนตัวหรือข้อมูลของบุคคลในฐานข้อมูลได้ครับ';
            }

            return $reply;
        } catch (\Throwable $e) {
            Log::error('URU AI Space Chat Error', ['error' => $e->getMessage()]);

            return null;
        }
    }

    private function apiKey(): string
    {
        return (string) Config::get('services.ai.api_key', Config::get('services.gemini.api_key'));
    }

    public function models(): array
    {
        $configuredModels = Config::get('services.ai.models', []);
        $models = [];

        if (is_array($configuredModels)) {
            foreach ($configuredModels as $model) {
                if (! is_array($model) || empty($model['id'])) {
                    continue;
                }

                $id = (string) $model['id'];
                $models[$id] = [
                    'id' => $id,
                    'display_name' => (string) ($model['display_name'] ?? $id),
                    'provider' => (string) ($model['provider'] ?? 'URU AI Space'),
                    'description' => (string) ($model['description'] ?? ''),
                ];
            }
        }

        $defaultModel = $this->model();

        if ($defaultModel !== '' && ! isset($models[$defaultModel])) {
            $models[$defaultModel] = [
                'id' => $defaultModel,
                'display_name' => $defaultModel,
                'provider' => 'URU AI Space',
                'description' => 'Default chatbot model',
            ];
        }

        return array_values($models);
    }

    public function resolveModel(?string $model = null): string
    {
        $model = trim((string) $model);
        $model = $model !== '' ? $model : $this->model();

        if (! in_array($model, array_column($this->models(), 'id'), true)) {
            throw new \InvalidArgumentException('The selected chat model is not supported.');
        }

        return $model;
    }

    private function baseUrl(): string
    {
        return rtrim((string) Config::get('services.ai.base_url', 'https://gen.ai.kku.ac.th/uruacth/api/v1'), '/');
    }

    private function model(): string
    {
        return (string) Config::get('services.ai.model', 'claude-sonnet-5');
    }

    private function chatCompletionsUrl(): string
    {
        return $this->baseUrl() . '/chat/completions';
    }

    private function buildSystemPrompt(): string
    {
        return <<<PROMPT
คุณคือ chatbot ของ URU Smart
ตอบคำถามทั่วไปได้ตามความรู้ทั่วไป และอธิบายการใช้งานระบบในภาพรวมได้

ข้อห้ามสำคัญ:
1. ห้ามเปิดเผยข้อมูลส่วนตัวของบุคคลใด ๆ
2. ห้ามตอบชื่อ นามสกุล อีเมล เบอร์โทร เลขบัตรประชาชน รหัสนักศึกษา รหัสบุคลากร หรือข้อมูลระบุตัวบุคคล
3. ห้ามกล่าวอ้างว่าดึงข้อมูลจาก database ตาราง users, expert2.users, lrdsystem2.users หรือ researchers
4. ถ้าผู้ใช้ถามหาข้อมูลส่วนตัวหรือข้อมูลของบุคคลในฐานข้อมูล ให้ปฏิเสธสั้น ๆ
5. ตอบคำถามทั่วไปได้ เช่น ความรู้รอบตัว เทคโนโลยี การเรียน การเขียนโค้ด หรือการใช้งานระบบแบบไม่เจาะจงบุคคล
6. ตอบเป็นภาษาไทย กระชับ สุภาพ และไม่แต่งข้อมูลเฉพาะบุคคล
PROMPT;
    }

    private function sanitizeText(string $text): string
    {
        if ($text === '') {
            return '';
        }

        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');

        return (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);
    }

    private function asksForPersonalOrDatabaseData(string $message): bool
    {
        $normalized = mb_strtolower($message, 'UTF-8');

        foreach ($this->blockedPersonalDataKeywords as $keyword) {
            if (str_contains($normalized, mb_strtolower($keyword, 'UTF-8'))) {
                return true;
            }
        }

        return false;
    }

    private function containsPersonalDataLeak(string $reply): bool
    {
        return (bool) preg_match('/\b\d{13}\b|[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $reply);
    }
}
