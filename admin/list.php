<?php
include_once ('../config-init.php');
include_once (ABSOLUTE_PATH.'/admin/header.php');
?>

<div class="container mt-3">

<?php echo get_admin_menu('list', $_GET['type']); ?>

<h2 class="mb-4">List of <?php echo $types[$_GET[type]]['plural']; ?></h2>

<table class="my-4 table datatable">
  <thead>
    <tr>
      <th scope="col">#</th>
      <?php
      $i=0;
      foreach ($types[$_GET[type]]['modules'] as $module) {
        echo ($module['list_field']?'<th scope="col" class="pl-2" data-orderable="'.($module['list_sortable']?'true':'false').'" data-searchable="'.($module['list_searchable']?'true':'false').'" '.($module['input_primary']?'style="max-width:50%"':'').'>'.$module['input_slug'].'</th>':'');
        $i++;
      } ?>
      <th scope="col" data-orderable="false" data-searchable="false"></th>
    </tr>
  </thead>
  <tbody>
  <?php 
  $ids = $dash::get_all_ids($_GET['type']);
  foreach ($ids as $arr) {
    $post = $dash::get_content($arr['id']);
    echo '<tr><th scope="row">'.$post['id'].'</th>';
    foreach ($types[$_GET[type]]['modules'] as $module) {
      $module_input_slug=$module['input_slug'];
      echo ($module['list_field']?'<td>'.$post[$module_input_slug].'</td>':'');
    }
    echo '<td><a href="/admin/edit?type='.$post['type'].'&id='.$post['id'].'"><span class="fas fa-edit"></span></a>&nbsp;<a target="new" href="/'.$post['type'].'/'.$post['slug'].'"><span class="fas fa-external-link-alt"></span></a>&nbsp;<a href="#"><span class="fas fa-trash-alt"></span></a></td></tr>';
  }
  ?>
  </tbody>
</table>

</div>

<?php include_once (ABSOLUTE_PATH.'/admin/footer.php'); ?>