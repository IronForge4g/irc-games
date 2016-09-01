<?php
require_once('codenames/player.php');

require_once('codenames/phase.nogame.php');
require_once('codenames/phase.setup.php');
require_once('codenames/phase.spymaster.php');
require_once('codenames/phase.guess.php');

require_once('generic/deck.base.php');
require_once('codenames/deck.codenames.php');

class codenames implements pluginInterface {
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

  var $codenamesDeck;
  var $spymaster;
  var $words;
  var $turn;

  function init($config, $socket) {
    $this->config = $config;
    $this->socket = $socket;
    $this->channel = '#PlayCodenames';
    $this->game = 'Codenames';
    $this->started = false;

    $this->phases = array();
    $this->phases['nogame'] = new phaseCodenamesNoGame($this);
    $this->phases['setup'] = new phaseCodenamesSetup($this);
    $this->phases['spymaster'] = new phaseCodenamesSpymaster($this);
    $this->phases['guess'] = new phaseCodenamesGuess($this);

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
      $this->playerMap[strtolower($to)] = $this->players[$to];
      unset($this->playerMap[strtolower($from)]);
      unset($this->players[$from]);
    }
  }
  function onQuit($from) {
    if($this->started) return;
    if(isset($this->players[$from])) {
      unset($this->players[$from]);
      unset($this->playerMap[strtolower($from)]);
      $playerCount = count($this->players);
      if($playerCount > 0) {
        $this->mChan("$from has left the game. Current players are now: ".$this->playerList().".");
      } else {
        $this->mChan("$from has left the game.");
      }
    }
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
      $this->mChan($from.", please wait your turn to $cmd.");
      return false;
    }
    return true;
  }
  function checkArgs($from, $args, $min, $max = -1) {
    if($max == -1) $max = $min;
    $argc = count($args);
    if($argc < $min || $argc > $max) {
      if($min == $max) $this->mChan("That command only takes $min argument(s). Please try again.");
      else $this->mChan("$from, That command requires $min-$max arguments. Please try again.");
      return false;
    }
    return true;
  }
  function findPlayer($nick) {
    if(!(isset($this->playerMap[strtolower($nick)]))) return null;
    return $this->playerMap[strtolower($nick)];
  }
  function cmdhelp($from, $args) {
    $this->nUser($from, "!rules - Show's the rules for Codenames.");
    $this->nUser($from, "!start - Start a new game of Codenames.");
    $this->nUser($from, "!join - Join a game.");
    $this->nUser($from, "!part - Part a game.");
    $this->nUser($from, "!(c)lue <word> <number> - Give a clue.");
    $this->nUser($from, "!(g)uess <word> - Guess a word.");
    $this->nUser($from, "!(s)top - Stop guessing.");
  }
  function cmdrules($from, $args) {
    $this->nUser($from, "Codenames is an IRC implementation of the game Codenames!");
    $this->nUser($from, "Codenames has two teams (green and pink), trying to guess all the code names for the spies on their team.");
    $this->nUser($from, "The two teams are broken up, with one player on each team being the spymaster for the game, and the remaining players trying to guess their clues.");
    $this->nUser($from, "Spymasters will provide a clue word, and how many cards that clue matches. Players on their team will then try to guess those cards, up to one extra guess.");
    $this->nUser($from, "If you guess an opponents spy, your turn ends. One spy is also an assassin, any player guessing the assassin instantly loses the game for their team.");
    $this->nUser($from, "See https://boardgamegeek.com/filepage/119841/codenames-rulebook-english for the full rules.");
  }
  function cmdnotice($from, $args) {
    $player = $this->findPlayer($from);
    if($player == null) return;
    $player->msgType = 'notice';
    $this->nUser("Messages will now be sent to you as a notice.");
  }
  function cmdmsg($from, $args) {
    $player = $this->findPlayer($from);
    if($player == null) return;
    $player->msgType = 'msg';
    $this->nUser("Messages will now be sent to you as private message.");
  }
  function plural($v, $word) {
    if($v == 0) return "no {$word}s";
    else if($v == 1) return "1 {$word}";
    else return "$v {$word}s";
  }
  function pluralWord($v, $singular, $plural) {
    if($v == 1) return $singular;
    return $plural;
  }
  function team($color, $spymaster = true) {
    $nicks = array();
    foreach($this->players as $nick => $player) {
      if($player->color == $color) {
        if($player->spymaster) {
          if($spymaster) $nicks[] = "**$nick**";
        }
        else $nicks[] = $nick;
      }
    }
    return $this->colorText(implode(', ', $nicks), $color);
  }
  function colorText($text, $color) {
    if($color == 'green') return chr(3).'03'.$text.chr(15);
    else if($color == 'pink') return chr(3).'13'.$text.chr(15);
    else if($color == 'orange') return chr(3).'07'.$text.chr(15);
    else if($color == 'none') return $text;
    return $text;
  }
  function cmdend($from, $args) {
    if(!($this->started)) return;
    $tPlayer = $this->findPlayer($from);
    if($tPlayer == null) return;
    $tPlayer->ending = true;
    $ending = array();
    $playerCount = count($this->players);
    $needed = ceil($playerCount / 2);
    $wants = 0;
    foreach($this->players as $nick => $player) {
      if($player->ending) {
        $ending[] = $nick;
        $wants++;
      }
    }
    sort($ending);
    if($wants == $needed) {
    $this->mChan("Some players (".implode(', ', $ending)." have voted to end the game. Since this is half of the players, the game is now over.");
      $this->revealWords();
      $this->setPhase('nogame');
      return;
    } else {
    $this->mChan("Some players (".implode(', ', $ending)." would like to end this game. Please !end if you agree with them. At least half of the players must agree for this to occur.");
    }
  }
  function revealWords() {
    $words = array('green' => array(), 'pink' => array(), 'none' => array(), 'orange' => array());
    foreach($this->words as $word) {
      $words[$word->color][] = $word->cWord();
    }
    $this->mChan("Assassin: ".implode(', ', $words['orange']).". Green: ".implode(', ', $words['green']).". Pink: ".implode(', ', $words['pink']).". Civilians: ".implode(', ', $words['none']).".");
  }
  function cmdwords($from, $args) {
    if(!($this->started)) return;
    $words = array('green' => array(), 'pink' => array(), 'none' => array(), 'hidden' => array(), 'spygreen' => array(), 'spypink' => array(), 'spynone' => array(), 'spyorange' => array());
    foreach($this->words as $word) {
      if($word->revealed) $words[$word->color][] = $word->code;
      else {
        $words['hidden'][] = $word->code;
        $words['spy'.$word->color][] = $word->cWord();
      }
    }
    if(count($words['green']) > 0) $this->mChan("Green Spies: ".$this->colorText(implode(', ', $words['green']), 'green'));
    if(count($words['pink']) > 0) $this->mChan("Pink Spies: ".$this->colorText(implode(', ', $words['pink']), 'pink'));
    if(count($words['none']) > 0) $this->mChan("Civilians: ".implode(', ', $words['none']));
    $this->mChan("Codenames remaining: ".implode(', ', $words['hidden']));
    if($this->spymaster['green']->nick == $from) $this->nUser($this->spymaster['green']->nick, "Assassin: ".implode(', ', $words['spyorange']).". Green: ".implode(', ', $words['spygreen']).". Pink: ".implode(', ', $words['spypink']).". Civilians: ".implode(', ', $words['spynone']).".");
    if($this->spymaster['pink']->nick == $from) $this->nUser($this->spymaster['pink']->nick, "Assassin: ".implode(', ', $words['spyorange']).". Green: ".implode(', ', $words['spygreen']).". Pink: ".implode(', ', $words['spypink']).". Civilians: ".implode(', ', $words['spynone']).".");
  }
}
?>
