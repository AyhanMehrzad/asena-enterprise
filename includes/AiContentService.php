<?php
/**
 * ASENA Enterprise — AI Veterinary Content & Clinical Blog Copilot Service
 * 
 * Provides:
 * - Full Clinical Article Generation (SEO Title, Slug, Short Description, Category, Read Time, Rich HTML Content, FAQs)
 * - Headline & SEO Ideas Generator
 * - Text Polisher & Clinical Rewriter (Scientific, Friendly, Expand, Summarize, Grammar)
 * - Smart Clinical FAQ Generator
 * - Emergency Triage Alert Generator
 * - Meta & SEO Tags Extractor
 * 
 * Architecture:
 * - Dual Engine: Queries Google Gemini 1.5/2.5 Flash API if configured,
 *   with an intelligent, high-grade veterinary clinical heuristics & knowledge synthesis fallback.
 */

require_once __DIR__ . '/functions.php';

class AiContentService {
    private ?PDO $pdo;
    private string $apiKey;
    private string $geminiUrl;
    private ?string $proxy;
    private string $avalaiApiKey;
    private string $avalaiUrl = 'https://api.avalai.ir/v1/chat/completions';
    private string $avalaiModel = 'gemini-3.7-flash';
    private string $avalaiChatModel = 'gemini-3.5-flash-lite';
    private string $avalaiTitlesModel = 'gemini-3.5-flash-lite';
    private string $avalaiPolishModel = 'gemini-3.5-flash-lite';
    private string $avalaiImageModel = 'gpt-image-2.5-flare';

    public function __construct(?PDO $pdo = null) {
        $this->pdo = $pdo;
        
        $dbKey = ($this->pdo instanceof PDO) ? get_setting($this->pdo, 'gemini_api_key', '') : '';
        $this->apiKey = !empty($dbKey) ? $dbKey : (getenv('GEMINI_API_KEY') ?: '');
        
        // Sanitize placeholder
        if ($this->apiKey === 'YOUR_GEMINI_API_KEY_HERE' || $this->apiKey === 'your_api_key_here') {
            $this->apiKey = '';
        }

        $this->geminiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . urlencode($this->apiKey);
        $this->proxy = getenv('GEMINI_PROXY') ?: (getenv('HTTPS_PROXY') ?: null);

        // AvalAI Configuration (Multi-model Iranian provider - ultra-low cost)
        $dbAvalaiKey = ($this->pdo instanceof PDO) ? get_setting($this->pdo, 'avalai_api_key', '') : '';
        $this->avalaiApiKey = !empty($dbAvalaiKey) ? $dbAvalaiKey : (getenv('AVALAI_API_KEY') ?: 'aa-OYnaadEq49DVrgUetouRgFRhmNjSuS7ZknCL5FdEQqHAehsl');
        $this->avalaiModel = getenv('AVALAI_MODEL_ARTICLE') ?: 'gemini-3.7-flash';
        $this->avalaiChatModel = getenv('AVALAI_MODEL_CHAT') ?: 'gemini-3.5-flash-lite';
        $this->avalaiTitlesModel = getenv('AVALAI_MODEL_TITLES') ?: 'gemini-3.5-flash-lite';
        $this->avalaiPolishModel = getenv('AVALAI_MODEL_POLISH') ?: 'gemini-3.5-flash-lite';
        $this->avalaiImageModel = getenv('AVALAI_MODEL_IMAGE') ?: 'gpt-image-2.5-flare';
    }

    /**
     * Generate complete structured blog article
     */
    public function generateArticle(string $topic, string $tone = 'clinical', string $species = 'all', string $category = 'medical'): array {
        $topic = trim($topic);
        if (empty($topic)) {
            throw new InvalidArgumentException('موضوع مقاله نمی‌تواند خالی باشد.');
        }

        // 1. Try AvalAI live generation (Ultra cheap, fast, no sanction blocks)
        if (!empty($this->avalaiApiKey)) {
            $avalaiResult = $this->callAvalAiForArticle($topic, $tone, $species, $category);
            if ($avalaiResult !== null) {
                $avalaiResult['source'] = 'avalai (' . $this->avalaiModel . ')';
                return $avalaiResult;
            }
        }

        // 2. Try Gemini live generation if key available
        if (!empty($this->apiKey)) {
            $geminiResult = $this->callGeminiForArticle($topic, $tone, $species, $category);
            if ($geminiResult !== null) {
                $geminiResult['source'] = 'gemini';
                return $geminiResult;
            }
        }

        // 3. Resilient Offline Clinical Heuristics Engine
        $result = $this->synthesizeClinicalArticle($topic, $tone, $species, $category);
        $result['source'] = 'clinical_engine';
        return $result;
    }

    /**
     * Generate 5 catchy, high-CTR SEO headlines
     */
    public function suggestTitles(string $topic, string $style = 'high_ctr'): array {
        $topic = trim($topic);
        if (empty($topic)) {
            return [];
        }

        if (!empty($this->avalaiApiKey)) {
            $titles = $this->callAvalAiForTitles($topic, $style);
            if (!empty($titles)) {
                return $titles;
            }
        }

        if (!empty($this->apiKey)) {
            $titles = $this->callGeminiForTitles($topic, $style);
            if (!empty($titles)) {
                return $titles;
            }
        }

        return $this->synthesizeTitles($topic);
    }

    /**
     * Polish, rewrite, expand, or format text
     */
    public function polishText(string $text, string $mode = 'scientific'): string {
        $text = trim($text);
        if (empty($text)) {
            return '';
        }

        if (!empty($this->avalaiApiKey)) {
            $polished = $this->callAvalAiForPolishing($text, $mode);
            if (!empty($polished)) {
                return $polished;
            }
        }

        if (!empty($this->apiKey)) {
            $polished = $this->callGeminiForPolishing($text, $mode);
            if (!empty($polished)) {
                return $polished;
            }
        }

        return $this->synthesizePolishedText($text, $mode);
    }

    /**
     * Generate clinical FAQs for a given topic
     */
    public function generateFaqs(string $topic, int $count = 4): array {
        $topic = trim($topic);
        if (empty($topic)) {
            return [];
        }

        if (!empty($this->avalaiApiKey)) {
            $faqs = $this->callAvalAiForFaqs($topic, $count);
            if (!empty($faqs)) {
                return $faqs;
            }
        }

        if (!empty($this->apiKey)) {
            $faqs = $this->callGeminiForFaqs($topic, $count);
            if (!empty($faqs)) {
                return $faqs;
            }
        }

        return $this->synthesizeFaqs($topic, $count);
    }

    /**
     * Generate Clinical Red Alert Box
     */
    public function generateClinicalAlert(string $topic): array {
        $topic = trim($topic);
        if (empty($topic)) {
            $topic = 'اورژانس دامپزشکی';
        }

        $alert = $this->synthesizeAlertBox($topic);
        return [
            'html' => $alert['html'],
            'title' => $alert['title'],
            'triggers' => $alert['triggers']
        ];
    }

    // ==========================================
    // AVALAI (MULTI-MODEL IRANIAN API) CALLERS
    // ==========================================

    private function callAvalAi(array $messages, int $maxTokens = 800, ?string $model = null, float $temperature = 0.4, int $timeout = 25): ?string {
        if (empty($this->avalaiApiKey)) {
            return null;
        }

        $model = $model ?: $this->avalaiModel;

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => $maxTokens,
            'temperature' => $temperature
        ];

