<?php
class phaseSecretHitlerChancellorPolicy {
  var $r;
  var $desc;

  var $veto;
  function __construct($root) {
    $this->r = $root;
    $this->desc = 'Chancellor Policy';
  }
  function init() {
    $player = $this->r->chancellor;
    $this->r->currentPlayer = $player;
    $this->r->mChan("Facist Track: ".implode(', ', $this->r->facistTrack));
    $this->r->mChan("Liberal Track: ".implode(', ', $this->r->liberalTrack));
    if($this->r->electionTrack > 0) $this->r->mChan("Failed Elections: ".{$this->r->electionTrack});
    if($this->r->facistPolicies == 5 && $this->r->veto)
      $this->r->mChan($player->nick.', you are the Chancellor. Please !(e)nact a policy, or !(v)eto this government.');
    else 
      $this->r->mChan($player->nick.', you are the Chancellor. Please !(e)nact a policy.');
    $cards = $this->r->policies;
    shuffle($cards);
    $this->r->policies = array('A' => $cards[0], 'B' => $cards[1]);
    $this->r->nUser($player->nick, "Policies: A. {$this->r->policies['A']} B. {$this->r->policies['B']}");
  }
  function cmde($from, $args) {
    $this->cmdenact($from, $args);
  }
  function cmdv($from, $args) {
    $this->cmdveto($from, $args);
  }
  function cmdenact($from, $args) {
    if(!($this->r->checkCurrentPlayer($from, '!(e)nact a policy'))) return;
    if(!($this->r->checkArgs($from, $args, 1))) return;
    $player = $this->r->chancellor;
    $enact = strtoupper($args[0]);
    if(!(isset($this->r->policies[$enact]))) {
      $this->r->mChan($from.": Please !(e)nact a valid policy.");
      return;
    }
    $card = $this->r->policies[$enact];
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
  }
}
?>
