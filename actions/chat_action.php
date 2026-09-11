<?php
require __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/AiContentService.php';
AiContentService::loadEnv();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

// AvalAI Multi-Model Configuration (Iranian ultra-low cost AI provider)
$avalai_api_key = getenv('AVALAI_API_KEY') ?: 'aa-OYnaadEq49DVrgUetouRgFRhmNjSuS7ZknCL5FdEQqHAehsl';
$avalai_model = getenv('AVALAI_MODEL_CHAT') ?: 'gemini-3.5-flash-lite';
$avalai_url = 'https://api.avalai.ir/v1/chat/completions';

// GEMINI API Configuration
$gemini_api_key = getenv('GEMINI_API_KEY') ?: 'YOUR_GEMINI_API_KEY_HERE';
$gemini_url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $gemini_api_key;

$leo_system_prompt = "نام تو لئو (Leo the Lion) است. تو یک دستیار هوشمند دامپزشکی در پت‌شاپ ASENA هستی. 
فقط و فقط درباره حیوانات خانگی، مشکلات جسمی آنها، و محصولات پت‌شاپ صحبت می‌کنی. 
اگر کاربر سوالی نامربوط به حیوانات پرسید، فقط بگو: 'من لئو هستم و فقط می‌توانم درباره حیوانات خانگی به شما کمک کنم.' 
توضیحاتت باید کوتاه، دقیق و با لحنی صمیمی اما حرفه‌ای باشد. اگر تصویری ارسال شد، مشکلات جسمی یا بیماری حیوان را تشخیص بده.";

function get_smart_veterinary_fallback($userMessage) {
    $msg = mb_strtolower((string)$userMessage);
    if (str_contains($msg, 'واکسن') || str_contains($msg, 'واکسیناسیون')) {
        return "برنامه واکسیناسیون حیوانات خانگی از حدود ۲ ماهگی با واکسن‌های چندگانه و سپس هاری آغاز می‌شود. برای بررسی دقیق تاریخ واکسیناسیون و پرونده سلامت، می‌توانید از بخش «رزرو نوبت کلینیک» یک زمان معاینه با پزشک هماهنگ کنید.";
    } elseif (str_contains($msg, 'غذا') || str_contains($msg, 'تغذیه') || str_contains($msg, 'خشک') || str_contains($msg, 'کنسرو')) {
        return "تغذیه اصولی مستقیماً با شادابی و طول عمر پت شما در ارتباط است. انواع غذاهای خشک تخصصی و مکمل‌های غذایی در بخش پت‌شاپ آسنا موجود است و می‌توانید سفارش خود را ثبت فرمایید.";
    } elseif (str_contains($msg, 'نوبت') || str_contains($msg, 'ویزیت') || str_contains($msg, 'رزرو') || str_contains($msg, 'دکتر')) {
        return "جهت رزرو نوبت ویزیت یا مشاوره تخصصی، به صفحه «رزرو نوبت» در منوی اصلی مراجعه نمایید و پزشک، روز و ساعت مورد نظر خود را انتخاب کنید.";
    } elseif (str_contains($msg, 'انگل') || str_contains($msg, 'قرص') || str_contains($msg, 'کرم') || str_contains($msg, 'ضدانگل')) {
        return "داروهای ضدانگل برای سگ و گربه معمولاً هر ۳ ماه یک‌بار تکرار می‌شوند. جهت انتخاب دوز مناسب با توجه به وزن دقیق حیوان، پیشنهاد می‌شود با دامپزشک کلینیک مشورت فرمایید.";
    } elseif (str_contains($msg, 'استفراغ') || str_contains($msg, 'اسهال') || str_contains($msg, 'بی‌حال') || str_contains($msg, 'بیحال') || str_contains($msg, 'خون')) {
        return "هشدار فوری: علائمی مثل بی‌حالی شدید، استفراغ مکرر یا اسهال ممکن است نیاز به مداخله فوری پزشکی داشته باشد. لطفاً هر چه سریع‌تر به صورت حضوری به کلینیک مراجعه نمایید یا از طریق گفتگوی آنلاین با پزشک پیام بگذارید.";
    } elseif (str_contains($msg, 'سلام') || str_contains($msg, 'درود') || str_contains($msg, 'صبح') || str_contains($msg, 'عصر')) {
        return "سلام دوست عزیز! من لئو، دستیار هوشمند دامپزشکی آسنا هستم. خوشحال می‌شوم در زمینه سلامت، نگهداری، تغذیه و راهنمایی خدمات به شما و پت دوست‌داشتنی‌تان کمک کنم.";
    }
    return "سلام! من لئو دستیار تخصصی کلینیک آسنا هستم. پیام شما دریافت شد. در صورت نیاز به بررسی تخصصی یا سوالات پزشکی دقیق، می‌توانید از بخش «رزرو نوبت» یک وقت معاینه ثبت کنید یا از طریق پشتیبانی با کارشناسان ما در ارتباط باشید.";
}

