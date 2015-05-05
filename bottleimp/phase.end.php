<?php
class phaseBottleImpEnd {
  var $r;
  var $desc;

  function __construct($root) {
    $this->r = $root;
    $this->desc = 'End Game';
  }
  function init() {
    $this->r->mChan("The hand is now over, scores:");
    $highScore = 0;
    foreach($this->r->players as $nick => $player) {
      if($player == $this->r->cursed) {
        $score = 0;
        foreach($this->r->impHand as $card) {
          $score += $card->points;
        }
        $player->score -= $score;
        $this->r->mChan("$nick was cursed, losing ".$this->r->points($score).", giving them {$player->score} / {$this->r->endScore}.");
      }
      else {
        $score = 0;
        foreach($player->tricks as $card) {
          $score += $card->points;
        }
        $player->score += $score;
        if($player->score > $highScore) $highScore = $player->score;
        $this->r->mChan("$nick earned ".$this->r->points($score).", giving them {$player->score} / {$this->r->endScore}.");
      }
    }
    if($highScore >= $this->r->endScore) {
      $this->r->mChan("The game has now ended. Final scores...");
      $this->r->score(false);
      $this->r->setPhase('nogame');
      return;
    }
    $this->r->setPhase('newRound');
  }
}
?>
