<?php
include_once ('../config-init.php');
include_once (ABSOLUTE_PATH.'/admin/header.php');

$list_fields=array_column((array) $types->{$_GET['type']}->modules, 'list_field', 'input_slug');
$list_search=array_column((array) $types->{$_GET['type']}->modules, 'list_search', 'input_slug');
$list_sort=array_column((array) $types->{$_GET['type']}->modules, 'list_sort', 'input_slug');
?>

<div class="container mt-3">

<?php echo get_admin_menu('list', $_GET['type']); ?>

<h2 class="mb-4">List of <?php echo $types->{$_GET['type']}->plural; ?></h2>

<table class="my-4 table datatable">
  <thead>
    <tr>
      <th scope="col">#</th>
      <?php foreach ($list_fields as $key => $value)  echo ($value?'<td>'.$key.'</td>':''); ?>
      <th scope="col"></th>
    </tr>
  </thead>
  <tbody>
  <?php 
  $ids = $dash::get_all_ids($_GET['type']);
  foreach ($ids as $arr) {
    $post = $dash::get_content($arr['id']);
    echo '<tr><th scope="row">'.$post['id'].'</th>';
    foreach ($list_fields as $key => $value)  echo ($value?'<td>'.$post[$key].'</td>':'');
    echo '<td><a href="/admin/edit?type='.$post['type'].'&id='.$post['id'].'">edit</a> | <a target="new" href="/'.$post['type'].'/'.$post['slug'].'">view</a> | <a href="#">delete</a></td></tr>';
  }
  ?>
  </tbody>
</table>

</div>

<?php include_once (ABSOLUTE_PATH.'/admin/footer.php'); ?>