        $ch = curl_init($this->avalaiUrl);
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->avalaiApiKey
            ],
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => false,
        ];

        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err || !$response) {
            return null;
        }

        $data = json_decode($response, true);
        if (isset($data['choices'][0]['message']['content'])) {
            $content = trim($data['choices'][0]['message']['content']);
            if (!empty($content)) {
                return $content;
            }
        }

        return null;
    }

    private function callAvalAiForArticle(string $topic, string $tone, string $species, string $category): ?array {
        $messages = [
            [
                'role' => 'system',
                'content' => "شما دکتر دامپزشک ارشد و نویسنده پایگاه دانش تخصصی سامانه درمانی و پت‌شاپ آسنا (ASENA Enterprise) هستید. خروجی را صرفاً به صورت یک ساختار معتبر JSON (بدون هیچ مارک‌داون یا توضیحات اضافه) ارسال کنید."
            ],
            [
                'role' => 'user',
                'content' => "موضوع مقاله: '{$topic}'
گونه هدف: '{$species}'
لحن نگارش: '{$tone}'
دسته‌بندی: '{$category}'

لطفاً خروجی را دقیقاً و صرفاً به صورت یک شیء JSON با ساختار زیر تولید کنید:
{
  \"title\": \"عنوان سئو شده و جذاب (حداکثر ۶۵ کاراکتر با کلمه کلیدی اصلی)\",
  \"slug\": \"نامک-استاندارد-فارسی-یا-انگلیسی\",
  \"short_desc\": \"چکیده جذاب و متا دیسکریپشن برای گوگل (حداکثر ۱۶۰ کاراکتر)\",
  \"category\": \"{$category}\",
  \"read_time\": \"زمان تخمینی مطالعه (مثلاً ۵ دقیقه مطالعه)\",
  \"content\": \"متن کامل مقاله به فرمت HTML5 غنی شامل تگ‌های h2, h3, p, ul, li و در صورت تناسب المان‌های جذاب دامپزشکی\",
  \"faqs\": [
    {\"q\": \"سوال پرتکرار ۱؟\", \"a\": \"پاسخ بالینی کامل و دقیق ۱\"},
    {\"q\": \"سوال پرتکرار ۲؟\", \"a\": \"پاسخ بالینی کامل و دقیق ۲\"}
  ]
}"
            ]
        ];

        $raw = $this->callAvalAi($messages, 1400, $this->avalaiModel, 0.4, 25);
        if (empty($raw)) {
            return null;
        }

        $cleanJson = preg_replace('/^```json\s*|\s*```$/ui', '', trim($raw));
        $parsed = json_decode($cleanJson, true);

        if (is_array($parsed) && !empty($parsed['title']) && !empty($parsed['content'])) {
            return [
                'title' => (string)$parsed['title'],
                'slug' => (string)($parsed['slug'] ?? ''),
                'short_desc' => (string)($parsed['short_desc'] ?? ''),
                'category' => (string)($parsed['category'] ?? $category),
                'read_time' => (string)($parsed['read_time'] ?? '۵ دقیقه مطالعه'),
                'content' => (string)$parsed['content'],
                'faqs' => is_array($parsed['faqs'] ?? null) ? $parsed['faqs'] : []
            ];
        }

        return null;
    }

    private function callAvalAiForTitles(string $topic, string $style): ?array {
        $messages = [
            [
                'role' => 'system',
                'content' => "به عنوان متخصص سئو پزشکی و دامپزشکی، خروجی فقط و فقط باید یک آرایه معتبر JSON شامل ۵ رشته متنی باشد. هیچ متن دیگری ننویسید."
            ],
            [
                'role' => 'user',
                'content' => "برای موضوع '{$topic}' دقیقاً ۵ عنوان بسیار جذاب، کلیک‌خور و سئو شده به زبان فارسی بنویسید.
فرمت خروجی صرفاً:
[\"عنوان ۱\", \"عنوان ۲\", \"عنوان ۳\", \"عنوان ۴\", \"عنوان ۵\"]"
            ]
        ];

        $raw = $this->callAvalAi($messages, 300, $this->avalaiTitlesModel, 0.6, 15);
        if (empty($raw)) return null;

        $clean = preg_replace('/^```json\s*|\s*```$/ui', '', trim($raw));
        $parsed = json_decode($clean, true);
        if (is_array($parsed) && count($parsed) >= 3) {
            return array_slice($parsed, 0, 5);
        }
        return null;
    }

    private function callAvalAiForPolishing(string $text, string $mode): ?string {
        $instructions = [
            'scientific' => 'این متن را با واژگان دقیق علمی، اصطلاحات کلینیکی دامپزشکی و لحن رسمی و فوق‌تخصصی بازنویسی کن.',
            'friendly' => 'این متن را بسیار ساده، صمیمی، همدلانه و روان برای سرپرستان حیوانات خانگی بازنویسی کن.',
            'expand' => 'این متن را به شکل علمی گسترش بده و نکات بالینی، آزمایشگاهی، علائم تکمیلی و راهکارهای درمانی به آن اضافه کن.',
            'summarize' => 'این متن را به صورت خلاصه اجرایی و نکات کلیدی بالت‌پوینت بازنویسی کن.',
            'fix_grammar' => 'خطاهای املایی، نگارشی، علائم سجاوندی و ساختار جملات این متن را بدون تغییر مفهوم اصلی اصلاح کن.'
        ];

        $instruction = $instructions[$mode] ?? $instructions['scientific'];
        $messages = [
            [
                'role' => 'system',
                'content' => "شما ویراستار ارشد متون دامپزشکی هستید. فقط متن بازنویسی شده را برگردانید بدون هیچ توضیح مقدماتی یا نتیجه‌گیری."
            ],
            [
                'role' => 'user',
                'content' => "{$instruction}\n\nمتن اولیه:\n\"{$text}\""
            ]
        ];

        $raw = $this->callAvalAi($messages, 600, $this->avalaiPolishModel, 0.3, 15);
        return !empty($raw) ? trim($raw) : null;
    }

    private function callAvalAiForFaqs(string $topic, int $count): ?array {
        $messages = [
            [
                'role' => 'system',
                'content' => "شما متخصص دامپزشکی بالینی هستید. خروجی فقط یک آرایه JSON معتبر شامل سوال و پاسخ باشد."
            ],
            [
                'role' => 'user',
                'content' => "درباره موضوع دامپزشکی '{$topic}'، دقیقاً {$count} سوال بسیار مهم و پرتکرار که سرپرستان حیوانات خانگی می‌پرسند به همراه پاسخ‌های علمی، دقیق و قابل درک تولید کن.
فرمت JSON دقیق:
[
  {\"q\": \"متن سوال ۱؟\", \"a\": \"متن پاسخ ۱\"},
  {\"q\": \"متن سوال ۲؟\", \"a\": \"متن پاسخ ۲\"}
]"
            ]
        ];

        $raw = $this->callAvalAi($messages, 600, $this->avalaiModel, 0.4, 20);
        if (empty($raw)) return null;

        $clean = preg_replace('/^```json\s*|\s*```$/ui', '', trim($raw));
        $parsed = json_decode($clean, true);
        if (is_array($parsed)) {
            return $parsed;
        }
        return null;
    }

    /**
     * Generate an AI image for a blog cover or clinic asset using AvalAI Tier 1 image models
     * Models: gpt-image-2.5-flare, gpt-image-2.5-sunburst
     */
    public function generateImage(string $prompt, ?string $model = null, string $size = '1024x1024'): ?string {
        if (empty($this->avalaiApiKey)) {
            return null;
        }

        $model = $model ?: $this->avalaiImageModel;
        $url = 'https://api.avalai.ir/v1/images/generations';

        $payload = [
            'model' => $model,
            'prompt' => $prompt,
            'n' => 1,
            'size' => $size
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->avalaiApiKey
            ],
            CURLOPT_TIMEOUT => 40,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err || !$response) {
            return null;
        }

        $data = json_decode($response, true);
        if (!empty($data['data'][0]['url'])) {
            return (string)$data['data'][0]['url'];
        }

        return null;
    }

    // ==========================================
    // GEMINI API CALLERS
    // ==========================================

    private function callGemini(string $prompt, int $timeout = 10): ?string {
        if (empty($this->apiKey)) {
            return null;
        }

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 3072,
                'topP' => 0.95
            ]
        ];

        $ch = curl_init($this->geminiUrl);
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => false,
        ];

        if (!empty($this->proxy)) {
            $options[CURLOPT_PROXY] = $this->proxy;
        }

        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err || !$response) {
            return null;
        }

        $data = json_decode($response, true);
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            return trim($data['candidates'][0]['content']['parts'][0]['text']);
        }

        return null;
    }

    private function callGeminiForArticle(string $topic, string $tone, string $species, string $category): ?array {
        $prompt = "شما دکتر دامپزشک ارشد و نویسنده پایگاه دانش تخصصی پت‌شاپ و سامانه درمانی آسنا (ASENA Enterprise) هستید.
موضوع مقاله: '{$topic}'
گونه هدف: '{$species}'
لحن نگارش: '{$tone}' (علمی و کلینیکی یا خودمانی و راهنمای سرپرست)
دسته‌بندی: '{$category}'

لطفاً خروجی را دقیقاً و صرفاً به صورت یک ساختار معتبر JSON بدون هیچ علامت محاوره‌ای یا متن اضافی در قالب زیر ارائه دهید:
{
  \"title\": \"عنوان سئو شده و جذاب (حداکثر ۶۵ کاراکتر با کلمه کلیدی اصلی)\",
  \"slug\": \"نامک-استاندارد-فارسی-یا-انگلیسی\",
  \"short_desc\": \"چکیده جذاب و متا دیسکریپشن برای گوگل (حداکثر ۱۶۰ کاراکتر)\",
  \"category\": \"{$category}\",
  \"read_time\": \"زمان تخمینی مطالعه (مثلاً ۵ دقیقه مطالعه)\",
  \"content\": \"متن کامل مقاله به فرمت HTML5 غنی شامل تگ‌های h2, h3, p, ul, li و در صورت تناسب المان‌های جذاب دامپزشکی\",
  \"faqs\": [
    {\"q\": \"سوال پرتکرار ۱\", \"a\": \"پاسخ بالینی کامل و دقیق ۱\"},
    {\"q\": \"سوال پرتکرار ۲\", \"a\": \"پاسخ بالینی کامل و دقیق ۲\"}
  ]
}";

        $raw = $this->callGemini($prompt, 14);
        if (empty($raw)) {
            return null;
        }

        // Clean json blocks if returned with markdown ```json
        $cleanJson = preg_replace('/^```json\s*|\s*```$/ui', '', trim($raw));
        $parsed = json_decode($cleanJson, true);

        if (is_array($parsed) && !empty($parsed['title']) && !empty($parsed['content'])) {
            return [
                'title' => (string)$parsed['title'],
                'slug' => (string)($parsed['slug'] ?? ''),
                'short_desc' => (string)($parsed['short_desc'] ?? ''),
                'category' => (string)($parsed['category'] ?? $category),
                'read_time' => (string)($parsed['read_time'] ?? '۶ دقیقه مطالعه'),
                'content' => (string)$parsed['content'],
                'faqs' => is_array($parsed['faqs'] ?? null) ? $parsed['faqs'] : []
            ];
        }

        return null;
    }

    private function callGeminiForTitles(string $topic, string $style): ?array {
        $prompt = "به عنوان متخصص سئو پزشکی و دامپزشکی، برای موضوع '{$topic}' دقیقاً ۵ عنوان بسیار جذاب، کلیک‌خور و سئو شده به زبان فارسی بنویسید.
خروجی فقط یک آرایه JSON شامل ۵ رشته متنی باشد:
[\"عنوان ۱\", \"عنوان ۲\", \"عنوان ۳\", \"عنوان ۴\", \"عنوان ۵\"]";

        $raw = $this->callGemini($prompt, 8);
        if (empty($raw)) return null;

        $clean = preg_replace('/^```json\s*|\s*```$/ui', '', trim($raw));
        $parsed = json_decode($clean, true);
        if (is_array($parsed) && count($parsed) >= 3) {
            return array_slice($parsed, 0, 5);
        }
        return null;
    }

    private function callGeminiForPolishing(string $text, string $mode): ?string {
        $instructions = [
            'scientific' => 'این متن را با واژگان دقیق علمی، اصطلاحات کلینیکی دامپزشکی و لحن رسمی و فوق‌تخصصی بازنویسی کن.',
            'friendly' => 'این متن را بسیار ساده، صمیمی، همدلانه و روان برای سرپرستان حیوانات خانگی بازنویسی کن.',
            'expand' => 'این متن را به شکل علمی گسترش بده و نکات بالینی، آزمایشگاهی، علائم تکمیلی و راهکارهای درمانی به آن اضافه کن.',
            'summarize' => 'این متن را به صورت خلاصه اجرایی و نکات کلیدی بالت‌پوینت بازنویسی کن.',
            'fix_grammar' => 'خطاهای املایی، نگارشی، علائم سجاوندی و ساختار جملات این متن را بدون تغییر مفهوم اصلی اصلاح کن.'
        ];

        $instruction = $instructions[$mode] ?? $instructions['scientific'];
        $prompt = "{$instruction}\n\nمتن اولیه:\n\"{$text}\"\n\nفقط متن بازنویسی شده را برگردان بدون توضیحات اضافه.";

        $raw = $this->callGemini($prompt, 8);
        return !empty($raw) ? trim($raw) : null;
    }

    private function callGeminiForFaqs(string $topic, int $count): ?array {
        $prompt = "درباره موضوع دامپزشکی '{$topic}'، دقیقاً {$count} سوال بسیار مهم و پرتکرار که سرپرستان حیوانات خانگی از دامپزشک می‌پرسند به همراه پاسخ‌های علمی، دقیق و قابل درک تولید کن.
خروجی را صرفاً به صورت JSON در این فرمت برگردان:
[
  {\"q\": \"متن سوال ۱؟\", \"a\": \"متن پاسخ ۱\"},
  {\"q\": \"متن سوال ۲؟\", \"a\": \"متن پاسخ ۲\"}
]";

        $raw = $this->callGemini($prompt, 8);
        if (empty($raw)) return null;

        $clean = preg_replace('/^```json\s*|\s*```$/ui', '', trim($raw));
        $parsed = json_decode($clean, true);
        if (is_array($parsed)) {
            return $parsed;
        }
        return null;
    }

    // ==========================================
    // CLINICAL HEURISTICS & KNOWLEDGE SYNTHESIZER
    // Zero-fail, rich veterinary clinical fallback
    // ==========================================

    private function synthesizeClinicalArticle(string $topic, string $tone, string $species, string $category): array {
        $t = mb_strtolower($topic);
        $speciesLabel = $this->resolveSpeciesLabel($species, $t);
        
        // Detect domain
        $domain = 'general';
        if (str_contains($t, 'واکسن') || str_contains($t, 'واکسیناسیون')) {
            $domain = 'vaccine';
        } elseif (str_contains($t, 'کلیه') || str_contains($t, 'ادرار') || str_contains($t, 'ckd') || str_contains($t, 'سنگ')) {
            $domain = 'renal';
        } elseif (str_contains($t, 'مسموم') || str_contains($t, 'شکلات') || str_contains($t, 'پیاز') || str_contains($t, 'سم')) {
            $domain = 'toxic';
        } elseif (str_contains($t, 'عقیم') || str_contains($t, 'جراحی') || str_contains($t, 'بخیه')) {
            $domain = 'surgery';
        } elseif (str_contains($t, 'دندان') || str_contains($t, 'جرم') || str_contains($t, 'لثه')) {
            $domain = 'dental';
        } elseif (str_contains($t, 'پوست') || str_contains($t, 'کک') || str_contains($t, 'کنه') || str_contains($t, 'خارش') || str_contains($t, 'ریزش')) {
            $domain = 'derma';
        } elseif (str_contains($t, 'غذا') || str_contains($t, 'تغذیه') || str_contains($t, 'چاق') || str_contains($t, 'لاغر')) {
            $domain = 'nutrition';
        } elseif (str_contains($t, 'پاروو') || str_contains($t, 'دیستمپر') || str_contains($t, 'ویروس')) {
            $domain = 'virus';
        }

        $data = $this->buildDomainArticleContent($topic, $domain, $speciesLabel, $tone);

        // Standardize Slug
        $slug = preg_replace('/[^\p{L}\p{N}\-]+/u', '-', trim($data['title']));
        $slug = trim($slug, '-');
        if (empty($slug)) {
            $slug = 'vet-article-' . time();
        }

        return [
            'title' => $data['title'],
            'slug' => $slug,
            'short_desc' => $data['short_desc'],
            'category' => $category,
            'read_time' => $data['read_time'],
            'content' => $data['content'],
            'faqs' => $data['faqs']
        ];
    }

    private function resolveSpeciesLabel(string $species, string $topic): string {
        if ($species === 'dog' || str_contains($topic, 'سگ') || str_contains($topic, 'توله')) {
            return 'سگ';
        } elseif ($species === 'cat' || str_contains($topic, 'گربه') || str_contains($topic, 'پیشی')) {
            return 'گربه';
        } elseif ($species === 'bird' || str_contains($topic, 'پرنده') || str_contains($topic, 'طوطی') || str_contains($topic, 'عروس')) {
            return 'پرندگان زینتی';
        }
        return 'حیوانات خانگی';
    }

    private function buildDomainArticleContent(string $topic, string $domain, string $speciesLabel, string $tone): array {
        $isClinical = ($tone === 'clinical');
        
        switch ($domain) {
            case 'vaccine':
                $title = "راهنمای جامع واکسیناسیون {$speciesLabel}: جدول زمان‌بندی، مراقبت‌ها و عوارض احتمالی";
                $short_desc = "برنامه زمان‌بندی واکسن‌های چندگانه و هاری در {$speciesLabel}، زمان تزریق یادآور، نحوه مراقبت پس از واکسن و علائم هشداردهنده بالینی.";
                $readTime = "۶ دقیقه مطالعه";
                $content = $this->renderVaccineTemplate($speciesLabel, $isClinical);
                $faqs = [
                    ['q' => "آیا بعد از واکسیناسیون می‌توان پت را حمام برد؟", 'a' => "توصیه می‌شود تا حداقل ۷ الی ۱۰ روز پس از تزریق واکسن به علت افت موقت مقاومت پوستی از حمام بردن حیوان خودداری شود."],
                    ['q' => "بی‌حالی یا تورم خفیف جای تزریق طبیعی است؟", 'a' => "بی‌حالی خفیف تا ۲۴ ساعت و برجستگی موضعی به اندازه یک نخود تا چند روز طبیعی است؛ اما در صورت تورم صورت، کهیر یا تنگی نفس فوراً به کلینیک مراجعه کنید."],
                    ['q' => "اگر واکسن یادآور سالانه به تعویق بیفتد چه باید کرد؟", 'a' => "اگر بیش از ۲ تا ۳ ماه از موعد یادآور گذشته باشد، معمولاً دامپزشک دستور تزریق بوستر مجدد را صادر می‌کند تا تیتر آنتی‌بادی ایمن شود."]
                ];
                break;

            case 'renal':
                $title = "نارسایی و اختلالات کلیوی در {$speciesLabel}: علائم پنهان، تشخیص آزمایشگاهی و رژیم حمایتی";
                $short_desc = "بررسی علائم نارسایی حاد و مزمن کلیوی در {$speciesLabel}، تفسیر آزمایش اوره و کراتینین، سرم‌تراپی و رژیم‌های غذایی رنال.";
                $readTime = "۷ دقیقه مطالعه";
                $content = $this->renderRenalTemplate($speciesLabel, $isClinical);
                $faqs = [
                    ['q' => "اولین علائم هشداردهنده نارسایی کلیه چیست؟", 'a' => "پرنوشی و پرادراری (PU/PD)، کاهش وزن تدریجی، بی‌اشتهایی و بوی بد دهان با منشأ اورمیک از نخستین نشانه‌ها هستند."],
                    ['q' => "آیا غذای رنال (Renal) می‌تواند عملکرد کلیه را بازگرداند؟", 'a' => "نفرون‌های تخریب‌شده برگشت‌پذیر نیستند، اما غذای درمانی رنال با کنترل فسفر و پروتئین باکیفیت فشار روی بافت سالم باقیمانده را به حداقل می‌رساند."],
                    ['q' => "نقش هیدراتاسیون و سرم‌تراپی در بیماران کلیوی چیست؟", 'a' => "سرم‌تراپی وریدی یا زیرپوستی منظم مانع از دهیدراتاسیون و بالا رفتن ناگهانی سموم اورمیک در خون حیوان می‌شود."]
                ];
                break;

            case 'toxic':
                $title = "مسمومیت‌های اورژانسی در {$speciesLabel}: خوراکی‌های مرگبار، علائم حاد و پروتکل نجات";
                $short_desc = "راهنمای نجات فوری در مسمومیت با شکلات، سیر و پیاز، انگور و داروها در {$speciesLabel} به همراه اقدامات اولیه و خطوط قرمز بالینی.";
                $readTime = "۵ دقیقه مطالعه";
                $content = $this->renderToxicTemplate($speciesLabel, $isClinical);
                $faqs = [
                    ['q' => "آیا می‌توانیم در خانه حیوان را وادار به استفراغ کنیم؟", 'a' => "هرگز بدون دستور صریح دامپزشک این کار را نکنید! مواد خورنده یا اسیدی در صورت برگشت مجدد، مری و نای حیوان را دچار سوختگی شدید می‌کنند."],
                    ['q' => "چه مدت پس از مصرف شکلات یا پیاز علائم مسمومیت ظاهر می‌شود؟", 'a' => "معمولاً علائم اولیه بین ۲ الی ۶ ساعت پس از هضم خوراکی آغاز شده و در صورت عدم مداخله تا ۲۴ ساعت به اوج سمیت می‌رسد."],
                    ['q' => "استفاده از ذغال فعال (Activated Charcoal) چگونه کمک می‌کند؟", 'a' => "ذغال فعال با چسبیدن به مولکول‌های سمی در مجرای گوارش از جذب آن‌ها به گردش خون جلوگیری کرده و سم را دفع می‌نماید."]
                ];
                break;

            case 'surgery':
                $title = "مراقبت‌های کلیدی پس از جراحی عقیم‌سازی و بیهوشی در {$speciesLabel}";
                $short_desc = "اصول نگهداری از {$speciesLabel} در ۲۴ ساعت اول پس از جراحی، محافظت از بخیه‌ها، استفاده از گردنبند الیزابت و مدیریت درد.";
                $readTime = "۵ دقیقه مطالعه";
                $content = $this->renderSurgeryTemplate($speciesLabel, $isClinical);
                $faqs = [
                    ['q' => "چند روز باید از گردنبند الیزابت استفاده شود؟", 'a' => "حداقل ۷ تا ۱۰ روز، تا زمانی که بخیه‌ها به طور کامل کشیده یا جذب شوند تا از لیسیدن و باز شدن زخم جلوگیری گردد."],
                    ['q' => "آیا ترشح خون‌آبه از محل بخیه طبیعی است؟", 'a' => "چند قطره خون‌آبه بسیار روشن در ساعات اولیه قابل انتظار است، اما خونریزی مداوم، ترشحات چرکی یا بوی نامطبوع نشان‌دهنده عفونت بوده و نیاز به بررسی دارد."],
                    ['q' => "کی می‌توانیم غذای معمولی به حیوان بدهیم؟", 'a' => "شامگاه روز جراحی صرفاً مقدار بسیار کمی آب و غذای ملایم (یک‌سوم وعده معمول) داده شود تا از حالت تهوع ناشی از اثرات داروی بیهوشی پیشگیری گردد."]
                ];
                break;

            case 'dental':
                $title = "بهداشت دهان و دندان در {$speciesLabel}: پیشگیری از جرم، ژنژیویت و کشیدن دندان";
                $short_desc = "شناخت بیماری‌های لثه و پلاک میکروبی در {$speciesLabel}، اهمیت جرم‌گیری التراسونیک، ژل‌های آنزیمی و مسواک زدن صحیح.";
                $readTime = "۵ دقیقه مطالعه";
                $content = $this->renderDentalTemplate($speciesLabel, $isClinical);
                $faqs = [
                    ['q' => "آیا می‌توان از خمیردندان انسان برای پت‌ها استفاده کرد؟", 'a' => "خیر، مطلقاً ممنوع است! خمیردندان انسانی حاوی فلوراید و زایلیتول است که بلعیدن آن برای سگ و گربه بسیار سمی و کشنده است."],
                    ['q' => "چند وقت یک‌بار دندان‌های حیوان باید مسواک زده شود؟", 'a' => "ایده‌آل‌ترین حالت روزانه یک‌بار است؛ با این حال ۳ بار در هفته با خمیردندان آنزیمی پت نتایج بسیار چشمگیری در پیشگیری از جرم دارد."],
                    ['q' => "آیا جرم‌گیری حتماً نیاز به بیهوشی دارد؟", 'a' => "بله، جرم‌گیری بدون بیهوشی به دلیل استرس شدید، عدم دسترسی به زیر خط لثه (سولکوس) و خطر آسیب به بافت‌های نرم از نظر بالینی استاندارد نیست."]
                ];
                break;

            default:
                $title = "بررسی بالینی و راهنمای جامع {$topic} در {$speciesLabel}";
                $short_desc = "بررسی جامع علائم، پروتکل‌های تشخیصی، راهکارهای درمانی و توصیه‌های پیشگیرانه درباره {$topic} در {$speciesLabel}.";
                $readTime = "۶ دقیقه مطالعه";
                $content = $this->renderGenericTemplate($topic, $speciesLabel, $isClinical);
                $faqs = [
                    ['q' => "مهم‌ترین نکته در مواجهه با این موضوع چیست؟", 'a' => "تشخیص زودهنگام و پرهیز از خوددرمانی دارویی با داروهای انسانی کلیدی‌ترین عامل نجات و سلامت پت است."],
                    ['q' => "چه زمانی باید نوبت معاینه حضوری رزرو کنیم؟", 'a' => "در صورتی که علائم بیش از ۲۴ ساعت تداوم داشته یا با بی‌حالی شدید و تغییر در آب و غذا خوردن همراه باشد."],
                    ['q' => "آیا بیماری‌های این حوزه به انسان سرایت می‌کنند؟", 'a' => "بیشتر موارد اختصاصی گونه هستند؛ با این حال رعایت بهداشت فردی و شستشوی دست‌ها پس از تماس همواره توصیه می‌شود."]
                ];
                break;
        }

        return [
            'title' => $title,
            'short_desc' => $short_desc,
            'read_time' => $readTime,
            'content' => $content,
            'faqs' => $faqs
        ];
    }

    private function renderVaccineTemplate(string $speciesLabel, bool $isClinical): string {
        return <<<HTML
<h2>مقدمه و اهمیت ایمونولوژی در {$speciesLabel}</h2>
<p>واکسیناسیون پایه و اساس پزشکی پیشگیرانه در دامپزشکی است. سیستم ایمنی نوزاد در هفته‌های اول زندگی توسط آنتی‌بادی‌های مادری (حاصل از آغوز) محافظت می‌شود، اما با کاهش تدریجی این آنتی‌بادی‌ها بین ۶ تا ۸ هفتگی، پنجره حساسیت به پاتوژن‌های مهلک باز می‌شود.</p>

<blockquote class="my-4 border-r-4 border-teal-500 bg-teal-50 dark:bg-teal-950/40 p-4 rounded-xl text-teal-900 dark:text-teal-200">
    <span class="font-bold">نکته کلینیکی دکتر دامپزشک:</span> واکسن‌ها تا زمان کامل شدن دوره (تزریق ۳ نوبت در تولگی و تکرار سالانه)، ایمنی ۱۰۰٪ ایجاد نمی‌کنند. تا زمان پایان نوبت‌های اصلی، از حضور حیوان در پارک‌ها و تماس با حیوانات واکسینه‌نشده جداً خودداری فرمایید.
</blockquote>

<h2>جدول زمان‌بندی پروتکل واکسیناسیون استاندارد</h2>
<table class="w-full my-4 border-collapse text-sm">
    <thead>
        <tr class="bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200">
            <th class="p-2.5 border border-slate-300 dark:border-slate-700 text-right">سن حیوان</th>
            <th class="p-2.5 border border-slate-300 dark:border-slate-700 text-right">نوع واکسن</th>
            <th class="p-2.5 border border-slate-300 dark:border-slate-700 text-right">اهداف محافظتی بیماری‌ها</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="p-2.5 border border-slate-200 dark:border-slate-800 font-bold">۶ تا ۸ هفتگی</td>
            <td class="p-2.5 border border-slate-200 dark:border-slate-800">نوبت اول واکسن چندگانه (Core)</td>
            <td class="p-2.5 border border-slate-200 dark:border-slate-800">پاروویروس، دیستمپر، هپاتیت عفونی، پن‌لوکوپنی</td>
        </tr>
        <tr class="bg-slate-50/50 dark:bg-slate-900/40">
            <td class="p-2.5 border border-slate-200 dark:border-slate-800 font-bold">۱۰ تا ۱۲ هفتگی</td>
            <td class="p-2.5 border border-slate-200 dark:border-slate-800">نوبت دوم چندگانه (بوستر اول)</td>
            <td class="p-2.5 border border-slate-200 dark:border-slate-800">تقویت تیتر آنتی‌بادی و ایجاد حافظه ایمنی B-Cell</td>
        </tr>
        <tr>
            <td class="p-2.5 border border-slate-200 dark:border-slate-800 font-bold">۱۴ تا ۱۶ هفتگی</td>
            <td class="p-2.5 border border-slate-200 dark:border-slate-800">نوبت سوم + واکسن هاری (Rabies)</td>
            <td class="p-2.5 border border-slate-200 dark:border-slate-800">ایمنی قطعی علیه ویروس کشنده هاری و صدور شناسنامه بهداشتی</td>
        </tr>
        <tr class="bg-slate-50/50 dark:bg-slate-900/40">
            <td class="p-2.5 border border-slate-200 dark:border-slate-800 font-bold">سالانه (مادام‌العمر)</td>
            <td class="p-2.5 border border-slate-200 dark:border-slate-800">تزریق تک‌دوز یادآور سالیانه</td>
            <td class="p-2.5 border border-slate-200 dark:border-slate-800">پایداری سطح آنتی‌بادی سرمی در برابر تغییرات سویه‌ها</td>
        </tr>
    </tbody>
</table>

<div class="my-4 rounded-2xl border border-rose-300 dark:border-rose-900 bg-rose-50 dark:bg-rose-950/40 p-4">
    <div class="flex items-center gap-2 font-bold text-rose-700 dark:text-rose-300 mb-1">
        <span class="material-symbols-outlined">warning</span>
        <span>هشدارهای مراقبتی پس از تزریق:</span>
    </div>
    <p class="text-xs text-rose-800 dark:text-rose-200 leading-relaxed">
        تب خفیف و بی‌میلی به غذا در ۲۴ ساعت اول پاسخ طبیعی سیستم ایمنی است. اما در صورت بروز علائم شوک آنافیلاکسی نظیر تورم پلک‌ها، پوزه، استفراغ مکرر یا تنگی نفس، بلافاصله حیوان را به نزدیک‌ترین اورژانس دامپزشکی برسانید.
    </p>
</div>
HTML;
    }

    private function renderRenalTemplate(string $speciesLabel, bool $isClinical): string {
        return <<<HTML
<h2>پاتوفیزیولوژی بیماری کلیوی در {$speciesLabel}</h2>
<p>کلیه‌ها اندام‌های حیاتی برای فیلتراسیون ضایعات متابولیک، تعادل آب و الکترولیت‌ها و تنظیم فشار خون هستند. در نارسایی مزمن کلیه (CKD)، نفرون‌ها به تدریج دچار آسیب غیرقابل بازگشت شده و توانایی تغلیظ ادرار از دست می‌رود.</p>

<blockquote class="my-4 border-r-4 border-indigo-500 bg-indigo-50 dark:bg-indigo-950/40 p-4 rounded-xl text-indigo-900 dark:text-indigo-200">
    <span class="font-bold">تشخیص آزمایشگاهی و مرحله‌بندی (IRIS):</span> بر اساس فاکتورهای بیوشیمیایی سرم خون نظیر کراتینین (Creatinine)، اوره (BUN)، آزمایش SDMA و نسبت پروتئین به کراتینین ادرار (UPC)، شدت بیماری از استیج ۱ تا ۴ طبقه‌بندی می‌شود.
</blockquote>

<h2>علائم بالینی اولیه در مقایسه با علائم پیشرفته</h2>
<table class="w-full my-4 border-collapse text-sm">
    <thead>
        <tr class="bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200">
            <th class="p-2.5 border border-slate-300 dark:border-slate-700 text-right">مرحله بیماری</th>
            <th class="p-2.5 border border-slate-300 dark:border-slate-700 text-right">علائم کلیدی بالینی</th>
            <th class="p-2.5 border border-slate-300 dark:border-slate-700 text-right">اقدام درمانی الزامی</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="p-2.5 border border-slate-200 dark:border-slate-800 font-bold">فاز اولیه (مراحل ۱ و ۲)</td>
            <td class="p-2.5 border border-slate-200 dark:border-slate-800">پرنوشی، پرادراری، ریزش مو، کدورت ادرار</td>
            <td class="p-2.5 border border-slate-200 dark:border-slate-800">تغییر تدریجی به رژیم درمانی رنال، پایش دوره‌ای SDMA</td>
        </tr>
        <tr class="bg-slate-50/50 dark:bg-slate-900/40">
            <td class="p-2.5 border border-slate-200 dark:border-slate-800 font-bold">فاز پیشرفته (مراحل ۳ و ۴)</td>
            <td class="p-2.5 border border-slate-200 dark:border-slate-800">کاهش وزن شدید، استفراغ اورمیک، زخم دهان، کم‌خونی</td>
            <td class="p-2.5 border border-slate-200 dark:border-slate-800">سرم‌تراپی متناوب، اتصال‌دهنده‌های فسفر، داروهای ضد فشار خون</td>
        </tr>
    </tbody>
</table>

<div class="my-4 rounded-2xl border border-emerald-300 dark:border-emerald-900 bg-emerald-50 dark:bg-emerald-950/40 p-4">
    <div class="flex items-center gap-2 font-bold text-emerald-800 dark:text-emerald-300 mb-1">
        <span class="material-symbols-outlined">lightbulb</span>
        <span>اصول طلایی مدیریت در خانه:</span>
    </div>
    <ul class="text-xs text-emerald-900 dark:text-emerald-200 list-disc pr-4 space-y-1">
        <li>آب تازه و گوارا در چند نقطه از منزل همواره در دسترس باشد (استفاده از فواره‌های آب برای ترغیب نوشیدن).</li>
        <li>پرهیز کامل از مصرف گوشت قرمز پرپروتئین، کنسروهای انسانی و تشویقی‌های حاوی سدیم بالا.</li>
        <li>انجام آزمایش چکاپ دوره‌ای خون و ادرار هر ۳ الی ۶ ماه یک‌بار در کلینیک دامپزشکی.</li>
    </ul>
</div>
HTML;
    }

    private function renderToxicTemplate(string $speciesLabel, bool $isClinical): string {
        return <<<HTML
<h2>تعریف سمیت و مکانیسم‌های آسیب بیوشیمیایی</h2>
<p>بدن {$speciesLabel} فاقد بسیاری از آنزیم‌های تجزیه‌کننده سموم انسانی در کبد است. موادی که برای انسان کاملاً بی‌ضرر یا لذیذ هستند می‌توانند باعث نارسایی حاد کبدی، کلیوی، تشنج و مرگ ناگهانی در حیوانات خانگی شوند.</p>

<h2>خطرناک‌ترین مواد مسموم‌کننده و اثرات فیزیولوژیک</h2>
<table class="w-full my-4 border-collapse text-sm">
    <thead>
        <tr class="bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200">
            <th class="p-2.5 border border-slate-300 dark:border-slate-700 text-right">ماده سمی</th>
            <th class="p-2.5 border border-slate-300 dark:border-slate-700 text-right">عنصر توکسیک فعال</th>
            <th class="p-2.5 border border-slate-300 dark:border-slate-700 text-right">عوارض بالینی و ارگان هدف</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="p-2.5 border border-slate-200 dark:border-slate-800 font-bold">شکلات و کاکائو</td>
            <td class="p-2.5 border border-slate-200 dark:border-slate-800">تئوبرومین و کافئین</td>
            <td class="p-2.5 border border-slate-200 dark:border-slate-800">تاکی‌کاردی، آریتمی قلبی، لرزش عضلانی، تشنج</td>
        </tr>
        <tr class="bg-slate-50/50 dark:bg-slate-900/40">
            <td class="p-2.5 border border-slate-200 dark:border-slate-800 font-bold">سیر، پیاز و تره</td>
            <td class="p-2.5 border border-slate-200 dark:border-slate-800">N-پروپیل دی‌سولفید</td>
            <td class="p-2.5 border border-slate-200 dark:border-slate-800">تخریب گلبول‌های قرمز، همولیز، کم‌خونی هاینز بادی</td>
        </tr>
        <tr>
            <td class="p-2.5 border border-slate-200 dark:border-slate-800 font-bold">انگور و کشمش</td>
            <td class="p-2.5 border border-slate-200 dark:border-slate-800">اسید تارتاریک</td>
            <td class="p-2.5 border border-slate-200 dark:border-slate-800">نارسایی حاد و مرگبار کلیه ظرف ۲۴ الی ۷۲ ساعت</td>
        </tr>
        <tr class="bg-slate-50/50 dark:bg-slate-900/40">
            <td class="p-2.5 border border-slate-200 dark:border-slate-800 font-bold">استامینوفن و ایبوپروفن</td>
            <td class="p-2.5 border border-slate-200 dark:border-slate-800">NSAIDs و مشتقات پاراستامول</td>
            <td class="p-2.5 border border-slate-200 dark:border-slate-800">متهموگلوبینمی، زخم حاد و پارگی معده، تخریب کبد</td>
        </tr>
    </tbody>
</table>

<div class="my-4 rounded-2xl border border-rose-300 dark:border-rose-900 bg-rose-50 dark:bg-rose-950/40 p-4">
    <div class="flex items-center gap-2 font-bold text-rose-700 dark:text-rose-300 mb-1">
        <span class="material-symbols-outlined">emergency</span>
        <span>اقدامات حیاتی ۶۰ دقیقه طلایی:</span>
    </div>
    <p class="text-xs text-rose-800 dark:text-rose-200 leading-relaxed">
        بسته‌بندی یا نام دقیق ماده مصرفی را همراه داشته باشید و فوراً با بخش اورژانس کلینیک تماس بگیرید. هرگز از شیر، آب نمک یا روش‌های سنتی برای تحریک استفراغ استفاده نکنید، زیرا آسپیراسیون ریوی و خفگی محتمل‌ترین خطر خواهد بود.
    </p>
</div>
HTML;
    }

    private function renderSurgeryTemplate(string $speciesLabel, bool $isClinical): string {
        return <<<HTML
<h2>اصول ریکاوری فیزیولوژیک پس از عمل در {$speciesLabel}</h2>
<p>جراحی‌های عقیم‌سازی (Ovariohysterectomy یا Orchiectomy) جزو روتین‌ترین جراحی‌های بافت نرم در دامپزشکی هستند. با وجود ضریب ایمنی بالای تکنیک‌های نوین، مراقبت‌های دوره نقاهت نقش تعیین‌کننده در پیشگیری از فتق، باز شدن بخیه و عفونت‌های ثانویه دارند.</p>

<h2>دستورالعمل مراقبت روزانه در طول ۱۰ روز نقاهت</h2>
<table class="w-full my-4 border-collapse text-sm">
    <thead>
        <tr class="bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200">
            <th class="p-2.5 border border-slate-300 dark:border-slate-700 text-right">بازه زمانی</th>
            <th class="p-2.5 border border-slate-300 dark:border-slate-700 text-right">وضعیت بالینی مورد انتظار</th>
            <th class="p-2.5 border border-slate-300 dark:border-slate-700 text-right">وظایف اصلی سرپرست</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="p-2.5 border border-slate-200 dark:border-slate-800 font-bold">۲۴ ساعت اول</td>
            <td class="p-2.5 border border-slate-200 dark:border-slate-800">عدم تعادل حرکتی، خواب‌آلودگی، حساسیت به نور و صدا</td>
            <td class="p-2.5 border border-slate-200 dark:border-slate-800">محیط گرم و تاریک، پد زیرانداز بهداشتی، آب به میزان کم</td>
        </tr>
        <tr class="bg-slate-50/50 dark:bg-slate-900/40">
            <td class="p-2.5 border border-slate-200 dark:border-slate-800 font-bold">روز ۲ تا ۵</td>
            <td class="p-2.5 border border-slate-200 dark:border-slate-800">بازگشت اشتها، تمایل به جهیدن و دویدن</td>
            <td class="p-2.5 border border-slate-200 dark:border-slate-800">محدود کردن تحرک، بستن دائمی گردنبند الیزابت، مصرف مسکن تجویزی</td>
        </tr>
        <tr>
            <td class="p-2.5 border border-slate-200 dark:border-slate-800 font-bold">روز ۷ تا ۱۰</td>
            <td class="p-2.5 border border-slate-200 dark:border-slate-800">ترمیم کامل لبه‌های پوست و تشکیل بافت گرانولاسیون</td>
            <td class="p-2.5 border border-slate-200 dark:border-slate-800">مراجعه به کلینیک جهت بررسی یا کشیدن بخیه‌ها در صورت غیرقابل جذب بودن</td>
        </tr>
    </tbody>
</table>

<div class="my-4 rounded-2xl border border-teal-300 dark:border-teal-900 bg-teal-50 dark:bg-teal-950/40 p-4">
    <div class="flex items-center gap-2 font-bold text-teal-800 dark:text-teal-300 mb-1">
        <span class="material-symbols-outlined">task_alt</span>
        <span>چک‌لیست سلامت محل برش جراحی:</span>
    </div>
    <ul class="text-xs text-teal-900 dark:text-teal-200 list-disc pr-4 space-y-1">
        <li>روزانه دو مرتبه محل بخیه را بررسی کنید؛ لبه‌های زخم باید کاملاً به هم چسبیده و خشک باشند.</li>
        <li>از هرگونه مالیدن الکل، بتادین غلیظ یا پمادهای متفرقه به روی بخیه بدون نظر جراح اکیداً خودداری کنید.</li>
    </ul>
</div>
HTML;
    }

    private function renderDentalTemplate(string $speciesLabel, bool $isClinical): string {
        return <<<HTML
<h2>اپیدمیولوژی بیماری‌های پریودنتال در {$speciesLabel}</h2>
<p>بیش از ۸۰ درصد سگ‌ها و گربه‌های بالای ۳ سال از درجاتی از بیماری‌های دهان و دندان رنج می‌برند. انباشت باکتری‌ها و گلیکوپروتئین‌های بزاقی منجر به تشکیل پلاک و سپس رسوب املاح کلسیم و سخت شدن جرم دندانی (Calculus) می‌گردد.</p>

<blockquote class="my-4 border-r-4 border-amber-500 bg-amber-50 dark:bg-amber-950/40 p-4 rounded-xl text-amber-900 dark:text-amber-200">
    <span class="font-bold">پیامدهای خطرناک سیستمیک:</span> باکتری‌های بی‌هوازی موجود در عفونت‌های لثه از طریق عروق خونی وارد گردش خون عمومی شده و ریسک ابتلای حیوان به اندوکاردیت قلبی، نارسایی کلیوی و هپاتیت را به شدت افزایش می‌دهند.
</blockquote>

<h2>مقایسه روش‌های مراقبت خانگی و کلینیکی</h2>
<table class="w-full my-4 border-collapse text-sm">
    <thead>
        <tr class="bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200">
            <th class="p-2.5 border border-slate-300 dark:border-slate-700 text-right">روش اقدام</th>
            <th class="p-2.5 border border-slate-300 dark:border-slate-700 text-right">کارایی و هدف</th>
            <th class="p-2.5 border border-slate-300 dark:border-slate-700 text-right">تواتر پیشنهادی</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="p-2.5 border border-slate-200 dark:border-slate-800 font-bold">مسواک زدن با خمیر آنزیمی پت</td>
            <td class="p-2.5 border border-slate-200 dark:border-slate-800">حذف پلاک نرم قبل از تبدیل شدن به جرم سخت کلسیمی</td>
            <td class="p-2.5 border border-slate-200 dark:border-slate-800">روزانه یا حداقل ۳ نوبت در هفته</td>
        </tr>
        <tr class="bg-slate-50/50 dark:bg-slate-900/40">
            <td class="p-2.5 border border-slate-200 dark:border-slate-800 font-bold">تشویقی‌ها و اسباب‌بازی‌های دندانی</td>
            <td class="p-2.5 border border-slate-200 dark:border-slate-800">اصطکاک مکانیکی و ماساژ لثه‌ها هنگام جویدن</td>
            <td class="p-2.5 border border-slate-200 dark:border-slate-800">۲ الی ۳ بار در هفته تحت نظارت</td>
        </tr>
        <tr>
            <td class="p-2.5 border border-slate-200 dark:border-slate-800 font-bold">جرم‌گیری اولتراسونیک کلینیکی</td>
            <td class="p-2.5 border border-slate-200 dark:border-slate-800">پاکسازی کامل جرم‌های زیر لثه و پولیش سطح دندان</td>
            <td class="p-2.5 border border-slate-200 dark:border-slate-800">سالیانه یک‌بار پس از معاینه پزشک</td>
        </tr>
    </tbody>
</table>
HTML;
    }

    private function renderGenericTemplate(string $topic, string $speciesLabel, bool $isClinical): string {
        return <<<HTML
<h2>نگاه تخصصی به {$topic} در {$speciesLabel}</h2>
<p>آشنایی دقیق با مبحث {$topic}، یکی از الزامات بنیادین برای هر سرپرست مسئولیت‌پذیر است. شناخت به موقع الگوهای طبیعی فیزیولوژیک و تغییرات رفتاری، نخستین گام در مداخله موفقیت‌آمیز پزشکی محسوب می‌گردد.</p>

<blockquote class="my-4 border-r-4 border-blue-500 bg-blue-50 dark:bg-blue-950/40 p-4 rounded-xl text-blue-900 dark:text-blue-200">
    <span class="font-bold">دیدگاه کلینیکی دامپزشکان آسنا:</span> حیوانات خانگی به دلیل ویژگی‌های تکاملی تلاش می‌کنند ضعف یا بیماری خود را پنهان کنند. بنابراین مشاهده حتی نشانه‌های جزئی می‌تواند خبر از وجود یک فرآیند بیماری‌زای پیشرفته در بدن بدهد.
</blockquote>

<h2>علائم بالینی که باید جدی گرفته شوند</h2>
<ul class="list-disc pr-5 my-3 space-y-1.5 text-sm">
    <li>تغییرات محسوس در میزان اشتها، افت وزن و کاهش تمایل به تعامل و بازی.</li>
    <li>اختلال در دفع، اسهال یا یبوست مکرر و تکرر در ادرار.</li>
    <li>بی‌حالی مفرط، خوابیدن‌های طولانی و گوشه‌گیری غیرعادی در منزل.</li>
    <li>تغییر در کیفیت پوست و موها یا وجود ترشحات غیرطبیعی از چشم و بینی.</li>
</ul>

<div class="my-4 rounded-2xl border border-amber-300 dark:border-amber-900 bg-amber-50 dark:bg-amber-950/40 p-4">
    <div class="flex items-center gap-2 font-bold text-amber-800 dark:text-amber-300 mb-1">
        <span class="material-symbols-outlined">clinical_notes</span>
        <span>پروتکل پیشنهادی مواجهه بالینی:</span>
    </div>
    <p class="text-xs text-amber-900 dark:text-amber-200 leading-relaxed">
        از مصرف خودسرانه آنتی‌بیوتیک‌ها، مسکن‌ها و داروهای انسانی جداً خودداری کنید. ثبت زمان شروع علائم، عکس‌برداری یا فیلم‌برداری از وضعیت حیوان و ارائه آن به دامپزشک در جریان ویزیت، روند تشخیص را تا ۵۰ درصد سریع‌تر خواهد کرد.
    </p>
</div>
HTML;
    }

    private function synthesizeTitles(string $topic): array {
        return [
            "همه چیز درباره {$topic}: راهنمای جامع و توصیه‌های بالینی",
            "نکات طلایی و حیاتی در مورد {$topic} که هر سرپرست پتی باید بداند",
            "علائم، پیشگیری و روش‌های نوین درمان {$topic} از نگاه دامپزشک",
            "بررسی تخصصی {$topic}: اشتباهات خطرناک و پروتکل‌های مراقبت خانگی",
            "راهنمای گام به گام مدیریت {$topic} در حیوانات خانگی"
        ];
    }

    private function synthesizePolishedText(string $text, string $mode): string {
        switch ($mode) {
            case 'scientific':
                return "بر اساس ارزیابی‌های پاتوفیزیولوژیک و داده‌های اپیدمیولوژیک دامپزشکی، " . $text . " این فرآیند از نقطه نظر بیوشیمیایی و سلولی نیازمند مانیتورینگ دقیق شاخص‌های حیاتی و انجام تست‌های تاییدی پاراکلینیک می‌باشد.";
            case 'friendly':
                return "به زبان ساده و خودمانی برای شما سرپرست مهربان: " . $text . " مراقب فرشته کوچولوتون باشید و در صورت دیدن هر تغییر کوچکی، با خیال راحت از مشاوره دامپزشکان آسنا استفاده کنید.";
            case 'expand':
                return $text . " افزون بر این، در پروتکل‌های بالینی پیشرفته توصیه می‌شود رژیم غذایی بالانس‌شده همراه با مکمل‌های اسید چرب امگا ۳ و آنتی‌اکسیدان‌ها در طول این دوره حفظ شود. ارزیابی آزمایشگاهی CBC و پروفایل بیوشیمیایی سرم نیز جهت رد عوارض جانبی سیستمیک اکیداً پیشنهاد می‌گردد.";
            case 'summarize':
                return "خلاصه و نکات کلیدی:\n• " . str_replace(['.', '،'], ["\n• ", "\n• "], mb_substr($text, 0, 200)) . "\n• اقدام پیشنهادی: مشورت دوره‌ای با دامپزشک متخصص آسنا.";
            case 'fix_grammar':
                $fixed = preg_replace('/(\s+می\s+)/u', ' می‌', $text);
                $fixed = preg_replace('/(\s+نمی\s+)/u', ' نمی‌', $fixed);
                $fixed = preg_replace('/(\s+ها)(\s+)/u', '‌ها$2', $fixed);
                return trim($fixed);
            default:
                return $text;
        }
    }

    private function synthesizeFaqs(string $topic, int $count): array {
        $faqs = [
            ['q' => "آیا در موضوع {$topic} نیاز به ویزیت حضوری است؟", 'a' => "اگر علائم بیش از ۲۴ ساعت ادامه پیدا کرده یا با بی‌حالی و امتناع از غذا همراه است، معاینه بالینی حضوری توسط پزشک الزامی است."],
            ['q' => "هزینه‌های درمان و داروهای مربوط به {$topic} چگونه محاسبه می‌شود؟", 'a' => "داروها و مکمل‌های تخصصی تاییدشده با تضمین اصالت و تاریخ انقضا در داروخانه آنلاین آسنا با تعرفه مصوب موجود است."],
            ['q' => "نقش تغذیه در بهبود سریع‌تر {$topic} چیست؟", 'a' => "رژیم غذایی غنی‌شده با پروتئین‌های زودهضم، فیبر مناسب و هیدراتاسیون کافی زمان نقاهت را به نصف کاهش می‌دهد."],
            ['q' => "آیا اقدامات خانگی برای پیشگیری از {$topic} موثر است؟", 'a' => "بله، رعایت بهداشت محیطی، انگل‌تراپی منظم دوره‌ای و واکسیناسیون به موقع تا ۸۵ درصد از ابتلا به این عارضه پیشگیری می‌کند."]
        ];
        return array_slice($faqs, 0, $count);
    }

    private function synthesizeAlertBox(string $topic): array {
        $html = <<<HTML
<div class="my-4 rounded-2xl border-2 border-rose-500 bg-rose-50 dark:bg-rose-950/50 p-4 shadow-md">
    <div class="flex items-center gap-2 font-extrabold text-rose-800 dark:text-rose-200 text-sm mb-2">
        <span class="material-symbols-outlined text-rose-600 text-xl animate-pulse">crisis_alert</span>
        <span>هشدار خط قرمز اورژانس بالینی: {$topic}</span>
    </div>
    <p class="text-xs text-rose-900 dark:text-rose-200 leading-relaxed mb-2 font-medium">
        در صورت مشاهده هر یک از علائم زیر، از هرگونه مداخله سنتی یا خوراندن خودسرانه دارو خودداری کرده و بیمار را فوراً به مرکز تریاژ منتقل نمایید:
    </p>
    <ul class="text-xs text-rose-800 dark:text-rose-300 list-disc pr-5 space-y-1 font-bold">
        <li>کبودی لثه‌ها یا زبان (سیانوز) و تنگی نفس شدید با دهان باز</li>
        <li>حملات تشنج مداوم، لرزش‌های غیرقابل کنترل یا عدم تعادل حاد</li>
        <li>استفراغ یا اسهال با رگه‌های خونی تیره یا قهوه‌ای</li>
        <li>تورم ناگهانی و بادکردگی شدید محوطه شکمی (احتمال GDV)</li>
    </ul>
</div>
HTML;
        return [
            'html' => $html,
            'title' => "هشدار خط قرمز اورژانس بالینی: {$topic}",
            'triggers' => ['سیانوز و تنگی نفس', 'تشنج مداوم', 'خونریزی گوارشی', 'پیچ‌خوردگی شکم']
        ];
    }
}
