<?php

/**
  This is an example skeleton for a Vikingbot plugin
**/
class liarsDice implements pluginInterface {
  var $config;
  var $socket;

  var $started;
  var $channel;
  var $players;
  var $currentPlayer;
  var $dice;
  var $lastBid;
  var $lastBidder;
  var $diceVal;
  var $passes;
  var $lastPasser;
  var $totalDice;
  var $theRiver;

  var $optionPass;
  var $optionSpot;
  var $optionWild;
  var $optionRiver;

  /**
  Called when plugins are loaded
  **/
  function init($config, $socket) {
    $this->config = $config;
    $this->socket = $socket;
    $this->resetVars();
    $this->diceVal = array('d1' => 1, 'd2' => 2, 'd3' => 3, 'd4' => 4, 'd5' => 5, 'd6' => 6);
  }
  function resetVars() {
    $this->started = false;
    $this->channel = '#LiarsDice';
    $this->players = array();
    $this->currentPlayer = '';
    $this->dice = array();
    $this->lastBid = array(0, '');
    $this->lastBidder = '';
    $this->passes = array();
    $this->lastPasser = '';

    $this->optionPass = false;
    $this->optionSpot = false;
    $this->optionWild = false;
    $this->optionRiver = false;
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
    else if($cmd == "!spot") $this->cmdSpot($from);
    else if($cmd == "!pass") $this->cmdPass($from);
    else if($cmd == "!cups") $this->cmdCups();
  }
  function onNick($from, $to) {
    if(isset($this->players[$from])) {
      $this->players[$to] = $this->players[$from];
      $this->dice[$to] = $this->dice[$from];
      unset($this->players[$from]);
      unset($this->dice[$from]);
      unset($this->passes[$from]);
      if($this->currentPlayer == $from) $this->currentPlayer = $to;
      if($this->lastBidder == $from) $this->lastBidder = $to;
    }
  }
  function onQuit($from) {
    if(isset($this->players[$from])) {
      $this->mChan("$from has left, and thus been eliminated!");
      unset($this->players[$from]);
      unset($this->dice[$from]);
      unset($this->passes[$from]);
      if(count($this->players) == 1) {
        $winner = array_shift(array_keys($this->players));
        $this->mChan("Only $winner remains! They win!");
        $this->resetVars();
        $this->mChan("A new game can now begin. Please !join if you would like to play again.");
        return;
      }
      if($this->lastBidder == $from) {
        $this->mChan("Since $from was the last bidder, a new round will now begin.");
        $this->lastBid = array(0, '');
        $this->lastBidder = '';
        $this->newHand();
        return;
      }
      if($this->currentPlayer == $from) {
        $this->nextPlayer();
        $options = array('bid', 'call');
        if($this->optionSpot) $options[] = 'spot';
        if($this->optionPass) $options[] = 'pass';
        $lastOption = array_pop($options);
        $this->mChan($this->lastBidder." set the high bid to ".implode(" ", $this->lastBid).". ".$this->currentPlayer." please ".implode(", ", $options).", or $lastOption.");
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
  function generateRiver($dice) {
    $this->theRiver = array();
    for($n=0;$n<$dice;$n++) {
      $this->theRiver[] = 'd'.mt_rand(1,6);
    }
    sort($this->theRiver);
  }
  function generateHand($player, $dice) {
    $this->players[$player] = array();
    for($n=0;$n<$dice;$n++) {
      $this->players[$player][] = 'd'.mt_rand(1,6);
    }
    sort($this->players[$player]);
  }
  function newHand($init = false) {
    $cupSizes = array();
    $maxDice = 0;
    foreach($this->players as $player => $hand) {
      if($init) $this->dice[$player] = 5;
      $this->passes[$player] = false;
      $this->generateHand($player, $this->dice[$player]);
      if($this->dice[$player] > $maxDice) $maxDice = $this->dice[$player];
      $this->nUser($player, "Your cup: ".implode(" ", $this->players[$player]));
      $cupSizes[] = "$player has ".$this->dice[$player]." dice";
    }
    $this->mChan(implode(", ", $cupSizes).'.');
    if($this->optionRiver) { 
      $this->generateRiver($maxDice);
      $this->mChan("The River has: ".implode(" ", $this->theRiver));
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
    $this->nUser($nick, "!rules - Show's the rules for LIRC's Dice");
    $this->nUser($nick, "!start <option> <option> - Start a new game of LIRC's Dice. To start with options, add them after. Eg. '!start pass spot wild' to start a game with passing and spotting allowed, and 1's are wild.");
    $this->nUser($nick, "!join - Join a game");
    $this->nUser($nick, "!cups - Shows how many dice are in everyones cups.");
    $this->nUser($nick, "!bid <number> <dice> - Places a bid of <number> <dice> as your bid. Eg. '!bid 5 d3' places a bid of 5 dice with d3 showing on the table.");
    $this->nUser($nick, "!call - Calls the last bid made.");
    $this->nUser($nick, "!spot - <optional> The last bid was exactly correct.");
    $this->nUser($nick, "!pass - <optional><optional>  Skip raising the bid, with your bid being that all your dice have different faces showing. Each player may only pass once per round.");
  }
  function cmdRules($nick) {
    $this->nUser($nick, "LIRC's Dice is an IRC implementation of Liar's Dice");
    $this->nUser($nick, "When no game is in progress, anyone can !join a new game. Once there is at least 2 players who have joined, anyone in the game can !start it to begin.");
    $this->nUser($nick, "The goal of LIRC's Dice is to out bid or bluff your opponents. Each player will roll 5 dice (to begin with), and then can bid.");
    $this->nUser($nick, "The lowest bid is 1d1 (one dice, with one pip showing.) The next highest bid is 1d2 (one dice, with two pips showing.) This goes to 1d6, then is followed by 2d1.");
    $this->nUser($nick, "So more pips outweighs less pips of the same dice count, but more dice always outweighs any bid with less dice.");
    $this->nUser($nick, "When bidding, you are bidding on how many of those dice/face counts are on the entire table, not just under your cup.");
    $this->nUser($nick, "When you believe someone has bid higher than the table shows, you can !call, this will check if they bluffed or not. If they bluffed, they lose a die, if they were correct, you lose a die.");
    $this->nUser($nick, "Options:");
    $this->nUser($nick, "Spot Option - If you believe someone matched the dice on the table exactly, you can !spot. If they did match the table exactly, everyone elses loses a die, otherwise you lose a die.");
    $this->nUser($nick, "Pass Option - If all your dice show different pip counts (or you bluff to that extent), you may !pass, letting the next player raise the bid. Calling this will call the different pip counts, once a player passes it is no longer possible to call the high bid.");
    $this->nUser($nick, "Wild Option - Dice with 1 pip showing will count as wilds, and be applied to the current high bid.");
    $this->nUser($nick, "River Option - An open cup will be displayed and counted for all containing open dice. The player with the most dice left will determine the river size.");
    $this->nUser($nick, "The game continues until only one player has dice.");
  }
  function cmdCups() {
    $cupSizes = array();
    foreach($this->players as $player => $hand) {
      $cupSizes[] = "$player has ".$this->dice[$player]." dice";
    }
    $this->mChan(implode(", ", $cupSizes).'.');
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
      $this->mChan("A minimum of 2 players is required to start LIRC's Dice. Current players are: $players. Please use !join to join this game.");
      return;
    }
    $options = array();
    if(in_array('pass', $optionReq)) { 
      $this->optionPass = true;
      $options[] = 'Pass'; 
    }
    if(in_array('spot', $optionReq)) { 
      $this->optionSpot = true;
      $options[] = 'Spot';
    }
    if(in_array('wild', $optionReq)) {
      $this->optionWild = true;
      $options[] = 'Wild';
    }
    if(in_array('river', $optionReq)) {
      $this->optionRiver = true;
      $options[] = 'River';
    }

    $players = implode(', ', array_keys($this->players));
    $this->mChan("$nick has started the game! The turn order is: $players.");
    if(count($options) == 0) $this->mChan("No options have been enabled for this game. Only bidding is allowed.");
    else $this->mChan("Options for this game are: ".implode(', ', $options).".");
    $this->currentPlayer = array_shift(array_keys($this->players));
    $this->newHand(true);
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
    $this->players[$nick] = array();
    $this->dice[$nick] = 0;
    $this->passes[$nick] = false;
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
    $number = 0;
    $dice = 'd0';
    if(count($msg) == 1) {
      if(strlen($msg[0]) >= 3) {
        $number = substr($msg[0], 0, -2);
        $dice = substr($msg[0], -2);
      } else {
        $this->mChan("$nick: $number is not a valid bid. Please make a valid bid in the format !bid <number> <dice>. (eg. !bid 5 d3)");
        return;
      }
    }
    else if(count($msg) == 2) {
      list($number, $dice) = $msg;
    }
    else {
      $this->mChan("$nick: Please make a valid bid in the format !bid <number> <dice>. (eg. !bid 5 d3)");
      return;
    }
    $checkNumber = preg_replace("#[^0-9]+#", "", $number);
    if($checkNumber != $number) {
      $this->mChan("$nick: $number is not a valid bid. Please make a valid bid in the format !bid <number> <dice>. (eg. !bid 5 d3)");
      return;
    }
    if($dice != 'd1' && $dice != 'd2' && $dice != 'd3' && $dice != 'd4' && $dice != 'd5' && $dice != 'd6') {
      $this->mChan("$nick: $dice is not a valid die. Please make a valid bid in the format !bid <number> <dice>. (eg. !bid 5 d3)");
      return;
    }
    if($number <= 0 && $this->lastBid[0] == 0) {
      $this->mChan("$nick: Opening bids ($number $dice) must be 1 or more dice.");
      return;
    } 
    if($number < $this->lastBid[0]) {
      $this->mChan("$nick: Your bid ($number $dice) must be higher than the last bid of ".implode(" ", $this->lastBid).".");
      return;
    }
    else if($number == $this->lastBid[0] && $this->diceVal[$dice] <= $this->diceVal[$this->lastBid[1]]) {
      $this->mChan("$nick: Your bid ($number) must be higher than the last bid of ".implode(" ", $this->lastBid).".");
      return;
    }
    $this->lastBid = array($number, $dice);
    $this->lastBidder = $nick;
    $this->lastPasser = '';
    $this->nextPlayer();
    $options = array('bid', 'call');
    if($this->optionSpot) $options[] = 'spot';
    if($this->optionPass) $options[] = 'pass';
    $lastOption = array_pop($options);
    if($this->optionRiver) $this->mChan("$nick set the new high bid to ".implode(" ", $this->lastBid).". ".$this->currentPlayer." please ".implode(", ", $options).", or $lastOption. The River has: ".implode(" ", $this->theRiver));
    else $this->mChan("$nick set the new high bid to ".implode(" ", $this->lastBid).". ".$this->currentPlayer." please ".implode(", ", $options).", or $lastOption.");
    $this->nUser($this->currentPlayer, "Your cup: ".implode(" ", $this->players[$this->currentPlayer]));
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
    if($this->lastBidder == '' && $this->lastPasser == '') {
      $this->mChan("$nick: Sorry, the first bidder must make a bid.");
      return;
    }
    if($this->lastPasser != '') {
      $this->mChan("$nick has called ".$this->lastPasser."'s pass!");
      $validPass = false;
      if($this->optionRiver) $this->mChan("The River has: ".implode(" ", $this->theRiver));
      foreach($this->players as $player => $cup) {
        $this->mChan("$player's cup contains: ".implode(" ", $cup).".");
        if($player == $this->lastPasser) {
          $tmp = array_count_values($cup);
          if(count($tmp) == count($cup)) $validPass = true;
        }
      }
      if($validPass) {
        $this->mChan($this->lastPasser." had completely different dice! $nick loses a die!");
        $loser = $nick;
      } else {
        $this->mChan($this->lastPasser." had some matching dice, and loses a die! Good catch $nick.");
        $loser = $this->lastPasser;
      }
    }
    else {
      $this->mChan("$nick has called ".$this->lastBidder."'s bid!");
      list($bid, $dice) = $this->lastBid;
      $count = 0;
      $realCount = 0;
      if($this->optionRiver) {
        $this->mChan("The River has: ".implode(" ", $this->theRiver));
        $tmp = array_count_values($this->theRiver);
        $count += (isset($tmp[$dice]) ? $tmp[$dice] : 0);
        $realCount += (isset($tmp[$dice]) ? $tmp[$dice] : 0);
        if($this->optionWild && $dice != 'd1') $count += (isset($tmp['d1']) ? $tmp['d1'] : 0);
      }
      foreach($this->players as $player => $cup) {
        $this->mChan("$player's cup contains: ".implode(" ", $cup).".");
        $tmp = array_count_values($cup);
        $count += (isset($tmp[$dice]) ? $tmp[$dice] : 0);
        $realCount += (isset($tmp[$dice]) ? $tmp[$dice] : 0);
        if($this->optionWild && $dice != 'd1') $count += (isset($tmp['d1']) ? $tmp['d1'] : 0);
      }
      if($count >= $bid) {
        if($this->optionWild) {
          $this->mChan("There were actually $count $dice ($realCount without 1s.) ".$this->lastBidder." was correct. $nick loses a die!");
        }
        else {
          $this->mChan("There were actually $count $dice. ".$this->lastBidder." was correct. $nick loses a die!");
        }
        $loser = $nick;
      } else {
        if($this->optionWild) {
          $this->mChan("There were only $count $dice ($realCount without 1s.) Good call $nick. ".$this->lastBidder." loses a die!");
        }
        else {
          $this->mChan("There were only $count $dice. Good call $nick. ".$this->lastBidder." loses a die!");
        }
        $loser = $this->lastBidder;
      }
    }
    $this->dice[$loser]--;
    if($this->dice[$loser] == 0) {
      $this->mChan("$loser has been eliminated!");
      unset($this->players[$loser]);
      unset($this->dice[$loser]);
      if(count($this->players) == 1) {
        $winner = array_shift(array_keys($this->players));
        $this->mChan("Only $winner remains! They win!");
        $this->resetVars();
        $this->mChan("A new game can now begin. Please !join if you would like to play again.");
        return;
      }
      $this->nextPlayer();
    } else {
      $this->currentPlayer = $loser;
    }
    $this->lastBid = array(0, '');
    $this->lastBidder = '';
    $this->newHand();
  }
  function cmdSpot($nick) {
    if(!($this->started)) {
      $this->mChan("$nick: No game has started yet.");
      return;
    }
    if($nick != $this->currentPlayer) {
      $this->mChan("$nick: Please wait your turn.");
      return;
    }
    if(!$this->optionSpot) {
      $this->mChan("$nick: Sorry, spot is not enabled for this game.");
      return;
    }
    $this->mChan("$nick has called ".$this->lastBidder."'s bid!");
    list($bid, $dice) = $this->lastBid;
    $count = 0;
    $realCount = 0;
    if($this->optionRiver) {
      $this->mChan("The River has: ".implode(" ", $this->theRiver));
      $tmp = array_count_values($this->theRiver);
      $count += (isset($tmp[$dice]) ? $tmp[$dice] : 0);
      $realCount += (isset($tmp[$dice]) ? $tmp[$dice] : 0);
      if($this->optionWild && $dice != 'd1') $count += (isset($tmp['d1']) ? $tmp['d1'] : 0);
    }
    foreach($this->players as $player => $cup) {
      $this->mChan("$player's cup contains: ".implode(" ", $cup).".");
      $tmp = array_count_values($cup);
      $count += (isset($tmp[$dice]) ? $tmp[$dice] : 0);
      $realCount += (isset($tmp[$dice]) ? $tmp[$dice] : 0);
      if($this->optionWild && $dice != 'd1') $count += (isset($tmp['d1']) ? $tmp['d1'] : 0);
    }
/*
    if($count == $bid) {
      $this->mChan("There were actually $count $dice. Good spot $nick! Everyone else loses a die.");
      $loser = $nick;
      $elim = array();
      foreach($this->players as $player => $d) {
        if($player == $nick) continue;
        $this->dice[$player]--;
        if($this->dice[$player] == 0) $elim[] = $player;
      }
      foreach($elim as $loser) {
        $this->mChan("$loser has been eliminated!");
      }
      if($this->lastBidder == '') {
        $this->mChan("$nick: Sorry, the first bidder must make a bid.");
        return;
      }
      $this->mChan("$nick has called ".$this->lastBidder."'s bid!");
      list($bid, $dice) = $this->lastBid;
      $count = 0;
      foreach($this->players as $player => $cup) {
        $this->mChan("$player's cup contains: ".implode(" ", $cup).".");
        $tmp = array_count_values($cup);
        $count += (isset($tmp[$dice]) ? $tmp[$dice] : 0);
      }
 */
    if($count == $bid) {
      if($this->optionWild) {
        $this->mChan("There were actually $count $dice ($realCount without 1s.) Good spot $nick! Everyone else loses a die.");
      }
      else {
        $this->mChan("There were actually $count $dice. Good spot $nick! Everyone else loses a die.");
      }
      $loser = $nick;
      $elim = array();
      foreach($this->players as $player => $d) {
        if($player == $nick) continue;
        $this->dice[$player]--;
        if($this->dice[$player] == 0) $elim[] = $player;
      }
      foreach($elim as $loser) {
        $this->mChan("$loser has been eliminated!");
        unset($this->players[$loser]);
        unset($this->dice[$loser]);
        if(count($this->players) == 1) {
          $winner = array_shift(array_keys($this->players));
          $this->mChan("Only $winner remains! They win!");
          $this->resetVars();
          $this->mChan("A new game can now begin. Please !join if you would like to play again.");
          return;
        }
      }
      $this->currentPlayer = $nick;
      $this->nextPlayer();
    } else {
      if($this->optionWild) {
        $this->mChan("There were $count $dice ($realCount without 1s.) Nice try $nick, but you lose a die!");
      }
      else {
        $this->mChan("There were $count $dice. Nice try $nick, but you lose a die!");
      }
      $loser = $nick;
      $this->dice[$loser]--;
      if($this->dice[$loser] == 0) {
        $this->mChan("$loser has been eliminated!");
        unset($this->players[$loser]);
        unset($this->dice[$loser]);
        if(count($this->players) == 1) {
          $winner = array_shift(array_keys($this->players));
          $this->mChan("Only $winner remains! They win!");
          $this->resetVars();
          $this->mChan("A new game can now begin. Please !join if you would like to play again.");
          return;
        }
        $this->nextPlayer();
      } else {
        $this->currentPlayer = $loser;
      }
    }
    $this->lastBid = array(0, '');
    $this->lastBidder = '';
    $this->newHand();
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
    if(!$this->optionPass) {
      $this->mChan("$nick: Sorry, pass is not enabled for this game.");
      return;
    }
    if($this->passes[$nick]) {
      $options = array('bid', 'call');
      if($this->optionSpot) $options[] = 'spot';
      if($this->optionPass) $options[] = 'pass';
      $lastOption = array_pop($options);
      $this->mChan("$nick: You have already passed. Please ".implode(", ", $options).", or $lastOption.");
      return;
    }
    $this->passes[$nick] = true;
    $this->lastPasser = $nick;
    $this->nextPlayer();
    if($this->lastBid[0] > 0) {
      $options = array('bid', 'call');
      if($this->optionSpot) $options[] = 'spot';
      if($this->optionPass) $options[] = 'pass';
      $lastOption = array_pop($options);
      $this->mChan("$nick: You have already passed. Please ".implode(", ", $options).", or $lastOption.");
      $this->mChan("$nick has passed. The high bid is ".implode(" ", $this->lastBid)." by ".$this->lastBidder.". ".$this->currentPlayer." please ".implode(", ", $options).", or $lastOption.");
    } else {
      $this->mChan("$nick has passed. ".$this->currentPlayer." please make the opening bid.");
    }
    if($this->optionRiver) $this->mChan("The River has: ".implode(" ", $this->theRiver));
    $this->nUser($this->currentPlayer, "Your cup: ".implode(" ", $this->players[$this->currentPlayer]));
  }
  // Handle player idle (!boot command)
}
