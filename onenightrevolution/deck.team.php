<?php
class teamDeck extends deck {
  function __construct($root, $players) {
    $this->r = $root;
    $this->deck = array();
    $this->discard = array();
    $this->cards[] = 'Informant';
    $this->cards[] = 'Informant';
    $this->cards[] = 'Informant';
    for($i=0;$i<$players;$i++) $this->cards[] = 'Rebel';
    $this->deck = $this->cards;
  }
}
?>
