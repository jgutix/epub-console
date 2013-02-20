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
        if($entry=='cover.xhtml'){
            continue;
        }
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
            $next = $element->next_sibling();
            if(!empty($next)){
                $element->outertext='<div class="no-page-break">'.$element->outertext.$next->outertext.'</div>';
                            $next->outertext='';
            }
        }

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
    <link rel="stylesheet" href="https://raw.github.com/sourcefabric/BookJS/0.25.0/book.css">
    <?php if(!isset($_GET['editablecss'])):?>
    <style type="text/css"><?php echo $css;?></style>
    <?php else:?>
    <style>
        body style {
            display: block;
            background: #e1e1e1;
            color: black;
            font: 10px courier;
            padding: 5px;
            white-space: pre;
            width:180px;
            height:100%;
             position: fixed;
          top: 5px;
          bottom: 5px;
          overflow-y:auto;
          border: 1px solid green;
        }
    </style>
    <?php endif;?>

<!--    <script type="text/javascript" src="https://raw.github.com/sourcefabric/BookJS/master/book-config.js"></script>-->
    <script type="text/javascript">
        paginationConfig = {
            'sectionStartMarker': 'div.section',
            'sectionTitleMarker': 'h1.sectiontitle',
            'chapterStartMarker': 'div.chapter',
            'chapterTitleMarker': 'h1.chaptertitle',
            'flowElement': "document.getElementById('flow')",
            'alwaysEven': true,
            'enableFrontmatter': true,
            'bulkPagesToAdd': 50,
            'pagesToAddIncrementRatio': 1.4,
            'frontmatterContents': '<h1><?php echo $xml->docTitle->text;?></h1>'
        	+ '<div class="pagination-pagebreak"></div>',
            'autoStart': true

        }
    </script>
    <script type="text/javascript" src="https://raw.github.com/sourcefabric/0.25.0/master/book.js"></script>
</head>
<body>
<?php if(isset($_GET['editablecss'])):?>
<style contenteditable><?php echo $css;?></style>
<?php endif;?>
<div id="flow">
<?php echo $fullHTML;?>
</div>
</body>
</html>