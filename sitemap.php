<?php include_once ('config-init.php'); ?>
<?php header('Content-type: application/xml; charset=utf-8'); ?>
<?php
$or=array();
$or[0]['url']=array(BASE_URL => 'loc', '2020-06-20' => 'lastmod', '1' => 'priority');
$xml = new SimpleXMLElement('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>');
array_walk_recursive($or, array ($xml, 'addChild'));
print $xml->asXML();
?>
