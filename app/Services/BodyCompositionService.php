<?php

namespace App\Services;

class BodyCompositionService
{
    /**
     * Estima % de grasa corporal sin cinta métrica usando la ecuación CUN-BAE
     * (Gómez-Ambrosi J. et al., Obesity, 2012). Correlación ~0.99 vs DEXA.
     * Error típico ±3.5%.
     *
     * sex: 'male' (→ 0) | 'female' (→ 1)
     */
    public function estimateCunbae(float $weightKg, float $heightCm, int $age, string $sex): float
    {
        $bmi     = $this->bmi($weightKg, $heightCm);
        $sexVal  = $sex === 'female' ? 1.0 : 0.0;
        $bmi2    = $bmi * $bmi;

        $pct = -44.988
            + 0.503  * $age
            + 10.689 * $sexVal
            + 3.172  * $bmi
            - 0.026  * $bmi2
            + 0.181  * $bmi  * $sexVal
            - 0.02   * $bmi  * $age
            - 0.005  * $bmi2 * $sexVal
            + 0.00021 * $bmi2 * $age;

        return round(max(2.0, min(60.0, $pct)), 1);
    }

    /**
     * Estima % de grasa con la ecuación de Deurenberg (1991) como fallback.
     * sex: 'male' (factor 1) | 'female' (factor 0).
     */
    public function estimateDeurenberg(float $weightKg, float $heightCm, int $age, string $sex): float
    {
        $bmi      = $this->bmi($weightKg, $heightCm);
        $sexFactor = $sex === 'male' ? 1.0 : 0.0;

        $pct = 1.20 * $bmi + 0.23 * $age - 10.8 * $sexFactor - 5.4;

        return round(max(2.0, min(60.0, $pct)), 1);
    }

    /**
     * Método U.S. Navy (Hodgdon & Beckett, 1984). Error típico ±3.5%, sin equipamiento.
     * Requiere: altura, cuello, cintura (hombres) + cadera (mujeres).
     * Todas las medidas en cm.
     */
    public function navyMethod(
        string $sex,
        float $heightCm,
        float $neckCm,
        float $waistCm,
        ?float $hipCm = null
    ): float {
        if ($sex === 'female') {
            if (!$hipCm || $hipCm <= 0) {
                throw new \InvalidArgumentException('Para mujeres se necesita la medida de cadera.');
            }
            // Navy female: 495 / (1.29579 − 0.35004·log10(waist+hip−neck) + 0.22100·log10(height)) − 450
            $num   = $waistCm + $hipCm - $neckCm;
            $denom = 1.29579
                - 0.35004 * log10(max(0.01, $num))
                + 0.22100 * log10($heightCm);
            $pct = (495 / $denom) - 450;
        } else {
            // Navy male: 495 / (1.0324 − 0.19077·log10(waist−neck) + 0.15456·log10(height)) − 450
            $num   = $waistCm - $neckCm;
            $denom = 1.0324
                - 0.19077 * log10(max(0.01, $num))
                + 0.15456 * log10($heightCm);
            $pct = (495 / $denom) - 450;
        }

        return round(max(2.0, min(65.0, $pct)), 1);
    }

    private function bmi(float $weightKg, float $heightCm): float
    {
        $heightM = $heightCm / 100.0;
        return $heightM > 0 ? $weightKg / ($heightM * $heightM) : 0.0;
    }
}
