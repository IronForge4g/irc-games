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
    $key = array_rand($this->deck);
    $card = $this->deck[$key];
    unset($this->deck[$key]);
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
