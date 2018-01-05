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
function get_last_sync_datestr($pdo) {
	return $pdo->query("SELECT value FROM sys_par WHERE name = 'LAST_SYNC_DT'")
	  ->fetch(PDO::FETCH_ASSOC)['value'];
}

function update_last_sync_datestr($pdo, $new_date) {
	$pdo->beginTransaction();
	try {
		$stmt = $pdo->prepare("UPDATE sys_par SET value = :value WHERE name = 'LAST_SYNC_DT'");
		$stmt->bindParam(":value", $new_date, PDO::PARAM_STR);
		$stmt->execute();
		$pdo->commit();
		print("Successfully updated last sync date to: " . $new_date . "\n");
	} catch (Exception $e) {
		$pdo->rollBack();
		print("Failed to update sync date");
		var_dump($e);
	}
}

function get_current_data($pdo, $post) {
	$stmt = $pdo->prepare('SELECT id,uuid,updated_at,article_id,revision_id FROM `posts` WHERE id = :id');
	$stmt->bindParam(':id', $post->id, PDO::PARAM_INT);
	$stmt->execute();

	$db_data = $stmt->fetch(PDO::FETCH_ASSOC);

	return $db_data;
}

function run_sync() {
	$proc_start_dt = substr(date("c"), 0, 19) . ".000Z";

	// Create SQLite connection:
	try {
		$pdo = new \PDO('sqlite:' . Config::SQLITE_PATH);
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		$last_sync_dt = get_last_sync_datestr($pdo);

		ini_set("log_errors", 1);
		ini_set("error_log" , __DIR__ ."/../logs/synclog-" . $proc_start_dt."-".$last_sync_dt.".log");

		print("Retrieving posts updated after " . $last_sync_dt . "\n");

		/*
		 * Retrieve only the posts that have been updated since we last performed
		 * the sync
		 *
		 * The first ever sync will be slow assuming the Ghost db is already large.
		 * This is expected as we will be moving all the data to the news database
		 */
		$ghost = new Ghost('posts', array(
			'filter' => 'updated_at:>' . $last_sync_dt,
			'limit' => 'all'
			)
		);

		# Counts any data that wasn't sync'd.
		$count_failure = 0;

		# For each post from Ghost, check if it's update or insert
		$cpts = count($ghost->response->posts); // $count_posts_to_sync
		$cc = 0; // $current_count
		print("Number of Ghost posts to sync: " . $cpts . "\n");
		foreach ($ghost->response->posts as $post) {
			$cc++;
			// Check if the data is already in the SQLite DB:
			$db_data = get_current_data($pdo, $post);

			// Ensure that we aren't trying to update the same record.
			// This is true if updated_at has not changed from what we saved.
			if ($db_data && $db_data["updated_at"] === $post->updated_at) {
				print("[" . $cc . "/" . $cpts . "] " . "Article with UUID " . $post->uuid . " is still updated. Skipping...\n");
				continue;
			}

			// Get a valid converter for the current data:
		  $converter = new GhostToAppleConverter($post);

			try {
				# Create a NewsPublisher
				$news_pub = new NewsPublisher(Config::APPLE_NEWS_CHANNEL_ID);
				// Publish or Update the current post:
				$response = null;
				if ($db_data) {
					print("[" . $cc . "/" . $cpts . "] " . "Updating article " . $post->uuid . "\n");
					$response = $news_pub->updateArticle($db_data["article_id"], $converter->getJSON(), $db_data["revision_id"]);
				} else {
					print("[" . $cc . "/" . $cpts . "] " . "Posting article " . $post->uuid . "\n");
					$response = $news_pub->postArticle($converter->getJSON());
				}

				if($response->data->id == null) {
					throw new \Exception("[" . $cc . "/" . $cpts . "]" . "Post/Update failed");
				}

				// Save new/updated revision_id and article_id to database:
				$new_data = [
					'id' => $post->id,
					'uuid' => $post->uuid,
					'updated_at' => $post->updated_at,
					'article_id' => $response->data->id,
					'revision_id' => $response->data->revision
				];

				$pdo->beginTransaction();

				try {
					if ($db_data) {
						$ustmt = $pdo->prepare('UPDATE posts SET updated_at = :updated_at, article_id = :article_id, revision_id = :revision_id WHERE id = :id and uuid = :uuid');
						$ustmt->execute($new_data);
					} else {
						$istmt = $pdo->prepare('INSERT INTO posts (id, uuid, updated_at, article_id, revision_id) VALUES (:id, :uuid, :updated_at, :article_id, :revision_id)');
						$istmt->execute($new_data);
					}
					$pdo->commit();
				}	catch (Exception $e) {
					$pdo->rollBack();
					error_log("[" . $cc . "/" . $cpts . "] " . "Error updating DB entry for UUID " . $post->uuid . "\n");
				}
			} catch (Exception $e) {
				error_log("[" . $cc . "/" . $cpts . "] " . "Error with processing article with UUID " . $post->uuid . "\n");
			}
		}

		if ($count_failure == 0) {
			update_last_sync_datestr($pdo, $proc_start_dt);
		} else {
			error_log("Failed to sync " . $count_failure . " items. Last sync date will not be updated\n");
		}

		// Close connection
		$pdo = null;
	} catch (PDOException $e) {
		print "Error: " . $e->getMessage() . "\n";
    die();
	}
}

# Execute the actual sync
run_sync();

?>
