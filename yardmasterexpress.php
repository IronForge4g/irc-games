<?php
require_once('yardmasterexpress/player.php');

require_once('yardmasterexpress/phase.nogame.php');
require_once('yardmasterexpress/phase.setup.php');
require_once('yardmasterexpress/phase.game.php');
require_once('yardmasterexpress/phase.end.php');

require_once('generic/deck.base.php');
require_once('yardmasterexpress/deck.train.php');
require_once('yardmasterexpress/deck.caboose.php');

class yardmasterexpress implements pluginInterface {
  var $config;
  var $socket;
  var $channel;
  var $game;
  var $started;

  var $players;
  var $playerMap;
  var $phases;
  var $phase; 
  var $currentPlayer;

  var $deck;
  var $hand;
  var $wild;
  var $cabooseDeck;
  var $caboose;
  var $gameEnd;
  var $autoScore;

  function init($config, $socket) {
    $this->config = $config;
    $this->socket = $socket;
    $this->channel = '#PlayYardmasterExpress';
    $this->game = 'Yardmaster Express';
    $this->started = false;
    $this->autoScore = null;

    $this->phases = array();
    $this->phases['nogame'] = new phaseYardmasterExpressNoGame($this);
    $this->phases['setup'] = new phaseYardmasterExpressSetup($this);
    $this->phases['game'] = new phaseYardmasterExpressGame($this);
    $this->phases['end'] = new phaseYardmasterExpressEnd($this);

    $this->setPhase('nogame');
  }

  function tick() {

  }

  function onMessage($from, $channel, $msg) {
    if($channel != $this->channel) return;
    if($msg{0} != '!') return;
    $args = explode(" ", $msg);
    $cmdRaw = array_shift($args);
    $cmd = 'cmd'.strtolower(substr($cmdRaw, 1));
    if(trim($cmd) == 'cmd') return;
    if(method_exists($this, $cmd)) {
      $this->$cmd($from, $args);
    } else if(method_exists($this->phase, $cmd)) {
      $this->phase->$cmd($from, $args);
    } else {
      $this->mChan("$from: $cmdRaw does not exist in the phase '{$this->phase->desc}'.");
    }

  }
  function onNick($from, $to) {
    if(isset($this->players[$from])) {
      $this->players[$to] = $this->players[$from];
      $this->players[$to]->nick = $to;
      unset($this->players[$from]);
    }
  }
  function onQuit($from) {

  }

