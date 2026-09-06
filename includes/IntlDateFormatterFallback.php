<?php
/**
 * ASENA Enterprise - IntlDateFormatter Polyfill / Fallback
 * Provides Persian (Jalali) date formatting when php_intl extension is disabled or missing.
 */

if (!class_exists('IntlDateFormatter')) {

    class IntlDateFormatter
    {
        public const FULL = 0;
        public const LONG = 1;
        public const MEDIUM = 2;
        public const SHORT = 3;
        public const NONE = -1;
        public const TRADITIONAL = 0;
        public const GREGORIAN = 1;

        private ?string $locale;
        private int $dateType;
        private int $timeType;
        private $timezone;
        private $calendar;
        private string $pattern;

        public function __construct(
            ?string $locale = null,
            int $dateType = self::FULL,
            int $timeType = self::FULL,
            $timezone = null,
            $calendar = self::GREGORIAN,
            ?string $pattern = null
        ) {
            $this->locale = $locale ?? 'fa_IR';
            $this->dateType = $dateType;
            $this->timeType = $timeType;
            $this->timezone = $timezone ?? 'Asia/Tehran';
            $this->calendar = $calendar;
            $this->pattern = $pattern ?? 'yyyy/MM/dd';
        }

        public function format($datetime): string
        {
            if (is_numeric($datetime)) {
                $dt = new DateTime('@' . (int)$datetime);
                $dt->setTimezone(new DateTimeZone($this->timezone ?: 'Asia/Tehran'));
            } elseif ($datetime instanceof DateTimeInterface) {
                $dt = clone $datetime;
                $dt->setTimezone(new DateTimeZone($this->timezone ?: 'Asia/Tehran'));
            } elseif (is_string($datetime)) {
                try {
                    $dt = new DateTime($datetime, new DateTimeZone($this->timezone ?: 'Asia/Tehran'));
                } catch (\Exception $e) {
                    return (string)$datetime;
                }
            } else {
                return '';
            }

            $gy = (int)$dt->format('Y');
            $gm = (int)$dt->format('m');
            $gd = (int)$dt->format('d');
            $hour = $dt->format('H');
            $min = $dt->format('i');
            $sec = $dt->format('s');

            list($jy, $jm, $jd) = self::gregorianToJalali($gy, $gm, $gd);

            $persianMonths = [
                1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد',
                4 => 'تیر', 5 => 'مرداد', 6 => 'شهریور',
                7 => 'مهر', 8 => 'آبان', 9 => 'آذر',
                10 => 'دی', 11 => 'بهمن', 12 => 'اسفند'
            ];

            $persianDays = [
                0 => 'یکشنبه', 1 => 'دوشنبه', 2 => 'سه‌شنبه',
                3 => 'چهارشنبه', 4 => 'پنج‌شنبه', 5 => 'جمعه', 6 => 'شنبه'
            ];

            $dayOfWeek = (int)$dt->format('w');
            $monthName = $persianMonths[$jm] ?? '';
            $dayName = $persianDays[$dayOfWeek] ?? '';

            $formatted = $this->pattern;

            // Replace patterns
            $replacements = [
                'yyyy' => sprintf('%04d', $jy),
                'YYYY' => sprintf('%04d', $jy),
                'yy'   => sprintf('%02d', $jy % 100),
                'MMMM' => $monthName,
                'MM'   => sprintf('%02d', $jm),
                'M'    => (string)$jm,
                'dd'   => sprintf('%02d', $jd),
                'd'    => (string)$jd,
                'EEEE' => $dayName,
                'HH'   => $hour,
                'H'    => (string)(int)$hour,
                'mm'   => $min,
                'm'    => (string)(int)$min,
                'ss'   => $sec,
                's'    => (string)(int)$sec
            ];

            return strtr($formatted, $replacements);
        }

        public function setPattern(string $pattern): void
        {
            $this->pattern = $pattern;
        }

        public function getPattern(): string
        {
            return $this->pattern;
        }

        /**
         * Standard Gregorian to Jalali calendar conversion
         */
        public static function gregorianToJalali(int $gy, int $gm, int $gd): array
        {
            $g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
            $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
            $days = 355666 + (365 * $gy) + ((int)(($gy2 + 3) / 4)) - ((int)(($gy2 + 99) / 100)) + ((int)(($gy2 + 399) / 400)) + $gd + $g_d_m[$gm - 1];
            $jy = -1595 + (33 * ((int)($days / 12053)));
            $days %= 12053;
            $jy += 4 * ((int)($days / 1461));
            $days %= 1461;
            if ($days > 365) {
                $jy += (int)(($days - 1) / 365);
                $days = ($days - 1) % 365;
            }
            if ($days < 186) {
                $jm = 1 + (int)($days / 31);
                $jd = 1 + ($days % 31);
            } else {
                $jm = 7 + (int)(($days - 186) / 30);
                $jd = 1 + (($days - 186) % 30);
            }
            return [$jy, $jm, $jd];
        }
    }
}
