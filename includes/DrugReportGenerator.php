<?php
/**
 * ASENA Enterprise - Veterinary Clinical Pharmacology Report Generator
 * Generates standalone, responsive, print-optimized HTML reports for pet drug interactions.
 */

declare(strict_types=1);

class DrugReportGenerator
{
    /**
     * Generate complete standalone HTML document for a pet drug interaction report.
     *
     * @param array $data
     * @return string Standalone HTML
     */
    public static function generate(array $data): string
    {
        $serial = htmlspecialchars($data['serial'] ?? 'ASENA-INT-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8)));
        $petName = htmlspecialchars($data['pet_name'] ?? 'حیوان خانگی من');
        $species = strtolower((string)($data['species'] ?? 'dog'));
        $speciesTaxonomy = match ($species) {
            'cat' => 'گربه (Feline / Felis catus)',
            'horse' => 'اسب (Equine / Equus caballus)',
            'bird' => 'پرنده (Avian)',
            'exotic' => 'اگزوتیک و جوندگان (Exotic / Rodentia)',
            default => 'سگ (Canine / Canis lupus familiaris)'
        };
        $speciesFa = match ($species) {
            'cat' => 'گربه',
            'horse' => 'اسب',
            'bird' => 'پرنده',
            'exotic' => 'اگزوتیک',
            default => 'سگ'
        };
        $race = htmlspecialchars($data['race'] ?? 'مشخص نشده');
        $weightKg = (float)($data['weight_kg'] ?? 8.5);
        $ageStage = (string)($data['age_stage'] ?? 'adult');
        $ageStageFa = match ($ageStage) {
            'puppy', 'kitten' => 'توله / کیتن (در حال رشد)',
            'senior' => 'مسن و ارشد (نیازمند پایش کلیوی-کبدی)',
            default => 'بالغ'
        };

        $overallSafety = strtolower((string)($data['overall_safety'] ?? 'safe'));
        $overallSummary = trim((string)($data['overall_summary'] ?? ''));
        $createdAt = $data['created_at'] ?? date('Y/m/d - H:i');

        $drugs = (array)($data['drugs'] ?? []);
        $interactions = (array)($data['interactions'] ?? []);
        $contraindications = (array)($data['contraindications'] ?? []);
        $safeCombinations = (array)($data['safe_combinations'] ?? []);
        $timeSpacingSchedule = (array)($data['time_spacing_schedule'] ?? []);
        $vetRecommendations = (array)($data['vet_recommendations'] ?? []);

        // Normalize drug list
        $cleanDrugs = [];
        foreach ($drugs as $d) {
            if (is_array($d)) {
                $cleanDrugs[] = [
                    'name' => trim((string)($d['name'] ?? '')),
                    'dose' => trim((string)($d['dose'] ?? '')),
                    'frequency' => trim((string)($d['frequency'] ?? ''))
                ];
            } elseif (is_string($d) && trim($d) !== '') {
                $cleanDrugs[] = [
                    'name' => trim($d),
                    'dose' => '',
                    'frequency' => ''
                ];
            }
        }

        // Detect high-level counts
        $totalDrugs = count($cleanDrugs);
        $totalInteractions = count($interactions);
        $totalContra = count($contraindications);

        $criticalCount = 0;
        $warningCount = 0;
        $moderateCount = 0;
        foreach ($interactions as $it) {
            $sev = strtolower((string)($it['severity'] ?? 'moderate'));
            if ($sev === 'critical') $criticalCount++;
            elseif ($sev === 'warning') $warningCount++;
            else $moderateCount++;
        }

        // Safety Palette and Titles
        $safetyTheme = match ($overallSafety) {
            'critical' => [
                'bg' => '#fef2f2',
                'border' => '#f87171',
                'accent' => '#dc2626',
                'badgeBg' => '#fee2e2',
                'badgeText' => '#991b1b',
                'badgeBorder' => '#fca5a5',
                'title' => 'هشدار بحرانی: منع مصرف قطعی و تداخل ماژور',
                'icon' => 'dangerous',
                'statusLabel' => 'خطر بحرانی 🔴',
                'rating' => 'غیرمجاز / پرخطر'
            ],
            'warning' => [
                'bg' => '#fff7ed',
                'border' => '#fb923c',
                'accent' => '#ea580c',
                'badgeBg' => '#ffedd5',
                'badgeText' => '#9a3412',
                'badgeBorder' => '#fdba74',
                'title' => 'احتیاط بالینی بالا: ریسک تداخل فارماکودینامیک / نیازمند پایش',
                'icon' => 'warning',
                'statusLabel' => 'احتیاط بالا 🟠',
                'rating' => 'مشروط و نیازمند پایش'
            ],
            'moderate' => [
                'bg' => '#fffbeb',
                'border' => '#facc15',
                'accent' => '#d97706',
                'badgeBg' => '#fef3c7',
                'badgeText' => '#92400e',
                'badgeBorder' => '#fde68a',
                'title' => 'تداخل متوسط: نیازمند رعایت فواصل زمانی دقیق مصرف',
                'icon' => 'schedule',
                'statusLabel' => 'تداخل متوسط 🟡',
                'rating' => 'مجاز با رعایت فواصل'
            ],
            default => [
                'bg' => '#f0fdf4',
                'border' => '#4ade80',
                'accent' => '#16a34a',
                'badgeBg' => '#dcfce7',
                'badgeText' => '#166534',
                'badgeBorder' => '#86efac',
                'title' => 'وضعیت ایمن: هیچ‌گونه تداخل بالینی شناخته‌شده‌ای ثبت نشد',
                'icon' => 'verified_user',
                'statusLabel' => 'سازگار و ایمن 🟢',
                'rating' => 'کاملاً سازگار و ایمن'
            ]
        };

        // Fallback default recommendations if empty
        if (empty($vetRecommendations)) {
            if ($overallSafety === 'critical') {
                $vetRecommendations = [
                    'توقف فوری تجویز همزمان داروهای ناسازگار و جایگزینی با پروتکل‌های ایمن‌تر دامپزشکی.',
                    'شروع دوره پاک‌سازی (Washout Period) حداقل ۵ تا ۷ روز پیش از تعویض دارو.',
                    'پایش مداوم آزمایشگاهی فاکتورهای کلیوی (BUN, Creatinine) و بررسی علائم آسیب مخاط گوارشی.'
                ];
            } else {
                $vetRecommendations = [
                    'رعایت دقیق دوز بر اساس وزن به‌روز و وضعیت جسمانی پت.',
                    'مصرف داروها همراه با مقداری آب تازه یا غذای سبک جهت کاهش عوارض گوارشی.',
                    'عدم تغییر خودسرانه دوز یا قطع ناگهانی بدون هماهنگی با دکتر دامپزشک معالج.'
                ];
            }
        }

        // Fallback default time spacing if empty
        if (empty($timeSpacingSchedule)) {
            if ($totalDrugs >= 2) {
                $timeSpacingSchedule = [
                    'وعده صبح (ساعت ۸:۰۰): داروی با اولویت اصلی همراه با وعده غذایی سبک.',
                    'وعده عصر (ساعت ۱۶:۰۰): داروی دوم با رعایت حداقل ۶ الی ۸ ساعت فاصله زمانی.',
                    'تأمین مداوم آب تصفیه‌شده و در دسترس در تمام طول شبانه‌روز.'
                ];
            } else {
                $timeSpacingSchedule = [
                    'تجویز طبق دستور درج‌شده روی نسخه توسط دکتر دامپزشک معالج با فواصل منظم روزانه.'
                ];
            }
        }

        ob_start();
        ?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>کارنامه بالینی پایش تداخلات دارویی پت | <?php echo $petName; ?> - آسنا</title>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <style>
        :root {
            --primary: #002d72;
            --primary-dark: #001a48;
            --emerald: #059669;
            --emerald-light: #10b981;
            --amber: #d97706;
            --rose: #e11d48;
            --rose-dark: #991b1b;
            --bg-slate: #f8fafc;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --border: #e2e8f0;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Vazirmatn', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        body {
            background-color: var(--bg-slate);
            color: var(--text-dark);
            line-height: 1.6;
            padding: 24px 16px;
            direction: rtl;
        }

        .document-container {
            max-width: 900px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 10px 30px -5px rgba(0, 45, 114, 0.08), 0 0 0 1px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        /* Action Bar */
        .action-bar {
            background: #001a48;
            padding: 14px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: #ffffff;
        }

        .action-btn {
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #ffffff;
            padding: 8px 16px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            transition: all 0.2s;
        }

        .action-btn:hover {
            background: rgba(255, 255, 255, 0.25);
        }

        .action-btn.primary {
            background: var(--emerald);
            border-color: var(--emerald-light);
        }

        .action-btn.primary:hover {
            background: #047857;
        }

        /* Official Header Strip */
        .header-strip {
            background: linear-gradient(135deg, #001a48 0%, #002d72 50%, #0348a6 100%);
            color: #ffffff;
            padding: 32px 32px 28px;
            position: relative;
        }

        .header-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(253, 129, 0, 0.2);
            color: #fed7aa;
            border: 1px solid rgba(253, 129, 0, 0.35);
            padding: 4px 12px;
            border-radius: 100px;
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .header-title {
            font-size: 24px;
            font-weight: 900;
            letter-spacing: -0.5px;
            margin-bottom: 6px;
        }

        .header-subtitle {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.8);
            font-weight: 400;
            max-width: 600px;
            line-height: 1.5;
        }

        .serial-badge {
            position: absolute;
            top: 28px;
            left: 32px;
            text-align: left;
        }

        .serial-badge .serial-text {
            font-family: monospace;
            font-size: 12px;
            background: rgba(255, 255, 255, 0.12);
            padding: 4px 10px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            font-weight: 800;
            letter-spacing: 0.5px;
            color: #ffffff;
            display: inline-block;
        }

        .serial-badge .date-text {
            font-size: 10px;
            color: rgba(255, 255, 255, 0.7);
            margin-top: 4px;
        }

        /* Patient Card */
        .patient-card {
            background: #f1f5f9;
            border-bottom: 1px solid var(--border);
            padding: 18px 32px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            gap: 16px;
        }

        .patient-item-label {
            font-size: 11px;
            color: var(--text-muted);
            font-weight: 600;
            margin-bottom: 2px;
        }

        .patient-item-value {
            font-size: 13px;
            font-weight: 800;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* Body Section */
        .body-section {
            padding: 32px;
        }

        /* Status Banner */
        .status-banner {
            background: <?php echo $safetyTheme['bg']; ?>;
            border: 2px solid <?php echo $safetyTheme['border']; ?>;
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 28px;
            display: flex;
            align-items: flex-start;
            gap: 18px;
        }

        .status-icon-wrap {
            width: 54px;
            height: 54px;
            border-radius: 16px;
            background: <?php echo $safetyTheme['badgeBg']; ?>;
            border: 1.5px solid <?php echo $safetyTheme['badgeBorder']; ?>;
            color: <?php echo $safetyTheme['accent']; ?>;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .status-icon-wrap .material-symbols-outlined {
            font-size: 32px;
        }

        .status-heading {
            font-size: 16px;
            font-weight: 900;
            color: <?php echo $safetyTheme['badgeText']; ?>;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .status-tag {
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 6px;
            background: #ffffff;
            border: 1px solid <?php echo $safetyTheme['border']; ?>;
            color: <?php echo $safetyTheme['badgeText']; ?>;
            font-weight: 800;
        }

        .status-desc {
            font-size: 13px;
            color: #334155;
            line-height: 1.7;
        }

        /* 4 Key Metrics Cards */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 28px;
        }

        @media (max-width: 640px) {
            .metrics-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .metric-card {
            padding: 16px;
            border-radius: 16px;
            border: 1px solid var(--border);
            background: #ffffff;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
        }

        .metric-card.danger {
            background: #fef2f2;
            border-color: #fecaca;
        }

        .metric-card.safe {
            background: #ecfdf5;
            border-color: #a7f3d0;
        }

        .metric-card-label {
            font-size: 11px;
            color: var(--text-muted);
            font-weight: 600;
            margin-bottom: 4px;
        }

        .metric-card-val {
            font-size: 22px;
            font-weight: 900;
            color: var(--primary);
            font-family: monospace;
        }

        .metric-card-val.red {
            color: var(--rose-dark);
        }

        .metric-card-val.green {
            color: var(--emerald);
        }

        .metric-card-sub {
            font-size: 10px;
            color: var(--text-muted);
            margin-top: 4px;
        }

        /* Section Titles */
        .section-title {
            font-size: 15px;
            font-weight: 900;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
            border-bottom: 2px solid #e0e7ff;
            padding-bottom: 8px;
        }

        .section-title .material-symbols-outlined {
            font-size: 20px;
            color: var(--primary);
        }

        /* Drugs Evaluated Roster */
        .drugs-roster-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 12px;
            margin-bottom: 30px;
        }

        .drug-chip-card {
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 12px 14px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .drug-chip-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: #eff6ff;
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            border: 1px solid #bfdbfe;
        }

        .drug-chip-name {
            font-size: 13px;
            font-weight: 800;
            color: var(--text-dark);
        }

        .drug-chip-sub {
            font-size: 11px;
            color: var(--text-muted);
        }

        /* Interaction Matrix Cards */
        .interaction-cards-list {
            display: flex;
            flex-direction: column;
            gap: 14px;
            margin-bottom: 30px;
        }

        .interaction-card {
            border-radius: 16px;
            padding: 18px 20px;
            background: #ffffff;
            border: 1px solid var(--border);
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.03);
            position: relative;
        }

        .interaction-card.critical {
            border-right: 5px solid var(--rose);
            background: #fffafa;
        }

        .interaction-card.warning {
            border-right: 5px solid var(--amber);
            background: #fffdfa;
        }

        .interaction-card.moderate {
            border-right: 5px solid #eab308;
            background: #fffff8;
        }

        .interaction-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
            flex-wrap: wrap;
            gap: 8px;
        }

        .pair-drugs {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 900;
            font-size: 14px;
            color: var(--primary-dark);
        }

        .pair-tag {
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 3px 10px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 12px;
            font-weight: 800;
        }

        .pair-arrow {
            color: var(--rose);
            font-weight: 900;
            font-size: 14px;
        }

        .sev-badge {
            font-size: 11px;
            font-weight: 800;
            padding: 3px 10px;
            border-radius: 8px;
        }

        .sev-badge.critical {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .sev-badge.warning {
            background: #ffedd5;
            color: #9a3412;
            border: 1px solid #fdba74;
        }

        .sev-badge.moderate {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        .interaction-title {
            font-size: 13px;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .interaction-mechanism {
            font-size: 12px;
            color: #475569;
            line-height: 1.7;
            margin-bottom: 12px;
            background: rgba(255, 255, 255, 0.7);
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid #f1f5f9;
        }

        .interaction-guidance {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 12px;
            color: #1e40af;
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }

        .interaction-guidance.danger {
            background: #fee2e2;
            border-color: #fca5a5;
            color: #991b1b;
        }

        /* Contraindications Box */
        .contra-box {
            background: #fef2f2;
            border: 1.5px solid #f87171;
            border-radius: 16px;
            padding: 18px 20px;
            margin-bottom: 30px;
        }

        .contra-title {
            font-size: 14px;
            font-weight: 900;
            color: #991b1b;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
        }

        .contra-item {
            font-size: 12px;
            color: #7f1d1d;
            line-height: 1.7;
            padding: 6px 0;
            border-bottom: 1px dashed #fca5a5;
        }

        .contra-item:last-child {
            border-bottom: none;
        }

        /* 2-Column Clinical Recommendations & Time Spacing */
        .two-col-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 30px;
        }

        @media (max-width: 768px) {
            .two-col-grid {
                grid-template-columns: 1fr;
            }
        }

        .box-panel {
            border-radius: 16px;
            padding: 18px;
            border: 1px solid var(--border);
            background: #ffffff;
        }

        .box-panel.schedule {
            background: #faf5ff;
            border-color: #e9d5ff;
        }

        .box-panel.advice {
            background: #f0fdf4;
            border-color: #bbf7d0;
        }

        .box-panel-title {
            font-size: 13px;
            font-weight: 800;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .box-panel.schedule .box-panel-title {
            color: #6b21a8;
        }

        .box-panel.advice .box-panel-title {
            color: #15803d;
        }

        .box-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .box-list li {
            font-size: 12px;
            line-height: 1.7;
            margin-bottom: 8px;
            position: relative;
            padding-right: 18px;
        }

        .box-panel.schedule .box-list li::before {
            content: "•";
            color: #9333ea;
            font-weight: bold;
            font-size: 18px;
            position: absolute;
            right: 0;
            top: -2px;
        }

        .box-panel.advice .box-list li::before {
            content: "✓";
            color: #16a34a;
            font-weight: bold;
            font-size: 13px;
            position: absolute;
            right: 0;
            top: 1px;
        }

        /* Sign-off & Verification */
        .signoff-section {
            border-top: 2px dashed var(--border);
            padding-top: 24px;
            margin-top: 24px;
            display: grid;
            grid-template-columns: 1fr 1fr 180px;
            gap: 16px;
            align-items: center;
        }

        @media (max-width: 768px) {
            .signoff-section {
                grid-template-columns: 1fr;
            }
        }

        .stamp-box {
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
            padding: 16px;
            text-align: center;
            background: #f8fafc;
            min-height: 90px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .stamp-title {
            font-size: 11px;
            font-weight: 700;
            color: var(--text-muted);
            margin-bottom: 4px;
        }

        .stamp-subtitle {
            font-size: 10px;
            color: #94a3b8;
        }

        .qr-box {
            text-align: center;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: #ffffff;
        }

        .qr-placeholder {
            width: 70px;
            height: 70px;
            margin: 0 auto 6px;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
        }

        .qr-label {
            font-size: 9px;
            font-weight: 700;
            color: var(--text-muted);
        }

        /* Disclaimer */
        .disclaimer-text {
            margin-top: 20px;
            text-align: center;
            font-size: 11px;
            color: #94a3b8;
            line-height: 1.6;
            border-top: 1px solid #f1f5f9;
            padding-top: 14px;
        }

        /* Print Media Styles */
        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
            }
            .action-bar {
                display: none !important;
            }
            .document-container {
                box-shadow: none !important;
                border: none !important;
                max-width: 100% !important;
            }
            .interaction-card {
                break-inside: avoid;
            }
        }
    </style>
</head>
<body>

<div class="document-container">

    <!-- Action Bar -->
    <div class="action-bar">
        <div style="display: flex; align-items: center; gap: 8px;">
            <a href="profile.php?tab=pets#pets" class="action-btn">
                <span class="material-symbols-outlined" style="font-size: 16px;">arrow_forward</span>
                <span>بازگشت به پرونده سلامت</span>
            </a>
            <a href="interactions.php" class="action-btn">
                <span class="material-symbols-outlined" style="font-size: 16px;">medication</span>
                <span>پایش مجدد در سامانه</span>
            </a>
        </div>
        <div style="display: flex; align-items: center; gap: 8px;">
            <button onclick="window.print()" class="action-btn primary">
                <span class="material-symbols-outlined" style="font-size: 16px;">print</span>
                <span>چاپ و ذخیره رسمی (PDF)</span>
            </button>
        </div>
    </div>

    <!-- Official Header Strip -->
    <div class="header-strip">
        <div class="header-badge">
            <span class="material-symbols-outlined" style="font-size: 14px;">science</span>
            <span>سامانه فارماکولوژی بالینی و سنجش تداخلات دارویی آسنا</span>
        </div>
        <h1 class="header-title">کارنامه بالینی پایش تداخلات دارویی پت</h1>
        <p class="header-subtitle">
            ارزیابی چندلایه فارماکودینامیک و فارماکوکینتیک بالینی بر پایه معتبرترین مستندات فارماکوپیای دامپزشکی (Plumb's Veterinary Drug Handbook & BSAVA)
        </p>

        <div class="serial-badge">
            <div class="serial-text"><?php echo $serial; ?></div>
            <div class="date-text">تاریخ صدور: <?php echo htmlspecialchars($createdAt); ?></div>
        </div>
    </div>

    <!-- Patient Dossier Bar -->
    <div class="patient-card">
        <div>
            <div class="patient-item-label">نام بیمار (پت)</div>
            <div class="patient-item-value">
                <span class="material-symbols-outlined" style="font-size: 16px; color: var(--primary);">pets</span>
                <span><?php echo $petName; ?></span>
            </div>
        </div>
        <div>
            <div class="patient-item-label">گونه و رده بالینی</div>
            <div class="patient-item-value">
                <span><?php echo $speciesFa; ?></span>
                <span style="font-size: 11px; color: var(--text-muted); font-weight: 600;">(<?php echo $species === 'cat' ? 'Feline' : ($species === 'dog' ? 'Canine' : ucfirst($species)); ?>)</span>
            </div>
        </div>
        <div>
            <div class="patient-item-label">نژاد</div>
            <div class="patient-item-value"><?php echo $race ?: 'عمومی / ترکیبی'; ?></div>
        </div>
        <div>
            <div class="patient-item-label">وزن ثبت‌شده</div>
            <div class="patient-item-value"><?php echo number_format($weightKg, 1); ?> کیلوگرم</div>
        </div>
        <div>
            <div class="patient-item-label">مرحله سنی</div>
            <div class="patient-item-value"><?php echo $ageStageFa; ?></div>
        </div>
        <div>
            <div class="patient-item-label">وضعیت ایمنی نسخه</div>
            <div class="patient-item-value">
                <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: <?php echo $safetyTheme['accent']; ?>;"></span>
                <span><?php echo $safetyTheme['statusLabel']; ?></span>
            </div>
        </div>
    </div>

    <!-- Body Section -->
    <div class="body-section">

        <!-- Executive Safety Status Banner -->
        <div class="status-banner">
            <div class="status-icon-wrap">
                <span class="material-symbols-outlined"><?php echo $safetyTheme['icon']; ?></span>
            </div>
            <div style="flex: 1;">
                <div class="status-heading">
                    <span><?php echo $safetyTheme['title']; ?></span>
                    <span class="status-tag"><?php echo $safetyTheme['rating']; ?></span>
                </div>
                <div class="status-desc">
                    <?php echo nl2br(htmlspecialchars($overallSummary ?: 'تحلیل بالینی تداخلات دارویی با دقت بالا توسط موتور فارماکولوژی بالینی آسنا پردازش شد.')); ?>
                </div>
            </div>
        </div>

        <!-- 4 Key Metrics Cards -->
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-card-label">داروهای پایش‌شده</div>
                <div class="metric-card-val"><?php echo $totalDrugs; ?></div>
                <div class="metric-card-sub">قلم داروی تجویزی</div>
            </div>
            <div class="metric-card <?php echo ($criticalCount > 0 ? 'danger' : ''); ?>">
                <div class="metric-card-label">تداخلات بحرانی (ماژور)</div>
                <div class="metric-card-val <?php echo ($criticalCount > 0 ? 'red' : ''); ?>"><?php echo $criticalCount; ?></div>
                <div class="metric-card-sub">منع مصرف قطعی همزمان</div>
            </div>
            <div class="metric-card">
                <div class="metric-card-label">احتیاط بالینی / متوسط</div>
                <div class="metric-card-val"><?php echo ($warningCount + $moderateCount); ?></div>
                <div class="metric-card-sub">نیازمند فاصله زمانی / مانیتورینگ</div>
            </div>
            <div class="metric-card <?php echo ($overallSafety === 'safe' ? 'safe' : ''); ?>">
                <div class="metric-card-label">ضریب سازگاری نسخه</div>
                <div class="metric-card-val <?php echo ($overallSafety === 'safe' ? 'green' : ''); ?>" style="font-family: inherit; font-size: 19px;">
                    <?php echo match($overallSafety) { 'critical' => '۰٪ (بحرانی)', 'warning' => '۳۵٪ (احتیاط)', 'moderate' => '۷۰٪ (مشروط)', default => '۱۰۰٪ (ایمن)' }; ?>
                </div>
                <div class="metric-card-sub">شاخص کلیرنس فارماکولوژی</div>
            </div>
        </div>

        <!-- Section: Evaluated Drugs Roster -->
        <div class="section-title">
            <span class="material-symbols-outlined">pill</span>
            <span>شناسنامه داروهای ارزیابی‌شده در نسخه</span>
        </div>

        <div class="drugs-roster-grid">
            <?php if (!empty($cleanDrugs)): ?>
                <?php foreach ($cleanDrugs as $d): ?>
                    <div class="drug-chip-card">
                        <div class="drug-chip-icon">
                            <span class="material-symbols-outlined" style="font-size: 20px;">medication</span>
                        </div>
                        <div>
                            <div class="drug-chip-name"><?php echo htmlspecialchars($d['name']); ?></div>
                            <div class="drug-chip-sub">
                                <?php echo !empty($d['dose']) ? htmlspecialchars($d['dose']) : 'دوز درمانی استاندارد'; ?>
                                <?php if (!empty($d['frequency'])) echo ' | ' . htmlspecialchars($d['frequency']); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="font-size: 12px; color: var(--text-muted); padding: 10px;">دارویی جهت نمایش ثبت نشده است.</div>
            <?php endif; ?>
        </div>

        <!-- Section: Absolute Contraindications (If any) -->
        <?php if (!empty($contraindications)): ?>
            <div class="contra-box">
                <div class="contra-title">
                    <span class="material-symbols-outlined">block</span>
                    <span>موارد منع مصرف قطعی شناسایی‌شده (Contraindications)</span>
                </div>
                <?php foreach ($contraindications as $ct): ?>
                    <div class="contra-item">
                        <strong><?php echo htmlspecialchars($ct['drug'] ?? $ct['title'] ?? 'دارو'); ?>:</strong>
                        <span><?php echo htmlspecialchars($ct['mechanism'] ?? $ct['title'] ?? ''); ?></span>
                        <?php if (!empty($ct['action'])): ?>
                            <div style="margin-top: 4px; font-weight: 700; color: #991b1b;">
                                اقدام فوری: <?php echo htmlspecialchars($ct['action']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Section: Detailed Pairwise Interaction Breakdown -->
        <div class="section-title">
            <span class="material-symbols-outlined">sync_alt</span>
            <span>تحلیل تفصیلی تداخلات فارماکولوژیک جفتی</span>
        </div>

        <?php if (!empty($interactions)): ?>
            <div class="interaction-cards-list">
                <?php foreach ($interactions as $it): 
                    $sev = strtolower((string)($it['severity'] ?? 'moderate'));
                    $isCrit = ($sev === 'critical');
                    $isWarn = ($sev === 'warning');
                    $cardClass = $isCrit ? 'critical' : ($isWarn ? 'warning' : 'moderate');
                    $badgeClass = $cardClass;
                    $badgeLabel = $it['severity_label'] ?? $it['level_fa'] ?? ($isCrit ? 'خطر بحرانی 🔴' : ($isWarn ? 'احتیاط بالا 🟠' : 'تداخل متوسط 🟡'));
                ?>
                    <div class="interaction-card <?php echo $cardClass; ?>">
                        <div class="interaction-header">
                            <div class="pair-drugs">
                                <span class="pair-tag"><?php echo htmlspecialchars($it['drug1'] ?? ''); ?></span>
                                <span class="pair-arrow">⇄</span>
                                <span class="pair-tag"><?php echo htmlspecialchars($it['drug2'] ?? ''); ?></span>
                            </div>
                            <div class="sev-badge <?php echo $badgeClass; ?>">
                                <?php echo htmlspecialchars($badgeLabel); ?>
                            </div>
                        </div>

                        <?php if (!empty($it['title'])): ?>
                            <div class="interaction-title">
                                <?php echo htmlspecialchars($it['title']); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($it['mechanism'])): ?>
                            <div class="interaction-mechanism">
                                <strong>مکانیسم بیوشیمیایی و فارماکوکینتیک:</strong>
                                <div><?php echo nl2br(htmlspecialchars($it['mechanism'])); ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($it['clinical_signs'])): ?>
                            <div style="font-size: 11px; color: #b91c1c; margin-bottom: 10px; font-weight: 600;">
                                ⚠️ <strong>علائم هشداردهنده بالینی:</strong> <?php echo htmlspecialchars($it['clinical_signs']); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($it['recommendation'])): ?>
                            <div class="interaction-guidance <?php echo $isCrit ? 'danger' : ''; ?>">
                                <span class="material-symbols-outlined" style="font-size: 18px; flex-shrink: 0;">medical_information</span>
                                <div>
                                    <strong>دستورالعمل بالینی و مداخله دامپزشکی:</strong>
                                    <div><?php echo nl2br(htmlspecialchars($it['recommendation'])); ?></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 14px; padding: 18px; margin-bottom: 28px; text-align: center; color: #166534; font-size: 13px;">
                <span class="material-symbols-outlined" style="font-size: 24px; vertical-align: middle; margin-left: 6px;">check_circle</span>
                <span>هیچ‌گونه تداخل دارویی نامطلوب میان اقلام بررسی‌شده ثبت نگردید. داروها از نظر فارماکوکینتیک سازگار ارزیابی می‌شوند.</span>
            </div>
        <?php endif; ?>

        <!-- Two-Column Grid: Safe Spacing Timetable & Clinical Guidance -->
        <div class="two-col-grid">
            <!-- Time Spacing Schedule -->
            <div class="box-panel schedule">
                <div class="box-panel-title">
                    <span class="material-symbols-outlined" style="font-size: 18px;">schedule</span>
                    <span>پروتکل فواصل زمانی و ساعات مصرف ایمن</span>
                </div>
                <ul class="box-list">
                    <?php foreach ($timeSpacingSchedule as $sch): ?>
                        <li><?php echo htmlspecialchars($sch); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Clinical Advice -->
            <div class="box-panel advice">
                <div class="box-panel-title">
                    <span class="material-symbols-outlined" style="font-size: 18px;">health_and_safety</span>
                    <span>توصیه‌های بالینی تخصصی به سرپرست و دامپزشک</span>
                </div>
                <ul class="box-list">
                    <?php foreach ($vetRecommendations as $rec): ?>
                        <li><?php echo htmlspecialchars($rec); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <!-- Official Sign-off & Medical Seals -->
        <div class="signoff-section">
            <div class="stamp-box">
                <div class="stamp-title">مهر و تایید دکتر دامپزشک معالج</div>
                <div class="stamp-subtitle">نام و شماره نظام دامپزشکی / تاریخ معاینه</div>
            </div>
            <div class="stamp-box">
                <div class="stamp-title">تاییدیه مسئول فنی داروخانه دامپزشکی</div>
                <div class="stamp-subtitle">ثبت تحویل اقلام دارویی و کنترل دوز</div>
            </div>
            <div class="qr-box">
                <div class="qr-placeholder">
                    <span class="material-symbols-outlined" style="font-size: 32px;">qr_code_2</span>
                </div>
                <div class="qr-label">شناسه رسمی: <?php echo $serial; ?></div>
            </div>
        </div>

        <!-- Disclaimer -->
        <div class="disclaimer-text">
            این کارنامه بالینی صرفاً جهت افزایش ایمنی درمان و بر پایه معتبرترین کتاب‌های مرجع فارماکولوژی دامپزشکی جهان استخراج گردیده است. تشخیص بیماری، دوز دقیق و دستور نهایی مصرف منحصراً در صلاحیت دکتر دامپزشک معالج پس از معاینه حضوری بیمار می‌باشد.
        </div>

    </div>

</div>

</body>
</html>
        <?php
        return (string)ob_get_clean();
    }
}
