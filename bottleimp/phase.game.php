<?php
class phaseBottleImpGame {
  var $r;
  var $desc;

  var $suit;
  var $open;
  var $winning;
  var $winningCard;
  var $table;

  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Playing the round';
  }
  function init() {
    foreach($this->r->players as $nick => $player) {
      $player->left->hand[] = $player->passLeft;
      $player->right->hand[] = $player->passRight;
      $this->r->impHand[] = $player->passImp;
    }
    foreach($this->r->players as $nick => $player) {
      $newHand = array();
      foreach($player->hand as $let => $card) {
        $newHand[$card->value] = $card;
      }
      $player->hand = $newHand;
      $this->r->nUser($nick, "Your Hand: ".$player->displayHand());
    }
    $this->suit = null;
    $this->winning = null;
    $this->table = array();
    $this->open = $this->r->currentPlayer;
    $this->r->mChan($this->r->currentPlayer->nick.": Please !play a card to open the trick.");
  }
  function cmdp($from, $args) {
    $this->cmdplay($from, $args);
  }
  function cmdplay($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'play a card'))) return;
    if(!($this->r->checkArgs($from, $args, 1))) return;
    $player = $this->r->currentPlayer;
    $cardNum = $args[0];
    if(!(isset($player->hand[$cardNum]))) {
      $this->r->mChan($player->nick.": Please specify a valid card to play.");
      return;
    }
    $card = $player->hand[$cardNum];
    if($this->suit != null && $this->suit != $card->color && $player->hasSuit($this->suit)) {
      $this->r->mChan($player->nick.": You must follow suit.");
      return;
    }
    if($this->suit == null) $this->suit = $card->color;
    $this->table[] = $card;
    unset($player->hand[$cardNum]);
    $best = null;
    $newBottle = false;
    foreach($this->table as $tCard) {
      if($tCard->value < $this->r->bottle->value) {
        $newBottle = true;
        break;
      }
    }
    if($newBottle) {
      foreach($this->table as $tCard) {
        if($tCard->value < $this->r->bottle->value) {
          if($best == null) $best = $tCard;
          else {
            if($tCard->value > $best->value) $best = $tCard;
          }
        }
      }
    }
    else {
      foreach($this->table as $tCard) {
        if($best == null) $best = $tCard;
        else {
          if($tCard->value > $best->value) $best = $tCard;
        }
      }
    }
    if($best == $card) {
      $this->winning = $player;
      $this->winningCard = $card;
    }
    $this->r->currentPlayer = $player->left;
    $display = array();
    $points = 0;
    foreach($this->table as $tCard) {
      $display[] = $tCard->display;
      $points += $tCard->points;
    }
    $display = implode(', ', $display)." = ".$this->r->points($points);
    if($this->r->currentPlayer == $this->open) {
      $this->r->currentPlayer = $this->winning;
      foreach($this->table as $card) {
        $this->winning->tricks[] = $card;
      }
      $this->open = $this->r->currentPlayer;
      $count = count($this->open->hand);
      if($this->winningCard->value < $this->r->bottle->value) {
        $this->r->bottle = $this->winningCard;
        $this->r->cursed = $this->winning;
        if($count > 0) {
          $this->r->mChan("{$this->winning->nick} took the trick ({$display}) with the {$this->winningCard->display}, claiming the bottle in the process. {$this->r->currentPlayer->nick}, please !play a card to open the next trick.");
          $this->r->nUser($this->r->currentPlayer->nick, "Your Hand: ".$this->r->currentPlayer->displayHand());
        }
        else {
          $this->r->mChan("{$this->winning->nick} took the trick ({$display}) with the {$this->winningCard->display}, claiming the bottle in the process.");
          $this->r->setPhase("end");
          return;
        }
      } else {
        if($count > 0) {
          $this->r->mChan("{$this->winning->nick} took the trick ({$display}) with the {$this->winningCard->display}. {$this->r->currentPlayer->nick}, please !play a card to open the next trick.");
          $this->r->nUser($this->r->currentPlayer->nick, "Your Hand: ".$this->r->currentPlayer->displayHand());
        }
        else {          
          $this->r->mChan("{$this->winning->nick} took the trick ({$display}) with the {$this->winningCard->display}.");
          $this->r->setPhase("end");
          return;
        }
      }
      $this->suit = null;
      $this->winning = null;
      $this->winningCard = null;
      $this->table = array();
    }
    else {
      $this->r->mChan("{$this->winning->nick} is winning the trick ({$display}) with the {$this->winningCard->display}. {$this->r->currentPlayer->nick}, please !play a card.");
      $this->r->nUser($this->r->currentPlayer->nick, "Your Hand: ".$this->r->currentPlayer->displayHand());
    }
  }
}
?>
