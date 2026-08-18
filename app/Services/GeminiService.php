<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class GeminiService
{
    private $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.1-flash-lite:generateContent';

    private function getApiKey()
    {
        return Config::get('services.gemini.api_key');
    }

    /**
     * Get university context from database
     */
    private function getUniversityContext(): string
    {
        $context = "### ข้อมูลมหาวิทยาลัยราชภัฏอุตรดิตถ์\n\n";

        try {
            // Get faculties
            $faculties = DB::table('users')
                ->select('faculty_name_th')
                ->distinct()
                ->whereNotNull('faculty_name_th')
                ->pluck('faculty_name_th')
                ->map(fn($f) => $this->sanitizeText($f))
                ->unique();

            $context .= "**คณะ/วิทยาลัย:** " . $faculties->join(', ') . "\n\n";

            // Get departments
            $departments = DB::table('users')
                ->select('department_name_th')
                ->distinct()
                ->whereNotNull('department_name_th')
                ->pluck('department_name_th')
                ->map(fn($d) => $this->sanitizeText($d))
                ->unique();

            $context .= "**สาขาวิชา/แผนก:** " . $departments->join(', ') . "\n\n";

            // Get staff count
            $staffCount = DB::table('users')->count();
            $context .= "**จำนวนบุคลากร:** " . $staffCount . " คน\n\n";

            // Get positions
            $positions = DB::table('users')
                ->select('position')
                ->distinct()
                ->whereNotNull('position')
                ->pluck('position')
                ->map(fn($p) => $this->sanitizeText($p))
                ->unique();

            if ($positions->count() > 0) {
                $context .= "**ตำแหน่ง:** " . $positions->join(', ') . "\n\n";
            }

            // Get announcements summary
            $announcements = DB::table('announcements')
                ->select('title', 'message')
                ->where('is_published', true)
                ->orderByDesc('published_at')
                ->limit(3)
                ->get();

            if ($announcements->count() > 0) {
                $context .= "**ประกาศล่าสุด:**\n";
                foreach ($announcements as $ann) {
                    $title = $this->sanitizeText($ann->title);
                    $message = $this->sanitizeText(substr($ann->message, 0, 50));
                    $context .= "- " . $title . ": " . $message . "\n";
                }
                $context .= "\n";
            }

            // Get research count
            $researchCount = DB::table('has_researches')->distinct('user_id')->count();
            $context .= "**จำนวนงานวิจัย:** " . $researchCount . " เรื่อง\n";

        } catch (\Exception $e) {
            \Log::error('Error building university context: ' . $e->getMessage());
        }

        return $context;
    }

    /**
     * Send message to Gemini API
     */
    public function chat(string $message, ?array $history = null): ?string
    {
        try {
            $systemPrompt = $this->buildSystemPrompt();

            // Build conversation history
            $contents = [];

            // Add system context as first user message
            $contents[] = [
                'role' => 'user',
                'parts' => [
                    ['text' => $systemPrompt]
                ]
            ];

            // Add assistant acknowledgment
            $contents[] = [
                'role' => 'model',
                'parts' => [
                    ['text' => 'เข้าใจแล้ว ฉันจะตอบคำถามเกี่ยวกับมหาวิทยาลัยราชภัฏอุตรดิตถ์โดยใช้ข้อมูลที่ให้มา']
                ]
            ];

            // Add previous history
            if ($history && is_array($history)) {
                foreach ($history as $item) {
                    $contents[] = [
                        'role' => $item['role'] === 'user' ? 'user' : 'model',
                        'parts' => [
                            ['text' => $this->sanitizeText($item['content'] ?? '')]
                        ]
                    ];
                }
            }

            // Add current message
            $contents[] = [
                'role' => 'user',
                'parts' => [
                    ['text' => $this->sanitizeText($message)]
                ]
            ];

            // Send to Gemini API
            $apiUrl = $this->apiUrl . '?key=' . urlencode($this->getApiKey());

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($apiUrl, [
                'contents' => $contents,
                'generationConfig' => [
                    'temperature' => 0.7,
                    'topK' => 40,
                    'topP' => 0.95,
                    'maxOutputTokens' => 2048,
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Extract response text
                if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                    return $data['candidates'][0]['content']['parts'][0]['text'];
                }
            } else {
                \Log::error('Gemini API Response: ' . $response->body());
            }

            return null;

        } catch (\Exception $e) {
            \Log::error('Gemini API Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Sanitize text for UTF-8 encoding
     */
    private function sanitizeText(string $text): string
    {
        // Remove any invalid UTF-8 sequences and return clean UTF-8
        if (empty($text)) {
            return '';
        }

        // Convert to UTF-8, removing invalid characters
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');

        // Remove any control characters except newlines
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);

        return $text;
    }

    /**
     * Get web content from university website
     */
    private function getWebContext(): string
    {
        try {
            $webContent = "### ข้อมูลจากเว็บไซต์ https://www.uru.ac.th/\n\n";

            // Try to fetch university website
            $response = Http::withoutVerifying()->timeout(5)->get('https://www.uru.ac.th/');

            if ($response->successful()) {
                $html = $response->body();

                // Extract h1 heading
                if (preg_match('/<h1[^>]*>([^<]+)<\/h1>/i', $html, $matches)) {
                    $webContent .= "**หัวข้อหลัก:** " . $this->sanitizeText($matches[1]) . "\n\n";
                }

                // Extract all h2 headings (sections)
                preg_match_all('/<h2[^>]*>([^<]+)<\/h2>/i', $html, $matches);
                if (!empty($matches[1])) {
                    $webContent .= "**ส่วนต่างๆของเว็บไซต์:**\n";
                    foreach (array_slice($matches[1], 0, 5) as $h2) {
                        $webContent .= "- " . $this->sanitizeText($h2) . "\n";
                    }
                    $webContent .= "\n";
                }

                // Extract text from paragraph tags (up to 300 chars)
                preg_match_all('/<p[^>]*>([^<]+)<\/p>/i', $html, $matches);
                if (!empty($matches[1])) {
                    $webContent .= "**เนื้อหา:** ";
                    $text = array_filter($matches[1], fn($t) => strlen(trim($t)) > 20);
                    if (!empty($text)) {
                        $combined = implode(" ", array_slice($text, 0, 3));
                        $webContent .= $this->sanitizeText(substr($combined, 0, 200)) . "...\n\n";
                    }
                }

                return $webContent;
            }

            return "### ข้อมูลเว็บ\nไม่สามารถดึงข้อมูลจากเว็บได้ขณะนี้\n\n";

        } catch (\Exception $e) {
            \Log::error('Web scraping error: ' . $e->getMessage());
            return "### ข้อมูลเว็บ\nไม่สามารถดึงข้อมูลจากเว็บได้ขณะนี้\n\n";
        }
    }

    /**
     * Build system prompt with university context
     */
    private function buildSystemPrompt(): string
    {
        $universityContext = $this->getUniversityContext();
        $webContext = $this->getWebContext();

        return <<<PROMPT
คุณคือผู้ช่วยสนทนาของมหาวิทยาลัยราชภัฏอุตรดิตถ์ ที่มีความรับผิดชอบต่อความถูกต้องและความน่าเชื่อถือของข้อมูล

ข้อมูลระบบที่มีอยู่:
{$universityContext}

{$webContext}

**กฎการตอบคำถาม:**

สำหรับคำถามเกี่ยวกับมหาวิทยาลัยราชภัฏอุตรดิตถ์:
1. ✅ ตอบจากข้อมูลที่มีในระบบเท่านั้น (database + เว็บไซต์ต่อหน้า)
2. ✅ ถ้ามีข้อมูลจากระบบ - ตอบอย่างชัดเจนพร้อมแหล่งข้อมูล
3. ❌ ห้ามสมมุติ ซุย หรือคิดเองหากไม่มีข้อมูล
4. ❌ ห้าม "คาดว่า" หรือ "ควรจะ" - ต้องตอบจากข้อเท็จจริง
5. ✅ ถ้าไม่มีข้อมูล - บอก "ขออภัย ไม่มีข้อมูล [เรื่องนี้] ในระบบ"
6. ❌สำคัญสุดห้ามข้อมูลส่วนตัวทั้งหมดเกี่ยวกับ user หลุดเด็ดขาดทั้ง ชื่อ, นามสกุล, เบอร์โทร, อีเมล, รหัสบัตรประชาชน, รหัสอาจารย์, หรือข้อมูลส่วนตัวอื่นๆ
สำหรับคำถามทั่วไป (ไม่เกี่ยวมหาวิทยาลัย):
- คุณสามารถตอบตามความรู้ได้ แต่ยังต้องเป็นความจริง ไม่หลอก

**สำคัญที่สุด:**
- ความถูกต้องมีความสำคัญมากกว่าการให้คำตอบที่ยาว
- ดีกว่าที่จะพูดว่า "ไม่รู้" มากกว่าให้ข้อมูลผิด
- ตอบเป็นภาษาไทยเสมอ
PROMPT;
    }
}
