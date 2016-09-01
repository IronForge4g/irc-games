<?php
class phaseSecretHitlerPresident {
  var $r;
  var $desc;

  function __construct($root) {
    $this->r = $root;
    $this->desc = 'President';
  }
  function init() {
    $player = $this->r->president;
    $this->r->currentPlayer = $player;
    $this->r->mChan("Facist Track: ".implode(', ', $this->r->facistTrack));
    $this->r->mChan("Liberal Track: ".implode(', ', $this->r->liberalTrack));
    if($this->r->electionTrack > 0) $this->r->mChan("Failed Elections: ".{$this->r->electionTrack});
    $this->r->mChan($player->nick.', you are the President. Please !(n)ominate a Chancellor.');
  }
  function cmdn($from, $args) {
    $this->cmdnominate($from, $args);
  }
  function cmdnominate($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, '!(n)ominate a Chancellor'))) return;
    if(!($this->r->checkArgs($from, $args, 1))) return;
    $player = $this->r->president;
    $chancellor = $this->r->findPlayer($args[0]);
    if($chancellor == null || $chancellor == $player) {
      $this->r->mChan($from.': Please !(n)ominate a valid player.');
      return;
    }
    $playerCount = count($this->r->players);
    if($chancellor == $this->r->lastPresident && $playerCount > 5) {
      $this->r->mChan($from.": {$args[0]} was President last round, and cannot be nominated as Chancellor. Please !(n)ominate a valid player.");
      return;
    }
    if($chancellor == $this->r->lastChancellor) {
      $this->r->mChan($from.": {$args[0]} was Chancellor last round, and cannot be nominated as Chancellor. Please !(n)ominate a valid player.");
      return;
    }
    $this->r->setPhase('vote');
  }
}
?>
