<?php


namespace Drupal\search_engine_bgg_and_db;


use Drupal\Core\StringTranslation\StringTranslationTrait;
use mysqli;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class SearchEngineBggAndDbSalutation {
  use StringTranslationTrait;

  public function getSalutation() {
    return $this->t('hello world from salutation');
  }
  public function getBoardFromBgg($boardName){
    try {
      $loadurl = 'https://www.boardgamegeek.com/xmlapi/search?search=' . $boardName;
      $memberXML = simplexml_load_file($loadurl) or die ("Error line: " . __LINE__);
      $gamearr =Array();
      $i = 0;
      foreach($memberXML->children() as $item){
        $gamearr[$i] = (string)$item->name;
        $i++;
      }
        return $gamearr;
      /**
      $search_results = \Drupal::httpClient()->get('https://www.boardgamegeek.com/xmlapi/search?search=' . $boardName);
      $search_results = (string) $search_results->getBody();
      $search_results = simplexml_load_string($search_results);
      $array = json_decode(json_encode((array)$search_results),true);
      return $array;
 **/
    } catch (TransportExceptionInterface $e) {
      return 'Could not get anything from BGG';
    }
  }

  public function getBoardName($searchString){
    $servername = "db";
    $username = "db";
    $password = "db";
    $dbname = "db";

    $sql = "SELECT name, from Board where name like $searchString";
    $conn = new mysqli($servername, $username, $password, $dbname);

    if (!$conn) {
      die("Connection failed: " . mysqli_connect_error());
    }
    $result = mysqli_query($conn, $sql);
    return $result;
  }
}
