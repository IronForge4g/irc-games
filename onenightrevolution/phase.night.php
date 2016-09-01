<?php
class phaseOneNightRevolutionNight {
  var $r;
  var $desc;

  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Night';
  }
  function init() {
    if($this->r->currentPlayer->actionTaken) {
      $this->r->nUser($this->r->currentPlayer->nick, "Your are currently on the {$this->r->currentPlayer->team} side.");
      $this->r->mChan("Everyone opens their eyes for the daytime.");
      foreach($this->r->players as $nick => $player) {
        if($player->revealed) $this->r->mChan("$nick has been revealed as {$player->team}.");
      }
      for($i=0;$i<3;$i++) {
        if($this->r->tableCardsRevealed[$i]) $this->r->mChan("There is a table card revealed as a Rebel.");
      }
      $this->r->setPhase('claim');
      return;
    }
    $tPlayer = $this->r->currentPlayer;
    if($tPlayer->initialTeam == 'Informant' && $tPlayer->revealed)
      $this->r->nUser($tPlayer->nick, "You open your eyes, remembering you are on the Informant side, however you now see you are a revealed as a Rebel. You are a specialist in '{$tPlayer->specialist->name}'."); 
    else
      $this->r->nUser($tPlayer->nick, "You open your eyes, remembering you are on the {$tPlayer->initialTeam} side and you are a specialist in '{$tPlayer->specialist->name}'."); 
    foreach($this->r->players as $nick => $player) {
      if($player->revealed) $this->r->nUser($this->r->currentPlayer->nick, "$nick has been revealed as {$player->team}.");
    }
    for($i=0;$i<3;$i++) {
      if($this->r->tableCardsRevealed[$i]) $this->r->nUser($this->r->currentPlayer->nick, "There is a table card revealed as a Rebel.");
    }
    $this->r->mChan("{$tPlayer->nick} has begun their night phase.");
    $tPlayer->specialist->init();
  }
  function cmdcomplete($from, $args, $source) {
    if(!($this->r->checkCurrentPlayer($from, 'complete the night phase', $source))) return;
    $player = $this->r->currentPlayer;
    if(!($player->actionTaken)) {
      $this->r->nUser($from, "Please take your action before completing.");
      return;
    }
    $this->r->mChan("$from has completed their night phase.");
    $this->r->currentPlayer = $player->left;
    $this->r->setPhase('night');
  }
  function cmdview($from, $args, $source) {
    if(!($this->r->checkCurrentPlayer($from, 'view a card', $source))) return;
    $player = $this->r->currentPlayer;
    if($player->actionTaken) {
      $this->r->nUser($from, "You have already completed your action. Please announce you are !complete.");
      return;
    }
    if(method_exists($player->specialist, 'view')) {
      $player->specialist->view($from, $args, $source);
    } else {
      $this->r->mTarget($from, $source, "!view does not exist in the phase '{$this->phase->desc}'.");
    }
  }
  function cmdtap($from, $args, $source) {
    if(!($this->r->checkCurrentPlayer($from, 'tap a shoulder', $source))) return;
    $player = $this->r->currentPlayer;
    if($player->actionTaken) {
      $this->r->nUser($from, "You have already completed your action. Please announce you are !complete.");
      return;
    }
    if(method_exists($player->specialist, 'tap')) {
      $player->specialist->tap($from, $args, $source);
    } else {
      $this->r->mTarget($from, $source, "!tap does not exist in the phase '{$this->phase->desc}'.");
    }
  }
  function cmdswap($from, $args, $source) {
    if(!($this->r->checkCurrentPlayer($from, 'swap a card', $source))) return;
    $player = $this->r->currentPlayer;
    if($player->actionTaken) {
      $this->r->nUser($from, "You have already completed your action. Please announce you are !complete.");
      return;
    }
    if(method_exists($player->specialist, 'swap')) {
      $player->specialist->swap($from, $args, $source);
    } else {
      $this->r->mTarget($from, $source, "!swap does not exist in the phase '{$this->phase->desc}'.");
    }
  }
}
?>
