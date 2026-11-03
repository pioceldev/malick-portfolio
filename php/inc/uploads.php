<?php
declare(strict_types=1);

/** Gestion des fichiers importés (images + PDF). */

const UPLOAD_MAX_BYTES = 12 * 1024 * 1024;

const UPLOAD_MIMES = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'image/avif' => 'avif',
    'image/gif' => 'gif',
    'image/svg+xml' => 'svg',
    'application/pdf' => 'pdf',
];

function uploads_dir(): string
{
    $dir = (string) cfg('uploads_dir');
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    return rtrim($dir, '/\\');
}

/**
 * Enregistre un fichier envoyé et retourne son nom stocké.
 *
 * @throws RuntimeException si le fichier est refusé
 */
function save_upload(array $file): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        throw new RuntimeException('Aucun fichier reçu.');
    }
    if (($file['error'] ?? 0) !== UPLOAD_ERR_OK) {
        throw new RuntimeException(($file['error'] ?? 0) === UPLOAD_ERR_INI_SIZE || ($file['error'] ?? 0) === UPLOAD_ERR_FORM_SIZE
            ? 'Fichier trop volumineux (12 Mo maximum).'
            : 'Le téléversement a échoué.');
    }
    if ((int) $file['size'] > UPLOAD_MAX_BYTES) {
        throw new RuntimeException('Fichier trop volumineux (12 Mo maximum).');
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        throw new RuntimeException('Fichier invalide.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string) $finfo->file($tmp);
    if (!isset(UPLOAD_MIMES[$mime])) {
        throw new RuntimeException('Format de fichier non autorisé (images ou PDF uniquement).');
    }

    $extension = UPLOAD_MIMES[$mime];
    $filename = base_convert((string) time(), 10, 36) . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
    $target = uploads_dir() . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($tmp, $target)) {
        throw new RuntimeException('Impossible d’enregistrer le fichier sur le serveur.');
    }

    record_media($filename, (string) ($file['name'] ?? $filename), $mime, (int) $file['size']);

    return $filename;
}

function record_media(string $filename, string $originalName, string $mime, int $size): void
{
    sql_exec(
        'INSERT INTO media (filename, original_name, alt, mime, size) VALUES (?, ?, ?, ?, ?)',
        [$filename, mb_substr($originalName, 0, 255), '', $mime, $size]
    );
}

function delete_upload(string $filename): void
{
    $path = uploads_dir() . DIRECTORY_SEPARATOR . basename($filename);
    if (is_file($path)) {
        @unlink($path);
    }
}

/**
 * Retourne le fichier envoyé pour un champ donné, ou null s'il est absent.
 */
function uploaded_file(string $field): ?array
{
    $file = $_FILES[$field] ?? null;
    if (!is_array($file)) {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    return $file;
}

/** Liste normalisée pour un champ multiple (files[]). */
function uploaded_files(string $field): array
{
    $file = $_FILES[$field] ?? null;
    if (!is_array($file) || !isset($file['name'])) {
        return [];
    }
    if (!is_array($file['name'])) {
        return uploaded_file($field) ? [uploaded_file($field)] : [];
    }

    $files = [];
    foreach (array_keys($file['name']) as $index) {
        if (($file['error'][$index] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $files[] = [
            'name' => $file['name'][$index],
            'type' => $file['type'][$index] ?? '',
            'tmp_name' => $file['tmp_name'][$index] ?? '',
            'error' => $file['error'][$index] ?? UPLOAD_ERR_NO_FILE,
            'size' => $file['size'][$index] ?? 0,
        ];
    }
    return $files;
}