  function destroy() {

  }
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
    $player = $this->findPlayer($nick);
    if($player == null) sendNotice($this->socket, $nick, $message);
    else {
      if($player->msgType == 'msg') sendMessage($this->socket, $nick, $message);
      else sendNotice($this->socket, $nick, $message);
    }
  }
  function playerList() {
    $players = array_keys($this->players);
    return implode(", ", $players);
  }
  function setPhase($phase) {
    $this->phase = $this->phases[$phase];
    $this->phase->init();
  }
  function checkCurrentPlayer($from, $cmd) {
    if($this->currentPlayer->nick != $from) {
      $this->mChan("$from: Please wait your turn to $cmd.");
      return false;
    }
    return true;
  }
  function checkArgs($from, $args, $min, $max = -1) {
    if($max == -1) $max = $min;
    $argc = count($args);
    if($argc < $min || $argc > $max) {
      if($min == $max) $this->mChan("$from: That command only takes $min argument(s). Please try again.");
      else $this->mChan("$from: That command requires $min-$max arguments. Please try again.");
      return false;
    }
    return true;
  }
  function findPlayer($nick) {
    if(!(isset($this->playerMap[strtolower($nick)]))) return null;
    return $this->playerMap[strtolower($nick)];
  }
  function points($c) {
    if($c == 0) return "no points";
    else if($c == 1) return "1 point";
    else return "$c points";
  }
  function cmdhelp($from, $args) {
    $this->nUser($from, "!rules - Shows the rules for Yardmaster Express.");
    $this->nUser($from, "!start - Start a new game of Yardmaster Express.");
    $this->nUser($from, "!join - Join a game.");
    $this->nUser($from, "!part - Part a game.");
    $this->nUser($from, "!notice - Bot will send notices for your hand.");
    $this->nUser($from, "!msg - Bot will send messages for your hand.");
    $this->nUser($from, "!autoscore <on|off> - Will show the score between each card play.");
    $this->nUser($from, "!play <card> | !p <card> - Play a card.");
    $this->nUser($from, "!wild <card> | !w <card> - Play a card face down as a wild.");
  }
  function cmdrules($from, $args) {
    $this->nUser($from, "Yardmaster Express is an IRC implementation of the game Yardmaster Express!");
    $this->nUser($from, "The rules can be found online at http://boardgamegeek.com/filepage/103647/yardmaster-express-prototype-rules-english");
  }
  function displayHand($drawn = '') {
    $display = array();
    foreach($this->hand as $letter => $card) {
      if($letter == $drawn) $display[] = "$letter*. ".$card->display();
      else $display[] = "$letter. ".$card->display();
    }
    return implode(', ', $display);
  }
  function cmdscores($from, $args) {
    if(!($this->started)) return;
    $this->score();
  }
  function cmdscore($from, $args) {
    if(!($this->started)) return;
    $this->score();
  }
  function cmdboard($from, $args) {
    if(!($this->started)) return;
    $this->score();
  }
  function score() {
    $runs = array();
    $scores = array();
    foreach($this->players as $nick => $player) {
      $last = null;
      $run = 0;
      $bestRun = 0;
      $score = 0;
      foreach($player->train as $car) {
        $score += $car->leftNumber;
        $score += $car->rightNumber;
        if($car->leftColor == $last) $run++;
        else {
          if($run > $bestRun && $last != 'Wild') $bestRun = $run;
          $last = $car->leftColor;
          $run = 1;
        }
        if($car->rightColor == $last) $run++;
        else {
          if($run > $bestRun && $last != 'Wild') $bestRun = $run;
          $last = $car->rightColor;
          $run = 1;
        }
      }
      if($run > $bestRun && $last != 'Wild') $bestRun = $run;
      $scores[$nick] = $score;
      $runs[$nick] = $bestRun;
    }
    $longestRun = -1;
    $longestRunners = array();
    foreach($runs as $nick => $bestRun) {
      if($bestRun > $longestRun) {
        $longestRun = $bestRun;
        $longestRunners = array($nick);
      }
      else if($bestRun == $longestRun) {
        $longestRunners[] = $nick;
      }
    }
    $this->mChan("The caboose is {$this->caboose->title} ({$this->caboose->points} points): {$this->caboose->text}");
    foreach($longestRunners as $nick) $scores[$nick] += $longestRun;
    $cabooses = $this->caboose->win();
    foreach($cabooses as $nick) $scores[$nick] += $this->caboose->points;
    foreach($this->players as $nick => $player) {
      $longest = in_array($nick, $longestRunners) ? '^' : '';
      $caboose = in_array($nick, $cabooses) ? '*' : '';
      $score = $scores[$nick];
      $this->mChan("$nick ({$score}{$longest}{$caboose}) ".$player->displayTrain().".");
    }
  }
  function cmdautoscore($from, $args) {
    if(!(isset($args[0]))) {
      $this->mChan("Auto scoring is currently ".($this->autoScore ? 'on' : 'off').".");
      return;
    }
    $cmd = strtolower($args[0]);
    if($cmd == 'off') {
      $this->autoScore = false;
      $this->mChan("Auto scoring has been turned off.");
    }
    else if($cmd == 'on') {
      $this->autoScore = true;
      $this->mChan("Auto scoring has been turned on.");
    }
    else {
      $this->mChan("Auto scoring is currently ".($this->autoScore ? 'on' : 'off').". It can only be set to on or off, please specify a valid option.");
    }
  }
  function cmdnotice($from, $args) {
    $player = $this->findPlayer($from);
    if($player == null) return;
    $player->msgType = 'notice';
    $this->mChan("Messages will now be sent to you as a notice.");
  }
  function cmdmsg($from, $args) {
    $player = $this->findPlayer($from);
    if($player == null) return;
    $player->msgType = 'msg';
    $this->mChan("Messages will now be sent to you as private message.");
  }
}
?>
