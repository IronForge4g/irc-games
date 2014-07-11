<?php
class phaseCamelUpLeg {
  var $r;
  var $desc;

  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Racing';
  }
  function init() {
    if(count($this->r->dice) == 0) {
      $this->r->setPhase('endleg');
      return;
    }
    $this->r->mChan($this->r->currentPlayer->nick.", you're up. Feel free to !help for options.");
  }
  function cmdhelp($from, $args) {
    $this->r->nUser($from, "!bet <color> - Bet on a camel to be ahead at the end of this leg.");
    $this->r->nUser($from, "!mirage <position> - Move your mirage tile to <position>.");
    $this->r->nUser($from, "!oasis <position> - Move your oasis tile to <position>.");
    $this->r->nUser($from, "!pyramid - Takes a dollar, and reveals a die from the pyramid to move a camel.");
    $this->r->nUser($from, "!win <letter> - Bets on a camel to win the overall race.");
    $this->r->nUser($from, "!lose <letter> - Bets on a camel to lose the overall race.");
    $this->r->nUser($from, "!hand - Shows you which camels are which letters for !win and !lose.");
    $this->r->nUser($from, "-- Each of these options can be shortformed to just the first letter. For example you can !b <color> to !bet a color. Colors may also be shortened to just the first letter if you wish.");
    $this->r->nUser($from, "!board -- Will show the current board. (Cannot be shortened.)");
    $this->r->nUser($from, "!players -- Will show the current players. (Cannot be shortened.)");
  }
  function cmdb($from, $args) {
    $this->cmdbet($from, $args);
  }
  function cmdbet($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, 'bet'))) return;
    if(!($this->r->checkArgs($from, $args, 1))) return;
    $color = $this->r->findColor($args[0]);
    if($color == null) {
      $this->r->mChan("$from: Please specify a valid color.");
      return;
    }
    if(count($this->r->legBets[$color]) == 0) {
      $this->r->mChan("$from: All $color bets have been claimed.");
      return;
    }
    $player = $this->r->currentPlayer;
    $bet = array_shift($this->r->legBets[$color]);
    $player->bets[] = array($color, $bet);
    $this->r->mChan("$from has claimed the ".$this->r->colorText("$bet of $color", $color).'.');
    $this->r->betsDisplay();
    $this->r->currentPlayer = $this->r->currentPlayer->left;
    $this->r->setPhase('leg');
  }
  function cmdm($from, $args) {
    $this->cmdmirage($from, $args);
  }
  function cmdmirage($from, $args) {
    $this->moveDesertTile($from, $args, 'Mirage');
  }
  function cmdo($from, $args) {
    $this->cmdoasis($from, $args);
  }
  function cmdoasis($from, $args) {
    $this->moveDesertTile($from, $args, 'Oasis');
  }
  function moveDesertTile($from, $args, $tile) {
    if(!($this->r->checkCurrentPlayer($from, "move your $tile"))) return;
    if(!($this->r->checkArgs($from, $args, 1))) return;
    $valids = array();
    for($i=2;$i<=16;$i++) $valids[$i] = $i;
    foreach($this->r->camels as $color => $camel) {
      if(isset($valids[$camel->position])) unset($valids[$camel->position]);
    }
    foreach($this->r->players as $nick => $player) {
      if($player == $this->r->currentPlayer) continue;
      if($player->desertTile != null) {
        $ldt = $player->desertTile->position - 1;
        $rdt = $player->desertTile->position + 1;
        for($dt=$ldt;$dt<=$rdt;$dt++) {
          if(isset($valids[$dt])) unset($valids[$dt]);
        }
      }
    }
    $pos = $args[0];
    if(!(isset($valids[$pos]))) {
      $this->r->mChan("$from: Sorry but $pos is not a valid position to move your $tile.");
      return;
    }
    $player = $this->r->currentPlayer;
    if($player->desertTile == null) $player->desertTile = new camelUpDesertTile();
    $player->desertTile->position = $pos;
    $player->desertTile->type = $tile;
    $this->r->mChan("$from has moved their $tile to $pos.");
    $this->r->currentPlayer = $this->r->currentPlayer->left;
    $this->r->setPhase('leg');  
  }
  function cmdw($from, $args) {
    $this->cmdwin($from, $args);
  }
  function cmdwin($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, "bet to win"))) return;
    if(!($this->r->checkArgs($from, $args, 1))) return;
    $card = strtoupper($args[0]);
    $player = $this->r->currentPlayer;
    if(!(isset($player->hand[$card]))) {
      $this->r->mChan("$from: Please specify a valid card from your !hand.");
      return;
    }
    $this->r->winDeck[] = array($player, $player->hand[$card]);
    unset($player->hand[$card]);
    $this->r->mChan("$from has bet a camel to win.");
    $this->r->currentPlayer = $this->r->currentPlayer->left;
    $this->r->setPhase('leg');
  }
  function cmdl($from, $args) {
    $this->cmdlose($from, $args);
  }
  function cmdlose($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, "bet to lose"))) return;
    if(!($this->r->checkArgs($from, $args, 1))) return;
    $card = strtoupper($args[0]);
    $player = $this->r->currentPlayer;
    if(!(isset($player->hand[$card]))) {
      $this->r->mChan("$from: Please specify a valid card from your !hand.");
      return;
    }
    $this->r->loseDeck[] = array($player, $player->hand[$card]);
    unset($player->hand[$card]);
    $this->r->mChan("$from has bet a camel to lose.");
    $this->r->currentPlayer = $this->r->currentPlayer->left;
    $this->r->setPhase('leg');
  }
  function cmdh($from, $args) {
    $this->cmdhand($from, $args);
  }
  function cmdhand($from, $args) {
    $player = $this->r->findPlayer($from);
    if($player == null) return;
    $display = $this->r->colorText("Cards: ", 'White');
    $first = true;
    foreach($player->hand as $letter => $color) {
      if($first) {
        $display .= $this->r->colorText("$letter. $color", $color);
        $first = false;
      } else {
        $display .= $this->r->colorText(', ', 'White').$this->r->colorText("$letter. $color", $color);
      }
    }
    $this->r->nUser($from, $display);
  }
  function cmdp($from, $args) {
    $this->cmdpyramid($from, $args);
  }
  function cmdpyramid($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, "roll from the pyramid"))) return;
    $color = array_shift($this->r->dice);
    $roll = mt_rand(1, 3);
    $this->r->rolledDice[$color] = $roll;
    $camel = $this->r->camels[$color];
    if($camel->below != null) $camel->below->above = null;
    $newPosition = $camel->position + $roll;
    $hitText = '';
    foreach($this->r->players as $nick => $player) { 
      if($player->desertTile != null) {
        if($player->desertTile->position == $newPosition) {
          if($player->desertTile->type == 'Oasis') {
            $newPosition++;
            $hitText = " after hitting {$player->nick}'s Oasis";
            $player->money++;
          }
          else if($player->desertTile->type == 'Mirage') {
            $newPosition--;
            $hitText = " after hitting {$player->nick}'s Mirage";
            $player->money++;
          }
          break;
        }
      }
    }
    $top = $this->r->topCamel($newPosition);
    $camel->position = $newPosition;
    $camel->below = $top;
    if($top != null) $top->above = $camel;
    $tCamel = $camel->above;
    while($tCamel != null) {
      $tCamel->position = $newPosition;
      $tCamel = $tCamel->above;
    }
    $this->r->mChan("$from has rolled a $roll, moving the ".$this->r->colorText("$color camel", $color)." to {$newPosition}{$hitText}.");
    $this->r->currentPlayer->money++;
    $this->r->board();
    if($newPosition > 16) {
      $this->r->gameEnd = true;
      $this->r->setPhase('endleg');
      return;
    }
    $this->r->currentPlayer = $this->r->currentPlayer->left;
    $this->r->setPhase('leg');
  }
}
?>
