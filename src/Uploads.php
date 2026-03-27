<?php
namespace Tribe;

class Uploads {

	/**
	 * Default allowed MIME types and their extensions
	 */
	private array $allowed_mime_types = [
		// Images
		'image/jpeg'          => ['jpg', 'jpeg'],
		'image/png'           => ['png'],
		'image/gif'           => ['gif'],
		'image/webp'          => ['webp'],
		'image/svg+xml'       => ['svg'],
		// Video
		'video/mp4'           => ['mp4'],
		'video/quicktime'     => ['mov'],
		'video/x-msvideo'     => ['avi'],
		'video/x-matroska'    => ['mkv'],
		'video/webm'          => ['webm'],
		// Audio
		'audio/mpeg'          => ['mp3'],
		'audio/mp4'           => ['m4a'],
		'audio/ogg'           => ['ogg', 'oga'],
		'audio/wav'           => ['wav'],
		'audio/x-wav'         => ['wav'],
		// Documents
		'application/pdf'                                                          => ['pdf'],
		'application/msword'                                                       => ['doc'],
		'application/vnd.openxmlformats-officedocument.wordprocessingml.document'   => ['docx'],
		'application/vnd.ms-excel'                                                 => ['xls'],
		'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'        => ['xlsx'],
		'application/vnd.ms-powerpoint'                                            => ['ppt'],
		'application/vnd.openxmlformats-officedocument.presentationml.presentation'=> ['pptx'],
		'application/zip'           => ['zip'],
		'application/x-rar-compressed' => ['rar'],
		'text/plain'                => ['txt'],
		'text/csv'                  => ['csv'],
		'text/vtt'                  => ['vtt'],
		'application/x-subrip'      => ['srt'],
		'application/json'          => ['json'],
	];

	/**
	 * Image size variants for resizing
	 */
	private array $image_versions = [
		'xl' => ['max_width' => 2100, 'max_height' => 2100],
		'lg' => ['max_width' => 1400, 'max_height' => 1400],
		'md' => ['max_width' => 700,  'max_height' => 700],
		'sm' => ['max_width' => 350,  'max_height' => 350],
		'xs' => ['max_width' => 100,  'max_height' => 100],
	];

	public function getUploaderPath()
	{
		$folder_path = 'uploads/' . date('Y') . '/' . date('m-F') . '/' . date('d-D');
		if (!is_dir($folder_path)) {
			mkdir($folder_path, 0755, true);
			mkdir($folder_path . '/xs', 0755, true);
			mkdir($folder_path . '/sm', 0755, true);
			mkdir($folder_path . '/md', 0755, true);
			mkdir($folder_path . '/lg', 0755, true);
			mkdir($folder_path . '/xl', 0755, true);
			mkdir($folder_path . '/hls', 0755, true);
		}

		return array('upload_dir' => $folder_path, 'upload_url' => '/'.$folder_path);
	}

	public function getUploadedImageInSize($file_url, $thumbnail = 'md')
	{
		if (preg_match('/\.(gif|jpe?g|png)$/i', $file_url)) {
			$file_arr = array();
			$file_parts = explode('/', $file_url);
			$file_parts = array_reverse($file_parts);
			$filename = urldecode($file_parts[0]);
			if (strlen($file_parts[1]) == 2) {
				$year = $file_parts[4];
				$month = $file_parts[3];
				$day = $file_parts[2];
				$size = $file_parts[1];
			} else {
				$year = $file_parts[3];
				$month = $file_parts[2];
				$day = $file_parts[1];
			}

			if (file_exists('/uploads/' . $year . '/' . $month . '/' . $day . '/' . $thumbnail . '/' . substr(escapeshellarg($filename), 1, -1))) {
				$file_arr['path'] = '/uploads/' . $year . '/' . $month . '/' . $day . '/' . $thumbnail . '/' . substr(escapeshellarg($filename), 1, -1);
				$file_arr['url'] = '/uploads/' . $year . '/' . $month . '/' . $day . '/' . $thumbnail . '/' . rawurlencode($filename);
			}
			else {
				$file_arr['path'] = '/uploads/' . $year . '/' . $month . '/' . $day . '/' . substr(escapeshellarg($filename), 1, -1);
				$file_arr['url'] = '/uploads/' . $year . '/' . $month . '/' . $day . '/' . rawurlencode($filename);
			}

			return $file_arr;
		} else {
			return false;
		}
	}

