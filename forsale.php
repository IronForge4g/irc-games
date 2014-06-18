<?php

class forsale implements pluginInterface {
  var $config;
  var $socket;

  var $started;
  var $channel;
  var $players;
  var $numPlayers;
  var $currentPlayer;
  var $roundPlayers;
  var $tableCards;
  var $hands;
  var $bids;
  var $houseDeck;
  var $cashDeck;
  var $phase;
  var $currentBid;

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
    $this->channel = '#PlayForSale';
    $this->players = array();
    $this->roundPlayers = array();
    $this->numPlayers = 0;
    $this->hands = array();
    $this->bids = array();
    $this->currentPlayer = '';
    $this->tableCards = array();
    $this->phase = 'buy';
    $this->currentBid = 0;
    $this->houseDeck = array();
    for($i=1;$i<=30;$i++) $this->houseDeck[] = $i;
    shuffle($this->houseDeck);
    $this->cashDeck = array(0, 0);
    for($i=2;$i<=15;$i++) {
      $this->cashDeck[] = $i;
      $this->cashDeck[] = $i;
    }
    shuffle($this->cashDeck);
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
    else if($cmd == "!pass") $this->cmdPass($from);
    else if($cmd == "!sell") $this->cmdSell($from, $tmp);
  }
  function onNick($from, $to) {
    if(isset($this->players[$from])) {
      $this->players[$to] = $this->players[$from];
      $this->hands[$to] = $this->hands[$from];
      $this->bids[$to] = $this->bids[$from];
      unset($this->players[$from]);
      unset($this->hands[$from]);
      unset($this->bids[$from]);
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
    $this->nUser($nick, "!rules - Show's the rules for For Sale.");
    $this->nUser($nick, "!start - Start a new game of For Sale.");
    $this->nUser($nick, "!join - Join a game.");
    $this->nUser($nick, "!bid <amt> - Increase the current bid.");
    $this->nUser($nick, "!pass - Take the lowest property, for half your bid (rounded up).");
    $this->nUser($nick, "!sell <property> - Puts a property up for sale.");
  }
  function cmdRules($nick) {
    $this->nUser($nick, "For Sale is an IRC implementation of the game For Sale!");
    $this->nUser($nick, "When no game is in progress, anyone can !join a new game. Once there is at least 3 players who have joined, and up to 6 players maximum, anyone in the game can !start it to begin.");
    $this->nUser($nick, "The goal of For Sale is to end up with more money than anyone else. It begins with a purchasing phase, followed by a selling phase.");
    $this->nUser($nick, "During the purchasing phase, you may !bid to increase the highest bid, or !pass. If you !pass, you pay half (rounded up) of YOUR bid to the bank, and take the lowest valued property. When only one property remains, the player who !bid the highest pays the full amount of their bid for that property.");
    $this->nUser($nick, "During the selling phase, each player secretly selects one of their properties to sell. Cash is then distributed, highest amount for the best property, lowest amount for the worst property.");
    $this->nUser($nick, "The player with the most money at the end of the game is the winner.");
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
      $this->mChan("A minimum of 3 players is required to start For Sale. Current players are: $players. Please use !join to join this game.");
      return;
    }

    $players = implode(', ', array_keys($this->players));
    $this->mChan("$nick has started the game! The turn order is: $players.");

    $players = array_keys($this->players);
    $this->currentPlayer = array_shift($players);
    $this->started = true;
    if($this->numPlayers == 3) {
      for($i=0;$i<6;$i++) {
        array_pop($this->houseDeck);
        array_pop($this->cashDeck);
      }
      foreach($this->hands as $nick => $hand) $this->players[$nick] = 18;
    }
    else if($this->numPlayers == 4) {
      for($i=0;$i<2;$i++) {
        array_pop($this->houseDeck);
        array_pop($this->cashDeck);
      }
      foreach($this->hands as $nick => $hand) $this->players[$nick] = 18;
    }
    else {
      foreach($this->hands as $nick => $hand) $this->players[$nick] = 14;
    }

    for($i=0;$i<$this->numPlayers;$i++) $this->tableCards[] = array_shift($this->houseDeck);
    sort($this->tableCards);
    $this->roundPlayers = array();
    foreach($this->players as $nick => $cash) {
      $this->roundPlayers[$nick] = $cash;
      $this->bids[$nick] = 0;
    }
    $this->currentBid = 0;
    $this->mChan("Properties available: ".implode(', ', $this->tableCards));
    $this->mChan($this->currentPlayer.", you're up. Please make the opening bid, or pass to take a property.");
    $this->nUser($this->currentPlayer, "You currently own nothing. Your current bid is 0 dollar(s) and you have ".$this->players[$this->currentPlayer]." dollar(s) left.");
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
    $this->bids[$nick] = 0;
    $this->numPlayers++;
    $players = implode(', ', array_keys($this->players));
    $this->mChan("$nick has joined the game. Current players are now: $players");
  }
  function displayProperty($nick) {
    $arr = $this->hands[$nick];
    $text = array();
    foreach($arr as $let => $prop) {
      $text[] = "$let. $prop";
    }
    return implode(", ", $text);
  }
  function cmdBid($nick, $tmp) {
    if(!($this->started)) {
      $this->mChan("$nick: No game has started yet.");
      return;
    }
    if($nick != $this->currentPlayer) {
      $this->mChan("$nick: Please wait your turn.");
      return;
    }
    if($this->phase != 'buy') {
      $this->mChan("$nick: Sorry, we're done buying. It's time to sell!");
      return;
    }
    if(count($tmp) != 1) {
      $this->mChan("$nick: Please make a valid bid, in the format !bid <number>. (eg. !bid 5)");
      return;
    }
    $bid = str_replace("$", "", $tmp[0]);
    $checkNumber = preg_replace("#[^0-9]+#", "", $bid);
    if($checkNumber != $bid) {
      $this->mChan("$nick: $bid is not a valid bid. Please make a valid bid in the format !bid <number>. (eg. !bid 5)");
      return;
    }
    if($bid <= 0) {
      $this->mChan("$nick: Bids ($bid) must be higher than 0.");
      return;
    }
    if($bid <= $this->currentBid) {
      $this->mChan("$nick: Your bid ($bid) must be higher than the last bid of ".$this->currentBid.".");
      return;
    }
    if($bid > $this->players[$nick]) {
      $this->mChan("$nick: You can't possibly bid that much. Now you've gone and told the channel that you're poor.");
      return;
    }
    $this->currentBid = $bid;
    $this->bids[$nick] = $bid;
    $this->nextPlayer();
    $this->mChan("$nick has raised the bid to $bid dollar(s). Properties remaining are still: ".implode(", ", $this->tableCards).".");
    $this->mChan($this->currentPlayer.", you're up. Please pass or bid.");
    $own = implode(", ", $this->hands[$this->currentPlayer]);
    if($own == "") $own = 'nothing';
    $this->nUser($this->currentPlayer, "You currently own $own. Your current bid is ".$this->bids[$this->currentPlayer]." dollar(s) and you have ".$this->players[$this->currentPlayer]." dollar(s) left.");
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
    if($this->phase != 'buy') {
      $this->mChan("$nick: Sorry, we're done buying. It's time to sell!");
      return;
    }
    $bid = ceil($this->bids[$nick] / 2);
    $this->players[$nick] -= $bid;
    $this->nextPlayer();
    $house = array_shift($this->tableCards);
    $this->hands[$nick][] = $house;
    unset($this->roundPlayers[$nick]);
    sort($this->hands[$nick]);
    $this->mChan("$nick has claimed $house for $bid dollar(s). Properties remaining are now: ".implode(", ", $this->tableCards).".");
    if(count($this->tableCards) == 1) {
      $lastPlayer = array_keys($this->roundPlayers);
      $lastBidder = array_shift($lastPlayer);
      $this->mChan("Only the ".$this->tableCards[0]." remains. ".$lastBidder." takes it for ".$this->currentBid." dollar(s).");
      $this->players[$lastBidder] -= $this->currentBid;
      $this->hands[$lastBidder][] = $this->tableCards[0];
      if(count($this->houseDeck) == 0) {
        $this->mChan("The time for buying is over! Let the time of selling begin!");
        $this->phase = 'sell';
        $this->tableCards = array();
        for($i=0;$i<$this->numPlayers;$i++) $this->tableCards[] = array_shift($this->cashDeck);
        sort($this->tableCards);
        $this->mChan("Cash available: ".implode(', ', $this->tableCards));
        $this->roundPlayers = array();
        foreach($this->players as $nick => $cash) {
          $this->roundPlayers[$nick] = $cash;
          $this->bids[$nick] = '';
          $tmp = $this->hands[$nick];
          shuffle($tmp);
          $this->hands[$nick] = array();
          $letters = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N');
          foreach($tmp as $property) {
            $this->hands[$nick][array_shift($letters)] = $property;
          }
          $this->nUser($nick, "You have ".$this->players[$nick]." dollar(s). Properties left to sell: ".$this->displayProperty($nick));
        }
      } else {
        $this->currentPlayer = $lastBidder;
        $this->tableCards = array();
        for($i=0;$i<$this->numPlayers;$i++) $this->tableCards[] = array_shift($this->houseDeck);
        sort($this->tableCards);
        $this->roundPlayers = array();
        foreach($this->players as $nick => $cash) {
          $this->roundPlayers[$nick] = $cash;
          $this->bids[$nick] = 0;
        }
        $this->currentBid = 0;
        $this->mChan("Properties available: ".implode(', ', $this->tableCards));
        $this->mChan($this->currentPlayer.", you're up. Please make the opening bid, or pass to take a property.");
    $own = implode(", ", $this->hands[$this->currentPlayer]);
    if($own == "") $own = 'nothing';
        $this->nUser($this->currentPlayer, "You currently own $own. Your current bid is ".$this->bids[$this->currentPlayer]." dollar(s) and you have ".$this->players[$this->currentPlayer]." dollar(s) left.");
      }
    }
    else {
      $this->mChan($this->currentPlayer.", you're up. Please pass or bid.");
    $own = implode(", ", $this->hands[$this->currentPlayer]);
    if($own == "") $own = 'nothing';
      $this->nUser($this->currentPlayer, "You currently own $own. Your current bid is ".$this->bids[$this->currentPlayer]." dollar(s) and you have ".$this->players[$this->currentPlayer]." dollar(s) left.");
    }
  }
  function cmdSell($nick, $prop) {
    if(!($this->started)) {
      $this->mChan("$nick: No game has started yet.");
      return;
    }
    if($this->phase != 'sell') {
      $this->mChan("$nick: Selling is for later, for now lets buy stuff!");
      return;
    }
    if(count($prop) != 1) {
      $this->mChan("$nick: Please try to sell a valid property in the format !sell <property> (eg. !sell A)");
      return;
    }
    if(!(isset($this->roundPlayers[$nick]))) {
      $this->mChan("$nick: Sorry, you've already selected a property to sell.");
      return;
    }
    $house = strtoupper($prop[0]);
    if(!(isset($this->hands[$nick][$house]))) {
      $this->mChan("$nick: You don't own $house! Please try again.");
      return;
    }
    $this->bids[$nick] = $this->hands[$nick][$house];
    unset($this->roundPlayers[$nick]);
    unset($this->hands[$nick][$house]);
    if(count($this->roundPlayers) == 0) {
      $this->mChan("$nick has selected a property to sell. Now we can process the sales.");
      asort($this->bids);
      foreach($this->bids as $nick => $prop) {
        $cash = array_shift($this->tableCards);
        $this->mChan("$nick has sold $prop for $cash dollar(s).");
        $this->players[$nick] += $cash;
      }
      if(count($this->cashDeck) == 0) {
        $this->mChan("The time for selling is over! Final scores...");
        $winners = array();
        $bestScore = -1;
        foreach($this->players as $player => $cash) {
          $this->mChan("$player had $cash dollar(s).");
          if($cash > $bestScore) {
            $winners = array($player);
            $bestScore = $cash;
          }
          else if($cash == $bestScore) {
            $winners[] = $player;
          }
        }
        if(count($winners) == 1) {
          $this->mChan($winners[0]. " has won the game, with a whopping ".$bestScore." dollar(s). Congrats!");
        } else {
          $this->mChan("Tie game! ".implode(", ", $winners). " rejoice in their shared victory, with ".$bestScore." dollar(s). Congrats!");
        }
        $this->resetVars();
        $this->mChan("A new game can now begin. Please !join if you would like to play again.");
        return;
      }
      else {
        $this->tableCards = array();
        for($i=0;$i<$this->numPlayers;$i++) $this->tableCards[] = array_shift($this->cashDeck);
        sort($this->tableCards);
        $this->mChan("Cash available: ".implode(', ', $this->tableCards));
        $this->roundPlayers = array();
        foreach($this->players as $nick => $cash) {
          $this->roundPlayers[$nick] = $cash;
          $this->bids[$nick] = '';
          $this->nUser($nick, "You have ".$this->players[$nick]." dollar(s). Properties left to sell: ".$this->displayProperty($nick));
        }
      }
    }
    else {
      $playersLeft = array_keys($this->roundPlayers);
      $this->mChan("$nick has selected a property to sell. Still waiting on ".implode(", ", $playersLeft).".");
    }

  }
  // Handle player idle (!boot command)
}
