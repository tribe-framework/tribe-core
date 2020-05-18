<?php
include_once ('../config-init.php');
include_once (ABSOLUTE_PATH.'/admin/header.php');
?>

<div class="p-3">

<?php echo get_admin_menu('dash'); ?>

<div class="row">
<div class="card col-md my-2">
  <div class="card-body">
    <h5 class="card-title">Dashboard widget 1</h5>
    <p class="card-text">This is a wider card with supporting text below as a natural lead-in to additional content. This content is a little bit longer.</p>
    <p class="card-text"><small class="text-muted">Last updated 3 mins ago</small></p>
  </div>
</div>
<div class="card col-md my-2">
  <div class="card-body">
    <h5 class="card-title">Dashboard widget 2</h5>
    <p class="card-text">This is a wider card with supporting text below as a natural lead-in to additional content. This content is a little bit longer.</p>
    <p class="card-text"><small class="text-muted">Last updated 3 mins ago</small></p>
  </div>
</div>
<div class="card col-md my-2">
  <div class="card-body">
    <h5 class="card-title">Dashboard widget 2</h5>
    <p class="card-text">This is a wider card with supporting text below as a natural lead-in to additional content. This content is a little bit longer.</p>
    <p class="card-text"><small class="text-muted">Last updated 3 mins ago</small></p>
  </div>
</div>
</div>
</div>

<?php include_once (ABSOLUTE_PATH.'/admin/footer.php'); ?>