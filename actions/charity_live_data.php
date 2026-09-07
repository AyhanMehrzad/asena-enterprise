<?php
/**
 * actions/charity_live_data.php — Real-time JSON endpoint for charity stats, progress, top champions, and recent donations
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

try {
    // 1. Fetch active campaigns with accurate calculated current_amount
    $stmt = $pdo->query("
        SELECT c.*, 
               COALESCE((
                   SELECT SUM(d.amount) 
                   FROM donations d 
                   WHERE d.campaign_id = c.id AND d.status = 'successful'
               ), 0) as calc_current_amount,
               COALESCE((
                   SELECT COUNT(*) 
                   FROM donations d 
                   WHERE d.campaign_id = c.id AND d.status = 'successful'
               ), 0) as donor_count
        FROM campaigns c 
        WHERE c.status = 'active' 
        ORDER BY c.created_at DESC
    ");
    $rawCampaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $campaigns = [];
    foreach ($rawCampaigns as $camp) {
        $goal = (int)$camp['goal_amount'];
        $current = (int)$camp['calc_current_amount'];
        // Sync column if needed
        if ((int)$camp['current_amount'] !== $current) {
            $updateStmt = $pdo->prepare("UPDATE campaigns SET current_amount = ? WHERE id = ?");
            $updateStmt->execute([$current, $camp['id']]);
        }
        $percent = $goal > 0 ? min(100, round(($current / $goal) * 100)) : 0;

        $campaigns[] = [
            'id' => (int)$camp['id'],
            'title' => $camp['title'],
            'description' => $camp['description'],
            'goal_amount' => $goal,
            'goal_amount_formatted' => number_format($goal),
            'current_amount' => $current,
            'current_amount_formatted' => number_format($current),
            'percent' => $percent,
            'donor_count' => (int)$camp['donor_count'],
            'image_url' => $camp['image_url']
        ];
    }

    // 2. Fetch Top 5 Champions
    $stmt = $pdo->query("
        SELECT donor_name, 
               SUM(amount) as total_donated, 
               COUNT(*) as donations_count,
               MAX(created_at) as last_donated_at
        FROM donations 
        WHERE status = 'successful' AND donor_name IS NOT NULL AND donor_name != '' AND donor_name != 'ناشناس'
        GROUP BY donor_name 
        ORDER BY total_donated DESC 
        LIMIT 5
    ");
    $rawTop = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $topDonors = [];
    foreach ($rawTop as $i => $donor) {
        $total = (int)$donor['total_donated'];
        $rank = $i + 1;
        $medal = '';
        $medalColor = '';
        if ($rank === 1) {
            $medal = '🥇';
            $medalColor = 'from-amber-400 to-yellow-500 text-slate-900 border-amber-300 ring-2 ring-amber-400/40 shadow-lg';
        } elseif ($rank === 2) {
            $medal = '🥈';
            $medalColor = 'from-slate-200 to-slate-400 text-slate-900 border-slate-300 shadow-md';
        } elseif ($rank === 3) {
            $medal = '🥉';
            $medalColor = 'from-amber-700 to-orange-800 text-white border-amber-600 shadow-md';
        } else {
            $medalColor = 'from-secondary-container to-emerald-600 text-white border-emerald-400/30';
        }

        $topDonors[] = [
            'rank' => $rank,
            'donor_name' => $donor['donor_name'],
            'total_donated' => $total,
            'total_donated_formatted' => number_format($total),
            'donations_count' => (int)$donor['donations_count'],
            'medal' => $medal,
            'badge_class' => $medalColor
        ];
    }

    // 3. Fetch Recent Donations (Latest 10)
    $stmt = $pdo->query("
        SELECT d.id, d.donor_name, d.amount, d.created_at, d.campaign_id, c.title as campaign_title 
        FROM donations d 
        LEFT JOIN campaigns c ON d.campaign_id = c.id 
        WHERE d.status = 'successful' 
        ORDER BY d.created_at DESC, d.id DESC 
        LIMIT 10
    ");
    $rawRecent = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $now = time();
    $recentDonations = [];
    foreach ($rawRecent as $recent) {
        $timeDiff = $now - strtotime($recent['created_at']);
        $relativeTime = 'چند لحظه پیش';
        if ($timeDiff >= 86400) {
            $days = floor($timeDiff / 86400);
            $relativeTime = $days . ' روز پیش';
        } elseif ($timeDiff >= 3600) {
            $hours = floor($timeDiff / 3600);
            $relativeTime = $hours . ' ساعت پیش';
        } elseif ($timeDiff >= 60) {
            $minutes = floor($timeDiff / 60);
            $relativeTime = $minutes . ' دقیقه پیش';
        }

        $recentDonations[] = [
            'id' => (int)$recent['id'],
            'donor_name' => !empty($recent['donor_name']) ? $recent['donor_name'] : 'ناشناس',
            'is_anonymous' => ($recent['donor_name'] === 'ناشناس' || empty($recent['donor_name'])),
            'campaign_id' => $recent['campaign_id'] ? (int)$recent['campaign_id'] : null,
            'campaign_title' => $recent['campaign_title'] ?: 'عمومی',
            'amount' => (int)$recent['amount'],
            'amount_formatted' => number_format((int)$recent['amount']),
            'created_at' => $recent['created_at'],
            'relative_time' => $relativeTime
        ];
    }

    // 4. Overall Totals
    $statsStmt = $pdo->query("
        SELECT COALESCE(SUM(amount), 0) as total_raised, 
               COUNT(*) as total_donations 
        FROM donations 
        WHERE status = 'successful'
    ");
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'timestamp' => $now,
        'campaigns' => $campaigns,
        'top_donors' => $topDonors,
        'recent_donations' => $recentDonations,
        'stats' => [
            'total_raised' => (int)$stats['total_raised'],
            'total_raised_formatted' => number_format((int)$stats['total_raised']),
            'total_donations' => (int)$stats['total_donations']
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
