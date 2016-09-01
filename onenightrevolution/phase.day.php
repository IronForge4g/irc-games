<?php
class phaseOneNightRevolutionDay {
  var $r;
  var $desc;

  var $called;
  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Day';
  }
  function init() {
    $this->r->mChan("Discussion can now begin. Anytime during this discussion, !vote in private for who you believe to be the informant. When you believe enough discussion has happened, !call for a vote. When a majority of players has called for a vote, your votes will be revealed.");
    $this->called = 0;
  }
  function tick() {
    $now = time() - 10;
    if($this->called > 0 && $this->called < $now) {
      $this->r->setPhase('end');
    }
  }
  function cmdcall($from, $args, $source) {
    $player = $this->r->findPlayer($from);
    if($player == null) return;
    if($player->called) {
      $this->r->mChan("You have already called for a vote.");
      return;
    }
    $player->called = true;
    $this->r->called++;
    $playerCount = count($this->r->players);
    $required = ceil($playerCount / 2);
    if($this->r->called >= $required) {
      $this->r->mChan("$from has called for a vote. There are now {$this->r->called} calls of the {$required} required for an accusation. You have 10 seconds to cast your vote, or you will vote for yourself!");
      $this->called = time();
    } else {
      $this->r->mChan("$from has called for a vote. There are now {$this->r->called} calls of the {$required} required for an accusation.");
    }
  }
}
?>
