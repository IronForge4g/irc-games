<?php
class phaseBibliosEnd {
  var $r;
  var $desc;

  function __construct($root) {
    $this->r = $root;
    $this->desc = 'End Game';
  }
  function init() {
    $colors = array('Red', 'Orange', 'Green', 'Blue', 'Purple');
    $this->r->mChan("The game has now ended.");
    $diceDisplay = array();
    foreach($colors as $color) {
      $diceDisplay[] = "$color: ".$this->r->dice[$color];
    }
    $this->r->mChan("The final dice were: ".implode(', ', $diceDisplay).".");
    
    $counts = array();
    foreach($this->r->players as $player) {
      $counts[$player->nick] = $player->displayColors();
    }
    $winners = array();
    $scores = array();
    foreach($colors as $color) {
      $bestScore = 0;
      $bestPlayer = '';
      foreach($counts as $nick => $count) {
        if(isset($count[$color])) {
          if($count[$color] > $bestScore) {
            $bestScore = $count[$color];
            $bestPlayer = $nick;
          }
          else if($count[$color] == $bestScore && $bestScore > 0) {
            $cPlayer = $this->r->findPlayer($bestPlayer);
            $nPlayer = $this->r->findPlayer($nick);
            $cLetter = $cPlayer->bestLetter($color);
            $nLetter = $nPlayer->bestLetter($color);
            if($nLetter < $cLetter) $bestPlayer = $nick;
          }
        }
      }
      if(!(isset($winners[$bestPlayer]))) {
        $winners[$bestPlayer] = array();
        $scores[$bestPlayer] = 0;
      }
      $winners[$bestPlayer][] = $color;
      $scores[$bestPlayer] += $this->r->dice[$color];
    }
    arsort($scores);
    foreach($scores as $nick => $score) {
      if($nick == '') $this->r->mChan("Nobody won: ".implode(', ', $winners[$nick]).".");
      else $this->r->mChan("$nick scored $score by winning: ".implode(', ', $winners[$nick]).".");
    }
    $this->r->setPhase('nogame');
    return;
  }
}
?>
