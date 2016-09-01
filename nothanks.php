<?php

class nothanks implements pluginInterface {
  var $config;
  var $socket;

  var $started;
  var $channel;
  var $players;
  var $currentPlayer;
  var $currentCard;
  var $currentChips;
  var $hands;
  var $drawDeck;

  /**
  Called when plugins are loaded
  **/
  function init($config, $socket) {
    $this->config = $config;
    $this->socket = $socket;
    $this->resetVars();
  }
  function resetVars() {
    $this->started = false;
    $this->channel = '#PlayNoThanks';
    $this->players = array();
    $this->hands = array();
    $this->currentPlayer = '';
    $this->currentCard = '';
    $this->currentChips = 0;
    $this->drawDeck = array();
    for($i=3;$i<=35;$i++) $this->drawDeck[] = $i;
    shuffle($this->drawDeck);
    for($i=0;$i<9;$i++) array_pop($this->drawDeck);
  }

  /**
  Called about twice per second or when there are
  activity on the channel the bot are in.
  put your jobs that needs to be run without user interaction here
  **/
  function tick() {
  }

  /**
  Called when messages are posted on the channel
  the bot are in, or when somebody talks to it
  **/
  function onMessage($from, $channel, $msg) {
    if($channel != $this->channel) return;
    $tmp = explode(" ", $msg);
    $cmd = array_shift($tmp);
    if($cmd == "!help") $this->cmdHelp($from);
    else if($cmd == "!rules") $this->cmdRules($from);
    else if($cmd == "!start") $this->cmdStart($from, $tmp);
    else if($cmd == "!join") $this->cmdJoin($from);
    else if($cmd == "!table") $this->cmdTable($from);
    else if($cmd == "!pass") $this->cmdPass($from);
    else if($cmd == "!take") $this->cmdTake($from);
  }
  function onNick($from, $to) {
    if(isset($this->players[$from])) {
      $this->players[$to] = $this->players[$from];
      $this->hands[$to] = $this->hands[$from];
      unset($this->players[$from]);
      unset($this->hands[$from]);
      if($this->currentPlayer == $from) $this->currentPlayer = $to;
    }
  }
  function onQuit($from) {
    if(isset($this->players[$from])) {
      if($from == $this->currentPlayer) {
        $this->nextPlayer(); 
      }
      $this->mChan("$from has left, and thus been eliminated! The current player is ".$this->currentPlayer.".");
      unset($this->players[$from]);
      unset($this->hands[$from]);
      if(count($this->players) == 1) {
        $winner = array_shift(array_keys($this->players));
        $this->mChan("Only $winner remains! They win!");
        $this->resetVars();
        $this->mChan("A new game can now begin. Please !join if you would like to play again.");
        return;
      }
    } 
  }

  /**
  Called when the bot is shutting down
  **/
  function destroy() {
  }

