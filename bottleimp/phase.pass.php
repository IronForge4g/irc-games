<?php
class phaseBottleImpPass {
  var $r;
  var $desc;

  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Passing Cards';
  }
  function init() {
    foreach($this->r->players as $nick => $player) {
      $player->passLeft = null;
      $player->passRight = null;
      $player->passImp = null;
      $this->r->nUser($nick, "Please !pass a card to ".$player->left->nick." and ".$player->right->nick.", and !imp a card for the imps hand.");
      $this->r->nUser($nick, "Your Hand: ".$player->displayHand(true));
    }
    $this->r->mChan('Everyone, please choose your cards to !pass to other players and the !imp.');
  }
  function cmdp($from, $args) {
    $this->cmdpass($from, $args);
  }
  function cmdpass($from, $args) {
    if(!($this->r->checkArgs($from, $args, 2))) return;
    $tPlayer = $this->r->findPlayer($from);
    if($tPlayer == null) return;
    $target = $this->r->findPlayer($args[0]);
    $card = strtoupper($args[1]);
    if($target == null) {
      $target = $this->r->findPlayer($args[1]);
      $card = strtoupper($args[0]);
    }
    if($tPlayer->left != $target && $tPlayer->right != $target) {
      $this->r->mChan("$from: Please !pass a card to your left or right (".$tPlayer->left->nick." and ".$tPlayer->right->nick.")");
      return;
    }
    if(!(isset($tPlayer->hand[$card]))) {
      $this->r->mChan("$from: Please !pass a valid card to your left or right (".$tPlayer->left->nick." and ".$tPlayer->right->nick.")");
      return;
    }
    $direction = 'left';
    if($tPlayer->right == $target) $direction = 'right';
    $passDir = 'pass'.ucfirst($direction);
    if($tPlayer->$passDir != null) {
      $this->r->mChan("$from: You have already passed a card to your {$direction}.");
      return;
    }
    $tPlayer->$passDir = $tPlayer->hand[$card];
    unset($tPlayer->hand[$card]);
    $waiting = array();
    foreach($this->r->players as $nick => $player) {
      if($player->passLeft == null) $waiting[] = $nick;
      else if($player->passRight == null) $waiting[] = $nick;
      else if($player->passImp == null) $waiting[] = $nick;
    }
    if(count($waiting) == 0) {
      $this->r->mChan("$from has passed to {$target->nick}. The hand can now begin.");
      $this->r->setPhase('game');
      return;
    }
    $this->r->mChan("$from has passed to {$target->nick}. Still waiting on ".implode(', ', $waiting).'.');
  }
  function cmdi($from, $args) {
    $this->cmdimp($from, $args);
  }
  function cmdimp($from, $args) {
    if(!($this->r->checkArgs($from, $args, 1))) return;
    $tPlayer = $this->r->findPlayer($from);
    if($tPlayer == null) return;
    $card = strtoupper($args[0]);
    if($tPlayer->passImp != null) {
      $this->r->mChan("$from: You have already passed a card to the Imp.");
      return;
    }
    if(!(isset($tPlayer->hand[$card]))) {
      $this->r->mChan("$from: Please pass a valid card to the Imp.");
      return;
    }
    $tPlayer->passImp = $tPlayer->hand[$card];
    unset($tPlayer->hand[$card]);
    $waiting = array();
    foreach($this->r->players as $nick => $player) {
      if($player->passLeft == null) $waiting[] = $nick;
      else if($player->passRight == null) $waiting[] = $nick;
      else if($player->passImp == null) $waiting[] = $nick;
    }
    if(count($waiting) == 0) {
      $this->r->mChan("$from has passed to the Imp. The hand can now begin.");
      $this->r->setPhase('game');
      return;
    }
    $this->r->mChan("$from has passed to the Imp. Still waiting on ".implode(', ', $waiting).'.');
  }
}
?>
