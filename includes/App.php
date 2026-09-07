<?php
/**
 * ASENA Enterprise - Central Service Container
 * Version: 1.0.0
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/SecurityMiddleware.php';
require_once __DIR__ . '/RateLimiter.php';
require_once __DIR__ . '/CacheService.php';
require_once __DIR__ . '/SmsService.php';
require_once __DIR__ . '/PushNotificationService.php';
require_once __DIR__ . '/AutoshipService.php';
require_once __DIR__ . '/PetPassportService.php';
require_once __DIR__ . '/OrderLifecycleService.php';
require_once __DIR__ . '/WholesaleService.php';
require_once __DIR__ . '/ShippingCalculator.php';
require_once __DIR__ . '/FlashSaleService.php';
require_once __DIR__ . '/RoleVerificationService.php';
require_once __DIR__ . '/OrganizationService.php';
require_once __DIR__ . '/SecurityAuditService.php';
require_once __DIR__ . '/WafMiddleware.php';
require_once __DIR__ . '/AuthGuard.php';
require_once __DIR__ . '/IranPostService.php';
require_once __DIR__ . '/MarketplaceEscrowService.php';
require_once __DIR__ . '/TrafficMonitoringService.php';
require_once __DIR__ . '/PostexShippingService.php';
require_once __DIR__ . '/LeaderboardService.php';

class App {
    private static ?PDO $db = null;
    private static ?CacheService $cache = null;
    private static ?RateLimiter $rateLimiter = null;
    private static ?SmsService $sms = null;
    private static ?PushNotificationService $push = null;
    private static ?AutoshipService $autoship = null;
    private static ?PetPassportService $petPassport = null;
    private static ?OrderLifecycleService $orderLifecycle = null;
    private static ?WholesaleService $wholesale = null;
    private static ?ShippingCalculator $shipping = null;
    private static ?FlashSaleService $flashSale = null;
    private static ?RoleVerificationService $roleVerification = null;
    private static ?OrganizationService $organization = null;
    private static ?SecurityAuditService $securityAudit = null;
    private static ?IranPostService $iranPost = null;
    private static ?MarketplaceEscrowService $escrow = null;
    private static ?TrafficMonitoringService $traffic = null;
    private static ?PostexShippingService $postex = null;
    private static ?LeaderboardService $leaderboard = null;



    public static function db(): PDO {
        if (self::$db === null) {
            self::$db = $GLOBALS['pdo'];
        }
        return self::$db;
    }

    public static function cache(): CacheService {
        if (self::$cache === null) {
            self::$cache = CacheService::getInstance();
        }
        return self::$cache;
    }

    public static function rateLimiter(): RateLimiter {
        if (self::$rateLimiter === null) {
            self::$rateLimiter = new RateLimiter(self::db());
        }
        return self::$rateLimiter;
    }

    public static function sms(): SmsService {
        if (self::$sms === null) {
            self::$sms = new SmsService();
        }
        return self::$sms;
    }

    public static function push(): PushNotificationService {
        if (self::$push === null) {
            self::$push = new PushNotificationService(self::sms());
        }
        return self::$push;
    }

    public static function autoship(): AutoshipService {
        if (self::$autoship === null) {
            self::$autoship = new AutoshipService(self::db());
        }
        return self::$autoship;
    }

    public static function petPassport(): PetPassportService {
        if (self::$petPassport === null) {
            self::$petPassport = new PetPassportService(self::db());
        }
        return self::$petPassport;
    }

    public static function orderLifecycle(): OrderLifecycleService {
        if (self::$orderLifecycle === null) {
            self::$orderLifecycle = new OrderLifecycleService(self::db());
        }
        return self::$orderLifecycle;
    }

    public static function wholesale(): WholesaleService {
        if (self::$wholesale === null) {
            self::$wholesale = new WholesaleService(self::db());
        }
        return self::$wholesale;
    }

    public static function shipping(): ShippingCalculator {
        if (self::$shipping === null) {
            self::$shipping = new ShippingCalculator(self::db());
        }
        return self::$shipping;
    }

    public static function flashSale(): FlashSaleService {
        if (self::$flashSale === null) {
            self::$flashSale = new FlashSaleService(self::db());
        }
        return self::$flashSale;
    }

    public static function roleVerification(): RoleVerificationService {
        if (self::$roleVerification === null) {
            self::$roleVerification = new RoleVerificationService(self::db(), self::sms());
        }
        return self::$roleVerification;
    }

    public static function organization(): OrganizationService {
        if (self::$organization === null) {
            self::$organization = new OrganizationService(self::db());
        }
        return self::$organization;
    }

    public static function securityAudit(): SecurityAuditService {
        if (self::$securityAudit === null) {
            self::$securityAudit = new SecurityAuditService(self::db());
        }
        return self::$securityAudit;
    }

    public static function iranPost(): IranPostService {
        if (self::$iranPost === null) {
            self::$iranPost = new IranPostService(self::db());
        }
        return self::$iranPost;
    }

    public static function escrow(): MarketplaceEscrowService {
        if (self::$escrow === null) {
            self::$escrow = new MarketplaceEscrowService(self::db());
        }
        return self::$escrow;
    }

    public static function traffic(): TrafficMonitoringService {
        if (self::$traffic === null) {
            self::$traffic = new TrafficMonitoringService(self::db());
        }
        return self::$traffic;
    }

    public static function postex(): PostexShippingService {
        if (self::$postex === null) {
            self::$postex = new PostexShippingService(self::db());
        }
        return self::$postex;
    }

    public static function leaderboard(): LeaderboardService {
        if (self::$leaderboard === null) {
            self::$leaderboard = new LeaderboardService(self::db());
        }
        return self::$leaderboard;
    }

    /**
     * Boot enterprise request environment: Headers, Secure Session, Traffic Inspection, WAF
     */
    public static function boot(): void {
        SecurityMiddleware::applyHeaders();
        SecurityMiddleware::secureSession();
        TrafficMonitoringService::inspectAndLog(self::db());
        WafMiddleware::inspect(self::db());
    }
}
