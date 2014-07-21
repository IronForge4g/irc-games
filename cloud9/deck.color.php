<?php
class colorDeck extends deck {
  function __construct($root) {
    $this->r = $root;
    $this->deck = array();
    $this->discard = array();
    $this->cards = array('Rainbow', 'Rainbow', 'Rainbow', 'Rainbow');
    for($i=0;$i<18;$i++) {
      $this->cards[] = 'Green';
      $this->cards[] = 'Purple';
      $this->cards[] = 'Red';
      $this->cards[] = 'Yellow';
    }
    $this->deck = $this->cards;
  }
}
?>
