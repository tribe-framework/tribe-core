<?php
include_once ('../config-init.php');
include_once (ABSOLUTE_PATH.'/admin/header.php');
?>

<div class="container mt-3">

<?php echo get_admin_menu('dash', $_GET['type']); ?>

</div>

<?php include_once (ABSOLUTE_PATH.'/admin/footer.php'); ?>