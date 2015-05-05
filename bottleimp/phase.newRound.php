<?php
class phaseBottleImpNewRound {
  var $r;
  var $desc;

  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Setting up New Round';
  }
  function init() {
    $this->setupBase();
    $this->setupPlayers();
    $this->r->setPhase('pass');
  }
  function setupBase() {
    $this->r->deck = new impDeck($this->r);
    $this->r->bottle = new impCard($this->r, 19, 'White');
    $this->r->impHand = array();
    $this->r->cursed = null;
  }
  function setupPlayers() {
    $playerCount = count($this->r->players);
    $cards = 36 / $playerCount;
    foreach($this->r->players as $nick => $player) {
      $letters = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
      shuffle($letters);
      $displayLetter = 0;
      $player->hand = array();
      $player->tricks = array();
      for($c=0;$c<$cards;$c++) {
        $tLetter = $letters[$displayLetter++];
        $player->hand[$tLetter] = $this->r->deck->draw();
      }
    }
    $this->r->dealer = $this->r->dealer->left;
    $this->r->currentPlayer = $this->r->dealer->left;
  }
}
?>
