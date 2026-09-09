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
     * Veterinary Body Condition Score (BCS 1-9) & Nutritional Energy Calculator
     * Replaces dangerous and unauthorized drug dosage prescription with standard veterinary
     * Body Condition Scoring (WSAVA standard), Resting/Maintenance Energy Requirements (RER/MER),
     * and daily hydration guidelines.
     */
    public static function calculateHealthAndBcs(float $weightKg, string $species = 'dog', ?int $ageYears = null, string $activityLevel = 'normal_neutered'): array
    {
        if ($weightKg <= 0) {
            return ['error' => 'وزن وارد شده باید بزرگتر از صفر باشد.'];
        }

        $species = strtolower($species) === 'cat' ? 'cat' : 'dog';

        // 1. Resting Energy Requirement (RER = 70 * weight^0.75 in kcal/day)
        $rer = round(70 * pow($weightKg, 0.75));

        // 2. Maintenance Energy Requirement Multiplier based on biological status
        $multiplier = match ($activityLevel) {
            'puppy_kitten'   => ($species === 'cat' ? 2.5 : 2.0),
            'normal_intact'  => ($species === 'cat' ? 1.4 : 1.8),
            'normal_neutered'=> ($species === 'cat' ? 1.2 : 1.6),
            'weight_loss'    => 1.0,
            'senior'         => ($species === 'cat' ? 1.1 : 1.2),
            'active_working' => 2.5,
            default          => 1.4,
        };

        $mer = round($rer * $multiplier);

        // 3. Daily Hydration Requirement (50 - 60 ml / kg / day)
        $minWaterMl = round($weightKg * 50);
        $maxWaterMl = round($weightKg * 60);

        // 4. Body Condition Score (BCS 1-9) Estimation & Weight Status
        if ($species === 'cat') {
            if ($weightKg < 3.0) {
                $bcs = 3;
                $status = 'زیر وزن ایده‌آل (لاغر)';
                $statusClass = 'text-amber-700 bg-amber-50 border-amber-200';
                $advice = 'دنده‌ها و مهره‌ها به راحتی قابل لمس بوده و چربی زیرپوستی اندک است. افزایش کالری روزانه با مشاوره پزشک توصیه می‌شود.';
            } elseif ($weightKg <= 5.2) {
                $bcs = 5;
                $status = 'وزن ایده‌آل و اندام متناسب (WSAVA 5/9)';
                $statusClass = 'text-emerald-700 bg-emerald-50 border-emerald-200';
                $advice = 'تناسب اندام عالی، دنده‌ها بدون فشار قابل لمس و قوس کمر از بالا مشخص است. رژیم نگه‌دارنده فعلی را ادامه دهید.';
            } elseif ($weightKg <= 6.5) {
                $bcs = 7;
                $status = 'دارای اضافه‌وزن خفیف (WSAVA 7/9)';
                $statusClass = 'text-amber-700 bg-amber-50 border-amber-200';
                $advice = 'تجمع چربی در ناحیه شکم و پهلوها؛ پیشنهاد می‌شود از تشویقی‌های کم‌کالری استفاده کرده و فعالیت بازی روزانه را افزایش دهید.';
            } else {
                $bcs = 9;
                $status = 'چاق و پرخطر بالینی (WSAVA 9/9)';
                $statusClass = 'text-rose-700 bg-rose-50 border-rose-200';
                $advice = 'رسوب چربی ضخیم روی قفسه سینه و ستون فقرات. نیازمند تنظیم رژیم لاغری متابولیک تحت نظارت مستقیم دکتر دامپزشک.';
            }
        } else {
            // Dog BCS Assessment
            if ($weightKg < 4.0) {
                $sizeCategory = 'جثه مینیاتوری / Toy';
            } elseif ($weightKg <= 10.0) {
                $sizeCategory = 'رده جثه کوچک / Small Breed';
            } elseif ($weightKg <= 25.0) {
                $sizeCategory = 'رده جثه متوسط / Medium Breed';
            } elseif ($weightKg <= 45.0) {
                $sizeCategory = 'رده جثه بزرگ / Large Breed';
            } else {
                $sizeCategory = 'رده جثه غول‌پیکر / Giant Breed';
            }

            $bcs = 5;
            $status = "تناسب بالینی متناسب با رده ({$sizeCategory})";
            $statusClass = 'text-emerald-700 bg-emerald-50 border-emerald-200';
            $advice = 'برای ارزیابی دقیق ضخامت چربی دنده‌ای و دور کمر سگ‌ها، لمس فیزیکی در چکاپ‌های دوره‌ای کلینیک ضروری است.';
        }

        return [
            'species'            => $species,
            'weight_kg'          => $weightKg,
            'bcs_score'          => $bcs,
            'bcs_scale'          => '1-9 (WSAVA Standard)',
            'health_status'      => $status,
            'status_class'       => $statusClass,
            'daily_rer_kcal'     => $rer,
            'daily_mer_kcal'     => $mer,
            'hydration_ml_day'   => "{$minWaterMl} الی {$maxWaterMl} میلی‌لیتر در شبانه‌روز",
            'clinical_advice'    => $advice,
            'disclaimer'         => 'هشدار بالینی: این محاسبه‌گر صرفاً جهت ارزیابی تغذیه و شاخص وضعیت بدنی است. هرگونه تجویز دارویی، مسکن، ضدانگل یا واکسیناسیون باید منحصراً توسط پزشک دامپزشک تجویز گردد. مصرف خودسرانه داروهای انسانی برای پت‌ها خطر مسمومیت و مرگ دارد.'
        ];
    }
}
