<?php
namespace Tribe;

class Config {

	public function getTypeSchema($type)
	{
		$types = $this->getTypes();
		$modules = array_column($types[$type]['modules'], 'input_slug');
		$modules[] = 'id';
		return array_fill_keys($modules, '');
	}

	public function getTypePrimaryModule(string $posttype, array $types)
	{

		//foreach loop that breaks
		$i = 0;
		foreach ($types[$posttype]['modules'] as $module) {
			$title = array();
			if ($module['input_primary']) {
				$title_id = $i;
				$title['slug'] = $module['input_slug'] . (is_array($module['input_lang'] ?? null) ? '_' . $module['input_lang'][0]['slug'] : '');
				$title['primary'] = $module['input_primary'];
				$title['unique'] = $module['input_unique'];
				break;
			}

			$i++;
		}
		return $title;
	}

	public function getMenus($json_path = ABSOLUTE_PATH . '/config/menus.json')
	{
		return json_decode(file_get_contents($json_path), true);
	}

	public function getTypes()
	{
		$folder_path = TRIBE_ROOT . '/uploads/types';
		$files = scandir($folder_path, SCANDIR_SORT_DESCENDING);
		$newest_file = $files[0];

		if ($newest_file)
			$json_path = $folder_path . '/' . $newest_file;
		else
			$json_path = ABSOLUTE_PATH . '/config/types.json';

		$types_json = \json_decode(\file_get_contents($json_path), true);
		if (!$types_json) {
			die("<em><b>Error:</b> types</em> validation failed");
		}

		$types = array_merge($types_json);
		
		foreach ($types as $key => $type) {
			$type_slug = $type['slug'] ?? ($key ?? 'undefined');

			if (!($type_slug == 'user' || $type_slug == 'webapp')) {
				$type_key_modules = $types[$key]['modules'] ?? [];

				if (!in_array('content_privacy', array_column($type_key_modules, 'input_slug'))) {
					if (($types[$key]['sendable'] ?? false) === true) {
						$content_privacy_json = '{
					        "input_slug": "content_privacy",
					        "input_placeholder": "Content privacy",
					        "input_type": "select",
					        "input_options": [
					          {"slug":"sent", "title":"Send now"},
					          {"slug":"draft", "title":"Save draft"}
					        ],
					        "list_field": false,
					        "input_unique": false
					    }';
					} else {
						$content_privacy_json = '{
					        "input_slug": "content_privacy",
					        "input_placeholder": "Content privacy",
					        "input_type": "select",
					        "input_options": [
					          {"slug":"public", "title":"Public link"},
					          {"slug":"private", "title":"Private link"},
					          {"slug":"pending", "title":"Submit for moderation"},
					          {"slug":"draft", "title":"Draft"}
					        ],
					        "list_field": false,
					        "input_unique": false
					    }';
					}
					$types[$key]['modules'][] = json_decode($content_privacy_json, true);
				}
			}

			if ($types[$key]['modules'] ?? false) {
				foreach ($types[$key]['modules'] as $module) {
					if (!isset($module['input_primary']) || $module['input_primary']!=true) {
						continue;
					}

					$types[$key]['primary_module'] = $module['input_slug'];
					break;
				}
			}
		}
		return $types;
	}
}
?>