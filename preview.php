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
            if(!empty($uri) && $uri!='#' && !preg_match('/[http|ftp|https|mailto|data]:/', $uri)){
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

        foreach($dom->find('table#bluebox') as $element){
            foreach($element->find('li') as $li){
                $li->outertext='<div class="listbreak">'.$li->outertext.'</div>';
            }
            $element->outertext='<div class="tablebreak">'.$element->outertext.'</div>';
        }



        $body = $dom->find('body', 0);
        $fullHTML.= (isset($sections[$entry])?$sections[$entry]:'')
            .'<div class="chapter">'.$body->innertext.'</div>';
    }

    /** CSS */
    $css = $zip1->getFromName('objavi.css');

    $opf = $zip1->getFromName('content.opf');

    $xml = new SimpleXMLElement($opf);
    //Use that namespace
    $namespaces = $xml->getNameSpaces(true);
    //Now we don't have the URL hard-coded
    $dc = $xml->metadata->children($namespaces['dc']);
    $bookTitle = empty($dc->title)?$xml->docTitle->text:$dc->title;
    $zip1->close();
}//end if
?>
<!DOCTYPE HTML>
<html lang="en-US">
<head>
    <meta charset="UTF-8">
    <title>Preview</title>
    <!-- 
		<link rel="stylesheet" href="css/book.css">
    -->
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
            'sectionStartMarker': 'none',
            'sectionTitleMarker': 'none',
            'chapterStartMarker': 'div.chapter',
            'chapterTitleMarker': 'h1.chaptertitle',
//        //    'flowElement': "document.getElementById('flowstuff')",
            'flowElement': "document.getElementById('flow')",
            'alwaysEven': false,
//            'columns': 2,
            'enableFrontmatter': false,
            'bulkPagesToAdd': 50,             
	    'pageHeight': 9.66,
            'pageWidth': 7.44,
//	   'headerTopMargin':1.2in,
//	   'innerMargin':0.8in,
//	   'outerMargin':0.8in,
//	   'divideContents':false; 
            'pagesToAddIncrementRatio': 1.4,
            'frontmatterContents': '<h1><?php echo $bookTitle;?></h1>'
//        	+ '<h3>Book subtitle</h3><h5>'
//        	+ 'ed. Editor 1, Editor II, Editor III</h5><div class="pagination-pagebreak">'
//        	+ '</div><div id="copyrightpage">Copyright: You<br>License: CC</div>'
        	+ '<div class="pagination-pagebreak"></div>',
            'autoStart': true

        }
    </script>
    <script type="text/javascript" src="js/book.js"></script>
</head>
<body class="sweet-hyphens">
<?php if(isset($_GET['editablecss'])):?>
<style contenteditable><?php echo $css;?></style>
<?php endif;?>
<div id="flow" class="sweet-hyphens">
<?php echo $fullHTML;?>
</div>
<style>
 .pagination-page .pagination-header-chapter:before,   .pagination-page .pagination-header-section:before { 
 content:"<?php echo $bookTitle;?>" !important;
}
</style>
 <script src="js/jquery-1.4.4.min.js"></script>
  <script src="js/sweet-justice.js"></script>
</body>
</html>
