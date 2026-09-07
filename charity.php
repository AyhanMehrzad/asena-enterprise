<?php
require_once 'includes/db.php';
if (!Feature::has('charity_campaigns')) {
    header('Location: index.php');
    exit;
}
include 'includes/header.php';

// Fetch active campaigns with accurate calculated current_amount & donor_count
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
$campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch top 5 donors
$stmt = $pdo->query("
    SELECT donor_name, SUM(amount) as total_donated 
    FROM donations 
    WHERE status = 'successful' AND donor_name IS NOT NULL AND donor_name != '' AND donor_name != 'ناشناس'
    GROUP BY donor_name 
    ORDER BY total_donated DESC 
    LIMIT 5
");
$topDonors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch recent donations
$stmt = $pdo->query("
    SELECT d.id, d.donor_name, d.amount, d.created_at, d.campaign_id, c.title as campaign_title 
    FROM donations d 
    LEFT JOIN campaigns c ON d.campaign_id = c.id 
    WHERE d.status = 'successful' 
    ORDER BY d.created_at DESC, d.id DESC 
    LIMIT 10
");
$recentDonations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Swiper CSS -->
<link rel="stylesheet" href="assets/css/swiper-bundle.min.css" />

<style>
    /* Swiper Hero Custom Pagination & Navigation */
    .charitySwiper .swiper-pagination-bullet {
        background: rgba(255, 255, 255, 0.45);
        width: 10px;
        height: 10px;
        opacity: 0.8;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .charitySwiper .swiper-pagination-bullet-active {
        background: #10b981 !important;
        width: 28px;
        border-radius: 9999px;
        opacity: 1;
        box-shadow: 0 0 12px rgba(16, 185, 129, 0.6);
    }
</style>

<!-- Confetti Canvas for Dynamic Celebration -->
<canvas id="charityConfettiCanvas" class="fixed inset-0 pointer-events-none z-[10070] hidden"></canvas>

<main class="w-full max-w-[1400px] mx-auto px-4 lg:px-8 space-y-16 pb-24 mt-8">
    
    <?php if(isset($_SESSION['charity_success'])): ?>
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 p-6 rounded-3xl flex items-center gap-4 shadow-sm animate-fade-in">
        <div class="w-12 h-12 rounded-2xl bg-emerald-100 flex items-center justify-center text-emerald-600 shrink-0">
            <span class="material-symbols-outlined text-3xl">check_circle</span>
        </div>
        <div>
            <h4 class="font-black text-base">حمایت شما با موفقیت ثبت شد</h4>
            <p class="text-sm font-medium mt-0.5 opacity-90"><?php echo htmlspecialchars($_SESSION['charity_success']); unset($_SESSION['charity_success']); ?></p>
        </div>
    </div>
    <?php endif; ?>

    <?php if(isset($_SESSION['charity_error'])): ?>
    <div class="bg-red-50 border border-red-200 text-red-800 p-6 rounded-3xl flex items-center gap-4 shadow-sm animate-fade-in">
        <div class="w-12 h-12 rounded-2xl bg-red-100 flex items-center justify-center text-red-600 shrink-0">
            <span class="material-symbols-outlined text-3xl">error</span>
        </div>
        <div>
            <h4 class="font-black text-base">خطا در پردازش حمایت</h4>
            <p class="text-sm font-medium mt-0.5 opacity-90"><?php echo htmlspecialchars($_SESSION['charity_error']); unset($_SESSION['charity_error']); ?></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Hero Section with Swiper -->
    <section class="relative bg-surface-container-low rounded-[2rem] sm:rounded-[3rem] overflow-hidden shadow-xl border border-outline-variant/10">
        <div class="swiper charitySwiper min-h-[500px] sm:min-h-[540px] md:min-h-[600px] lg:min-h-[680px] h-[520px] sm:h-[560px] md:h-[620px] lg:h-[680px]">
            <div class="swiper-wrapper">
                <?php if(empty($campaigns)): ?>
                <div class="swiper-slide flex items-center justify-center h-full bg-primary-container text-white p-8 sm:p-12 text-center">
                    <div>
                        <span class="material-symbols-outlined text-5xl sm:text-6xl mb-4">volunteer_activism</span>
                        <h2 class="text-2xl sm:text-3xl font-bold">در حال حاضر کمپین فعالی وجود ندارد</h2>
                        <p class="mt-4 opacity-80 text-sm sm:text-base">اما شما همیشه می‌توانید به صورت عمومی حمایت کنید.</p>
                    </div>
                </div>
                <?php else: ?>
                    <?php foreach($campaigns as $camp): 
                        $currentAmount = (int)($camp['calc_current_amount'] ?? $camp['current_amount']);
                        $goalAmount = (int)$camp['goal_amount'];
                        $percent = $goalAmount > 0 ? min(100, round(($currentAmount / $goalAmount) * 100)) : 0;
                    ?>
                    <div class="swiper-slide relative h-full group" data-campaign-id="<?php echo $camp['id']; ?>">
                        <div class="absolute inset-0 bg-cover bg-center transition-transform duration-[10s] group-hover:scale-110" style="background-image: url('<?php echo htmlspecialchars($camp['image_url'] ?: 'https://placehold.co/1200x600?text=Campaign'); ?>')"></div>
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/95 via-slate-900/60 to-slate-950/20"></div>
                        
                        <div class="absolute inset-0 p-5 sm:p-8 md:p-12 lg:p-16 flex flex-col justify-end text-white">
                            <div class="max-w-3xl space-y-4 sm:space-y-6">
                                <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                                    <span class="bg-secondary-container text-white px-3.5 py-1 sm:px-4 sm:py-1.5 rounded-full text-[11px] sm:text-xs font-black shadow-lg inline-flex items-center gap-1.5">
                                        <span class="material-symbols-outlined text-xs sm:text-sm">favorite</span> کمپین فعال نیکوکاری
                                    </span>
                                    <span class="inline-flex items-center gap-1 text-[10px] sm:text-[11px] font-bold bg-white/20 backdrop-blur-md px-2.5 sm:px-3 py-1 rounded-full text-emerald-300">
                                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                        آمار زنده
                                    </span>
                                </div>
                                <h2 class="text-2xl sm:text-3xl md:text-5xl lg:text-6xl font-black leading-snug sm:leading-tight"><?php echo htmlspecialchars($camp['title']); ?></h2>
                                <p class="text-xs sm:text-base md:text-lg font-light opacity-90 leading-relaxed max-w-2xl line-clamp-2 sm:line-clamp-none"><?php echo nl2br(htmlspecialchars($camp['description'])); ?></p>
                                
                                <!-- Dynamic Progress Card -->
                                <div class="bg-white/15 backdrop-blur-xl p-4 sm:p-6 rounded-2xl sm:rounded-3xl border border-white/20 mt-4 sm:mt-8 space-y-3 sm:space-y-4 max-w-xl shadow-2xl">
                                    <div class="flex flex-wrap justify-between items-center gap-2 text-xs sm:text-sm font-bold">
                                        <span class="whitespace-nowrap">تامین شده: <strong id="camp-raised-<?php echo $camp['id']; ?>" class="text-emerald-300 text-sm sm:text-base font-black"><?php echo number_format($currentAmount); ?></strong> تومان</span>
                                        <span class="opacity-90 whitespace-nowrap">هدف: <strong id="camp-goal-<?php echo $camp['id']; ?>"><?php echo number_format($goalAmount); ?></strong> تومان</span>
                                    </div>
                                    <div class="w-full h-2.5 sm:h-3.5 bg-white/20 rounded-full overflow-hidden p-0.5">
                                        <div id="camp-bar-<?php echo $camp['id']; ?>" class="h-full bg-gradient-to-r from-emerald-400 to-secondary-container rounded-full transition-all duration-1000 relative overflow-hidden" style="width: <?php echo $percent; ?>%">
                                            <div class="absolute inset-0 bg-white/30 animate-pulse"></div>
                                        </div>
                                    </div>
                                    <div class="flex justify-between items-center text-[11px] sm:text-xs font-bold">
                                        <span id="camp-percent-<?php echo $camp['id']; ?>" class="text-emerald-200 bg-white/15 px-2.5 py-0.5 sm:px-3 sm:py-1 rounded-xl text-xs font-black"><?php echo $percent; ?>%</span>
                                        <span class="text-white/80"><span id="camp-donors-<?php echo $camp['id']; ?>"><?php echo (int)($camp['donor_count'] ?? 0); ?></span> حامی مهربان</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <!-- Pagination Dots (Centered at bottom) -->
            <div class="swiper-pagination !bottom-4 sm:!bottom-6"></div>
            <!-- Custom Navigation Pill (Desktop only, positioned cleanly in bottom-left corner) -->
            <div class="absolute bottom-5 left-5 md:bottom-8 md:left-8 z-20 !hidden md:!flex items-center gap-2">
                <button type="button" aria-label="اسلاید قبلی" class="charity-nav-prev w-11 h-11 rounded-2xl bg-black/40 hover:bg-black/70 backdrop-blur-md text-white flex items-center justify-center transition-all border border-white/15 hover:scale-105 active:scale-95 shadow-lg group cursor-pointer" title="اسلاید قبلی">
                    <span class="material-symbols-outlined text-xl transition-transform group-hover:translate-x-0.5">arrow_forward</span>
                </button>
                <button type="button" aria-label="اسلاید بعدی" class="charity-nav-next w-11 h-11 rounded-2xl bg-black/40 hover:bg-black/70 backdrop-blur-md text-white flex items-center justify-center transition-all border border-white/15 hover:scale-105 active:scale-95 shadow-lg group cursor-pointer" title="اسلاید بعدی">
                    <span class="material-symbols-outlined text-xl transition-transform group-hover:-translate-x-0.5">arrow_back</span>
                </button>
            </div>
        </div>
    </section>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- Donation Form -->
        <div class="lg:col-span-8">
            <div class="bg-white p-8 md:p-12 rounded-[2.5rem] shadow-xl border border-outline-variant/10 relative overflow-hidden">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-16 h-16 bg-primary/10 rounded-2xl flex items-center justify-center text-primary shrink-0">
                        <span class="material-symbols-outlined text-4xl">volunteer_activism</span>
                    </div>
                    <div>
                        <h2 class="text-2xl md:text-3xl font-black text-primary">فرم حمایت و نیکوکاری آنلاین</h2>
                        <p class="text-on-surface-variant text-sm mt-1">با هر مبلغی، دلی را شاد کنید و زندگی دوباره ببخشید.</p>
                    </div>
                </div>

                <form id="charityDonationForm" action="charity_payment.php" method="POST" class="space-y-8">
                    <div class="space-y-3">
                        <label class="font-bold text-primary text-base md:text-lg flex items-center gap-2">
                            <span class="material-symbols-outlined text-secondary-container">campaign</span>
                            انتخاب کمپین نیکوکاری
                        </label>
                        <select name="campaign_id" id="campaignSelect" class="w-full bg-surface-container-low border border-outline-variant/20 rounded-2xl p-4 focus:ring-2 focus:ring-primary text-on-surface font-bold text-sm md:text-base cursor-pointer">
                            <option value="">کمک عمومی به آسنا (بدون کمپین خاص)</option>
                            <?php foreach($campaigns as $camp): ?>
                                <option value="<?php echo $camp['id']; ?>"><?php echo htmlspecialchars($camp['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="space-y-3">
                        <label class="font-bold text-primary text-base md:text-lg flex items-center gap-2">
                            <span class="material-symbols-outlined text-secondary-container">payments</span>
                            مبلغ حمایت (تومان)
                        </label>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                            <button type="button" onclick="setAmount(50000, this)" data-amount="50000" class="btn-amount py-3.5 rounded-2xl border-2 border-outline-variant/30 text-primary font-black text-sm hover:border-primary hover:bg-primary/5 transition-all">۵۰,۰۰۰</button>
                            <button type="button" onclick="setAmount(100000, this)" data-amount="100000" class="btn-amount py-3.5 rounded-2xl border-2 border-outline-variant/30 text-primary font-black text-sm hover:border-primary hover:bg-primary/5 transition-all">۱۰۰,۰۰۰</button>
                            <button type="button" onclick="setAmount(500000, this)" data-amount="500000" class="btn-amount py-3.5 rounded-2xl border-2 border-outline-variant/30 text-primary font-black text-sm hover:border-primary hover:bg-primary/5 transition-all">۵۰۰,۰۰۰</button>
                            <button type="button" onclick="setAmount(1000000, this)" data-amount="1000000" class="btn-amount py-3.5 rounded-2xl border-2 border-outline-variant/30 text-primary font-black text-sm hover:border-primary hover:bg-primary/5 transition-all">۱,۰۰۰,۰۰۰</button>
                        </div>
                        <div class="relative">
                            <input type="number" name="amount" id="customAmount" placeholder="مبلغ دلخواه خود را وارد کنید..." required min="1000" class="w-full bg-surface-container-low border border-outline-variant/20 rounded-2xl p-4 pl-16 focus:ring-2 focus:ring-primary text-on-surface font-black text-lg">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm font-bold">تومان</span>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="font-bold text-primary text-base md:text-lg flex items-center gap-2">
                            <span class="material-symbols-outlined text-secondary-container">badge</span>
                            نام شما (اختیاری)
                        </label>
                        <input type="text" name="donor_name" id="donorNameInput" placeholder="در صورت خالی بودن، نام کاربری یا «نیکوکار ناشناس» ثبت می‌شود" class="w-full bg-surface-container-low border border-outline-variant/20 rounded-2xl p-4 focus:ring-2 focus:ring-primary text-on-surface font-medium">
                        <label class="flex items-center gap-3 mt-4 cursor-pointer select-none">
                            <input type="checkbox" name="is_anonymous" id="isAnonymousCheckbox" class="w-5 h-5 rounded text-primary focus:ring-primary border-outline-variant cursor-pointer">
                            <span class="font-bold text-sm text-on-surface-variant">می‌خواهم نام من نمایش داده نشود و به صورت ناشناس ثبت گردد.</span>
                        </label>
                    </div>

                    <!-- Dual Action Buttons: Instant Test Donation vs Shaparak Gateway -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                        <button type="button" id="btnInstantDonate" onclick="handleInstantDonation()" class="w-full bg-secondary-container hover:bg-secondary text-white py-4 px-6 rounded-2xl font-black text-base shadow-lg shadow-emerald-700/20 hover:shadow-xl hover:scale-[1.01] transition-all flex justify-center items-center gap-2 active:scale-95 cursor-pointer">
                            <span class="material-symbols-outlined text-2xl">bolt</span>
                            <span>ثبت و پرداخت آنی (تست فوری)</span>
                        </button>
                        <button type="submit" id="btnGatewayDonate" class="w-full bg-primary hover:bg-primary-container text-white py-4 px-6 rounded-2xl font-black text-base shadow-lg shadow-primary/20 hover:shadow-xl hover:scale-[1.01] transition-all flex justify-center items-center gap-2 active:scale-95 cursor-pointer">
                            <span class="material-symbols-outlined text-2xl">credit_card</span>
                            <span>انتقال به درگاه بانکی شاپرک</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Sidebar (Top Donors & Recent Donations) -->
        <div class="lg:col-span-4 space-y-8 sticky top-28">
            
            <!-- Top Donors -->
            <div class="bg-primary text-white p-7 md:p-8 rounded-[2.5rem] shadow-2xl relative overflow-hidden">
                <div class="absolute -right-10 -bottom-10 opacity-10 pointer-events-none">
                    <span class="material-symbols-outlined text-[200px]">workspace_premium</span>
                </div>
                <div class="flex items-center justify-between mb-6 relative z-10">
                    <h3 class="text-xl font-bold flex items-center gap-2.5">
                        <span class="material-symbols-outlined text-secondary-container text-2xl">stars</span>
                        قهرمانان برتر ما
                    </h3>
                    <span class="text-[11px] bg-white/10 px-2.5 py-1 rounded-full text-white/80 font-bold">رتبه‌بندی حامیان</span>
                </div>
                
                <div id="topDonorsList" class="space-y-3.5 relative z-10 transition-all">
                    <?php if(empty($topDonors)): ?>
                    <p class="text-white/70 text-sm" id="noTopDonorsMsg">هنوز رکوردی ثبت نشده است.</p>
                    <?php else: ?>
                        <?php foreach($topDonors as $i => $donor): 
                            $rank = $i + 1;
                            $medal = ($rank === 1) ? '🥇' : (($rank === 2) ? '🥈' : (($rank === 3) ? '🥉' : $rank));
                            $badgeClass = ($rank === 1) ? 'bg-gradient-to-r from-amber-400 to-yellow-500 text-slate-900 border-amber-300 ring-2 ring-amber-400/30' : (($rank === 2) ? 'bg-gradient-to-r from-slate-200 to-slate-400 text-slate-900' : (($rank === 3) ? 'bg-gradient-to-r from-amber-700 to-orange-800 text-white' : 'bg-secondary-container text-white'));
                        ?>
                        <div class="donor-card-item flex items-center gap-3.5 bg-white/10 hover:bg-white/15 p-3.5 rounded-2xl backdrop-blur-sm border border-white/10 transition-all duration-300">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center font-black text-sm shadow-inner shrink-0 <?php echo $badgeClass; ?>">
                                <?php echo $medal; ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-bold text-sm truncate"><?php echo htmlspecialchars($donor['donor_name']); ?></h4>
                                <p class="text-xs text-emerald-300 font-black mt-0.5"><?php echo number_format($donor['total_donated']); ?> تومان</p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Donations -->
            <div class="bg-white p-7 md:p-8 rounded-[2.5rem] shadow-xl border border-outline-variant/10">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-primary flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary-container">history</span>
                        آخرین حمایت‌ها
                    </h3>
                    <span class="inline-flex items-center gap-1.5 text-[11px] font-bold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-200/60">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        به‌روزرسانی لحظه‌ای
                    </span>
                </div>
                
                <div id="recentDonationsList" class="space-y-4 max-h-[520px] overflow-y-auto pr-1">
                    <?php if(empty($recentDonations)): ?>
                    <p class="text-on-surface-variant text-sm" id="noRecentDonationsMsg">هنوز حمایتی ثبت نشده است.</p>
                    <?php else: ?>
                        <?php foreach($recentDonations as $recent): 
                            $isAnon = ($recent['donor_name'] === 'ناشناس' || empty($recent['donor_name']));
                        ?>
                        <div class="recent-donation-item flex items-start gap-3.5 border-b border-outline-variant/20 last:border-0 pb-3.5 last:pb-0 transition-all duration-300">
                            <div class="w-10 h-10 rounded-xl <?php echo $isAnon ? 'bg-slate-100 text-slate-400' : 'bg-primary/10 text-primary'; ?> flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-xl">person</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <h4 class="font-bold text-on-surface text-sm truncate"><?php echo htmlspecialchars($recent['donor_name']); ?></h4>
                                    <span class="text-[10px] text-slate-400 shrink-0"><?php echo htmlspecialchars(date('H:i', strtotime($recent['created_at']))); ?></span>
                                </div>
                                <div class="text-xs text-on-surface-variant mt-0.5 truncate">
                                    حمایت از: <span class="text-primary font-bold"><?php echo htmlspecialchars($recent['campaign_title'] ?: 'عمومی'); ?></span>
                                </div>
                                <div class="text-xs font-black text-secondary-container mt-1.5"><?php echo number_format($recent['amount']); ?> تومان</div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    </div>
</main>

<!-- In-Page Thank You Celebration Modal -->
<div id="charityThankYouModal" class="fixed inset-0 z-[10060] hidden items-center justify-center p-4 bg-black/60 backdrop-blur-sm rtl text-right" onclick="if(event.target === this) closeThankYouModal();">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden border border-slate-100 transform transition-all animate-fade-in flex flex-col">
        <div class="p-6 text-center space-y-4 bg-gradient-to-b from-emerald-50/80 to-white">
            <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto shadow-inner">
                <span class="material-symbols-outlined text-3xl">volunteer_activism</span>
            </div>
            <div>
                <h3 class="text-xl font-black text-slate-800">صمیمانه سپاسگزاریم!</h3>
                <p class="text-xs text-slate-500 mt-1">حمایت نیکوکارانه شما با موفقیت ثبت گردید و اعمال شد.</p>
            </div>
            
            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/60 space-y-2 text-xs">
                <div class="flex justify-between">
                    <span class="text-slate-500">حامی نیکوکار:</span>
                    <strong id="modalDonorName" class="text-slate-800 font-black">—</strong>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">مبلغ اهدایی:</span>
                    <strong id="modalAmount" class="text-emerald-600 font-black text-sm">—</strong>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">کمپین:</span>
                    <strong id="modalCampaign" class="text-primary font-bold truncate max-w-[200px]">—</strong>
                </div>
                <div class="flex justify-between pt-2 border-t border-slate-200/60 text-[11px]">
                    <span class="text-slate-400">کد رهگیری تراکنش:</span>
                    <span id="modalRefId" class="font-mono text-slate-600 font-bold">—</span>
                </div>
            </div>

            <button type="button" onclick="closeThankYouModal()" class="w-full bg-primary text-white py-3.5 rounded-2xl font-black text-sm hover:bg-primary-container transition-all active:scale-95 shadow-md">
                مشاهده آمار و ادامه
            </button>
        </div>
    </div>
</div>

<!-- Swiper JS -->
<script src="assets/js/swiper-bundle.min.js"></script>

<script>
    // 1. Initialize Swiper
    const swiper = new Swiper('.charitySwiper', {
        loop: true,
        autoplay: {
            delay: 6000,
            disableOnInteraction: false,
        },
        pagination: {
            el: '.swiper-pagination',
            clickable: true,
        },
        navigation: {
            nextEl: '.charity-nav-next',
            prevEl: '.charity-nav-prev',
        },
        effect: 'fade',
        fadeEffect: {
            crossFade: true
        }
    });

    // 2. Swiper <-> Select Bi-Directional Synchronization
    swiper.on('slideChange', function() {
        const activeSlide = swiper.slides[swiper.activeIndex];
        if (activeSlide) {
            const campId = activeSlide.getAttribute('data-campaign-id');
            const select = document.getElementById('campaignSelect');
            if (select && campId && select.value !== campId) {
                select.value = campId;
            }
        }
    });

    document.getElementById('campaignSelect')?.addEventListener('change', function(e) {
        const val = e.target.value;
        if (!val) return;
        const slides = document.querySelectorAll('.charitySwiper .swiper-slide[data-campaign-id]');
        slides.forEach((slide, idx) => {
            if (slide.getAttribute('data-campaign-id') === val) {
                swiper.slideToLoop(idx);
            }
        });
    });

    // 3. Preset Amount Button Selection
    function setAmount(amount, btnElement) {
        const input = document.getElementById('customAmount');
        if (input) {
            input.value = amount;
        }
        document.querySelectorAll('.btn-amount').forEach(btn => {
            btn.classList.remove('bg-primary', 'text-white', 'border-primary', 'shadow-md');
            btn.classList.add('border-outline-variant/30', 'text-primary');
        });
        if (btnElement) {
            btnElement.classList.remove('border-outline-variant/30', 'text-primary');
            btnElement.classList.add('bg-primary', 'text-white', 'border-primary', 'shadow-md');
        }
    }

    document.getElementById('customAmount')?.addEventListener('input', function(e) {
        const val = parseInt(e.target.value, 10);
        let matched = false;
        document.querySelectorAll('.btn-amount').forEach(btn => {
            const btnAmt = parseInt(btn.getAttribute('data-amount'), 10);
            if (btnAmt === val) {
                btn.classList.remove('border-outline-variant/30', 'text-primary');
                btn.classList.add('bg-primary', 'text-white', 'border-primary', 'shadow-md');
                matched = true;
            } else {
                btn.classList.remove('bg-primary', 'text-white', 'border-primary', 'shadow-md');
                btn.classList.add('border-outline-variant/30', 'text-primary');
            }
        });
    });

    // 4. Number Smooth Count-Up Animation
    function animateNumber(element, start, end, duration = 1200) {
        if (!element) return;
        if (isNaN(start) || isNaN(end) || start === end) {
            element.textContent = Number(end).toLocaleString('fa-IR');
            return;
        }
        const startTime = performance.now();
        function update(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            // Easing Out Cubic
            const easeOut = 1 - Math.pow(1 - progress, 3);
            const current = Math.round(start + (end - start) * easeOut);
            element.textContent = Number(current).toLocaleString('fa-IR');
            if (progress < 1) {
                requestAnimationFrame(update);
            } else {
                element.textContent = Number(end).toLocaleString('fa-IR');
            }
        }
        requestAnimationFrame(update);
    }

    // 5. Lightweight Canvas Confetti Burst
    function fireConfetti() {
        const canvas = document.getElementById('charityConfettiCanvas');
        if (!canvas) return;
        canvas.classList.remove('hidden');
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
        const ctx = canvas.getContext('2d');

        const pieces = [];
        const colors = ['#10b981', '#059669', '#3b82f6', '#f59e0b', '#ec4899', '#8b5cf6', '#14b8a6'];
        for (let i = 0; i < 90; i++) {
            pieces.push({
                x: canvas.width / 2 + (Math.random() - 0.5) * 200,
                y: canvas.height * 0.45,
                vx: (Math.random() - 0.5) * 16,
                vy: (Math.random() - 0.9) * 18,
                size: Math.random() * 8 + 4,
                color: colors[Math.floor(Math.random() * colors.length)],
                rotation: Math.random() * 360,
                rotSpeed: (Math.random() - 0.5) * 10
            });
        }

        let animationFrame;
        const startTime = performance.now();
        function draw(now) {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            let alive = false;
            pieces.forEach(p => {
                p.x += p.vx;
                p.y += p.vy;
                p.vy += 0.45; // gravity
                p.rotation += p.rotSpeed;
                if (p.y < canvas.height + 50) {
                    alive = true;
                    ctx.save();
                    ctx.translate(p.x, p.y);
                    ctx.rotate((p.rotation * Math.PI) / 180);
                    ctx.fillStyle = p.color;
                    ctx.fillRect(-p.size / 2, -p.size / 2, p.size, p.size);
                    ctx.restore();
                }
            });

            if (alive && (now - startTime < 3500)) {
                animationFrame = requestAnimationFrame(draw);
            } else {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                canvas.classList.add('hidden');
            }
        }
        animationFrame = requestAnimationFrame(draw);
    }

    // 6. Dynamic Live Data Management & Real-Time Poller
    let currentCampaignsCache = {};

    async function fetchAndRenderLiveData(isUserAction = false) {
        try {
            const res = await fetch('actions/charity_live_data.php?t=' + Date.now());
            if (!res.ok) return;
            const data = await res.json();
            if (!data.success) return;

            // A. Update Active Campaigns (Raised, Goal, Progress Bar, Percent, Donors)
            if (data.campaigns && data.campaigns.length) {
                data.campaigns.forEach(camp => {
                    const raisedEl = document.getElementById('camp-raised-' + camp.id);
                    const barEl = document.getElementById('camp-bar-' + camp.id);
                    const percentEl = document.getElementById('camp-percent-' + camp.id);
                    const donorsEl = document.getElementById('camp-donors-' + camp.id);

                    const prev = currentCampaignsCache[camp.id];
                    const prevAmount = prev ? prev.current_amount : camp.current_amount;

                    if (raisedEl) {
                        if (isUserAction || (prev && prev.current_amount !== camp.current_amount)) {
                            animateNumber(raisedEl, prevAmount, camp.current_amount);
                        } else {
                            raisedEl.textContent = Number(camp.current_amount).toLocaleString('fa-IR');
                        }
                    }

                    if (barEl) {
                        barEl.style.width = camp.percent + '%';
                    }

                    if (percentEl) {
                        percentEl.textContent = camp.percent + '%';
                    }

                    if (donorsEl) {
                        donorsEl.textContent = Number(camp.donor_count).toLocaleString('fa-IR');
                    }

                    currentCampaignsCache[camp.id] = camp;
                });
            }

            // B. Update Top Champions
            if (data.top_donors) {
                renderTopDonors(data.top_donors);
            }

            // C. Update Recent Donations
            if (data.recent_donations) {
                renderRecentDonations(data.recent_donations);
            }

        } catch (err) {
            console.warn('[Charity Poller] Error:', err);
        }
    }

    function renderTopDonors(donors) {
        const container = document.getElementById('topDonorsList');
        if (!container) return;

        if (!donors || donors.length === 0) {
            container.innerHTML = '<p class="text-white/70 text-sm" id="noTopDonorsMsg">هنوز رکوردی ثبت نشده است.</p>';
            return;
        }

        let html = '';
        donors.forEach((donor, idx) => {
            const rank = idx + 1;
            const medal = (rank === 1) ? '🥇' : ((rank === 2) ? '🥈' : ((rank === 3) ? '🥉' : rank));
            const badgeClass = (rank === 1) 
                ? 'bg-gradient-to-r from-amber-400 to-yellow-500 text-slate-900 border-amber-300 ring-2 ring-amber-400/30'
                : ((rank === 2) 
                    ? 'bg-gradient-to-r from-slate-200 to-slate-400 text-slate-900' 
                    : ((rank === 3) 
                        ? 'bg-gradient-to-r from-amber-700 to-orange-800 text-white' 
                        : 'bg-secondary-container text-white'));

            html += `
                <div class="donor-card-item flex items-center gap-3.5 bg-white/10 hover:bg-white/15 p-3.5 rounded-2xl backdrop-blur-sm border border-white/10 transition-all duration-300">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center font-black text-sm shadow-inner shrink-0 ${badgeClass}">
                        ${medal}
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="font-bold text-sm truncate">${escapeHtml(donor.donor_name)}</h4>
                        <p class="text-xs text-emerald-300 font-black mt-0.5">${Number(donor.total_donated).toLocaleString('fa-IR')} تومان</p>
                    </div>
                </div>
            `;
        });
        container.innerHTML = html;
    }

    function renderRecentDonations(donations) {
        const container = document.getElementById('recentDonationsList');
        if (!container) return;

        if (!donations || donations.length === 0) {
            container.innerHTML = '<p class="text-on-surface-variant text-sm" id="noRecentDonationsMsg">هنوز حمایتی ثبت نشده است.</p>';
            return;
        }

        let html = '';
        donations.forEach(d => {
            const boxClass = d.is_anonymous ? 'bg-slate-100 text-slate-400' : 'bg-primary/10 text-primary';
            html += `
                <div class="recent-donation-item flex items-start gap-3.5 border-b border-outline-variant/20 last:border-0 pb-3.5 last:pb-0 transition-all duration-300">
                    <div class="w-10 h-10 rounded-xl ${boxClass} flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-xl">person</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2">
                            <h4 class="font-bold text-on-surface text-sm truncate">${escapeHtml(d.donor_name)}</h4>
                            <span class="text-[10px] text-slate-400 shrink-0">${escapeHtml(d.relative_time)}</span>
                        </div>
                        <div class="text-xs text-on-surface-variant mt-0.5 truncate">
                            حمایت از: <span class="text-primary font-bold">${escapeHtml(d.campaign_title)}</span>
                        </div>
                        <div class="text-xs font-black text-secondary-container mt-1.5">${Number(d.amount).toLocaleString('fa-IR')} تومان</div>
                    </div>
                </div>
            `;
        });
        container.innerHTML = html;
    }

    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.toString().replace(/[&<>"']/g, m => map[m]);
    }

    // 7. Instant In-Page Donation Handler
    async function handleInstantDonation() {
        const amountInput = document.getElementById('customAmount');
        const amount = parseInt(amountInput?.value || '0', 10);
        if (isNaN(amount) || amount < 1000) {
            alert('لطفاً مبلغ معتبری (حداقل ۱,۰۰۰ تومان) وارد نمایید.');
            amountInput?.focus();
            return;
        }

        const campaignSelect = document.getElementById('campaignSelect');
        const campaignId = campaignSelect?.value || '';
        const campaignText = campaignSelect?.options[campaignSelect.selectedIndex]?.text || 'کمک عمومی به آسنا';
        const donorName = document.getElementById('donorNameInput')?.value.trim() || '';
        const isAnonymous = document.getElementById('isAnonymousCheckbox')?.checked;

        const btn = document.getElementById('btnInstantDonate');
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="material-symbols-outlined animate-spin text-xl">progress_activity</span><span>در حال ثبت...</span>';

        try {
            const formData = new FormData();
            formData.append('amount', amount);
            formData.append('campaign_id', campaignId);
            formData.append('donor_name', donorName);
            formData.append('is_anonymous', isAnonymous ? '1' : '0');

            const res = await fetch('actions/charity_ajax_donate.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.success) {
                // Show celebration modal
                document.getElementById('modalDonorName').textContent = isAnonymous ? 'ناشناس' : (donorName || 'نیکوکار ناشناس');
                document.getElementById('modalAmount').textContent = Number(amount).toLocaleString('fa-IR') + ' تومان';
                document.getElementById('modalCampaign').textContent = campaignText;
                document.getElementById('modalRefId').textContent = data.ref_id;

                const modal = document.getElementById('charityThankYouModal');
                if (modal) {
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                }

                // Fire celebratory confetti!
                fireConfetti();

                // Trigger live updates with smooth numbers
                await fetchAndRenderLiveData(true);

                // Reset form inputs
                if (amountInput) amountInput.value = '';
                document.querySelectorAll('.btn-amount').forEach(b => {
                    b.classList.remove('bg-primary', 'text-white', 'border-primary', 'shadow-md');
                    b.classList.add('border-outline-variant/30', 'text-primary');
                });
            } else {
                alert(data.error || 'خطا در ثبت حمایت.');
            }

        } catch (err) {
            console.error(err);
            alert('خطا در برقراری ارتباط با سرور.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    }

    function closeThankYouModal() {
        const modal = document.getElementById('charityThankYouModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    // 8. Start Background Polling Every 4 Seconds
    document.addEventListener('DOMContentLoaded', () => {
        fetchAndRenderLiveData(false);
        setInterval(() => {
            fetchAndRenderLiveData(false);
        }, 4000);
    });
</script>

<?php include 'includes/footer.php'; ?>