	public function getUploadedFileVersions($file_url, $thumbnail = 'xs')
	{
		$file_arr = array();
		$file_parts = explode('/', $file_url);
		$file_parts = array_reverse($file_parts);
		$filename = urldecode($file_parts[0]);

		if (strlen($file_parts[1]) == 2) {
			$year = $file_parts[4];
			$month = $file_parts[3];
			$day = $file_parts[2];
			$size = $file_parts[1];
		} else {
			$year = $file_parts[3];
			$month = $file_parts[2];
			$day = $file_parts[1];
		}

		$file_arr['path']['source'] = '/uploads/' . $year . '/' . $month . '/' . $day . '/' . substr(escapeshellarg($filename), 1, -1);
		$file_arr['url']['source'] = '/uploads/' . $year . '/' . $month . '/' . $day . '/' . rawurlencode($filename);

		if (preg_match('/\.(gif|jpe?g|png)$/i', $file_url)) {
			$sizes = array('xl', 'lg', 'md', 'sm', 'xs');

			foreach ($sizes as $size) {
				if (file_exists('/uploads/' . $year . '/' . $month . '/' . $day . '/' . $size . '/' . $filename)) {
					$file_arr['path'][$size] = '/uploads/' . $year . '/' . $month . '/' . $day . '/' . $size . '/' . substr(escapeshellarg($filename), 1, -1);
					$file_arr['url'][$size] = '/uploads/' . $year . '/' . $month . '/' . $day . '/' . $size . '/' . rawurlencode($filename);
				}
				else {
					$file_arr['path'][$size] = $file_arr['path']['source'];
					$file_arr['url'][$size] = $file_arr['url']['source'];
				}
			}

			if (file_exists($file_arr['path'][$thumbnail])) {
				$file_arr['url']['thumbnail'] = $file_arr['url'][$thumbnail];
				$file_arr['path']['thumbnail'] = $file_arr['path'][$thumbnail];
			} else {
				$file_arr['url']['thumbnail'] = $file_arr['url']['source'];
				$file_arr['path']['thumbnail'] = $file_arr['path']['source'];
			}
		}

		// Check for HLS version ONLY for MP4 and MOV videos
		if (preg_match('/\.(mp4|mov)$/i', $file_url)) {
			$hls_path = '/uploads/' . $year . '/' . $month . '/' . $day . '/hls/' . pathinfo($filename, PATHINFO_FILENAME) . '.m3u8';
			$hls_url = '/uploads/' . $year . '/' . $month . '/' . $day . '/hls/' . pathinfo($filename, PATHINFO_FILENAME) . '.m3u8';
			
			if (file_exists($hls_path)) {
				$file_arr['path']['hls'] = $hls_path;
				$file_arr['url']['hls'] = $hls_url;
				
				// Check for different quality versions
				$video_qualities = ['xl', 'lg', 'md', 'sm', 'xs'];
				foreach ($video_qualities as $quality) {
					$quality_hls_path = '/uploads/' . $year . '/' . $month . '/' . $day . '/hls/' . pathinfo($filename, PATHINFO_FILENAME) . '_' . $quality . '.m3u8';
					if (file_exists($quality_hls_path)) {
						$file_arr['path']['hls_' . $quality] = $quality_hls_path;
						$file_arr['url']['hls_' . $quality] = '/uploads/' . $year . '/' . $month . '/' . $day . '/hls/' . pathinfo($filename, PATHINFO_FILENAME) . '_' . $quality . '.m3u8';
					}
				}
			}
		}

		return $file_arr;
	}

	public function deleteFileRecord($object) {
		if ($object['url'] ?? false) {
			unlink($object['url']);
		}

		if ($object['file']['lg']['url'] ?? false) {
			unlink($object['file']['lg']['url']);
		}

		if ($object['file']['md']['url'] ?? false) {
			unlink($object['file']['md']['url']);
		}

		if ($object['file']['sm']['url'] ?? false) {
			unlink($object['file']['sm']['url']);
		}

		if ($object['file']['xl']['url'] ?? false) {
			unlink($object['file']['xl']['url']);
		}

		if ($object['file']['xs']['url'] ?? false) {
			unlink($object['file']['xs']['url']);
		}

		// Delete HLS files
		if ($object['file']['hls']['url'] ?? false) {
			$hls_dir = dirname($object['file']['hls']['url']);
			if (is_dir($hls_dir)) {
				// Remove all HLS files in the directory
				$files = glob($hls_dir . '/*');
				foreach($files as $file) {
					if(is_file($file)) {
						unlink($file);
					}
				}
				rmdir($hls_dir);
			}
		}
	}

