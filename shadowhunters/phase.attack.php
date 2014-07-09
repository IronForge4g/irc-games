<?php
class phaseAttack {
  var $r;
  var $desc;

  var $validTargets;
  var $hasMachineGun;
  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Attack';
  }
  function init() {
    $this->validTargets = array();
    $this->hasMachineGun = false;
    $targetDistance = 0;
    if($this->r->currentPlayer->hasEquipment('Handgun')) $targetDistance = 1;
    foreach($this->r->players as $nick => $player) {
      if($this->r->currentPlayer == $player) continue;
      if(!($player->alive)) continue;
      if($this->distance($this->r->currentPlayer, $player) == $targetDistance) $this->validTargets[$nick] = $player;
    }
    if(count($this->validTargets) == 0) {
      $this->r->mChan($this->r->currentPlayer->nick.": You have no valid targets to !attack, please !pass.");
    } else {
      if($this->r->currentPlayer->hasEquipment('Machine Gun')) {
        $this->hasMachineGun = true;
        $this->r->mChan($this->r->currentPlayer->nick.": You have the Machine Gun. Please !attack everyone, or !pass.");
      } else {
        $targets = array_keys($this->validTargets);
        $this->r->mChan($this->r->currentPlayer->nick.": Please choose a player (".implode(', ', $targets).") to !attack or !pass.");
      }
    }
  }
  function cmdattack($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'attack'))) return;
    if(!($this->hasMachineGun)) {
      if(!($this->r->checkArgs($from, $args, 1))) return;
      $player = $args[0];
      if(!(isset($this->validTargets[$player]))) {
        $this->r->mChan("$from: Please choose a valid target to attack.");
        return;
      }
      $player = $this->validTargets[$player];
      $this->validTargets = array($args[0] => $player);
    }
    $d4 = mt_rand(1, 4);
    if($this->r->currentPlayer->hasEquipment('Masamune')) {
      $this->r->mChan("$from has the Masamune, and rolls a single d4: $d4.");
      $dmg = $d4;
      $this->damageTargets($d4);
      if($this->r->currentPlayer->revealed && $this->r->currentPlayer->character->name == 'Vampire') $this->heal(2);
    } else {
      $d6 = mt_rand(1, 6);
      $diff = $d6 - $d4;
      $dmg = abs($diff);
      if($dmg > 0) {
        $this->r->mChan("$from rolls the d4 ($d4) and the d6 ($d6) for a base damage of $dmg.");
        $this->damageTargets($dmg);
        if($this->r->currentPlayer->revealed && $this->r->currentPlayer->character->name == 'Vampire') $this->r->currentPlayer->heal(2);
      } else {
        $this->r->mChan("$from rolls the d4 ($d4) and the d6 ($d6), managing to miss with their attack.");
      }
    }
    foreach($this->validTargets as $nick => $player) {
      if($player->revealed && $player->character->name == 'Werewolf') {
        $this->r->phases['werewolf']->werewolf = $player;
        $this->r->phases['werewolf']->target = $this->r->currentPlayer;
        $this->r->setPhase('werewolf');
        return;
      }
    }
    if($this->r->currentPlayer->character->name == 'Charles' && $this->r->currentPlayer->revealed) {
      $this->r->phases['charles']->args = $args;
      $this->r->setPhase('charles');
    }
    $this->r->setPhase('end');
  }
  function cmdpass($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'Attack'))) return;
    if($this->r->currentPlayer->hasEquipment('Masamune')) {
      $this->r->mChan("$from: You possess the Masamune, and must attack.");
      return;
    }
    $this->r->setPhase('end');
  }
  function distance($playera, $playerb) {
    if($playera->location == $playerb->location) return 0;
    if($playera->location->neighbour == $playerb->location) return 0;
    return 1;
  }
  function damageTargets($amount) {
    $who = $this->r->currentPlayer;
    $adds = array();
    foreach($who->equipment as $eid => $equip) {
      if($equip->name == 'Butcher Knife') {
        $adds[] = 'Butcher Knife (+1)';
        $amount++;
      }
      if($equip->name == 'Chainsaw') {
        $adds[] = 'Chainsaw (+1)';
        $amount++;
      }
      if($equip->name == 'Rusted Broad Axe') {
        $adds[] = 'Rusted Broad Axe (+1)';
        $amount++;
      }
      if($equip->name == 'Holy Robe') {
        $adds[] = 'Holy Robe (-1)';
        $amount--;
      }
      if($equip->name == 'Spear of Longinus' && $who->revealed && $who->character->team == 'Hunter') {
        $adds[] = 'Spear of Longinus (+2)';
        $amount += 2;
      }
    }
    if(count($adds) > 0) $this->r->mChan("{$this->r->currentPlayer->nick} adds to their attack: ".implode(", ", $adds).".");
    foreach($this->validTargets as $nick => $player)
      $player->damage($amount, 'attack');
  }
}
?>
