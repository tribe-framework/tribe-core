<?php
include_once __DIR__.'/../init.php';

class PDFLayer {
    /**
     * @param string
     * @return string
     *
     * this function accepts a web-url and then returns a link that can be used
     * to download the pdf
     */
    public static function convert_to_pdf ($url) {
        global $dash;

        $upload = $dash->get_uploader_path();

        $f_name = uniqid().'.pdf';
        $full_path = $upload['upload_dir'].'/'.$f_name;
        $download_link = $upload['upload_url'].'/'.$f_name;

        $dash->do_shell_command("wget 'http://api.pdflayer.com/api/convert?access_key=".PDFLayerKey."&document_url=".$url."&page_size=A4' -O ".$full_path);

        return $download_link;
    }
}

?>
