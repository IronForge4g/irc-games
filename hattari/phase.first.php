<?php
class phaseHattariFirst {
  var $r;
  var $desc;

  function __construct($root) {
    $this->r = $root;
    $this->desc = 'First Player';
  }
  function init() {
    $playerCount = count($this->r->players);
    if($playerCount == 2) $this->r->suspects = array('X' => 'X', 3 => 3, 4 => 4, 5 => 5, 6 => 6, 7 => 7);
    else if($playerCount == 3) $this->r->suspects = array('X' => 'X', 3 => 3, 4 => 4, 5 => 5, 6 => 6, 7 => 7, 8 => 8);
    else if($playerCount == 4) $this->r->suspects = array('X' => 'X', 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6, 7 => 7, 8 => 8);
    foreach($this->r->players as $nick => $player) {
      $player->accused = null;
      $suspect = array_rand($this->r->suspects);
      $player->suspect = $this->r->suspects[$suspect];
      unset($this->r->suspects[$suspect]);
    }
    $playerCount = count($this->r->players);
    foreach($this->r->players as $nick => $player) {
      $this->r->nUser($nick, "The first suspect you interrogate is #{$player->suspect}. You pass this suspect on to {$player->right->nick}.");
      if($playerCount > 2) $this->r->nUser($nick, "{$player->left->nick} passes you suspect #{$player->left->suspect} for interrogation.");
    }
    $victim = array_rand($this->r->suspects);
    $this->r->victim = $this->r->suspects[$victim];
    unset($this->r->suspects[$victim]);
    $newSuspects = array();

    $suspect = array_rand($this->r->suspects);
    $newSuspects['A'] = $this->r->suspects[$suspect];
    unset($this->r->suspects[$suspect]);
    $suspect = array_rand($this->r->suspects);
    $newSuspects['B'] = $this->r->suspects[$suspect];
    unset($this->r->suspects[$suspect]);
    $suspect = array_rand($this->r->suspects);
    $newSuspects['C'] = $this->r->suspects[$suspect];
    unset($this->r->suspects[$suspect]);
    $this->r->suspects = $newSuspects;

    $this->r->tampered = null;
    $this->r->unknown = null;
    $this->r->accused = array();
    $this->r->accused['A'] = array();
    $this->r->accused['B'] = array();
    $this->r->accused['C'] = array();

    $this->r->firstPlayer = $this->r->currentPlayer;
    $this->r->board();
    $this->r->mChan($this->r->currentPlayer->nick.": Please choose which two suspects you would like to !interrogate (!i a b).");
  }
  function cmdi($from, $args) {
    $this->cmdinterrogate($from, $args);
  }
  function cmdinterrogate($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'interrogate'))) return;
    if(!($this->r->checkArgs($from, $args, 2, 2))) return;
    $suspect1 = $this->r->findSuspect($args[0]);
    $suspect2 = $this->r->findSuspect($args[1]);
    if($suspect1 == null || $suspect2 == null) {
      $this->r->mChan("$from: Please specify valid suspects for interrogation.");
      return;
    }
    $this->r->nUser($from, "Suspect $suspect1 reveals themselves to be {$this->r->suspects[$suspect1]}, and Suspect $suspect2 reveals themselves to be {$this->r->suspects[$suspect2]}.");
    if($suspect1 != 'A' && $suspect2 != 'A') $this->r->unknown = 'A';
    else if($suspect1 != 'B' && $suspect2 != 'B') $this->r->unknown = 'B';
    else if($suspect1 != 'C' && $suspect2 != 'C') $this->r->unknown = 'C';
    $this->r->setPhase('swap');
  }
}
?>

