<?php
include_once ('config-init.php');
include_once ('header.php');

$types=json_decode(file_get_contents('types.json', true));
?>

<div class="container mt-3">

<div class="card mb-4"><div class="card-body p-0">
<div class="btn-toolbar bg-light justify-content-between">
  <div class="btn-group">
    <a href="list?type=<?php echo $_GET['type']; ?>" class="btn btn-outline-secondary bg-light border-top-0 border-left-0 border-right-0 rounded-0 btn-lg"><span class="fa fa-list"></span>&nbsp;List <?php echo $types->{$_GET['type']}->plural; ?></a>
    <a href="edit?type=<?php echo $_GET['type']; ?>" class="btn btn-outline-secondary bg-light border-top-0 border-left-0 border-right-0 rounded-0 btn-lg"><span class="fa fa-edit"></span>&nbsp;New <?php echo $_GET['type']; ?></a>
  </div>
</div>
</div></div>

<h2 class="mb-4">List of <?php echo $types->{$_GET['type']}->plural; ?></h2>

<table class="my-4 table datatable">
  <thead>
    <tr>
      <th scope="col">#</th>
      <th scope="col">Title</th>
      <th scope="col"></th>
    </tr>
  </thead>
  <tbody>
  <?php 
  $ids = $dash::get_all_ids($_GET['type']);
  foreach ($ids as $arr) {
    $post = $dash::get_content($arr['id']);
    echo '<tr><th scope="row">'.$post['id'].'</th><td>'.$post['title'].'</td><td><a href="/edit.php?type='.$post['type'].'&id='.$post['id'].'">edit</a> | <a href="#">delete</a></td></tr>';
  }
  ?>
  </tbody>
</table>

</div>

<?php include_once ('footer.php'); ?>