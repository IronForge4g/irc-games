<?php
class phaseFauxCureEnd {
  var $r;
  var $desc;

  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Bring out your dead';
  }
  var $cures;
  function init() {
    $this->r->currentPlayer = $this->r->currentPlayer->right;
    $dead = array();
    foreach($this->r->players as $nick => $player) {
      if($player->poison >= 3) {
        $this->r->mChan("$nick has been killed off.");
        $dead[] = $nick;
      } else {
        $player->gift = array();
        $player->keep = array();
        $player->returned = false;
      }
    }
    foreach($dead as $nick) {
      $this->r->players[$nick]->left->right = $this->r->players[$nick]->right;
      $this->r->players[$nick]->right->left = $this->r->players[$nick]->left;
      if($this->r->players[$nick] == $this->r->currentPlayer) {
        $this->r->currentPlayer = $this->r->currentPlayer->right;
      }
      unset($this->r->players[$nick]);
      unset($this->r->playerMap[strtolower($nick)]);
    }
    $playerCount = count($this->r->players);
    if($playerCount == 0) {
      $this->r->mChan("Everyone is dead. You all lose.");
      $this->r->setPhase('nogame');
      return;
    } else if($playerCount == 1) {
      $this->r->mChan("Only {$this->r->currentPlayer->nick} remains. They win!");
      $this->r->setPhase('nogame');
      return;
    }
    $this->r->setPhase('round1');
  }
}
?>
