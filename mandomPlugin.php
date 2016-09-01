<?php

/*
http://bgg.cc/filepage/104219/english-rules-v1-1

Items:
Chainmail 		HP +5
Knight Shield	HP +3
Dragon Lance	Receive	no damage from dragons.
Holy Grail 		Receive	no damage from undead (even number) monsters.
Torch			Receive no damage from monsters with power of 3 or lower.
Vorpal Sword 	Before entering the dungeon, name one monster. You will not receive damage from that monster.


Monster Cards:
1	Goblin
1	Goblin
2	Skeleton
2	Skeleton
3	Orc
3	Orc
4	Vampire
4	Vampire
5	Golem
5	Golem
6	Lich
7	Demon
9	Dragon
*/

class mandomPlugin implements pluginInterface {
var $config;
var $socket;

var $started;
var $channel;
var $players;
var $allPlayers;
var $currentPlayer;
var $roundPlayers;

var $monsters = array( "", // 0
	"Goblin", // 1
	"Skeleton", // 2
	"Orc", // 3
	"Vampire", // 4
	"Golem", // 5
	"Lich", // 6
	"Demon", // 7
	"", // 8
	"Dragon", // 9
);

var $itemNames = array(
		"c" => "Chainmail",
		"d" => "Dragon Lance",
		"h" => "Holy Grail",
		"k" => "Knight Shield",
		"t" => "Torch",
		"v" => "Vorpal Sword"
	);
var $drawDeck;
var $items;
var $dungeonDeck;

var $hands;
var $phase;


/**
Called when plugins are loaded
**/
function init($config, $socket) {
	list($usec, $sec) = explode(' ', microtime());
	mt_srand((float) $sec + ((float) $usec * 100000));
	$this->config = $config;
	$this->socket = $socket;
	$this->resetVars();
}

function resetVars() {
	$this->started = false;
	$this->channel = '#PlayDungeonOM';
	$this->players = array();
	$this->allPlayers = array();
	$this->roundPlayers = array();
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
		case "!items":
			$this->cmdItems($from);
			break;
		case "!monsters":
			$this->cmdMonsters($from);
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
		case "!pass":
			$this->cmdPass($from);
			break;
		case "!draw":
			$this->cmdDraw($from);
			break;
		case "!add":
			$this->cmdAdd($from);
			break;
		case "!take":
			$this->cmdTake($from, $tmp);
			break;
		case "!vorpal":
			$this->cmdVorpal($from, $tmp);
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
	if(isset($this->allPlayers[$from])) {
		$this->players[$to] = $this->players[$from];
		$this->roundPlayers[$to] = $this->roundPlayers[$from];
		$this->allPlayers[$to] = $this->allPlayers[$from];
		$this->playerHand[$to] = $this->playerHand[$from];
		unset($this->players[$from]);
		unset($this->roundPlayers[$from]);
		unset($this->allPlayers[$from]);
		unset($this->playerHand[$from]);
		if($this->currentPlayer == $from) $this->currentPlayer = $to;

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
function cmdMonsters($nick) {
	$this->nUser($nick, "Monster Cards:");
	$this->nUser($nick, "2x Goblin (1), defeated by Torch");
	$this->nUser($nick, "2x Skeleton (2), defeated by Torch or Holy Grail");
	$this->nUser($nick, "2x Orc (3), defeated by Torch");
	$this->nUser($nick, "2x Vampire (4), defeated by Holy Grail");
	$this->nUser($nick, "2x Golem (5)");
	$this->nUser($nick, "1x Lich (6), defeated by Holy Grail");
	$this->nUser($nick, "1x Demon (7)");
	$this->nUser($nick, "1x Dragon (9), defeated by Dragon Lance");
}

function cmdItems($nick) {
	$this->nUser($nick, "Items:");
	$this->nUser($nick, "Chainmail: HP +5");
	$this->nUser($nick, "Knight Shield: HP +3");
	$this->nUser($nick, "Dragon Lance: Receive no damage from Dragons (9).");
	$this->nUser($nick, "Holy Grail: Receive no damage from undead (even number) monsters.");
	$this->nUser($nick, "Torch: Receive no damage from monsters with power of 3 or lower.");
	$this->nUser($nick, "Vorpal Sword: Before entering the dungeon, name one monster. You will not receive damage from that monster.");
}

function cmdHelp($nick) {
	$this->nUser($nick, "!rules - Shows the rules for Dungeon of Mandom.");
	$this->nUser($nick, "!items - Describes the items.");
	$this->nUser($nick, "!monsters - Lists the monsters.");
	$this->nUser($nick, "!join - Join a game.");
	$this->nUser($nick, "!start - Start a new game (after players have joined).");
	$this->nUser($nick, "!pass - Do not draw a monster card. You will not be entering the dungeon this round.");
	$this->nUser($nick, "!draw - Draw the top card.");
	$this->nUser($nick, "!add - Add your drawn card to the dungeon deck.");
	$this->nUser($nick, "!take <item> - Keep your drawn card, and remove an item from the hero.");
}
function cmdRules($nick) {
	$this->nUser($nick, "This is an IRC implementation of the game Dungeon of Mandom.");
	$this->nUser($nick, "When no game is in progress, anyone can !join a new game. Once at least 2 players have joined, and no more than 4, anyone in the game can !start it to begin.");
	$this->nUser($nick, "The game is played over a number of rounds, until all players have been eliminated or one player has won.");
	$this->nUser($nick, "At the beginning of a round, there is a hero with six items equipped, an empty Dungeon, and a draw deck of Monster cards. On your turn, you choose whether to draw a card (and thus stay in the running) or pass (and sit out the round).");
	$this->nUser($nick, "If you draw a card, you then choose whether to add it to the Dungeon, or to hold it in your hand - and remove one of the hero's items. The hero will not be able to use that item when entering the dungeon this round, even if it is you.");
	$this->nUser($nick, "If you pass, you are done for this round. If all players but 1 have passed, that last player must enter the dungeon, with whatever items are left.");
	$this->nUser($nick, "The player entering the jungle turns over the Dungeon cards one by one. If a monster is not handled by an item, the hero takes that monster's number in damage to HP. If the hero has 1 HP or more after all the monsters, he has survived the dungeon.");
	$this->nUser($nick, "If the total value of the Monster cards (not otherwise handled by items) is greater than the hero's total HP, that player has died once. Once the hero is dead, or the dungeon conquered, a new round begins.");

	$this->nUser($nick, "The first player to survive entering the dungeon twice wins - OR - If all players but one are eliminated, the remaining player wins.");
}
function cmdStart($nick, $optionReq) {
	if($this->started) {
		$this->mChan("$nick: Sorry, a game is already in progress. Please wait till it finishes to begin a new one.");
		return;
	}
	if(!isset($this->allPlayers[$nick])) {
		$this->mChan("$nick: You must be in the current game to start it.");
		return;
	}
	if(count($this->allPlayers) < 2) {
		$players = implode(', ', $this->allPlayers);
		if($players == '') $players = '(none)';
		$this->mChan("A minimum of 2 players is required to start Dungeon of Mandom. Current players are: $players. Please use !join to join this game.");
		return;
	}

	$this->allPlayers = $this->shufflePlayers( $this->allPlayers );

	$players = implode(', ', array_keys($this->allPlayers));
	$this->mChan("$nick has started the game! The turn order is: $players.");

	$this->started = true;

	$this->players = $this->allPlayers;

	$this->newRound( false );

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

function newRound( $curr ) {

	$this->playerHand = array();
	$this->roundPlayers = array();

	foreach($this->players as $nick => $score) {
		$this->roundPlayers[$nick] = true;
		$this->playerHand[$nick] = array();
	}

	$this->pickedCard = 0;

	$this->drawDeck = array(6, 7, 9);
	for($i=1; $i<=5; $i++) {
		$this->drawDeck[] = $i;
		$this->drawDeck[] = $i;
	}
	shuffle($this->drawDeck);

	$this->dungeonDeck = array();

	$this->phase = 'draw';

	$this->items = array(
		// if an item is true, it is usable
		"c" => true,
		"d" => true,
		"h" => true,
		"k" => true,
		"t" => true,
		"v" => true
	);

	if( !$curr) {
		$this->nextPlayer();
	}

	$this->mChan("New round. Cards in the Draw Deck: ".count($this->drawDeck));
	$this->mChan($this->currentPlayer.", you're up. Please draw a card, or pass.");

	$this->noticeHand($nick);

}

function cmdJoin($nick) {
	if($this->started) {
		$this->mChan("$nick: Sorry, a game is already in progress. Please wait until it finishes to begin a new one.");
		return;
	}
	if(isset($this->allPlayers[$nick])) {
		$this->mChan("$nick: You have already joined the current game.");
		return;
	}
	if(count($this->allPlayers) == 4) {
		$this->mChan("$nick: Sorry, the player limit of 4 has been reached. Please wait for the next game.");
		return;
	}
	$this->allPlayers[$nick] = 0;

	$this->hands[$nick] = array();

	$players = implode(', ', array_keys($this->allPlayers));
	$this->mChan("$nick has joined the game. Current players are now: $players");
}

function noticeHand($nick) {
	$cards = array();

	for($m=0; $m<count($this->playerHand[$nick]); $m++) {
		$thisCard = $this->playerHand[$nick][$m];

		if( in_array($thisCard, array_keys($this->itemNames ))) {
			$cards[] = $this->itemNames[$thisCard];
		} else {
			array_unshift( $cards, "{$this->monsters[$thisCard]} ($thisCard)" );
		}
	}
	if(count($cards) === 0) {
		$this->nUser($this->currentPlayer, "You hold no cards.");
	} else {
		$this->nUser($this->currentPlayer, "You hold: ". implode( ", ", $cards));
	}

}

function cmdDraw($nick) {
	if(!($this->started)) {
		$this->mChan("$nick: No game has started yet.");
		return;
	}
	if(!in_array($nick, array_keys($this->allPlayers))) {
		return;
	}
	if($nick != $this->currentPlayer) {
		$this->mChan("$nick: Please wait your turn.");
		return;
	}
	if($this->phase != 'draw') {
		$this->mChan("$nick: Sorry, this is not the time to draw a card.");
		return;
	}
	if(count($this->drawDeck)==0) {
		$this->mChan("$nick: No more monster cards are available. You must pass.");
		return;
	}

	$items = array();
	foreach( $this->itemNames as $key => $name ) {
		if($this->items[$key] !== 0) {
			$items[] = $name;
		}
	}

	if(count($items)) {
		$this->mChan("$nick: !add that card to the Dungeon, or keep it and !take an item. Current items are: " . implode( ", ", $items));
	}
	else {
		// no items are left. Player must add the monster to the dungeon.
		$this->mChan("$nick: !add that card to the Dungeon. There are no items remaining.");
	}

	$topCard = array_pop($this->drawDeck);

	$this->noticeHand($this->currentPlayer);

	$this->nUser($this->currentPlayer, "You drew a ".$this->monsters[$topCard]. " (" . $topCard . ") card. Choose whether to !add it to the Dungeon or to keep it (and !take an item).");

	$this->pickedCard = $topCard;

	$this->phase = 'monster card';

}

function cmdAdd($nick) {
	if(!($this->started)) {
		$this->mChan("$nick: No game has started yet.");
		return;
	}
	if(!in_array($nick, array_keys($this->allPlayers))) {
		return;
	}
	if($nick != $this->currentPlayer) {
		$this->mChan("$nick: Please wait your turn.");
		return;
	}
	if($this->phase != 'monster card') {
		//$this->mChan("$nick: Sorry, the Dungeon is not currently being set up.");
		return;
	}
	$this->dungeonDeck[] = $this->pickedCard;
	//$this->mChan("Dungeon Deck: " . implode( ", ", $this->dungeonDeck));
	$this->mChan("$nick has added a monster to the Dungeon. It now contains ".count($this->dungeonDeck)." monster".(count($this->dungeonDeck) > 1 ? "s." : "."));

	$this->nextPlayer();
	$this->mChan($this->currentPlayer.", you're up. Please draw a card, or pass.");

	$this->phase = 'draw';

	$this->noticeHand($this->currentPlayer);
}

function cmdTake($nick, $tmp) {
	if(!($this->started)) {
		$this->mChan("$nick: No game has started yet.");
		return;
	}
	if(!in_array($nick, array_keys($this->allPlayers))) {
		return;
	}
	if($nick != $this->currentPlayer) {
		$this->mChan("$nick: Please wait your turn.");
		return;
	}


	$items = array();
	$pattern = '';

	foreach( $this->itemNames as $key => $name ) {
		if($this->items[$key] !== 0) {
			$items[] = $name;
			$pattern .= $key;
		}
	}

	if($this->phase != 'monster card' || count($items) == 0) {
		$this->mChan("$nick: You cannot take an item right now.");
		return;
	}

	$letter = strtolower(substr($tmp[0], 0, 1));

	if( preg_match( "/[$pattern]/", $letter) !== 1) {
		// NB: [] is an invalid pattern. Fortunately for no items, the previous conditional returns.
		$this->mChan("$nick: That is not a valid item.");
		return;
	}

	array_push($this->playerHand[$nick], $this->pickedCard);
	array_push($this->playerHand[$nick], $letter);

	$this->mChan("$nick has removed the " . $this->itemNames[$letter] . ".");

	$this->items[$letter] = 0;

	$this->nextPlayer();
	$this->mChan($this->currentPlayer.", you're up. Please draw a card, or pass.");

	$this->phase = 'draw';

	$this->noticeHand($this->currentPlayer);
}

function cmdPass($nick) {
	if(!($this->started)) {
		$this->mChan("$nick: No game has started yet.");
		return;
	}
	if(!in_array($nick, array_keys($this->allPlayers))) {
		return;
	}
	if($nick != $this->currentPlayer) {
		$this->mChan("$nick: Please wait your turn.");
		return;
	}
	if($this->phase != 'draw') {
		$this->mChan("$nick: You cannot pass now.");
		return;
	}
//		$this->mChan("before unset players: ".implode(", ", array_keys($this->roundPlayers)).".");
//		$this->mChan("after unset players: ".implode(", ", array_keys($this->roundPlayers)).".");



  if(count(array_keys($this->roundPlayers)) == 2) {
    unset($this->roundPlayers[$nick]);
    // all but 1 player has passed. Dungeon time!
    $lastPlayer = array_keys($this->roundPlayers);
    $hero = array_shift($lastPlayer);
    $this->mChan("$nick has passed. Now $hero goes into the dungeon!");

    $this->currentPlayer = $hero;

    if($this->items["v"] !== 0 && count($this->dungeonDeck) > 0) {
      // ask for Vorpal input, if need be.

      $this->phase = 'vorpal';
      $this->mChan("$hero: You have the Vorpal Sword. Please choose a Monster type that will deal you no damage:");
      $this->mChan("1-Goblin, 2-Skeleton, 3-Orc, 4-Vampire, 5-Golem, 6-Lich, 7-Demon, 9-Dragon (e.g. !vorpal 5)");

    } else {
      // actually crawl the dungeon

      $this->enterDungeon($hero, false);
    }
  }
  else {
    $this->mChan("$nick has passed. Remaining players: ".implode(", ", array_keys($this->roundPlayers)).".");
    $this->nextPlayer();
    unset($this->roundPlayers[$nick]);
    $this->mChan($this->currentPlayer.", you're up. Please draw a card, or pass.");
    $this->phase = 'draw';
  }
}

function enterDungeon($nick, $vorpal) {
  $leftoverMonsters = array();
  $this->dungeonDeck = array_reverse($this->dungeonDeck);

  // determine player HP.

  $yourHP = 3 + ($this->items["k"] * 3) + ($this->items["c"] * 5);
  $this->mChan("$nick: You start with $yourHP hit points.");

  // assess each monster.

  for( $i=0; $i<count($this->dungeonDeck); $i++) {

    $hpBefore = $yourHP;

    $thisMonster = $this->dungeonDeck[$i];
    $monsterText = $this->monsters[$thisMonster];

    $this->currentPlayer = $nick;

    if( $hpBefore > 0 ) {

      if( $vorpal && $vorpal == $thisMonster ) {
        $this->mChan("$nick: You ignore the $monsterText with your Vorpal Sword.");
      } else {
        // 9
        $yourHP -= ($thisMonster==9) * !$this->items["d"] * $thisMonster;

        // 7 always hits
        $yourHP -= ($thisMonster==7) * $thisMonster;

        // 6, 4, 2
        $yourHP -= ($thisMonster%2==0) * !$this->items["h"] * $thisMonster;

        // 5 always hits
        $yourHP -= ($thisMonster==5) * $thisMonster;

        // 3, 2, 1
        $yourHP -= ($thisMonster<4) * !$this->items["t"] * $thisMonster;
      }
      if( $hpBefore == $yourHP ) {
        $this->mChan("$nick: You take no damage from the $monsterText.");
      } else {
        if( $yourHP <= 0) {
          $this->mChan("$nick: The $monsterText damages you for $thisMonster, killing you. You have died.");
        } else {
          $this->mChan("$nick: The $monsterText damages you for $thisMonster. You have $yourHP hit points left.");
        }
      }
    } else {
      // you're already dead
      $leftoverMonsters[] = $monsterText;
    }
  }

  if( count( $leftoverMonsters) > 0 ) {
    $this->mChan("$nick: The dungeon also contained: " . implode( ", ", $leftoverMonsters));
  }

  $score = $this->allPlayers[$nick];


  if( $yourHP > 0) {
    $this->mChan("$nick: You survived the dungeon!");

    $this->allPlayers[$nick] = sign($score) * (10 + abs($score));

  } else {

    if( $score < 0) {
      // remove dead player, if need be.
      $this->mChan("$nick has died twice and is removed from the game.");

      unset( $this->players[$nick]);
      unset( $this->roundPlayers[$nick]);
      $this->nextPlayer();

    } else {
      $this->allPlayers[$nick] = -$score;
    }
    $this->allPlayers[$nick] -= 1;
  }

  // end the game, if need be.

  if( abs( $this->allPlayers[$nick] ) >= 20 ) {
    //	$this->mChan("$nick has survived the dungeon twice and wins the game.");

    $this->endGame();
    return;
  }

  if( count(array_keys($this->players)) == 1 ) {
    $players = array_keys($this->players);
    $this->mChan("Only {$player[0]} is left standing, and wins the game.");

    $this->endGame();
    return;
  }

  $this->scores(false);

  sleep( 1 );

  // start a new round
  $this->newRound($this->currentPlayer);
}

function scores($final) {
  foreach( $this->allPlayers as $nick => $score ) {
    //		$this->mChan( "$nick $score");

    switch( $score ) {
    case 10:
      $this->mChan("$nick survived once.");
      break;
    case 20:
      $this->mChan("$nick survived twice, winning the game.");
      break;
    case -21:
      $this->mChan("$nick died once, but survived twice, winning the game.");
      break;
    case -1:
      $this->mChan("$nick died once.");
      break;
    case -2:
      $this->mChan("$nick died twice.");
      break;
    case -11:
      $this->mChan("$nick survived once, and died once.");
      break;
    case -12:
      $this->mChan("$nick survived once, and died twice.");
      break;

    default:
      $this->mChan("$nick " . ($final ? "never": "hasn't") . " entered the dungeon.");
    }

  }
  sleep(1);
}

function endGame() {
  $this->scores(true);
  $this->resetVars();
  $this->mChan("A new game can now begin. Please !join if you would like to play again.");
}

function cmdVorpal($nick, $tmp) {
  if(!($this->started)) {
    //$this->mChan("$nick: No game has started yet.");
    return;
  }

  if(!in_array($nick, array_keys($this->allPlayers))) {
    return;
  }

  if($this->phase != 'vorpal') {
    $this->mChan("$nick: Now is not the time for the Vorpal Sword.");
    return;
  }

  if($nick != $this->currentPlayer) {
    $this->mChan("$nick: {$this->currentPlayer} is using the Vorpal Sword, not you.");
    return;
  }

  $m = $tmp[0]+0;

  if( $m > 9 || $m <= 0 || $m == 8) {
    $this->mChan("$nick: That is not a valid monster. It must be 1, 2, 3, 4, 5, 6, 7, or 9.");
    return;
  }

  $this->enterDungeon($nick, $m);

}
}
/*
function sendNotice($socket, $channel, $msg) {
  if(strlen($msg) > 2) { //Avoid sending empty lines to server, since all data should contain a line break, 2 chars is minimum
    $msg = prettify($msg);
    sendData($socket, "NOTICE {$channel} :{$msg}");
  }
}
 */
function sign( $x ) {
  // dumb, yes. But it gets the job done.
  if ( $x >= 0 ) {
    return 1;
  } else {
    return -1;
  }
}
