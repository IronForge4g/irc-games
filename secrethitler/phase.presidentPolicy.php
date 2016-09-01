<?php
class phaseSecretHitlerPresidentPolicy {
  var $r;
  var $desc;

  function __construct($root) {
    $this->r = $root;
    $this->desc = 'President Policy';
  }
  function init() {
    $player = $this->r->president;
    $this->r->currentPlayer = $player;
    $this->r->mChan("Facist Track: ".implode(', ', $this->r->facistTrack));
    $this->r->mChan("Liberal Track: ".implode(', ', $this->r->liberalTrack));
    if($this->r->electionTrack > 0) $this->r->mChan("Failed Elections: ".{$this->r->electionTrack});
    $this->r->mChan($player->nick.', you are the President. Please !(d)iscard a policy option.');
    $cards = array();
    $cards[] = $this->r->deck->draw();
    $cards[] = $this->r->deck->draw();
    $cards[] = $this->r->deck->draw();
    shuffle($cards);
    $this->r->policies = array('A' => $cards[0], 'B' => $cards[1], 'C' => $cards[2]);
    $this->r->nUser($player->nick, "Policies: A. {$this->r->policies['A']} B. {$this->r->policies['B']} C. {$this->r->policies['C']}");
  }
  function cmdd($from, $args) {
    $this->cmddiscard($from, $args);
  }
  function cmddiscard($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, '!(d)iscard a policy option'))) return;
    if(!($this->r->checkArgs($from, $args, 1))) return;
    $player = $this->r->president;
    $discard = strtoupper($args[0]);
    if(!(isset($this->r->policies[$discard]))) {
      $this->r->mChan($from.": Please !(d)iscard a valid policy.");
      return;
    }
    $this->r->deck->discard($this->r->policies[$discard]);
    unset($this->r->policies[$discard]);
    $this->r->phases['chancellorPolicy']->veto = true;
    $this->r->setPhase('chancellorPolicy');
  }
}
?>
