<?php

namespace GhostAppleNews;

class Config {
  const SQLITE_PATH = __DIR__ . '/../db/sync.db';

  # Happfiul Details
  const APPLE_NEWS_CHANNEL_ID = '__channel_ID_here__';
  const APPLE_NEWS_KEY_ID = '__Key_ID_here__';
  const APPLE_NEWS_SECRET = '__apple_news_secret_here__';

  const GHOST_CLIENT = 'ghost_client_id_here';
  const GHOST_SECRET = 'ghost_secret_here';
  const GHOST_ENDPOINT = 'https://actual_ghost_endpoint_here.endpoint';

  const APPLE_NEWS_ENDPOINT = 'https://news-api.apple.com';

  const BASE_URL = 'https://happiful.com';
}

?>
