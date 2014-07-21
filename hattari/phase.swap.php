<?php
class phaseHattariSwap {
  var $r;
  var $desc;

  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Swap Suspects';
  }
  function init() {
    $this->r->mChan($this->r->currentPlayer->nick.": As the first player, you have the option to !swap a suspect, or !pass this option.");
  }
  function cmds($from, $args) {
    $this->cmdswap($from, $args);
  }
  function cmdswap($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'swap'))) return;
    if(!($this->r->checkArgs($from, $args, 1, 1))) return;
    $suspect = $this->r->findSuspect($args[0]);
    if($suspect == null) {
      $this->r->mChan("$from: Please specify valid suspect to tamper with.");
      return;
    }
    $tmp = $this->r->victim;
    $this->r->victim = $this->r->suspects[$suspect];
    $this->r->suspects[$suspect] = $tmp;
    $this->r->mChan("$from has swapped $suspect with the victim.");
    $this->r->tampered = $suspect;
    $this->r->setPhase('accuse');
  }
  function cmdp($from, $args) {
    $this->cmdpass($from, $args);
  }
  function cmdpass($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'pass'))) return;
    $this->r->setPhase('accuse');
  }
}
?>

