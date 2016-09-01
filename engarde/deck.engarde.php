<?php
class enGardeDeck extends deck {
  function __construct($root) {
    $this->r = $root;
    $this->deck = array();
    $this->discard = array();
    for($n=0;$n<5;$n++) 
      for($i=1;$i<6;$i++) $this->cards[] = $i;
    $this->deck = $this->cards;
  }
}
?>
