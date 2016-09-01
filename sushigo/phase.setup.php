<?php
class phaseSushiGoSetup {
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
    $this->r->setPhase('game');
  }
  function setupBase() {
    $playerCount = count($this->r->players);
    if($playerCount == 2) $this->r->deck = new trainDeck($this->r, false);
    else $this->r->deck = new trainDeck($this->r);
    $this->r->wild = new trainCar($this->r, 'Wild', 2, 'Wild', 2);
    $this->r->gameEnd = 9 - $playerCount;
    $this->r->cabooseDeck = new cabooseDeck($this->r);
    $this->r->hand = array();
    for($i=0;$i<$playerCount;$i++) $this->r->hand[] = $this->r->deck->draw();
    $this->r->caboose = $this->r->cabooseDeck->draw();
    $this->r->mChan("The caboose is {$this->r->caboose->title} ({$this->r->caboose->points} points): {$this->r->caboose->text}");
    
    $this->r->started = true;
    if($this->r->autoScore == null) $this->r->autoScore = true;
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
      $player->train = array();
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
