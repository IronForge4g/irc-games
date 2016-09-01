<?php
class gameDeck extends deck {
  function __construct($root) {
    $this->r = $root;
    $this->deck = array();
    $this->discard = array();
    for($i=2;$i<100;$i++) {
      $this->cards[] = $i;
    }
    $this->deck = $this->cards;
  }
}
?>
