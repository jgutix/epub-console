<?php
/**
 * Created by JetBrains PhpStorm.
 * User: jgutix
 * Date: 01-23-13
 * Time: 08:18 AM
 */
error_reporting(E_ALL);
require 'simple_html_dom.php';
if(isset($_GET['book'])){
    $file = 'tmp/'.$_GET['book'].'.epub';

    $zip = zip_open($file);
    $xhtmlFiles = array();

     if ($zip) {

         while ($zip_entry = zip_read($zip)) {
            $entryName = zip_entry_name($zip_entry);
             if (!is_dir($entryName)) {
                 $path_parts = pathinfo($entryName);

                  $ext = strtolower(trim(isset ($path_parts['extension']) ? $path_parts['extension'] : ''));

                  if($ext == 'xhtml') {

                      $xhtmlFiles[] = $entryName;

                     }
             }
         }
     }
    zip_close($zip);
    asort($xhtmlFiles);

    $zip1 = new ZipArchive;
        //Opens a Zip archive
    $epub = $zip1->open($file);
    $toc = $zip1->getFromName('toc.ncx');
    $xml = new SimpleXMLElement($toc);
//    echo '<pre>'; print_r($xml->navMap->navPoint); echo '</pre>';
    $sections = array();
    foreach($xml->navMap->navPoint as $navPoint){
        $sectionId = $navPoint->content->attributes()->src[0];
        $sectionPage='<div class="section">';
        $sectionPage.='<h1 class="sectiontitle">'.$navPoint->navLabel->text.'</h1>';
        $i=0;
        while(isset($navPoint->navPoint[$i])){
            $sectionPage.='<h2 class="sectionchaptertitle">'.($navPoint->navPoint[$i]->navLabel->text).'</h2>';
            ++$i;
        }
        $sectionPage.='</div>';
        $sections[$sectionId.''] = $sectionPage;
    }
    $fullHTML='';
    foreach($xhtmlFiles as $entry){
        $xhtml = $zip1->getFromName($entry);
        $dom = str_get_html($xhtml);
        foreach($dom->find('img') as $element){
            $uri = $element->src;
            if(!empty($uri) && $uri!='#' && !preg_match('/[http|ftp|https|mailto]:/', $uri)){
                $parts = pathinfo($uri);
                $element->src='data:image/' . (empty($parts['extension'])?'jpeg':$parts['extension']) . ';base64,' . base64_encode($zip1->getFromName($uri));
            }

        }
        foreach($dom->find('h1') as $element){
            $element->class='chaptertitle';

        }

        foreach($dom->find('h2') as $element){
//            $h2s[$entry][] = $element->innertext;
            $next = $element->next_sibling();
            $element->outertext='<div class="no-page-break">'.$element->outertext.$next->outertext.'</div>';
            $next->outertext='';

        }

//        $section = '<div class="sectionpage">'.'<h1 class="sectiontitle">'.$h1.'</h1>';
//        foreach($h2s as $element){
//            $section.='<h2>'.$element.'</h2>';
//        }
//        $section .='</div>';
        $body = $dom->find('body', 0);
        $fullHTML.= (isset($sections[$entry])?$sections[$entry]:'')
            .'<div class="chapter">'.$body->innertext.'</div>';
    }

    /** CSS */
    $css = $zip1->getFromName('objavi.css');
}
?>
<!DOCTYPE HTML>
<html lang="en-US">
<head>
    <meta charset="UTF-8">
    <title>Preview</title>
    <link rel="stylesheet" href="https://raw.github.com/sourcefabric/BookJS/master/book.css">
    <style type="text/css"><?php echo $css;?></style>
<!--    <script type="text/javascript" src="https://raw.github.com/sourcefabric/BookJS/master/book-config.js"></script>-->
    <script type="text/javascript">
        paginationConfig = {
            'sectionStartMarker': 'div.section',
            'sectionTitleMarker': 'h1.sectiontitle',
            'chapterStartMarker': 'div.chapter',
            'chapterTitleMarker': 'h1.chaptertitle',
//        //    'flowElement': "document.getElementById('flowstuff')",
            'flowElement': 'document.body',
            'alwaysEven': true,
//            'columns': 2,
            'enableFrontmatter': true,
            'bulkPagesToAdd': 50,
            'pagesToAddIncrementRatio': 1.4,
            'frontmatterContents': '<h1><?php echo $xml->docTitle->text;?></h1>'
//        	+ '<h3>Book subtitle</h3><h5>'
//        	+ 'ed. Editor 1, Editor II, Editor III</h5><div class="pagination-pagebreak">'
//        	+ '</div><div id="copyrightpage">Copyright: You<br>License: CC</div>'
        	+ '<div class="pagination-pagebreak"></div>',
            'autoStart': true

        }
    </script>
    <script type="text/javascript" src="https://raw.github.com/sourcefabric/BookJS/master/book.js"></script>
</head>
<body>
<?php echo $fullHTML;?>
</body>
</html>