<?php
/**
 * ASENA Enterprise — AI Blog Copilot AJAX Endpoint
 * Handles requests from blog_editor.php
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/AiContentService.php';

// Auth Check: User must be logged in and role must be admin or doctor
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'لطفاً ابتدا وارد حساب کاربری خود شوید.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $pdo->prepare("SELECT id, role, name FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || !in_array($user['role'], ['admin', 'doctor'])) {
    echo json_encode(['status' => 'error', 'message' => 'دسترسی غیرمجاز: صرفاً پزشکان و مدیران مجاز به استفاده از هوش مصنوعی هستند.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $_POST['action'] ?? '';
$aiService = new AiContentService($pdo);

try {
    switch ($action) {
        case 'generate_article':
            $topic = trim($_POST['topic'] ?? '');
            $tone = trim($_POST['tone'] ?? 'clinical');
            $species = trim($_POST['species'] ?? 'all');
            $category = trim($_POST['category'] ?? 'medical');

            if (empty($topic)) {
                echo json_encode(['status' => 'error', 'message' => 'لطفاً موضوع مقاله را وارد نمایید.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $articleData = $aiService->generateArticle($topic, $tone, $species, $category);
            
            // Generate FAQ HTML for direct embedding if FAQs exist
            $faqHtml = '';
            if (!empty($articleData['faqs']) && is_array($articleData['faqs'])) {
                $faqHtml .= '<div class="mt-8 pt-6 border-t border-slate-200 dark:border-slate-800"><h3 class="text-lg font-bold mb-4 flex items-center gap-2 text-slate-800 dark:text-slate-100"><span class="material-symbols-outlined text-primary">help</span><span>پرسش‌های متداول و بالینی</span></h3><div class="space-y-3">';
                foreach ($articleData['faqs'] as $faq) {
                    $q = htmlspecialchars($faq['q'] ?? '');
                    $a = htmlspecialchars($faq['a'] ?? '');
                    $faqHtml .= "<details class=\"group rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/50 p-4 transition-all\"><summary class=\"cursor-pointer font-bold text-sm text-slate-800 dark:text-slate-200 flex items-center justify-between list-none\"><span>{$q}</span><span class=\"material-symbols-outlined text-slate-400 group-open:rotate-180 transition-transform\">expand_more</span></summary><p class=\"mt-3 text-xs md:text-sm text-slate-600 dark:text-slate-300 leading-relaxed pt-2 border-t border-slate-200/60 dark:border-slate-700/60\">{$a}</p></details>";
                }
                $faqHtml .= '</div></div>';
            }

            echo json_encode([
                'status' => 'success',
                'data' => array_merge($articleData, ['faq_html' => $faqHtml])
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'suggest_titles':
            $topic = trim($_POST['topic'] ?? '');
            $style = trim($_POST['style'] ?? 'high_ctr');

            if (empty($topic)) {
                echo json_encode(['status' => 'error', 'message' => 'موضوع برای عنوان‌سازی الزامی است.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $titles = $aiService->suggestTitles($topic, $style);
            echo json_encode([
                'status' => 'success',
                'data' => [
                    'titles' => $titles
                ]
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'polish_text':
            $text = trim($_POST['text'] ?? '');
            $mode = trim($_POST['mode'] ?? 'scientific');

            if (empty($text)) {
                echo json_encode(['status' => 'error', 'message' => 'متنی برای بازنویسی ارسال نشده است.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $polished = $aiService->polishText($text, $mode);
            echo json_encode([
                'status' => 'success',
                'data' => [
                    'polished' => $polished
                ]
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'generate_faqs':
            $topic = trim($_POST['topic'] ?? '');
            $count = max(1, min(6, (int)($_POST['count'] ?? 4)));

            if (empty($topic)) {
                echo json_encode(['status' => 'error', 'message' => 'موضوع برای سوالات متداول الزامی است.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $faqs = $aiService->generateFaqs($topic, $count);
            $faqHtml = '<div class="my-6 space-y-3">';
            foreach ($faqs as $f) {
                $q = htmlspecialchars($f['q']);
                $a = htmlspecialchars($f['a']);
                $faqHtml .= "<details class=\"group rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/50 p-4 transition-all\"><summary class=\"cursor-pointer font-bold text-sm text-slate-800 dark:text-slate-200 flex items-center justify-between list-none\"><span>{$q}</span><span class=\"material-symbols-outlined text-slate-400 group-open:rotate-180 transition-transform\">expand_more</span></summary><p class=\"mt-3 text-xs md:text-sm text-slate-600 dark:text-slate-300 leading-relaxed pt-2 border-t border-slate-200/60 dark:border-slate-700/60\">{$a}</p></details>";
            }
            $faqHtml .= '</div>';

            echo json_encode([
                'status' => 'success',
                'data' => [
                    'faqs' => $faqs,
                    'html' => $faqHtml
                ]
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'generate_alert':
            $topic = trim($_POST['topic'] ?? '');
            $alert = $aiService->generateClinicalAlert($topic);
            echo json_encode([
                'status' => 'success',
                'data' => $alert
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'generate_image':
            $prompt = trim($_POST['prompt'] ?? '');
            $model = trim($_POST['model'] ?? 'gpt-image-2.5-flare');
            $size = trim($_POST['size'] ?? '1024x1024');

            if (empty($prompt)) {
                echo json_encode(['status' => 'error', 'message' => 'توصیف تصویر (پرامپت) الزامی است.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $imageUrl = $aiService->generateImage($prompt, $model, $size);
            if ($imageUrl) {
                echo json_encode([
                    'status' => 'success',
                    'data' => [
                        'url' => $imageUrl,
                        'prompt' => $prompt,
                        'model' => $model
                    ]
                ], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'خطا در تولید تصویر توسط مدل‌های هوش مصنوعی تصویرساز AvalAI.'
                ], JSON_UNESCAPED_UNICODE);
            }
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'عملیات نامعتبر است.'], JSON_UNESCAPED_UNICODE);
            break;
    }
} catch (Throwable $e) {
    error_log("AI Blog Action Error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'خطا در پردازش هوش مصنوعی: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
