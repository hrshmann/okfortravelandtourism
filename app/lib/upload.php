<?php
declare(strict_types=1);

const UPLOAD_MAX_BYTES = 12 * 1024 * 1024;
const UPLOAD_MAX_DIM = 2000;

/**
 * Validate and store one uploaded image. The image is decoded and re-encoded with GD,
 * which strips metadata and anything that is not pixels. Returns the media row.
 */
function store_upload(array $file, string $alt = ''): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $map = [
            UPLOAD_ERR_INI_SIZE => 'The file is larger than the server allows (' . ini_get('upload_max_filesize') . ').',
            UPLOAD_ERR_FORM_SIZE => 'The file is too large.',
            UPLOAD_ERR_PARTIAL => 'The upload was interrupted. Try again.',
            UPLOAD_ERR_NO_FILE => 'Choose a file to upload.',
        ];
        throw new RuntimeException($map[$file['error'] ?? UPLOAD_ERR_NO_FILE] ?? 'Upload failed.');
    }
    if ($file['size'] > UPLOAD_MAX_BYTES) {
        throw new RuntimeException('Images must be under 12 MB.');
    }
    $info = @getimagesize($file['tmp_name']);
    $types = [IMAGETYPE_JPEG => 'jpeg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp', IMAGETYPE_GIF => 'gif'];
    if (!$info || !isset($types[$info[2]])) {
        throw new RuntimeException('Upload a JPG, PNG, WebP or GIF image.');
    }
    $dir = 'uploads/' . date('Y/m');
    if (!is_dir(ROOT . '/' . $dir) && !@mkdir(ROOT . '/' . $dir, 0755, true)) {
        throw new RuntimeException('The uploads folder is not writable.');
    }
    $base = slugify(pathinfo((string) $file['name'], PATHINFO_FILENAME));
    $base = substr($base, 0, 50) . '-' . strtolower(random_code(5));

    if (!function_exists('imagecreatefromstring')) {
        // No GD: keep the original file but with a safe, server-chosen extension.
        $ext = $types[$info[2]] === 'jpeg' ? 'jpg' : $types[$info[2]];
        $path = "$dir/$base.$ext";
        if (!move_uploaded_file($file['tmp_name'], ROOT . '/' . $path)) {
            throw new RuntimeException('Could not save the file.');
        }
        return media_record($path, $file, (int) $info[0], (int) $info[1], $alt);
    }

    $src = @imagecreatefromstring((string) file_get_contents($file['tmp_name']));
    if (!$src) {
        throw new RuntimeException('This image could not be read. Try saving it again as JPG.');
    }
    [$w, $h] = [imagesx($src), imagesy($src)];
    $scale = min(1, UPLOAD_MAX_DIM / max($w, $h));
    $nw = (int) round($w * $scale);
    $nh = (int) round($h * $scale);
    $dst = imagecreatetruecolor($nw, $nh);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    imagedestroy($src);

    if (function_exists('imagewebp')) {
        $path = "$dir/$base.webp";
        $ok = imagewebp($dst, ROOT . '/' . $path, 82);
    } elseif ($types[$info[2]] === 'png') {
        $path = "$dir/$base.png";
        $ok = imagepng($dst, ROOT . '/' . $path, 7);
    } else {
        $path = "$dir/$base.jpg";
        $ok = imagejpeg($dst, ROOT . '/' . $path, 84);
    }
    imagedestroy($dst);
    if (!$ok) {
        throw new RuntimeException('Could not save the image.');
    }
    return media_record($path, $file, $nw, $nh, $alt);
}

function media_record(string $path, array $file, int $w, int $h, string $alt): array
{
    $full = ROOT . '/' . $path;
    $mime = function_exists('mime_content_type') ? (string) @mime_content_type($full) : '';
    $data = [
        'path' => $path,
        'original_name' => mb_substr((string) $file['name'], 0, 255),
        'mime' => $mime ?: 'image/' . pathinfo($path, PATHINFO_EXTENSION),
        'size' => (int) @filesize($full),
        'width' => $w,
        'height' => $h,
        'alt' => mb_substr($alt, 0, 255),
        'created_at' => now(),
    ];
    $data['id'] = insert('media', $data);
    return $data;
}

/** Normalise $_FILES['x'] with multiple files into a list of single-file arrays. */
function files_list(string $key): array
{
    $f = $_FILES[$key] ?? null;
    if (!$f) {
        return [];
    }
    if (!is_array($f['name'])) {
        return [$f];
    }
    $out = [];
    foreach ($f['name'] as $i => $name) {
        $out[] = ['name' => $name, 'type' => $f['type'][$i], 'tmp_name' => $f['tmp_name'][$i], 'error' => $f['error'][$i], 'size' => $f['size'][$i]];
    }
    return $out;
}
