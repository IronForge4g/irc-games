<?php

class powwow implements pluginInterface {
  var $config;
  var $socket;

  var $started;
  var $channel;
  var $players;
  var $currentPlayer;
  var $cards;
  var $lastBid;
  var $lastBidder;
  var $drawDeck;
  var $discardDeck;

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
    $this->channel = '#PowWow';
    $this->players = array();
    $this->cards = array();
    $this->currentPlayer = '';
    $this->lastBid = 0;
    $this->lastBidder = '';
    $this->drawDeck = array('+1', '+1', '+1', '+1', '+2', '+2', '+2', '+2', '+3', '+3', '+3', '+3', '+4', '+4', '+4', '+4', '+5', '+5', '+5', '+5', '+10', '+10', '+10', '+15', '+15', '+20', '0', '0', '0', '-5', '-5', '-10', '?', '0*', 'Max=0', 'x2');
    $this->discardDeck = array();
    $this->resetDraw();
  }
  function discard($card) {
    $this->discardDeck[] = $card;
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
    else if($cmd == "!bid") $this->cmdBid($from, $tmp);
    else if($cmd == "!call") $this->cmdCall($from);
    else if($cmd == "!table") $this->cmdTable($from, true);
  }
  function onNick($from, $to) {
    if(isset($this->players[$from])) {
      $this->players[$to] = $this->players[$from];
      $this->cards[$to] = $this->cards[$from];
      unset($this->players[$from]);
      unset($this->cards[$from]);
      if($this->currentPlayer == $from) $this->currentPlayer = $to;
      if($this->lastBidder == $from) $this->lastBidder = $to;
    }
  }
  function onQuit($from) {
    if(isset($this->players[$from])) {
      $this->mChan("$from has left, and thus been eliminated!");
      $this->discard($this->cards[$from]);
      unset($this->players[$from]);
      unset($this->cards[$from]);
      if(count($this->players) == 1) {
        $winner = array_shift(array_keys($this->players));
        $this->mChan("Only $winner remains! They win!");
        $this->resetVars();
        $this->mChan("A new game can now begin. Please !join if you would like to play again.");
        return;
      }
      $this->mChan("Since $from has left, a new round will now begin.");
      $this->lastBid = 0;
      $this->lastBidder = '';
      $this->newTable();
      return;
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
    if(!(isset($tmp[1]))) continue;
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
  function resetDraw() {
    foreach($this->discardDeck as $card) {
      $this->drawDeck[] = $card;
    }
    $this->discardDeck = array();
    shuffle($this->drawDeck);
  }
  function nextCard() {
    if(count($this->drawDeck) == 0) {
      $this->mChan("The draw deck is empty, reshuffling discard pile into the draw deck.");
      $this->resetDraw();
    }
    $card = array_shift($this->drawDeck);
    return $card;
  }
  function tableValue() {
    $table = 0;
    $green = 0;
    $highest = 0;
    $qFound = false;
    $sFound = false;
    $mFound = false;
    $xFound = false;
    foreach($this->hands as $player => $card) {
      $this->mChan("$player had a $card");
      $val = 0;
      if($card == '+1') $val = 1;
      else if($card == '+2') $val = 2;
      else if($card == '+3') $val = 3;
      else if($card == '+4') $val = 4;
      else if($card == '+5') $val = 5;
      else if($card == '+10') $val = 10;
      else if($card == '+15') $val = 15;
      else if($card == '+20') $val = 20;
      else if($card == '-5') $val = -5;
      else if($card == '-10') $val = -10;
      else if($card == '?') $qFound = true;
      else if($card == '0*') $sFound = true;
      else if($card == 'Max=0') $mFound = true;
      else if($card == 'x2') $xFound = true;
      if($val > $highest) $highest = $val;
      $table += $val;
      if($val > 0) $green += $val;
      $discardList[] = $card;
    }
    if($qFound) {
      $card = $this->nextCard();
      $this->mChan("The ? was replaced with $card");
      if($card == '+1') $val = 1;
      else if($card == '+2') $val = 2;
      else if($card == '+3') $val = 3;
      else if($card == '+4') $val = 4;
      else if($card == '+5') $val = 5;
      else if($card == '+10') $val = 10;
      else if($card == '+15') $val = 15;
      else if($card == '+20') $val = 20;
      else if($card == '-5') $val = -5;
      else if($card == '-10') $val = -10;
      else if($card == '0*') $sFound = true;
      else if($card == 'Max=0') $mFound = true;
      else if($card == 'x2') $xFound = true;
      if($val > $highest) $highest = $val;
      $table += $val;
      if($val > 0) $green += $val;
      $discardList[] = $card;
    }
    if($mFound) {
      $green -= $highest;
      $table -= $highest;
    }
    if($xFound) {
      $table += $green;
    }
//    $this->mChan("The table value is $table!");
    foreach($discardList as $card) $this->discard($card);
    if($sFound) $this->resetDraw();
    return $table;
  }
  function lifeDisplay($life) {
    if($life == 1) return '()XX';
    else if($life == 2) return '()()X';
    else if($life == 3) return '()()()';
  }
  function newTable() {
    $table = array();
    foreach($this->players as $player => $life) {
      $this->hands[$player] = $this->nextCard();
      $table[] = "$player life: ".$this->lifeDisplay($life);
    }
    $this->mChan(implode(', ', $table).'.');
    foreach($this->players as $who => $life) {
      $this->cmdTable($who);
    }
    $this->mChan($this->currentPlayer." is up. Please make the opening bid.");
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
    $this->nUser($nick, "!rules - Show's the rules for Pow Wow.");
    $this->nUser($nick, "!start - Start a new game of Pow Wow.");
    $this->nUser($nick, "!join - Join a game.");
    $this->nUser($nick, "!table - Shows the current table state.");
    $this->nUser($nick, "!bid <number> - Places a bid of <number> as your bid. Eg. '!bid 5' places a bid of 5.");
    $this->nUser($nick, "!call - Calls the last bid made.");
  }
  function cmdRules($nick) {
    $this->nUser($nick, "Pow Wow is an IRC implementation of the game Pow Wow (coyote).");
    $this->nUser($nick, "When no game is in progress, anyone can !join a new game. Once there is at least 2 players who have joined, anyone in the game can !start it to begin.");
    $this->nUser($nick, "The goal of Pow Wow is to out bid or bluff your opponents. Each player will recieve one card they cannot see, and then can bid based on the cards showing.");
    $this->nUser($nick, "When you believe someone has bid higher than the table shows, you can !call, this will check if they bluffed or not. If they bluffed, they get a hit, if they were correct, you take a hit. 3 hits and you're eliminated.");
    $this->nUser($nick, "The game continues until only one player remains.");
    $this->nUser($nick, "There are 4 special cards in the game. In order of processing, '?' will be replaced with the top card from the draw deck. '0*' is a 0, but will cause the discard pile to be shuffled back in after the round. 'Max=0' changes the highest + card to a 0. 'x2' doubles the value of all + cards.");
  }
  function cmdTable($who, $cmd = false) {
    if($cmd) {
      if(!($this->started)) {
        $this->mChan("$nick: No game has started yet.");
        return;
      }
    }
    $table = array();
    foreach($this->hands as $player => $hand) {
      if($player == $who) continue;
      $table[] = "$player has $hand";
    }
    $this->nUser($who, implode(', ', $table).'.');
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
    if(count($this->players) < 2) {
      $players = implode(', ', array_keys($this->players));
      if($players == '') $players = '(none)';
      $this->mChan("A minimum of 2 players is required to start Pow Wow. Current players are: $players. Please use !join to join this game.");
      return;
    }

    $players = implode(', ', array_keys($this->players));
    $this->mChan("$nick has started the game! The turn order is: $players.");

    $players = array_keys($this->players);
    $this->currentPlayer = array_shift($players);
    $this->newTable();
    $this->started = true;
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
    $this->players[$nick] = 3;
    $this->hand[$nick] = '';
    $players = implode(', ', array_keys($this->players));
    $this->mChan("$nick has joined the game. Current players are now: $players");
  }
  function cmdBid($nick, $msg) {
    if(!($this->started)) {
      $this->mChan("$nick: No game has started yet.");
      return;
    }
    if($nick != $this->currentPlayer) {
      $this->mChan("$nick: Please wait your turn.");
      return;
    }
    if(count($msg) == 1) {
      $valid = preg_match('/^-?[0-9]+$/', $msg[0]) ? true : false;
      if($valid) {
        $number = $msg[0];
      } else {
        $this->mChan("$nick: $number is not a valid bid. Please make a valid bid in the format !bid <number> <dice>. (eg. !bid 5 d3)");
        return;
      }
    }
    else {
      $this->mChan("$nick: Please make a valid bid in the format !bid <number>. (eg. !bid 5)");
      return;
    }
    $checkNumber = preg_replace("#[^0-9]+#", "", $number);
    if($number <= $this->lastBid && $this->lastBidder != '') {
      $this->mChan("$nick: Your bid ($number) must be higher than the last bid of ".$this->lastBid.".");
      return;
    }
    $this->lastBid = $number;
    $this->lastBidder = $nick;
    $this->nextPlayer();
    $this->mChan("$nick set the new high bid to ".$this->lastBid.". ".$this->currentPlayer." please bid or call.");
    $this->cmdTable($this->currentPlayer);
  }
  function cmdCall($nick) {
    if(!($this->started)) {
      $this->mChan("$nick: No game has started yet.");
      return;
    }
    if($nick != $this->currentPlayer) {
      $this->mChan("$nick: Please wait your turn.");
      return;
    }
    if($this->lastBidder == '') {
      $this->mChan("$nick: Sorry, the first bidder must make a bid.");
      return;
    }
    $this->mChan("$nick has called ".$this->lastBidder."'s bid!");
    $this->lastBid;
    $tableVal = $this->tableValue();
    if($tableVal >= $this->lastBid) {
      $this->mChan("The table value was actually $tableVal. ".$this->lastBidder." was correct. $nick takes a hit.");
      $loser = $nick;
    } else {
      $this->mChan("The table value was only $tableVal. Good call $nick. ".$this->lastBidder." takes a hit.");
      $loser = $this->lastBidder;
    }
    $this->players[$loser]--;
    if($this->players[$loser] == 0) {
      $this->mChan("$loser has been eliminated!");
      unset($this->players[$loser]);
      unset($this->hands[$loser]);
      if(count($this->players) == 1) {
        $winner = array_shift(array_keys($this->players));
        $this->mChan("Only $winner remains! They win!");
        $this->resetVars();
        $this->mChan("A new game can now begin. Please !join if you would like to play again.");
        return;
      }
    } 
    $this->nextPlayer();
    $this->lastBid = 0;
    $this->lastBidder = '';
    $this->newTable();
  }
  // Handle player idle (!boot command)
}
