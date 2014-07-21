<?php
class phaseCloud9EndClimb {
  var $r;
  var $desc;

  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Climbing';
  }
  function init() {
    foreach($this->r->players as $nick => $player) {
      $player->jumped = false;
      if($player->points >= 50) {
        $this->r->mChan("The game has now ended. Final scores...");
        $this->r->score();
        $this->r->setPhase('nogame');
        return;
      }
      $player->hand[] = $this->r->deck->draw();
      sort($player->hand);
    }
    $this->r->setPhase('startcloud');
  }
}
?>
