<?php
# vim: sw=2 ts=2 et

function readDirect(){
  global $BASEDIR;
  $books=glob($BASEDIR.'/*/*');
  foreach ($books as &$book) $book=substr($book,strlen($BASEDIR)+1);
  return $books;
}

function readSql(){
  $cmd="/opt/bin/sqlite3 /volume1/public/calibre/metadata.db 'select path from books;'";
  $f=popen($cmd,"r");
  if ($f) {
    $books=array();
    while ($l=fgets($f)){
      array_push($books,preg_replace('#\s*$#','',utf8_decode($l)));
    }
    $rt=pclose($f);
    sort($books);
    return $books;
  }
  return array();
}

//xml support
$xml_printout=0;
$xml_data=null;
function startElement($parser, $name, $attrs) {
  global $xml_printout,$xml_data;
  //echo "name=$name<br>";
  $xml_data=null;
  if (strtolower($name) == 'dc:description'){
    $xml_printout=1;
  }
}
function endElement($parser, $name){
  global $xml_printout,$xml_data;
  //if (isset($xml_data)) echo html_entity_decode(preg_replace('/[^[:ascii:]]/','',$xml_data));
  //if (isset($xml_data)) echo preg_replace('/[^[:ascii:]]/','',$xml_data);
  if (isset($xml_data)) echo utf8_decode($xml_data);
  $xml_data=null;
  $xml_printout=0;
}

function characterData($parser, $data) {
  global $xml_printout,$xml_data;
  if ($xml_printout) {
    if (isset($xml_data)) $xml_data.=$data;
    else $xml_data=$data;
  }
  //echo $data;
}


$addons="";
if (isset($_REQUEST['filter'])) $addons.='&filter='.urlencode($_REQUEST['filter']);
if (isset($_REQUEST['direct'])) $addons.='&direct='.urlencode($_REQUEST['direct']);
$BASEDIR="/volume1/public/calibre";
$FORMATS=array('azw3','azw','mobi','epub','pdf');
if (isset($_REQUEST['download'])) {
  $name=$BASEDIR."/".$_REQUEST['download'];
  //echo "name=$name<br/>";
  if (file_exists($name)) {
    $shortname=preg_replace('#.*/#','',$name);
    if (preg_match('#epub$#',$shortname)) {
      header("Content-Type: application/epub+zip");
    }
    else {
      if (preg_match('#mobi$#',$shortname) || preg_match('#azw$#',$shortname)|| preg_match('#azw3$#',$shortname)) {
        header("Content-Type: application/x-mobipocket-ebook");
      }
      else {
        header("Content-Type: application/octet-string");
      }
    }
    header("Content-Length: " . filesize($name));
    header('Content-Disposition: attachment; filename="'.$shortname.'"');
    $h=fopen($name,"rb");
    fpassthru($h);
    exit(0);
  }
  header("HTTP/1.0 404 Not Found");
  exit(0);
}
?>
<?php header("Expires: 0"); header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); header("cache-control: no-store, no-cache, must-revalidate"); header("Pragma: no-cache");

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<meta content="text/html; charset=iso-8859-1" http-equiv="Content-Type"/>
<head>
<?php
?>
<title>B&uuml;cher</title>
<style type="text/css">
body {
	padding-left:10px; 
	padding-right:10px; 
	max-width: 600px;
	margin: auto;
	background-color: rgb(222, 223, 214);
	} 
.formatlink{
	text-decoration: none;
	background-color: rgb(162, 162, 147);
	padding-left: 15px;
	padding-right: 10px;
	padding-top: 5px;
	padding-bottom: 5px;
	color: white;
	display: block;
}
.backbutton{
	margin-top: 15px;
	margin-bottom: 10px;
}
#booklist a{
	text-decoration: none;
	color: white;
	display: block;
	font-size: 1.1em;
	margin-bottom: 3px;
	margin-top: 3px;
	padding-left: 5px;
}
#booklist li{
	list-style: none;
	background-color: rgb(162, 162, 147);
	margin-bottom: 3px;
	margin-top: 6px;
	padding-bottom: 3px;
	padding-top: 3px;
}
#booklist ul{
	padding-left:0;
}
</style>
<style type="text/css" media="print">
  .printhide {display:none}
  body {
  	background-color: white;
  	}
