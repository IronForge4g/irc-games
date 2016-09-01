<?php
class phaseDeadMansChestGame {
  var $r;
  var $desc;

  var $currentBidder;
  var $currentBid;
  var $currentRoll;
  var $shaken;
  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Game';
    $this->>currentBidder = null;
    $this->currentBid = null;
  }
  function init() {
    $player = $this->r->currentPlayer;
    $this->shaken = false;
    if($this->currentBidder == null) {
      $this->currentRoll = $this->r->bids[mt_rand(0,20)];
      foreach($this->r->players as $nick => $playerInfo) {
        $gemCounts = "$nick: {$playerInfo->gems}";
      }
      $this->r->mChan("Gems Remaining: ".implode(', ', $gemCounts).".");
      $this->r->mChan($player->nick.", please make the opening !(b)id.");
      $this->r->nUser($player->nick, "The current roll in the Dead Mans Chest is: {$this->r->bids[$this->r->currentRoll]}.");
    } else if($this->shaken) {
      $this->r->mChan($player->nick.", you're up. The current bid is ** {$this->r->bids[$this->currentBid]} **. Please !(b)id higher, !(c)all the bluff, or !(s)hake the Dead Mans Chest before bidding.");
    } else {
      $this->r->mChan($player->nick.", you're up. The current bid is ** {$this->r->bids[$this->currentBid]} **. Now that you have shaken the Dead Mans Chest, please !(b)id higher.");
    }
  }
  function cmdb($from, $args) {
    $this->cmdbid($from, $args);
  }
  function cmdbid($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'make a bid'))) return;
    if(!($this->r->checkArgs($from, $args, 1))) return;
    $player = $this->r->currentPlayer;
    $bid = array_search($this->r->bids, $args[0]);
    if($bid === false) {
      $this->mChan($from.", please make a valid bid.");
      return;
    } else if($this->currentBid != null && $bid < $this->currentBid) {
      $this->mChan($from.", your bid of {$args[0]} must be higher than the current bid of {$this->r->bids[$this->currentBid]}.");
      return;
    }
    $this->currentBid = $bid;
    $this->currentBidder = $player;
    $this->r->mChan($from." has raised the bid to {$args[0]}.");
    $this->r->currentPlayer = $player->left;
    $this->setPhase('game');
  }
  function cmdc($from, $args) {
    $this->cmdcall($from, $args);
  }
  function cmdcall($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'call a bluff'))) return;
    $this->r->mChan($from." has called {");
  }
}
?>
