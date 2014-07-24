<?php
class phaseYardmasterExpressEnd {
  var $r;
  var $desc;

  function __construct($root) {
    $this->r = $root;
    $this->desc = 'End Game';
  }
  function init() {
    $this->r->mChan("The game has now ended. Final scores...");
    $this->r->score();
    $this->r->setPhase('nogame');
    return;
  }
}
?>
