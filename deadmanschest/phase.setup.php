<?php
class phaseDeadMansChestSetup {
  var $r;
  var $desc;

  var $informants;

  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Setting up Game';
  }
  function init() {
    $this->setupBase();
    $this->setupPlayers();
    $this->r->mChan("The game is now starting. The player order is: ".$this->r->playerList().".");
    $this->r->setPhase('game');
  }
  function setupBase() {
    $this->r->started = true;
    $this->r->bids = array('31', '32', '41', '42', '43', '51', '52', '53', '54', '61', '62', '63', '64', '65', '11', '22', '33', '44', '55', '66', '21');
  }
  function setupPlayers() {
    $first = null;
    $last = null;
    $playerCount = count($this->r->players);
    $gemCount = 4;
    if($playerCount > 6) $gemCount = 3;
    $nicks = array_keys($this->r->players);
    shuffle($nicks);
    $new = array();
    foreach($nicks as $nick) $new[$nick] = $this->r->players[$nick];
    $this->r->players = $new;
    foreach($this->r->players as $nick => $player) {
      $player->gems = $gemCount;
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
