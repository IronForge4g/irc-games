<?php
class phaseDeadDropTrade {
  var $r;
  var $desc;

  var $card;
  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Trade Phase';
  }
  function init() {
    $this->r->mChan($this->r->activePlayer->nick.", ".$this->r->currentPlayer->nick." is trading information with you. Please choose a card to !trade.");
    $this->r->activePlayer->displayHand();
  }
  function cmdt($from, $args) {
    $this->cmdtrade($from, $args);
  }
  function cmdtrade($from, $args) {
    if(!($this->r->checkActivePlayer($from, 'trade info'))) return;
    if(!($this->r->checkArgs($from, $args, 1))) return;
    $player = $this->r->activePlayer;
    $card = strtoupper($args[0]);
    if(!(isset($player->hand[$card]))) {
      $this->r->mChan($from.": Please specify a valid card to trade.");
      return;
    }
    $trader = $this->r->currentPlayer;
    $given = $trader->hand[$this->card];
    $this->r->nUser($player->nick, "{$trader->nick} has given you a {$given}.");
    $this->r->nUser($trader->nick, "{$player->nick} has given you a {$player->hand[$card]}.");
    $trader->hand[$this->card] = $player->hand[$card];
    $player->hand[$card] = $given;
    $trader->displayHand();
    $player->displayHand();
    $this->r->setPhase('game');
  }
}
?>
