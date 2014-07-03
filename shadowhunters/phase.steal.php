<?php
class phaseSteal {
  var $r;
  var $desc;

  var $return;
  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Steal Equipment';
  }
  function init() {
    $steals = array_keys($this->r->currentPlayer->steal);
    if($this->r->currentPlayer->hasEquipment('Silver Rosary')) {
      $this->r->mChan("$from possesses the Silvery Rosary, stealing all equipment from: ".implode(', ', $steals).".");
      foreach($this->r->currentPlayer->steals as $nick => $player) {
        foreach($player->equipment as $eid => $equip) {
          $this->r->currentPlayer->equipment[] = $equip;
        }
      }
      foreach($steals as $steal) unset($this->r->currentPlayer->steals[$steal]);
      $this->r->currentPlayer->equipment();
      $this->r->setPhase($return);
      return;
    }
    if($this->r->currentPlayer->character->name == 'Bob' && $this->r->currentPlayer->revealed) {
      $this->r->mChan("$from is Bob. As Bob, he steals all equipment from: ".implode(', ', $steals).".");
      foreach($this->r->currentPlayer->steals as $nick => $player) {
        foreach($player->equipment as $eid => $equip) {
          $this->r->currentPlayer->equipment[] = $equip;
        }
      }
      foreach($steals as $steal) unset($this->r->currentPlayer->steals[$steal]);
      $this->r->currentPlayer->equipment();
      $this->r->setPhase($return);
      return;
    }
    if(count($steals) > 0) {
      $this->r->mChan("$from: For having killed off ".implode(', ', $steals).", please !steal a piece of equipment from them.");
    }
  }
  function cmdsteal($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'Steal Equipment'))) return;
    if(!($this->r->checkArgs($from, $args, 2))) return;
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
        $equipment = $args[0];
      }
    }
    if($player == null) {
      $this->r->mChan($from.": Please select a valid target and piece of equipment.");
      return false;
    }
    $this->r->mChan($from." takes ".$player->equipment[$e]->name." from {$player->nick}. The rest is discarded.");
    $target = $this->r->currentPlayer;
    $target->equipment[] = $player->equipment[$equipment];
    unset($player->equipment[$equipment]);
    $target->equipment();
    $eids = array();
    foreach($player->equipment as $eid => $equip) {
      if($equip->type == 'Cemetary') $this->r->cemetaryDeck->discard($equip);
      else if($equip->type == 'Church') $this->r->churchDeck->discard($equip);
      $eids[] = $eid;
    }
    foreach($eids as $eid) unset($player->equipment[$eid]);
    unset($this->r->currentPlayer->steal[$player->nick]);
    if(count($this->r->currentPlayer->steal) == 0) {
      $this->r->setPhase($return);
    } else {
      $steals = array_keys($this->r->currentPlayer->steal);
      $this->r->mChan("$from: You still need to take from: ".implode(', ', $steals).".");
    }
  }
}
?>
