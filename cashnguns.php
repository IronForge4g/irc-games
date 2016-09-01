<?php

class cashnguns implements pluginInterface {
  var $config;
  var $socket;

  var $started;
  var $channel;
  var $players;
  var $wounds;
  var $numPlayers;
  var $roundPlayers;
  var $inPlayers;
  var $tableCards;
  var $targets;
  var $hands;
  var $sCard;
  var $sTarget;
  var $playerDeck;
  var $cashDeck;
  var $phase;
  var $bidTime;
  var $alertTime;

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
    $this->channel = '#PlayCashNGuns';
    $this->players = array();
    $this->wounds = array();
    $this->numPlayers = 0;
    $this->roundPlayers = array();
    $this->inPlayers = array();
    $this->tableCards = array();
    $this->targets = array();
    $this->hands = array();
    $this->sCard = array();
    $this->sTarget = array();
    $this->targets = array();
    $this->playerDeck = array('Bang! Bang! Bang!', 'Bang!', 'Bang!', 'Click.', 'Click.', 'Click.', 'Click.', 'Click.');
    $this->cashDeck = array();
    for($i=0;$i<15;$i++) { 
      $this->cashDeck[] = 5;
      $this->cashDeck[] = 10;
    }
    for($i=0;$i<10;$i++) $this->cashDeck[] = 20;
    shuffle($this->cashDeck);
    $this->phase = 'aim';
    $this->bidTime = 0;
    $this->alertTime = 0;
  }

  /**
  Called about twice per second or when there are
  activity on the channel the bot are in.
  put your jobs that needs to be run without user interaction here
  **/
  function tick() {
    if($this->bidTime == 0) return;
    if(time() > $this->bidTime) $this->startFight();
    else if($this->alertTime == 0) return;
    else if(time() > $this->alertTime) {
      $remaining = $this->bidTime - $this->alertTime;
      $this->mChan("Only $remaining seconds left to decide...");
      if($remaining <= 21 && $remaining >= 19) $this->alertTime = $this->bidTime - 10;
      else if($remaining <= 11 && $remaining >= 9) $this->alertTime = $this->bidTime - 5;
      else $this->alertTime = 0;
    }
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
    else if($cmd == "!aim") $this->cmdAim($from, $tmp);
    else if($cmd == "!in") $this->cmdIn($from);
    else if($cmd == "!coward") $this->cmdCoward($from);
  }
  function onNick($from, $to) {
    if(isset($this->players[$from])) {
      $this->players[$to] = $this->players[$from];
      $this->hands[$to] = $this->hands[$from];
      $this->bids[$to] = $this->bids[$from];
      unset($this->players[$from]);
      unset($this->hands[$from]);
      unset($this->bids[$from]);
    }
  }
  function onQuit($from) {
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
  function cmdHelp($nick) {
    $this->nUser($nick, "!rules - Show's the rules for Cash N Guns.");
    $this->nUser($nick, "!start - Start a new game of Cash N Guns.");
    $this->nUser($nick, "!join - Join a game.");
    $this->nUser($nick, "!aim <card> <target> - Load your gun with a card (really?) and point it at <target>. (eg. !aim X C)");
    $this->nUser($nick, "!in - Timer be damned, you're in it for the money.");
    $this->nUser($nick, "!coward - Timer be damned, you're a coward!");
  }
  function cmdRules($nick) {
    $this->nUser($nick, "Cash N Guns is an IRC implementation of the game Cash N Guns!");
    $this->nUser($nick, "I recommend you find the rules online, or ask someone for a teaching game.");
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
    if(count($this->players) < 4) {
      $players = implode(', ', array_keys($this->players));
      if($players == '') $players = '(none)';
      $this->mChan("A minimum of 4 players is required to start Cash N Guns. Current players are: $players. Please use !join to join this game.");
      return;
    }
    $players = implode(', ', array_keys($this->players));
    $this->mChan("Let the game begin! The players are: $players.");

    $this->started = true;

    for($i=0;$i<5;$i++) $this->tableCards[] = array_shift($this->cashDeck);
    sort($this->tableCards);

    $this->hands = array();
    $this->roundPlayers = array();
    foreach($this->players as $player => $cash) {
      $this->roundPlayers[$player] = $cash;
      $this->bids[$player] = '';
      $letters = array('S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'); // Trimmed down for 8 cards.
      shuffle($this->playerDeck);
      foreach($this->playerDeck as $card) {
        $this->hands[$player][array_shift($letters)] = $card;
      }
    }
    $this->phase = 'aim';
    $this->mChan("Cash on the table: ".implode(', ', $this->tableCards).". Everyone take aim!");
    foreach($this->players as $player => $cash) {
      $this->nUser($player, "Wounds: ".$this->wounds[$player]." Cash: ".'$'.$cash." Cards: ".$this->displayCards($player).". Targets: ".$this->displayTargets($player).".");
    }
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
    if($this->numPlayers == 6) {
      $this->mChan("$nick: Sorry, the player limit of 6 has been reached. Please wait for the next game.");
      return;
    }
    $this->players[$nick] = 0;
    $this->wounds[$nick] = 0;
    $this->numPlayers++;
    $players = implode(', ', array_keys($this->players));
    $this->mChan("$nick has joined the game. Current players are now: $players");
  }
  function displayCards($nick) {
    $arr = $this->hands[$nick];
    $text = array();
    foreach($arr as $let => $prop) {
      $text[] = "$let. $prop";
    }
    return implode(", ", $text);
  }
  function displayTargets($nick) {
    $letters = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
    $arr = array_keys($this->players);
    shuffle($arr);
    $text = array();
    $this->targets[$nick] = array();
    foreach($arr as $player) {
      if($player == $nick) continue;
      $let = array_shift($letters);
      if($this->wounds[$player] == 0) $wounds = '';
      if($this->wounds[$player] == 1) $wounds = '*';
      if($this->wounds[$player] == 2) $wounds = '**';
      if($this->wounds[$player] == 3) $wounds = '***';
      $text[] = "$let. $player$wounds ($".$this->players[$player].")";
      $this->targets[$nick][$let] = $player;
    }
    return implode(", ", $text);
  }
  function cmdAim($nick, $tmp) {
    if(!($this->started)) {
      $this->mChan("$nick: No game has started yet.");
      return;
    }
    if(!(isset($this->players[$nick]))) {
      $this->mChan("$nick: Bystanders should hide, please stop trying to get involved.");
      return;
    }
    if($this->phase != 'aim') {
      $this->mChan("$nick: Sorry, we're done aiming. It's time to threaten your colleagues for money!");
      return;
    }
    if(count($tmp) != 2) {
      $this->mChan("$nick: Please learn to hold your gun, in the format !aim <card> <target>. (eg !aim X C)");
      return;
    }
    if(!(isset($this->roundPlayers[$nick]))) {
      $this->mChan("$nick: You've already made your choice.");
      return;
    }
    $p1 = strtoupper($tmp[0]);
    $p2 = strtoupper($tmp[1]);
    if(isset($this->hands[$nick][$p1]) && isset($this->targets[$nick][$p2])) {
      $target = $p2;
      $card = $p1;
    }
    else if(isset($this->hands[$nick][$p2]) && isset($this->targets[$nick][$p1])) {
      $target = $p1;
      $card = $p2;
    }
    else {
      $this->mChan("$nick: Please learn to aim your gun ($p1 $p2 isn't valid), in the format !aim <card> <target>. (eg !aim X C)");
      return;
    }
    $this->sCard[$nick] = $card;
    $this->sTarget[$nick] = $target;
    unset($this->roundPlayers[$nick]);
    if(count($this->roundPlayers) > 0) {
      $waiting = array_keys($this->roundPlayers);
      $this->mChan("$nick has chosen their target. Still waiting for ".implode(", ", $waiting).".");
    }
    else {
      $this->phase = 'fire';
      $this->mChan("$nick has chosen their target. Let the bargaining commence! Are you in or are you a coward! You have 30 seconds to decide...");
      $this->bidTime = time() + 30;
      $this->alertTime = $this->bidTime - 20;
      foreach($this->players as $player => $cash) {
        $this->roundPlayers[$player] = $cash;
        $this->inPlayers[$player] = $cash;
        $this->mChan("$player has pointed their gun at ".$this->targets[$player][$this->sTarget[$player]].".");
      }
    }
  }
  function cmdIn($nick) {
    if(!($this->started)) {
      $this->mChan("$nick: No game has started yet.");
      return;
    }
    if(!(isset($this->players[$nick]))) {
      $this->mChan("$nick: Bystanders should hide, please stop trying to get involved.");
      return;
    }
    if($this->phase != 'fire') {
      $this->mChan("$nick: Sorry, everyone needs to aim first!");
      return;
    }
    if(!(isset($this->roundPlayers[$nick]))) {
      $this->mChan("$nick: You've already made your choice.");
      return;
    }
    unset($this->roundPlayers[$nick]);
    if(count($this->roundPlayers) > 0) {
      $waiting = array_keys($this->roundPlayers);
      $this->mChan("$nick is in it for the money. Still waiting for ".implode(', ', $waiting).".");
    }
    else {
      $this->startFight();
    }
  }
  function cmdCoward($nick) {
    if(!($this->started)) {
      $this->mChan("$nick: No game has started yet.");
      return;
    }
    if(!(isset($this->players[$nick]))) {
      $this->mChan("$nick: Bystanders should hide, please stop trying to get involved.");
      return;
    }
    if($this->phase != 'fire') {
      $this->mChan("$nick: Sorry, everyone needs to aim first!");
      return;
    }
    if(!(isset($this->roundPlayers[$nick]))) {
      $this->mChan("$nick: You've already made your choice.");
      return;
    }
    unset($this->roundPlayers[$nick]);
    unset($this->inPlayers[$nick]);
    if(count($this->roundPlayers) > 0) {
      $waiting = array_keys($this->roundPlayers);
      $this->mChan("$nick is a yellow bellied coward! Still waiting for ".implode(', ', $waiting).".");
      $this->players[$nick] -= 5;
    }
    else {
      $this->startFight();
    }
  }
  function startFight() {
    $this->mChan("The time for indecision is over...");
    $this->bidTime = 0;
    $this->alertTime = 0;
    $eliminated = array();
    $cantshoot = array();
    foreach($this->inPlayers as $player => $cash) {
      $target = $this->targets[$player][$this->sTarget[$player]];
      if(!(isset($this->inPlayers[$target]))) continue;
      if($this->hands[$player][$this->sCard[$player]] == 'Bang! Bang! Bang!') {
        $this->mChan("$player takes a cheap shot at ".$target.".");
        $this->wounds[$target]++;
        $eliminated[] = $target;
        $cantshoot[] = $target;
      }
    }
    foreach($this->inPlayers as $player => $cash) {
      if(in_array($player, $cantshoot)) continue;
      $target = $this->targets[$player][$this->sTarget[$player]];
      if(!(isset($this->inPlayers[$target]))) continue;
      if($this->hands[$player][$this->sCard[$player]] == 'Bang!') {
        $this->mChan("$player shoots ".$target.".");
        $this->wounds[$target]++;
        $eliminated[] = $target;
      }
      else if($this->hands[$player][$this->sCard[$player]] == 'Click.') {
        $this->mChan("$player makes clicky noises towards ".$target.".");
      }
    }
    foreach($eliminated as $e) {
      if(isset($this->inPlayers[$e])) {
        unset($this->inPlayers[$e]);
        //        $this->mChan("$e has been eliminated.");
      }
    }
    $killed = array();
    foreach($this->players as $player => $cash) {
      if($this->wounds[$player] >= 3) {
        $this->mChan("$player has been killed.");
        $killed[] = $player;
      }
      unset($this->hands[$player][$this->sCard[$player]]);
    }
    $this->sCard = array();
    $this->sTarget = array();
    foreach($killed as $dead) unset($this->players[$dead]);
    if(count($this->players) == 0) {
      $this->mChan("Everyone is dead. What is wrong with you people?..The game is now over, please !join to start a new one.");
      $this->resetVars();
      return;
    }
    else if(count($this->players) == 1) {
      $winner = array_keys($this->players);
      $this->mChan("Only ".$winner[0]." remains. They win I guess...The game is now over, please !join to start a new one.");
      $this->resetVars();
      return;
    }
    if(count($this->inPlayers) == 0) {
      $this->mChan("No one remains you greedy bastards.");
    } else {
      $this->dividePot();
    }
    if(count($this->cashDeck) == 0) {
      $winners = array();
      $bestScore = -1;
      $this->mChan("The game is now over.");
      foreach($this->players as $player => $cash) {
        $this->mChan("$player ended with ".'$'."$cash.");
        if($cash > $bestScore) {
          $winners = array($player);
          $bestScore = $cash;
        }
        else if($cash == $bestScore) {
          $winners[] = $player;
        }
      }
      if(count($winners) == 1) {
        $this->mChan($winners[0]. " has won the game, with a whopping ".'$'.$bestScore.". Congrats!");
      } else {
        $this->mChan("Tie game! ".implode(", ", $winners). " rejoice in their shared victory, with ".'$'.$bestScore.". Congrats!");
      }
      $this->resetVars();
      $this->mChan("A new game can now begin. Please !join if you would like to play again.");
      return;
    }
    $this->phase = 'aim';
    for($i=0;$i<5;$i++) $this->tableCards[] = array_shift($this->cashDeck);
    sort($this->tableCards);
    $this->roundPlayers = array();
    foreach($this->players as $player => $cash) $this->roundPlayers[$player] = $cash;
    $this->mChan("Cash on the table: ".implode(', ', $this->tableCards).". Everyone take aim!");
    foreach($this->players as $player => $cash) {
      $this->nUser($player, "Wounds: ".$this->wounds[$player]." Cash: ".'$'.$cash." Cards: ".$this->displayCards($player).". Targets: ".$this->displayTargets($player).".");
    }
  }
  function dividePot() {
    $players = count($this->inPlayers);
    $thisCash = $this->tableCards;
    sort($thisCash);
    $leftPot = array();
    while(true) {
      $total = array_sum($thisCash);
      $max = floor($total / $players / 5) * 5;
      if($max == 0) {
        $this->mChan("Cash can't be divided up, it was all for nothing...");
        return;
      }
      $remove = array();
      foreach($thisCash as $id => $amount) {
        if($amount > $max) $remove[] = $id;
      }
      foreach($remove as $r) {
        $leftPot[] = $thisCash[$r];
        unset($thisCash[$r]);
      }
      if(count($remove) == 0) break;
    }
    while(true) {
      $success = true;
      $testCash = $thisCash;
      for($i=0;$i<$players;$i++) {
        $pot = $this->makePile($max, $testCash);
        if(!(is_array($pot))) {
          $max -= 5;
          if($max == 0) {
            $this->mChan("Cash can't be divided up, it was all for nothing...");
            return;
          }
          $success = false;
          break;
        }
        else {
          $testCash = $pot;
        }
      }
      if($success) {
        foreach($pot as $c) $leftPot[] = $c;
        break;
      }
    }
    $names = array_keys($this->inPlayers);
    $this->mChan('$'."$max has been given to ".implode(', ', $names));
    foreach($this->inPlayers as $player => $cash) {
      $this->players[$player] += $max;
    }
    $this->tableCards = $leftPot;
  }
  function makePile($n, $tc) {
    $testCash = $tc;
    $needed = $n;
    while(true) {
      $bestId = -1;
      foreach($testCash as $id => $amount) {
        if($amount <= $needed) $bestId = $id;
      }
      if($bestId == -1) {
        return -1;
      }
      $needed -= $testCash[$bestId];
      unset($testCash[$bestId]);
      if($needed == 0) return $testCash;
    }
  }
  // Handle player idle (!boot command)
}
