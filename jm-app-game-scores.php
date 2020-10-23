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
		$scores = [
			'top' => [],
			'recent' => [],
		];
		add_option('jmapp_game_scores', $scores);
	}
	// sort the scores
	// uasort($scores['top'], 'jmapp_score_compare');
	return $scores;
}

// owner is an array of wordpress user id & nickname
function jmapp_add_game_score($score, $owner, $emoji='')
{
	if (empty($owner['id'])) return FALSE;
	
	$scores = jmapp_get_game_scores();
	$date = current_time('Y-m-d'); // honors wordpress locale
	$score = (int)$score;
	
	// make the emoji safe for database insertion
	$emoji = wp_encode_emoji($emoji);
	
	// does owner already have a top score?
	$id = $owner['id'];
	$nickname = $owner['nickname'];
	if (empty($scores['top'][$id]) || $scores['top'][$id]['score'] < $score)
	{
		$scores['top'][$id] = ['owner'=>$owner, 'score' => $score, 'date'=>$date, 'emoji' => $emoji];
	}
	
	// do the same with the recent scores
	if (empty($scores['recent'][$date])) $scores['recent'][$date] = [];
	if (empty($scores['recent'][$date][$id]) || $scores['recent'][$date][$id]['score'] < $score)
	{
		$scores['recent'][$date][$id] = ['owner'=>$owner, 'score' => $score, 'date'=>$date, 'emoji' => $emoji];
	}

	// maybe clean out the scores to make the data smaller
	update_option('jmapp_game_scores', $scores);
	return jmapp_get_game_scores();
}
