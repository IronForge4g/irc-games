<?php
class phaseSecretHitlerVote {
  var $r;
  var $desc;

  var $needed;

  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Vote';
  }
  function init() {
    $this->needed = array();
    foreach($this->r->players as $nick => $player) {
      $player->voted = false;
      $yn = array('Yes', 'No');
      shuffle($yn);
      $player->hand = array('A' => $yn[0], 'B' => $yn[1]);
      $this->needed[$nick] = $nick;
    }
    $this->r->mChan("Your government has been nominated. The President is {$this->r->president->nick} and your Chancellor is {$this->r->chancellor->nick}. Please !(v)ote in this election.");
  }
  function cmdv($from, $args) {
    $this->cmdvote($from, $args);
  }
  function cmdvote($from, $args) {
    $player = $this->r->findPlayer($from);
    if($player == null) return;
    if(!($this->r->checkArgs($from, $args, 1))) return;
    $vote = strtoupper($args[0]);
    if(!(isset($player->hand[$vote]))) {
      $this->r->mChan($from.": Please '!(v)ote A', or '!(v)ote B'.");
      return;
    }
    if($player->voted !== false) {
      $this->r->mChan($from.": I'm sorry, but you have already voted.");
      return;
    }
    $player->voted = $player->hand[$vote];
    unset($this->needed[$from]);
    if(count($this->needed) > 0) {
      $keys = array_keys($this->needed);
      $this->mChan($from." has voted. Still waiting on ballots from: ".implode(', ', $keys));
      return;
    } 
    $this->mChan($from." has voted. Calculating the results...");
    $yes = 0;
    $no = 0;
    $votes = array();
    foreach($this->r->players as $nick => $player) {
      if($player->voted == 'Yes') $yes++;
      else $no++;
      $votes[] = "{$nick} voted {$player->voted}.";
    }
    if($yes > $no) {
      $this->r->mChan(implode(' ', $votes). " The results are {$yes} Yes and {$no} No. The government has been elected.");
      $this->r->setPhase('presidentPolicy');
      return;
    }
    $this->r->mChan(implode(' ', $votes). " The results are {$yes} Yes and {$no} No. The government has not passed.");
    $this->r->electionTrack++;
    if($this->r->electionTrack == 3) {
      $card = $this->r->deck->draw();
      $this->r->mChan("3 Elections in a row have failed, and the frustrated populace takes matters into their own hands, passing a $card Policy.");
      if($card == 'Facist') {
        $policy = $this->r->facistTrack[$this->r->facistPolicies];
        $policy = '*'.substr($policy, 1, -1).'*'; 
        $this->r->facistTrack[$this->r->facistPolicies] = $policy;
        $this->r->facistPolicies++;
        if($this->r->facistPolicies == 6) {
          $this->r->setPhase('end');
          return;
        }
      }
      else {
        $policy = $this->r->liberalTrack[$this->r->liberalPolicies];
        $policy = '*'.substr($policy, 1, -1).'*'; 
        $this->r->liberalTrack[$this->r->liberalPolicies] = $policy;
        $this->r->liberalPolicies++;
        if($this->r->liberalPolicies == 5) {
          $this->r->setPhase('end');
          return;
        }
      }
      $this->r->lastPresident = null;
      $this->r->lastChancellor = null;
      $this->r->electionTrack = 0;
    }
    $this->president = $this->president->left;
    $this->r->setPhase('president');
  }
}
?>
