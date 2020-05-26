<?php
include_once ('../config-init.php');
include_once (ABSOLUTE_PATH.'/admin/header.php'); ?>
<?php
if ($_GET['id'])
	$post = $dash::get_content($_GET['id']);

if ($_GET['role'])
	$role = $types['user']['roles'][$_GET[role]];

if (($_GET['id'] && $post['type']==$type) || !$_GET['id']):

	//for testing resticted min and max ids for archive format changes
	if (!($pid=$_GET['id']))
		$pid=$dash::get_next_id();
?>

	<link rel="stylesheet" type="text/css" href="/plugins/typeout/typeout.css">

	<div class="p-3">
	<a name="infos"></a><div id="infos" class="d-none alert alert-info"></div>
	<a name="errors"></a><div id="errors" class="d-none alert alert-danger"></div>

	<form method="post" class="edit_form" action="/admin/json" autocomplete="off">

		<?php echo get_admin_menu('edit', $type, $role['slug'], $_GET['id']); ?>

		<h2 class="form_title"><?php echo ($type=='user'?$role['title'].'&nbsp;<small><span class="fas fa-angle-double-right"></span></small>&nbsp;':'').'Edit '.$types[$type]['name']; ?></h2>

		<?php include_once (ABSOLUTE_PATH.'/admin/form.php'); ?>

		<?php if (count($types[$type]['modules'])>3) { echo get_admin_menu('edit', $type, $role['slug'], $_GET['id']); } ?>
		<p>&nbsp;</p>
	
	</form>

	</div>
		
<?php endif; ?>
<?php include_once (ABSOLUTE_PATH.'/admin/footer.php'); ?>