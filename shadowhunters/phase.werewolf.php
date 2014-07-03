<?php
class phaseWerewolf {
  var $r;
  var $desc;

  var $werewolf;
  var $target;
  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Werewolf Counter';
  }
  function init() {
    $this->r->mChan($this->werewolf->nick.": As the Werewolf, you may !counter or !pass");
  }
  function cmdcounter($from, $args) {
    if($from != $this->werewolf->nick) {
      $this->r->mChan("$from: The Werewolf must make this decision.");
      return;
    }
    $d4 = mt_rand(1, 4);
    if($this->werewolf->hasEquipment('Masamune')) {
      $this->r->mChan("$from has the Masamune, and rolls a single d4: $d4.");
      $dmg = $d4;
      $this->damageTarget($d4);
    } else {
      $d6 = mt_rand(1, 6);
      $dmg = abs($d6, $d4);
      if($dmg == 0) {
        $this->r->mChan("$from rolls the d4 ($d4) and the d6 ($d6) for a base damage of $dmg.");
        $this->damageTarget($dmg);
      } else {
        $this->r->mChan("$from manages to miss with their attack.");
      }
    }
    if($this->r->currentPlayer->character->name == 'Charles' && $this->r->currentPlayer->revealed) {
      $this->r->phases['charles']->args = $args;
      $this->r->setPhase('charles');
    }
    $this->r->setPhase('end');
  }
  function cmdpass($from, $args) {
    if($from != $this->werewolf->nick) {
      $this->r->mChan("$from: The Werewolf must make this decision.");
      return;
    }
    $this->r->setPhase('end');
  }
  function damageTarget($amount) {
    $adds = array();
    foreach($this->werewolf->equipment as $eid => $equip) {
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
    }
    if(count($adds) > 0) $this->r->mChan("{$this->werewolf->nick} adds to their attack: ".implode(", ", $adds).".");
    $this->target->damage($dmg, 'attack');
  }
}
?>
