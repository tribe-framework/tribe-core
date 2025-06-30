<?php
namespace Tribe;
set_time_limit(600);

use \Tribe\Core as Core;
use \Ifsnop\Mysqldump as IMysqldump;

class Backup {
	public function mysqlDatabase() {
		$core = new Core;

		$upload_paths = $this->get_backup_path();
		$backupfile = $upload_paths['upload_dir'] . '/backup-' . uniqid() . '-' . time() . '-' . $_ENV['DB_NAME'] . '.sql';
		try {

				$dump = new IMysqldump\Mysqldump('mysql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_NAME'], $_ENV['DB_USER'], $_ENV['DB_PASS']);
				$dump->start($backupfile);

			} catch (\Exception $e) {

				echo 'mysqldump-php error: ' . $e->getMessage();

			} finally {

				$this->linux_command('7z a ' . $backupfile . '.7z ' . $backupfile . ' -p' . $_ENV['DB_PASS'] . '; rm ' . $backupfile . ' > /dev/null 2>&1 &');

				$core->pushObject(array('type' => 'tribe_backup', 'title'=>'MySQL backed up on '.date('Ymd_his'), 'mysql_backup_path' => $backupfile . '.7z '));

			}
	}

	public function uploadsFolder() {
		$core = new Core;

		if (($_ENV['S3_BKUP_ACCESS_KEY'] ?? false) && ($_ENV['S3_BKUP_BUCKET_NAME'] ?? false) && ($_ENV['S3_BKUP_HOST_BUCKET'] ?? false) && ($_ENV['S3_BKUP_SECRET_KEY'] ?? false) && ($_ENV['S3_BKUP_HOST_BASE'] ?? false) && defined('ABSOLUTE_PATH') && ($_ENV['DB_NAME'] ?? false) && ($_ENV['DB_USER'] ?? false) && ($_ENV['DB_PASS'] ?? false)) {

			//BACKUP UPLOADS, with --skip-existing
			$this->linux_command('s3cmd sync -r --skip-existing --host="' . $_ENV['S3_BKUP_HOST_BASE'] . '" --access_key="' . $_ENV['S3_BKUP_ACCESS_KEY'] . '" --secret_key="' . $_ENV['S3_BKUP_SECRET_KEY'] . '" --host-bucket="' . $_ENV['S3_BKUP_HOST_BUCKET'] . '" ' . ABSOLUTE_PATH . '/uploads/ s3://' . $_ENV['S3_BKUP_BUCKET_NAME'] . '/uploads/ > /dev/null 2>&1 &');

			$core->pushObject(array('type' => 'tribe_backup', 'title'=>'Uploads backed up to S3 on '.date('Ymd_his')));

		}

		//IF UPLOADS FOLDER HAS AN S3
		if (($_ENV['S3_UPLOADS_ACCESS_KEY'] ?? false) && ($_ENV['S3_UPLOADS_BUCKET_NAME'] ?? false) && ($_ENV['S3_UPLOADS_HOST_BUCKET'] ?? false) && ($_ENV['S3_UPLOADS_SECRET_KEY'] ?? false) && ($_ENV['S3_UPLOADS_HOST_BASE'] ?? false)) {

			//UPLOADS FOLDER TO S3, with --skip-existing and make --acl-public
			$this->linux_command('s3cmd sync -r --acl-public --skip-existing --host="' . $_ENV['S3_UPLOADS_HOST_BASE'] . '" --access_key="' . $_ENV['S3_UPLOADS_ACCESS_KEY'] . '" --secret_key="' . $_ENV['S3_UPLOADS_SECRET_KEY'] . '" --host-bucket="' . $_ENV['S3_UPLOADS_HOST_BUCKET'] . '" ' . ABSOLUTE_PATH . '/uploads/ s3://' . $_ENV['S3_UPLOADS_BUCKET_NAME'] . ' > /dev/null 2>&1 &');

			$core->pushObject(array('type' => 'tribe_backup', 'title'=>'Uploads backed up to S3 Public on '.date('Ymd_his')));
		}

	}

	//retains those mysql 7z backups which are of current date, -1 day, of every monday in the current month and -1 months, or those of 01 date of every month in every year in current year and -1 year, and of 01-01 day-month in every year before that
	public function deleteOldMySQLBackups() {
	}

	function linux_command($cmd) {
		ob_start();
		passthru($cmd . ' > /dev/null 2>&1 &');
		$tml = ob_get_contents();
		ob_end_clean();
		return $tml;
	}

	function get_backup_path() {
		$folder_path = 'uploads/mysql-backups/' . date('Y') . '/' . date('m-F') . '/' . date('d-D');
		if (!is_dir(TRIBE_ROOT . '/' . $folder_path)) {
			mkdir(TRIBE_ROOT . '/' . $folder_path, 0755, true);
		}

		return array('upload_dir' => TRIBE_ROOT . '/' . $folder_path, 'upload_url' => BASE_URL . '/' . $folder_path);
	}
}
?>