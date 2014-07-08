<?php

/*
http://bgg.cc/filepage/3554/topsecretspiesheimlich-uspdf

This is only the basic game.
*/

class heimlichPlugin implements pluginInterface {
var $config;
var $socket;

var $started;
var $channel;
var $players;
var $agentLocations;
var $agentScores;
var $safe;
var $mustScore;

var $roll;
var $phase;

var $locations = array( 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, -3);
var $letters = array("A", "B", "C", "D", "E", "F", "G");


/**
Called when plugins are loaded
**/
function init($config, $socket) {
//	list($usec, $sec) = explode(' ', microtime());
//	mt_srand((float) $sec + ((float) $usec * 100000));
	$this->config = $config;
	$this->socket = $socket;
	$this->resetVars();
}

function resetVars() {
	$this->started = false;
	$this->channel = '#playHeimlich';
	$this->players = array();
	$this->currentPlayer = '';
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
	switch( $cmd ) {
		case "!help":
			$this->cmdHelp($from);
			break;
		case "!rules":
			$this->cmdRules($from);
			break;
		case "!join":
			$this->cmdJoin($from);
			break;
		case "!start":
			$this->cmdStart($from, $tmp);
			break;
		case "!move":
			$this->cmdMove($from, $tmp);
			break;
		case "!safe":
			$this->cmdSafe($from, $tmp);
			break;

		case "!bid":
		case "!sell":
			$this->mChan("$nick: Wrong game. Try #PlayForSale, maybe.");
			break;
		case "!stay":
		case "!jump":
			$this->mChan("$nick: Wrong game. Try #PlayCloud9, maybe.");
			break;
	}

}

function onNick($from, $to) {
	// move all of a player's stuff on a nick change
	if(isset($this->players[$from])) {
		$this->players[$to] = $this->players[$from];
		unset($this->players[$from]);
		if($this->currentPlayer == $from) $this->currentPlayer = $to;

	}
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
	if(!(isset($tmp[1]))) return; //continue;
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
	$this->nUser($nick, "!rules - Shows the rules for Heimlich & Co.");
	$this->nUser($nick, "!join - Join a game.");
	$this->nUser($nick, "!start - Start a new game (after players have joined).");
	$this->nUser($nick, "!move <agent><spaces> <agent><spaces> - Move agent(s).");
	$this->nUser($nick, "!safe <location> - Move the safe ($) to <location>.");
}
function cmdRules($nick) {
	$this->nUser($nick, "This is an IRC implementation of the game Heimlich & Co.");
	$this->nUser($nick, "When no game is in progress, anyone can !join a new game. Once at least 2 players have joined, and no more than 7, anyone in the game can !start it to begin.");
	$this->nUser($nick, "At the beginning of the game, a facedown Agent card is dealed to each player. That player should keep his identity secret as long as possible, since on each player's turn, that player can move one or more agents.");

	$this->nUser($nick, "On a player's turn, that player rolls a 6-sided die with faces 6, 5, 4, 3, 2, and 1-3. The 1-3 face can be used as 1, 2, or 3 movement points, but all other rules must be used exactly.");
	$this->nUser($nick, "The player moves his agent, and/or any of the others, to a total number of movement points matching the roll of the die. For example, '!move A3 B2' moves A three spaces, and B two, on a roll of 5.");
	$this->nUser($nick, "If after moving agents, one or more of them have been moved into the same space as the safe (represented by |$|), a scoring situation is started.");
	$this->nUser($nick, "In a scoring situation, each agent is awarded points based on the number of its space. After scoring all agents, the active player must move the safe to a new location.");

	$this->nUser($nick, "The game ends when an agent scores 42 points or more. At that point, the player with that agent reveals it and wins. It is possible the winning agent piece belongs to none of the players.");
	$this->nUser($nick, "Complete rules, including the Top Secret variant not playable here, are available from Rio Grande Games: http://riograndegames.com/getFile.php?id=190.");
}

function cmdStart($nick, $optionReq) {
	if($this->started) {
		$this->mChan("$nick: Sorry, a game is already in progress. Please wait till it finishes to begin a new one.");
		return;
	}
	if(!isset($this->players[$nick])) {
		$this->mChan("$nick: You must be in the current game to start it.");
		return;
	}
	$playerCount = count($this->players);
	if($playerCount < 2) {
		$players = implode(', ', $this->players);
		if($players == '') $players = '(none)';
		$this->mChan("A minimum of 2 players is required to start the game. Current players are: $players. Please use !join to join this game.");
		return;
	}

	$agentKeys = array( "A", "B", "C", "D", "E" );
	// use the agents based on the player count
	switch( $playerCount ) {
		case 2:
			// do nothing
			break;
		default: // case 4-7
			$agentKeys[] = "G";
			// no break!
		case 3:
			$agentKeys[] = "F";
			break;
	}

	// shuffle the keys
	shuffle( $agentKeys );

//	$this->mChan("Agent order: " .implode(', ', $agentKeys));


	$this->players = $this->shufflePlayers( $this->players );

	$players = implode(', ', array_keys($this->players));
	$this->mChan("$nick has started the game! The turn order is: $players.");

	$this->started = true;

	// place all agents at 0
	foreach( $agentKeys as $agent ) {
		$this->agentScores[$agent] = 0;
		$this->agentLocations[$agent] = 0;
	}

	$this->safe = 7;

	$this->showLocations();

	$agentsAssigned = 0;
	foreach( $this->players as $player => $agent ) {
		$this->players[$player] = $agentKeys[$agentsAssigned];
		$agentsAssigned++;
		$this->noticeAgent($player);
	}

	$this->mustScore = false;

	$this->nextPlayer();
	$this->doRoll();
}

function doRoll() {
	$this->roll = $this->getRoll();
	$roll = ($this->roll !== 1  ? $this->roll : "1-3");
	$this->mChan("{$this->currentPlayer}: You roll $roll. Please !move one or more agents.");
	$this->phase = 'move';
}

function getRoll() {
	return mt_rand(1, 6);
}

function shufflePlayers( $list ) {
	if (!is_array($list)) return $list;

	$keys = array_keys($list);
	shuffle($keys);
	$random = array();
	foreach ($keys as $key) {
		$random[$key] = $list[$key];
	}
	return $random;
}

function cmdJoin($nick) {
	if($this->started) {
		$this->mChan("$nick: Sorry, a game is already in progress. Please wait until it finishes to begin a new one.");
		return;
	}
	if(isset($this->players[$nick])) {
		$this->mChan("$nick: You have already joined the current game.");
		return;
	}
	if(count($this->players) == 7) {
		$this->mChan("$nick: Sorry, the player limit of 7 has been reached. Please wait for the next game.");
		return;
	}
	$this->players[$nick] = "";

	$players = implode(', ', array_keys($this->players));
	$this->mChan("$nick has joined the game. Current players are now: $players");
}

function noticeAgent($nick) {
	$agent = $this->players[$nick];
	$this->nUser($nick, "You are Agent $agent. Your score is {$this->agentScores[$agent]}.");
}

function doScoring() {
	$fortyTwo = false;
	foreach( $this->agentLocations as $agent => $location ) {
		$this->agentScores[$agent] += $location;
		if($this->agentScores[$agent] > 41) {
			$fortyTwo = true;
		}
	}

	$this->showScores();

//	$this->showLocations();

	if( $fortyTwo ) {
		// game is over.
		$this->endGame();

	} else {
		$this->mChan("{$this->currentPlayer}: Please select a new location for the safe (e.g. !safe 5).");
		$this->phase = 'safe';
		$this->mustScore = false;
	}
}

function showScores() {
	arsort( $this->agentScores );

	$scoreString = "";
	foreach( $this->agentScores as $agent => $score ) {
		$scoreString .= "$agent: $score ";
	}
	$this->mChan("Current scores: $scoreString");

	foreach( $this->players as $nick => $agent ) {
		$this->noticeAgent($nick);
	}
}

function showLocations() {
	$agents = array();

	ksort( $this->agentLocations);

	foreach( $this->locations as $location ) {
		$agents[$location] = "";
	}

	foreach( $this->agentLocations as $agent => $location ) {
		$agents[$location] .= $agent;
	}
	$agents[$this->safe] .= "|$|";

	$locationString = "";
	foreach( $this->locations as $location ) {
		$locationString .= "$location[ {$agents[$location]} ] ";
	}
	$this->mChan("$locationString");
}

function cmdSafe($nick, $tmp) {
	if(!($this->started)) {
		//$this->mChan("$nick: No game has started yet.");
		return;
	}

	if(!in_array($nick, array_keys($this->players))) {
		return;
	}

	if($this->phase != 'safe') {
		$this->mChan("$nick: You cannot move the safe now.");
		return;
	}

	if($nick != $this->currentPlayer) {
		$this->mChan("$nick: {$this->currentPlayer} is moving the safe, not you.");
		return;
	}

//	$this->mChan("--> {$tmp[0]} <--");

	$m = $tmp[0]+0;

	if(!in_array($m, $this->locations) || $m == $this->safe) {
		$this->mChan("$nick: That is not a valid location.");
		return;
	}

	$this->safe = $m;

	$this->showLocations();

	$this->phase = 'move';
	$this->nextPlayer();
	$this->doRoll();
}


function cmdMove($nick, $tmp) {
	if(!($this->started)) {
		$this->mChan("$nick: No game has started yet.");
		return;
	}
	if(!in_array($nick, array_keys($this->players))) {
		return;
	}
	if($nick != $this->currentPlayer) {
		$this->mChan("$nick: Please wait your turn.");
		return;
	}

	if($this->phase != 'move' ) {
		$this->mChan("$nick: You cannot move agents right now.");
		return;
	}

//	$this->mChan("Counted: " . count( $tmp));

	$pattern = implode( array_keys($this->agentLocations));

	$moves = array();
	$totalMoves = 0;

	foreach( $tmp as $move ) {
		$item = strtoupper($move);

		if( preg_match( "/[$pattern][1-6]/", $item) !== 1) {
			// NB: [] is an invalid pattern. Fortunately for no items, the previous conditional returns.
			$this->mChan("$nick: That is not a valid move.");
			return;
		}
		$thisMove = substr($move, 1, 1) + 0;

		$totalMoves += $thisMove;
		$moves[strtoupper(substr($move, 0, 1))] = $thisMove;
	}

	if( ($this->roll > 1 && $totalMoves != $this->roll) || ($this->roll == 1 && ($totalMoves < 1 || $totalMoves > 3))) {
		$this->mChan("$nick: Your moves must match your roll. Move one or more agents {$this->roll}.");
		return;
	}

	foreach( $moves as $agent => $move ) {
		$this->moveAgent($agent, $move);
	}

	$this->showLocations();

	if( $this->mustScore ) {
		$this->doScoring();

	} else {

		$this->nextPlayer();
		$this->doRoll();
	}
}

function moveAgent($agent, $amount) {
	$before = $this->agentLocations[$agent];
	if ($before === -3) {
		$before = -1;
	}

	$after = $before + $amount;
	if ($after > 10) {
		if( $after !== 11 ) {
			$after -= 12;
		} else {
			$after = -3;
		}
	} /*else {
		$this->agentLocations[$agent] = $after;
	}*/
	if( $after === $this->safe) {
		$this->mustScore = true;
	}
	$this->agentLocations[$agent] = $after;
}

function endGame() {
	$this->showFinalScores();
	$this->resetVars();
	sleep( 1 );
	$this->mChan("A new game can now begin. Please !join if you would like to play again.");
}

function showFinalScores() {
	arsort( $this->agentScores );

	$playerAgents = array();

	foreach($this->players as $player => $agent) {
		$playerAgents[$agent] = " ($player)";
	}

	$this->mChan("Final scores:");

	foreach( $this->agentScores as $agent => $score ) {
		$this->mChan("$agent: $score{$playerAgents[$agent]}");
	}
	sleep( 1 );
}
}

/*function sendNotice($socket, $channel, $msg) {
	if(strlen($msg) > 2) { //Avoid sending empty lines to server, since all data should contain a line break, 2 chars is minimum
		$msg = prettify($msg);
		sendData($socket, "NOTICE {$channel} :{$msg}");
	}
}*/
