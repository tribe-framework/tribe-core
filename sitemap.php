<?php include_once ('config-init.php'); ?>
<?php header('Content-type: application/xml; charset=utf-8'); ?>
<?php
$or=array();
$xml = new SimpleXMLElement('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>');
$or[0]['url']=array('loc'=>BASE_URL, 'lastmod'=>'2020-06-20', 'priority'=>'1');
to_xml($xml, $or);
print $xml->asXML();

function to_xml(SimpleXMLElement $object, array $data) {   
    foreach ($data as $dt) {
    	foreach ($dt as $key => $value) {
	        if (is_array($value)) {
	            $new_object = $object->addChild($key);
	            to_xml($new_object, $value);
	        } else {
	            // if the key is an integer, it needs text with it to actually work.
	            if ($key == (int) $key) {
	                $key = "key_$key";
	            }

	            $object->addChild($key, $value);
	        }
	    }   
    }   
}   
?>