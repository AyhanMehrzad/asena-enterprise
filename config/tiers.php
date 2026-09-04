<?php
/**
 * ASENA Enterprise - Tier & Feature Definitions
 * 
 * Defines the capability matrix for all editions of the platform.
 * Single source of truth for features across storefront, admin, and doctor views.
 */

return [
    'tiers' => [
        'basic' => [
            'name'        => 'نسخه پایه کلینیک و پت‌شاپ',
            'code'        => 'basic',
            'description' => 'سیستم اختصاصی نوبت‌دهی آنلاین و فروشگاه ملزومات حیوانات خانگی',
            'features'    => [
                'clinic_booking',
                'petshop_catalog',
                'user_profile',
                'basic_cart',
                'online_payment',
            ],
        ],
        'standard' => [
            'name'        => 'نسخه استاندارد تجاری',
            'code'        => 'standard',
            'description' => 'کلینیک و پت‌شاپ مجهز به دانشنامه تخصصی، سیستم امتیازدهی و باشگاه وفاداری',
            'features'    => [
                'clinic_booking',
                'petshop_catalog',
                'user_profile',
                'basic_cart',
                'online_payment',
                'blog_engine',
                'loyalty_points',
                'reviews',
            ],
        ],
        'premium' => [
            'name'        => 'نسخه حرفه‌ای فول کلینیک',
            'code'        => 'premium',
            'description' => 'پلتفرم جامع کلینیک با سفارش دوره‌ای خودکار (Autoship)، رتبه‌بندی بیزی، تله‌هلث و بسته‌های اشتراکی',
            'features'    => [
                'clinic_booking',
                'petshop_catalog',
                'user_profile',
                'basic_cart',
                'online_payment',
                'blog_engine',
                'loyalty_points',
                'reviews',
                'autoship',
                'custom_boxes',
                'bayesian_reviews',
                'telehealth_chat',
                'charity_campaigns',
                'sms_automation',
            ],
        ],
        'pharmacy' => [
            'name'        => 'نسخه تخصصی داروخانه دامپزشکی',
            'code'        => 'pharmacy',
            'description' => 'داروخانه آنلاین داروهای دام، طیور و پت با آپلود نسخه الکترونیک، زنجیره سرد و پنل داروساز',
            'features'    => [
                'pharmacy_catalog',
                'prescription_rx',
                'cold_chain_dispatch',
                'pharmacist_panel',
                'autoship',
                'bayesian_reviews',
                'user_profile',
                'basic_cart',
                'online_payment',
                'sms_automation',
            ],
        ],
        'enterprise' => [
            'name'        => 'نسخه اینترپرایز جامع (فول اکوسیستم)',
            'code'        => 'enterprise',
            'description' => 'سامانه همه‌جانبه کلینیک دامپزشکی، پت‌شاپ آنلاین و داروخانه تخصصی با تمامی امکانات',
            'features'    => [
                'clinic_booking',
                'petshop_catalog',
                'pharmacy_catalog',
                'prescription_rx',
                'cold_chain_dispatch',
                'pharmacist_panel',
                'user_profile',
                'basic_cart',
                'online_payment',
                'blog_engine',
                'loyalty_points',
                'reviews',
                'autoship',
                'custom_boxes',
                'bayesian_reviews',
                'telehealth_chat',
                'charity_campaigns',
                'sms_automation',
            ],
        ],
    ],
    // Default tier if not specified in .env
    'default_tier' => 'enterprise',
];
