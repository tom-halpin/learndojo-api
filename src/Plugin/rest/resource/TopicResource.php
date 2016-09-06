<?php
/**
 * @file
 * Contains Drupal\learndojoapi\Plugin\rest\resource\TopicResource.
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
 * Provides a resource to get learning dojo topic by id.
 *
 * @RestResource(
 *   id = "topic",
 *   label = @Translation("Learn Dojo Topic"),
 *   uri_paths = {
 *     "canonical" = "/api/topic/{id}"
 *   }
 * )

 */
class TopicResource extends ResourceBase {

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
        $record = db_query('SELECT h.id as countryid, h.name as countryname, 
                a.id as missionid, a.name as missionname, 
                b.id as strandid, b.name as strandname, 
                c.id as unitid, c.name as unitname, 
                d.id, d.name, d.description, d.corecontent, d.learning_outcome as learningoutcome, d.ka_topic as externalTopic, d.ka_url as externalUrl, 
                d.difficultyindex, d.term_id as termid, d.weeknumber, 
                d.topictype_id as topictypeid, e.name as topictypename, d.notes
                FROM kamission a, kastrand b, kaunit c, katopic d, katopictype e, katerm f, kacountry h
                where 
                h.id = a.country_id AND
                a.id = b.mission_id AND 
                b.id = c.strand_id AND
                c.id = d.unit_id AND
                e.id = d.topictype_id AND
                f.id = d.term_id AND
                d.id = :id', array(':id' => $id))->fetchAllAssoc('id');
                
        if (!empty($record)) {
            // need to turn off the cache on the results array so set the max-age to 0 by adding $results entity to the cache dependencies.
            // This will clear our cache when this entity updates.
            $renderer = \Drupal::service('renderer');
            $renderer->addCacheableDependency($record, null);
          
            $outp = "[";
            $outp .= '{"id":' . '"'  . $record[$id] -> id . '",';
            $outp .= '"name":"'   . $record[$id] -> name        . '",';
            $outp .= '"description":"'. $record[$id] -> description     . '",';
            $outp .= '"corecontent":"'   . $record[$id] -> corecontent        . '",';
            $outp .= '"learningoutcome":"'   . $record[$id] -> learningoutcome        . '",';
            $outp .= '"externalTopic":"'   . $record[$id] -> externalTopic        . '",';
            $outp .= '"externalUrl":"'   . $record[$id] -> externalUrl        . '",';
            $outp .= '"difficultyindex":"'   . $record[$id] -> difficultyindex        . '",';
            $outp .= '"termid":"'   . $record[$id] -> termid        . '",';
            $outp .= '"weeknumber":"'   . $record[$id] -> weeknumber        . '",';
            $outp .= '"topictypeid":"'   . $record[$id] -> topictypeid        . '",';
            $outp .= '"topictypename":"'   . $record[$id] -> topictypename        . '",';
            $outp .= '"notes":"'   . $record[$id] -> notes        . '",';            
            $outp .= '"last_update":"'. $record[$id] -> last_update     . '",';
            $outp .= '"countryid":"'. $record[$id] -> countryid     . '",';
            $outp .= '"countryname":"'. $record[$id] -> countryname     . '",';
            $outp .= '"missionid":"'. $record[$id] -> missionid     . '",';
            $outp .= '"missionname":"'. $record[$id] -> missionname     . '",';            
            $outp .= '"strandid":"'. $record[$id] -> strandid     . '",';
            $outp .= '"strandname":"'. $record[$id] -> strandname     . '",';
            $outp .= '"unitid":"'. $record[$id] -> unitid     . '",';
            $outp .= '"unitname":"'. $record[$id] -> unitname     . '"}';
            $outp .="]";
    
            // note decoding JSON before returning it to avoid embedded "'s being converted to escaped UTF characters
            // as we are passing a string to JsonResponse and not an array
            return  new \Symfony\Component\HttpFoundation\JsonResponse(json_decode($outp));
        }
        throw new NotFoundHttpException(t('Topic with ID @id was not found', array('@id' => $id)));
    }
      throw new NotFoundHttpException(t('Topic ID not provided'));
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
 