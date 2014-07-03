<?php
class phaseCharles {
  var $r;
  var $desc;

  var $args;
  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Attack (Charles)';
  }
  function init() {
    $this->r->mChan("$from: As Charles, you may choose to !attack again (giving yourself 2 damage), or !pass.");
  }
  function cmdattack($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'Attack (Charles)'))) return;
    $this->r->currentPlayer->damage(2);
    $this->r->phases['attack']->cmdattack($from, $this->args);
  }
  function cmdpass($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'Attack (Charles)'))) return;
    $this->r->setPhase('end');
  }
}
?>
