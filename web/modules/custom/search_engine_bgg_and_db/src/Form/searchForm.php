<?php

namespace Drupal\search_engine_bgg_and_db\Form;

use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use mysqli;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class searchForm extends FormBase {

  protected $nodeStorage;


  public function __construct(EntityTypeManagerInterface $entity_type_manager)
  {
    $this->nodeStorage = $entity_type_manager->getStorage('node');
  }
  public static function create(ContainerInterface $container){
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'BGGsearchForm';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['searchField'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search Field'),
      '#autocomplete_route_name' => 'search_engine_bgg_and_db.autocomplete.search_box'
    ];
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit']['#submit'][] = 'submitForm';


    return $form;
  }
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $search_box_id = EntityAutocomplete::extractEntityIdFromAutocompleteInput($form_state->getValue('searchField'));
    $input = $form_state['input']['searchField'];
    $database = \Drupal::database();

    $result = $database->query("SELECT [nid] from {node_field_revision} where title = '$input'");

    if(!$result) {
      $loadurl = 'https://www.boardgamegeek.com/xmlapi/search?search=' . $input . '&exact=1';
      $memberXML = simplexml_load_file($loadurl) or die ("Error line: " . __LINE__);
      $boardId = $memberXML[0]->children()->boardgame['objectid'];
      $loadBoard = simplexml_load_file('https://www.boardgamegeek.com/xmlapi/boardgame/' . $boardId) or die ("Error line: " . __LINE__);

      $designer = $loadBoard[0]->children()->boardgamedesigner;
      $artist = $loadBoard[0]->children()->boardgameartist;
      $publisher = $loadBoard[0]->children()->boardgamepublisher;
      $description = $loadBoard[0]->children()->description;
      $image = $loadBoard[0]->children()->image;
      $fields = ['title' => $input];
      $id = $database->insert('node_field_revision')->fields($fields)->execute();

    }

    return new TrustedRedirectResponse('http://verkefni2.ddev.site/node/' . $result);
  }
  /**
   * Saves a file, based on it's type
   *
   * @param $url
   *   Full path to the image on the internet
   * @param $folder
   *   The folder where the image is stored on your hard drive
   * @param $type
   *   Type should be 'image' at all time for images.
   * @param $title
   *   The title of the image (like ALBUM_NAME - Cover), as it will appear in the Media management system
   * @param $basename
   *   The name of the file, as it will be saved on your hard drive
   *
   * @return int|null|string
   * @throws EntityStorageException
   */
  function _save_file($url, $folder, $type, $title, $basename, $uid = 1) {
    if(!is_dir(\Drupal::config('system.file')->get('default_scheme').'://' . $folder)) {
      return null;
    }
    $destination = \Drupal::config('system.file')->get('default_scheme').'://' . $folder . '/'.basename($basename);
    if(!file_exists($destination)) {
      $file = file_get_contents($url);
      $file = file_save_data($file, $destination);
    }
    else {
      $file = \Drupal\file\Entity\File::create([
        'uri' => $destination,
        'uid' => $uid,
        'status' => FILE_STATUS_PERMANENT
      ]);

      $file->save();
    }

    $file->status = 1;

    $media_type_field_name = 'field_media_image';

    $media_array = [
      $media_type_field_name => $file->id(),
      'name' => $title,
      'bundle' => $type,
    ];
    if($type == 'image') {
      $media_array['alt'] = $title;
    }

    $media_object = \Drupal\media\Entity\Media::create($media_array);
    $media_object->save();
    return $media_object->id();
  }
}
