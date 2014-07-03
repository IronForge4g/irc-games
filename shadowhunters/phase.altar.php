<?php
class phaseAltar {
  var $r;
  var $desc;

  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Erstwhile Altar';
  }
  function init() {
    $count = 0;
    foreach($this->r->players as $nick => $player) {
      if($player == $this->r->currentPlayer) continue;
      $count += count($player->equipment);
      if($count > 0) break;
    }
    if($count == 0) {
      $this->r->mChan($this->r->currentPlayer->nick.": You have ended up at the Erstwhile Altar. No one has any equipment to steal.");
      $this->r->setPhase('attack');
      return;
    }
    $this->r->mChan($this->r->currentPlayer->nick.": You have ended up at the Erstwhile Altar. Please '!steal player equipment', or !pass this action.");
  }
  function cmdsteal($from, $args) {
    if(count($args) != 2) {
      $this->r->mChan("$from: Please select a valid target and piece of equipment.");
      return false;
    }
    $player = null;
    $equipment = null;
    if($this->r->validTarget($args[0])) {
      if(isset($this->r->players[$args[0]]->equipment[$args[1]])) {
        $player = $this->r->players[$args[0]];
        $equipment = $args[1];
      }
    }
    else if($this->r->validTarget($args[1])) {
      if(isset($this->r->players[$args[1]]->equipment[$args[0]])) {
        $player = $this->r->players[$args[1]];
        $equipment = $args[1];
      }
    }
    if($player == null) {
      $this->r->mChan("$from: Please select a valid target and piece of equipment.");
      return false;
    }
    $this->r->mChan("$from takes ".$player->equipment[$equipment]->name." from {$player->nick}.");
    $this->r->currentPlayer->equipment[] = $player->equipment[$equipment];
    unset($player->equipment[$equipment]);
    $this->r->currentPlayer->equipment();
    $player->equipment();
    $this->r->setPhase('attack');
  }
  function cmdpass($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'Erstwhile Altar'))) return;
    $this->r->setPhase('attack');
  }
}
?>
