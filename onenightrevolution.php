<?php
require_once('onenightrevolution/player.php');

require_once('onenightrevolution/phase.nogame.php');
require_once('onenightrevolution/phase.setup.php');
require_once('onenightrevolution/phase.night.php');
require_once('onenightrevolution/phase.claim.php');
require_once('onenightrevolution/phase.day.php');
require_once('onenightrevolution/phase.end.php');

require_once('generic/deck.base.php');
require_once('onenightrevolution/deck.spec.php');
require_once('onenightrevolution/deck.team.php');

class onenightrevolution implements pluginInterface {
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
  var $controller;

  var $specDeck;
  var $teamDeck;
  var $table;
  var $tableCards;
  var $tableCardsRevealed;
  var $called;

  function init($config, $socket) {
    $this->config = $config;
    $this->socket = $socket;
    $this->channel = '#PlayOneNightRevolution';
    $this->game = 'One Night Revolution';
    $this->started = false;

    $this->phases = array();
    $this->phases['nogame'] = new phaseOneNightRevolutionNoGame($this);
    $this->phases['setup'] = new phaseOneNightRevolutionSetup($this);
    $this->phases['night'] = new phaseOneNightRevolutionNight($this);
    $this->phases['claim'] = new phaseOneNightRevolutionClaim($this);
    $this->phases['day'] = new phaseOneNightRevolutionDay($this);
    $this->phases['end'] = new phaseOneNightRevolutionEnd($this);

    $this->setPhase('nogame');
  }

  function tick() {
    if($this->started && $this->phase->desc == 'Day') $this->phase->tick();
  }

