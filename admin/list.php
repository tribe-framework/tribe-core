<?php
include_once ('../config-init.php');
include_once (ABSOLUTE_PATH.'/admin/header.php');
?>

<div class="p-3">

<?php
if ($_GET['role'])
  $role = json_decode(urldecode($_GET['role']), true);
?>

<?php echo get_admin_menu('list', $type, $_GET['role']); ?>

<h2 class="mb-4">List of <?php echo ($type=='user'?$role['title'].'&nbsp;<span class="fas fa-angle-double-right"></span>&nbsp;':'').$types[$type]['plural']; ?></h2>

<table class="my-4 table table-borderless table-hover datatable">
  <thead>
    <tr>
      <th scope="col">#</th>
      <?php
      $i=0;
      $displayed_field_slugs=array();
      foreach ($types[$type]['modules'] as $module) {
        if (!in_array($module['input_slug'], $displayed_field_slugs)) {
          echo ((isset($module['list_field']) && $module['list_field'])?'<th scope="col" class="pl-2" data-orderable="'.(isset($module['list_sortable'])?$module['list_sortable']:'false').'" data-searchable="'.(isset($module['list_searchable'])?$module['list_searchable']:'false').'" '.((isset($module['input_primary']) && $module['input_primary'])?'style="max-width:50%"':'').'>'.$module['input_slug'].'</th>':'');
          $displayed_field_slugs[]=$module['input_slug'];
        }
        $i++;
      } ?>
      <th scope="col" data-orderable="false" data-searchable="false"></th>
    </tr>
  </thead>
  <tbody>
  <?php 
  $ids = $dash::get_all_ids($type);
  foreach ($ids as $arr) {
    $post = $dash::get_content($arr['id']);
    echo '<tr><th scope="row">'.$post['id'].'</th>';
    foreach ($types[$type]['modules'] as $module) {
      if (isset($module['list_field']) && $module['list_field'] && (!$module['restrict_id_max'] || $post['id']<=$module['restrict_id_max']) && (!$module['restrict_id_min'] || $post['id']>=$module['restrict_id_min'])) {
        $module_input_slug_lang=$module['input_slug'].(is_array($module['input_lang'])?'_'.$module['input_lang'][0]['slug']:'');
        echo '<td>'.$post[$module_input_slug_lang].'</td>';
      }
    }
    echo '<td><a href="/admin/edit?type='.$post['type'].'&id='.$post['id'].'"><span class="fas fa-edit"></span></a>&nbsp;<a target="new" href="/'.$post['type'].'/'.$post['slug'].'"><span class="fas fa-external-link-alt"></span></a></td></tr>';
  }
  ?>
  </tbody>
</table>

</div>

<?php include_once (ABSOLUTE_PATH.'/admin/footer.php'); ?>