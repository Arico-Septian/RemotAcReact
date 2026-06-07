<?php

namespace App\Console\Commands;

use App\Events\RaspiTemperatureUpdated;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class SnmpRaspiTemp extends Command
{
    protected $signature = 'snmp:raspi-temp';

    protected $description = 'Baca suhu CPU Raspberry Pi via SNMP (snmpwalk), simpan ke cache + broadcast ke dashboard';

    public function handle(): int
    {
        if (! config('services.snmp.enabled')) {
            $this->warn('SNMP disabled. Set SNMP_ENABLED=true di .env untuk mengaktifkan.');

            return self::SUCCESS;
        }

        $host = trim((string) config('services.snmp.host'));
        $community = trim((string) config('services.snmp.community'));
        $oid = trim((string) config('services.snmp.temp_oid'));

        if ($host === '' || $community === '' || $oid === '') {
            $this->error('Konfigurasi SNMP tidak lengkap (SNMP_HOST / SNMP_COMMUNITY / SNMP_TEMP_OID).');

            return self::FAILURE;
        }

        // Strategi 1: binary `snmpwalk` (Linux). Strategi 2 (fallback): ekstensi PHP snmp (Windows).
        [$raw, $error] = $this->querySnmp($host, $community, $oid);

        if ($raw === null) {
            $this->error("SNMP query gagal: {$error}");
            Log::warning('SNMP raspi temp query failed', [
                'host' => $host,
                'oid' => $oid,
                'error' => $error,
            ]);

            return self::FAILURE;
        }

        // Ambil angka pertama: mendukung "48.3", "\"48.3\"", "STRING: 48.3", atau "48300" (millidegree).
        if (! preg_match('/-?\d+(\.\d+)?/', $raw, $m)) {
            $this->error("Tidak bisa parse suhu dari output SNMP: [{$raw}]");
            Log::warning('SNMP raspi temp parse failed', ['raw' => $raw]);

            return self::FAILURE;
        }

        $temp = (float) $m[0];

        // lm-sensors mengembalikan millidegree (mis. 48000) -> konversi ke derajat Celsius.
        if ($temp > 1000) {
            $temp = round($temp / 1000, 1);
        }

        if ($temp <= 0 || $temp > 150) {
            $this->error("Suhu di luar rentang wajar ({$temp}°C), diabaikan.");

            return self::FAILURE;
        }

        // Plug ke jalur yang sama dengan handler MQTT raspi/temperature:
        // cache 'raspi_temperature' (TTL 300s) + broadcast RaspiTemperatureUpdated.
        Cache::put('raspi_temperature', $temp, 300);
        event(new RaspiTemperatureUpdated($temp));

        $this->info("RASPI TEMP (SNMP): {$temp}°C  [host={$host}]");

        return self::SUCCESS;
    }

    /**
     * Coba binary snmpwalk dulu; kalau binary tidak ada / gagal, fallback ke ekstensi PHP snmp.
     *
     * @return array{0: ?string, 1: ?string} [raw output, error message]
     */
    private function querySnmp(string $host, string $community, string $oid): array
    {
        $errors = [];

        // === Strategi 1: binary snmpwalk (apa yang diminta, jalan di Linux) ===
        try {
            // -Oqv: cetak nilai saja (tanpa OID & tipe). Argumen array -> aman dari shell injection.
            $result = Process::timeout(20)->run([
                'snmpwalk', '-v', '2c', '-c', $community, '-Oqv', '-t', '5', '-r', '1', $host, $oid,
            ]);

            if ($result->successful() && trim($result->output()) !== '') {
                return [trim($result->output()), null];
            }

            $errors[] = 'snmpwalk: '.(trim($result->errorOutput()) ?: trim($result->output()) ?: 'gagal/empty');
        } catch (\Throwable $e) {
            // Biasanya "snmpwalk not recognized/not found" -> lanjut ke fallback.
            $errors[] = 'snmpwalk: '.$e->getMessage();
        }

        // === Strategi 2: ekstensi PHP snmp (fallback, jalan di Windows) ===
        if (function_exists('snmp2_walk')) {
            snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
            // timeout 5 detik (microseconds), 1 retry
            $vals = @snmp2_walk($host, $community, $oid, 5_000_000, 1);

            if (is_array($vals) && ! empty($vals)) {
                return [trim((string) reset($vals)), null];
            }

            $errors[] = 'php-snmp: tidak ada balasan (host tidak terjangkau / OID kosong / community salah)';
        } else {
            $errors[] = 'php-snmp: ekstensi tidak tersedia';
        }

        return [null, implode(' | ', $errors)];
    }
}
