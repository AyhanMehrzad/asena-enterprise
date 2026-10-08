<?php
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__) . '/includes/App.php';

App::boot();
AuthGuard::requireRole('admin');
$pdo = App::db();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

csrf_verify();
$action = trim((string)($_POST['action'] ?? ''));
$adminId = (int)($_SESSION['user_id'] ?? 0);
$tenantService = App::tenantSite();

$json = static function (array $payload): never {
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
};

try {
    if ($action === 'provision') {
        $tenantType = trim((string)($_POST['tenant_type'] ?? ''));
        $tenantId = (int)($_POST['tenant_id'] ?? 0);
        $archetype = trim((string)($_POST['archetype'] ?? $tenantType));
        $tier = trim((string)($_POST['site_tier'] ?? 'standard'));
        $slug = trim((string)($_POST['slug'] ?? ''));
        $title = trim((string)($_POST['site_title'] ?? ''));
        $tagline = trim((string)($_POST['site_tagline'] ?? ''));
        $status = trim((string)($_POST['lifecycle_status'] ?? 'draft'));

        if (!in_array($tenantType, ['doctor', 'organization', 'pharmacist', 'seller'], true) || $tenantId < 1) {
            $json(['success' => false, 'message' => 'Tenant role and identity are required.']);
        }
        if (!in_array($tier, ['basic', 'standard', 'premium', 'pharmacy', 'enterprise'], true)) $tier = 'standard';
        if (!in_array($status, ['draft', 'provisioned', 'customizing', 'published', 'suspended', 'archived'], true)) $status = 'draft';
        if (!in_array($archetype, ['doctor', 'organization', 'pharmacist', 'seller'], true)) $archetype = $tenantType;

        $info = ['name' => $title ?: ($tenantType . ' website'), 'tagline' => $tagline];
        $site = $tenantService->getOrCreateDefault($tenantType, $tenantId, $info);
        $layout = $site['layout'] ?? [];
        $layout['theme']['archetype'] = $archetype;
        $save = $tenantService->saveSite($tenantType, $tenantId, [
            'slug' => $slug ?: ($site['slug'] ?? ''),
            'site_title' => $title ?: ($site['site_title'] ?? $info['name']),
            'site_tagline' => $tagline ?: ($site['site_tagline'] ?? ''),
            'site_tier' => $tier,
            'theme_palette' => $site['theme_palette'] ?? 'emerald',
            'layout' => $layout,
            'is_published' => $status === 'published' ? 1 : 0,
        ]);
        if (empty($save['success'])) $json($save);

        $stmt = $pdo->prepare("UPDATE tenant_sites SET lifecycle_status = ?, provisioned_by_user_id = ?, provisioning_source = 'admin', payment_required = 0, approved_by_user_id = ?, approved_at = CURRENT_TIMESTAMP, published_by_user_id = ?, published_at = CASE WHEN ? = 'published' THEN CURRENT_TIMESTAMP ELSE NULL END WHERE tenant_type = ? AND tenant_id = ?");
        $stmt->execute([$status, $adminId, $adminId, $status === 'published' ? $adminId : null, $status, $tenantType, $tenantId]);
        App::securityAudit()->logEvent('website_provisioned', 'info', $adminId, 'Admin provisioned tenant website', ['tenant_type' => $tenantType, 'tenant_id' => $tenantId, 'tier' => $tier, 'status' => $status]);
        $json(['success' => true, 'message' => 'Website provisioned successfully.', 'slug' => $save['slug']]);
    }

    if ($action === 'update_site') {
        $siteId = (int)($_POST['site_id'] ?? 0);
        $status = trim((string)($_POST['lifecycle_status'] ?? 'published'));
        $tier = trim((string)($_POST['site_tier'] ?? 'standard'));
        if ($siteId < 1 || !in_array($status, ['draft', 'provisioned', 'customizing', 'published', 'suspended', 'archived'], true)) $json(['success' => false, 'message' => 'Invalid website update.']);
        $stmt = $pdo->prepare("UPDATE tenant_sites SET lifecycle_status = ?, site_tier = ?, is_published = ?, published_by_user_id = CASE WHEN ? = 'published' THEN ? ELSE published_by_user_id END, published_at = CASE WHEN ? = 'published' THEN CURRENT_TIMESTAMP ELSE published_at END WHERE id = ?");
        $stmt->execute([$status, $tier, $status === 'published' ? 1 : 0, $status, $adminId, $status, $siteId]);
        App::securityAudit()->logEvent('website_updated', 'info', $adminId, 'Admin updated tenant website lifecycle', ['site_id' => $siteId, 'status' => $status, 'tier' => $tier]);
        $json(['success' => true, 'message' => 'Website updated.']);
    }

    if ($action === 'update_order') {
        $orderId = (int)($_POST['order_id'] ?? 0);
        $status = trim((string)($_POST['status'] ?? 'pending'));
        if ($orderId < 1 || !in_array($status, ['pending', 'needs_information', 'approved', 'provisioned', 'in_progress', 'published', 'rejected', 'cancelled'], true)) $json(['success' => false, 'message' => 'Invalid request update.']);
        $stmt = $pdo->prepare("UPDATE website_orders SET status = ?, reviewed_by_user_id = ?, reviewed_at = CURRENT_TIMESTAMP, admin_notes = ? WHERE id = ?");
        $stmt->execute([$status, $adminId, trim((string)($_POST['admin_notes'] ?? '')), $orderId]);
        App::securityAudit()->logEvent('website_order_reviewed', 'info', $adminId, 'Admin reviewed website request', ['order_id' => $orderId, 'status' => $status]);
        $json(['success' => true, 'message' => 'Request status updated.']);
    }

    $json(['success' => false, 'message' => 'Unknown action.']);
} catch (Throwable $e) {
    error_log('[admin_websites_action] ' . $e->getMessage());
    $json(['success' => false, 'message' => 'Unable to complete website operation.']);
}
