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
        $results = db_query('SELECT h.id as countryid, h.name as countryname, 
                a.id as missionid, a.name as missionname, 
                b.id as strandid, b.name as strandname, 
                c.id as unitid, c.name as unitname, c.description as unitdescription,
                d.id, d.name, d.description, d.corecontent, d.learning_outcome as learningoutcome, d.ka_topic as externalTopic, d.ka_url as externalUrl, 
                d.difficultyindex, d.term_id as termid, d.weeknumber, 
                d.topictype_id as topictypeid, e.name as topictypename, d.notes, d.last_update
                FROM kamission a, kastrand b, kaunit c, katopic d, katopictype e, katerm f, kacountry h
                where 
                h.id = a.country_id AND
                a.id = b.mission_id AND 
                b.id = c.strand_id AND
                c.id = d.unit_id AND
                e.id = d.topictype_id AND
                f.id = d.term_id AND
                d.id = :id', array(':id' => $id))->fetchAll();


        $topics = array("unit");
        $i = 0;
        foreach($results as $row)
        {
          $id = $row -> id;
          // if we already have an element for this mission reuse it otherwise create it should only be one
          if (isset($topics[$id]))
          { 
            $item = $topics[$id];
          }
          else
          {
            $item = array
            (
              'id' => $row -> id,
              'name' => $row -> name,
              'description' => $row -> description,
              'corecontent' => $row -> corecontent,
              'learningoutcome' => $row -> learningoutcome,
              'externalTopic' => $row -> externalTopic,
              'externalUrl' => $row -> externalUrl,
              'difficultyindex' => $row -> difficultyindex,
              'termid' => $row -> termid,
              'weeknumber' => $row -> weeknumber,
              'topictypeid' => $row -> topictypeid,
              'topictypename' => $row -> topictypename,
              'notes' => $row -> notes,               
              'last_update' => $row -> last_update,
              'countryid' => $row -> countryid,
              'countryname' => $row -> countryname,
              'missionid' => $row -> missionid,
              'missionname' => $row -> missionname,
              'strandid' => $row -> strandid,
              'strandname' => $row -> strandname,
              'unitid' => $row -> unitid,
              'unitname' => $row -> unitname,
              'unitdescription' => $row -> unitdescription                                           
            );
          }
          // update country element
          $topics[$id] = $item;
          $i = $i + 1;
        }

        // preformat the arrays to faciliate conversion to JSON in the required format
        // as we are selecting by id should only be one.
        $retTopics = array();
        foreach ($topics as $topicrow)
        {
            $retTopics = $topicrow;
            
        }
    
        if ($i > 0) {
           // need to turn off the cache on the results array so set the max-age to 0 by adding $results entity to the cache dependencies.
          // This will clear our cache when this entity updates.
          $renderer = \Drupal::service('renderer');
          $renderer->addCacheableDependency($results, null);

          return  new \Symfony\Component\HttpFoundation\JsonResponse($retTopics);
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
 