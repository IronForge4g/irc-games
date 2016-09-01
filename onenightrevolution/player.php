<?php
class oneNightRevolutionPlayer {
  var $r;
  var $nick;
  var $left;
  var $right;
  var $msgType;

  var $specialist;
  var $initialTeam;
  var $team;
  var $claimed;
  var $revealed;
  var $actionTaken;
  var $vote;
  var $called;

  function __construct($root, $nick) {
    $this->r = $root;
    $this->nick = $nick;
    $this->msgType = 'msg';
    $this->left = null;
    $this->right = null;
    $this->specialist = null;
    $this->team = null;
    $this->claimed = null;
    $this->revealed = false;
    $this->actionTaken = false;
    $this->vote = null;
    $this->called = false;
  }
  function init() {
  }
}
?>
