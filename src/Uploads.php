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

		// Check for HLS version for videos
		if (preg_match('/\.(mp4|mov|avi|mkv|webm)$/i', $file_url)) {
			$hls_path = '/uploads/' . $year . '/' . $month . '/' . $day . '/hls/' . pathinfo($filename, PATHINFO_FILENAME) . '.m3u8';
			$hls_url = '/uploads/' . $year . '/' . $month . '/' . $day . '/hls/' . pathinfo($filename, PATHINFO_FILENAME) . '.m3u8';
			
			if (file_exists($hls_path)) {
				$file_arr['path']['hls'] = $hls_path;
				$file_arr['url']['hls'] = $hls_url;
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
                $filecontents_op[] = $file;
        }

        foreach ($filecontents_op as $file) {
            $filecontents_or[] = $file;
        }

        $filenames_or = array_unique($filenames_or);
        $filenames_or = array_combine(array_map('basename', $filenames_or), $filenames_or);

        $filecontents_or = array_unique($filecontents_or);
        $filecontents_or = array_combine(array_map('basename', $filecontents_or), $filecontents_or);

        return array('by_file_name'=>$filenames_or, 'by_file_content'=>$filecontents_or);
	}

	/**
	 * Convert video to HLS format using FFmpeg in background
	 */
	private function convertToHLS($input_path, $output_dir, $filename_base) {
		// Create HLS output directory
		if (!is_dir($output_dir)) {
			mkdir($output_dir, 0755, true);
		}

		$output_playlist = $output_dir . '/' . $filename_base . '.m3u8';
		$output_segment = $output_dir . '/' . $filename_base . '_%03d.ts';
		
		// Escape paths for shell execution
		$input_escaped = escapeshellarg($input_path);
		$playlist_escaped = escapeshellarg($output_playlist);
		$segment_escaped = escapeshellarg($output_segment);
		
		// Build FFmpeg command for HLS conversion with multiple quality levels
		$ffmpeg_cmd = "ffmpeg -i {$input_escaped} " .
			// Video codec settings
			"-c:v libx264 -preset fast -crf 23 " .
			// Audio codec settings  
			"-c:a aac -b:a 128k " .
			// HLS specific settings
			"-hls_time 6 " .                    // 6 second segments
			"-hls_list_size 0 " .               // Keep all segments in playlist
			"-hls_segment_filename {$segment_escaped} " .
			// Output playlist
			"{$playlist_escaped} " .
			// Run in background, redirect output to log file
			"2>/tmp/ffmpeg_{$filename_base}.log &";
		
		// Execute FFmpeg command in background
		exec($ffmpeg_cmd);
		
		// Return the expected playlist URL (even though conversion is still in progress)
		return str_replace('/var/www/html/', '/', $output_playlist);
	}

	/**
	 * Check if HLS conversion is complete
	 */
	public function isHLSReady($hls_path) {
		if (!file_exists($hls_path)) {
			return false;
		}
		
		// Check if playlist file has content and ends properly
		$content = file_get_contents($hls_path);
		return strpos($content, '#EXT-X-ENDLIST') !== false;
	}

	/**
	 * Get HLS conversion status
	 */
	public function getHLSStatus($filename_base) {
		$log_file = "/tmp/ffmpeg_{$filename_base}.log";
		
		if (!file_exists($log_file)) {
			return 'not_started';
		}
		
		$log_content = file_get_contents($log_file);
		
		if (strpos($log_content, 'video:') !== false && strpos($log_content, 'audio:') !== false) {
			return 'completed';
		}
		
		if (strpos($log_content, 'Error') !== false || strpos($log_content, 'failed') !== false) {
			return 'failed';
		}
		
		return 'processing';
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

		$video_mime_types_allowed[] = 'video/mp4';
		$video_mime_types_allowed[] = 'video/mov';
		$video_mime_types_allowed[] = 'video/ogg';
		$video_mime_types_allowed[] = 'video/mpeg';
		$video_mime_types_allowed[] = 'video/quicktime';
		$video_mime_types_allowed[] = 'video/webm';

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

		//Video size variants
		$video_versions = [
			'md' => array(
				'max_width' => 540,
				'max_height' => 540,
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

			// Handle MP4 conversion to HLS
			else if (in_array(strtolower($file_extension), ['mp4', 'mov', 'avi', 'mkv', 'webm'])) {
				$input_path = $uploader_path['upload_dir'].'/'.$handle->file_dst_name;
				$hls_output_dir = $uploader_path['upload_dir'].'/hls';
				$filename_base = $file['name'];
				
				// Start HLS conversion in background
				$hls_url = $this->convertToHLS($input_path, $hls_output_dir, $filename_base);
				
				// Add HLS info to file array (conversion may still be in progress)
				$file['hls'] = array(
					'url' => str_replace('/var/www/html', '', $hls_url),
					'status' => 'processing',
					'playlist' => $filename_base . '.m3u8'
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