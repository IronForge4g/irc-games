<?php
class phaseEnd {
  var $r;
  var $desc;

  var $return;
  function __construct($root) {
    $this->r = $root;
    $this->desc = 'End Turn';
  }
  function init() {
    if($this->r->currentPlayer->freeTurn) {
      $this->r->mChan($this->r->currentPlayer->nick.": You have earned a free turn from the Concealed Knowledge.");
      $this->r->currentPlayer->freeTurn = false;
    } else {
      while(true) {
        $this->r->currentPlayer = $this->r->currentPlayer->next;
        if($this->r->currentPlayer->alive) break;
      }
    }
    $this->r->setPhase('move');
  }
}
?>
