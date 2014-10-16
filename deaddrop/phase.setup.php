<?php
class phaseDeadDropSetup {
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
    $this->r->deck = new deadDropDeck($this->r);
    $this->r->table = array();
    $letters = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
    for($i=0;$i<$playerCount;$i++) $this->r->table[$letters[25-$i]] = $this->r->deck->draw();
    $this->r->chosenCard = $this->r->deck->draw();
    $this->r->started = true;
    $this->r->cmdtable(null, null);
  }
  function setupPlayers() {
    $first = null;
    $last = null;
    $nicks = array_keys($this->r->players);
    $playerCount = count($this->r->players);
    shuffle($nicks);
    $new = array();
    foreach($nicks as $nick) $new[$nick] = $this->r->players[$nick];
    $this->r->players = $new;
    foreach($this->r->players as $nick => $player) {
      $player->hand = array();
      if($playerCount == 2) for($i=0;$i<5;$i++) $player->hand[] = $this->r->deck->draw();
      else if($playerCount == 3) for($i=0;$i<3;$i++) $player->hand[] = $this->r->deck->draw();
      else if($playerCount == 4) for($i=0;$i<2;$i++) $player->hand[] = $this->r->deck->draw();
      $player->displayHand();
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
