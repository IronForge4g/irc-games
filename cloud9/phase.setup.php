<?php
class phaseCloud9Setup {
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
    $this->r->setPhase('startcloud');
  }
  function setupBase() {
    $this->r->deck = new colorDeck($this->r);
    $this->r->currentCloud = 1;
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
      for($i=0;$i<6;$i++) {
        $player->hand[] = $this->r->deck->draw();;
      }
      sort($player->hand);
      $player->jumped = false;
      $player->points = 0;
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
