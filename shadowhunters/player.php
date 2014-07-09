<?php
class player {
  var $r;
  var $nick;
  var $next;

  var $character;
  var $location;
  var $damage;
  var $alive;
  var $revealed;
  var $equipment;
  var $steal;

  var $freeTurn;
  var $guardianAngel;
  function __construct($root, $nick) {
    $this->r = $root;
    $this->nick = $nick;
    $this->next = null;
    $this->character = null;
    $this->location = null;
    $this->damage = 0;
    $this->alive = true;
    $this->revealed = false;
    $this->equipment = array();
    $this->steal = array();

    $this->freeTurn = false;
    $this->guardianAngel = false;
  }
  function init() {
    if($this->guardianAngel) $this->guardianAngel = false;
  }
  function equipment($player = '') {
    if($player == '') $player = $this->nick;
    $letters = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
    $equip = array();
    $e = 0;
    $this->r->nUser($player, "{$this->nick}'s equipment is now:");
    foreach($this->equipment as $eid => $equipment) {
      $equip[$letters[$e]] = $equipment;
      $this->r->nUser($player, $letters[$e].'. '.$equipment->name.' ('.$equipment->cardText.')');
      $e++;
    }
    $this->equipment = $equip;
  }
  function heal($amount = 1) {
    $this->r->mChan($this->nick." heals $amount damage.");
    $this->damage -= $amount;
    if($this->damage < 0) $this->damage = 0;
  }
  function damage($amount = 1, $reason = 'Generic') {
    if($this->guardianAngel) {
      $this->r->mChan($this->nick." is protected by their Guardian Angel.");
      return;
    }
    if($this->hasEquipment('Talisman')) {
      if($reason == 'Bloodthirsty Spider' || $reason == 'Vampire Bat' || $reason == 'Dynamite') {
        $this->r->mChan($this->nick." is protected by their Talisman.");
        return;
      }
    }
    if($this->hasEquipment('Fortune Brooch')) {
      if($reason == 'Weird Woods') {
        $this->r->mChan($this->nick." is protected by their Fortune Brooch.");
        return;
      }
    }
    if($reason == 'attack') {
      if($this->hasEquipment('Holy Robe')) $amount--;
    }
    if($amount < 0) $amount = 0;
    $this->r->mChan($this->nick." takes $amount damage.");
    $this->damage += $amount;
    if($this->damage >= $this->character->life) {
      $this->alive = false;
      unset($this->location->players[$this->nick]);
      $this->r->mChan("{$this->nick} has been killed by {$this->r->currentPlayer->nick}.");
      if($this->r->currentPlayer->character->name == 'Charles') {
        $dead = 0;
        foreach($this->r->players as $nick => $player) {
          if(!($player->alive)) $dead++;
        }
        if($dead > 2) {
          $this->r->checkWin($this->r->currentPlayer->nick);
          return;
        }
      }
      foreach($this->r->players as $nick => $player) {
        if($player->character->name == 'Daniel') {
          if($player->revealed == false) {
            $player->reveal();
          }
          break;
        }
      }
      if(count($this->equipment) > 0) $this->r->currentPlayer->steal[$this->nick] = $this;
      $this->reveal();
    }
  }
  function hasEquipment($find) {
    foreach($this->equipment as $eid => $equip) {
      if($equip->name == $find) return true;
    }
    return false;
  }
  function reveal() {
    if($this->revealed) {
      $this->r->mChan("{$this->nick} was already revealed as {$this->character->name} ({$this->character->team}).");
      return;
    }
    $this->revealed = true;
    $this->r->mChan("{$this->nick} is revealed as {$this->character->name} ({$this->character->team}).");
  }
}
?>
