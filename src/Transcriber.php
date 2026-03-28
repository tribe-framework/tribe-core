<?php
/**
 * Transcriber.php — File transcription for Tribe file_record objects.
 *
 * Routing rules (mutually exclusive — only ONE tool ever touches a file):
 *
 *   1. Images (jpg, png, bmp, tiff, webp, gif)  →  PaddleOCR only. Never Tika.
 *   2. Documents (docx, xlsx, pptx, txt, etc.)   →  Tika only. Never PaddleOCR.
 *   3. PDF                                        →  Check if text-extractable first
 *                                                    (pdftotext, fast and local). If yes → Tika.
 *                                                    If flattened/scanned (no text layer) → PaddleOCR.
 *   4. Everything else (video, audio, archives)   →  Skip entirely. No processing.
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

    // ─── File extension allowlists (mutually exclusive) ─────────────────────────

    /** Extensions routed exclusively to PaddleOCR */
    private const OCR_EXTENSIONS = [
        'jpg', 'jpeg', 'png', 'bmp', 'tiff', 'tif', 'webp', 'gif',
    ];

    /** Extensions routed exclusively to Tika (no PDF — PDF has its own route) */
    private const TIKA_EXTENSIONS = [
        'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'rtf', 'txt', 'html', 'htm', 'csv', 'xml', 'json',
        'epub', 'odt', 'ods', 'odp', 'md', 'log',
    ];

    /** Minimum extractable characters for a PDF to be considered text-based */
    private const MIN_PDF_TEXT_LENGTH = 50;

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

        try {
            // ── Route 1: Image → PaddleOCR only ────────────────────────────────
            if (in_array($extension, self::OCR_EXTENSIONS, true)) {
                return $this->ocrImage($filePath);
            }

            // ── Route 2: PDF → decide tool based on whether text is extractable
            if ($extension === 'pdf') {
                return $this->transcribePdf($filePath);
            }

            // ── Route 3: Document → Tika only ──────────────────────────────────
            if (in_array($extension, self::TIKA_EXTENSIONS, true)) {
                return $this->extractViaTika($filePath);
            }

            // ── Route 4: Unsupported (video, audio, archive, binary, etc.) ─────
            // Silently skip — these files cannot be text-transcribed.
            return null;

        } catch (\Throwable $e) {
            error_log("[Transcriber] Error transcribing {$urlPath}: " . $e->getMessage());
            return null;
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  PDF routing — the only case where tool selection is conditional
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Determine whether a PDF is text-based or image-based (flattened/scanned),
     * then route to exactly one tool.
     *
     * Strategy: use pdftotext (poppler-utils, already in the Docker image) to
     * probe for an embedded text layer. This is a fast, local check — no network
     * call to Tika. If pdftotext extracts sufficient text, the PDF is text-based
     * and we send it to Tika (which handles structure, metadata, and encoding
     * better than raw pdftotext). If pdftotext returns little or nothing, the
     * PDF is scanned/flattened and we send it to PaddleOCR.
     *
     * Result: only ONE tool ever processes the PDF.
     */
    private function transcribePdf(string $filePath): ?array
    {
        $isTextBased = $this->pdfHasTextLayer($filePath);

        if ($isTextBased) {
            // Text-based PDF → Tika only
            return $this->extractViaTika($filePath);
        }

        // Scanned/flattened PDF → PaddleOCR only
        return $this->ocrPdf($filePath);
    }

    /**
     * Fast local check: does this PDF contain an extractable text layer?
     * Uses pdftotext from poppler-utils (already installed in the Docker image).
     */
    private function pdfHasTextLayer(string $filePath): bool
    {
        $cmd = sprintf(
            'pdftotext %s - 2>/dev/null | head -c 500',
            escapeshellarg($filePath)
        );

        $output = shell_exec($cmd);
        $textLength = mb_strlen(trim($output ?? ''));

        return $textLength >= self::MIN_PDF_TEXT_LENGTH;
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  Tika extraction (documents + text-based PDFs)
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Extract text from a document using Apache Tika's REST API.
     * Called ONLY for documents and text-based PDFs. Never for images.
     */
    private function extractViaTika(string $filePath): ?array
    {
        $text = $this->callTikaText($filePath);
        if ($text === null || empty(trim($text))) {
            return null;
        }

        $language = $this->detectLanguageViaTika($filePath) ?? 'en';

        return [
            'transcription' => [
                'text' => trim($text),
                'language' => $language,
                'tool' => 'Apache Tika',
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
            CURLOPT_URL => $this->tikaBaseUrl . '/tika',
            CURLOPT_PUT => true,
            CURLOPT_INFILE => $fh,
            CURLOPT_INFILESIZE => filesize($filePath),
            CURLOPT_HTTPHEADER => ['Accept: text/plain'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
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
     * Detect language via Tika's /language/stream endpoint.
     */
    private function detectLanguageViaTika(string $filePath): ?string
    {
        $fh = fopen($filePath, 'r');
        if (!$fh) {
            return null;
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->tikaBaseUrl . '/language/stream',
            CURLOPT_PUT => true,
            CURLOPT_INFILE => $fh,
            CURLOPT_INFILESIZE => filesize($filePath),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fh);

        if ($httpCode === 200 && !empty(trim($response))) {
            return trim($response);
        }

        return null;
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  PaddleOCR (images + scanned/flattened PDFs)
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Run PaddleOCR on a single image file.
     * Called ONLY for image files. Never for documents.
     */
    private function ocrImage(string $filePath): ?array
    {
        $result = $this->callPaddleOCR($filePath);
        if (!$result || empty($result['text'])) {
            return null;
        }

        return [
            'transcription' => [
                'text' => $result['text'],
                'language' => $result['language'] ?? 'en',
                'tool' => 'Paddle OCR',
            ]
        ];
    }

    /**
     * OCR a scanned/flattened PDF: render pages to temporary images via
     * Ghostscript, run PaddleOCR on each page, concatenate, then delete temps.
     * Called ONLY when pdfHasTextLayer() returned false. Tika is never involved.
     */
    private function ocrPdf(string $filePath): ?array
    {
        $tmpDir = sys_get_temp_dir() . '/tribe_ocr_' . uniqid();
        if (!mkdir($tmpDir, 0755, true)) {
            error_log("[Transcriber] Failed to create temp dir: {$tmpDir}");
            return null;
        }

        try {
            // Render PDF pages to PNG at 200 DPI
            $gsCmd = sprintf(
                'gs -dNOPAUSE -dBATCH -sDEVICE=png16m -r200 '
                . '-dTextAlphaBits=4 -dGraphicsAlphaBits=4 '
                . '-sOutputFile=%s/page_%%04d.png %s 2>&1',
                escapeshellarg($tmpDir),
                escapeshellarg($filePath)
            );

            exec($gsCmd, $gsOutput, $gsReturnCode);

            if ($gsReturnCode !== 0) {
                error_log("[Transcriber] Ghostscript failed (code {$gsReturnCode}): " . implode("\n", $gsOutput));
                return null;
            }

            $pageImages = glob($tmpDir . '/page_*.png');
            if (empty($pageImages)) {
                error_log("[Transcriber] No page images generated from PDF");
                return null;
            }
            sort($pageImages);

            $allText = [];
            $bestLang = 'en';
            $bestLangConf = 0.0;

            foreach ($pageImages as $pageImage) {
                $ocrResult = $this->callPaddleOCR($pageImage);
                if ($ocrResult && !empty($ocrResult['text'])) {
                    $allText[] = $ocrResult['text'];
                    // Track the language with highest confidence across all pages
                    $conf = $ocrResult['confidence'] ?? 0;
                    if ($conf > $bestLangConf) {
                        $bestLangConf = $conf;
                        $bestLang = $ocrResult['language'] ?? 'en';
                    }
                }
            }

            if (empty($allText)) {
                return null;
            }

            return [
                'transcription' => [
                    'text' => implode("\n\n--- Page Break ---\n\n", $allText),
                    'language' => $bestLang,
                    'tool' => 'Paddle OCR',
                ]
            ];

        } finally {
            $this->removeDirectory($tmpDir);
        }
    }

    /**
     * Call the PaddleOCR Python wrapper script and parse its JSON output.
     */
    private function callPaddleOCR(string $imagePath): ?array
    {
        $cmd = sprintf(
            'python3 /usr/local/bin/paddle_ocr.py %s 2>/dev/null',
            escapeshellarg($imagePath)
        );

        $output = shell_exec($cmd);

        if (empty($output)) {
            error_log("[Transcriber] PaddleOCR returned no output for: {$imagePath}");
            return null;
        }

        $result = json_decode(trim($output), true);

        if (!$result || isset($result['error'])) {
            error_log("[Transcriber] PaddleOCR error: " . ($result['error'] ?? 'invalid JSON'));
            return null;
        }

        return $result;
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  Utilities
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Resolve a URL path like /uploads/2026/03/file.pdf to a filesystem path.
     */
    private function resolveFilePath(string $urlPath): ?string
    {
        $cleanPath = ltrim($urlPath, '/');
        $fullPath = $this->uploadsRoot . '/' . $cleanPath;

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

    /**
     * Recursively remove a directory and its contents.
     */
    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->removeDirectory($path) : @unlink($path);
        }
        @rmdir($dir);
    }
}
