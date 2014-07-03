<?php
class deck {
  var $r;

  var $cards;
  var $deck;
  var $discard;

  function draw() {
    if(count($this->deck) == 0) {
      if(count($this->discard) == 0) return null;
      $this->deck = $this->discard;
      $this->discard = array();
    }
    $keys = array_keys($this->deck);
    shuffle($keys);
    $card = $this->deck[$keys[0]];
    unset($this->deck[$keys[0]]);
    return $card;
  }
  function discard($card) {
    $this->discard[] = $card;
  }
}
?>
