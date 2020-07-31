<?php
include_once ('../config-init.php');
include_once (ABSOLUTE_PATH.'/auth/header.php');
?>

<?php
foreach ($types as $k => $v) {
	if ($types[$k]['type']=='entity') {
		foreach ($types[$k]['roles'] as $key => $value) {
			if ($value['role']!='admin')
		    	echo '<button type="button" class="btn btn-primary btn-lg">'.ucfirst($value['title']).'</button>';	
		}
	}
	break;
}
?>

<?php include_once (ABSOLUTE_PATH.'/auth/footer.php'); ?>