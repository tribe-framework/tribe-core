<?php
namespace Tribe;

class Uploads {

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
					'playlist' => $playlist_name
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
				'playlist' => $playlist_name
			];
		}
		
		// Build complete FFmpeg command
		$complete_command = $ffmpeg_base;
		foreach ($output_options as $output) {
			$complete_command .= " " . $output;
		}
		$complete_command .= " 2>/tmp/ffmpeg_{$filename_base}.log &";
		
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
			$content .= $quality_info['playlist'] . "\n\n";
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
			'status' => 'processing',
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
			// This is a rough estimate - you might want to get duration first
			// and calculate actual percentage
			return min(50, count($matches[0]) * 2); // Rough progress estimation
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
		
		// This would typically check which quality files actually exist
		// For now, returning the standard qualities
		foreach ($quality_settings as $quality => $settings) {
			$qualities[] = [
				'quality' => $quality,
				'label' => $settings['label'],
				'resolution' => $settings['resolution']
			];
		}
		
		return $qualities;
	}

	public function handleUpload(array $files_server_arr, array $post_server_arr = [], array $get_server_arr = []) {

		if ($post_server_arr['url'] ?? false)
			return array('status'=>'success', 'success'=>1, 'error'=>0, 'file'=>array('url'=>$post_server_arr['url']));

		else if ($files_server_arr['image'] ?? false)
			$files_server_arr['file'] = $files_server_arr['image'];

		//handle upload search
		else if (($post_server_arr['search'] ?? false) && ($post_server_arr['q'] ?? false))
			return $this->handleFileSearch($post_server_arr['q'], ($post_server_arr['deep_search'] ?? false));

		$handle = new \Verot\Upload\Upload($files_server_arr['file']);

		// Add additional allowed mime types
		$handle->mime_types = array_merge($handle->mime_types, array(
		    'svg'  => 'image/svg+xml',
		    'vtt'  => 'text/vtt',
		    'srt'  => 'application/x-subrip',
		    'm4a'  => 'audio/mp4',
		    'ogg'  => 'audio/ogg',
		    'oga'  => 'audio/ogg',
		    'webm' => 'video/webm',
		    'json' => 'application/json'
		));

		// Add additional allowed mime types
		$handle->allowed = array_merge($handle->allowed, array(
		    'image/svg+xml',
		    'text/vtt',
		    'application/x-subrip',
		    'audio/mp4',
		    'audio/ogg',
		    'video/webm',
		    'application/json'
		));

		//Image size variants
		$image_versions = [
			'xl' => array(
				'max_width' => 2100,
				'max_height' => 2100,
			),
			'lg' => array(
				'max_width' => 1400,
				'max_height' => 1400,
			),
			'md' => array(
				'max_width' => 700,
				'max_height' => 700,
			),
			'sm' => array(
				'max_width' => 350,
				'max_height' => 350,
			),
			'xs' => array(
				'max_width' => 100,
				'max_height' => 100,
			),
		];

		if ($handle->uploaded) {
		  
			$file = array();
			$uploader_path = $this->getUploaderPath();
			$file_extension = pathinfo($files_server_arr['file']['name'], PATHINFO_EXTENSION);
			$file['name'] = pathinfo($files_server_arr['file']['name'], PATHINFO_FILENAME).'_'.uniqid();

			$handle->file_new_name_body = $file['name'];
			$handle->process($uploader_path['upload_dir']);

			$file['name'] = $handle->file_dst_name_body;
			$file['url'] = $uploader_path['upload_url'].'/'.$handle->file_dst_name;
			$file['mime'] = mime_content_type($uploader_path['upload_dir'].'/'.$handle->file_dst_name);

			if (in_array(strtolower($file_extension), ['jpg', 'jpeg', 'png', 'webp'])) {
				foreach ($image_versions as $version => $constraints) {
					$handle->file_new_name_body = $file['name'];

					$handle->image_resize         = true;
					$handle->image_x              = $constraints['max_width'];
					$handle->image_y              = $constraints['max_height'];
					$handle->image_ratio          = true;

					$handle->process($uploader_path['upload_dir'].'/'.$version);

					$file[$version]['name'] = $handle->file_dst_name_body;
					$file[$version]['url'] = $uploader_path['upload_url'].'/'.$version.'/'.$handle->file_dst_name;
				}
			}

			// Handle video conversion to multi-quality HLS
			else if (in_array(strtolower($file_extension), ['mp4', 'mov', 'avi', 'mkv', 'webm'])) {
				$input_path = $uploader_path['upload_dir'].'/'.$handle->file_dst_name;
				$hls_output_dir = $uploader_path['upload_dir'].'/hls';
				$filename_base = $file['name'];
				
				// Start multi-quality HLS conversion in background
				$hls_url = $this->convertToMultiQualityHLS($input_path, $hls_output_dir, $filename_base);
				
				// Add HLS info to file array
				$file['hls'] = array(
					'url' => str_replace('/var/www/html', '', $hls_url),
					'status' => 'processing',
					'playlist' => $filename_base . '.m3u8',
					'type' => 'adaptive', // Indicates this supports multiple qualities
					'qualities' => array_keys($this->getVideoQualitySettings())
				);
			}

			if ($handle->processed) {
				return array('status'=>'success', 'success'=>1, 'error'=>0, 'file'=>$file);
				$handle->clean();
			} else {
				return array('status'=>'error', 'success'=>0, 'error'=>1, 'error_message'=>$handle->error);
			}
		}
	}
}