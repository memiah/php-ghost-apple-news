<?php

require 'Ghost.php';
require 'NewsPublisher.php';
require 'GhostToAppleConverter.php';
require 'Config.php';

use \GhostAppleNews\Ghost;
use \GhostAppleNews\NewsPublisher;
use \GhostAppleNews\GhostToAppleConverter;
use \GhostAppleNews\Config;

/**
 * Returns the last update datestr from
 * the SQLite DB
 */
function get_last_update_datestr() {
	return "2017-10-22T11:18:19.000Z";
}

function run_sync() {
	/*
	 * Retrieve only the posts that have been updated since we last performed
	 * the sync
	 *
	 * The first ever sync will be slow assuming the Ghost db is already large.
	 * This is expected as we will be moving all the data to the news database
	 */
	$ghost = new Ghost('posts', array(
		'filter' => 'updated_at:>' . get_last_update_datestr(),
		'limit' => 'all'
		)
	);

	# Create a NewsPublisher. This will be used for all our posts and updates
	$news_pub = new NewsPublisher(Config::APPLE_NEWS_CHANNEL_ID);

	# For each post from Ghost, check if it's update or insert
	foreach ($ghost->response->posts as $post) {
	  $converter = new GhostToAppleConverter($post);

		echo $converter->getJSON() . "\n";

		# FIXME REMOVE. FOR TESTING ONLY.

		#$news_pub->postArticle($converter->getJSON());

		break;
	}
}

run_sync();

#$NP = new NewsPublisher('765dff56-0a18-4166-a092-233ed60830b9');
#var_dump( $NP->getArticles());


#$NP->postArticle($json);

?>
