<?php
class phaseCamelUpStartLeg {
  var $r;
  var $desc;

  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Starting Leg';
  }
  function init() {
    $this->setupBets();
    $this->setupDice();
    $this->r->board();
    $this->r->players();
    $this->r->setPhase('leg');
  }
  function setupBets() {
    $this->r->legBets = array();
    $this->r->legBets['Red'] = array(5, 3, 2);
    $this->r->legBets['Yellow'] = array(5, 3, 2);
    $this->r->legBets['Green'] = array(5, 3, 2);
    $this->r->legBets['Blue'] = array(5, 3, 2);
    $this->r->legBets['Pink'] = array(5, 3, 2);
    foreach($this->r->players as $nick => $player) $player->bets = array();
  }
  function setupDice() {
    $this->r->rolledDice = array();
    $this->r->dice = array('Red', 'Yellow', 'Green', 'Blue', 'Pink');
    shuffle($this->r->dice);
  }
}
?>
