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

    /** OOXML/OPC packages whose raw XML parts are captured into transcription.xml */
    private const OOXML_EXTENSIONS = ['docx', 'xlsx', 'pptx', 'potx', 'thmx'];

    /** Primary content parts per package; slides/sheets globbed separately */
    private const OOXML_PRIMARY_PARTS = [
        'docx' => ['word/document.xml'],
        'xlsx' => ['xl/workbook.xml'],
        'pptx' => ['ppt/presentation.xml'],
        'potx' => ['ppt/presentation.xml'],
        'thmx' => ['theme/theme/theme1.xml'],
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
            if ($extension === 'html' || $extension === 'htm') {
                return $this->extractHtml($filePath);
            }

            $result = $this->extractViaTika($filePath);

            if (in_array($extension, self::OOXML_EXTENSIONS, true)) {
                $xml = $this->extractOoxml($filePath, $extension);
                if ($xml !== null) {
                    $result ??= ['transcription' => ['text' => '', 'tool' => 'Apache Tika']];
                    $result['transcription']['xml'] = $xml;
                }
            }

            return $result;
        } catch (\Throwable $e) {
            error_log("[Transcriber] Error transcribing {$urlPath}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Retain full markup, derive a text-only rendition.
     */
    private function extractHtml(string $filePath): ?array
    {
        $html = file_get_contents($filePath);
        if ($html === false) {
            return null;
        }

        $stripped = preg_replace(
            ['#<script\b[^>]*>.*?</script>#is', '#<style\b[^>]*>.*?</style>#is'],
            ' ',
            $html
        );
        $text = html_entity_decode(strip_tags($stripped), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim(preg_replace('/\s+/u', ' ', $text));

        return [
            'transcription' => [
                'text' => $text,
                'html' => $html,
                'tool' => 'PHP strip_tags',
            ]
        ];
    }

    /**
     * Concatenate primary content parts plus every slide/sheet XML into one blob.
     */
    private function extractOoxml(string $filePath, string $extension): ?string
    {
        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) {
            return null;
        }

        $parts = self::OOXML_PRIMARY_PARTS[$extension] ?? [];
        $globs = [
            'pptx' => '#^ppt/slides/slide\d+\.xml$#',
            'potx' => '#^ppt/slides/slide\d+\.xml$#',
            'xlsx' => '#^xl/worksheets/sheet\d+\.xml$#',
        ];
        $pattern = $globs[$extension] ?? null;

        $matched = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if ($pattern && preg_match($pattern, $name)) {
                $matched[] = $name;
            }
        }
        sort($matched, SORT_NATURAL);

        $segments = [];
        foreach (array_merge($parts, $matched) as $part) {
            $content = $zip->getFromName($part);
            if ($content !== false && $content !== '') {
                $segments[] = "<!-- {$part} -->\n" . $content;
            }
        }
        $zip->close();

        return $segments ? implode("\n", $segments) : null;
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