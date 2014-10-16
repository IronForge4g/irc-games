<?php
class phaseDeadDropEnd {
  var $r;
  var $desc;

  function __construct($root) {
    $this->r = $root;
    $this->desc = 'End Game';
  }
  function init() {
    $scores = array();
    foreach($this->r->players as $nick => $player) {
      $scores[$nick] = $player->score;
    }
    arsort($scores);
    foreach($scores as $nick => $score) {
      $this->r->mChan("$nick scored $score.");
    }
    $this->r->setPhase('nogame');
    return;
  }
}
?>