if ($action === 'init') {
    $mode = $_POST['mode'] ?? 'ai'; // 'ai' or 'admin'
    
    // Find active ticket for this mode
    $stmt = $pdo->prepare("SELECT id FROM tickets WHERE user_id = ? AND mode = ? AND status = 'open' ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$user_id, $mode]);
    $ticket_id = $stmt->fetchColumn();
    
    if (!$ticket_id) {
        $stmt = $pdo->prepare("INSERT INTO tickets (user_id, mode) VALUES (?, ?)");
        $stmt->execute([$user_id, $mode]);
        $ticket_id = $pdo->lastInsertId();
        
        // Add welcome message
        if ($mode === 'ai') {
            $welcome = "سلام! من لئو هستم، دستیار هوشمند شما. چطور می‌تونم به فرشته کوچولوت کمک کنم؟";
            $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_type, message) VALUES (?, 'ai', ?)")->execute([$ticket_id, $welcome]);
        } else {
            $welcome = "درخواست شما ثبت شد. یکی از کارشناسان ما به زودی پاسخگوی شما خواهد بود.";
            $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_type, message) VALUES (?, 'admin', ?)")->execute([$ticket_id, $welcome]);
        }
    }
    
    echo json_encode(['status' => 'success', 'ticket_id' => $ticket_id]);
    exit;
}

if ($action === 'fetch') {
    $ticket_id = (int)($_POST['ticket_id'] ?? 0);
    $last_id = (int)($_POST['last_id'] ?? 0);
    
    // IDOR Protection: Verify ticket belongs to user or user is admin
    $chkStmt = $pdo->prepare("SELECT user_id FROM tickets WHERE id = ?");
    $chkStmt->execute([$ticket_id]);
    $ticket_owner = $chkStmt->fetchColumn();
    
    if (!$ticket_owner) {
        echo json_encode(['status' => 'error', 'message' => 'Ticket not found']);
        exit;
    }
    
    $isAdmin = (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'superadmin']));
    if (!$isAdmin) {
        $uStmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $uStmt->execute([$user_id]);
        $uRole = $uStmt->fetchColumn();
        if (in_array($uRole, ['admin', 'superadmin'])) {
            $isAdmin = true;
        }
    }
    
    if ($ticket_owner != $user_id && !$isAdmin) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT id, sender_type, message, image_url, created_at FROM ticket_messages WHERE ticket_id = ? AND id > ? ORDER BY id ASC");
    $stmt->execute([$ticket_id, $last_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['status' => 'success', 'messages' => $messages]);
    exit;
}

