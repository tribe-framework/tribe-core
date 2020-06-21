<?php include_once ('config-init.php'); ?>
<?php header('Content-type: application/xml; charset=utf-8'); ?>
<?php
$or=array();
$or[0]['url']=array(BASE_URL => 'loc', '2020-06-20' => 'lastmod', '1' => 'priority');
$xml = new SimpleXMLElement('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>');
array_to_xml($or, $xml);
print $xml->asXML();

function array_to_xml( $data, &$xml_data ) {
    foreach( $data as $key => $value ) {
        if( is_array($value) ) {
            if( is_numeric($key) ){
                $key = 'item'.$key; //dealing with <0/>..<n/> issues
            }
            $subnode = $xml_data->addChild($key);
            array_to_xml($value, $subnode);
        } else {
            $xml_data->addChild("$key",htmlspecialchars("$value"));
        }
     }
}
?>