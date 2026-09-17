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

// Dynamic Telehealth Schema Alignment (Self-healing for MySQL 8.0 & MariaDB)
if (!function_exists('ensure_chat_telehealth_schema')) {
    function ensure_chat_telehealth_schema(PDO $pdo): void {
        static $checked = false;
        if ($checked) return;
        $checked = true;

        try {
            $cols = $pdo->query("SHOW COLUMNS FROM `tickets`")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('doctor_id', $cols)) {
                $pdo->exec("ALTER TABLE `tickets` ADD `doctor_id` INT(11) NULL");
            }
            if (!in_array('closed_by', $cols)) {
                $pdo->exec("ALTER TABLE `tickets` ADD `closed_by` INT(11) NULL");
            }
            if (!in_array('resolution_notes', $cols)) {
                $pdo->exec("ALTER TABLE `tickets` ADD `resolution_notes` TEXT NULL");
            }
            if (!in_array('last_notified_at', $cols)) {
                $pdo->exec("ALTER TABLE `tickets` ADD `last_notified_at` DATETIME NULL");
            }
            if (!in_array('last_user_notified_at', $cols)) {
                $pdo->exec("ALTER TABLE `tickets` ADD `last_user_notified_at` DATETIME NULL");
            }

            try {
                $pdo->exec("ALTER TABLE `ticket_messages` MODIFY COLUMN `sender_type` ENUM('user', 'ai', 'admin', 'doctor', 'organization') NOT NULL");
            } catch (Throwable $e) {}

            try {
                $rxCols = $pdo->query("SHOW COLUMNS FROM `prescriptions`")->fetchAll(PDO::FETCH_COLUMN);
                if (!in_array('tracking_code', $rxCols)) {
                    $pdo->exec("ALTER TABLE `prescriptions` ADD `tracking_code` VARCHAR(50) NULL");
                }
            } catch (Throwable $e) {}
        } catch (Throwable $ignore) {
            error_log("Schema auto-alignment note: " . $ignore->getMessage());
        }
    }
}
ensure_chat_telehealth_schema($pdo);

// AvalAI Multi-Model Configuration (Iranian ultra-low cost AI provider)
$avalai_api_key = getenv('AVALAI_API_KEY') ?: 'aa-OYnaadEq49DVrgUetouRgFRhmNjSuS7ZknCL5FdEQqHAehsl';
$avalai_model = getenv('AVALAI_MODEL_CHAT') ?: 'gemini-3.5-flash-lite';
$avalai_url = 'https://api.avalai.ir/v1/chat/completions';

// GEMINI API Configuration
$gemini_api_key = getenv('GEMINI_API_KEY') ?: 'YOUR_GEMINI_API_KEY_HERE';
$gemini_url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $gemini_api_key;