  function onMessage($from, $channel, $msg) {
    $source = $this->channel;
    if($channel{0} == '#' && strtolower($channel) != strtolower($this->channel)) return;
    else if($channel{0} != '#') {
      if(!($this->started)) return;
      if($this->findPlayer($from) == null) return;
      $source = $from;
    }
    if($msg{0} != '!') return;
    $args = explode(" ", $msg);
    $cmdRaw = array_shift($args);
    $cmd = 'cmd'.strtolower(substr($cmdRaw, 1));
    if(trim($cmd) == 'cmd') return;
    if(method_exists($this, $cmd)) {
      $this->$cmd($from, $args, $source);
    } else if(method_exists($this->phase, $cmd)) {
      $this->phase->$cmd($from, $args, $source);
    } else {
      if($source == $this->channel) $this->mChan("$from: $cmdRaw does not exist in the phase '{$this->phase->desc}'.");
      else $this->nUser($from, "$cmdRaw does not exist in the phase '{$this->phase->desc}'.");
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
  function mTarget($from, $target, $message) {
    if($target == $this->channel) $this->mChan("$from: $message");
    else $this->nUser($target, $message);
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
  function checkCurrentPlayer($from, $cmd, $source) {
    if($this->currentPlayer->nick != $from) {
      $this->mTarget($from, $source, "Please wait your turn to $cmd.");
      return false;
    }
    return true;
  }
  function checkArgs($from, $args, $min, $max = -1, $source) {
    if($max == -1) $max = $min;
    $argc = count($args);
    if($argc < $min || $argc > $max) {
      if($min == $max) $this->mTarget($from, $source, "That command only takes $min argument(s). Please try again.");
      else $this->mTarget($from, $source, "That command requires $min-$max arguments. Please try again.");
      return false;
    }
    return true;
  }
  function findPlayer($nick) {
    if(!(isset($this->playerMap[strtolower($nick)]))) return null;
    return $this->playerMap[strtolower($nick)];
  }
  function cmdhelp($from, $args, $source) {
    $this->nUser($from, "!rules - Show's the rules for One Night Revolution.");
    $this->nUser($from, "!start <Specialists> - Start a new game of One Night Revolution, If no Specialists are given, the default for new players are used. Otherwise, specify a comma separated list of the specialists, one per player.");
    $this->nUser($from, "!join - Join a game.");
    $this->nUser($from, "!part - Part a game.");
    $this->nUser($from, "!take <Specialist> - Takes a specialist token.");
    $this->nUser($from, "!specs - List all specialists.");
    $this->nUser($from, "!complete - Notify everyone that you are done your night task.");
    $this->nUser($from, "Other commands will be given for your specific specialist card.");
    $this->nUser($from, "Due to the nature of ONR, the bot will send you private messages for communication. This can be changed using !notice, but it is not recommended.");
  }
  function cmdrules($from, $args, $source) {
    $this->nUser($from, "One Night Revolution is an IRC implementation of the game One Night Revolution!");
    $this->nUser($from, "The rules can be found online at https://dl.dropboxusercontent.com/u/77913315/One%20Night%20Revolution%20Draft%20Rules.pdf (hopefully it is still there)");
    $this->nUser($from, "Or ask someone in #boardgames for a learning game!");
  }
  function cmdnotice($from, $args, $source) {
    $player = $this->findPlayer($from);
    if($player == null) return;
    $player->msgType = 'notice';
    $this->nUser("Messages will now be sent to you as a notice.");
  }
  function cmdmsg($from, $args, $source) {
    $player = $this->findPlayer($from);
    if($player == null) return;
    $player->msgType = 'msg';
    $this->nUser("Messages will now be sent to you as private message.");
  }
  function findSpec($spec) {
    $tSpec = strtolower($spec);
    $spec1 = substr($tSpec, 0, 1);
    $spec2 = substr($tSpec, 0, 2);
    $spec3 = substr($tSpec, 0, 3);
    if($spec1 == 'o') return 'Observer'; 
    else if ($spec1 == 'i') return 'Investigator';
    else if ($spec1 == 's') return 'Signaler';
    else if ($spec1 == 't') return 'Thief';
    else if ($spec1 == 'a') return 'Analyst';
    else if ($spec1 == 'c') return 'Confirmer';
    else if ($spec1 == 'b') return 'BlindInformant';
    else if ($spec1 == 'd') return 'Defector';
    else if ($spec2 == 'ro') return 'Rogue';
    else if ($spec3 == 'rea') return 'Reassignor';
    else if ($spec3 == 'rev') return 'Revealer';
    return null;
  }
  function cmdspecs($from, $args, $source) {
    $this->nUser($from, '(O)bserver: no night action');
    $this->nUser($from, '(I)nvestigator: look at one ID');
    $this->nUser($from, '(S)ignaler: if Informant, tap an Informant on the shoulder to your immediate left or immediate right; if Rebel, tap a player on the shoulder to your immediate left or immediate right');
    $this->nUser($from, '(T)hief: if Informant, view your own ID; if Rebel, swap your ID with another players ID and view that new ID');
    $this->nUser($from, '(Rea)ssignor: if Informant, switch an Informant ID in the middle with a Rebel players ID; if Rebel, swap two other players IDs');
    $this->nUser($from, '(A)nalyst: view 1 players Specialist card');
    $this->nUser($from, '(C)onfirmer: view your ID');
    $this->nUser($from, '(Rev)ealer: flip a players ID face up (including your own) and only if it is an Informant, flip it back face down');
    $this->nUser($from, '(B)lind Informant: if Informant, do not wake up during Informant reveal phase but instead put your thumb up; if Rebel, no night action');
    $this->nUser($from, '(D)efector: if Informant, view your ID; if Rebel, swap your ID with an Informant ID in the middle (if none exist, skip)');
    $this->nUser($from, '(Ro)gue: if Informant, swap a Rebels ID with another Informants ID; if Rebel, view your ID card');
  }
  function cmdvote($from, $args, $source) {
    $player = $this->findPlayer($from);
    if($player == null) return;
    if(!($this->checkArgs($from, $args, 1, 1, $source))) return;
    $vote = $this->findPlayer($args[0]);
    if($vote == null) {
      $this->nUser($from, "{$args[0]} is not a valid player. Please specify a valid player to !vote for.");
      return;
    }
    $player->vote = $vote;
    $this->nUser($from, "You have changed your vote to {$vote->nick}.");
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
}
?>
