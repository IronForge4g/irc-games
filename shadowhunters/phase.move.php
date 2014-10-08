<?php
class phaseMove {
  var $r;
  var $desc;

  var $locations;
  var $seven;
  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Player Moving';
    $this->seven = false;
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
      $this->locations['A'] = $this->rollMovement(); 
      $this->locations['B'] = $this->rollMovement(); 
      if($this->seven) {
        $this->r->mChan("$from: You have rolled a 7. Please choose which number you wish to !move to.");
        return;
      }
      $this->r->mChan("$from: Because you possess the Mystic Compass, you may choose to !move to:");
      $this->r->mChan("A. ".$this->locations['A']->name.".");
      $this->r->mChan("B. ".$this->locations['B']->name.".");
    } else {
      $this->locations['A'] = $this->rollMovement();
      if($this->seven) {
        $this->r->mChan("$from: You have rolled a 7. Please choose which number you wish to !move to.");
        return;
      }
      $this->cmdmove($from, array('A'));
    }
  }
  function cmdgoto($from, $args) {
    $this->cmdmove($from, $args);
  }
  function cmdmove($from, $args) {
    if($this->seven) {
      $this->moveSeven($from, $args);
      return;
    }
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
  function moveSeven($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'Move Player'))) return;
    if(!($this->r->checkArgs($from, $args, 1))) return;
    if(!(isset($this->r->areasNum[$args[0]]))) {
      $this->r->mChan("$from: Please select a valid location to move to.");
      return;
    }
    $newLocation = $this->r->areasNum[$args[0]];
    if($newLocation == $this->r->currentPlayer->location) {
      $this->r->mChan("$from: You must move to a new location. Please select a valid location to move to.");
      return;
    }
    if($this->r->currentPlayer->location != null) {
      unset($this->r->currentPlayer->location->players[$from]);
    }
    $this->r->currentPlayer->location = $newLocation;
    $newLocation->players[$from] = $this->r->currentPlayer;
    $this->r->setPhase($newLocation->phase);
  }
  function rollMovement() {
    $this->seven = false;
    while(true) {
      $dice = mt_rand(1, 4) + mt_rand(1, 6);
      if($dice == 7) {
        $this->seven = true;
        return;
      }
      if($this->r->areasNum[$dice] == $this->r->currentPlayer->location) continue;
      return $this->r->areasNum[$dice];
    }
  }
}
?>
