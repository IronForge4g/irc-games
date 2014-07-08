<?php
class phaseFauxCureSetup {
  var $r;
  var $desc;

  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Setting up Game';
  }
  function init() {
    $this->setupPlayers();
    $this->r->setPhase('round1');
  }
  function setupPlayers() {
    $first = null;
    $last = null;
    foreach($this->r->players as $nick => $player) {
      if($last == null) {
        $first = $player;
        $last = $player;
        continue;
      }
      $player->right = $last;
      $last->left = $player;
      $last = $player;
    }
    $first->right = $last;
    $last->left = $first;
    $this->r->currentPlayer = $first;
  }
}
?>
