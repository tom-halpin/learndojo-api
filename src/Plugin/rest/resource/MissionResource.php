<?php
/**
 * @file
 * Contains Drupal\learndojoapi\Plugin\rest\resource\MissionResource.
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
 * Provides a resource to get learning dojo mission by id.
 *
 * @RestResource(
 *   id = "mission",
 *   label = @Translation("Learn Dojo Mission"),
 *   uri_paths = {
 *     "canonical" = "/api/mission/{id}"
 *   }
 * )

 */
class MissionResource extends ResourceBase {

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
        $results = db_query("SELECT m.id, m.country_id as countryid, m.name, m.description, m.last_update, 
                             c.name as countryname, c.description as countrydescription FROM kamission m, 
                             kacountry c where m.country_id = c.id and m.id = :id", array(':id' => $id))->fetchAll();

        // create mission array
        $missions = array("mission");
        $i = 0;
        foreach($results as $row)
        {
          $id = $row -> id;
          // if we already have an element for this mission reuse it otherwise create it should only be one
          if (isset($missions[$id]))
          { 
            $item = $missions[$id];
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
              'countrydescription' => $row -> countrydescription,
            );
          }
          // update country element
          $missions[$id] = $item;
          $i = $i + 1;
        }

        // preformat the arrays to faciliate conversion to JSON in the required format
        // as we are selecting by id should only be one.
        $retMissions = array();
        foreach ($missions as $missionrow)
        {
            $retMissions = $missionrow;
            
        }
    
        if ($i > 0) {
           // need to turn off the cache on the results array so set the max-age to 0 by adding $results entity to the cache dependencies.
          // This will clear our cache when this entity updates.
          $renderer = \Drupal::service('renderer');
          $renderer->addCacheableDependency($results, null);

          return  new \Symfony\Component\HttpFoundation\JsonResponse($retMissions);
        }

        throw new NotFoundHttpException(t('Mission with ID @id was not found', array('@id' => $id)));
    }
      throw new NotFoundHttpException(t('Mission ID not provided'));
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
 