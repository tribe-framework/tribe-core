<?php
include_once ('../init.php');
include_once (ABSOLUTE_PATH.'/admin/header.php');
?>

<div class="p-3">

<?php echo get_admin_menu('dash'); ?>

<div class="card-group m-0">
<div class="card my-2">
  <div class="card-header">Analytics</div>
  <div class="card-body">
    <p class="card-text">
	<?php
	//prism display
	include_once(ABSOLUTE_PATH.'/plugins/prism/trac.class.php');
	$trac = new Trac();
	print_r($trac->get_visit(1));
	print_r($trac->get_visit(2));
	?>
    </p>
  </div>
</div>
<div class="card my-2">
  <div class="card-header">Latest</div>
  <div class="card-body">
    <p class="card-text">Eos et ut voluptas ad. Vero quis nihil quis impedit omnis ut. Quod non nesciunt illum qui in quidem repellendus libero. Odio molestiae voluptate neque vero architecto esse sunt quae. Quod molestiae est ut tenetur autem esse voluptas itaque. Et similique sunt ipsa libero numquam blanditiis.</p>
  </div>
</div>
</div>
</div>

<?php include_once (ABSOLUTE_PATH.'/admin/footer.php'); ?>