<?php
class phaseDeadDropGame {
  var $r;
  var $desc;

  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Game Phase';
  }
  function init() {
    while(true) {
      $this->r->currentPlayer = $this->r->currentPlayer->left;
      $this->r->activePlayer = $this->r->currentPlayer;
      if(count($this->r->currentPlayer->hand) > 0) break;
    }
    $this->r->mChan($this->r->currentPlayer->nick." is up. Please !trade information, !swap the stash, !sell secrets, or !grab the drop.");
    $this->r->currentPlayer->displayHand();
  }
  function cmdt($from, $args) {
    $this->cmdtrade($from, $args);
  }
  function cmds($from, $args) {
    if(count($args) == 2) $this->cmdswap($from, $args);
    else $this->cmdsell($from, $args);
  }
  function cmdg($from, $args) {
    $this->cmdgrab($from, $args);
  }
  function cmdtrade($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'trade information'))) return;
    if(!($this->r->checkArgs($from, $args, 2))) return;
    $player = $this->r->currentPlayer;
    $target = null;
    $card = null;
    $target = $this->r->findPlayer($args[0]);
    if($target == null) {
      $target = $this->r->findPlayer($args[1]);
      if($target == null) {
        $this->r->mChan($from.": Please specify a valid target to trade with.");
        return;
      }
      $card = strtoupper($args[0]);
      if(!(isset($player->hand[$card]))) {
        $this->r->mChan($from.": Please specify a valid card to trade.");
        return;
      }
    } else {
      $card = strtoupper($args[1]);
      if(!(isset($player->hand[$card]))) {
        $this->r->mChan($from.": Please specify a valid card to trade.");
        return;
      }
    }
    $this->r->activePlayer = $target;
    $this->r->phases['trade']->card = $card;
    $this->r->setPhase('trade');
  }
  function cmdswap($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'swap the stash'))) return;
    if(!($this->r->checkArgs($from, $args, 2))) return;
    $player = $this->r->currentPlayer;
    $target = null;
    $card = strtoupper($args[0]);
    if(!(isset($player->hand[$card]))) {
      $card = strtoupper($args[1]);
      if(!(isset($player->hand[$card]))) {
        $this->r->mChan($from.": Please specify a valid card to swap from your hand.");
        return;
      }
      $target = strtoupper($args[0]);
      if(!(isset($this->r->table[$target]))) {
        $this->r->mChan($from.": Please specify a valid card to swap from the stash.");
        return;
      }
    } else {
      $target = strtoupper($args[1]);
      if(!(isset($this->r->table[$target]))) {
        $this->r->mChan($from.": Please specify a valid card to swap from the stash.");
        return;
      }
    }
    $this->r->mChan($from." has swapped a {$player->hand[$card]} for a {$this->r->table[$target]} from the stash.");
    $swapped = $this->r->table[$target];
    $this->r->table[$target] = $player->hand[$card];
    $player->hand[$card] = $swapped;
    $this->r->cmdtable(null, null);
    $this->r->setPhase('game');
  }
  function cmdsell($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'sell secrets'))) return;
    if(!($this->r->checkArgs($from, $args, 3))) return;
    $player = $this->r->currentPlayer;
    $target = null;
    $card = null;
    $target = $this->r->findPlayer($args[0]);
    if($target == null) {
      $this->r->mChan($from.": Please specify a valid target to sell to.");
      return;
    }
    $card1 = strtoupper($args[1]);
    if(!(isset($player->hand[$card1]))) {
      $this->r->mChan($from.": Please specify two valid cards to sell.");
      return;
    }
    $card2 = strtoupper($args[2]);
    if(!(isset($player->hand[$card2]))) {
      $this->r->mChan($from.": Please specify two valid cards to sell.");
      return;
    }
    $sum = 0;
    $sum += $player->hand[$card1] == 5 ? 0 : $player->hand[$card1]; 
    $sum += $player->hand[$card2] == 5 ? 0 : $player->hand[$card2]; 
    $foundCard = null;
    foreach($target->hand as $letter => $tCard) {
      if($tCard == $sum) {
        $foundCard = $letter;
        break;
      }
    }
    if($foundCard == null) {
      $this->r->nUser($target->nick, $player->nick." has shown you {$player->hand[$card1]} and {$player->hand[$card2]}. You had no match for $sum.");
      $this->r->mChan($from." failed to sell secrets to {$target->nick}.");
    } else {
      $this->r->nUser($target->nick, $player->nick." has shown you a {$player->hand[$card1]} and a {$player->hand[$card2]}. You had a match with your $sum, and this has been sold for the {$player->hand[$card1]}.");
      $this->r->nUser($player->nick, $target->nick." has sold you $sum for your {$player->hand[$card1]}. Your {$player->hand[$card2]} has been returned to your hand.");
      $this->r->mChan($from." successfully sold secrets to {$target->nick}.");
      $sold = $target->hand[$foundCard];
      $target->hand[$foundCard] = $player->hand[$card1];
      $player->hand[$card1] = $sold;
      $target->displayHand();
      $player->displayHand();
    }
    $this->r->setPhase('game');
  }
  function cmdgrab($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'grab the drop'))) return;
    if(!($this->r->checkArgs($from, $args, 2))) return;
    $player = $this->r->currentPlayer;
    $card1 = strtoupper($args[0]);
    if(!(isset($player->hand[$card1]))) {
      $this->r->mChan($from.": Please specify two valid cards to sell.");
      return;
    }
    $card2 = strtoupper($args[1]);
    if(!(isset($player->hand[$card2]))) {
      $this->r->mChan($from.": Please specify two valid cards to sell.");
      return;
    }
    $sum = 0;
    $sum += $player->hand[$card1] == 5 ? 0 : $player->hand[$card1]; 
    $sum += $player->hand[$card2] == 5 ? 0 : $player->hand[$card2]; 
    if($this->r->chosenCard == $sum) {
      $this->r->mChan($from." has successfully grabbed the stash with {$player->hand[$card1]}+{$player->hand[$card2]}.");
      $player->score++;
      if($player->score >= 3) {
        $this->r->setPhase('end');
        return;
      }
      $this->r->setPhase('newRound');
      return;
    } else {
      $this->r->mChan($from." failed to grab the stash with {$player->hand[$card1]}+{$player->hand[$card2]}.");
      $letters = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
      $c = 25 - count($this->r->table);
      foreach($player->hand as $letter => $card) {
        $this->r->table[$letters[$c]] = $card;
        $c--;
      }
      $player->hand = array();
      $remaining = array();
      foreach($this->r->players as $nick => $p) {
        if(count($p->hand) > 0) $remaining[] = $p;
      }
      if(count($remaining) == 1) {
        $winner = $remaining[0];
        $this->r->mChan("{$winner->nick} is the only remaining spy. They grab the drop.");
        $winner->score++;
        if($winner->score >= 3) {
          $this->r->setPhase('end');
          return;
        }
        $this->r->setPhase('newRound');
        return;
      }
      $this->r->mChan("Adding all cards from $from's hand to the stash.");
      $this->r->cmdtable(null, null);
      $this->r->setPhase('game');
      return;
    }
  }
}
?>
