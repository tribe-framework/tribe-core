<?php
include_once ('../config-init.php');
include_once (ABSOLUTE_PATH.'/admin/header.php');
?>

<div class="p-3">

<?php echo get_admin_menu('dash'); ?>

<?php
$op='';
$postids=$dash::get_all_ids('lane');
if ($postids) {
  echo '<div class="card my-2"><div class="card-header">Mapping Overview</div><div class="card-body p-0"><div id="map" style="height: 500px"></div>';
  foreach ($postids as $postarr) {
    $pdata=$dash::get_content($postarr['id']);
    $i++;
    $op.='
      var flightPlanCoordinates_'.$i.' = [
      {lat: '.$pdata['start_lat'].', lng: '.$pdata['start_lng'].'},
      {lat: '.$pdata['end_lat'].', lng: '.$pdata['end_lng'].'}
    ];
    var flightPath_'.$i.' = new google.maps.Polyline({
      path: flightPlanCoordinates_'.$i.',
      strokeColor: \'#00aaee\',
      strokeOpacity: 1.0,
      strokeWeight: 10
    });
    flightPath_'.$i.'.setMap(map);';
  }
  ?>
  <script type="text/javascript"> 
  function initMap() {
    var map = new google.maps.Map(document.getElementById('map'), {
      zoom: 16,
      center: <?php echo '{lat: 28.6310992, lng: 77.2883984}'; ?>
    });
    <?php echo $op; ?>
  }
  </script>
<?php echo '</div></div>'; } ?>

<div class="card-group m-0">
<div class="card my-2">
  <div class="card-header">Analytics</div>
  <div class="card-body">
    <p class="card-text">This is a wider card with supporting text below as a natural lead-in to additional content. This content is a little bit longer.</p>
    <p class="card-text"><small class="text-muted">Last updated 3 mins ago</small></p>
  </div>
</div>
<div class="card my-2">
  <div class="card-header">Latest</div>
  <div class="card-body">
    <p class="card-text">This is a wider card with supporting text below as a natural lead-in to additional content. This content is a little bit longer.</p>
    <p class="card-text"><small class="text-muted">Last updated 3 mins ago</small></p>
  </div>
</div>
</div>
</div>

<?php include_once (ABSOLUTE_PATH.'/admin/footer.php'); ?>