</style>
</head>
<body>
<?php
$serverbase="http://".$_SERVER['SERVER_ADDR']."/".$_SERVER['PHP_SELF']."?download=";
if (isset($_REQUEST['bookdetails'])) {
  $book=$_REQUEST['bookdetails'];
  $format=$_REQUEST['format'];
  $bookname=preg_replace('#.*/#','',$book);
  $bookname=preg_replace('# *[(][0-9]*[)] *$#','',$bookname);
  $author=preg_replace('#^([^/]*).*#','$1',$book);
  echo '<div style="text-align:center">';
  echo '<h1>'.htmlspecialchars($author).'<br/>'.htmlspecialchars($bookname).'</h1>';
  echo '<p>Format: '.htmlspecialchars($format).'</p>';
  $dir=$BASEDIR."/".$book;
  $dlurl=$_REQUEST['dlurl'];
  $fullbase=$dir.'/'.$fbase;
  $img=$dir."/cover.jpg";
  if (file_exists($img)){
    echo '<p><img width="200px" src="?download='.urlencode($book."/cover.jpg").'"/></p>';
  }
  echo '<p><img src="http://api.qrserver.com/v1/create-qr-code/?data='.urlencode($serverbase.urlencode($dlurl)).'&size=160x160"></img></p>';
  echo '<form method="get" class="printhide backbutton">';
  echo '<input type="hidden" name="book" value="',htmlspecialchars($book).'"/>';
  if (isset($_REQUEST['filter'])) echo '<input type="hidden" name="filter" value="'.htmlspecialchars($_REQUEST['filter']).'"/>';
        if (isset($_REQUEST['direct']))  echo '<input type="hidden" name="direct" value="'.htmlspecialchars($_REQUEST['direct']).'"/>';
  echo '<input type="submit" value="back"/>';
  echo '</form>';
  echo '</div>';
  exit(0);
}
if (isset($_REQUEST['book'])) {
  $book=$_REQUEST['book'];
  $bookname=preg_replace('#.*/#','',$book);
  $bookname=preg_replace('# *[(][0-9]*[)] *$#','',$bookname);
  $author=preg_replace('#^([^/]*).*#','$1',$book);
  echo '<h1>'.htmlspecialchars($author).'<br/>'.htmlspecialchars($bookname).'</h1>';
  $dir=$BASEDIR."/".$book;
  $fbase=$bookname.' - '.$author;
  $availableBooks=array();
  $img=$dir."/cover.jpg";
  $meta=$dir."/metadata.opf";
  echo '<div id="info"><table width="90%"><tr>';
  if (file_exists($img)){
    echo '<td><img width="200px" src="?download='.urlencode($book."/cover.jpg").'"/></td>';
  }
  if (file_exists($meta)) {
    echo '<td>';
    $xml_parser = xml_parser_create();
    xml_set_element_handler($xml_parser, "startElement", "endElement");
    xml_set_character_data_handler($xml_parser, "characterData");
    $f=fopen($meta,"r");
    if ($f){
      while ($data = fread($f, 4096)) {
        //echo "data=".htmlspecialchars($data)."<br>";
        $rt=xml_parse($xml_parser, $data, feof($f));
        //echo "parser=$rt,feof=".feof($f)."<br>";
      }
    fclose($f);
    }
  }
  echo '</tr></table></div>';
  echo '<p>Formate:</p>';
  foreach ($FORMATS as $fmt) {
    $bookformats=glob($dir."/*.".$fmt);
    foreach ($bookformats as $bookformat){
      $availableBooks[$fmt]=preg_replace('#.*/#','',$bookformat);
    }
  }
  echo '<table border="0">';
  
  foreach ($FORMATS as $fmt) {
    if ($availableBooks[$fmt]) {
      $dlurl=$book."/".$availableBooks[$fmt];
      echo '<tr class="formatlist "><td><a class="formatlink " href="?download='.urlencode($dlurl).'">'.$fmt.'</a></td><td><a class="formatlink" href="?bookdetails='.urlencode($book).'&format='.urlencode($fmt).'&dlurl='.urlencode($dlurl).$addons.'">Druckansicht</a></td></tr>';
    }
  }
  echo '</table>';
  echo '<form class="backbutton" method="get">';
  if (isset($_REQUEST['filter'])) echo '<input type="hidden" name="filter" value="'.htmlspecialchars($_REQUEST['filter']).'"/>';
        if (isset($_REQUEST['direct']))  echo '<input type="hidden" name="direct" value="'.htmlspecialchars($_REQUEST['direct']).'"/>';
  echo '<input type="submit" value="back"/>';
  echo '</form>';
  exit(0);
}
$books=null;
if ( !isset($_REQUEST['direct'])) $books=readSql();
else $books=readDirect();
echo '<h1>Bücher</h1>';
if (isset($_REQUEST['filter']) && ! isset($_REQUEST['reset'])) $filter=$_REQUEST['filter'];
?>
<form method="get" >
Filter:<input type="text" name="filter" value="<?php if (isset($filter)) echo $filter;?>"></input>
<input type="submit" name="action" value="Filter">
<input type="submit" name="reset" value="Reset">
</form>
<?php
$lastAuthor="";
echo '<div id="booklist">';
echo '<ul >';
foreach ($books as $book){
  if (preg_match('#eaDir#',$book)) continue;
  if (isset($filter) && ! preg_match('#'.$filter.'#i',$book)) continue;
  //echo "book=$book<br/>";
  $author=preg_replace('#^([^/]*).*#','$1',$book);
  //echo "author=$author<br/>";
  $bookname=preg_replace('#^[^/]*/([^/]*).*#','$1',$book);
  $bookname=preg_replace('# *[(][0-9]*[)] *$#','',$bookname);
  if ($author != $lastAuthor) {
    echo '</ul><ul>'.htmlspecialchars($author);
    $lastAuthor=$author;
  }
  echo '<li><a href="?book='.urlencode($book).$addons.'">'.htmlspecialchars($bookname).'</a></li>';
  echo '</li>';
}
echo '</ul>';
echo '</div>';
?>
</body>
</html>
