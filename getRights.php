<?php
function getUserRights($playerId) {
	$res = array();
	if ($playerId) {
		$getRights = mysql_query('SELECT privilege FROM `mkrights` WHERE player="'.$playerId.'"');
		while ($getRight = mysql_fetch_array($getRights))
			$res[$getRight['privilege']] = true;
		if (isset($res['admin'])) {
			$res['moderator'] = true;
			$res['organizer'] = true;
		}
		if (isset($res['moderator']) || isset($res['organizer']))
			$res['manager'] = true;
	}
	return $res;
}
$hasRight = getUserRights($id);
function hasRight($key) {
	global $hasRight;
	return isset($hasRight[$key]);
}
?>