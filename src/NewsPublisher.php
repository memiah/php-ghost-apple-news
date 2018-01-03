<?php

namespace Pem;

require __DIR__ . '/../vendor/autoload.php';

use \ChapterThree\AppleNewsAPI\PublisherAPI;

class NewsPublisher {
  const API_KEY_ID = Config::APPLE_NEWS_KEY_ID;
  const API_SECRET = Config::APPLE_NEWS_SECRET;
  const API_ENDPOINT = Config::APPLE_NEWS_ENDPOINT;

  private $PublisherAPI = null;
  private $channel_id = null;

  public function __construct($channel_id) {
    $this->PublisherAPI = new PublisherAPI(
      NewsPublisher::API_KEY_ID,
      NewsPublisher::API_SECRET,
      NewsPublisher::API_ENDPOINT
    );
    $this->channel_id = $channel_id;
  }

  public function getChannel() {
    return $this->PublisherAPI->get('/channels/{channel_id}',
      [
        'channel_id' => $this->channel_id
      ]
    );
  }

  public function getArticles() {
    return $this->PublisherAPI->get('/channels/{channel_id}/articles',
      [
        'channel_id' => $this->channel_id
      ]
    );
  }

  public function postArticle($json, $files = []) {
    $metadata = json_encode([
      'data' => [
        'revision' => null
      ]
    ]);

    return $this->PublisherAPI->post('/channels/{channel_id}/articles',
      [
        'channel_id' => $this->channel_id
      ],
      [
        'files' => $files,
        'metadata' => $metadata,
        'json' => $json
      ]
    );
  }

  public function updateArticle($article_id, $revision_id, $json, $files = []) {
    $metadata = json_encode([
      'data' => [
        'revision' => $revision_id
      ]
    ]);

    return $this->PublisherAPI->post('/articles/{article_id}',
      [
        'article_id' => $article_id
      ],
      [
        'files' => $files,
        'metadata' => $metadata,
        'json' => $json
      ]
    );
  }
}

?>
