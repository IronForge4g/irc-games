<?php
require_once('../generic/deck.base.php');
require_once('deck.codenames.php');
$distrib = array();
for($i=0;$i<100000;$i++) {
  $deck = new codenamesDeck(null);
  $words = array();
  for($n=0;$n<25;$n++) {
    $word = $deck->draw();
    if(!(isset($distrib[$word]))) $distrib[$word] = 0;
    $distrib[$word]++;
  }
}
asort($distrib);
print_r($distrib);
echo count($distrib)."\n";
?>
