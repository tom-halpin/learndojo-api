<?php
/**
 * @file
 * Contains Drupal\learndojoapi\Plugin\rest\resource\UnitResource.
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
 * Provides a resource to get learning dojo unit by id.
 *
 * @RestResource(
 *   id = "unit",
 *   label = @Translation("Learn Dojo Unit"),
 *   uri_paths = {
 *     "canonical" = "/api/unit/{id}"
 *   }
 * )

 */
class UnitResource extends ResourceBase {

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
        $results = db_query("SELECT u.id, u.strand_id as strandid, u.name, u.description, u.last_update, 
                             c.id as countryid, c.name as countryname, 
                             m.id as missionid, m.name as missionname,
                             s.name as strandname, s.description as stranddescription  
                             FROM kaunit u, kastrand s, kamission m, kacountry c 
                             where m.country_id = c.id and 
                             s.mission_id = m.id and
                             u.strand_id = s.id and u.id = :id", array(':id' => $id))->fetchAll();
        
       $units = array("unit");
        $i = 0;
        foreach($results as $row)
        {
          $id = $row -> id;
          // if we already have an element for this mission reuse it otherwise create it should only be one
          if (isset($units[$id]))
          { 
            $item = $units[$id];
          }
          else
          {
            $item = array
            (
              'id' => $row -> id,
              'name' => $row -> name,
              'description' => $row -> description,
              'last_update' => $row -> last_update,
              'countryid' => $row -> countryid,
              'countryname' => $row -> countryname,
              'missionid' => $row -> missionid,
              'missionname' => $row -> missionname,
              'strandid' => $row -> strandid,
              'strandname' => $row -> strandname,
              'stranddescription' => $row -> stranddescription                            
            );
          }
          // update country element
          $units[$id] = $item;
          $i = $i + 1;
        }

        // preformat the arrays to faciliate conversion to JSON in the required format
        // as we are selecting by id should only be one.
        $retUnits = array();
        foreach ($units as $unitrow)
        {
            $retUnits = $unitrow;
            
        }
    
        if ($i > 0) {
           // need to turn off the cache on the results array so set the max-age to 0 by adding $results entity to the cache dependencies.
          // This will clear our cache when this entity updates.
          $renderer = \Drupal::service('renderer');
          $renderer->addCacheableDependency($results, null);

          return  new \Symfony\Component\HttpFoundation\JsonResponse($retUnits);
        }
                
        throw new NotFoundHttpException(t('Unit with ID @id was not found', array('@id' => $id)));
    }
      throw new NotFoundHttpException(t('Unit ID not provided'));
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
 