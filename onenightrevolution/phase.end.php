<?php
class phaseOneNightRevolutionEnd {
  var $r;
  var $desc;

  function __construct($root) {
    $this->r = $root;
    $this->desc = 'End Game';
  }
  function init() {
    $votes = array();
    $max = 0;
    $informantCount = 0;
    foreach($this->r->players as $nick => $player) {
      $vote = $player->vote->nick;
      $this->r->mChan("$nick was on the {$player->team} side, and voted for {$vote}.");
      if(!(isset($votes[$vote]))) $votes[$vote] = 0;
      $votes[$vote]++;
      if($votes[$vote] > $max) $max = $votes[$vote];
      if($player->team == 'Informant') $informantCount++;
    }
    if($max < 2)  {
      if($informantCount == 0) {
        $this->r->mChan("No one recieved 2 votes, no one is eliminated. Since there were no Informants, the Rebels win!");
      } else {
        $this->r->mChan("No one recieved 2 votes, no one is eliminated, the Informants win!");
      }
    } else {
      $informantCount = 0;
      foreach($votes as $nick => $votes) {
        if($votes == $max) {
          $this->r->mChan("$nick has been eliminated.");
          if($this->r->players[$nick]->team == 'Informant') $informantCount++;
        }
      }
      if($informantCount == 0) {
        $this->r->mChan("No Informants were eliminated, the Informants win!");
      } else {
        $this->r->mChan($this->r->plural($informantCount, 'Informant').' '.$this->r->pluralWord($informantCount, 'was', 'were')." eliminated, the Rebels win!");
      }
    }
    $this->r->setPhase('nogame');
  }
}
?>
