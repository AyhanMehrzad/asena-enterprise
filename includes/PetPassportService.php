<?php
/**
 * ASENA Enterprise - Pet Health Passport & Clinical Dossier Service
 * Benchmarked against Chewy.com "Connect With a Vet" & Pet Health Profile
 */

class PetPassportService
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        if ($pdo) {
            $this->pdo = $pdo;
        } else {
            global $pdo;
            $this->pdo = $pdo;
        }
    }

    /**
     * Retrieve all pets belonging to a user account.
     */
    public function getPetsByUser(int $userId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM pet_health_records 
            WHERE user_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get single pet profile by ID with ownership verification.
     */
    public function getPet(int $petId, ?int $userId = null): ?array
    {
        $sql = "SELECT * FROM pet_health_records WHERE id = ?";
        $params = [$petId];
        if ($userId !== null) {
            $sql .= " AND user_id = ?";
            $params[] = $userId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    /**
     * Create or update pet health passport.
     */
    public function savePet(int $userId, array $data, ?int $petId = null): int
    {
        $fields = [
            'pet_name'           => trim($data['pet_name'] ?? 'پت من'),
            'species'            => in_array($data['species'] ?? '', ['dog', 'cat', 'bird', 'horse', 'cow', 'rabbit', 'other']) ? $data['species'] : 'dog',
            'breed'              => trim($data['breed'] ?? ''),
            'gender'             => in_array($data['gender'] ?? '', ['male', 'female', 'neutered_male', 'spayed_female']) ? $data['gender'] : 'male',
            'birth_date'         => !empty($data['birth_date']) ? $data['birth_date'] : null,
            'weight_kg'          => !empty($data['weight_kg']) ? (float)$data['weight_kg'] : null,
            'microchip_id'       => trim($data['microchip_id'] ?? ''),
            'allergies'          => trim($data['allergies'] ?? ''),
            'chronic_conditions' => trim($data['chronic_conditions'] ?? ''),
            'rabies_tag_num'     => trim($data['rabies_tag_num'] ?? ''),
            'avatar_url'         => trim($data['avatar_url'] ?? ''),
        ];

        if ($petId) {
            // Update
            $stmt = $this->pdo->prepare("
                UPDATE pet_health_records SET
                    pet_name = ?, species = ?, breed = ?, gender = ?, birth_date = ?,
                    weight_kg = ?, microchip_id = ?, allergies = ?, chronic_conditions = ?,
                    rabies_tag_num = ?, avatar_url = ?
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([
                $fields['pet_name'], $fields['species'], $fields['breed'], $fields['gender'],
                $fields['birth_date'], $fields['weight_kg'], $fields['microchip_id'],
                $fields['allergies'], $fields['chronic_conditions'], $fields['rabies_tag_num'],
                $fields['avatar_url'], $petId, $userId
            ]);
            return $petId;
        } else {
            // Insert
            $stmt = $this->pdo->prepare("
                INSERT INTO pet_health_records 
                    (user_id, pet_name, species, breed, gender, birth_date, weight_kg, microchip_id, allergies, chronic_conditions, rabies_tag_num, avatar_url)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $userId, $fields['pet_name'], $fields['species'], $fields['breed'], $fields['gender'],
                $fields['birth_date'], $fields['weight_kg'], $fields['microchip_id'],
                $fields['allergies'], $fields['chronic_conditions'], $fields['rabies_tag_num'],
                $fields['avatar_url']
            ]);
            return (int)$this->pdo->lastInsertId();
        }
    }

    /**
     * Add vaccination record for a pet.
     */
    public function addVaccination(int $petId, array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO pet_vaccinations 
                (pet_id, vaccine_name, administered_date, next_due_date, vet_name, clinic_name, batch_number, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $petId,
            trim($data['vaccine_name'] ?? 'واکسن چندگانه'),
            $data['administered_date'] ?? date('Y-m-d'),
            !empty($data['next_due_date']) ? $data['next_due_date'] : null,
            trim($data['vet_name'] ?? ''),
            trim($data['clinic_name'] ?? ''),
            trim($data['batch_number'] ?? ''),
            trim($data['notes'] ?? ''),
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Retrieve all vaccinations for a pet.
     */
    public function getVaccinations(int $petId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM pet_vaccinations 
            WHERE pet_id = ? 
            ORDER BY administered_date DESC
        ");
        $stmt->execute([$petId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieve upcoming vaccinations due within N days across all user's pets.
     */
    public function getUpcomingVaccinationReminders(int $userId, int $daysWindow = 30): array
    {
        $stmt = $this->pdo->prepare("
            SELECT v.*, p.pet_name, p.species, p.breed
            FROM pet_vaccinations v
            JOIN pet_health_records p ON v.pet_id = p.id
            WHERE p.user_id = ?
              AND v.next_due_date IS NOT NULL
              AND v.next_due_date BETWEEN CURRENT_DATE AND DATE_ADD(CURRENT_DATE, INTERVAL ? DAY)
            ORDER BY v.next_due_date ASC
        ");
        $stmt->execute([$userId, $daysWindow]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Chewy Clinical Tool: Veterinary Weight-Based Dosage Calculator
     * Calculates recommended dosage according to species, weight, and clinical formulation.
     */
    public static function calculateDosage(string $drugCategory, float $weightKg, string $species = 'dog'): array
    {
        if ($weightKg <= 0) {
            return ['error' => 'وزن وارد شده باید بزرگتر از صفر باشد.'];
        }

        switch (strtolower($drugCategory)) {
            case 'dewormer': // قرص ضد انگل (پرازیکوانتل / پیرانتل: ۱ قرص به ازای هر ۱۰ کیلوگرم)
                $tablets = max(0.25, round($weightKg / 10, 2));
                return [
                    'drug'        => 'ضد انگل وسیع‌الطیف (Praziquantel/Pyrantel)',
                    'dosage'      => "{$tablets} عدد قرص",
                    'frequency'   => 'تکرار هر ۳ ماه یک‌بار برای سگ و گربه بالغ',
                    'instructions'=> 'همراه با مقدار کمی غذا خورانده شود.',
                    'safe_limit'  => 'برای توله‌های زیر ۲ هفته یا زیر ۱ کیلوگرم با احتیاط مصرف شود.'
                ];

            case 'flea_tick': // کک و کنه موضعی یا خوراکی (براوکتو / نکست‌گارد / سیمپاریکا)
                $band = '';
                if ($weightKg < 4.5) $band = '۲ تا ۴.۵ کیلوگرم (بسیار کوچک)';
                elseif ($weightKg < 10) $band = '۴.۵ تا ۱۰ کیلوگرم (کوچک)';
                elseif ($weightKg < 20) $band = '۱۰ تا ۲۰ کیلوگرم (متوسط)';
                elseif ($weightKg < 40) $band = '۲۰ تا ۴۰ کیلوگرم (بزرگ)';
                else $band = '۴۰ تا ۵۶ کیلوگرم (بسیار بزرگ)';

                return [
                    'drug'        => 'محافظت ضد کک و کنه (Fluralaner / Afoxolaner)',
                    'dosage'      => "۱ دوز مخصوص رده وزنی {$band}",
                    'frequency'   => ($drugCategory === 'bravecto') ? 'هر ۱۲ هفته یک‌بار' : 'هر ۳۰ روز یک‌بار',
                    'instructions'=> 'قرص جویدنی را در زمان یا بلافاصله پس از غذا به حیوان بدهید.',
                    'safe_limit'  => 'برای توله‌های بالای ۸ هفته با حداقل وزن ۲ کیلوگرم.'
                ];

            case 'antibiotic': // آموکسی‌سیلین کلاوولانات (12.5 - 20 mg/kg دو بار در روز)
                $doseMg = round($weightKg * 13.75, 1);
                return [
                    'drug'        => 'آموکسی‌سیلین کلاوولانات (Amoxicillin-Clavulanate)',
                    'dosage'      => "{$doseMg} میلی‌گرم در هر وعده",
                    'frequency'   => 'هر ۱۲ ساعت (۲ بار در روز) به مدت ۵ تا ۷ روز',
                    'instructions'=> 'دوره درمان باید به طور کامل حتی پس از بهبود علائم ادامه یابد.',
                    'safe_limit'  => 'در بیماران با نارسایی کلیوی دوز باید توسط دامپزشک تعدیل گردد.'
                ];

            case 'pain_relief': // ملوکسیکام (0.1 mg/kg روزانه برای سگ)
                $meloxMg = round($weightKg * 0.1, 2);
                return [
                    'drug'        => 'ملوکسیکام ضدالتهاب غیراستروئیدی (Meloxicam)',
                    'dosage'      => "{$meloxMg} میلی‌گرم در روز",
                    'frequency'   => 'روزی یک‌بار همراه با وعده اصلی غذایی',
                    'instructions'=> 'به هیچ عنوان با معده خالی مصرف نشود. آب آشامیدنی در دسترس باشد.',
                    'safe_limit'  => 'در گربه‌ها با دوز بسیار پایین‌تر (0.05 mg/kg) و تنها با تجویز مستقیم پزشک.'
                ];

            default:
                return [
                    'drug'        => 'محاسبه‌گر عمومی داروهای دامپزشکی',
                    'dosage'      => 'طبق دستور مندرج بر روی بسته‌بندی دارو متناسب با وزن حیوان',
                    'frequency'   => 'مشاوره با داروساز یا دکتر دامپزشک الزامی است.',
                    'instructions'=> 'قبل از مصرف بروشور رسمی دارو را مطالعه فرمایید.',
                    'safe_limit'  => 'دارو را دور از دسترس کودکان نگهداری فرمایید.'
                ];
        }
    }
}