if ($action === 'send') {
    $ticket_id = $_POST['ticket_id'] ?? 0;
    $message = trim($_POST['message'] ?? '');
    
    // Verify ticket belongs to user
    $stmt = $pdo->prepare("SELECT mode FROM tickets WHERE id = ? AND user_id = ?");
    $stmt->execute([$ticket_id, $user_id]);
    $mode = $stmt->fetchColumn();
    
    if (!$mode) {
        echo json_encode(['status' => 'error', 'message' => 'Ticket not found or unauthorized']);
        exit;
    }
    
    $image_url = null;
    $base64_image = null;
    $mime_type = null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['image']['tmp_name'];
        $mime_type = mime_content_type($file_tmp);
        
        $allowed_mime_map = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
        ];
        
        if (isset($allowed_mime_map[$mime_type]) && $_FILES['image']['size'] <= 5 * 1024 * 1024) {
            $ext = $allowed_mime_map[$mime_type];
            if ($mode === 'admin') {
                // Save safely with random hash and strict verified extension
                $filename = 'ticket_' . bin2hex(random_bytes(10)) . '.' . $ext;
                $filepath = '../uploads/' . $filename;
                if (!is_dir('../uploads/')) mkdir('../uploads/', 0755, true);
                if (move_uploaded_file($file_tmp, $filepath)) {
                    $image_url = 'uploads/' . $filename;
                }
            } else if ($mode === 'ai') {
                // Convert to base64 for Gemini (Do NOT save to disk)
                $data = file_get_contents($file_tmp);
                $base64_image = base64_encode($data);
            }
        }
    }
    
    // Insert user message
    $stmt = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_type, message, image_url) VALUES (?, 'user', ?, ?)");
    $stmt->execute([$ticket_id, $message, $image_url]);
    $user_msg_id = $pdo->lastInsertId();
    
    // Trigger AI if mode is ai
    if ($mode === 'ai') {
        // Fetch last 10 messages for context
        $stmt = $pdo->prepare("SELECT sender_type, message FROM ticket_messages WHERE ticket_id = ? ORDER BY id DESC LIMIT 10");
        $stmt->execute([$ticket_id]);
        $history = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
        
        $contents = [];
        foreach ($history as $msg) {
            if ($msg['sender_type'] === 'user') {
                $parts = [];
                $parts[] = ["text" => $msg['message'] ?: ''];
                
                // If this is the current message and has an image, attach it
                if ($msg['message'] == $message && $base64_image) {
                    $parts[] = [
                        "inline_data" => [
                            "mime_type" => $mime_type,
                            "data" => $base64_image
                        ]
                    ];
                }
                $contents[] = ["role" => "user", "parts" => $parts];
            } else if ($msg['sender_type'] === 'ai') {
                $contents[] = ["role" => "model", "parts" => [["text" => $msg['message']]]];
            }
        }
        
        $payload = [
            "systemInstruction" => [
                "role" => "system",
                "parts" => [["text" => $leo_system_prompt]]
            ],
            "contents" => $contents,
            "generationConfig" => [
                "temperature" => 0.4,
                "maxOutputTokens" => 400
            ]
        ];
        
        $ai_reply = null;

        // 1. Primary Engine: AvalAI Multi-Model API (Ultra-low cost, fast Persian processing)
        if (!empty($avalai_api_key)) {
            $avalaiMessages = [
                ['role' => 'system', 'content' => $leo_system_prompt]
            ];
            foreach ($history as $msg) {
                if ($msg['sender_type'] === 'user') {
                    if ($msg['message'] == $message && !empty($base64_image)) {
                        $avalaiMessages[] = [
                            'role' => 'user',
                            'content' => [
                                ['type' => 'text', 'text' => $msg['message'] ?: 'این تصویر از حیوان خانگی من است:'],
                                ['type' => 'image_url', 'image_url' => ['url' => "data:{$mime_type};base64,{$base64_image}"]]
                            ]
                        ];
                    } else {
                        $avalaiMessages[] = ['role' => 'user', 'content' => (string)($msg['message'] ?: '')];
                    }
                } else if ($msg['sender_type'] === 'ai') {
                    $avalaiMessages[] = ['role' => 'assistant', 'content' => (string)($msg['message'] ?: '')];
                }
            }

            $ch = curl_init($avalai_url);
            $avPayload = [
                'model' => $avalai_model,
                'messages' => $avalaiMessages,
                'max_tokens' => 250,
                'temperature' => 0.4
            ];
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode($avPayload),
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $avalai_api_key
                ],
                CURLOPT_TIMEOUT        => 20,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $avResp = curl_exec($ch);
            $avErr = curl_error($ch);
            curl_close($ch);

            if (!$avErr && $avResp) {
                $avData = json_decode($avResp, true);
                if (isset($avData['choices'][0]['message']['content'])) {
                    $content = trim($avData['choices'][0]['message']['content']);
                    if (!empty($content)) {
                        $ai_reply = $content;
                    }
                }
            }
        }

        // 2. Secondary Engine: Gemini API
        if (empty($ai_reply) && !empty($gemini_api_key) && $gemini_api_key !== 'YOUR_GEMINI_API_KEY_HERE') {
            $ch = curl_init($gemini_url);
            $curlOptions = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode($payload),
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT        => 12,
                CURLOPT_SSL_VERIFYPEER => false,
            ];
            
            $proxy = getenv('GEMINI_PROXY') ?: getenv('HTTPS_PROXY');
            if (!empty($proxy)) {
                $curlOptions[CURLOPT_PROXY] = $proxy;
            }
            
            curl_setopt_array($ch, $curlOptions);
            $response = curl_exec($ch);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if (!$curlError && $response) {
                $responseData = json_decode($response, true);
                if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
                    $ai_reply = trim($responseData['candidates'][0]['content']['parts'][0]['text']);
                }
            }
        }
        
        // Smart veterinary triage fallback if AI response was not obtained
        if (empty($ai_reply)) {
            $ai_reply = get_smart_veterinary_fallback($message);
        }
        
        $stmt = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_type, message) VALUES (?, 'ai', ?)");
        $stmt->execute([$ticket_id, $ai_reply]);
    }
    
    echo json_encode(['status' => 'success']);
    exit;
}

if ($action === 'admin_send') {
    $ticket_id = $_POST['ticket_id'] ?? 0;
    $message = trim($_POST['message'] ?? '');
    
    // Verify admin
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    if ($stmt->fetchColumn() !== 'admin') {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }
    
    $stmt = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_type, message) VALUES (?, 'admin', ?)");
    if ($stmt->execute([$ticket_id, $message])) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
    exit;
}

if ($action === 'reopen') {
    $ticket_id = $_POST['ticket_id'] ?? 0;
    
    // Verify user
    $stmt = $pdo->prepare("SELECT user_id FROM tickets WHERE id = ?");
    $stmt->execute([$ticket_id]);
    if ($stmt->fetchColumn() == $user_id) {
        $stmt = $pdo->prepare("UPDATE tickets SET status = 'open' WHERE id = ?");
        $stmt->execute([$ticket_id]);
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    }
    exit;
}
?>
