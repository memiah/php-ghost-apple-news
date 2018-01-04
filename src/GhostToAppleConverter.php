<?php

namespace GhostAppleNews;

use \ChapterThree\AppleNewsAPI\Document;
use \ChapterThree\AppleNewsAPI\Document\Components\Body;
use \ChapterThree\AppleNewsAPI\Document\Components\Photo;
use \ChapterThree\AppleNewsAPI\Document\Components\Author;
use \ChapterThree\AppleNewsAPI\Document\Layouts\Layout;
use \ChapterThree\AppleNewsAPI\Document\Styles\ComponentTextStyle;

class GhostToAppleConverter {
  private $post = null;
  private $revision_id = null;
  private $article_id = null;
  private $title = null;
  private $json = null;

  public function __construct($post) {
    $this->post = $post;

    $obj = new Document(uniqid(), $post->title, $post->language, new Layout(7, 1024));
    $markdown = $post->markdown;
    # Replace the images to compensate for lack of https://
    $markdown = str_replace('(/content/images/', '(https://happiful.com/content/images/', $markdown);
    $obj->addComponent((new Body($markdown))->setFormat('markdown'))
      ->addComponentTextStyle('default', new ComponentTextStyle());

    # PHOTO
    if ($post->featured_image !== NULL) {
      $obj->addComponent(new Photo('https://happiful.com' . $post->featured_image));
    }

    # AUTHOR
    if ($post->author !== NULL) {
      $obj->addComponent(new Author($post->author));
    }

    $this->json = $obj->json();
  }

  public function getJSON() {
    return $this->json;
  }
}