	public function copyFileFromURL($url)
	{
		if ($url ?? false) {
			$path = $this->getUploaderPath();

			$file_name = time() . '-' . basename($url);
			$wf_uploads_path = $path['upload_dir'] . '/' . $file_name;
			$wf_uploads_url = $path['upload_url'] . '/' . $file_name;

			if (copy($url, $wf_uploads_path)) {
				return $wf_uploads_url;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	public function handleFileSearch($query, $deep_search = false) {
		$strings = array_map('trim', explode('##', urldecode($query)));
        $filenames_op = [];
        $filecontents_op = [];
        $filenames_or = [];
        $filecontents_or = [];

        $search_q = '^(?=.*'.implode(')(?=.*', $strings).')';

        $files = explode(PHP_EOL, shell_exec('grep -PRil "'.$search_q.'" /uploads/'));

        if ($deep_search) {
	        $files = array_merge($files, explode(PHP_EOL, shell_exec('timeout 7 pdfgrep -PRil "'.$search_q.'" /uploads/')));
        }

        $filenames = explode(PHP_EOL, shell_exec("find /uploads -not -path '*/[@.]*' -type f"));

        foreach ($strings as $string) {
            $filenames_op = array_merge($filenames_op, preg_grep("/".$search_q."/i", $filenames));
        }

        foreach ($filenames_op as $file) {
            $filenames_or[] = $file;
        }

        foreach ($files as $file) {
            if ($file !== '')
                $filenames_op[] = $file;
        }

        foreach ($filenames_op as $file) {
            $filecontents_or[] = $file;
        }

        $filenames_or = array_unique($filenames_or);
        $filenames_or = array_combine(array_map('basename', $filenames_or), $filenames_or);

        $filecontents_or = array_unique($filecontents_or);
        $filecontents_or = array_combine(array_map('basename', $filecontents_or), $filecontents_or);

        return array('by_file_name'=>$filenames_or, 'by_file_content'=>$filecontents_or);
	}

	/**
	 * Video quality configurations for HLS streaming
	 */
	private function getVideoQualitySettings() {
		return [
			'xl' => [
				'resolution' => '3840x2160',
				'bitrate_video' => '15000k',
				'bitrate_audio' => '192k',
				'label' => '2160p'
			],
			'lg' => [
				'resolution' => '1920x1080',
				'bitrate_video' => '8000k',
				'bitrate_audio' => '128k',
				'label' => '1080p'
			],
			'md' => [
				'resolution' => '1280x720',
				'bitrate_video' => '4000k',
				'bitrate_audio' => '128k',
				'label' => '720p'
			],
			'sm' => [
				'resolution' => '960x540',
				'bitrate_video' => '2000k',
				'bitrate_audio' => '96k',
				'label' => '540p'
			],
			'xs' => [
				'resolution' => '640x360',
				'bitrate_video' => '1000k',
				'bitrate_audio' => '64k',
				'label' => '360p'
			]
		];
	}

	/**
	 * Convert video to multiple quality HLS streams
	 */
	private function convertToMultiQualityHLS($input_path, $output_dir, $filename_base) {
		// Create HLS output directory
		if (!is_dir($output_dir)) {
			mkdir($output_dir, 0755, true);
		}

		$input_escaped = escapeshellarg($input_path);
		$quality_settings = $this->getVideoQualitySettings();
		$master_playlist_path = $output_dir . '/' . $filename_base . '.m3u8';
		
		// Get video info to determine available qualities
		$video_info_cmd = "ffprobe -v quiet -print_format json -show_streams " . $input_escaped;
		$video_info = json_decode(shell_exec($video_info_cmd), true);
		
		$source_width = 0;
		$source_height = 0;
		foreach ($video_info['streams'] as $stream) {
			if ($stream['codec_type'] === 'video') {
				$source_width = $stream['width'] ?? 0;
				$source_height = $stream['height'] ?? 0;
				break;
			}
		}

		$ffmpeg_commands = [];
		$available_qualities = [];
		
		// Build FFmpeg command for multiple outputs
		$ffmpeg_base = "ffmpeg -i {$input_escaped}";
		$output_options = [];
		$map_options = [];
		$var_stream_map = [];
		
		$stream_index = 0;
		foreach ($quality_settings as $quality => $settings) {
			// Parse target resolution
			list($target_width, $target_height) = explode('x', $settings['resolution']);
			
			// Only create quality if source is equal or higher resolution
			if ($source_height >= $target_height) {
				$playlist_name = $filename_base . '_' . $quality . '.m3u8';
				$segment_name = $filename_base . '_' . $quality . '_%03d.ts';
				
				// Video encoding options
				$video_options = "-c:v libx264 -preset medium -crf 23 " .
					"-maxrate {$settings['bitrate_video']} -bufsize " . (intval($settings['bitrate_video']) * 2) . "k " .
					"-vf \"scale=-2:{$target_height}\" " .
					"-g 48 -keyint_min 48 -sc_threshold 0";
				
				// Audio encoding options
				$audio_options = "-c:a aac -b:a {$settings['bitrate_audio']} -ar 44100";
				
				// HLS options
				$hls_options = "-f hls -hls_time 6 -hls_list_size 0 " .
					"-hls_segment_filename " . escapeshellarg($output_dir . '/' . $segment_name);
				
				$output_options[] = "{$video_options} {$audio_options} {$hls_options} " . 
					escapeshellarg($output_dir . '/' . $playlist_name);
				
				$available_qualities[] = [
					'quality' => $quality,
					'label' => $settings['label'],
					'resolution' => $settings['resolution'],
					'bitrate' => $settings['bitrate_video'],
					'filename' => $playlist_name
				];
				
				$stream_index++;
			}
		}
		
		// If no qualities are available, create at least one stream at source resolution
		if (empty($available_qualities)) {
			$playlist_name = $filename_base . '_source.m3u8';
			$segment_name = $filename_base . '_source_%03d.ts';
			
			$output_options[] = "-c:v libx264 -preset medium -crf 23 " .
				"-c:a aac -b:a 128k -ar 44100 " .
				"-f hls -hls_time 6 -hls_list_size 0 " .
				"-hls_segment_filename " . escapeshellarg($output_dir . '/' . $segment_name) . " " .
				escapeshellarg($output_dir . '/' . $playlist_name);
			
			$available_qualities[] = [
				'quality' => 'source',
				'label' => 'Original',
				'resolution' => $source_width . 'x' . $source_height,
				'bitrate' => '5000k',
				'filename' => $playlist_name
			];
		}
		
		// Build complete FFmpeg command
		$complete_command = $ffmpeg_base;
		foreach ($output_options as $output) {
			$complete_command .= " " . $output;
		}
		$complete_command .= " 2>/tmp/ffmpeg_{$filename_base}.log > /dev/null 2>&1 &";
		
		// Execute FFmpeg command
		exec($complete_command);
		
		// Create master playlist
		$this->createMasterPlaylist($master_playlist_path, $available_qualities, $filename_base);
		
		return str_replace('/var/www/html/', '/', $master_playlist_path);
	}

	/**
	 * Create HLS master playlist for adaptive streaming
	 */
	private function createMasterPlaylist($master_playlist_path, $available_qualities, $filename_base) {
		$content = "#EXTM3U\n";
		$content .= "#EXT-X-VERSION:3\n\n";
		
		foreach ($available_qualities as $quality_info) {
			// Parse bitrate (remove 'k' suffix and convert to bits)
			$bitrate_kbps = intval(str_replace('k', '', $quality_info['bitrate']));
			$bitrate_bps = $bitrate_kbps * 1000;
			
			$content .= "#EXT-X-STREAM-INF:BANDWIDTH={$bitrate_bps},RESOLUTION={$quality_info['resolution']},NAME=\"{$quality_info['label']}\"\n";
			$content .= $quality_info['filename'] . "\n\n";
		}
		
		// Write master playlist file
		file_put_contents($master_playlist_path, $content);
	}

	/**
	 * Legacy method - now calls the new multi-quality version
	 */
	private function convertToHLS($input_path, $output_dir, $filename_base) {
		return $this->convertToMultiQualityHLS($input_path, $output_dir, $filename_base);
	}

	/**
	 * Check if HLS conversion is complete
	 */
	public function isHLSReady($hls_path) {
		if (!file_exists($hls_path)) {
			return false;
		}
		
		// For master playlist, check if it has content
		$content = file_get_contents($hls_path);
		if (strpos($content, '#EXT-X-STREAM-INF:') !== false) {
			// This is a master playlist, check individual quality playlists
			preg_match_all('/^(?!#).*\.m3u8$/m', $content, $matches);
			$playlist_dir = dirname($hls_path);
			
			foreach ($matches[0] as $playlist_file) {
				$playlist_path = $playlist_dir . '/' . trim($playlist_file);
				if (!file_exists($playlist_path)) {
					return false;
				}
				
				$playlist_content = file_get_contents($playlist_path);
				if (strpos($playlist_content, '#EXT-X-ENDLIST') === false) {
					return false;
				}
			}
			return true;
		}
		
		// Regular playlist - check for end marker
		return strpos($content, '#EXT-X-ENDLIST') !== false;
	}

	/**
	 * Get HLS conversion status with quality information
	 */
	public function getHLSStatus($filename_base) {
		$log_file = "/tmp/ffmpeg_{$filename_base}.log";
		
		if (!file_exists($log_file)) {
			return [
				'status' => 'not_started',
				'progress' => 0,
				'qualities' => []
			];
		}
		
		$log_content = file_get_contents($log_file);
		
		// Check for completion indicators
		if (strpos($log_content, 'video:') !== false && strpos($log_content, 'audio:') !== false) {
			return [
				'status' => 'completed',
				'progress' => 100,
				'qualities' => $this->getAvailableQualities($filename_base)
			];
		}
		
		if (strpos($log_content, 'Error') !== false || strpos($log_content, 'failed') !== false) {
			return [
				'status' => 'failed',
				'progress' => 0,
				'error' => $this->extractErrorFromLog($log_content)
			];
		}
		
		// Try to extract progress information
		$progress = $this->extractProgressFromLog($log_content);
		
		return [
			'progress' => $progress,
			'qualities' => $this->getAvailableQualities($filename_base)
		];
	}

	/**
	 * Extract progress percentage from FFmpeg log
	 */
	private function extractProgressFromLog($log_content) {
		// Look for time indicators in FFmpeg output
		preg_match_all('/time=(\d{2}):(\d{2}):(\d{2})\.(\d{2})/', $log_content, $matches);
		
		if (!empty($matches[0])) {
			$last_time = end($matches[0]);
			return min(50, count($matches[0]) * 2);
		}
		
		return 0;
	}

	/**
	 * Extract error message from FFmpeg log
	 */
	private function extractErrorFromLog($log_content) {
		$lines = explode("\n", $log_content);
		foreach (array_reverse($lines) as $line) {
			if (stripos($line, 'error') !== false || stripos($line, 'failed') !== false) {
				return trim($line);
			}
		}
		return 'Unknown error occurred during conversion';
	}

	/**
	 * Get available quality streams for a video
	 */
	private function getAvailableQualities($filename_base) {
		$qualities = [];
		$quality_settings = $this->getVideoQualitySettings();
		
		foreach ($quality_settings as $quality => $settings) {
			$qualities[] = [
				'quality' => $quality,
				'label' => $settings['label'],
				'resolution' => $settings['resolution']
			];
		}
		
		return $qualities;
	}

	/**
	 * Validate the target parameter: must be 'dist' or 'dist-php'.
	 */
	private function validateDistTarget(string $target): bool {
		return in_array($target, ['dist', 'dist-php'], true);
	}

	/**
	 * Deploy a dist folder or zip file to /uploads/sites/{target}.
	 *
	 * Accepts:
	 *   - A single .zip file  ($_FILES['file'])
	 *   - Multiple files from a folder upload ($_FILES['files'], sent via
	 *     <input webkitdirectory> which provides relative paths in
	 *     $_POST['paths[]'])
	 *
	 * The previous live folder is moved to /uploads/sites/{target}-{time()},
	 * and at most 7 backups are kept.
	 *
	 * @param array  $files_server_arr  $_FILES
	 * @param array  $post_server_arr   $_POST
	 * @param string $target            'dist' or 'dist-php'
	 */
	public function handleDistUpload(array $files_server_arr, array $post_server_arr = [], string $target = 'dist') {
		if (!$this->validateDistTarget($target)) {
			return ['status' => 'error', 'error_message' => 'Invalid target. Must be "dist" or "dist-php".'];
		}

		$sites_dir    = '/var/www/html/uploads/sites';
		$dist_target  = $sites_dir . '/' . $target;
		$max_versions = 7;

		// ── Determine upload mode: zip or folder ──────────────────────────────
		$has_zip    = !empty($files_server_arr['file']['tmp_name']);
		$has_folder = !empty($files_server_arr['files']);

		if (!$has_zip && !$has_folder) {
			return ['status' => 'error', 'error_message' => 'No file received.'];
		}

		// ── 1. Back up current live folder ────────────────────────────────────
		if (is_dir($dist_target)) {
			$backup_name = $target . '-' . time();
			$backup_path = $sites_dir . '/' . $backup_name;
			rename($dist_target, $backup_path);
		}

		// Ensure sites dir exists
		if (!is_dir($sites_dir)) {
			mkdir($sites_dir, 0755, true);
		}

		// ── 2a. ZIP upload ─────────────────────────────────────────────────────
		if ($has_zip) {
			$tmp_path  = $files_server_arr['file']['tmp_name'];
			$orig_name = $files_server_arr['file']['name'];
			$mime      = mime_content_type($tmp_path);

			$is_zip = in_array($mime, ['application/zip', 'application/x-zip-compressed', 'application/octet-stream'])
			          || strtolower(pathinfo($orig_name, PATHINFO_EXTENSION)) === 'zip';

			if (!$is_zip) {
				// Restore backup if we moved it
				if (isset($backup_path) && is_dir($backup_path)) {
					rename($backup_path, $dist_target);
				}
				return ['status' => 'error', 'error_message' => 'Only .zip files or folder uploads are supported.'];
			}

			$extract_tmp = sys_get_temp_dir() . '/dist_upload_' . uniqid();
			mkdir($extract_tmp, 0755, true);

			$zip = new \ZipArchive();
			if ($zip->open($tmp_path) !== true) {
				if (isset($backup_path) && is_dir($backup_path)) rename($backup_path, $dist_target);
				return ['status' => 'error', 'error_message' => 'Could not open zip file.'];
			}
			$zip->extractTo($extract_tmp);
			$zip->close();

			// Look for a folder matching the target name inside the zip, then
			// any 'dist' folder, then fall back to the extracted root.
			$found = $this->findFolderByName($extract_tmp, $target)
			      ?? $this->findFolderByName($extract_tmp, 'dist')
			      ?? $extract_tmp;

			$this->copyDirectory($found, $dist_target);
			$this->deleteDirectory($extract_tmp);
		}

		// ── 2b. Folder upload (webkitdirectory / drag-and-drop directory) ─────
		else {
			// $_FILES['files'] arrives as a multi-file array.
			// $_POST['paths'] holds the relative paths within the folder.
			$files_arr  = $files_server_arr['files'];
			$rel_paths  = $post_server_arr['paths'] ?? [];

			// Normalise to a list of [tmp, rel_path] pairs
			$count = is_array($files_arr['tmp_name']) ? count($files_arr['tmp_name']) : 0;
			if ($count === 0) {
				if (isset($backup_path) && is_dir($backup_path)) rename($backup_path, $dist_target);
				return ['status' => 'error', 'error_message' => 'No files received in folder upload.'];
			}

			for ($i = 0; $i < $count; $i++) {
				if ($files_arr['error'][$i] !== UPLOAD_ERR_OK) continue;

				$tmp  = $files_arr['tmp_name'][$i];
				$rel  = $rel_paths[$i] ?? $files_arr['name'][$i];

				// Strip a leading folder component that matches the target or 'dist'
				$rel = $this->stripLeadingDistComponent($rel, $target);

				// Sanitise each path segment (allow dots for extensions)
				$segments = explode('/', $rel);
				$segments = array_map(function($s) { return preg_replace('/[^a-zA-Z0-9._-]/', '_', $s); }, $segments);
				$segments = array_filter($segments, function($s) { return $s !== '' && $s !== '.' && $s !== '..'; });
				$rel      = implode('/', $segments);

				if ($rel === '') continue;

				$dest = $dist_target . '/' . $rel;
				$dir  = dirname($dest);
				if (!is_dir($dir)) mkdir($dir, 0755, true);
				move_uploaded_file($tmp, $dest);
			}
		}

		// ── 3. Prune old versions ─────────────────────────────────────────────
		$this->pruneDistVersions($sites_dir, $target, $max_versions);

		return [
			'status'   => 'success',
			'message'  => 'Deployed successfully to ' . $target . '.',
			'versions' => $this->getDistVersionsList($sites_dir, $target),
		];
	}

	/**
	 * Return the list of available backup versions (newest first).
	 *
	 * @param string $target 'dist' or 'dist-php'
	 */
	public function getDistVersions(string $target = 'dist') {
		if (!$this->validateDistTarget($target)) {
			return ['status' => 'error', 'error_message' => 'Invalid target.'];
		}
		$sites_dir = '/var/www/html/uploads/sites';
		return [
			'status'   => 'success',
			'versions' => $this->getDistVersionsList($sites_dir, $target),
		];
	}

	/**
	 * Revert to a specific backup version by its Unix timestamp.
	 *
	 * @param string $timestamp  The numeric timestamp suffix (from time())
	 * @param string $target     'dist' or 'dist-php'
	 */
	public function revertDistVersion(string $timestamp, string $target = 'dist') {
		if (!$this->validateDistTarget($target)) {
			return ['status' => 'error', 'error_message' => 'Invalid target.'];
		}

		$sites_dir    = '/var/www/html/uploads/sites';
		$dist_target  = $sites_dir . '/' . $target;
		$backup_name  = $target . '-' . $timestamp;
		$backup_path  = $sites_dir . '/' . $backup_name;
		$max_versions = 7;

		if (!is_dir($backup_path)) {
			return ['status' => 'error', 'error_message' => 'Version not found: ' . $timestamp];
		}

		// Back up current live folder before restoring
		if (is_dir($dist_target)) {
			$new_backup = $sites_dir . '/' . $target . '-' . time();
			rename($dist_target, $new_backup);
		}

		rename($backup_path, $dist_target);

		$this->pruneDistVersions($sites_dir, $target, $max_versions);

		return [
			'status'   => 'success',
			'message'  => 'Reverted ' . $target . ' to version: ' . $timestamp,
			'versions' => $this->getDistVersionsList($sites_dir, $target),
		];
	}

	// ── Private helpers ──────────────────────────────────────────────────────

	/**
	 * BFS search for a sub-directory with the given name.
	 */
	private function findFolderByName(string $base, string $name): ?string {
		$queue = [$base];
		while (!empty($queue)) {
			$dir = array_shift($queue);
			foreach (scandir($dir) as $entry) {
				if ($entry === '.' || $entry === '..') continue;
				$full = $dir . '/' . $entry;
				if (is_dir($full)) {
					if (strtolower($entry) === strtolower($name)) return $full;
					$queue[] = $full;
				}
			}
		}
		return null;
	}

	/**
	 * Strip a leading path component from $rel if it matches $target or 'dist'.
	 * e.g. "dist/assets/app.js" → "assets/app.js"
	 *      "dist-php/index.php" → "index.php"
	 */
	private function stripLeadingDistComponent(string $rel, string $target): string {
		$rel = ltrim(str_replace('\\', '/', $rel), '/');
		foreach ([$target, 'dist'] as $prefix) {
			if (stripos($rel, $prefix . '/') === 0) {
				return substr($rel, strlen($prefix) + 1);
			}
		}
		return $rel;
	}

	private function copyDirectory(string $src, string $dst): void {
		if (!is_dir($dst)) mkdir($dst, 0755, true);
		foreach (scandir($src) as $entry) {
			if ($entry === '.' || $entry === '..') continue;
			$s = $src . '/' . $entry;
			$d = $dst . '/' . $entry;
			is_dir($s) ? $this->copyDirectory($s, $d) : copy($s, $d);
		}
	}

	private function deleteDirectory(string $dir): void {
		if (!is_dir($dir)) return;
		foreach (scandir($dir) as $entry) {
			if ($entry === '.' || $entry === '..') continue;
			$path = $dir . '/' . $entry;
			is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
		}
		rmdir($dir);
	}

	/**
	 * Delete oldest backups beyond $max for a given target.
	 * Backup folders are named: {target}-{unix_timestamp}
	 */
	private function pruneDistVersions(string $sites_dir, string $target, int $max): void {
		$versions  = $this->getDistVersionsList($sites_dir, $target);
		$to_delete = array_slice($versions, $max);
		foreach ($to_delete as $v) {
			$this->deleteDirectory($sites_dir . '/' . $target . '-' . $v['timestamp']);
		}
	}

	/**
	 * Return sorted (newest first) list of backup versions for $target.
	 * Folder naming: {target}-{unix_timestamp}
	 */
	private function getDistVersionsList(string $sites_dir, string $target): array {
		$versions = [];
		if (!is_dir($sites_dir)) return $versions;

		$prefix = $target . '-';
		foreach (scandir($sites_dir) as $entry) {
			if (strpos($entry, $prefix) !== 0) continue;
			$ts = substr($entry, strlen($prefix));
			if (!ctype_digit($ts)) continue;
			$dt = date('Y/m/d H:i', (int)$ts);
			$versions[] = [
				'timestamp' => $ts,
				'label'     => $dt,
				'path'      => $sites_dir . '/' . $entry,
			];
		}

		// Sort newest first
		usort($versions, function($a, $b) { return $b['timestamp'] <=> $a['timestamp']; });
		return $versions;
	}

	/**
	 * Validate an uploaded file against allowed MIME types.
	 * Returns the detected MIME type on success, or false on failure.
	 */
	private function validateUpload(array $file_info): string|false
	{
		// Check for upload errors
		if (($file_info['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
			return false;
		}

		$tmp_path = $file_info['tmp_name'] ?? '';
		if (!$tmp_path || !file_exists($tmp_path)) {
			return false;
		}

		// Detect MIME from file content (not the client-provided type)
		$finfo = new \finfo(FILEINFO_MIME_TYPE);
		$detected_mime = $finfo->file($tmp_path);

		// SVG files are often detected as text/xml or text/html — check extension too
		$ext = strtolower(pathinfo($file_info['name'] ?? '', PATHINFO_EXTENSION));
		if ($ext === 'svg' && in_array($detected_mime, ['text/xml', 'text/html', 'text/plain', 'application/xml'])) {
			$detected_mime = 'image/svg+xml';
		}

		// VTT files detected as text/plain
		if ($ext === 'vtt' && $detected_mime === 'text/plain') {
			$detected_mime = 'text/vtt';
		}

		// SRT files detected as text/plain
		if ($ext === 'srt' && $detected_mime === 'text/plain') {
			$detected_mime = 'application/x-subrip';
		}

		// JSON files detected as text/plain
		if ($ext === 'json' && $detected_mime === 'text/plain') {
			$detected_mime = 'application/json';
		}

		if (!isset($this->allowed_mime_types[$detected_mime])) {
			return false;
		}

		return $detected_mime;
	}

	/**
	 * Move an uploaded file to the destination, generating a safe unique filename.
	 * Returns the final filename (body + extension) or false on failure.
	 */
	private function moveUploadedFile(array $file_info, string $dest_dir): string|false
	{
		$original_name = $file_info['name'] ?? 'file';
		$ext  = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
		$body = pathinfo($original_name, PATHINFO_FILENAME);

		// Sanitize the filename body: keep only safe characters
		$body = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $body);
		$body = $body . '_' . uniqid();

		$final_name = $body . '.' . $ext;
		$dest_path  = $dest_dir . '/' . $final_name;

		if (is_uploaded_file($file_info['tmp_name'])) {
			$success = move_uploaded_file($file_info['tmp_name'], $dest_path);
		} else {
			// For testing or internal moves
			$success = rename($file_info['tmp_name'], $dest_path);
		}

		return $success ? $final_name : false;
	}

	/**
	 * Resize an image using GD library.
	 * Supports JPEG, PNG, WEBP, and GIF.
	 * Maintains aspect ratio within the given max dimensions.
	 */
	private function resizeImage(string $source_path, string $dest_path, int $max_width, int $max_height): bool
	{
		$image_info = @getimagesize($source_path);
		if (!$image_info) {
			return false;
		}

		$orig_width  = $image_info[0];
		$orig_height = $image_info[1];
		$mime        = $image_info['mime'];

		// No resize needed if already within bounds
		if ($orig_width <= $max_width && $orig_height <= $max_height) {
			return copy($source_path, $dest_path);
		}

		// Calculate new dimensions preserving aspect ratio
		$ratio = min($max_width / $orig_width, $max_height / $orig_height);
		$new_width  = (int) round($orig_width * $ratio);
		$new_height = (int) round($orig_height * $ratio);

		// Create source image resource
		$source_image = match ($mime) {
			'image/jpeg' => @imagecreatefromjpeg($source_path),
			'image/png'  => @imagecreatefrompng($source_path),
			'image/gif'  => @imagecreatefromgif($source_path),
			'image/webp' => @imagecreatefromwebp($source_path),
			default      => false,
		};

		if (!$source_image) {
			return false;
		}

		// Create destination image
		$dest_image = imagecreatetruecolor($new_width, $new_height);

		// Preserve transparency for PNG and GIF
		if ($mime === 'image/png' || $mime === 'image/gif') {
			imagealphablending($dest_image, false);
			imagesavealpha($dest_image, true);
			$transparent = imagecolorallocatealpha($dest_image, 0, 0, 0, 127);
			imagefill($dest_image, 0, 0, $transparent);
		}

		// Resize with resampling for best quality
		imagecopyresampled(
			$dest_image, $source_image,
			0, 0, 0, 0,
			$new_width, $new_height,
			$orig_width, $orig_height
		);

		// Save in the same format
		$result = match ($mime) {
			'image/jpeg' => imagejpeg($dest_image, $dest_path, 85),
			'image/png'  => imagepng($dest_image, $dest_path, 6),
			'image/gif'  => imagegif($dest_image, $dest_path),
			'image/webp' => imagewebp($dest_image, $dest_path, 85),
			default      => false,
		};

		// Free memory
		imagedestroy($source_image);
		imagedestroy($dest_image);

		return $result;
	}

	/**
	 * Handle the full upload workflow: validation, move, resize, video conversion.
	 * Drop-in replacement for the previous verot-based handleUpload().
	 */
	public function handleUpload(array $files_server_arr, array $post_server_arr = [], array $get_server_arr = []) {

		// URL-only "upload" — just return it
		if ($post_server_arr['url'] ?? false)
			return array('status'=>'success', 'success'=>1, 'error'=>0, 'file'=>array('url'=>$post_server_arr['url']));

		// Alias: accept 'image' key as 'file'
		else if ($files_server_arr['image'] ?? false)
			$files_server_arr['file'] = $files_server_arr['image'];

		// Handle upload search
		else if (($post_server_arr['search'] ?? false) && ($post_server_arr['q'] ?? false))
			return $this->handleFileSearch($post_server_arr['q'], ($post_server_arr['deep_search'] ?? false));

		// ── Validate ─────────────────────────────────────────────────────────
		$file_input = $files_server_arr['file'] ?? null;
		if (!$file_input) {
			return array('status'=>'error', 'success'=>0, 'error'=>1, 'error_message'=>'No file provided.');
		}

		$detected_mime = $this->validateUpload($file_input);
		if ($detected_mime === false) {
			return array('status'=>'error', 'success'=>0, 'error'=>1, 'error_message'=>'File type not allowed or upload error.');
		}

		// ── Move to upload directory ─────────────────────────────────────────
		$uploader_path = $this->getUploaderPath();
		$final_name = $this->moveUploadedFile($file_input, $uploader_path['upload_dir']);
		if ($final_name === false) {
			return array('status'=>'error', 'success'=>0, 'error'=>1, 'error_message'=>'Failed to move uploaded file.');
		}

		$file_extension = strtolower(pathinfo($final_name, PATHINFO_EXTENSION));
		$file_body      = pathinfo($final_name, PATHINFO_FILENAME);

		$file = array();
		$file['name'] = $file_body;
		$file['url']  = $uploader_path['upload_url'] . '/' . $final_name;
		$file['mime'] = $detected_mime;

		// ── Generate image size variants ─────────────────────────────────────
		if (in_array($file_extension, ['jpg', 'jpeg', 'png', 'webp'])) {
			$source_path = $uploader_path['upload_dir'] . '/' . $final_name;

			foreach ($this->image_versions as $version => $constraints) {
				$version_dir  = $uploader_path['upload_dir'] . '/' . $version;
				$version_path = $version_dir . '/' . $final_name;

				$resized = $this->resizeImage(
					$source_path,
					$version_path,
					$constraints['max_width'],
					$constraints['max_height']
				);

				if ($resized) {
					$file[$version]['name'] = $file_body;
					$file[$version]['url']  = $uploader_path['upload_url'] . '/' . $version . '/' . $final_name;
				}
			}
		}

		// ── Handle video conversion to multi-quality HLS ─────────────────────
		else if (in_array($file_extension, ['mp4', 'mov', 'avi', 'mkv', 'webm'])) {
			$input_path     = $uploader_path['upload_dir'] . '/' . $final_name;
			$hls_output_dir = $uploader_path['upload_dir'] . '/hls';

			// Start multi-quality HLS conversion in background
			$hls_url = $this->convertToMultiQualityHLS($input_path, $hls_output_dir, $file_body);

			$file['hls'] = array(
				'url'       => '/' . $hls_url,
				'filename'  => $file_body . '.m3u8',
				'type'      => 'adaptive',
				'qualities' => array_keys($this->getVideoQualitySettings())
			);
		}

		return array('status'=>'success', 'success'=>1, 'error'=>0, 'file'=>$file);
	}
}