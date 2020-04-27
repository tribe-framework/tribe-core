<?php
include_once ('../config-init.php');
include_once (ABSOLUTE_PATH.'/admin/header.php');
?>

<div class="container mt-3">

<?php echo get_admin_menu('list', $type); ?>

<h2 class="mb-4">List of <?php echo $types[$type]['plural']; ?></h2>

<table class="my-4 table datatable">
  <thead>
    <tr>
      <th scope="col">#</th>
      <?php
      $i=0;
      $displayed_field_slugs=array();
      foreach ($types[$type]['modules'] as $module) {
        if (!in_array($module['input_slug'], $displayed_field_slugs)) {
          echo (isset($module['list_field'])?'<th scope="col" class="pl-2" data-orderable="'.(isset($module['list_sortable'])?'true':'false').'" data-searchable="'.(isset($module['list_searchable'])?'true':'false').'" '.(isset($module['input_primary'])?'style="max-width:50%"':'').'>'.$module['input_slug'].'</th>':'');
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
      if (!in_array($module['input_slug'], $displayed_field_slugs)) {
        $module_input_slug=$module['input_slug'];

        $module_input_slug_arr=array();
        if (is_array($module_input_lang))
          $module_input_slug_arr=$module_input_lang;
        else
          $module_input_slug_arr[0]['slug']='';
        $module_input_slug_lang=$module_input_slug.($module_input_slug_arr[0]['slug']?'_'.$module_input_slug_arr[0]['slug']:'');
          
        echo (isset($module['list_field'])?'<td>'.$post[$module_input_slug_lang].'</td>':'');
        $displayed_field_slugs[]=$module['input_slug'];
      }
    }
    echo '<td><a href="/admin/edit?type='.$post['type'].'&id='.$post['id'].'"><span class="fas fa-edit"></span></a>&nbsp;<a target="new" href="/'.$post['type'].'/'.$post['slug'].'"><span class="fas fa-external-link-alt"></span></a></td></tr>';
  }
  ?>
  </tbody>
</table>

</div>

<?php include_once (ABSOLUTE_PATH.'/admin/footer.php'); ?>