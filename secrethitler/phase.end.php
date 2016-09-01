<?php
class phaseTheGameEnd {
  var $r;
  var $desc;

  function __construct($root) {
    $this->r = $root;
    $this->desc = 'End Game';
  }
  function init() {
    $this->r->mChan("The Game is now over.");
    $score = $this->r->gameDeck->count();
    $this->r->mChan("The deck had ".$this->r->plural($score, "card"). " left.");
    foreach($this->r->players as $nick => $player) {
      $cards = count($player->hand);
      $this->r->mChan("$nick had ".$this->r->plural($cards, "card") ." left.");
      $score += $cards;
    }
    $this->r->mChan("The final score was: {$score}.");
    $this->r->setPhase('nogame');
  }
}
?>
