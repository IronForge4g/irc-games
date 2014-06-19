<?php

class incangold implements pluginInterface {
  var $config;
  var $socket;

  var $started;
  var $channel;
  var $players;
  var $numPlayers;
  var $roundPlayers;
  var $tableCards;
  var $choices;
  var $choice;
  var $treasureDeck;
  var $enemies;
  var $artifacts;
  var $round;
  var $ground;
  var $artifactsTaken = 0;

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
    $this->channel = '#PlayIncanGold';
    $this->players = array();
    $this->roundPlayers = array();
    $this->numPlayers = 0;
    $this->tableCards = array();
    $this->choices = array();
    $this->choice = array();
    $this->enemies = array('Rock', 'Snake', 'Spider', 'Mummy', 'Teddy Bear', 'Creepy Doll', 'Pointed Stick', 'ManBearPig', 'Cacodemon', 'Djinn', 'Basilisk', 'Ghooost', 'Minotaur', 'Moderator', 'Unicorn', 'Wraith', 'Owl Bear', 'Farm Animal');
    shuffle($this->enemies);
    $this->artifacts = array('Book of Infinite Spells', 'Rogues Dice', 'Philosophers Stone', 'Copy of Munchkin', 'Bag of Santa Claus', 'Pamphlet of Dad Jokes', 'Abacus of Counting', 'Pencil of Writing', 'Door Stopper of Stopping', 'Keyboard of Typing', 'Cup of Drinking', 'Chair of Sitting', 'Ladder of Climbing', 'Peephole of Peeping', 'Sorting Hat', 'Broomstick', 'Golden Quidditch Snitch', 'Cloak of Invisibility', 'Old Boot');
    shuffle($this->artifacts);
    $this->treasureDeck = array(1, 2, 3, 4, 5, 5, 7, 7, 9, 11, 11, 13, 14, 15, 17);
    for($i=0;$i<3;$i++) {
      for($n=0;$n<5;$n++) $this->treasureDeck[] = $this->enemies[$n];
    }
    shuffle($this->treasureDeck);
    $this->ground = 0;
    $this->artifactsTaken = 0;
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
    else if($cmd == "!path") $this->cmdPath($from, $tmp);
    else if($cmd == "!p") $this->cmdPath($from, $tmp);
    else if($cmd == "!pa") $this->cmdPath($from, array('A'));
    else if($cmd == "!pb") $this->cmdPath($from, array('B'));
    else if($cmd == "!a") $this->cmdPath($from, array('A'));
    else if($cmd == "!b") $this->cmdPath($from, array('B'));
  }
  function onNick($from, $to) {
    if(isset($this->players[$from])) {
      $this->players[$to] = $this->players[$from];
      if(isset($this->roundPlayers[$from])) {
        $this->roundPlayers[$to] = $this->roundPlayers[$from];
        unset($this->roundPlayers[$from]);
      }
      if(isset($this->choices[$from])) {
        $this->choices[$to] = $this->choices[$from];
        unset($this->choices[$from]);
      }
      if(isset($this->choice[$from])) {
        $this->choice[$to] = $this->choice[$from];
        unset($this->choice[$from]);
      }
      unset($this->players[$from]);
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
    $this->nUser($nick, "!rules - Show's the rules for Incan Gold.");
    $this->nUser($nick, "!start - Start a new game of Incan Gold.");
    $this->nUser($nick, "!join - Join a game.");
    $this->nUser($nick, "!path <choice> - Choose to forge on or flee in terror.");
    $this->nUser($nick, "!p <choice> - Shortform for above.");
    $this->nUser($nick, "!pa | !a - Shortform for path A.");
    $this->nUser($nick, "!pb | !b - Shortform for path B.");
  }
  function cmdRules($nick) {
    $this->nUser($nick, "Incan Gold is an IRC implementation of the game Incan Gold!");
    $this->nUser($nick, "Find the rules online, or ask someone for a learning game.");
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
      $this->mChan("A minimum of 3 players is required to start Incan Gold. Current players are: $players. Please use !join to join this game.");
      return;
    }

    $players = implode(', ', array_keys($this->players));
    $this->mChan("$nick has started the game!");

    $this->started = true;
    $this->round = 0;
    $this->newRound();
  }
  function gems($c) {
    if($c == 0) return "no gems";
    else if($c == 1) return "1 gem";
    else return "$c gems";
  }
  function newRound() {
    $this->round++;
    if($this->round == 6) {
      $this->mChan("The game has now come to its conclusion.");
      $winners = array();
      $bestScore = -1;
      foreach($this->players as $player => $gems) {
        $this->mChan("$player had ".$this->gems($gems).".");
        if($gems > $bestScore) {
          $winners = array($player);
          $bestScore = $gems;
        }
        else if($gems == $bestScore) {
          $winners[] = $player;
        }
      }
      if(count($winners) == 1) {
        $this->mChan($winners[0]. " has won the game, with a miraculous ".$this->gems($bestScore).". Congrats!");
      } else {
        $this->mChan("Tie game! ".implode(", ", $winners). " rejoice in their shared victory, with ".$this->gems($bestScore).". Congrats!");
      }
      $this->resetVars();
      $this->mChan("A new game can now begin. Please !join if you would like to play again.");
      return;
    }
    $roundText = 'first';
    if($this->round == 2) $roundText = 'second';
    else if($this->round == 3) $roundText = 'third';
    else if($this->round == 4) $roundText = 'fourth';
    else if($this->round == 5) $roundText = 'fifth';
    $this->mChan("You all gather your supplies, and head into the $roundText temple of the five you wish to explore today.");
    $this->roundPlayers = array();
    foreach($this->players as $nick => $gems) {
      $this->roundPlayers[$nick] = 0;
    }
    foreach($this->tableCards as $tCard) {
      if(in_array($tCard, $this->artifacts)) continue;
      $this->treasureDeck[] = $tCard;
    }
    $this->tableCards = array();
    $this->treasureDeck[] = $this->artifacts[$this->round];
    shuffle($this->treasureDeck);
    $this->ground = 0;
    $this->flipCard();
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
    if($this->numPlayers == 8) {
      $this->mChan("$nick: Sorry, the player limit of 8 has been reached. Please wait for the next game.");
      return;
    }
    $this->players[$nick] = 0;
    $this->numPlayers++;
    $players = implode(', ', array_keys($this->players));
    $this->mChan("$nick has joined the game. Current players are now: $players");
  }
  function cmdPath($nick, $tmp) {
    if(!($this->started)) {
      $this->mChan("$nick: No game has started yet.");
      return;
    }
    if(!(isset($this->players[$nick]))) {
      $this->mChan("$nick: Cheeky monkey, you're not in this game.");
      return;
    }
    if(!(isset($this->roundPlayers[$nick]))) {
      $this->mChan("$nick: Silly monkey, you're not in the temple.");
      return;
    }
    if(!(isset($this->choices[$nick]))) {
      $this->mChan("$nick: You have already made your terrible choice, now you must live with it, if you can...");
      return;
    }
    if(count($tmp) != 1) {
      $this->mChan("$nick: Please specify the path you wish to choose.");
      return;
    }
    $choice = strtoupper($tmp[0]);
    if(!(isset($this->choices[$nick][$choice]))) {
      $this->mChan("$nick: Please specify a valid path you wish to choose.");
      return;
    }
    $this->choice[$nick] = $this->choices[$nick][$choice];
    unset($this->choices[$nick]);
    $waiting = array_keys($this->choices);
    if(count($waiting) == 0) {
      $this->mChan("$nick has made their choice.");
      $fleeing = array();
      foreach($this->choice as $nick => $choice) {
        if($choice == 'Flee') $fleeing[] = $nick;
      }
      $countFleeing = count($fleeing);
      if($countFleeing == 0) {
        $this->mChan("No one has decided to run away!");
      } else if($countFleeing == 1) {
        $tFlee = $fleeing[0];
        $artifacts = array();
        foreach($this->tableCards as $id => $card) {
          if(in_array($card, $this->artifacts)) $artifacts[$id] = $card;
        }
        $countArtifacts = count($artifacts);
        if($countArtifacts == 0) {
          $this->mChan("$tFlee has run away like a thief!, taking ".$this->gems($this->ground)." from the ground as they go.");
        } else if($countArtifacts == 1) {
          $this->mChan("$tFlee has run away like a thief!, taking ".$this->gems($this->ground)." from the ground as they go. They also managed to grab the ".implode("", $artifacts)." on their way.");
        } else {
          $this->mChan("$tFlee has run away like a thief!, taking ".$this->gems($this->ground)." from the ground as they go. They also managed to pickup several artifacts on their way, especially the ones you wanted. Their completed haul was ".implode(', ', $artifacts).".");
        }
        if($countArtifacts > 0) {
          foreach($artifacts as $id => $card) {
            unset($this->tableCards[$id]);
            $this->artifactsTaken++;
            if($this->artifactsTaken > 3) $this->players[$tFlee] += 5;
            else $this->players[$tFlee] += 3;
          }
        }
        $this->players[$tFlee] += $this->roundPlayers[$tFlee];
        $this->players[$tFlee] += $this->ground;
        unset($this->roundPlayers[$tFlee]);
        $this->ground = 0;
      } else {
        $each = floor($this->ground / $countFleeing);
        $remainder = $this->ground - ($each * $countFleeing);
        $this->ground = $remainder;
        $this->mChan(implode(', ', $fleeing)." have all crawled out of the temple like greedy little fools, taking ".$this->gems($each)." from the ground as they go.");
        foreach($fleeing as $tFlee) {
          $this->players[$tFlee] += $this->roundPlayers[$tFlee];
          $this->players[$tFlee] += $each;
          unset($this->roundPlayers[$tFlee]);
        }
      }
      $this->flipCard();
    }
    else {
      $this->mChan("$nick has made their choice. Still waiting for: ".implode(", ", $waiting));
    }
  }
  function flipCard() {
    $roundPlayers = count($this->roundPlayers);
    if($roundPlayers == 0) {
      $this->newRound();
      return;
    }
    $card = array_shift($this->treasureDeck);
    if(in_array($card, $this->enemies)) {
      if(in_array($card, $this->tableCards)) {
        $this->mChan("Pushing into the darkness, you come face to face with a menacing looking $card. Screaming like a little girl, you drop your bag of loot, and flee...The $card takes up your loot, and skips merrily away...");
        $this->newRound();
      }
      else {
        $this->mChan("As you delve deeper into the temple, you see a menacing looking $card. You sidestep down another path, fearing the worst...");
        $this->tableCards[] = $card;
      }
    }
    else if(in_array($card, $this->artifacts)) {
      $this->mChan("Entering the next chamber, you can't believe your eyes! You thought this relic lost to the ages! You all take a moment to stare awestruck at the $card. Secretly, you begin plotting how to steal the $card from your fellow adventurers.");
      $this->tableCards[] = $card;
    }
    else {
      $each = floor($card / $roundPlayers);
      $remainder = $card - ($each * $roundPlayers);
      $this->ground += $remainder;
      $this->mChan("What luck! You have stumbled upon a pile of gems. Counting, you find there to be $card of them. Splitting as fairly as you can, each of you take $each in your loot bag, and leave $remainder on the ground for the next traveller.");
      $this->tableCards[] = $card;
      $add = $this->roundPlayers;
      foreach($add as $nick => $gems) {
        $this->roundPlayers[$nick] += $each;
      }
    }
    $options = array('Forge Onward', 'Flee');
    $this->choices = array();
    $this->choice = array();
    $players = array_keys($this->roundPlayers);
    $this->mChan("Your winding path has now passed by: ".implode(', ', $this->tableCards).".");
    $this->mChan("Gems on the Path: {$this->ground}.");
    $this->mChan("Choose your path wisely. Still in the temple: ".implode(", ", $players).".");
    foreach($this->roundPlayers as $nick => $gems) {
      shuffle($options);
      $this->choices[$nick] = array();
      $this->choices[$nick]['A'] = $options[0];
      $this->choices[$nick]['B'] = $options[1];
      $this->nUser($nick, "Path A: ".$options[0].", Path B: ".$options[1].". You have ".$this->gems($gems)." in your loot bag, with ".$this->gems($this->players[$nick])." back up at base camp.");
    }
  }
  // Handle player idle (!boot command)
}