$leo_system_prompt = "تو «لئو» (Leo)، دستیار هوشمند، مهربان و متخصص دامپزشکی و نگهداری از حیوانات خانگی در پلتفرم آسنا (ASENA) هستی.
اصول مکالمه و همراهی با کاربر:
۱. همواره لحنی گرم، دوستانه، همدلانه و پیوسته داشته باش تا کاربر بتواند آزادانه و پیوسته با تو گفتگو کند.
۲. در احوالپرسی‌ها، سلام، تشکر و گفتگوهای صمیمانه (فارسی یا انگلیسی مثل Hi, Hello, How are you)، بسیار خوش‌رو باش، سلام کن، حال کاربر و پت او را بپرس و بگو امروز چطور می‌توانی به او و حیوان خانگی‌اش کمک کنی.
۳. تخصص تو پاسخ به سوالات سلامت، تغذیه، واکسن، دوز دارو، رفتارشناسی و محصولات پت‌شاپ آسنا برای انواع حیوانات خانگی (سگ، گربه، پرندگان، جوندگان و...) است.
۴. زبان پاسخگویی تو متناسب با زبان کاربر (فارسی یا انگلیسی) باشد.
۵. پاسخ‌ها را شمرده، مختصر، کاربردی و با پاراگراف‌بندی خوانا بنویس و در پایان در صورت نیاز یک سوال پیگیرانه کوتاه درباره وضعیت پت بپرس تا رشته گفتگو قطع نشود.
۶. در موارد اورژانسی (مسمومیت، تشنج، خونریزی شدید، تنگی نفس) کاربر را به مراجعه حضوری فوری به نزدیک‌ترین کلینیک یا رزرو نوبت اورژانس راهنمایی کن.";

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
    } elseif (str_contains($msg, 'سلام') || str_contains($msg, 'درود') || str_contains($msg, 'صبح') || str_contains($msg, 'عصر') || str_contains($msg, 'hi') || str_contains($msg, 'hello') || str_contains($msg, 'how are you') || str_contains($msg, 'how re you') || str_contains($msg, 'hey')) {
        return "سلام دوست عزیز! من لئو، دستیار تخصصی دامپزشکی و سلامت پت شما در آسنا هستم. حال پت دوست‌داشتنی‌تون چطوره و امروز چطور می‌تونم کمکتون کنم؟";
    }
    return "سلام! من لئو دستیار تخصصی کلینیک آسنا هستم. پیام شما دریافت شد. در صورت نیاز به بررسی تخصصی یا سوالات پزشکی دقیق، می‌توانید از بخش «رزرو نوبت» یک وقت معاینه ثبت کنید یا از طریق پشتیبانی با کارشناسان ما در ارتباط باشید.";
}

function can_user_access_ticket(PDO $pdo, int $userId, array $ticket): bool {
    // Ticket creator / owner always has access to their own ticket
    if ((int)$ticket['user_id'] === $userId) {
        return true;
    }

    $mode = $ticket['mode'] ?? 'admin';

    // 1. Organization Tickets: ONLY the assigned organization staff has access (NOT platform admin, NOT other orgs)
    if ($mode === 'organization' && !empty($ticket['organization_id'])) {
        $orgId = (int)$ticket['organization_id'];
        // Check if user owns the organization
        $ownerStmt = $pdo->prepare("SELECT id FROM organizations WHERE id = ? AND user_id = ?");
        $ownerStmt->execute([$orgId, $userId]);
        if ($ownerStmt->fetchColumn()) {
            return true;
        }
        // Check if user is an active sub-admin in organization_admins
        try {
            $subStmt = $pdo->prepare("SELECT id FROM organization_admins WHERE organization_id = ? AND user_id = ? AND status = 'active'");
            $subStmt->execute([$orgId, $userId]);
            if ($subStmt->fetchColumn()) {
                return true;
            }
        } catch (Throwable $e) {}
        return false;
    }

    // 2. Admin Tickets: Platform super-admin has access
    if ($mode === 'admin') {
        $isAdmin = (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'superadmin']));
        if (!$isAdmin) {
            $uStmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
            $uStmt->execute([$userId]);
            $uRole = $uStmt->fetchColumn();
            if (in_array($uRole, ['admin', 'superadmin'])) {
                $isAdmin = true;
            }
        }
        return $isAdmin;
    }

    // 3. Doctor Telehealth Tickets: The assigned doctor or platform admin has access
    if ($mode === 'doctor' && !empty($ticket['doctor_id'])) {
        $docId = (int)$ticket['doctor_id'];
        $dStmt = $pdo->prepare("SELECT id FROM doctors WHERE id = ? AND user_id = ?");
        $dStmt->execute([$docId, $userId]);
        if ($dStmt->fetchColumn()) {
            return true;
        }
        $uStmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $uStmt->execute([$userId]);
        if (in_array($uStmt->fetchColumn(), ['admin', 'superadmin'])) {
            return true;
        }
        return false;
    }

    return false;
}

