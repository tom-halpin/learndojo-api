<?php
/**
 * @file
 * Contains Drupal\learndojoapi\Plugin\rest\resource\MissionStrandsResource.
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
 * Provides a resource to get learning dojo mission strands.
 *
 * @RestResource(
 *   id = "missionstrands",
 *   label = @Translation("Learn Dojo Mission Strands"),
 *   uri_paths = {
 *     "canonical" = "/api/missionstrands/{missionid}"
 *   }
 * )

 */
class MissionStrandsResource extends ResourceBase {


  /**
   *  A instance of entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */

  protected $entityManager;

  /*
   * Responds to GET requests.
   */
  public function get($missionid = NULL) {
      if ($missionid) {

        $results = db_query("SELECT s.id, s.mission_id as missionid, s.name, s.description, s.last_update, 
                             m.name as missionname, m.description as missiondescription FROM kastrand s, 
                             kamission m where s.mission_id = m.id and s.mission_id = :missionid", array(':missionid' => $missionid))->fetchAllAssoc('id');
        $i = 0;
        $outp = "[";
        foreach ($results as $row) {
        if ($outp != "[") {$outp .= ",";}
        
            $outp .= '{"id":' . '"'  . $row -> id . '",';
            $outp .= '"name":"'   . $row -> name        . '",';
            $outp .= '"description":"'. $row -> description     . '",';
            $outp .= '"last_update":"'. $row -> last_update     . '",';
            $outp .= '"missionid":"'. $row -> missionid     . '",';
            $outp .= '"missionname":"'. $row -> missionname     . '",';
            $outp .= '"missiondescription":"'. $row -> missiondescription     . '"}';
            $i = $i + 1;
        }
        $outp .="]";

        //$outp1 = json_decode($outp);
        //throw new NotFoundHttpException(json_last_error());
            
        if ($i > 0) {
           // need to turn off the cache on the results array so set the max-age to 0 by adding $results entity to the cache dependencies.
          // This will clear our cache when this entity updates.
          $renderer = \Drupal::service('renderer');
          $renderer->addCacheableDependency($results, null);

          // note decoding JSON before returning it to avoid embedded "'s being converted to escaped UTF characters
          // as we are passing a string to JsonResponse and not an array
          return  new \Symfony\Component\HttpFoundation\JsonResponse(json_decode($outp));
        }
    
        throw new NotFoundHttpException(t('No Strands found for missionid: ' . $missionid));
    }
    throw new NotFoundHttpException(t('mission not provided'));
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
 