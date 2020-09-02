<?php
/*
	functions start with push_, pull_, get_, do_ or is_
	push_ is to save to database
	pull_ is to pull from database, returns 1 or 0, saves the output array in $last_data
	get_ is to get usable values from functions
	do_ is for action that doesn't have a database push or pull
	is_ is for a yes/no answer
*/

class Trac
{
	function push_visit ($post)
	{
		global $sql;
		$updated_on=time();

		$sql->executeSQL("INSERT INTO `trac` (`created_on`, `visit`) VALUES ('$updated_on', '".json_encode($post)."')");
		return $sql->lastInsertID();
	}

	function push_visit_meta ($id, $meta_key, $meta_value='')
	{
		global $sql;
		if ($id && $meta_key) {
			if (!trim($meta_value)) {
				//to delete a key, leave it empty
				$q=$sql->executeSQL("UPDATE `trac` SET `visit` = JSON_REMOVE(`visit`, '$.".$meta_key."') WHERE `id`='$id'");
			}
			else {
				$q=$sql->executeSQL("UPDATE `trac` SET `visit` = JSON_SET(`visit`, '$.".$meta_key."', '$meta_value') WHERE `id`='$id'");
			}
			return 1;
		}
		else
			return 0;
	}
}
?>
