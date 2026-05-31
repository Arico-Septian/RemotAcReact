<?php

namespace App\Services;

class FuzzyMamdaniService
{
    private const TEMP_COLD = 22;
    private const TEMP_HOT  = 30;

    private int $cold;
    private int $hot;
    private int $mid;

    public function __construct()
    {
        $this->cold = self::TEMP_COLD;
        $this->hot  = self::TEMP_HOT;
        $this->mid  = (int) round(($this->cold + $this->hot) / 2);
    }

    // Dingin
    public function muSuhuDingin(float $x): float
    {
        if ($x <= $this->cold) {
            return 1;
        }

        if ($x > $this->cold && $x < $this->mid) {
            return ($this->mid - $x) / ($this->mid - $this->cold);
        }

        return 0;
    }

    // Normal
    public function muSuhuNormal(float $x): float
    {
        if ($x <= $this->cold || $x >= $this->hot) {
            return 0;
        }

        if ($x > $this->cold && $x < $this->mid) {
            return ($x - $this->cold) / ($this->mid - $this->cold);
        }

        if ($x >= $this->mid && $x < $this->hot) {
            return ($this->hot - $x) / ($this->hot - $this->mid);
        }

        return 0;
    }

    // Panas
    public function muSuhuPanas(float $x): float
    {
        if ($x <= $this->mid) {
            return 0;
        }

        if ($x > $this->mid && $x < $this->hot) {
            return ($x - $this->mid) / ($this->hot - $this->mid);
        }

        return 1;
    }

    // Turun
    public function muDeltaTurun(float $dt): float
    {
        if ($dt <= -2) {
            return 1;
        }

        if ($dt > -2 && $dt < 0) {
            return (0 - $dt) / 2;
        }

        return 0;
    }

    // Stabil
    public function muDeltaStabil(float $dt): float
    {
        if ($dt <= -2 || $dt >= 2) {
            return 0;
        }

        if ($dt > -2 && $dt < 0) {
            return ($dt + 2) / 2;
        }

        if ($dt >= 0 && $dt < 2) {
            return (2 - $dt) / 2;
        }

        return 0;
    }

    // Naik
    public function muDeltaNaik(float $dt): float
    {
        if ($dt <= 0) {
            return 0;
        }

        if ($dt > 0 && $dt < 2) {
            return $dt / 2;
        }

        return 1;
    }

    // AC Rendah
    public function muAcRendah(float $z): float
    {
        if ($z <= 0) {
            return 1;
        }

        if ($z > 0 && $z < 40) {
            return (40 - $z) / 40;
        }

        return 0;
    }

    // AC Sedang
    public function muAcSedang(float $z): float
    {
        if ($z <= 30 || $z >= 70) {
            return 0;
        }

        if ($z > 30 && $z < 50) {
            return ($z - 30) / 20;
        }

        if ($z >= 50 && $z < 70) {
            return (70 - $z) / 20;
        }

        return 0;
    }

    // AC Tinggi
    public function muAcTinggi(float $z): float
    {
        if ($z <= 60) {
            return 0;
        }

        if ($z > 60 && $z < 100) {
            return ($z - 60) / 40;
        }

        return 1;
    }

    public function calculate(float $suhu, float $deltaT): array
    {
        $dingin = $this->muSuhuDingin($suhu);
        $normal = $this->muSuhuNormal($suhu);
        $panas = $this->muSuhuPanas($suhu);

        $turun = $this->muDeltaTurun($deltaT);
        $stabil = $this->muDeltaStabil($deltaT);
        $naik = $this->muDeltaNaik($deltaT);

        // RULE BASE
        // R1
        $r1 = min($dingin, $turun);

        // R2
        $r2 = min($dingin, $stabil);

        // R3
        $r3 = min($dingin, $naik);

        // R4
        $r4 = min($normal, $turun);

        // R5
        $r5 = min($normal, $stabil);

        // R6
        $r6 = min($normal, $naik);

        // R7
        $r7 = min($panas, $turun);

        // R8
        $r8 = min($panas, $stabil);

        // R9
        $r9 = min($panas, $naik);

        // AGREGASI OUTPUT
        // AC Rendah
        $acRendah = max($r1, $r2, $r4);

        // AC Sedang
        $acSedang = max($r3, $r5, $r7);

        // AC Tinggi
        $acTinggi = max($r6, $r8, $r9);

        // DEFUZZIFIKASI (CENTROID)
        $numerator = 0;
        $denominator = 0;

        for ($z = 0; $z <= 100; $z++) {

            $mu = max(
                min($acRendah, $this->muAcRendah($z)),
                min($acSedang, $this->muAcSedang($z)),
                min($acTinggi, $this->muAcTinggi($z))
            );

            $numerator += ($z * $mu);
            $denominator += $mu;
        }

        $crisp = $denominator != 0
            ? $numerator / $denominator
            : 0;

        $muR = $this->muAcRendah($crisp);
        $muS = $this->muAcSedang($crisp);
        $muT = $this->muAcTinggi($crisp);

        $status = 'AC Rendah';

        $maxMu = max($muR, $muS, $muT);

        if ($maxMu == $muT) {
            $status = 'AC Tinggi';
        } elseif ($maxMu == $muS) {
            $status = 'AC Sedang';
        }

        return [
            'suhu' => round($suhu, 2),
            'delta_t' => round($deltaT, 2),

            'membership_suhu' => [
                'dingin' => round($dingin, 3),
                'normal' => round($normal, 3),
                'panas' => round($panas, 3),
            ],

            'membership_delta_t' => [
                'turun' => round($turun, 3),
                'stabil' => round($stabil, 3),
                'naik' => round($naik, 3),
            ],

            'rules' => [
                'R1' => round($r1, 3),
                'R2' => round($r2, 3),
                'R3' => round($r3, 3),
                'R4' => round($r4, 3),
                'R5' => round($r5, 3),
                'R6' => round($r6, 3),
                'R7' => round($r7, 3),
                'R8' => round($r8, 3),
                'R9' => round($r9, 3),
            ],

            'output_membership' => [
                'ac_rendah' => round($acRendah, 3),
                'ac_sedang' => round($acSedang, 3),
                'ac_tinggi' => round($acTinggi, 3),
            ],

            'crisp_output' => round($crisp, 2),
            'status_pendinginan' => $status,
        ];
    }
    public function decideAction(array $fuzzyResult, int $currentSetpoint, int $min = 16, int $max = 30): array
    {
        $status = $fuzzyResult['status_pendinginan'] ?? 'AC Sedang';

        $deltaSetpoint = 0;
        $action = 'DIAM';

        if ($status === 'AC Tinggi') {
            $action = 'TURUNKAN';
            $deltaSetpoint = -1;
        } elseif ($status === 'AC Rendah') {
            $action = 'NAIKKAN';
            $deltaSetpoint = +1;
        }

        $newSetpoint = max($min, min($max, $currentSetpoint + $deltaSetpoint));

        return [
            'action' => $action,
            'delta_setpoint' => $deltaSetpoint,
            'setpoint_before' => $currentSetpoint,
            'setpoint_after' => $newSetpoint,
        ];
    }
}
