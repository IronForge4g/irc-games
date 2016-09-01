<?php
class phaseCodenamesGuess {
  var $r;
  var $desc;

  var $guesses;
  var $count;
  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Guessing Clues';
  }
  function init() {
    $this->guesses = 0;
    $remain = $this->count + 1 - $this->guesses;
    if($this->count == 0 || $this->count > 24) $remaining = 'unlimited guesses';
    else if($remain == 1) $remaining = '1 guess';
    else $remaining = $remain.' guesses';
    $this->r->mChan($this->r->colorText(ucfirst($this->r->turn), $this->r->turn)." team (".$this->r->team($this->r->turn, false)."), you're up. Please !(g)uess a codename. You have {$remaining} remaining.");
  }
  function cmdg($from, $args) {
    $this->cmdguess($from, $args);
  }
  function cmdguess($from, $args) {
    $player = $this->r->findPlayer($from);
    if($player == null) return;
    if($player->spymaster) {
      $this->r->mChan("Sorry ".$player->cNick().", you're a spymaster, stop cheating.");
      return;
    }
    if($player->color != $this->r->turn) {
      $this->r->mChan("Sorry ".$player->cNick().", it's not currently your turn to guess.");
      return;
    }
    $guessWord = strtolower(implode(' ', $args));
    $code = null;
    foreach($this->r->words as $word) {
      if($word->revealed) continue;
      if($guessWord == strtolower($word->code)) {
        $code = $word;
        break;
      }
    }
    if($code == null) {
      $this->r->mChan($player->cNick().", ".implode(' ', $args)." is not a valid codename.");
      return;
    }
    $code->revealed = true;
    if($code->color == 'orange') {
      $win = $this->r->turn == 'green' ? 'Pink' : 'Green';
      $win = $this->r->colorText($win, strtolower($win));
      $this->r->mChan($player->cNick()." has revealed the assassin! The {$win} team wins!");
      $this->r->revealWords();
      $this->r->setPhase('nogame');
      return;
    }
    else if($code->color == $this->r->turn) {
      $this->r->mChan($player->cNick()." has successfully contacted ".$code->cWord()); 
      $this->guesses++;
      $win = $this->checkWin();
      if($win == $this->r->turn) {
        $win = $this->r->colorText(ucfirst($win), $win);
        $this->r->mChan($win." has revealed all their spies, and won the game!");
        $this->r->revealWords();
        $this->r->setPhase('nogame');
        return;
      }
      $remain = $this->count + 1 - $this->guesses;
      if($this->count == 0 || $this->count > 24) $remaining = 'unlimited guesses';
      else if($remain == 1) $remaining = '1 guess';
      else if($remain == 0) {
        $this->r->setPhase('spymaster');
        return;
      }
      else $remaining = $remain.' guesses';
      $this->r->mChan($this->r->colorText(ucfirst($this->r->turn), $this->r->turn)." team (".$this->r->team($this->r->turn, false)."). Please !(g)uess another codename, or !(s)top your turn. You have {$remaining} remaining.");
    }
    else {
      $this->r->mChan($player->cNick()." incorrectly contacted ".$code->cWord()); 
      $win = $this->checkWin();
      if($win != 'none') {
        $win = $this->r->colorText(ucfirst($win), $win);
        $this->r->mChan($win." has revealed all their spies, and won the game!");
        $this->r->revealWords();
        $this->r->setPhase('nogame');
        return;
      }
      $this->r->setPhase('spymaster');
      return;
    }
  }
  function cmds($from, $args) {
    $this->cmdstop($from, $args);
  }
  function cmdstop($from, $args) {
    $player = $this->r->findPlayer($from);
    if($player == null) return;
    if($player->spymaster) {
      $this->r->mChan("Sorry ".$player->cNick().", you're a spymaster, stop cheating.");
      return;
    }
    if($player->color != $this->r->turn) {
      $this->r->mChan("Sorry ".$player->cNick().", it's not currently your turn.");
      return;
    }
    if($this->guesses == 0) {
      $this->r->mChan($player->cNick().", you must give at least one guess.");
      return;
    }
    $this->r->setPhase('spymaster');
  }
  function checkWin() {
    $wins = array('green' => true, 'pink' => true, 'none' => true, 'orange' => true);
    foreach($this->r->words as $word) {
      if(!($word->revealed)) $wins[$word->color] = false;
    }
    if($wins['green'] == true) return 'green';
    else if($wins['pink'] == true) return 'pink';
    return 'none';
  }
}
?>
