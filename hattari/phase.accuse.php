<?php
class phaseHattariAccuse {
  var $r;
  var $desc;

  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Accuse Suspect';
  }
  function init() {
    if($this->r->firstPlayer != $this->r->currentPlayer) {
      $this->r->board();
      if($this->r->currentPlayer->right->accused == 'A')
        $this->r->nUser($this->r->currentPlayer->nick, "Suspect B reveals themselves to be {$this->r->suspects['B']}, and Suspect C reveals themselves to be {$this->r->suspects['C']}.");
      else if($this->r->currentPlayer->right->accused == 'B')
        $this->r->nUser($this->r->currentPlayer->nick, "Suspect A reveals themselves to be {$this->r->suspects['A']}, and Suspect C reveals themselves to be {$this->r->suspects['C']}.");
      else if($this->r->currentPlayer->right->accused == 'C')
        $this->r->nUser($this->r->currentPlayer->nick, "Suspect A reveals themselves to be {$this->r->suspects['A']}, and Suspect B reveals themselves to be {$this->r->suspects['B']}.");
    } else if($this->r->firstPlayer->accused != null) {
      $this->r->setPhase('end');
      return;
    }
    $this->r->mChan($this->r->currentPlayer->nick.": Please !accuse a suspect.");
  }
  function cmda($from, $args) {
    $this->cmdaccuse($from, $args);
  }
  function cmdaccuse($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'accuse'))) return;
    if(!($this->r->checkArgs($from, $args, 1, 1))) return;
    $suspect = $this->r->findSuspect($args[0]);
    if($suspect == null) {
      $this->r->mChan("$from: Please specify valid suspect to accuse.");
      return;
    }
    if($suspect == 'A') $this->r->accused['A'][] = $from;
    else if($suspect == 'B') $this->r->accused['B'][] = $from;
    else if($suspect == 'C') $this->r->accused['C'][] = $from;
    $this->r->currentPlayer->accused = $suspect;
    $this->r->currentPlayer->chips--;
    $this->r->currentPlayer = $this->r->currentPlayer->left;
    $this->r->setPhase('accuse');
  }
}
?>

