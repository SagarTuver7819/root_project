<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\Setting;
use App\Services\AuditService;

class SettingsController extends Controller
{
    public function branding(Request $request): void
    {
        $this->view('modules/settings/branding', [
            'title' => 'Hospital Branding',
            'pageTitle' => 'Hospital Profile / Branding',
            'settings' => branding(),
        ]);
    }

    public function updateBranding(Request $request): void
    {
        $data = $this->validate($request, [
            'hospital_name' => 'required|max:150',
            'primary_color' => 'required',
            'secondary_color' => 'required',
            'sidebar_color' => 'required',
            'sidebar_text_color' => 'required',
        ]);

        $pairs = [
            'hospital_name' => $data['hospital_name'],
            'primary_color' => $data['primary_color'],
            'secondary_color' => $data['secondary_color'],
            'sidebar_color' => $data['sidebar_color'],
            'sidebar_text_color' => $data['sidebar_text_color'],
            'hospital_address' => $request->input('hospital_address', ''),
            'hospital_phone' => $request->input('hospital_phone', ''),
            'hospital_email' => $request->input('hospital_email', ''),
        ];

        if ($request->input('hospital_tagline') !== null) {
            $pairs['hospital_tagline'] = $request->input('hospital_tagline', '');
        }

        $logoKeys = ['logo_main', 'logo_login', 'logo_sidebar', 'logo_collapsed', 'favicon'];
        $uploadErrors = [];
        $uploaded = [];

        foreach ($logoKeys as $key) {
            $file = $request->file($key);
            if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                $uploadErrors[] = ucwords(str_replace('_', ' ', $key)) . ' upload failed (error code ' . $file['error'] . ').';
                continue;
            }

            $path = $this->storeLogo($file, $key);
            if ($path) {
                $pairs[$key] = $path;
                $uploaded[] = $key;
                if ($key === 'logo_sidebar' && $request->input('sync_collapsed_logo')) {
                    $pairs['logo_collapsed'] = $path;
                    if (!in_array('logo_collapsed', $uploaded, true)) {
                        $uploaded[] = 'logo_collapsed';
                    }
                }
            } else {
                $uploadErrors[] = ucwords(str_replace('_', ' ', $key)) . ' was not saved. Use PNG, JPG, WEBP, GIF or SVG.';
            }
        }

        Setting::setMany($pairs, 'branding');
        AuditService::log('settings', 'update', null, null, $pairs);

        $message = 'Branding settings saved successfully.';
        if ($uploaded) {
            $message .= ' Updated: ' . implode(', ', array_map(fn ($k) => str_replace('_', ' ', $k), $uploaded)) . '.';
        }
        if ($uploadErrors) {
            $message .= ' ' . implode(' ', $uploadErrors);
        }

        Session::flash($uploadErrors ? 'warning' : 'success', $message);
        $this->redirect('settings/branding');
    }

    private function storeLogo(array $file, string $key): ?string
    {
        $tmp = $file['tmp_name'] ?? '';
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return null;
        }

        $mime = null;
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $tmp) ?: null;
            finfo_close($finfo);
        }
        if (!$mime && function_exists('mime_content_type')) {
            $mime = mime_content_type($tmp) ?: null;
        }

        $extFromName = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        $allowedExt = ['png', 'jpg', 'jpeg', 'webp', 'svg', 'gif'];
        $mimeMap = [
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            'image/gif' => 'gif',
            'image/x-png' => 'png',
            'application/octet-stream' => in_array($extFromName, $allowedExt, true) ? ($extFromName === 'jpeg' ? 'jpg' : $extFromName) : null,
        ];

        $ext = $mimeMap[$mime] ?? null;
        if (!$ext && in_array($extFromName, $allowedExt, true)) {
            $ext = $extFromName === 'jpeg' ? 'jpg' : $extFromName;
        }
        if (!$ext) {
            return null;
        }

        $dir = dirname(__DIR__, 2) . '/public/assets/uploads/branding';
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            return null;
        }

        $filename = $key . '-' . time() . '-' . bin2hex(random_bytes(3)) . '.' . $ext;
        $dest = $dir . DIRECTORY_SEPARATOR . $filename;
        if (!move_uploaded_file($tmp, $dest)) {
            return null;
        }

        return 'branding/' . $filename;
    }
}
