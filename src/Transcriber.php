<?php
/**
 * Transcriber.php — File transcription for Tribe file_record objects.
 *
 * Environment:
 *   TRANSCRIBE_FILE_RECORDS=true   — master switch
 *   TIKA_HOST=tika                 — hostname of Apache Tika container (default: tika)
 *   TIKA_PORT=9998                 — port of Apache Tika container (default: 9998)
 */

namespace Tribe;

class Transcriber
{
    private string $tikaBaseUrl;
    private string $uploadsRoot;
    private bool $enabled;

    /** All extensions Tika can handle (images rely on Tika's built-in Tesseract OCR) */
    private const SUPPORTED_EXTENSIONS = [
        // Images — OCR via Tesseract inside Tika
        'jpg', 'jpeg', 'png', 'bmp', 'tiff', 'tif', 'webp', 'gif',
        // Documents
        'pdf',
        'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'rtf', 'txt', 'html', 'htm', 'csv', 'xml', 'json',
        'epub', 'odt', 'ods', 'odp', 'md', 'log',
    ];

    public function __construct()
    {
        $tikaHost = $_ENV['TIKA_HOST'] ?? 'tika';
        $tikaPort = $_ENV['TIKA_PORT'] ?? '9998';
        $this->tikaBaseUrl = "http://{$tikaHost}:{$tikaPort}";
        $this->uploadsRoot = '/var/www/html';
        $this->enabled = (($_ENV['TRANSCRIBE_FILE_RECORDS'] ?? 'false') === 'true');
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Transcribe a file given its internal URL path (e.g. /uploads/2026/03/report.pdf).
     *
     * @param string $urlPath The file's URL path starting with /uploads/
     * @return array|null Transcription fields, or null if disabled/unsupported/failed.
     */
    public function transcribe(string $urlPath): ?array
    {
        if (!$this->enabled) {
            return null;
        }

        $filePath = $this->resolveFilePath($urlPath);
        if (!$filePath || !file_exists($filePath)) {
            error_log("[Transcriber] File not found: {$urlPath} (resolved: {$filePath})");
            return null;
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if (!in_array($extension, self::SUPPORTED_EXTENSIONS, true)) {
            // Silently skip unsupported types (video, audio, archives, binaries, etc.)
            return null;
        }

        try {
            return $this->extractViaTika($filePath);
        } catch (\Throwable $e) {
            error_log("[Transcriber] Error transcribing {$urlPath}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Extract text from a file using Apache Tika's REST API.
     */
    private function extractViaTika(string $filePath): ?array
    {
        $text = $this->callTikaText($filePath);
        if ($text === null || empty(trim($text))) {
            return null;
        }

        return [
            'transcription' => [
                'text'     => trim($text),
                'tool'     => 'Apache Tika',
            ]
        ];
    }

    /**
     * PUT file to Tika /tika endpoint to extract plain text.
     */
    private function callTikaText(string $filePath): ?string
    {
        $fh = fopen($filePath, 'r');
        if (!$fh) {
            error_log("[Transcriber] Cannot open file for Tika: {$filePath}");
            return null;
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->tikaBaseUrl . '/tika',
            CURLOPT_PUT            => true,
            CURLOPT_INFILE         => $fh,
            CURLOPT_INFILESIZE     => filesize($filePath),
            CURLOPT_HTTPHEADER     => ['Accept: text/plain'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);
        fclose($fh);

        if ($error) {
            error_log("[Transcriber] Tika curl error: {$error}");
            return null;
        }

        if ($httpCode !== 200) {
            error_log("[Transcriber] Tika returned HTTP {$httpCode}");
            return null;
        }

        return $response;
    }

    /**
     * Resolve a URL path like /uploads/2026/03/file.pdf to a filesystem path.
     */
    private function resolveFilePath(string $urlPath): ?string
    {
        $cleanPath = ltrim($urlPath, '/');
        $fullPath  = $this->uploadsRoot . '/' . $cleanPath;

        // Security: prevent directory traversal
        $realPath = realpath($fullPath);
        if ($realPath === false) {
            return $fullPath;
        }

        $realUploadsRoot = realpath($this->uploadsRoot);
        if ($realUploadsRoot && strpos($realPath, $realUploadsRoot) !== 0) {
            error_log("[Transcriber] Path traversal attempt blocked: {$urlPath}");
            return null;
        }

        return $realPath;
    }
}