if ($action === 'init') {
    $mode = $_POST['mode'] ?? 'ai'; // 'ai', 'admin', 'organization', or 'doctor'
    $org_id = !empty($_POST['organization_id']) ? (int)$_POST['organization_id'] : null;
    $doc_id = !empty($_POST['doctor_id']) ? (int)$_POST['doctor_id'] : null;

    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
              || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
              || (isset($_POST['is_ajax']) && $_POST['is_ajax'] == 1);

    if ($mode === 'doctor') {
        if (!$doc_id) {
            echo json_encode(['status' => 'error', 'message' => 'شناسه پزشک مشخص نشده است.']);
            exit;
        }

        // CLINICAL RULE: Gatekeeper - Only patients with appointment in the last 7 days can message doctors!
        $apptStmt = $pdo->prepare("
            SELECT id, appointment_date, status 
            FROM appointments 
            WHERE user_id = ? 
              AND doctor_id = ? 
              AND appointment_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
              AND status NOT IN ('cancelled')
            ORDER BY appointment_date DESC 
            LIMIT 1
        ");
        $apptStmt->execute([$user_id, $doc_id]);
        $validAppointment = $apptStmt->fetch(PDO::FETCH_ASSOC);

        if (!$validAppointment) {
            $gatekeeperMsg = 'مشاوره آنلاین تله‌هلث اختصاصی مراجعینی است که طی ۷ روز گذشته ویزیت شده‌اند یا نوبت فعال دارند. جهت آغاز گفتگو، لطفاً ابتدا نوبت خود را رزرو فرمایید.';
            if ($isAjax) {
                echo json_encode([
                    'status' => 'error',
                    'code' => 'VISIT_REQUIRED',
                    'message' => $gatekeeperMsg,
                    'booking_url' => 'booking.php?doctor_id=' . $doc_id
                ]);
            } else {
                $_SESSION['flash_error'] = $gatekeeperMsg;
                header("Location: ../booking.php?doctor_id=" . $doc_id);
            }
            exit;
        }

        // Find active open ticket with this doctor
        $stmt = $pdo->prepare("SELECT id FROM tickets WHERE user_id = ? AND mode = 'doctor' AND doctor_id = ? AND status = 'open' ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$user_id, $doc_id]);
        $ticket_id = $stmt->fetchColumn();

        if (!$ticket_id) {
            $docNameStmt = $pdo->prepare("SELECT name FROM doctors WHERE id = ?");
            $docNameStmt->execute([$doc_id]);
            $docName = $docNameStmt->fetchColumn() ?: 'پزشک گرامی';

            $stmt = $pdo->prepare("INSERT INTO tickets (user_id, mode, doctor_id, subject, status, created_at, updated_at) VALUES (?, 'doctor', ?, ?, 'open', NOW(), NOW())");
            $stmt->execute([$user_id, $doc_id, "مشاوره آنلاین با " . $docName]);
            $ticket_id = $pdo->lastInsertId();

            $welcome = "سلام و درود. من {$docName} هستم. نشست مشاوره آنلاین جهت پیگیری روند بهبودی پت شما فعال گردید. لطفاً شرح حال دقیق و در صورت نیاز مدارک یا تصاویر را ارسال فرمایید.";
            $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_type, message, created_at) VALUES (?, 'doctor', ?, NOW())")->execute([$ticket_id, $welcome]);
        }
    } elseif ($mode === 'organization' && $org_id) {
        // Find active open ticket for this user and this specific organization
        $stmt = $pdo->prepare("SELECT id FROM tickets WHERE user_id = ? AND mode = 'organization' AND organization_id = ? AND status = 'open' ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$user_id, $org_id]);
        $ticket_id = $stmt->fetchColumn();

        if (!$ticket_id) {
            $orgNameStmt = $pdo->prepare("SELECT name FROM organizations WHERE id = ?");
            $orgNameStmt->execute([$org_id]);
            $orgName = $orgNameStmt->fetchColumn() ?: 'مرکز درمانی';

            $stmt = $pdo->prepare("INSERT INTO tickets (user_id, mode, organization_id, subject, status, created_at, updated_at) VALUES (?, 'organization', ?, ?, 'open', NOW(), NOW())");
            $stmt->execute([$user_id, $org_id, "پیام به " . $orgName]);
            $ticket_id = $pdo->lastInsertId();

            $welcome = "سلام و درود از طرف کلینیک «{$orgName}». پیام و سوال شما دریافت شد و کادر پذیرش و مدیریت به زودی پاسخگوی شما خواهند بود.";
            $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_type, message, created_at) VALUES (?, 'admin', ?, NOW())")->execute([$ticket_id, $welcome]);
        }
    } else {
        // Find active ticket for this mode
        $stmt = $pdo->prepare("SELECT id FROM tickets WHERE user_id = ? AND mode = ? AND status = 'open' ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$user_id, $mode]);
        $ticket_id = $stmt->fetchColumn();

        if (!$ticket_id) {
            $stmt = $pdo->prepare("INSERT INTO tickets (user_id, mode, status, created_at, updated_at) VALUES (?, ?, 'open', NOW(), NOW())");
            $stmt->execute([$user_id, $mode]);
            $ticket_id = $pdo->lastInsertId();

            // Add welcome message
            if ($mode === 'ai') {
                $welcome = "سلام! من لئو هستم، دستیار هوشمند شما. چطور می‌تونم به فرشته کوچولوت کمک کنم؟";
                $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_type, message, created_at) VALUES (?, 'ai', ?, NOW())")->execute([$ticket_id, $welcome]);
            } else {
                $welcome = "درخواست شما با مدیریت آسنا ثبت شد. یکی از کارشناسان ما به زودی پاسخگوی شما خواهد بود.";
                $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_type, message, created_at) VALUES (?, 'admin', ?, NOW())")->execute([$ticket_id, $welcome]);
            }
        }
    }

    if ($isAjax) {
        echo json_encode(['status' => 'success', 'ticket_id' => $ticket_id]);
    } else {
        header("Location: ../chat.php?ticket_id=" . (int)$ticket_id);
    }
    exit;
}

