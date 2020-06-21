<?php include_once ('config-init.php'); ?>
<?php header('Content-type: application/xml; charset=utf-8'); ?>
<?php
$or=array();
$xml = new SimpleXMLElement('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>');

$or['url']=array('loc'=>BASE_URL, 'lastmod'=>'2020-06-20', 'priority'=>'1');
to_xml($xml, $or);

foreach ($types['webapp']['searchable_types'] as $type) {
	$ids=$dash->get_all_ids($type);
	foreach ($ids as $idr) {
		$post=$dash->get_content($idr['id']);
		$or['url']=array('loc'=>BASE_URL.'/'.$post['type'].'/'.$post['slug'], 'lastmod'=>date('Y-m-d', $post['updated_on']), 'priority'=>'0.7');
		to_xml($xml, $or);
	}
}

function to_xml(SimpleXMLElement $object, array $data) {
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            $new_object = $object->addChild($key);
            to_xml($new_object, $value);
        } else {
            if (is_int($key))
                $key = "key_$key";
            $object->addChild($key, $value);
        }
    }
}
?>