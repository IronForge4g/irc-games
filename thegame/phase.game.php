<?php
class phaseTheGameGame {
  var $r;
  var $desc;

  var $minimum;
  var $played;
  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Game';
  }
  function init() {
    $player = $this->r->currentPlayer;
    $this->minimum = 2;
    $this->played = 0;
    $deckSize = $this->r->gameDeck->count();
    if($deckSize == 0) $this->minimum = 1;
    $validPlays = false;
    foreach($player->hand as $key => $card) {
      if(
        ($card > $this->r->piles['a'] || ($card == ($this->r->piles['a'] - 10))) ||
        ($card > $this->r->piles['b'] || ($card == ($this->r->piles['b'] - 10))) ||
        ($card < $this->r->piles['y'] || ($card == ($this->r->piles['y'] + 10))) ||
        ($card < $this->r->piles['z'] || ($card == ($this->r->piles['z'] + 10)))
      ) {
        $validPlays = true;
        break;
      }
    }
    if($validPlays) {
      if($this->r->solo) {
        $this->r->mChan("Your hand: ".implode(', ', $player->hand)." --- Piles: a. {$this->r->piles['a']} b. {$this->r->piles['b']} y. {$this->r->piles['y']} z. {$this->r->piles['z']}. The deck has ".$this->r->plural($deckSize, "card")." left. Please !(p)lay a card.");
      } else {
        $this->r->mChan("a. {$this->r->piles['a']} b. {$this->r->piles['b']} y. {$this->r->piles['y']} z. {$this->r->piles['z']}. {$player->nick}, you're up. The deck has ".$this->r->plural($deckSize, "card")." left. Please !(p)lay a card.");
        $this->r->nUser($player->nick, "Your hand: ".implode(', ', $player->hand)." --- Piles: a. {$this->r->piles['a']} b. {$this->r->piles['b']} y. {$this->r->piles['y']} z. {$this->r->piles['z']}.");
      }
    }
    else {
      if($this->r->solo) {
        $this->r->mChan("Your hand: ".implode(', ', $player->hand)." --- Piles: a. {$this->r->piles['a']} b. {$this->r->piles['b']} y. {$this->r->piles['y']} z. {$this->r->piles['z']}. The deck has ".$this->r->plural($deckSize, "card")." left, however you have no valid plays.");
      }
      else {
        $this->r->mChan("a. {$this->r->piles['a']} b. {$this->r->piles['b']} y. {$this->r->piles['y']} z. {$this->r->piles['z']}. {$player->nick}, you're up. The deck has ".$this->r->plural($deckSize, "card")." left, however you have no valid plays.");
        $this->r->nUser($player->nick, "Your hand: ".implode(', ', $player->hand)." --- Piles: a. {$this->r->piles['a']} b. {$this->r->piles['b']} y. {$this->r->piles['y']} z. {$this->r->piles['z']}.");
      }
      $this->r->setPhase('end');
    }
  }
  function cmdp($from, $args) {
    $this->cmdplay($from, $args);
  }
  function cmda($from, $args) {
    $args[] = 'a';
    $this->cmdplay($from, $args);
  }
  function cmdb($from, $args) {
    $args[] = 'b';
    $this->cmdplay($from, $args);
  }
  function cmdy($from, $args) {
    $args[] = 'y';
    $this->cmdplay($from, $args);
  }
  function cmdz($from, $args) {
    $args[] = 'z';
    $this->cmdplay($from, $args);
  }
  function cmdplay($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'play a card'))) return;
    if(!($this->r->checkArgs($from, $args, 2, 2))) return;
    $player = $this->r->currentPlayer;
    $card = $player->has($args[0]);
    if($card === false) {
      $card = $player->has($args[1]);
      if($card === false) {
        $this->r->mChan("$from, please play a valid card.");
        return;
      } else {
        $pile = strtolower($args[0]);
      }
    } else {
      $pile = strtolower($args[1]);
    }
    $cardValue = $player->hand[$card];
    if($pile == 'a' || $pile == 'b') {
      if($cardValue > $this->r->piles[$pile] || ($cardValue == ($this->r->piles[$pile] - 10))) {
        $this->r->piles[$pile] = $cardValue;
        unset($player->hand[$card]);
        $msg = "$from played the {$cardValue} on {$pile}.";
        $this->played++;
      } else {
        $msg = "$from, $cardValue is not a valid card for pile {$pile}.";
      }
    } else if ($pile == 'y' || $pile == 'z') {
      if($cardValue < $this->r->piles[$pile] || ($cardValue == ($this->r->piles[$pile] + 10))) {
        $this->r->piles[$pile] = $cardValue;
        unset($player->hand[$card]);
        $msg = "$from played the {$cardValue} on {$pile}.";
        $this->played++;
      } else {
        $msg = "$from, $cardValue is not a valid card for pile {$pile}.";
      }
    } else {
      $msg = "$from, please specify a valid pile.";
    }
    $msg .= " a. {$this->r->piles['a']} b. {$this->r->piles['b']} y. {$this->r->piles['y']} z. {$this->r->piles['z']}. ";
    $validPlays = true;
    if($this->played >= $this->minimum) $msg .= "{$player->nick}, please !(p)lay a card or !(e)nd your turn.";
    else {
      $validPlays = false;
      foreach($player->hand as $key => $card) {
        if(
          ($card > $this->r->piles['a'] || ($card == ($this->r->piles['a'] - 10))) ||
          ($card > $this->r->piles['b'] || ($card == ($this->r->piles['b'] - 10))) ||
          ($card < $this->r->piles['y'] || ($card == ($this->r->piles['y'] + 10))) ||
          ($card < $this->r->piles['z'] || ($card == ($this->r->piles['z'] + 10)))
        ) {
          $validPlays = true;
          break;
        }
      }
      if($validPlays) $msg .= "{$player->nick}, please !(p)lay a card.";
      else $msg .= "There are no valid plays left.";
    }
    if(!($this->r->solo)) $this->r->mChan($msg);
    if(!($validPlays)) {
      if($this->r->solo) $this->r->mChan($msg);
      $this->r->setPhase('end');
      return;
    } else {
      if($this->r->solo) {
        if(count($player->hand) > 0) $this->r->mChan("Your hand: ".implode(', ', $player->hand)." --- Piles: a. {$this->r->piles['a']} b. {$this->r->piles['b']} y. {$this->r->piles['y']} z. {$this->r->piles['z']}. $msg");
        else $this->r->mChan("Your hand is empty. --- Piles: a. {$this->r->piles['a']} b. {$this->r->piles['b']} y. {$this->r->piles['y']} z. {$this->r->piles['z']}. $msg");
      } else {
        if(count($player->hand) > 0) $this->r->nUser($player->nick, "Your hand: ".implode(', ', $player->hand)." --- Piles: a. {$this->r->piles['a']} b. {$this->r->piles['b']} y. {$this->r->piles['y']} z. {$this->r->piles['z']}.");
        else $this->r->nUser($player->nick, "Your hand is empty.");
      }
    }
  }
  function cmde($from, $args) {
    $this->cmdend($from, $args);
  }
  function cmdend($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'play a card'))) return;
    if($this->played < $this->minimum) {
      $this->r->mChan("$from, you must play at least ".$this->r->plural($this->minimum, "card")." before you can end your turn.");
      return;
    }
    $player = $this->r->currentPlayer;
    while($this->played > 0 && $this->r->gameDeck->count() > 0) {
      $player->hand[] = $this->r->gameDeck->draw();
      $this->played--;
    }
    if(count($player->hand) > 0) {
      sort($player->hand);
      if(!($this->r->solo)) $this->r->nUser($player->nick, "Your hand: ".implode(', ', $player->hand)." --- Piles: a. {$this->r->piles['a']} b. {$this->r->piles['b']} y. {$this->r->piles['y']} z. {$this->r->piles['z']}.");
    } else {
      if(!($this->r->solo)) $this->r->nUser($player->nick, "Your hand is empty.");
    }
    $nextPlayer = $player->left;
    while($nextPlayer != $player) {
      if(count($nextPlayer->hand) > 0) break;
      $nextPlayer = $nextPlayer->left;
    }
    if(count($nextPlayer->hand) > 0) {
      $this->r->currentPlayer = $nextPlayer;
      $this->r->setPhase("game");
    } else {
      $this->r->setPhase("end");
    }
  }
}
?>
