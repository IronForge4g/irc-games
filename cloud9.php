<?php

class cloud9 implements pluginInterface {
  var $config;
  var $socket;

  var $started;
  var $channel;
  var $players;
  var $numPlayers;
  var $currentPlayer;
  var $roundPlayers;
  var $waiting;
  var $required;
  var $hands;
  var $deck;
  var $discard;
  var $dice;
  var $cloudDice;
  var $cloudPoints;
  var $currentCloud;
  var $phase;

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
    $this->channel = '#PlayCloud9';
    $this->players = array();
    $this->numPlayers = 0;
    $this->currentPlayer = '';
    $this->roundPlayers = array();
    $this->waiting = array();
    $this->required = array();
    $this->hands = array();
    $this->deck = array('Rainbow', 'Rainbow', 'Rainbow', 'Rainbow');
    for($i=0;$i<18;$i++) {
      $this->deck[] = 'Green';
      $this->deck[] = 'Purple';
      $this->deck[] = 'Red';
      $this->deck[] = 'Yellow';
    }
    shuffle($this->deck);
    $this->discard = array();
    $this->dice = array('Green', 'Purple', 'Red', 'Yellow', 'Blank', 'Blank');
    $this->cloudDice = array(0, 2, 2, 2, 3, 3, 3, 4, 4, 0);
    $this->cloudPoints = array(0, 1, 2, 4, 6, 9, 12, 15, 20, 25);
    $this->currentCloud = 1;
    $this->phase = '';
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
    else if($cmd == "!jump") $this->cmdJump($from);
    else if($cmd == "!stay") $this->cmdStay($from);
    else if($cmd == "!pilot") $this->cmdPilot($from, false);
    else if($cmd == "!rainbow") $this->cmdPilot($from, true);
    else if($cmd == "!score") $this->scores();
  }
  function onNick($from, $to) {
    if(isset($this->players[$from])) {
      $this->players[$to] = $this->players[$from];
      unset($this->players[$from]);
    }
    if(isset($this->roundPlayers[$from])) {
      $this->roundPlayers[$to] = $this->roundPlayers[$from];
      unset($this->roundPlayers[$from]);
    }
    if(isset($this->waiting[$from])) {
      $this->waiting[$to] = $this->waiting[$from];
      unset($this->waiting[$from]);
    }
    if($this->currentPlayer == $from) $this->currentPlayer = $to;
  }
  function onQuit($from) {
    /*
     * Wait for their return.
     echo "Processing On Quit\n";
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
     */
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
    $players = array_keys($this->roundPlayers);
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
    $this->nUser($nick, "!rules - Show's the rules for Cloud 9.");
    $this->nUser($nick, "!start - Start a new game of Cloud 9.");
    $this->nUser($nick, "!join - Join a game.");
    $this->nUser($nick, "!jump - Jump out of the balloon.");
    $this->nUser($nick, "!stay - Stay in the balloon.");
    $this->nUser($nick, "!pilot - Show if you can pilot the balloon safely or not.");
    $this->nUser($nick, "!rainbow - Pilot with a rainbow card.");
  }
  function cmdRules($nick) {
    $this->nUser($nick, "Cloud 9 is an IRC implementation of the game Cloud 9!");
    $this->nUser($nick, "The rules can be found online at http://www.otb-games.com/wordpress/wp-content/uploads/2011/05/cloud9_rules.pdf");
  }
  function points($c) {
    if($c == 0) return "no points";
    else if($c == 1) return "1 point";
    else return "$c points";
  }

  function rollDice($number) {
    $this->required = array();
    for($i=0;$i<$number;$i++) {
      $diceKey = array_rand($this->dice);
      $this->required[] = $this->dice[$diceKey];
    }
    sort($this->required);
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
      $this->mChan("A minimum of 3 players is required to start Cloud 9. Current players are: $players. Please use !join to join this game.");
      return;
    }

    $players = implode(', ', array_keys($this->players));
    $this->mChan("$nick has started the game! The turn order is: $players.");

    $players = array_keys($this->players);
    $this->currentPlayer = array_shift($players);
    $this->started = true;

    foreach($this->players as $player => $score) {
      $this->roundPlayers[$player] = $score;
      for($i=0;$i<6;$i++) $this->hands[$player][] = array_shift($this->deck);
      sort($this->hands[$player]);
      $this->nUser($player, "Your hand is: ".implode(', ', $this->hands[$player]).".");
    }
    $this->rollDice(2);
    $this->waiting = $this->roundPlayers;
    unset($this->waiting[$this->currentPlayer]);
    $this->phase = 'waiting';
    $this->mChan($this->currentPlayer." is the current pilot with ".count($this->hands[$this->currentPlayer])." skills, and the balloon is at cloud 1. Skills required: ".implode(', ', $this->required).". Everyone please !jump or !stay.");
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
    $this->hands[$nick] = array();
    $this->numPlayers++;
    $players = implode(', ', array_keys($this->players));
    $this->mChan("$nick has joined the game. Current players are now: $players");
  }
  function cmdJump($nick) {
    if(!($this->started)) {
      $this->mChan("$nick: No game has started yet.");
      return;
    }
    if($nick == $this->currentPlayer && $this->phase != 'solo') {
      $this->mChan("$nick: Your the pilot. No one is going to let you jump.");
      return;
    }
    if(!(isset($this->roundPlayers[$nick]))) {
      $this->mChan("$nick: You jump up and down on the ground trying to get a better look at the balloon. Nothing happens.");
      return;
    }
    if($this->phase != 'waiting' && $this->phase != 'solo') {
      $this->mChan("Sorry, we're waiting on ".$this->currentPlayer." to pilot the balloon.");
      return;
    }
    $points = $this->cloudPoints[$this->currentCloud];
    $this->players[$nick] += $points;
    unset($this->roundPlayers[$nick]);
    unset($this->waiting[$nick]);
    if(count($this->waiting) > 0) {
      $this->mChan("$nick has jumped from the balloon gaining ".$this->points($points).".");
    } else {
      $this->mChan("$nick has jumped from the balloon gaining ".$this->points($points).".");
      if($this->phase == 'solo') {
        $this->scores();
        $this->newRound();
        return;
      }
      $this->mChan($this->currentPlayer." everyone has made their choice. Skills required: ".implode(", ", $this->required).". Please !pilot or !rainbow.");
      $this->nUser($this->currentPlayer, "Your cards: ".implode(", ", $this->hands[$this->currentPlayer]).".");
      $this->phase = 'pilot';
    }
  }
  function cmdStay($nick) {
    if(!($this->started)) {
      $this->mChan("$nick: No game has started yet.");
      return;
    }
    if(!(isset($this->roundPlayers[$nick]))) {
      $this->mChan("$nick: You sit quietly on the ground. Nothing happens.");
      return;
    }
    if($nick == $this->currentPlayer && $this->phase != 'solo') {
      $this->mChan("$nick: Your the pilot. I don't think anyones going to let you jump anyways.");
      return;
    }
    if($this->phase != 'waiting' && $this->phase != 'solo') {
      $this->mChan("Sorry, we're waiting on ".$this->currentPlayer." to pilot the balloon.");
      return;
    }
    unset($this->waiting[$nick]);
    if(count($this->waiting) > 0) {
      $players = array_keys($this->waiting);
      $this->mChan("$nick has chosen to remain in the balloon. Still waiting on: ".implode(', ', $players).".");
    } else {
      $this->mChan("$nick has chosen to remain in the balloon.");
      if($this->phase == 'solo') {
        $this->mChan($this->currentPlayer." The skills you require are: ".implode(", ", $this->required).".");
        $this->nUser($this->currentPlayer, "Your cards: ".implode(", ", $this->hands[$this->currentPlayer]).".");
      }
      else {
        $this->mChan($this->currentPlayer." everyone has made their choice. Skills required: ".implode(", ", $this->required).". Please !pilot or !rainbow.");
        $this->nUser($this->currentPlayer, "Your cards: ".implode(", ", $this->hands[$this->currentPlayer]).".");
      }
      $this->phase = 'pilot';
    }
  }
  function cmdPilot($nick, $rainbow) {
    if(!($this->started)) {
      $this->mChan("$nick: No game has started yet.");
      return;
    }
    if($this->currentPlayer != $nick) {
      $this->mChan("$nick: Only the pilot (".$this->currentPlayer.") can pilot the balloon.");
      return;
    }
    if($this->phase != 'pilot') {
      $this->mChan("$nick: Everyone needs to make their choices first.");
      return;
    }
    $required = array_count_values($this->required);
    unset($required['Blank']);
    $hand = array_count_values($this->hands[$nick]);
    if($rainbow) {
      if(!(isset($hand['Rainbow']))) {
        $this->mChan("$nick: You look everywhere for a rainbow amongst the clouds, but alas find nothing.");
        return;
      }
      $required = array('Rainbow' => 1);
    }
    $valid = true;
    foreach($required as $key => $val) {
      if(!(isset($hand[$key]))) {
        $valid = false;
        break;
      }
      if($hand[$key] < $val) {
        $valid = false;
        break;
      }
    }
    if($valid) {
      $discard = array();
      foreach($this->hands[$nick] as $idx => $card) {
        if(!(isset($required[$card]))) continue;
        if($required[$card] == 0) continue;
        $discard[] = $idx;
        $this->discard[] = $card;
        $required[$card]--;
      }
      foreach($discard as $idx) unset($this->hands[$nick][$idx]);
      $this->currentCloud++;
      $this->mChan("$nick pilots the balloon like a boss. Welcome to cloud {$this->currentCloud}. This cloud is worth ".$this->points($this->cloudPoints[$this->currentCloud]).".");
      if($this->currentCloud == 9) {
        $players = array_keys($this->roundPlayers);
        $this->mChan("By making it to cloud 9, everyone still on board (".implode(", ", $players).") earns 25 points, has a wonderful ride, and then lands safely back to the ground.");
        foreach($this->roundPlayers as $player => $score) {
          $this->players[$player] += 25;
        }
        $this->scores();
        $winner = $this->checkWinner();
        if($winner != 'none') {
          $this->mChan("$winner has won the game with a score of ".$this->players[$winner]." points. The game is now over. Please !join to start a new one.");
          $this->resetVars();
          return;
        } 
        $this->roundPlayers = $this->players;
        foreach($this->roundPlayers as $player => $score) {
          $this->hands[$player][] = $this->drawCard();
          sort($this->hands[$player]);
          $this->nUser($player, "Your hand is: ".implode(', ', $this->hands[$player]).".");
        }
        $this->currentCloud = 1;
      } 
      $this->nextPlayer();
      $this->waiting = $this->roundPlayers;
      unset($this->waiting[$this->currentPlayer]);
      $this->rollDice($this->cloudDice[$this->currentCloud]);
      if(count($this->waiting) == 0) {
        $this->waiting[$this->currentPlayer] = $this->currentPlayer;
        $this->mChan($this->currentPlayer." is the current pilot with ".count($this->hands[$this->currentPlayer])." skills, and the only person left on board. Please !jump or !stay.");
        $this->phase = 'solo';
      } else {
        $players = array_keys($this->waiting);
        $this->mChan($this->currentPlayer." is the current pilot with ".count($this->hands[$this->currentPlayer])." skills. Skills required: ".implode(', ', $this->required).". ".implode(', ', $players)." please !jump or !stay.");
        $this->phase = 'waiting';
      }
    } else {
      $this->mChan("$nick pretends like they know what they're doing, crashing the balloon into the ground. Way to go $nick.");
      $this->scores();
      $winner = $this->checkWinner();
      if($winner != 'none') {
        $this->mChan("$winner has won the game with a score of ".$this->players[$winner]." points. The game is now over. Please !join to start a new one.");
        $this->resetVars();
        return;
      } 
      $this->newRound();
    }
  }
  function scores() {
    $scores = array();
    foreach($this->players as $player => $score) $scores[] = "$player ($score)";
    $this->mChan("Current Scores: ".implode(", ", $scores).".");
  }
  function checkWinner() {
    $winner = 'none';
    $bestScore = 0;
    foreach($this->players as $player => $score) {
      if($score >= 50 && $score > $bestScore) {
        $winner = $player;
        $bestScore = $score;
      }
      if($score >= 50 && $score == $bestScore) {
        if($this->hands[$player] > $this->hands[$winner]) $winner = $player;
      }
    }
    return $winner;
  }
  function drawCard() {
    if(count($this->deck) == 0) {
      $this->deck = $this->discard;
      $this->discard = array();
      shuffle($this->deck);
    }
    return array_shift($this->deck);
  }
  function newRound() {
    $this->roundPlayers = $this->players;
    foreach($this->roundPlayers as $player => $score) {
      $this->hands[$player][] = $this->drawCard();
      sort($this->hands[$player]);
      $this->nUser($player, "Your hand is: ".implode(', ', $this->hands[$player]).".");
    }
    $this->currentCloud = 1;
    $this->nextPlayer();
    $this->rollDice($this->cloudDice[$this->currentCloud]);
    $this->waiting = $this->roundPlayers;
    unset($this->waiting[$this->currentPlayer]);
    $this->phase = 'waiting';
    $this->mChan($this->currentPlayer." is the current pilot with ".count($this->hands[$this->currentPlayer])." skills, and the balloon is at cloud {$this->currentCloud}. Skills required: ".implode(', ', $this->required).". Everyone please !jump or !stay.");
  }
}
