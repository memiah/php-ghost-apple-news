<?php

namespace GhostAppleNews;

use \ChapterThree\AppleNewsAPI\Document;
use \ChapterThree\AppleNewsAPI\Document\Metadata;
use \ChapterThree\AppleNewsAPI\Document\Components\Body;
use \ChapterThree\AppleNewsAPI\Document\Components\Heading;
use \ChapterThree\AppleNewsAPI\Document\Components\Photo;
use \ChapterThree\AppleNewsAPI\Document\Components\Author;
use \ChapterThree\AppleNewsAPI\Document\Layouts\Layout;
use \ChapterThree\AppleNewsAPI\Document\Styles\ComponentTextStyle;

class GhostToAppleConverter {
  private $post = null;
  private $json = null;

  public function __construct($post) {
    $this->post = $post;

    $doc = new Document(uniqid(), $post->title, $post->language, new Layout(7, 1024));
    $body = $post->html;

    # PHOTO
    if ($post->feature_image !== NULL) {
      $doc->addComponent(new Photo(Config::BASE_URL . $post->feature_image));
    }

    $this->addBodyComponents($doc, $body);

    $this->json = $doc->json();
    $this->initMetadata($doc, $post);
  }

  private function initMetadata($doc, $post) {
    $metadata = new Metadata();
    $metadata->addAuthor($post->author);
    $metadata->setDateModified($post->updated_at);
    $metadata->setDatePublished($post->published_at);
    $metadata->setCanonicalURL(Config::BASE_URL . $post->url);

    if ($post->feature_image !== NULL) {
      $metadata->setThumbnailURL(Config::BASE_URL . $post->feature_image);
    }

    $doc->setMetadata($metadata);
  }

  private function addBodyComponents($doc, $body) {
    $start = 0;
    $matches = [];
    $image_regex = "<div[^>]*?>\s*<img[^>]+?>(?:.|\n)*?<\/div>";
    $header_regex = "<h([1-6])[^>]*?>.+?<\/h\\1>";

    $str = "";

    while (preg_match("/(?:" . $image_regex . "|" . $header_regex . ")/", $body, $matches, PREG_OFFSET_CAPTURE, $start)) {
      $offset = $matches[0][1];

      // Append the part of the body that came before the image:
      $stringPiece = substr($body, $start, $offset - $start);
      if (trim($stringPiece)) {
        $doc->addComponent((new Body($stringPiece))->setFormat('html'))
          ->addComponentTextStyle('default', new ComponentTextStyle());
      }

      $matchStr = $matches[0][0];

      // Then append the matched component:
      if (preg_match("/" . $image_regex . "/", $matchStr)) {
        // We matched an image section:
        $imageURL = $this->extractUrlFromImgTag($matchStr);
        $imageCaption = $this->extractCaptionFromImgTag($matchStr);
        $doc->addComponent(
          (new Photo(Config::BASE_URL . $imageURL))
            ->setCaption($imageCaption)
        );
      } else if (preg_match("/" . $header_regex . "/", $matchStr)) {
        // We matched a header section:
        $headerText = $this->extractHeaderText($matchStr);
        if($headerText) {
          $heading = new Heading($headerText);
          $heading->setRole("heading" . $matches[1][0]);
          $doc->addComponent($heading);
        }
      }

      $start = $offset + strlen($matchStr);
    }

    // Do something with substring:
    $stringPiece = substr($body, $start, $offset - $start);
    if (trim($stringPiece)) {
      $doc->addComponent((new Body($stringPiece))->setFormat('html'))
        ->addComponentTextStyle('default', new ComponentTextStyle());
    }
  }

  /**
   * Image sources are of the pattern:
   * <img src="{source}" alt="" />
   */
  private function extractUrlFromImgTag($str) {
    $matches = [];
    $regex = "/src=['\"]([^'\"]+?)['\"]/";
    preg_match($regex, $str, $matches);

    return $matches[1];
  }

  /**
   * Captions in with images are of the pattern:
   * <p class="caption-text">{text}</p>
   */
  private function extractCaptionFromImgTag($str) {
    $matches = [];
    $regex = "/<p\s+class\s*=\s*['\"]caption-text['\"]\s*>(.+?)<\/p>/";
    preg_match($regex, $str, $matches);

    return $matches[1];
  }

  private function extractHeaderText($str) {
    $matches = [];
    $regex = "/<h3[^>]*?>(.+?)<\/h3>/";
    preg_match($regex, $str, $matches);

    return $matches[1];
  }

  public function getJSON() {
    return $this->json;
  }
}
