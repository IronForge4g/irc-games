<?php
class phaseBottleImpSetup {
  var $r;
  var $desc;

  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Setting up Game';
  }
  function init() {
    $this->setupBase();
    $this->setupPlayers();
    $this->r->mChan("The game is now starting. The player order is: ".$this->r->playerList());
    $this->r->setPhase('newRound');
  }
  function setupBase() {
    $this->r->started = true;
  }
  function setupPlayers() {
    $first = null;
    $last = null;
    $playerCount = count($this->r->players);
    $cards = 36 / $playerCount;
    $nicks = array_keys($this->r->players);
    shuffle($nicks);
    $new = array();
    foreach($nicks as $nick) $new[$nick] = $this->r->players[$nick];
    $this->r->players = $new;
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
    $this->r->dealer = $last;
    $this->r->currentPlayer = $this->r->dealer->left;
  }
}
?>
