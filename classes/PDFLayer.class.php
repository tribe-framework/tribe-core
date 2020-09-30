<?php
include_once __DIR__.'/../init.php';

class PDFLayer {
    private function downloadPdf ($url, $upload) {
        $filename      = uniqid() . '.pdf';
        $full_path     = $upload['upload_dir'] . '/' . $filename;
        $cmd = "wget 'http://api.pdflayer.com/api/convert?access_key=" . PDFLayerKey . "&document_url=" . $url . "&page_size=A4' -O " . $full_path;

        shell_exec($cmd);

        return $filename;
    }

    private function mergePdf ($files, $upload) {
        if (gettype($files) != 'array') return false; // terminate if $files not array

        $filename = uniqid() . '.pdf';
        $files = implode(' ', $files);
        $out_file = $upload['upload_dir'] . '/' .$filename;
        $cmd = 'pdfunite ' . $files . ' ' . $out_file;

        shell_exec($cmd);
        shell_exec('rm ' . $files);

        return $filename;
    }

    /**
     * @param string
     * @return string
     *
     * this function accepts a web-url and then returns a link that can be used
     * to download the pdf
     */
    public function convert_to_pdf ($url) {
        global $dash;

        $upload = $dash->get_uploader_path();
        $filename = null;

        switch (gettype($url)) {
            case 'array':
                $pdfArray = [];

                foreach ($url as $u) {
                    $f_name = $this->downloadPdf($u, $upload);
                    array_push($pdfArray, $upload['upload_dir']. '/' .$f_name);
                }

                $filename = $this->mergePdf($pdfArray, $upload);
                break;

            case 'string':
                $filename = $this->downloadPdf($url, $upload);
                break;

            default:
                break;
        }

        if (!$filename) return false; // if filename is null, return false

        $download_link = $upload['upload_url'] . '/' . $filename;

        return $download_link;
    }
}

?>
