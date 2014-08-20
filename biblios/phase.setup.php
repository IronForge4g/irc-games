<?php
class phaseBibliosSetup {
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
    $this->r->setPhase('gift');
  }
  function setupBase() {
    $playerCount = count($this->r->players);
    $this->r->deck = new bibliosDeck($this->r, $playerCount);
    $this->r->auction = new bibliosAuctionDeck($this->r);
    $this->r->table = array();
    $this->r->dice = array('Red' => 3, 'Orange' => 3, 'Green' => 3, 'Blue' => 3, 'Purple' => 3);
    $this->r->startPlayer = null;
    
    $this->r->started = true;
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
      $player->hand = array();
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
    $this->r->currentPlayer = $first->right;
    $this->r->activePlayer = $first->right;
  }
}
?>
