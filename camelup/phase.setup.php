<?php
class phaseCamelUpSetup {
  var $r;
  var $desc;

  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Setting up Game';
  }
  function init() {
    $this->setupBase();
    $this->setupPlayers();
    $this->setupCamels();
    $this->r->mChan("The game is now starting. The player order is: ".$this->r->playerList());
    $this->r->setPhase('startleg');
  }
  function setupBase() {
    $this->r->gameEnd = false;
    $this->r->winDeck = array();
    $this->r->loseDeck = array();
    $this->r->started = true;
  }
  function setupPlayers() {
    $first = null;
    $last = null;
    $colors = array('Red', 'Yellow', 'Green', 'Blue', 'Pink');
    $nicks = array_keys($this->r->players);
    shuffle($nicks);
    $new = array();
    foreach($nicks as $nick) $new[$nick] = $this->r->players[$nick];
    $this->r->players = $new;
    foreach($this->r->players as $nick => $player) {
      $player->money = 3;
      shuffle($colors);
      $player->hand = array('A' => $colors[0], 'B' => $colors[1], 'C' => $colors[2], 'D' => $colors[3], 'E' => $colors[4]);
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
  function setupCamels() {
    $this->r->camels['Red'] = new camelUpCamel($this->r, 'Red', mt_rand(1, 3));
    $this->r->camels['Yellow'] = new camelUpCamel($this->r, 'Yellow', mt_rand(1, 3));
    $this->r->camels['Green'] = new camelUpCamel($this->r, 'Green', mt_rand(1, 3));
    $this->r->camels['Blue'] = new camelUpCamel($this->r, 'Blue', mt_rand(1, 3));
    $this->r->camels['Pink'] = new camelUpCamel($this->r, 'Pink', mt_rand(1, 3));
    $colors = array('Red', 'Yellow', 'Green', 'Blue', 'Pink');
    shuffle($colors);
    foreach($colors as $color) {
      $pos = $this->r->camels[$color]->position;
      $top = $this->r->topCamel($pos);
      $this->r->camels[$color]->placed = true;
      if($top == null) continue;
      $top->above = $this->r->camels[$color];
      $this->r->camels[$color]->below = $top;
    }
  }
}
?>
