<?php

function jmapp_score_compare($a, $b)
{
	$sa = $a['score'];
	$sb = $b['score'];
	if ($sa == $sb) {
		return 0;
	}
	return ($sa < $sb) ? -1 : 1; // ascending
}

function jmapp_get_game_scores()
{
	$scores = get_option('jmapp_game_scores');
	if ($scores === FALSE)
	{
		// root level top and recent are for backwards compatibility
		$scores = ['dash' => ['top' => [], 'recent' => []], 'top' => [], 'recent' => []];
	}
	// sort the scores
	// uasort($scores['top'], 'jmapp_score_compare');
	
	// backwards compatibility only on get
	if (empty($scores['top'])) $scores['top'] = $scores['dash']['top'];
	if (empty($scores['recent'])) $scores['recent'] = $scores['dash']['recent'];
	return $scores;
}

// owner is an array of wordpress user id & nickname
function jmapp_add_game_score($game, $score, $owner, $emoji='')
{
	if (empty($owner['id'])) return FALSE;
	
	$scores = jmapp_get_game_scores();
	if (empty($scores[$game])) $scores[$game] = ['top'=>[],'recent'=>[]];
	
	$date = current_time('Y-m-d'); // honors wordpress locale
	$score = (int)$score;
	
	// make the emoji safe for database insertion
	$emoji = wp_encode_emoji($emoji);
	
	// does owner already have a top score?
	$id = $owner['id'];
	$nickname = $owner['nickname'];
	if (empty($scores[$game]['top'][$id]) || $scores[$game]['top'][$id]['score'] < $score)
	{
		$scores[$game]['top'][$id] = ['owner'=>$owner, 'score' => $score, 'date'=>$date, 'emoji' => $emoji];
	}
	
	// do the same with the recent scores
	if (empty($scores[$game]['recent'][$date])) $scores[$game]['recent'][$date] = [];
	if (empty($scores[$game]['recent'][$date][$id]) || $scores[$game]['recent'][$date][$id]['score'] < $score)
	{
		$scores[$game]['recent'][$date][$id] = ['owner'=>$owner, 'score' => $score, 'date'=>$date, 'emoji' => $emoji];
	}

	// maybe clean out the scores to make the data smaller
	// maybe clean out the scores of people who haven't played in a while
	// maybe clean out old scores
	update_option('jmapp_game_scores', $scores);
	return jmapp_get_game_scores();
}