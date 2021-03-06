<?php
/**
 * @file
 * Contains Drupal\learndojoapi\Plugin\rest\resource\CountriesResource.
 */

namespace Drupal\learndojoapi\Plugin\rest\resource;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpFoundation;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a resource to get learning dojo countries.
 *
 * @RestResource(
 *   id = "countries",
 *   label = @Translation("Learn Dojo Countries"),
 *   uri_paths = {
 *     "canonical" = "/api/countries"
 *   }
 * )

 */
class CountriesResource extends ResourceBase {


  /**
   *  A instance of entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */

  protected $entityManager;

  /*
   * Responds to GET requests.
   */
  public function get() {

    $results = db_query('SELECT  c.id, c.name, c.description, c.last_update, t.id as termid, t.name as termname, t.description as termdescription, 
                         start_date as startdate, date_add(start_date, INTERVAL num_weeks WEEK) as enddate, num_weeks as numweeks 
                         FROM kacountry c LEFT JOIN katerm t 
                         ON t.country_id = c.id ORDER BY c.id asc')->fetchAll();
   
   // create country array
    $countries = array();
    $i = 0;
    foreach($results as $row)
    {
      $id = $row -> id;
      // if we already have an element for this country reuse it otherwise create it
      if (isset($countries["countries"]))
      { 
        $item = $countries["countries"];
      }
      else
      {
        $item = array
        (
          'countryid' => $row -> id,
          'countryname' => $row -> name,
          'countrydescription' => $row -> description,
          'terms' => array() // create terms array for country
        );
        $countries["countries"] = $item;
      }

      $termid = $row -> termid;
      // are terms defined for the country if so add them 
      if($termid !== null)
      {
          $term = array 
          (
              'termid' => $termid,
              'termname' => $row -> termname,
              'termdescription' => $row -> termdescription,
              'startdate' => $row -> startdate,
              'enddate' => $row -> enddate,
              'numweeks' => $row -> numweeks,
          );
          $item['terms'][] = $term;
      }
     // update the root array with the updated arrays
     $countries["countries"] = $item;     
      $i = $i + 1;
    }

    if ($i > 0) {
       // need to turn off the cache on the results array so set the max-age to 0 by adding $results entity to the cache dependencies.
      // This will clear our cache when this entity updates.
      $renderer = \Drupal::service('renderer');
      $renderer->addCacheableDependency($results, null);

      return  new \Symfony\Component\HttpFoundation\JsonResponse($countries);
    }

    throw new NotFoundHttpException(t('No Countries found'));
  }

    /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    EntityManagerInterface $entity_manager,
    AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->entityManager = $entity_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('entity.manager'),
      $container->get('current_user')
    );
  }
}
 