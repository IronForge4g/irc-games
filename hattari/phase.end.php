<?php
class phaseHattariEnd {
  var $r;
  var $desc;

  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Murder Revealed';
  }
  function init() {
    $this->r->board(true);
    $lowest = 10;
    $lowestSuspect = '';
    $highest = -1;
    $highestSuspect = '';
    $win = 'highest';
    foreach($this->r->suspects as $letter => $number) {
      if($number == 'X') continue;
      if($number < $lowest) {
        $lowest = $number;
        $lowestSuspect = $letter;
      }
      if($number > $highest) {
        $highest = $number;
        $highestSuspect = $letter;
      }
      if($number == 5) $win = 'lowest';
    }
    if($win == 'highest') $murderer = $highestSuspect;
    else $murderer = $lowestSuspect;
    foreach($this->r->accused[$murderer] as $nick) {
      $player = $this->r->findPlayer($nick);
      $this->r->mChan("$nick was correct in accusing $murderer, getting their token back.");
      $player->chips++;
    }
    foreach($this->r->suspects as $letter => $suspect) {
      if($letter == $murderer) continue;
      $wrong = count($this->r->accused[$letter]);
      if($wrong > 0) {
        $lastPlayer = array_pop($this->r->accused[$letter]);
        $player = $this->r->findPlayer($lastPlayer);
        $this->r->mChan("$lastPlayer was the last person to incorrectly accuse $letter, gaining $wrong black marks.");
        $player->failed += $wrong;
      }
    }
    $gameOver = false;
    $scores = array();
    foreach($this->r->players as $nick => $player) {
      $score = $player->chips + $player->failed;
      $scores[$nick] = $score;
      if($score >= 8) $gameOver = true;
      else if($player->chips == 0) $gameOver = true;
    }
    if($gameOver) {
      asort($scores);
      $display = array();
      foreach($scores as $nick => $score) $display[] = "$nick ($score)";
      $this->r->mChan("The game has ended, final scores: ".implode(', ', $display).".");
      $this->r->setPhase('nogame');
    } else {
    $this->r->currentPlayer = $this->r->currentPlayer->left;
    $this->r->setPhase('first');
    }
  }
}
?>

