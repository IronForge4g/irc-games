<?php
class phaseEnGardeSetup {
  var $r;
  var $desc;

  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Setting up Game';
  }
  function init() {
    $this->setupBase();
    $this->setupPlayers();
    $this->r->mChan("The game is now starting. The player order is: ".$this->r->playerList().".");
    $this->r->setPhase('offence');
  }
  function setupBase() {
    $this->r->started = true;
    $this->r->deck = new enGardeDeck($this->r);
    $this->r->discarded = array();
  }
  function setupPlayers() {
    $first = null;
    $last = null;
    $nicks = array_keys($this->r->players);
    shuffle($nicks);
    $new = array();
    foreach($nicks as $nick) $new[$nick] = $this->r->players[$nick];
    $this->r->players = $new;
    foreach($this->r->players as $nick => $player) {
      $player->draw();
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
    $this->r->startPlayer = $first;
    $first->position = 1;
    $first->side = 1;
    $last->position = 23;
    $last->side = -1;
  }
}
?>
