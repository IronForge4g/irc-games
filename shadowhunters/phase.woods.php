<?php
class phaseWoods {
  var $r;
  var $desc;

  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Weird Woods';
  }
  function init() {
    $this->r->mChan($this->r->currentPlayer->nick.": You have ended up at the Weird Woods. Please !damage, !heal, or !pass this action.");
    $this->card = null;
  }
  function cmddamage($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'Weird Woods'))) return;
    if(!($this->r->checkArgs($from, $args, 1))) return;
    $target = $args[0];
    if(!($this->r->valdiTarget($target))) {
      $this->r->mChan($from.": $target is not a valid player to damage.");
      return;
    }
    $targetPlayer = $this->r->players[$target];
    if(!($targetPlayer->alive)) {
      $this->r->mChan($from.": $target is not a valid player to damage.");
      return;
    }
    $targetPlayer->damage(2, 'Weird Woods');
    $this->r->setPhase('attack');
  }
  function cmdheal($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'Weird Woods'))) return;
    if(!($this->r->checkArgs($from, $args, 1))) return;
    $target = $args[0];
    if(!($this->r->validTarget($target))) {
      $this->r->mChan($from.": $target is not a valid player to heal.");
      return;
    }
    $targetPlayer = $this->r->players[$target];
    if(!($targetPlayer->alive)) {
      $this->r->mChan($from.": $target is not a valid player to heal.");
      return;
    }
    $targetPlayer->heal();
    $this->r->setPhase('attack');
  }
  function cmdpass($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'Weird Woods'))) return;
    $this->r->setPhase('attack');
  }
}
?>
