<?php
class secretHitlerDeck {
  var $r;

  var $cards;
  var $deck;
  var $discard;

  function __construct($root) {
    $this->r = $root;
    $this->deck = array();
    $this->discard = array();
    $this->cards = array(
      'Fascist',
      'Fascist',
      'Fascist',
      'Fascist',
      'Fascist',
      'Fascist',
      'Fascist',
      'Fascist',
      'Fascist',
      'Fascist',
      'Fascist',
      'Liberal',
      'Liberal',
      'Liberal',
      'Liberal',
      'Liberal',
      'Liberal'
    );
    shuffle($this->cards);
    $this->deck = $this->cards;
  }
  function draw() {
    if(count($this->deck) == 0) {
      if(count($this->discard) == 0) return null;
      $this->deck = $this->discard;
      shuffle($this->deck);
      $this->discard = array();
    }
    $card = array_shift($this->deck);
    return $card;
  }
  function discard($card) {
    $this->discard[] = $card;
  }
  function count() {
    return count($this->deck);
  }
}
?>
