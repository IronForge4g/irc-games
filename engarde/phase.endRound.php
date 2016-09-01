<?php
class phaseEnGardeEndRound {
  var $r;
  var $desc;

  var $attack;
  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Ending Round';
  }
  function init() {
    $this->r->mChan("The last card has been drawn.");
    $player = $this->r->currentPlayer;
    $opponent = $player->left;
    $distance = abs($player->position - $opponent->position);
    if($this->attack) {
      $attacks = array();
      foreach($this->r->players as $nick => $p) {
        if(!(isset($attacks[$nick]))) $attacks[$nick] = 0;
        foreach($p->hand as $card) if($card == $distance) $attacks[$nick]++;
      }
      if($attacks[$player->nick] > $attacks[$opponent->nick]) {
        $this->r->mChan("{$player->nick} manages a final attack on {$opponent->nick} for the point.");
        $player->score++;
        $this->r->setPhase('newRound');
        return;
      }
      else if($attacks[$player->nick] < $attacks[$opponent->nick]) {
        $this->r->mChan("{$opponent->nick} manages a final attack on {$player->nick} for the point.");
        $opponent->score++;
        $this->r->setPhase('newRound');
        return;
      }
    }
    if($player->distance() > $opponent->distance()) {
      $this->r->mChan("{$player->nick} travelled further than {$opponent->nick} for the point.");
      $player->score++;
      $this->r->setPhase('newRound');
      return;
    }
    else if($player->distance() < $opponent->distance()) {
      $this->r->mChan("{$opponent->nick} travelled further than {$player->nick} for the point.");
      $opponent->score++;
      $this->r->setPhase('newRound');
      return;
    }
    $this->r->mChan("The round ends in a draw.");
    $this->r->setPhase('newRound');
    return;
  }
}
?>
