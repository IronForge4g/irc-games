<?php
class findgame implements pluginInterface {
  var $config;
  var $socket;
  var $channel;
  var $db;

  function init($config, $socket) {
    $this->config = $config;
    $this->socket = $socket;
    $this->channel = '#FindBoardGame';
  }

  function tick() {

  }

  function onMessage($from, $channel, $msg) {
    if($channel != $this->channel) return;
    if($msg{0} == '+' || $msg{0} == '-') $msg = '!'.$msg;
    if($msg{0} != '!') return;
    $args = explode(" ", str_replace(';', ' ', $msg));
    $cmdRaw = array_shift($args);
    $cmd = 'cmd'.strtolower(substr($cmdRaw, 1));
    if(trim($cmd) == 'cmd') return;
    $cmd = str_replace('+', 'p', str_replace('-', 'm', $cmd));
    $args = array(trim(implode(' ', $args)));
    if(method_exists($this, $cmd)) {
      $this->$cmd($from, $args);
    } else {
      $this->mChan("$from: $cmdRaw does not exist.");
    }
  }
  function onNick($from, $to) {
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
  function mChan($message) {
    sendMessage($this->socket, $this->channel, $message);
  }
  function nUser($nick, $message) {
    sendNotice($this->socket, $nick, $message);
  }
  function cmdhelp($from, $args) {
    $this->nUser($from, "!list - List all games that currently match the criteria.");
    $this->nUser($from, "<-|+>all - Add/Remove all games.");
    $this->nUser($from, "<-|+>collection - Add/Remove all games in the user collection.");
    $this->nUser($from, "<-|+>players - Add/Remove all games that allow the player count.");
    $this->nUser($from, "<-|+>artist - Add/Remove all games that match an artist.");
    $this->nUser($from, "<-|+>category - Add/Remove all games that match a category.");
    $this->nUser($from, "<-|+>compilation - Add/Remove all games that match a compilation.");
    $this->nUser($from, "<-|+>designer - Add/Remove all games that match a designer.");
    $this->nUser($from, "<-|+>expansion - Add/Remove all games that match an expansion.");
    $this->nUser($from, "<-|+>family - Add/Remove all games that match a family.");
    $this->nUser($from, "<-|+>implementation - Add/Remove all games that match an implementation.");
    $this->nUser($from, "<-|+>mechanic - Add/Remove all games that match a mechanic.");
    $this->nUser($from, "<-|+>publisher - Add/Remove all games that match a publisher.");
    $this->nUser($from, "<-|+>rating - Add/Remove all games that have a given rating. Must be used with >number or <number.");
    $this->nUser($from, "For example: '+designer Vlaada' will add all games designed by Vlaada Chvatil.");
    $this->nUser($from, "You can also prefix an arguement with ! for an exclusion. For example: '-players !2' will remove all games from the search that don't support 2 players.");
  }
  function searchCount($message) {
    $count = mysql_result(mysql_query("select count(*) from searchgames"), 0);
    if($count == 1) $this->mChan("$message There is now $count matching game.");
    else $this->mChan("$message There are now $count matching games.");
  }
  function cmdpall($from, $args) {
    $this->db = mysql_connect('localhost', 'root', '171737');
    mysql_select_db('bggtmp');
    mysql_query("insert ignore into searchgames select * from games");
    $this->searchCount("Adding all games.");
  }
  function cmdmall($from, $args) {
    $this->db = mysql_connect('localhost', 'root', '171737');
    mysql_select_db('bggtmp');
    mysql_query("delete from searchgames");
    $this->mchan("All games have been removed. There are now 0 matching games.");
  }
  function cmdlist($from, $args) {
    $this->db = mysql_connect('localhost', 'root', '171737');
    mysql_select_db('bggtmp');
    $return = array();
    $returnLen = 0;
    $extraCount = 0;
    $skipping = false;
    $games = mysql_query("select name, yearPublished, bggRating from searchgames order by bggRating desc");
    while($game = mysql_fetch_assoc($games)) {
      $tText = $game['name'].' ('.$game['yearPublished'].') - '.number_format($game['bggRating'], 2);
      $tLen = strlen($tText);
      if($skipping || (($returnLen + $tLen + 2) > 400)) {
        $extraCount++;
        $skipping = true;
      } else {
        $return[] = $tText;
        $returnLen += ($tLen + 2);
      }
    }
    mysql_free_result($games);
    $matches = implode(', ', $return);
    if($matches == '') $this->mChan('No matches found.');
    else $this->mChan('Matches: '.$matches);
    if ($extraCount == 1) $this->mChan("There was $extraCount more match not listed. Please narrow your search.");
    else if ($extraCount > 1) $this->mChan("There were $extraCount more matches not listed. Please narrow your search.");
  }
  function requiresArgs($args) {
    if($args[0] == '') {
      $this->mChan("Please specify an arguement.");
      return true;
    }
    return false;
  }
  function cmdpplayers($from, $args) {
    if($this->requiresArgs($args)) return;
    $this->db = mysql_connect('localhost', 'root', '171737');
    mysql_select_db('bggtmp');
    $arg = $args[0];
    if($arg{0} == '!') {
      $arg = substr($arg, 1);
      mysql_query("insert ignore into searchgames select * from games where minPlayers > '$arg' or maxPlayers < '$arg'");
      if($arg == 1) $this->searchCount("Adding all games that don't allow $arg player.");
      else $this->searchCount("Adding all games that don't allow $arg players.");
    } else {
      mysql_query("insert ignore into searchgames select * from games where minPlayers <= '$arg' and maxPlayers >= '$arg'");
      if($arg == 1) $this->searchCount("Adding all games that allow $arg player.");
      else $this->searchCount("Adding all games that allow $arg players.");
    }
  }
  function cmdmplayers($from, $args) {
    if($this->requiresArgs($args)) return;
    $this->db = mysql_connect('localhost', 'root', '171737');
    mysql_select_db('bggtmp');
    $arg = $args[0];
    if($arg{0} == '!') {
      $arg = substr($arg, 1);
      mysql_query("delete from searchgames where minPlayers > '$arg' or maxPlayers < '$arg'");
      if($arg == 1) $this->searchCount("Removing all games that don't allow $arg player.");
      else $this->searchCount("Removing all games that don't allow $arg players.");
    } else {
      mysql_query("delete from searchgames where minPlayers <= '$arg' and maxPlayers >= '$arg'");
      if($arg == 1) $this->searchCount("Removing all games that allow $arg player.");
      else $this->searchCount("Removing all games that allow $arg players.");
    }
  }
  function cmdprating($from, $args) {
    if($this->requiresArgs($args)) return;
    $this->db = mysql_connect('localhost', 'root', '171737');
    mysql_select_db('bggtmp');
    $arg = $args[0];
    if($arg{0} == '>') {
      $arg = substr($arg, 1);
      mysql_query("insert ignore into searchgames select * from games where bggRating >= '$arg'");
      $this->searchCount("Adding all games that have a bgg rating higher than $arg.");
    } else if($arg{0} == '<') {
      $arg = substr($arg, 1);
      mysql_query("insert ignore into searchgames select * from games where bggRating <= '$arg'");
      $this->searchCount("Adding all games that having a bgg rating less than $arg.");
    } else {
      $this->mChan("rating arguement must begin with < or >.");
    }
  }
  function cmdmrating($from, $args) {
    if($this->requiresArgs($args)) return;
    $this->db = mysql_connect('localhost', 'root', '171737');
    mysql_select_db('bggtmp');
    $arg = $args[0];
    if($arg{0} == '>') {
      $arg = substr($arg, 1);
      mysql_query("delete from searchgames where bggRating >= '$arg'");
      $this->searchCount("Removing all games that have a bgg rating higher than $arg.");
    } else if($arg{0} == '<') {
      $arg = substr($arg, 1);
      mysql_query("delete from searchgames where bggRating <= '$arg'");
      $this->searchCount("Removing all games that having a bgg rating less than $arg.");
    } else {
      $this->mChan("rating arguement must begin with < or >.");
    }
  }
  function cmdpartist($from, $args) { $this->plinks('artist', $args); }
    function cmdmartist($from, $args) { $this->mlinks('artist', $args); }
    function cmdpcategory($from, $args) { $this->plinks('category', $args); }
    function cmdmcategory($from, $args) { $this->mlinks('category', $args); }
    function cmdpcompilation($from, $args) { $this->plinks('compilation', $args); }
    function cmdmcompilation($from, $args) { $this->mlinks('compilation', $args); }
    function cmdpdesigner($from, $args) { $this->plinks('designer', $args); }
    function cmdmdesigner($from, $args) { $this->mlinks('designer', $args); }
    function cmdpexpansion($from, $args) { $this->plinks('expansion', $args); }
    function cmdmexpansion($from, $args) { $this->mlinks('expansion', $args); }
    function cmdpfamily($from, $args) { $this->plinks('family', $args); }
    function cmdmfamily($from, $args) { $this->mlinks('family', $args); }
    function cmdpimplementation($from, $args) { $this->plinks('implementation', $args); }
    function cmdmimplementation($from, $args) { $this->mlinks('implementation', $args); }
    function cmdpmechanic($from, $args) { $this->plinks('mechanic', $args); }
    function cmdmmechanic($from, $args) { $this->mlinks('mechanic', $args); }
    function cmdppublisher($from, $args) { $this->plinks('publisher', $args); }
    function cmdmpublisher($from, $args) { $this->mlinks('publisher', $args); }
    function plinks($link, $args) {
      if($this->requiresArgs($args)) return;
    $this->db = mysql_connect('localhost', 'root', '171737');
    mysql_select_db('bggtmp');
      $arg = $args[0];
      if($arg{0} == '!') {
        $arg = substr($arg, 1);
        mysql_query("delete from tmpgames");
        mysql_query("insert ignore into tmpgames select distinct g.id from games g, links l, linkdata ld where l.linkType='boardgame$link' and ld.linkType='boardgame$link' and l.linkId=ld.linkId and ld.value like '%$arg%' and g.id=l.game");
        mysql_query("insert ignore into searchgames select g.* from games g LEFT JOIN tmpgames t on g.id=t.id where t.id is null");
        $this->searchCount("Adding all games that don't match $link '$arg'.");
      } else {
        mysql_query("insert ignore into searchgames select g.* from games g, links l, linkdata ld where l.linkType='boardgame$link' and ld.linkType='boardgame$link' and l.linkId=ld.linkId and ld.value like '%$arg%' and g.id=l.game");
        $this->searchCount("Adding all games that match $link '$arg'.");
      }
    }
  function mlinks($link, $args) {
    if($this->requiresArgs($args)) return;
    $this->db = mysql_connect('localhost', 'root', '171737');
    mysql_select_db('bggtmp');
    $arg = $args[0];
    if($arg{0} == '!') {
      $arg = substr($arg, 1);
      mysql_query("delete from tmpgames");
      mysql_query("insert ignore into tmpgames (select distinct g.id from games g, links l, linkdata ld where l.linkType='boardgame$link' and ld.linkType='boardgame$link' and l.linkId=ld.linkId and ld.value like '%$arg%' and g.id=l.game)");
      mysql_query("delete from searchgames where id in (select g.id from games g LEFT JOIN tmpgames t on g.id=t.id where t.id is null)");
      $this->searchCount("Removing all games that don't match $link '$arg'.");
    } else {
      mysql_query("delete from tmpgames");
      mysql_query("insert ignore into tmpgames (select distinct g.id from games g, links l, linkdata ld where l.linkType='boardgame$link' and ld.linkType='boardgame$link' and l.linkId=ld.linkId and ld.value like '%$arg%' and g.id=l.game)");
      mysql_query("delete from searchgames where id in (select id from tmpgames)");
      $this->searchCount("Removing all games that match $link '$arg'.");
    }
  }
  function getCollection($arg) {
    $page = $this->getPage("http://www.boardgamegeek.com/xmlapi2/collection?username=$arg&own=1");
    if(strpos($page, "Invalid username specified")) {
      $this->mChan("Invalid username.");
      return false;
    }
    $found = false;
    $count = 0;
    while($count < 4) {
      if(strpos($page, "Your request for this collection has been accepted and will be processed")) {
        sleep($count + 1);
        $page = $this->getPage("http://www.boardgamegeek.com/xmlapi2/collection?username=$arg&own=1");
      }
      else {
        $found = true;
        break;
      }
      $count++;
    }
    if(!($found)) {
      $this->mChan("Failed to load collection, please try again.");
      return false;
    }
    mysql_query("delete from tmpgames");
    $games = $this->regexFindAll($page, '#objectid="(.*?)"#');
    $add = array();
    $count = 0;
    foreach($games as $game) {
      $add[] = "($game)";
      $count++;
      if($count > 100) {
        mysql_query("insert ignore into tmpgames (id) values ".implode(',',$add));
        $add = array();
        $count = 0;
      }
    }
    if($count > 0) {
      mysql_query("insert ignore into tmpgames (id) values ".implode(',',$add));
    }
    return true;
  }
  function cmdpcollection($from, $args) {
    if($this->requiresArgs($args)) return;
    $this->db = mysql_connect('localhost', 'root', '171737');
    mysql_select_db('bggtmp');
    $inverse = false;
    $arg = $args[0];
    if($arg{0} == '!') {
      $inverse = true;
      $arg = substr($arg, 1);
    }
    $return = $this->getCollection($arg);
    if(!$return) return;
    if($inverse) {
      mysql_query("insert ignore into searchgames select g.* from games g LEFT JOIN tmpgames t on g.id=t.id where t.id is null");
      $this->searchCount("Adding all games that are not in the collection '$arg'.");
    } else {
      mysql_query("insert ignore into searchgames (select g.* from games g, tmpgames t where g.id=t.id)");
      $this->searchCount("Adding all games that are in the collection '$arg'.");
    }
  }
  function cmdmcollection($from, $args) {
    if($this->requiresArgs($args)) return;
    $this->db = mysql_connect('localhost', 'root', '171737');
    mysql_select_db('bggtmp');
    $inverse = false;
    $arg = $args[0];
    if($arg{0} == '!') {
      $inverse = true;
      $arg = substr($arg, 1);
    }
    $return = $this->getCollection($arg);
    if(!$return) return;
    if($inverse) {
      mysql_query("delete from searchgames where id in (select g.id from games g LEFT JOIN tmpgames t on g.id=t.id where t.id is null)");
      $this->searchCount("Removing all games that are not in the collection '$arg'.");
    } else {
      mysql_query("delete from searchgames where id in (select id from tmpgames)");
      $this->searchCount("Removing all games that are in the collection '$arg'.");
    }
  }
  function getPage($link) {
    $curl = curl_init ();
    curl_setopt($curl, CURLOPT_URL, $link); 
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_COOKIEFILE, "cookies.txt");
    curl_setopt($curl, CURLOPT_COOKIEJAR, "cookies.txt");
    curl_setopt($curl, CURLOPT_USERAGENT, "User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/32.0.1700.76 Safari/537.36");
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);


    //  curl_setopt($curl, CURLOPT_PROXY, '127.0.0.1:8888');
    $output = curl_exec ($curl);

    curl_close ($curl);
    return $output;
  }
  function regexFind($xml, $regex) {
    preg_match($regex, $xml, $match);
    if(!(isset($match[1]))) return "";
    if(isset($match[2])) {
      array_shift($match);
      return $match;
    }
    return trim($match[1]);
  }
  function regexFindAll($xml, $regex) {
    $finds = array();
    preg_match_all($regex, $xml, $matches, PREG_SET_ORDER);
    if(isset($matches[0][2])) {
      foreach($matches as $match) {
        array_shift($match);
        $finds[] = $match;
      }
    } else {
      foreach($matches as $match) {
        $finds[] = $match[1];
      }
    }
    return $finds;
  }

}
?>
