<?php
include_once ('../config-init.php');
include_once (ABSOLUTE_PATH.'/auth/header.php');
?>

<?php
foreach ($types as $key => $value) {
	if ($types[$key]['type']=='content')
    	echo '<button type="button" class="btn btn-primary btn-lg">'.ucfirst($types[$key]['name']).'</button>';
}
?>

<?php include_once (ABSOLUTE_PATH.'/auth/footer.php'); ?>