<?php

use App\Core\App;
use App\Core\Auth;
use App\Core\Session;
use App\Models\Setting;

function app_url(string $path = ''): string
{
    return App::url($path);
}

function asset(string $path): string
{
    return App::asset($path);
}

function csrf_token(): string
{
    return Session::csrfToken();
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function old(string $key, mixed $default = ''): mixed
{
    $old = Session::get('_flash_old_keep') ?? Session::getFlash('old', []);
    return $old[$key] ?? $default;
}

function can(string $permission): bool
{
    return Auth::can($permission);
}

function auth_user(): ?array
{
    return Auth::user();
}

/**
 * Linked doctor profile id for pure doctor logins (not admin/super_admin).
 */
function current_doctor_id(): ?int
{
    if (!Auth::check()) {
        return null;
    }
    if (Auth::hasRole('super_admin') || Auth::hasRole('admin')) {
        return null;
    }
    if (!Auth::hasRole('doctor')) {
        return null;
    }

    static $doctorId = false;
    if ($doctorId !== false) {
        return $doctorId;
    }

    try {
        $doc = \App\Core\Database::fetch(
            'SELECT id FROM doctors WHERE user_id = ? AND deleted_at IS NULL AND is_active = 1',
            [Auth::id()]
        );
        $doctorId = $doc ? (int) $doc['id'] : null;
    } catch (Throwable $e) {
        $doctorId = null;
    }

    return $doctorId;
}

function branding(?string $key = null, mixed $default = null): mixed
{
    static $settings = null;
    if ($settings === null) {
        try {
            $settings = Setting::allKeyed();
        } catch (Throwable $e) {
            $settings = [];
        }
    }

    $defaults = [
        'hospital_name' => 'Roots Dentistry',
        'hospital_tagline' => 'Oral Surgeon | Implants | Surgery | Smile Design',
        'logo_main' => 'branding/logo-main.jpg',
        'logo_login' => 'branding/logo-login.jpg',
        'logo_sidebar' => 'branding/logo-sidebar.jpg',
        'logo_collapsed' => 'branding/logo-collapsed.png',
        'favicon' => 'branding/logo-collapsed.png',
        'primary_color' => '#00AEEF',
        'secondary_color' => '#58595B',
        'sidebar_color' => '#111111',
        'sidebar_text_color' => '#FFFFFF',
        'hospital_address' => '208 Jasal Complex, Nanavati Circle, 150 Feet Ring Rd, Rajkot, Gujarat 360007',
        'hospital_phone' => '083477 60330',
        'hospital_email' => 'info@rootsdentistry.in',
        'booking_amount' => '300',
        'booking_validity_months' => '3',
    ];

    $all = array_merge($defaults, $settings);

    if ($key === null) {
        return $all;
    }

    return $all[$key] ?? $default;
}

/** Clinic contact lines for invoices / print reports. */
function hospital_contact_lines(): array
{
    return array_values(array_filter([
        trim((string) branding('hospital_address', '')),
        trim((string) branding('hospital_phone', '')) !== '' ? 'Phone: ' . trim((string) branding('hospital_phone')) : '',
        trim((string) branding('hospital_email', '')) !== '' ? 'Email: ' . trim((string) branding('hospital_email')) : '',
    ]));
}

function logo_url(string $key = 'logo_main'): string
{
    $path = branding($key);
    if (!$path) {
        $path = 'branding/logo-main.jpg';
    }
    if (str_starts_with($path, 'http')) {
        return $path;
    }

    $relative = 'uploads/' . ltrim($path, '/');
    $url = asset($relative);
    $full = App::basePath('public/assets/' . $relative);
    if (is_file($full)) {
        $url .= (str_contains($url, '?') ? '&' : '?') . 'v=' . filemtime($full);
    }
    return $url;
}

function appointment_statuses_list(): array
{
    static $list = null;
    if ($list === null) {
        try {
            $list = \App\Core\Database::fetchAll('SELECT * FROM appointment_statuses WHERE deleted_at IS NULL AND is_active = 1 ORDER BY sort_order ASC, id ASC');
        } catch (Throwable $e) {
            $list = [];
        }
    }
    return $list ?: [];
}

function status_badge(string $status): string
{
    $key = strtolower(str_replace([' ', '-'], '_', trim($status)));

    // Check DB status master first
    foreach (appointment_statuses_list() as $s) {
        if ($s['slug'] === $key) {
            $class = $s['badge_class'] ?: 'primary';
            $label = $s['name'];
            return '<span class="badge badge-status badge-' . e($class) . '">' . e($label) . '</span>';
        }
    }

    $map = [
        'scheduled' => 'primary',
        'confirmed' => 'info',
        'checked_in' => 'warning',
        'checked-in' => 'warning',
        'waiting' => 'warning',
        'with_doctor' => 'accent',
        'with-doctor' => 'accent',
        'completed' => 'success',
        'cancelled' => 'danger',
        'canceled' => 'danger',
        'no_show' => 'secondary',
        'no-show' => 'secondary',
        'pending' => 'warning',
        'partial' => 'info',
        'paid' => 'success',
        'approved' => 'success',
        'rejected' => 'danger',
        'active' => 'success',
        'inactive' => 'secondary',
        'recommended' => 'info',
        'planned' => 'primary',
        'started' => 'info',
        'in_progress' => 'accent',
        'on_hold' => 'warning',
        'missed' => 'secondary',
    ];

    $class = $map[$key] ?? 'secondary';
    $label = ucwords(str_replace(['_', '-'], ' ', $status));

    return '<span class="badge badge-status badge-' . e($class) . '">' . e($label) . '</span>';
}

function flash_toastr(): void
{
    $success = Session::getFlash('success');
    $error = Session::getFlash('error');
    $warning = Session::getFlash('warning');
    $info = Session::getFlash('info');

    if ($success) {
        echo "<script>document.addEventListener('DOMContentLoaded',()=>toastr.success(" . json_encode($success) . "));</script>";
    }
    if ($error) {
        echo "<script>document.addEventListener('DOMContentLoaded',()=>toastr.error(" . json_encode($error) . "));</script>";
    }
    if ($warning) {
        echo "<script>document.addEventListener('DOMContentLoaded',()=>toastr.warning(" . json_encode($warning) . "));</script>";
    }
    if ($info) {
        echo "<script>document.addEventListener('DOMContentLoaded',()=>toastr.info(" . json_encode($info) . "));</script>";
    }
}

function format_date(?string $date, string $format = 'd-m-Y'): string
{
    if (!$date) {
        return '-';
    }
    $ts = strtotime($date);
    return $ts ? date($format, $ts) : '-';
}

function format_time(?string $time, string $format = 'h:i A'): string
{
    if (!$time) {
        return '-';
    }
    $ts = strtotime($time);
    if (!$ts) {
        $ts = strtotime('1970-01-01 ' . $time);
    }
    return $ts ? date($format, $ts) : '-';
}

function format_money($amount): string
{
    return '₹' . number_format((float) $amount, 2);
}

function doctor_label(?string $name): string
{
    $name = trim((string) $name);
    if ($name === '') {
        return '-';
    }
    return preg_match('/^dr\.?\s+/i', $name) ? $name : ('Dr. ' . $name);
}

function appointment_status_color(string $status): string
{
    $key = strtolower(str_replace([' ', '-'], '_', trim($status)));
    foreach (appointment_statuses_list() as $s) {
        if ($s['slug'] === $key && !empty($s['color'])) {
            return $s['color'];
        }
    }

    return match ($key) {
        'scheduled' => '#3B82F6',
        'confirmed' => '#0EA5E9',
        'waiting' => '#F59E0B',
        'checked_in' => '#8B5CF6',
        'with_doctor' => '#6366F1',
        'completed' => '#22C55E',
        'cancelled' => '#94A3B8',
        'no_show' => '#EF4444',
        default => '#00AEEF',
    };
}

/**
 * Mark sidebar link active using exact path-segment match
 * (avoids "doctors" matching "reference-doctors").
 */
function active_menu(string $needle): string
{
    $path = trim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '', '/');
    $base = trim(parse_url(\App\Core\App::url(''), PHP_URL_PATH) ?: '', '/');
    if ($base !== '' && str_starts_with($path, $base . '/')) {
        $path = substr($path, strlen($base) + 1);
    } elseif ($base !== '' && $path === $base) {
        $path = '';
    }

    $needle = trim($needle, '/');
    if ($needle === '') {
        return '';
    }

    $segments = $path === '' ? [] : explode('/', $path);
    $first = $segments[0] ?? '';

    if ($needle === 'dashboard') {
        return ($first === '' || $first === 'dashboard') ? 'active' : '';
    }

    // Exact segment match only (first URL segment after public base).
    return $first === $needle ? 'active' : '';
}

function menu_open(array $needles): string
{
    foreach ($needles as $n) {
        if (active_menu($n) === 'active') {
            return 'show';
        }
    }
    return '';
}
