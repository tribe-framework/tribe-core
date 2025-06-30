<?php
namespace Tribe;


class Uploads {

	public function getUploadDirPath()
	{
		return TRIBE_ROOT . '/uploads/' . date('Y') . '/' . date('m-F') . '/' . date('d-D');
	}

	public function getUploadDirURL()
	{
		return BASE_URL . '/uploads/' . date('Y') . '/' . date('m-F') . '/' . date('d-D');
	}

	public function getUploaderPath()
	{
		$folder_path = 'uploads/' . date('Y') . '/' . date('m-F') . '/' . date('d-D');
		if (!is_dir(TRIBE_ROOT . '/' . $folder_path)) {
			mkdir(TRIBE_ROOT . '/' . $folder_path, 0755, true);
			mkdir(TRIBE_ROOT . '/' . $folder_path . '/xs', 0755, true);
			mkdir(TRIBE_ROOT . '/' . $folder_path . '/sm', 0755, true);
			mkdir(TRIBE_ROOT . '/' . $folder_path . '/md', 0755, true);
			mkdir(TRIBE_ROOT . '/' . $folder_path . '/lg', 0755, true);
			mkdir(TRIBE_ROOT . '/' . $folder_path . '/xl', 0755, true);
		}

		return array('upload_dir' => TRIBE_ROOT . '/' . $folder_path, 'upload_url' => BASE_URL . '/' . $folder_path);
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

			if (file_exists(TRIBE_ROOT . '/uploads/' . $year . '/' . $month . '/' . $day . '/' . $thumbnail . '/' . substr(escapeshellarg($filename), 1, -1))) {
				$file_arr['path'] = TRIBE_ROOT . '/uploads/' . $year . '/' . $month . '/' . $day . '/' . $thumbnail . '/' . substr(escapeshellarg($filename), 1, -1);
				$file_arr['url'] = BASE_URL . '/uploads/' . $year . '/' . $month . '/' . $day . '/' . $thumbnail . '/' . rawurlencode($filename);
			}
			else {
				$file_arr['path'] = TRIBE_ROOT . '/uploads/' . $year . '/' . $month . '/' . $day . '/' . substr(escapeshellarg($filename), 1, -1);
				$file_arr['url'] = BASE_URL . '/uploads/' . $year . '/' . $month . '/' . $day . '/' . rawurlencode($filename);
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

		$file_arr['path']['source'] = TRIBE_ROOT . '/uploads/' . $year . '/' . $month . '/' . $day . '/' . substr(escapeshellarg($filename), 1, -1);
		$file_arr['url']['source'] = BASE_URL . '/uploads/' . $year . '/' . $month . '/' . $day . '/' . rawurlencode($filename);

		if (preg_match('/\.(gif|jpe?g|png)$/i', $file_url)) {
			$sizes = array('xl', 'lg', 'md', 'sm', 'xs');

			foreach ($sizes as $size) {
				if (file_exists(TRIBE_ROOT . '/uploads/' . $year . '/' . $month . '/' . $day . '/' . $size . '/' . $filename)) {
					$file_arr['path'][$size] = TRIBE_ROOT . '/uploads/' . $year . '/' . $month . '/' . $day . '/' . $size . '/' . substr(escapeshellarg($filename), 1, -1);
					$file_arr['url'][$size] = BASE_URL . '/uploads/' . $year . '/' . $month . '/' . $day . '/' . $size . '/' . rawurlencode($filename);
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

		return $file_arr;
	}

	public function deleteFileRecord($object) {
        if ($_ENV['DOCKER_ROOT'] ?? false)
        	$web_root = $_ENV['DOCKER_ROOT'];
        else
	        $web_root = ($_ENV['WEB_ROOT'] ?? '/mnt/wildfire').'/'.$_ENV['WEB_BARE_URL'];

		if ($object['url'] ?? false) {
			$pth = str_replace($_ENV['WEB_URL'], $web_root, $object['url']);
			unlink($pth);
		}

		if ($object['file']['lg']['url'] ?? false) {
			$pth = str_replace($_ENV['WEB_URL'], $web_root, $object['file']['lg']['url']);
			unlink($pth);
		}

		if ($object['file']['md']['url'] ?? false) {
			$pth = str_replace($_ENV['WEB_URL'], $web_root, $object['file']['md']['url']);
			unlink($pth);
		}

		if ($object['file']['sm']['url'] ?? false) {
			$pth = str_replace($_ENV['WEB_URL'], $web_root, $object['file']['sm']['url']);
			unlink($pth);
		}

		if ($object['file']['xl']['url'] ?? false) {
			$pth = str_replace($_ENV['WEB_URL'], $web_root, $object['file']['xl']['url']);
			unlink($pth);
		}

		if ($object['file']['xs']['url'] ?? false) {
			$pth = str_replace($_ENV['WEB_URL'], $web_root, $object['file']['xs']['url']);
			unlink($pth);
		}
	}

	public function getDirURL()
	{
		return str_replace(TRIBE_ROOT, BASE_URL, getcwd());
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

        if ($_ENV['DOCKER_ROOT'] ?? false)
        	$web_root = $_ENV['DOCKER_ROOT'];
        else
	        $web_root = ($_ENV['WEB_ROOT'] ?? '/mnt/wildfire').'/'.$_ENV['WEB_BARE_URL'];

        $search_q = '^(?=.*'.implode(')(?=.*', $strings).')';

        $files = explode(PHP_EOL, shell_exec('grep -PRil "'.$search_q.'" '.$web_root.'/uploads/'));

        if ($deep_search) {
	        $files = array_merge($files, explode(PHP_EOL, shell_exec('timeout 7 pdfgrep -PRil "'.$search_q.'" '.$web_root.'/uploads/')));
        }

        $filenames = explode(PHP_EOL, shell_exec("find ".$web_root."/uploads -not -path '*/[@.]*' -type f"));

        foreach ($strings as $string) {
            $filenames_op = array_merge($filenames_op, preg_grep("/".$search_q."/i", $filenames));
        }

        foreach ($filenames_op as $file) {
            $filenames_or[] = str_replace($web_root, 'https://', $file);
        }

        foreach ($files as $file) {
            if ($file !== '')
                $filecontents_op[] = $file;
        }

        foreach ($filecontents_op as $file) {
            $filecontents_or[] = str_replace($web_root, 'https://', $file);
        }

        $filenames_or = array_unique($filenames_or);
        $filenames_or = array_combine(array_map('basename', $filenames_or), $filenames_or);

        $filecontents_or = array_unique($filecontents_or);
        $filecontents_or = array_combine(array_map('basename', $filecontents_or), $filecontents_or);

        return array('by_file_name'=>$filenames_or, 'by_file_content'=>$filecontents_or);
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

			else if (($_ENV['CLOUDFLARE_STREAM_TOKEN'] ?? false) &&  ($_ENV['CLOUDFLARE_STREAM_ACCOUNT'] ?? false) && in_array(mime_content_type($uploader_path['upload_dir'].'/'.$handle->file_dst_name), $video_mime_types_allowed)) {		

				$output = null;
				$retval = null;
		    	exec('curl -X POST -F file=@'.$uploader_path['upload_dir'].'/'.$handle->file_dst_name.' -H "Authorization: Bearer '.$_ENV['CLOUDFLARE_STREAM_TOKEN'].'" https://api.cloudflare.com/client/v4/accounts/'.$_ENV['CLOUDFLARE_STREAM_ACCOUNT'].'/stream', $output, $retval);
		    	
		    	$file['cloudflare_stream'] = json_decode(implode(' ', $output), 1);
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
