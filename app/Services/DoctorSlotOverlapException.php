<?php

namespace App\Services;

use RuntimeException;

/**
 * Soft conflict: same doctor already has overlapping appointment.
 * UI should warn (orange) and allow force book after user confirms.
 */
class DoctorSlotOverlapException extends RuntimeException
{
}
