<?php
class deadDropDeck extends deck {
  function __construct($root) {
    $this->r = $root;
    $this->deck = array();
    $this->discard = array();
    $this->cards = array(0, 0, 0, 0, 1, 1, 1, 2, 2, 3, 3, 4, 5);
    $this->deck = $this->cards;
  }
}
?>
