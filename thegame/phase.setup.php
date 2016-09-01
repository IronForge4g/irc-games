<?php
class phaseTheGameSetup {
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
    $this->r->gameDeck = new gameDeck($this->r);
    $this->r->piles = array();
    $this->r->piles['a'] = 1;
    $this->r->piles['b'] = 1;
    $this->r->piles['y'] = 100;
    $this->r->piles['z'] = 100;
    $this->r->solo = count($this->r->players) == 1 ? true : false;
  }
  function setupPlayers() {
    $first = null;
    $last = null;
    $playerCount = count($this->r->players);
    $cardCount = array(1 => 8, 2 => 7, 3 => 6, 4 => 6, 5 => 6);
    $cardCount = $cardCount[$playerCount];
    $nicks = array_keys($this->r->players);
    shuffle($nicks);
    $new = array();
    foreach($nicks as $nick) $new[$nick] = $this->r->players[$nick];
    $this->r->players = $new;
    foreach($this->r->players as $nick => $player) {
      for($n=0;$n<$cardCount;$n++) $player->hand[] = $this->r->gameDeck->draw();
      sort($player->hand);
      if($this->r->solo) $this->r->mChan("Your hand: ".implode(', ', $player->hand));
      else $this->r->nUser($nick, "Your hand: ".implode(', ', $player->hand));
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
