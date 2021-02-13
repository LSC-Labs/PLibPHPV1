<?php
$CompressHtml = false;

function writeHtmlHeader($PageTitle, $PageCSS) {
    $htmlPreambel = <<<EOTXT
        <!doctype html>
        <html lang="en">
            <head>
        EOTXT;
    
    writeHtml($htmlPreambel.'<title>'.$PageTitle.'</title>');
    $htmlMetaBlock = file_get_contents(dirname(__FILE__)."/../rsc/Html-HeaderCommonArea.html");
    writeHtml($htmlMetaBlock);
    echo ("<!-- Page CSS -->");
    echo (" <style>");
    if(isset($PageCSS)) echo($PageCSS);
    echo ("</style>");
    $htmlEnd = <<<EOTXT
        </head>
    EOTXT;
    writeHtml($htmlEnd);
}

function writePageNavigation() {
    $Data  = file_get_contents("../rsc/Page-Navigation.html");
    writeHtml($Data);
}



function writePageFooter() {
}

function writeHtmlFooter() {
    $Data  = file_get_contents("../rsc/Page-Html-Footer.html");
    writeHtml($Data);
}

function writeHtml($t)
{
    global $CompressHtml;
    if($CompressHtml) {
        $t = preg_replace('/>\s*\n\s*</', '><', $t); // line break between tags
        $t = preg_replace('/\n/', ' ', $t); // line break to space
        $t = preg_replace('/(.)\s+(.)/', '$1 $2', $t); // spaces between letters
        $t = preg_replace("/;\s*(.)/", ';$1', $t); // colon and letter
        $t = preg_replace("/>\s*(.)/", '>$1', $t); // tag and letter
        $t = preg_replace("/(.)\s*</", '$1<', $t); // letter and tag
        $t = preg_replace("/;\s*</", '<', $t); // colon and tag
        $t = preg_replace("/;\s*}/", '}', $t); // colon and curly brace
        $t = preg_replace("/(.)\s*}/", '$1}', $t); // letter and curly brace
        $t = preg_replace("/(.)\s*{/", '$1{', $t); // letter and curly brace
        $t = preg_replace("/{\s*{/", '{{', $t); // curly brace and curly brace
        $t = preg_replace("/}\s*}/", '}}', $t); // curly brace and curly brace
        $t = preg_replace("/{\s*([\w|.|\$])/", '{$1', $t); // curly brace and letter
        $t = preg_replace("/}\s*([\w|.|\$])/", '}$1', $t); // curly brace and letter
        $t = preg_replace("/\+\s+\'/", "+ '", $t); // plus and quote
        $t = preg_replace('/\+\s+\"/', '+ "', $t); // plus and double quote
        $t = preg_replace("/\'\s+\+/", "' +", $t); // quote and plus
        $t = preg_replace('/\"\s+\+/', '" +', $t); // double quote and plus
    }
    echo $t;
}

?>