<?php
/**
 * @file
 * Contains Drupal\learndojoapi\Plugin\rest\resource\CountryResource.
 */

namespace Drupal\learndojoapi\Plugin\rest\resource;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a resource to get learning dojo country by id.
 *
 * @RestResource(
 *   id = "country",
 *   label = @Translation("Learn Dojo Country"),
 *   uri_paths = {
 *     "canonical" = "/api/country/{id}"
 *   }
 * )

 */
class CountryResource extends ResourceBase {

  /**
   *  A curent user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */

  protected $currentUser;

  /**
   *  A instance of entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */

  protected $entityManager;

  /*
   * Responds to GET requests.
   */
  public function get($id = NULL) {
      if ($id) {
                           
        $results = db_query("SELECT  c.id as id, c.name as name, c.description as description, c.last_update as last_update, 
                            t.id as termid, t.name as termname, t.description as termdescription, 
                            t.start_date as startdate, date_add(t.start_date, INTERVAL num_weeks WEEK) as enddate, t.num_weeks as numweeks 
                            FROM kacountry c left join katerm t 
                            on c.id = t.country_id where c.id = :id", array(':id' => $id))->fetchAll();
        $i = 0;
        // create country array
        $countries = array();
        $i = 0;
        foreach($results as $row)
        {
          $id = $row -> id;
          // if we already have an element for this country reuse it otherwise create it
          if (isset($countries[$id]))
          { 
            $item = $countries[$id];
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
          // update country element
          $countries[$id] = $item;
          $i = $i + 1;
        }

        // preformat the arrays to faciliate conversion to JSON in the required format
        // as we are selecting by id should only be one.
        $retCountries = array();
        foreach ($countries as $countryrow)
        {
            $retCountries = $countryrow;
            
        }
    
        if ($i > 0) {
           // need to turn off the cache on the results array so set the max-age to 0 by adding $results entity to the cache dependencies.
          // This will clear our cache when this entity updates.
          $renderer = \Drupal::service('renderer');
          $renderer->addCacheableDependency($results, null);

          // note decoding JSON before returning it to avoid embedded "'s being converted to escaped UTF characters
          // as we are passing a string to JsonResponse and not an array
          return  new \Symfony\Component\HttpFoundation\JsonResponse($retCountries);
        }
                
        throw new NotFoundHttpException(t('Country with ID @id was not found', array('@id' => $id)));
    }
    throw new NotFoundHttpException(t('ID not provided'));
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
 