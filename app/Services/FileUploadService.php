<?php
declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class FileUploadService
{
    private array $extensions = ['jpg', 'jpeg', 'png', 'webp'];

    private array $mimes = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    private array $videoExtensions = ['mp4', 'webm'];

    private array $videoMimes = [
        'video/mp4',
        'video/webm',
    ];

    private array $attachmentExtensions = [
        'jpg',
        'jpeg',
        'png',
        'webp',
        'pdf',
        'doc',
        'docx',
        'xls',
        'xlsx',
    ];

    private array $attachmentMimes = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    /**
     * Upload an image.
     */
    public function image(
        string $field,
        string $directory,
        ?string $oldPath = null
    ): ?string {
        return $this->upload(
            $field,
            $directory,
            $oldPath,
            false
        );
    }

    /**
     * Upload image or video.
     */
    public function media(
        string $field,
        string $directory,
        ?string $oldPath = null
    ): ?string {
        return $this->upload(
            $field,
            $directory,
            $oldPath,
            true
        );
    }

    /**
     * Upload general attachment.
     */
    public function attachment(
        string $field,
        string $directory,
        ?string $oldPath = null
    ): ?string {
        return $this->uploadAttachment(
            $field,
            $directory,
            $oldPath
        );
    }

    /**
     * Detect MIME type.
     *
     * Fileinfo is preferred, but some shared-hosting servers do not enable it.
     * In that case use safe, extension-specific fallbacks. Images are verified
     * with getimagesize(), while common document/container signatures are
     * checked before returning their expected MIME type.
     */
    private function detectMime(string $file, string $extension): string
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);

            if ($finfo !== false) {
                $mime = finfo_file($finfo, $file);
                finfo_close($finfo);

                if ($mime) {
                    return $mime;
                }
            }
        }

        $extension = strtolower($extension);

        // Strong image verification without Fileinfo.
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $image = @getimagesize($file);

            if ($image === false) {
                throw new RuntimeException(
                    'Uploaded file is not a valid image.'
                );
            }

            return match ((int)($image[2] ?? 0)) {
                IMAGETYPE_JPEG => 'image/jpeg',
                IMAGETYPE_PNG  => 'image/png',
                IMAGETYPE_WEBP => 'image/webp',
                default => throw new RuntimeException(
                    'Unsupported image format.'
                ),
            };
        }

        // PDF signature: %PDF-
        if ($extension === 'pdf') {
            $handle = @fopen($file, 'rb');

            if ($handle === false) {
                throw new RuntimeException(
                    'Unable to inspect uploaded PDF.'
                );
            }

            $header = fread($handle, 5);
            fclose($handle);

            if ($header !== '%PDF-') {
                throw new RuntimeException(
                    'Uploaded file is not a valid PDF.'
                );
            }

            return 'application/pdf';
        }

        // DOCX/XLSX are ZIP containers. Verify the ZIP signature.
        if (in_array($extension, ['docx', 'xlsx'], true)) {
            $handle = @fopen($file, 'rb');

            if ($handle === false) {
                throw new RuntimeException(
                    'Unable to inspect uploaded document.'
                );
            }

            $header = fread($handle, 4);
            fclose($handle);

            if ($header !== 'PK\\x03\\x04') {
                throw new RuntimeException(
                    'Uploaded Office document is not a valid ZIP-based document.'
                );
            }

            return $extension === 'docx'
                ? 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        }

        // Legacy DOC/XLS use OLE compound-file signature.
        if (in_array($extension, ['doc', 'xls'], true)) {
            $handle = @fopen($file, 'rb');

            if ($handle === false) {
                throw new RuntimeException(
                    'Unable to inspect uploaded Office file.'
                );
            }

            $header = fread($handle, 8);
            fclose($handle);

            if ($header !== "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1") {
                throw new RuntimeException(
                    'Uploaded Office file is not a valid legacy document.'
                );
            }

            return $extension === 'doc'
                ? 'application/msword'
                : 'application/vnd.ms-excel';
        }

        // Fileinfo is unavailable for videos. Validate their extension here;
        // the upload() method still requires the extension to be in the
        // explicit video allow-list.
        return match ($extension) {
            'mp4'  => 'video/mp4',
            'webm' => 'video/webm',
            default => throw new RuntimeException(
                'Unable to determine uploaded file type on this server. PHP Fileinfo is disabled.'
            ),
        };
    }

    /**
     * Return a useful message for PHP upload errors.
     */
    private function uploadErrorMessage(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE =>
                'Uploaded file exceeds the server upload_max_filesize limit.',

            UPLOAD_ERR_FORM_SIZE =>
                'Uploaded file exceeds the form upload size limit.',

            UPLOAD_ERR_PARTIAL =>
                'The uploaded file was only partially uploaded.',

            UPLOAD_ERR_NO_FILE =>
                'No file was uploaded.',

            UPLOAD_ERR_NO_TMP_DIR =>
                'The server temporary upload directory is missing.',

            UPLOAD_ERR_CANT_WRITE =>
                'The server could not write the uploaded file.',

            UPLOAD_ERR_EXTENSION =>
                'A PHP extension stopped the file upload.',

            default =>
                'Unknown file upload error.',
        };
    }

    /**
     * Upload image/video.
     */
    private function upload(
        string $field,
        string $directory,
        ?string $oldPath,
        bool $allowVideo
    ): ?string {
        if (!isset($_FILES[$field])) {
            return null;
        }

        $file = $_FILES[$field];

        $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);

        /*
         * No file selected is normal when editing a record.
         */
        if ($error === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        /*
         * Any other PHP upload error must be reported.
         */
        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException(
                $this->uploadErrorMessage($error)
            );
        }

        $tmpName = (string)($file['tmp_name'] ?? '');

        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            throw new RuntimeException(
                'Uploaded file is missing or invalid.'
            );
        }

        $originalName = (string)($file['name'] ?? '');

        $extension = strtolower(
            pathinfo($originalName, PATHINFO_EXTENSION)
        );

        $mime = $this->detectMime($tmpName, $extension);

        $size = (int)($file['size'] ?? 0);

        /*
         * Video is allowed only for homepage-media.
         */
        $isVideo =
            $allowVideo &&
            in_array($extension, $this->videoExtensions, true) &&
            in_array($mime, $this->videoMimes, true);

        /*
         * Application-level limits.
         *
         * Images: 10 MB
         * Videos: 50 MB
         */
        $maxSize = $isVideo
            ? 50 * 1024 * 1024
            : 10 * 1024 * 1024;

        if ($size <= 0) {
            throw new RuntimeException(
                'Uploaded file is empty.'
            );
        }

        if ($size > $maxSize) {
            throw new RuntimeException(
                $isVideo
                    ? 'Video exceeds the 50 MB application limit.'
                    : 'Image exceeds the 10 MB application limit.'
            );
        }

        /*
         * Validate video.
         */
        if ($isVideo) {
            if (
                !in_array($extension, $this->videoExtensions, true) ||
                !in_array($mime, $this->videoMimes, true)
            ) {
                throw new RuntimeException(
                    'Invalid video file type.'
                );
            }
        } else {
            /*
             * Validate image extension + MIME.
             */
            if (
                !in_array($extension, $this->extensions, true) ||
                !in_array($mime, $this->mimes, true)
            ) {
                throw new RuntimeException(
                    'Invalid image type. Allowed: JPG, JPEG, PNG, WEBP.'
                );
            }

            /*
             * Verify that the file is actually an image.
             */
            $dimensions = @getimagesize($tmpName);

            if ($dimensions === false) {
                throw new RuntimeException(
                    'Uploaded file is not a valid image.'
                );
            }
        }

        /*
         * Build target directory.
         */
        $relativeDirectory = trim($directory, '/');

        $targetDir = base_path(
            'public/uploads/' . $relativeDirectory
        );

        /*
         * Create directory if necessary.
         */
        if (!is_dir($targetDir)) {
            if (!@mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
                throw new RuntimeException(
                    'Unable to create upload directory: ' . $targetDir .
                    '. Please create it and give the PHP process write permission.'
                );
            }
        }

        /*
         * Shared hosting commonly creates directories with 0755.
         * If PHP owns the directory, try to make it group-writable before
         * failing. Never suppress the final diagnostic.
         */
        if (!is_writable($targetDir)) {
            @chmod($targetDir, 0775);
        }

        if (!is_writable($targetDir)) {
            throw new RuntimeException(
                'Upload directory is not writable: ' . $targetDir .
                '. Set the directory owner to the web/PHP user or permissions to 0775.'
            );
        }

        /*
         * Generate unpredictable filename.
         */
        try {
            $randomName = bin2hex(random_bytes(16));
        } catch (\Throwable $exception) {
            throw new RuntimeException(
                'Unable to generate secure upload filename.',
                0,
                $exception
            );
        }

        $name = $randomName . '.' . $extension;

        $target = $targetDir . DIRECTORY_SEPARATOR . $name;

        /*
         * Move uploaded file.
         */
        if (!move_uploaded_file($tmpName, $target)) {
            throw new RuntimeException(
                'PHP could not move the uploaded file to: ' . $targetDir
            );
        }

        /*
         * Verify final file exists.
         */
        if (!is_file($target)) {
            throw new RuntimeException(
                'Upload appeared successful but the destination file was not created.'
            );
        }

        $newPath =
            'uploads/' .
            $relativeDirectory .
            '/' .
            $name;

        /*
         * Delete old file only after new file exists.
         */
        if (
            $oldPath &&
            str_starts_with($oldPath, 'uploads/')
        ) {
            $oldAbsolute = base_path(
                'public/' . ltrim($oldPath, '/')
            );

            if (
                is_file($oldAbsolute) &&
                realpath($oldAbsolute) !== realpath($target)
            ) {
                @unlink($oldAbsolute);
            }
        }

        return $newPath;
    }

    /**
     * Upload project/general attachment.
     */
    private function uploadAttachment(
        string $field,
        string $directory,
        ?string $oldPath
    ): ?string {
        if (!isset($_FILES[$field])) {
            return null;
        }

        $file = $_FILES[$field];

        $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($error === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException(
                $this->uploadErrorMessage($error)
            );
        }

        $tmpName = (string)($file['tmp_name'] ?? '');

        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            throw new RuntimeException(
                'Uploaded attachment is missing or invalid.'
            );
        }

        $originalName = (string)($file['name'] ?? '');

        $extension = strtolower(
            pathinfo($originalName, PATHINFO_EXTENSION)
        );

        $mime = $this->detectMime($tmpName, $extension);

        $size = (int)($file['size'] ?? 0);

        if (
            !in_array(
                $extension,
                $this->attachmentExtensions,
                true
            )
        ) {
            throw new RuntimeException(
                'Invalid attachment extension.'
            );
        }

        if (
            !in_array(
                $mime,
                $this->attachmentMimes,
                true
            )
        ) {
            throw new RuntimeException(
                'Invalid attachment MIME type.'
            );
        }

        if ($size <= 0) {
            throw new RuntimeException(
                'Uploaded attachment is empty.'
            );
        }

        if ($size > 10 * 1024 * 1024) {
            throw new RuntimeException(
                'Attachment exceeds the 10 MB limit.'
            );
        }

        $relativeDirectory = trim($directory, '/');

        $targetDir = base_path(
            'storage/' . $relativeDirectory
        );

        if (!is_dir($targetDir)) {
            if (!@mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
                throw new RuntimeException(
                    'Unable to create attachment directory: ' . $targetDir .
                    '. Please create it and give the PHP process write permission.'
                );
            }
        }

        if (!is_writable($targetDir)) {
            @chmod($targetDir, 0775);
        }

        if (!is_writable($targetDir)) {
            throw new RuntimeException(
                'Attachment directory is not writable: ' . $targetDir .
                '. Set the directory owner to the web/PHP user or permissions to 0775.'
            );
        }

        try {
            $name =
                bin2hex(random_bytes(16)) .
                '.' .
                $extension;
        } catch (\Throwable $exception) {
            throw new RuntimeException(
                'Unable to generate secure attachment filename.',
                0,
                $exception
            );
        }

        $target =
            $targetDir .
            DIRECTORY_SEPARATOR .
            $name;

        if (!move_uploaded_file($tmpName, $target)) {
            throw new RuntimeException(
                'PHP could not move the uploaded attachment.'
            );
        }

        if (!is_file($target)) {
            throw new RuntimeException(
                'Attachment was not created successfully.'
            );
        }

        /*
         * Remove previous attachment after successful new upload.
         */
        if (
            $oldPath &&
            str_starts_with($oldPath, 'storage/')
        ) {
            $oldAbsolute = base_path(
                ltrim($oldPath, '/')
            );

            if (
                is_file($oldAbsolute) &&
                realpath($oldAbsolute) !== realpath($target)
            ) {
                @unlink($oldAbsolute);
            }
        }

        return
            'storage/' .
            $relativeDirectory .
            '/' .
            $name;
    }
}