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

    $results = db_query('SELECT * FROM {kacountry} ')->fetchAllAssoc('id');
    $i = 0;
    $outp = "[";
    foreach ($results as $row) {
    if ($outp != "[") {$outp .= ",";}
    
        $outp .= '{"id":' . '"'  . $row -> id . '",';
        $outp .= '"name":"'   . $row -> name        . '",';
        $outp .= '"description":"'. $row -> description     . '",';
        $outp .= '"last_update":"'. $row -> last_update     . '"}';
        $i = $i + 1;
    }
    $outp .="]";

    if ($i > 0) {
       // need to turn off the cache on the results array so set the max-age to 0 by adding $results entity to the cache dependencies.
      // This will clear our cache when this entity updates.
      $renderer = \Drupal::service('renderer');
      $renderer->addCacheableDependency($results, null);
      // note decoding JSON before returning it to avoid embedded "'s being converted to escaped UTF characters
      // as we are passing a string to JsonResponse and not an array
      return  new \Symfony\Component\HttpFoundation\JsonResponse(json_decode($outp));
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
 