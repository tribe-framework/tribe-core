<?php
namespace Tribe;

class Config {

	public function projectRoot()
	{
	    return $_SERVER['DOCUMENT_ROOT'];
	}

	public function getTypeSchema($type)
	{
		$types = $this->getTypes();
		$modules = array_column($types[$type]['modules'], 'input_slug');
		$modules[] = 'id';
		return array_fill_keys($modules, '');
	}

	public function getTypePrimaryModule(string $posttype, array $types)
	{
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

	public function getTypeLinkedModules(string $posttype)
	{
		$types = $this->newestValidTypes();
		$or = [];
		if (isset($types[$posttype]['modules'])) {
			foreach ($types[$posttype]['modules'] as $module) {
				if ($module['linked_type'] ?? false) {
					$slug = $module['input_slug'];
					$or[$slug] = $module['linked_type'];
				}
			}
		}
		return $or;
	}

	/**
	 * Load the active blueprint from blueprint_record entries in the database.
	 * Finds the newest blueprint_record where modules.active === true.
	 * Returns the decoded types array on success, or null if nothing found.
	 */
	private function getActiveBlueprintFromDB(): ?array
	{
		try {
			$sql = new \Tribe\MySQL();

			// Find the newest blueprint_record with active=true
			$rows = $sql->executeSQL(
				"SELECT `id`, `content` FROM `data`
				 WHERE `content`->>'$.type' = 'blueprint_record'
				   AND `content`->>'$.active' = 'true'
				 ORDER BY `id` DESC
				 LIMIT 1"
			);

			// Fallback: also check for boolean true stored as JSON true (not string)
			if (empty($rows[0]['content'])) {
				$rows = $sql->executeSQL(
					"SELECT `id`, `content` FROM `data`
					 WHERE `content`->>'$.type' = 'blueprint_record'
					   AND JSON_EXTRACT(`content`, '$.active') = true
					 ORDER BY `id` DESC
					 LIMIT 1"
				);
			}

			if (!empty($rows[0]['content'])) {
				$record = json_decode($rows[0]['content'], true);

				if (json_last_error() === JSON_ERROR_NONE && !empty($record['blueprint'])) {
					$blueprint = json_decode($record['blueprint'], true);

					if (json_last_error() === JSON_ERROR_NONE && is_array($blueprint)) {
						return $blueprint;
					}
				}
			}
		} catch (\Throwable $e) {
			error_log('[Config::getActiveBlueprintFromDB] ' . $e->getMessage());
		}

		return null;
	}

	/**
	 * Return the newest valid types array, preferring the active blueprint_record
	 * in the DB and falling back to the uploads/types folder (with auto-migration).
	 */
	public function newestValidTypes(): ?array
	{
		// 1. Try active blueprint_record from DB
		$fromDb = $this->getActiveBlueprintFromDB();
		if ($fromDb !== null) {
			return $fromDb;
		}

		// 2. Fall back to folder, auto-migrating if found
		$fromFolder = $this->newestValidTypesInUploads();
		if ($fromFolder) {
			$this->migrateTypesToBlueprintRecord($fromFolder);
			return $fromFolder;
		}

		return null;
	}

	/**
	 * Migrate a types.json array from the uploads folder into a blueprint_record
	 * in the database, marking it as active. This provides backward compatibility:
	 * existing folder-based types files are consumed once and stored properly.
	 */
	private function migrateTypesToBlueprintRecord(array $typesJson): void
	{
		try {
			$core = new \Tribe\Core();

			// Determine a title from the webapp entry if available
			$title = $typesJson['webapp']['name'] ?? 'Migrated Blueprint';

			$blueprintJson = json_encode($typesJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

			$record = [
				'type'    => 'blueprint_record',
				'title'   => $title,
				'active'  => true,
				'blueprint' => $blueprintJson,
				'content_privacy' => 'private',
			];

			$core->pushObject($record);

			error_log('[Config::migrateTypesToBlueprintRecord] Successfully migrated folder types to blueprint_record: ' . $title);
		} catch (\Throwable $e) {
			error_log('[Config::migrateTypesToBlueprintRecord] Migration failed: ' . $e->getMessage());
		}
	}

	public function getMenus($json_path = 'config/menus.json')
	{
		return json_decode(file_get_contents($json_path), true);
	}

	public function newestValidTypesInUploads() {
		$folder_path = $this->projectRoot().'/uploads/types';

		if (!is_dir($folder_path)) {
			mkdir($folder_path, 0755, true);
		}

		$files = scandir($folder_path, SCANDIR_SORT_DESCENDING);
		$files = array_diff($files, array('..', '.'));
		$newest_file = $files[0] ?? false;

		if ($newest_file) {
			$newest_path = $folder_path . '/' . $newest_file;
			$newest_json = \json_decode(\file_get_contents($newest_path), true);

			if (json_last_error() === JSON_ERROR_NONE) {
			    return $newest_json;
			} else {
				unlink($newest_path);
			    return $this->newestValidTypesInUploads();
			}
		}
		else
			return false;
	}

	public function getTypes()
	{
		$newest_json = $this->newestValidTypes();

		if ($newest_json) {
			$types_json = $newest_json;
		}
		else if (file_exists('config/types.json')) {
			$json_path = 'config/types.json';
			$types_json = \json_decode(\file_get_contents($json_path), true);
		}
		else {
			$json_path = 'https://raw.githubusercontent.com/tribe-framework/types.json/master/blueprints/junction-init.json';
			$types_json = \json_decode(\file_get_contents($json_path), true);
		}

		$types_json_junction = \json_decode(\file_get_contents('https://raw.githubusercontent.com/tribe-framework/types.json/master/junction.json'), true);

		if (!$types_json) {
			die("<em><b>Error:</b> types</em> validation failed");
		}

		$types = array_merge($types_json, ($types_json_junction ?? []));
		
		foreach ($types as $key => $type) {
			$type_slug = $type['slug'] ?? ($key ?? 'undefined');

			if ($type_slug != 'webapp') {
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
