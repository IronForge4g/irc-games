<?php
class phaseMove {
  var $r;
  var $desc;

  var $locations;
  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Player Moving';
  }
  function init() {
    $this->r->currentPlayer->init();
    $this->r->mChan($this->r->currentPlayer->nick.": You're up. Please !roll to move.");
    $this->locations = null;
  }
  function cmdroll($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'Move Player'))) return;
    $this->locations = array();
    if($this->r->currentPlayer->hasEquipment('Mystic Compass')) {
      $this->r->mChan("$from: Because you possess the Mystic Compass, you may choose to !move to:");
      $this->locations['A'] = $this->rollMovement(); 
      $this->locations['B'] = $this->rollMovement(); 
      $this->r->mChan("A. ".$this->locations['A']->name.".");
      $this->r->mChan("B. ".$this->locations['B']->name.".");
    } else {
      $this->locations['A'] = $this->rollMovement();
      $this->cmdmove($from, array('A'));
    }
  }
  function cmdmove($from, $args) {
    if($this->locations == null) return;
    if(!($this->r->checkCurrentPlayer($from, 'Move Player'))) return;
    if(!($this->r->checkArgs($from, $args, 1))) return;
    if(!(isset($this->locations[$args[0]]))) {
      $this->r->mChan("$from: Please select a valid location to move to.");
      return;
    }
    $newLocation = $this->locations[$args[0]];
    if($this->r->currentPlayer->location != null) {
      unset($this->r->currentPlayer->location->players[$from]);
    }
    $this->r->currentPlayer->location = $newLocation;
    $newLocation->players[$from] = $this->r->currentPlayer;
    $this->r->setPhase($newLocation->phase);
  }
  function rollMovement() {
    while(true) {
      $dice = mt_rand(1, 4) + mt_rand(1, 6);
      if($this->r->areasNum[$dice] == $this->r->currentPlayer->location) continue;
      return $this->r->areasNum[$dice];
    }
  }
}
?>
