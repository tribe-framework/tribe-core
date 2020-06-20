<?php
header('Content-Type: application/json');
include_once ('../config-init.php');
$or=array();
if ($types['webapp']['searchable_types'])
	$or['data']=$sql->executeSQL("SELECT `id`, CONCAT('<a href=\"/', `content`->>'$.type', '/', `content`->>'$.slug', '\" target=\"new\">', `content`->>'$.title', '</a> <div class=\"d-none\">', `content`->>'$.view_searchable_data', '</div>') `result` FROM `data` WHERE `content`->'$.content_privacy'='public' && `content`->'$.type'!='' ORDER BY `id` DESC");
echo json_encode($or);
/* code for converting existing data into searchable
include_once(ABSOLUTE_PATH.'/includes/dash.class.php');
$dash = new dash();
$q=$sql->executeSQL("SELECT `id` FROM `data` WHERE 1");
foreach ($q as $r) {
	$og=$dash->get_content($r['id']);
	$dash->push_content($og);
	echo $r['id'].'<br>';
}
*/
?>