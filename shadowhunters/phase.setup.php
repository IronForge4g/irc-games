<?php
class phaseSetup {
  var $p;
  var $desc;

  function __construct($root) {
    $this->p = $root;
    $this->desc = 'Setting up Game'
  }
  function init() {
    $this->setupPlayers();
    $this->setupCharacters();
    $this->setupAreas();
    $this->r->started = true;
    $this->r->cmdboard();
    $this->r->setPhase('game');
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
      $last->next = $player;
      $last = $player;
    }
    $last->next = $first;
    $this->r->currentPlayer = $first;
  }
  function setupCharacters() {
    $deck = array();
    $n = array('nchar0', 'nchar1', 'nchar2', 'nchar3');
    $h = array('hchar0', 'hchar1', 'hchar2');
    $s = array('schar0', 'schar1', 'schar2');
    $playerCount = count($this->r->players);
    if($playerCount == 4) {
      $deck = array_merge((array)array_rand($s, 2), (array)array_rand($h, 2));
    } else if($playerCount == 5) {
      $deck = array_merge((array)array_rand($s, 2), (array)array_rand($h, 2), (array)array_rand($n, 1));
    } else if ($playerCount == 6) {
      $deck = array_merge((array)array_rand($s, 2), (array)array_rand($h, 2), (array)array_rand($n, 2));
    } else if ($playerCount == 7) {
      $deck = array_merge((array)array_rand($s, 2), (array)array_rand($h, 2), (array)array_rand($n, 3));
    } else if ($playerCount == 8) {
      $deck = array_merge((array)array_rand($s, 3), (array)array_rand($h, 3), (array)array_rand($n, 2));
    }
    shuffle($deck);
    $players = array_keys($this->r->players);
    foreach($players as $player) {
      $char = array_shift($deck);
      $character = new $char($this->p);
      $character->player = $this->r->players[$player];
      $this->r->players[$player]->character = $character;
      $this->r->nUser($player, "You are {$character->name}. You are on the {$character->team} team.");
    } 
  }
  function setupAreas() {
    for($i=0;$i<6;$i++) {
      $area = 'area'.$i;
      $newArea = new $area($this->p);
      $this->r->areas[$area] = $newArea;
      foreach($newArea->numbers as $num) {
        $this->r->areasNum[$num] = $newArea;
      }
    }
    $deck = array('area0', 'area1', 'area2', 'area3', 'area4', 'area5');
    shuffle($deck);
    for($bi=0;$bi<3;$bi++) {
      $block = array_shift($deck);
      $block1 = array_shift($deck);
      $this->r->areas[$block]->block = $bi;
      $this->r->areas[$block]->side = 0;
      $this->r->areas[$block]->neighbour = $this->r->areas[$block1];
      $this->r->areas[$block1]->block = $bi;
      $this->r->areas[$block1]->side = 1;
      $this->r->areas[$block1]->neighbour = $this->r->areas[$block];
      $this->r->blocks[$bi] = array();
      $this->r->blocks[$bi][0] = $this->r->areas[$block];
      $this->r->blocks[$bi][1] = $this->r->areas[$block1];
    }
  }
}
?>