if ($action === 'fetch') {
    $ticket_id = (int)($_POST['ticket_id'] ?? 0);
    $last_id = (int)($_POST['last_id'] ?? 0);

    // Multi-tenant IDOR Protection: Fetch ticket row
    $chkStmt = $pdo->prepare("SELECT * FROM tickets WHERE id = ?");
    $chkStmt->execute([$ticket_id]);
    $ticketRow = $chkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticketRow) {
        echo json_encode(['status' => 'error', 'message' => 'Ticket not found']);
        exit;
    }

    if (!can_user_access_ticket($pdo, (int)$user_id, $ticketRow)) {
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
    try {
        $ticket_id = (int)($_POST['ticket_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');

        // Verify ticket belongs to user (resilient SELECT *)
        $stmt = $pdo->prepare("SELECT * FROM tickets WHERE id = ? AND user_id = ?");
        $stmt->execute([$ticket_id, $user_id]);
        $ticketRow = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ticketRow) {
            echo json_encode(['status' => 'error', 'message' => 'Ticket not found or unauthorized']);
            exit;
        }

        // Gatekeeper: if doctor has closed or resolved the session, prevent further patient messaging
        if (!empty($ticketRow['status']) && $ticketRow['status'] !== 'open') {
            echo json_encode(['status' => 'error', 'message' => 'این جلسه مشاوره توسط پزشک خاتمه یافته است. برای شروع مشاوره جدید نیاز به ثبت نوبت جدید دارید.']);
            exit;
        }

    $mode = $ticketRow['mode'];
    
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
            if ($mode === 'admin' || $mode === 'organization' || $mode === 'doctor') {
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
        $ai_msg_id = (int)$pdo->lastInsertId();
    }
    
    // Update ticket timestamp & keep open
    $pdo->prepare("UPDATE tickets SET updated_at = NOW(), status = 'open' WHERE id = ?")->execute([$ticket_id]);

    // Anti-Spam Doctor Notification: Send SMS alert to doctor upon patient message (throttled to once per 15 min)
    $doctorSmsEnabled = get_setting($pdo, 'doctor_sms_on_telehealth', '1');
    if ($doctorSmsEnabled === '1' && $mode === 'doctor' && !empty($ticketRow['doctor_id'])) {
        $docId = (int)$ticketRow['doctor_id'];
        $docStmt = $pdo->prepare("
            SELECT d.name, d.phone, u.phone AS user_phone, u.name AS doc_user_name 
            FROM doctors d 
            LEFT JOIN users u ON d.user_id = u.id 
            WHERE d.id = ?
        ");
        $docStmt->execute([$docId]);
        $docInfo = $docStmt->fetch(PDO::FETCH_ASSOC);
        
        $targetPhone = !empty($docInfo['phone']) ? $docInfo['phone'] : ($docInfo['user_phone'] ?? '');
        
        // Check cooldown from tickets.last_notified_at
        $notifStmt = $pdo->prepare("SELECT last_notified_at FROM tickets WHERE id = ?");
        $notifStmt->execute([$ticket_id]);
        $lastNotified = $notifStmt->fetchColumn();
        
        $cooldownPassed = empty($lastNotified) || (strtotime($lastNotified) < (time() - 900));
        
        if ($cooldownPassed && !empty($targetPhone)) {
            require_once __DIR__ . '/../includes/SmsService.php';
            $sms = new SmsService();
            $patientName = $_SESSION['name'] ?? ($_SESSION['user_name'] ?? 'بیمار محترم');
            $docName = !empty($docInfo['name']) ? $docInfo['name'] : ($docInfo['doc_user_name'] ?? 'پزشک گرامی');
            $sms->sendDoctorTelehealthAlert($targetPhone, $docName, $patientName, $ticket_id);
            $pdo->prepare("UPDATE tickets SET last_notified_at = NOW() WHERE id = ?")->execute([$ticket_id]);
        }
    }

    $outMessages = [
        [
            'id' => (int)$user_msg_id,
            'sender_type' => 'user',
            'message' => $message,
            'image_url' => $image_url,
            'created_at' => date('Y-m-d H:i:s')
        ]
    ];
    if ($mode === 'ai' && !empty($ai_reply)) {
        $outMessages[] = [
            'id' => (int)($ai_msg_id ?? ($user_msg_id + 1)),
            'sender_type' => 'ai',
            'message' => $ai_reply,
            'image_url' => null,
            'created_at' => date('Y-m-d H:i:s')
        ];
    }

        echo json_encode([
            'status' => 'success',
            'message_id' => $user_msg_id,
            'mode' => $mode,
            'messages' => $outMessages
        ]);
        exit;
    } catch (Throwable $e) {
        error_log("Chat send error: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
        echo json_encode([
            'status' => 'error',
            'message' => 'خطا در ثبت و پردازش پیام: ' . $e->getMessage()
        ]);
        exit;
    }
}


if ($action === 'org_send') {
    $ticket_id = (int)($_POST['ticket_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');

    if (empty($message)) {
        echo json_encode(['status' => 'error', 'message' => 'پیام نمی‌تواند خالی باشد']);
        exit;
    }

    // Verify user is authorized manager/staff of this ticket's organization
    $chkStmt = $pdo->prepare("SELECT * FROM tickets WHERE id = ?");
    $chkStmt->execute([$ticket_id]);
    $ticketRow = $chkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticketRow || !can_user_access_ticket($pdo, (int)$user_id, $ticketRow)) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_type, message, created_at) VALUES (?, 'admin', ?, NOW())");
    if ($stmt->execute([$ticket_id, $message])) {
        $pdo->prepare("UPDATE tickets SET updated_at = NOW() WHERE id = ?")->execute([$ticket_id]);

        // Anti-Spam User Notification: Send SMS alert to user upon clinic/org response (throttled to once per 15 min)
        try {
            $userSmsEnabled = get_setting($pdo, 'user_sms_on_chat', '1');
            if ($userSmsEnabled === '1') {
                $uStmt = $pdo->prepare("
                    SELECT u.name, u.phone, t.last_user_notified_at, o.name AS org_name
                    FROM tickets t
                    JOIN users u ON t.user_id = u.id
                    LEFT JOIN organizations o ON t.organization_id = o.id
                    WHERE t.id = ?
                ");
                $uStmt->execute([$ticket_id]);
                $uInfo = $uStmt->fetch(PDO::FETCH_ASSOC);

                if (!empty($uInfo['phone'])) {
                    $lastUserNotified = $uInfo['last_user_notified_at'] ?? null;
                    $cooldownPassed = empty($lastUserNotified) || (strtotime($lastUserNotified) < (time() - 900));

                    if ($cooldownPassed) {
                        require_once __DIR__ . '/../includes/SmsService.php';
                        $sms = new SmsService();
                        $orgSender = !empty($uInfo['org_name']) ? ('کلینیک ' . $uInfo['org_name']) : 'مرکز درمانی';
                        $chatUrl = "https://asena.company/chat.php?ticket_id=" . (int)$ticket_id;
                        $sms->sendUserChatMessageAlert($uInfo['phone'], $uInfo['name'], $orgSender, $chatUrl);
                        $pdo->prepare("UPDATE tickets SET last_user_notified_at = NOW() WHERE id = ?")->execute([$ticket_id]);
                    }
                }
            }
        } catch (Throwable $eNotif) {
            error_log("Org send SMS user notif note: " . $eNotif->getMessage());
        }

        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save message']);
    }
    exit;
}

if ($action === 'admin_send') {
    $ticket_id = (int)($_POST['ticket_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');
    
    // Verify admin
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    if (!in_array($stmt->fetchColumn(), ['admin', 'superadmin'])) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }
    
    // Admin only sends to mode = 'admin' tickets (never private clinic-user tickets)
    $chkStmt = $pdo->prepare("SELECT mode FROM tickets WHERE id = ?");
    $chkStmt->execute([$ticket_id]);
    $targetMode = $chkStmt->fetchColumn();
    if ($targetMode !== 'admin') {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized: Admin cannot send to private clinic tickets']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_type, message, created_at) VALUES (?, 'admin', ?, NOW())");
    if ($stmt->execute([$ticket_id, $message])) {
        $pdo->prepare("UPDATE tickets SET updated_at = NOW() WHERE id = ?")->execute([$ticket_id]);

        // Anti-Spam User Notification: Send SMS alert to user upon admin response (throttled to once per 15 min)
        try {
            $userSmsEnabled = get_setting($pdo, 'user_sms_on_chat', '1');
            if ($userSmsEnabled === '1') {
                $uStmt = $pdo->prepare("
                    SELECT u.name, u.phone, t.last_user_notified_at
                    FROM tickets t
                    JOIN users u ON t.user_id = u.id
                    WHERE t.id = ?
                ");
                $uStmt->execute([$ticket_id]);
                $uInfo = $uStmt->fetch(PDO::FETCH_ASSOC);

                if (!empty($uInfo['phone'])) {
                    $lastUserNotified = $uInfo['last_user_notified_at'] ?? null;
                    $cooldownPassed = empty($lastUserNotified) || (strtotime($lastUserNotified) < (time() - 900));

                    if ($cooldownPassed) {
                        require_once __DIR__ . '/../includes/SmsService.php';
                        $sms = new SmsService();
                        $chatUrl = "https://asena.company/chat.php?ticket_id=" . (int)$ticket_id;
                        $sms->sendUserChatMessageAlert($uInfo['phone'], $uInfo['name'], 'پشتیبانی مدیریت آسنا', $chatUrl);
                        $pdo->prepare("UPDATE tickets SET last_user_notified_at = NOW() WHERE id = ?")->execute([$ticket_id]);
                    }
                }
            }
        } catch (Throwable $eNotif) {
            error_log("Admin send SMS user notif note: " . $eNotif->getMessage());
        }

        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
    exit;
}

if ($action === 'doctor_send') {
    $ticket_id = (int)($_POST['ticket_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');

    // Verify current user is the doctor of this ticket or an admin
    $chk = $pdo->prepare("
        SELECT t.id, t.doctor_id, t.status 
        FROM tickets t 
        JOIN doctors d ON t.doctor_id = d.id 
        WHERE t.id = ? AND d.user_id = ?
    ");
    $chk->execute([$ticket_id, $user_id]);
    $tRow = $chk->fetch(PDO::FETCH_ASSOC);

    if (!$tRow) {
        $uRole = $pdo->query("SELECT role FROM users WHERE id = {$user_id}")->fetchColumn();
        if (!in_array($uRole, ['admin', 'superadmin'])) {
            echo json_encode(['status' => 'error', 'message' => 'دسترسی غیرمجاز: تنها پزشک مربوطه امکان ارسال پیام در این گفتگو را دارد.']);
            exit;
        }
    }

    $image_url = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['image']['tmp_name'];
        $mime_type = mime_content_type($file_tmp);
        $allowed_mime_map = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        ];
        if (isset($allowed_mime_map[$mime_type]) && $_FILES['image']['size'] <= 5 * 1024 * 1024) {
            $ext = $allowed_mime_map[$mime_type];
            $filename = 'rx_advice_' . bin2hex(random_bytes(10)) . '.' . $ext;
            $filepath = '../uploads/' . $filename;
            if (!is_dir('../uploads/')) mkdir('../uploads/', 0755, true);
            if (move_uploaded_file($file_tmp, $filepath)) {
                $image_url = 'uploads/' . $filename;
            }
        }
    }

    if (empty($message) && empty($image_url)) {
        echo json_encode(['status' => 'error', 'message' => 'متن پیام یا تصویر ضمیمه نمی‌تواند خالی باشد.']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_type, message, image_url, created_at) VALUES (?, 'doctor', ?, ?, NOW())");
    if ($stmt->execute([$ticket_id, $message, $image_url])) {
        $pdo->prepare("UPDATE tickets SET updated_at = NOW(), status = 'open' WHERE id = ?")->execute([$ticket_id]);

        // Anti-Spam User Notification: Send SMS alert to patient upon doctor response (throttled to once per 15 min)
        try {
            $userSmsEnabled = get_setting($pdo, 'user_sms_on_chat', '1');
            if ($userSmsEnabled === '1') {
                $uStmt = $pdo->prepare("
                    SELECT u.name AS patient_name, u.phone AS patient_phone, t.last_user_notified_at, d.name AS doc_name
                    FROM tickets t
                    JOIN users u ON t.user_id = u.id
                    LEFT JOIN doctors d ON t.doctor_id = d.id
                    WHERE t.id = ?
                ");
                $uStmt->execute([$ticket_id]);
                $uInfo = $uStmt->fetch(PDO::FETCH_ASSOC);

                if (!empty($uInfo['patient_phone'])) {
                    $lastUserNotified = $uInfo['last_user_notified_at'] ?? null;
                    $cooldownPassed = empty($lastUserNotified) || (strtotime($lastUserNotified) < (time() - 900));

                    if ($cooldownPassed) {
                        require_once __DIR__ . '/../includes/SmsService.php';
                        $sms = new SmsService();
                        $docSender = !empty($uInfo['doc_name']) ? ('دکتر ' . $uInfo['doc_name']) : 'پزشک معالج شما';
                        $chatUrl = "https://asena.company/chat.php?ticket_id=" . (int)$ticket_id;
                        $sms->sendUserChatMessageAlert($uInfo['patient_phone'], $uInfo['patient_name'], $docSender, $chatUrl);
                        $pdo->prepare("UPDATE tickets SET last_user_notified_at = NOW() WHERE id = ?")->execute([$ticket_id]);
                    }
                }
            }
        } catch (Throwable $eNotif) {
            error_log("Doctor send SMS user notif note: " . $eNotif->getMessage());
        }

        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'خطا در ثبت پیام']);
    }
    exit;
}

if ($action === 'doctor_end_chat') {
    $ticket_id = (int)($_POST['ticket_id'] ?? 0);
    $notes = trim($_POST['resolution_notes'] ?? '');

    // Verify current user is the doctor of this ticket or admin
    $chk = $pdo->prepare("
        SELECT t.id, t.doctor_id 
        FROM tickets t 
        JOIN doctors d ON t.doctor_id = d.id 
        WHERE t.id = ? AND d.user_id = ?
    ");
    $chk->execute([$ticket_id, $user_id]);
    if (!$chk->fetchColumn()) {
        $uRole = $pdo->query("SELECT role FROM users WHERE id = {$user_id}")->fetchColumn();
        if (!in_array($uRole, ['admin', 'superadmin'])) {
            echo json_encode(['status' => 'error', 'message' => 'دسترسی غیرمجاز']);
            exit;
        }
    }

    $pdo->prepare("
        UPDATE tickets 
        SET status = 'resolved', closed_by = ?, resolution_notes = ?, updated_at = NOW() 
        WHERE id = ?
    ")->execute([$user_id, $notes, $ticket_id]);

    $summaryMsg = "🩺 پایان مشاوره بالینی توسط پزشک.";
    if (!empty($notes)) {
        $summaryMsg .= "\n\n📋 توصیه‌ها و دستورات پزشک:\n" . $notes;
    }
    $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_type, message, created_at) VALUES (?, 'doctor', ?, NOW())")->execute([$ticket_id, $summaryMsg]);

    echo json_encode(['status' => 'success', 'message' => 'جلسه مشاوره بالینی با موفقیت خاتمه یافت.']);
    exit;
}

if ($action === 'fetch_doctor_chats') {
    $dStmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
    $dStmt->execute([$user_id]);
    $docId = (int)$dStmt->fetchColumn();

    if (!$docId) {
        $uRole = $pdo->query("SELECT role FROM users WHERE id = {$user_id}")->fetchColumn();
        if (!in_array($uRole, ['admin', 'superadmin'])) {
            echo json_encode(['status' => 'error', 'message' => 'پروفایل پزشک برای این کاربر یافت نشد.']);
            exit;
        }
        $docId = (int)($pdo->query("SELECT id FROM doctors ORDER BY id ASC LIMIT 1")->fetchColumn() ?: 0);
    }

    $stmt = $pdo->prepare("
        SELECT t.id, t.user_id, t.status, t.created_at, t.updated_at, t.resolution_notes,
               u.name AS patient_name, u.phone AS patient_phone,
               (SELECT message FROM ticket_messages WHERE ticket_id = t.id ORDER BY id DESC LIMIT 1) AS last_message,
               (SELECT sender_type FROM ticket_messages WHERE ticket_id = t.id ORDER BY id DESC LIMIT 1) AS last_sender,
               (SELECT created_at FROM ticket_messages WHERE ticket_id = t.id ORDER BY id DESC LIMIT 1) AS last_message_at
        FROM tickets t
        JOIN users u ON t.user_id = u.id
        WHERE t.doctor_id = ? AND t.mode = 'doctor'
        ORDER BY t.updated_at DESC
    ");
    $stmt->execute([$docId]);
    $chats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'chats' => $chats]);
    exit;
}

if ($action === 'reopen') {
    $ticket_id = (int)($_POST['ticket_id'] ?? 0);
    
    // Verify user
    $stmt = $pdo->prepare("SELECT user_id, mode, doctor_id, status FROM tickets WHERE id = ?");
    $stmt->execute([$ticket_id]);
    $t = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($t && (int)$t['user_id'] === $user_id) {
        // CLINICAL RULE: Doctors end chat permanently; patient cannot reopen doctor chat without new visit
        if ($t['mode'] === 'doctor') {
            echo json_encode(['status' => 'error', 'message' => 'این جلسه مشاوره توسط پزشک پایان یافته است. جهت مشاوره جدید، لطفاً نوبت جدید ثبت فرمایید.']);
            exit;
        }
        $stmt = $pdo->prepare("UPDATE tickets SET status = 'open', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$ticket_id]);
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    }
    exit;
}
?>

