<?php

namespace App\Services;

use App\Core\Database;

/**
 * Clinic booking / case fee:
 * - First visit (or after 3-month case expiry) charges booking amount (default ₹300).
 * - Within validity window, revisits share the same case — no new booking fee.
 * - Booking is added on the bill after treatment amount.
 */
class BookingService
{
    public static function amount(): float
    {
        return (float) branding('booking_amount', 300);
    }

    public static function validityMonths(): int
    {
        $months = (int) branding('booking_validity_months', 3);
        return max(1, $months);
    }

    /**
     * @return array{
     *   due: bool,
     *   amount: float,
     *   case_type: string,
     *   valid_till: ?string,
     *   case_started: ?string,
     *   message: string,
     *   label: string
     * }
     */
    public static function statusForPatient(?int $patientId, ?string $asOfDate = null): array
    {
        $fee = self::amount();
        $months = self::validityMonths();
        $asOf = $asOfDate ?: date('Y-m-d');

        if (!$patientId) {
            return [
                'due' => true,
                'amount' => $fee,
                'case_type' => 'new',
                'valid_till' => null,
                'case_started' => null,
                'message' => 'First visit — booking ₹' . number_format($fee, 0) . ' will apply',
                'label' => 'New Case · Booking ₹' . number_format($fee, 0),
            ];
        }

        $cutoff = date('Y-m-d', strtotime($asOf . ' -' . $months . ' months'));

        $active = Database::fetch(
            "SELECT id, billing_date, booking_amount
             FROM bills
             WHERE patient_id = ?
               AND deleted_at IS NULL
               AND booking_amount > 0
               AND billing_date >= ?
               AND billing_date <= ?
               AND status <> 'cancelled'
             ORDER BY billing_date DESC, id DESC
             LIMIT 1",
            [$patientId, $cutoff, $asOf]
        );

        if ($active) {
            $started = (string) $active['billing_date'];
            $validTill = date('Y-m-d', strtotime($started . ' +' . $months . ' months'));
            return [
                'due' => false,
                'amount' => 0.0,
                'case_type' => 'existing',
                'valid_till' => $validTill,
                'case_started' => $started,
                'message' => 'Case active — booking covered till ' . format_date($validTill),
                'label' => 'Case valid till ' . format_date($validTill),
            ];
        }

        $prior = Database::fetch(
            'SELECT id FROM bills WHERE patient_id = ? AND deleted_at IS NULL AND status <> ? LIMIT 1',
            [$patientId, 'cancelled']
        );

        if ($prior) {
            return [
                'due' => true,
                'amount' => $fee,
                'case_type' => 'renewal',
                'valid_till' => null,
                'case_started' => null,
                'message' => 'New case (3-month window expired) — booking ₹' . number_format($fee, 0) . ' due',
                'label' => 'New Case · Booking ₹' . number_format($fee, 0),
            ];
        }

        return [
            'due' => true,
            'amount' => $fee,
            'case_type' => 'new',
            'valid_till' => null,
            'case_started' => null,
            'message' => 'First visit — booking ₹' . number_format($fee, 0) . ' due',
            'label' => 'New Case · Booking ₹' . number_format($fee, 0),
        ];
    }

    /** Ensure bills.booking_amount column exists (safe for live DB). */
    public static function ensureSchema(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        try {
            $col = Database::fetch("SHOW COLUMNS FROM bills LIKE 'booking_amount'");
            if (!$col) {
                Database::query('ALTER TABLE bills ADD COLUMN booking_amount DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER gross_amount');
            }
        } catch (\Throwable $e) {
            // ignore — table may not exist during install
        }
        $done = true;
    }
}