  /**
  Called when the server sends data to the bot which is *not* a channel message, useful
  if you want to have a plugin do it`s own communication to the server.
  **/
  function onData($data) {
    $tmp = explode(" ", trim($data));
    $from = getNick($tmp[0]);
    if(!(isset($tmp[1]))) return;
    if($tmp[1] == 'NICK') $this->onNick($from, str_replace(":", "", $tmp[2]));
    else if($tmp[1] == 'PART' && trim(strtolower($this->channel)) == trim(strtolower($tmp[2]))) $this->onQuit($from);
    else if($tmp[1] == 'QUIT') $this->onQuit($from);
  }
  function mChan($message) {
    sendMessage($this->socket, $this->channel, $message);
  }
  function nUser($nick, $message) {
    sendNotice($this->socket, $nick, $message);
  }
  function nextPlayer() {
    $players = array_keys($this->players);
    $thisPlayer = -1;
    foreach($players as $num => $name) {
      if($this->currentPlayer == $name) {
        $thisPlayer = $num;
        break;
      }
    }
    $thisPlayer++;
    if(!(isset($players[$thisPlayer]))) $this->currentPlayer = $players[0];
    else $this->currentPlayer = $players[$thisPlayer];
  }
  function cmdHelp($nick) {
    $this->nUser($nick, "!rules - Show's the rules for No Thanks.");
    $this->nUser($nick, "!start - Start a new game of No Thanks.");
    $this->nUser($nick, "!join - Join a game.");
    $this->nUser($nick, "!table - Shows everything on the table.");
    $this->nUser($nick, "!pass - Places a chip (if you have one) on the current card.");
    $this->nUser($nick, "!take - Takes the current card, and any chips on it.");
  }
  function cmdRules($nick) {
    $this->nUser($nick, "No Thanks is an IRC implementation of the game No Thanks! (suprise!).");
    $this->nUser($nick, "When no game is in progress, anyone can !join a new game. Once there is at least 3 players who have joined, anyone in the game can !start it to begin.");
    $this->nUser($nick, "The goal of No Thanks is to push your luck and earn the lowest points possible. Each turn, you will either take the top card from the deck, or place a chip on it.");
    $this->nUser($nick, "Points are gained from the face value of the cards you collect, minus one point for every chip you have.");
    $this->nUser($nick, "If you have a run of cards in your hand however, you only score the lowest. So 29, 31 will score 60 points, but 29, 30, 31 will score 29 points.");
    $this->nUser($nick, "The game continues until the draw deck is empty.");
  }
  function cmdTable($who) {
    if(!($this->started)) {
      $this->mChan("$nick: No game has started yet.");
      return;
    }
    foreach($this->hands as $player => $hand) {
      $chipC = $this->players[$player];
      if($chipC == 1) $chipDesc = '1 chip';
      else if($chipC == 0) $chipDesc = 'no chips';
      else $chipDesc = $chipC.' chips';
      $this->mChan("$player has ".implode(", ", $hand)." with ".$chipDesc.".");
    }
  }
  function cmdStart($nick, $optionReq) {
    if($this->started) {
      $this->mChan("$nick: Sorry, a game is already in progress. Please wait till it finishes to begin a new one.");
      return;
    }
    if(!(isset($this->players[$nick]))) {
      $this->mChan("$nick: You must be in the current game to start it.");
      return;
    }
    if(count($this->players) < 3) {
      $players = implode(', ', array_keys($this->players));
      if($players == '') $players = '(none)';
      $this->mChan("A minimum of 3 players is required to start No Thanks. Current players are: $players. Please use !join to join this game.");
      return;
    }

    $players = implode(', ', array_keys($this->players));
    $this->mChan("$nick has started the game! The turn order is: $players.");

    $players = array_keys($this->players);
    $this->currentPlayer = array_shift($players);
    $this->started = true;
    $this->currentCard = array_shift($this->drawDeck);
    $this->mChan("Deck: ".count($this->drawDeck)." Card: {bold}** ".$this->currentCard." **{reset} Chips: ".$this->currentChips);
    $this->mChan($this->currentPlayer.", you're up. Your hand is: empty. You have 11 chips left. Please pass or take the ".$this->currentCard.".");
  }
  function cmdJoin($nick) {
    if($this->started) {
      $this->mChan("$nick: Sorry, a game is already in progress. Please wait till it finishes to begin a new one.");
      return;
    }
    if(isset($this->players[$nick])) {
      $this->mChan("$nick: You have already joined the current game.");
      return;
    }
    $this->players[$nick] = 11;
    $this->hands[$nick] = array();
    $players = implode(', ', array_keys($this->players));
    $this->mChan("$nick has joined the game. Current players are now: $players");
  }
  function cmdPass($nick) {
    if(!($this->started)) {
      $this->mChan("$nick: No game has started yet.");
      return;
    }
    if($nick != $this->currentPlayer) {
      $this->mChan("$nick: Please wait your turn.");
      return;
    }
    if($this->players[$nick] == 0) {
      $this->mChan("$nick: Sorry, you are all out of chips. Passing is no longer an option.");
      return;
    }
    $this->currentChips++;
    $this->players[$nick]--;
    $this->nextPlayer();
    $this->mChan("$nick has passed. Deck: ".count($this->drawDeck)." Card: {bold}** ".$this->currentCard." **{reset} Chips: ".$this->currentChips);
    $chipC = $this->players[$this->currentPlayer];
    if($chipC == 1) $chipDesc = '1 chip';
    else if($chipC == 0) $chipDesc = 'no chips';
    else $chipDesc = $chipC.' chips';
    $yourHand = implode(", ", $this->hands[$this->currentPlayer]);
    if($yourHand == '') $yourHand = 'empty';
    $this->mChan($this->currentPlayer.", you're up. Your hand is: ".$yourHand.". You have ".$chipDesc." left. Please pass or take the ".$this->currentCard.".");
  }
  function cmdTake($nick) {
    if(!($this->started)) {
      $this->mChan("$nick: No game has started yet.");
      return;
    }
    if($nick != $this->currentPlayer) {
      $this->mChan("$nick: Please wait your turn.");
      return;
    }
    $tChips = $this->currentChips;
    $tCard = $this->currentCard;
    $this->players[$nick] += $this->currentChips;
    $this->currentChips = 0;
    $this->hands[$nick][] = $tCard;
    sort($this->hands[$nick]);
    if(count($this->drawDeck) == 0) {
      $this->mChan("The draw deck is empty, game over.");
      $winners = array();
      $bestScore = 10000;
      foreach($this->players as $player => $chipC) {
        $score = 0 - $chipC;
        foreach($this->hands[$player] as $card) {
          $needle = $card - 1;
          if(!(in_array($needle, $this->hands[$player]))) $score += $card;
        }
        if($score < $bestScore) {
          $winners = array($player);
          $bestScore = $score;
        } else if ($score == $bestScore) {
          $winners[] = $player;
        }
        if($chipC == 1) $chipDesc = '1 chip';
        else if($chipC == 0) $chipDesc = 'no chips';
        else $chipDesc = $chipC.' chips';
        $this->mChan("$player had ".implode(", ", $this->hands[$player])." with ".$chipDesc.". Score: ".$score.".");
      }
      if(count($winners) == 1) {
        $this->mChan($winners[0]. " has won the game, with a final score of ".$bestScore.". Congrats!");
      } else {
        $this->mChan("Tie game! ".implode(", ", $winners). " rejoice in their shared victory, with a final score of ".$bestScore.". Congrats!");
      }
      $this->resetVars();
      $this->mChan("A new game can now begin. Please !join if you would like to play again.");
      return;
    }
    $this->currentCard = array_shift($this->drawDeck);
    $claimedChips = '';
    if($tChips == 1) $claimedChips = ' (with 1 chip)';
    else if($tChips > 1) $claimedChips = ' (with '.$tChips.' chips)';
    $this->mChan("$nick claimed the ".$tCard.$claimedChips.". Deck: ".count($this->drawDeck)." Card: {bold}** ".$this->currentCard." **{reset} Chips: ".$this->currentChips);
    //$this->nextPlayer();
    $chipC = $this->players[$this->currentPlayer];
    if($chipC == 1) $chipDesc = '1 chip';
    else if($chipC == 0) $chipDesc = 'no chips';
    else $chipDesc = $chipC.' chips';
    $yourHand = implode(", ", $this->hands[$this->currentPlayer]);
    if($yourHand == '') $yourHand = 'empty';
    $this->mChan($this->currentPlayer.", you're up. Your hand is: ".$yourHand.". You have ".$chipDesc." left. Please pass or take the ".$this->currentCard.".");
  }
  // Handle player idle (!boot command)
}
