<?php
require_once('shadowhunters\player.php');
require_once('shadowhunters\areas.php');
require_once('shadowhunters\characters.php');

require_once('shadowhunters\phase.altar.php');
require_once('shadowhunters\phase.attack.php');
require_once('shadowhunters\phase.cemetary.php');
require_once('shadowhunters\phase.charles.php');
require_once('shadowhunters\phase.church.php');
require_once('shadowhunters\phase.end.php');
require_once('shadowhunters\phase.hermit.php');
require_once('shadowhunters\phase.move.php');
require_once('shadowhunters\phase.nogame.php');
require_once('shadowhunters\phase.setup.php');
require_once('shadowhunters\phase.steal.php');
require_once('shadowhunters\phase.underworld.php');
require_once('shadowhunters\phase.werewolf.php');
require_once('shadowhunters\phase.woods.php');

require_once('shadowhunters\deck.base.php');
require_once('shadowhunters\deck.cemetary.php');
require_once('shadowhunters\deck.church.php');
require_once('shadowhunters\deck.hermit.php');


class shadowhunters implements pluginInterface {
  var $config;
  var $socket;
  var $channel;
  var $game;
  var $started;

  var $players;
  var $phases;
  var $phase; 
  var $currentPlayer;
  var $areas;
  var $areasNum;
  var $blocks;

  var $cemetaryDeck;
  var $churchDeck;
  var $hermitDeck;

  function init($config, $socket) {
    $this->config = $config;
    $this->socket = $socket;
    $this->channel = '#PlayShadowHunters';
    $this->game = 'Shadow Hunters';
    $this->started = false;

    $this->phases = array();
    $this->phases['altar'] = new phaseAltar($this);
    $this->phases['attack'] = new phaseAttack($this);
    $this->phases['cemetary'] = new phaseCemetary($this);
    $this->phases['charles'] = new phaseCharles($this);
    $this->phases['church'] = new phaseChurch($this);
    $this->phases['end'] = new phaseEnd($this);
    $this->phases['hermit'] = new phaseHermit($this);
    $this->phases['move'] = new phaseMove($this);
    $this->phases['nogame'] = new phaseNoGame($this);
    $this->phases['setup'] = new phaseSetup($this);
    $this->phases['steal'] = new phaseSteal($this);
    $this->phases['underworld'] = new phaseUnderworld($this);
    $this->phases['werewolf'] = new phaseWerewolf($this);
    $this->phases['woods'] = new phaseWoods($this);

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
  function playerList() {
    $players = array_keys($this->players);
    return implode(", ", $players);
  }
  function setPhase($phase) {
    if($this->currentPlayer != null) {
      if($this->checkWin()) return;
      if(count($this->currentPlayer->steal) > 0) {
        $this->phase = $this->phases['steal'];
        $this->phase->return = $phase;
        $this->phase->init();
        return;
      } 
    }
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
  function validTarget($nick) {
    if(isset($this->players[$nick])) {
      if($this->players[$nick]->alive) return true;
    }
    return false;
  }
  function cmdboard($from, $args) {
    if(!($this->started)) return;
    $this->mChan('Block 1: '.$this->blocks[0][0]->display().', '.$this->blocks[0][1]->display());
    $this->mChan('Block 2: '.$this->blocks[1][0]->display().', '.$this->blocks[1][1]->display());
    $this->mChan('Block 3: '.$this->blocks[2][0]->display().', '.$this->blocks[2][1]->display());
    $damage = array();
    foreach($this->players as $nick => $player) $damage[$nick] = $player->damage;
    ksort($damage);
    asort($damage);
    $display = array();
    foreach($damage as $nick => $dmg) $display[] = "$nick ($dmg)";
    $this->mChan('Damage: '.implode(', ', $display));
  }
  function cmdequip($from, $args) {
    if(count($args) == 0) $args[0] = $from;
    if(!(isset($this->players[$args[0]]))) {
      $this->mChan("$from: Please specify a valid player.");
      return;
    }
    $this->players[$args[0]]->equipment($from);
  }
  function cmdreveal($from, $args) {
    if(!(isset($this->players[$from]))) return;
    $this->players[$from]->reveal();
  }
  function cmdwhoami($from, $args) {
    if(!($this->started)) return;
    if(!(isset($this->players[$from]))) return;
    $this->nUser($from, "You are {$this->players[$from]->character->name} ({$this->players[$from]->character->team}).");
  }
  function cmdchar($from, $args) {
    if(!(isset($this->players[$from]))) return;
    $this->players[$from]->character->action($from, $args);
  }
  function checkWin($winner = '') {
    $winCount = 0;
    $winners = array();
    if($winner != '') $winners[] = $winner;
    foreach($this->players as $nick => $player) {
      if($nick == $winner) continue;
      if($player->character->win()) {
        $winCount++;
        $winners[] = $nick;
      }
    }
    if($winCount > 0) {
      $this->mChan("The game is now over!."); 
      foreach($this->players as $nick => $player) {
        $player->reveal();
        if($player->character->name == 'Allie' && $player->alive) $winners[] = $nick;
      }
      sort($winners);
      $this->mChan("The winner(s) are: ".implode(', ', $winners).".");
      $this->phase = $this->phases['nogame'];
      $this->phase->init();
      return true;
    }
    return false;
  }
}
?>
