<?php
/**
 * Convenience entry when domain/docroot points at project root (not /public).
 *
 * Live:  https://roots.oceanhub.co.in/  → /public/login
 * Local: http://localhost/roots_project/ → /roots_project/public/login
 *
 * Absolute path (leading /) is required to avoid redirect loops.
 */
$script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
$uri = str_replace('\\', '/', (string) ($_SERVER['REQUEST_URI'] ?? ''));

$underRootsProject = str_contains($script, '/roots_project/')
    || str_contains($uri, '/roots_project/')
    || str_contains($script, '/roots_project');

$base = $underRootsProject ? '/roots_project/public' : '/public';

header('Location: ' . $base . '/login', true, 302);
exit;
