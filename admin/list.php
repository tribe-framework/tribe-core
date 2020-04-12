<?php
include_once ('../config-init.php');
include_once (ABSOLUTE_PATH.'/admin/header.php');

$list_fields=array_column((array) $types->{$_GET['type']}->modules, 'list_field', 'input_slug');
?>

<div class="container mt-3">

<?php echo get_admin_menu('list', $_GET['type']); ?>

<h2 class="mb-4">List of <?php echo $types->{$_GET['type']}->plural; ?></h2>

<table class="my-4 table datatable">
  <thead>
    <tr>
      <th scope="col">#</th>
      <?php
      $i=0;
      foreach ($list_fields as $key => $value) {
        echo ($value?'<th scope="col" class="pl-2" data-orderable="'.($types->{$_GET['type']}->modules[$i]->list_sortable?'true':'false').'" data-searchable="'.($types->{$_GET['type']}->modules[$i]->list_searchable?'true':'false').'" '.($types->{$_GET['type']}->modules[$i]->input_primary?'style="max-width:40%"':'').'>'.$key.'</th>':'');
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
    foreach ($list_fields as $key => $value)  echo ($value?'<td>'.$post[$key].'</td>':'');
    echo '<td><a href="/admin/edit?type='.$post['type'].'&id='.$post['id'].'"><span class="fas fa-edit"></span></a>&nbsp;<a target="new" href="/'.$post['type'].'/'.$post['slug'].'"><span class="fas fa-external-link-alt"></span></a>&nbsp;<a href="#"><span class="fas fa-trash-alt"></span></a></td></tr>';
  }
  ?>
  </tbody>
</table>

</div>

<?php include_once (ABSOLUTE_PATH.'/admin/footer.php'); ?>