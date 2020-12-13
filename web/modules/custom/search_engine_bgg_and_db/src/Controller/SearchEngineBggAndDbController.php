<?php

namespace Drupal\search_engine_bgg_and_db\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\Markup;
use Drupal\search_engine_bgg_and_db\SearchEngineBggAndDbSalutation;
use Drupal\search_engine_bgg_and_db\Form\searchForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;


class SearchEngineBggAndDbController extends ControllerBase {
  /**
   * The salutation service
   * @var \Drupal\search_engine_bgg_and_db\SearchEngineBggAndDbSalutation
   */
  protected $salutation;
  protected $form;
  protected $nodeStorage;

  /**
   * Constructor
   * @param SearchEngineBggAndDbSalutation $salutation
   */
  public function __construct(SearchEngineBggAndDbSalutation $salutation, EntityTypeManagerInterface $entity_type_manager) {
    $this->salutation = $salutation;
    $this->form = $this->formBuilder()->getForm('Drupal\search_engine_bgg_and_db\Form\searchForm');
    $this->nodeStorage = $entity_type_manager->getStorage('node');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('search_engine_bgg_and_db.salutation'),
      $container->get('entity_type.manager')
    );
  }

  public function search() {
    $renderer = \Drupal::service('renderer');
    $myFormHtml = $renderer->render($this->form);
    $array = $this->salutation->getBoardFromBgg('Crossbows and catapults');
    $string = '';
    foreach($array as $item) {
      $string .= $item . '</br>';
    }
    return [
      '#markup' => markup::create($myFormHtml),
    ];
  }

  public function autocomplete(Request $request)
  {
    $results = $this->salutation->getBoardFromBgg('');
    $input = $request->query->get('q');
    if($input) {
      $results = $this->salutation->getBoardFromBgg($input);
    }


    return new JsonResponse($results);
  }

  public function submit($input) {

  }
}

/**
 *     $result =
$result = explode(' ', $result);
return new JsonResponse($result);
$input = $request->query->get('q');

if(!$input) {
return new JsonResponse($result);
}
$input = Xss::filter($input);

$query = $this->nodeStorage->getQuery()
->condition('type','search_box')
->condition('title', $input, 'CONTAINS')
->groupBy('nid')
->sort('created','DESC')
->range(0,10);
$ids = $query->execute();
$nodes = $ids ? $this->nodeStorage->loadMultiple($ids): [];

foreach($nodes as $node) {
switch ($node->isPublished()) {
case TRUE:
$availability = TRUE;
break;
case FALSE:
default:
$availability = FALSE;
break;
}
$label = [
$node->getTitle(),
'<small>(' . $node->id() . ')</small>',
$availability
];

$results[] = [
'value' => EntityAutocomplete::getEntityLabels([$node]),
'label' => implode('', $label),
];
}

return new JsonResponse($result);
}
 */
