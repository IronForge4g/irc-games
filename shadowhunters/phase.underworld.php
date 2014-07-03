<?php
class phaseUnderworld {
  var $r;
  var $desc;

  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Underworld Gate';
  }
  function init() {
    $this->r->mChan($this->r->currentPlayer->nick.": You have ended up at the Underworld Gate. Please !draw a card (green/white/black), or !pass this action.");
  }
  function cmddraw($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'Underworld Gate'))) return;
    if(count($args) != 1) {
      $this->r->mChan($from.": Please specify the deck you wish to draw from.");
      return;
    }
    $card = strtolower($args[0]);
    $card = $card{0};
    if($card == 'g') {
      $this->r->phase = $this->r->phases['hermit'];
      $this->r->phase->card = null;
      $this->r->phase->target = null;
      $this->r->phase->cmddraw($from, array());
    } else if($card == 'w') {
      $this->r->phase = $this->r->phases['church'];
      $this->r->phase->card = null;
      $this->r->phase->target = null;
      $this->r->phase->cmddraw($from, array());
    } else if($card == 'b') {
      $this->r->phase = $this->r->phases['cemetary'];
      $this->r->phase->card = null;
      $this->r->phase->target = null;
      $this->r->phase->cmddraw($from, array());
    } else {
      $this->r->mChan($from.": Please specify a valid deck to !draw from (green/white/black).");
      return;
    }
  }
  function cmdpass($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'Underworld Gate'))) return;
    $this->r->setPhase('attack');
  }
}
?>
