<?php
class phaseBibliosAuction {
  var $r;
  var $desc;

  var $card;
  var $passed;
  var $highBid;
  var $highBidder;

  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Auction Phase';
  }
  function init() {
    if($this->card == null) {
      if($this->r->startPlayer != null) {
        $this->r->mChan("Gift phase is over, let the auction begin...");
        $this->r->activePlayer = $this->r->startPlayer;
        $this->r->currentPlayer = $this->r->activePlayer->left;
        $this->r->startPlayer = null;
      } else {
        $this->r->activePlayer = $this->r->activePlayer->left;
        $this->r->currentPlayer = $this->r->activePlayer->left;
      }
      $this->passed = array();
      $this->highBid = 0;
      $this->highBidder = null;
      if($this->r->auction->count() == 0) {
        $this->r->setPhase('end');
        return;
      }
      $this->card = $this->r->auction->draw();
      $this->r->mChan($this->r->activePlayer->nick." has drawn ".$this->card->display().". {$this->r->currentPlayer->nick}, please !bid or !pass.");
      $this->r->currentPlayer->displayHand();
    } else {
      while(true) {
        $this->r->currentPlayer = $this->r->currentPlayer->left;
        if($this->r->currentPlayer == $this->highBidder) {
          $this->r->mChan($this->r->currentPlayer->nick." wins the auction for ".$this->card->display().". Please !pay for this card.");
          $this->r->phases['pay']->card = $this->card;
          $this->r->phases['pay']->cost = $this->highBid;
          $this->card = null;
          $this->r->setPhase('pay');
          return;
        }
        if(!(isset($this->passed[$this->r->currentPlayer->nick]))) break;
      }
      $this->r->mChan($this->r->currentPlayer->nick." you're up. Please !bid or !pass for ".$this->card->display().".");
      $this->r->currentPlayer->displayHand();
    }
  }
  function cmdb($from, $args) {
    $this->cmdbid($from, $args);
  }
  function cmdp($from, $args) {
    $this->cmdpass($from, $args);
  }
  function cmdbid($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'bid'))) return;
    if(!($this->r->checkArgs($from, $args, 1))) return;
    $player = $this->r->findPlayer($from);
    $bid = $args[0];
    $checkBid = preg_replace("#[^0-9]+#", "", $bid);
    if($checkBid != $bid) {
      $this->r->mChan("$from: $bid is not a valid bid. Please make a valid bid in the format !bid <number>. (eg. !bid 5)");
      return;
    }
    if($bid <= $this->highBid) {
      $this->r->mChan("$from: $bid must be higher than the current bid of {$this->highBid}.");
      return;
    }
    if($this->card->type == 'gold') {
      if($bid > $player->cards()) {
        $this->r->mChan("$from: You didn't mean to bid that, we all forgive you. Please try something else.");
        return;
      }
    } else {
      if($bid > $player->gold()) {
        $this->r->mChan("$from: You didn't mean to bid that, we all forgive you. Please try something else.");
        return;
      }
    }
    $this->highBid = $bid;
    $this->highBidder = $player;
    $this->r->setPhase('auction');
  }
  function cmdpass($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'pass'))) return;
    $this->r->mChan("$from has passed...");
    $this->passed[$from] = true;
    if(count($this->passed) == count($this->r->players)) {
      $this->r->mChan("Everyone has passed, discarding the card.");
      $this->r->auction->discard($this->card);
      $this->card = null;
    }
    $this->r->setPhase('auction');
  }
}
?>
