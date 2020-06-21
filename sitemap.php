<?php header('Content-type: application/xml; charset=utf-8'); ?>
<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<?php include_once ('config-init.php'); ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php
$or=array (BASE_URL => 'loc', '2020-06-20' => 'lastmod', '1' => 'priority');
$xml = new SimpleXMLElement('<url/>');
array_walk_recursive($or, array ($xml, 'addChild'));
print $xml->asXML();
?>
</urlset>