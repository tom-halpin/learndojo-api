<?php
/**
 * @file
 * Contains Drupal\learndojoapi\Plugin\rest\resource\UnitTopicsResource.
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
 * Provides a resource to get learning dojo topics by unit.
 *
 * @RestResource(
 *   id = "unittopics",
 *   label = @Translation("Learn Dojo Topi Units"),
 *   uri_paths = {
 *     "canonical" = "/api/unittopics/{unitid}"
 *   }
 * )

 */
class UnitTopicsResource extends ResourceBase {


  /**
   *  A instance of entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */

  protected $entityManager;

  /*
   * Responds to GET requests.
   */
  public function get($unitid = NULL) {
      if ($unitid) {

        $results = db_query('SELECT h.id as countryid, h.name as countryname, 
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
                d.unit_id = :unitid', array(':unitid' => $unitid))->fetchAllAssoc('id');

        $i = 0;
        $outp = "{";
        foreach ($results as $row) {

            if ($i == 0){
                // first row
                $outp .= '"countryid":"'. $row -> countryid     . '",';
                $outp .= '"countryname":"'. $row -> countryname     . '",';
                $outp .= '"missionid":"'. $row -> missionid     . '",';
                $outp .= '"missionname":"'. $row -> missionname     . '",';            
                $outp .= '"strandid":"'. $row -> strandid     . '",';
                $outp .= '"strandname":"'. $row -> strandname     . '",';                
                $outp .= '"unitid":"'. $row -> unitid     . '",';
                $outp .= '"unitname":"'. $row -> unitname     . '",';
                $outp .= '"topics": [{';             
            }
            else {
                $outp .= ',{';
            }
            $outp .= '"id":"'. $row -> id     . '",';
            $outp .= '"name":"'. $row -> name     . '",';
            $outp .= '"description":"'. $row -> description. '",';                
            $outp .= '"corecontent":"'   . $row -> corecontent        . '",';
            $outp .= '"learningoutcome":"'   . $row -> learningoutcome        . '",';
            $outp .= '"externalTopic":"'   . $row -> externalTopic        . '",';
            $outp .= '"externalUrl":"'   . $row -> externalUrl        . '",';
            $outp .= '"difficultyindex":"'   . $row -> difficultyindex        . '",';
            $outp .= '"termid":"'   . $row -> termid        . '",';
            $outp .= '"weeknumber":"'   . $row -> weeknumber        . '",';
            $outp .= '"topictypeid":"'   . $row -> topictypeid        . '",';
            $outp .= '"topictypename":"'   . $row -> topictypename        . '",';
            $outp .= '"notes":"'   . $row -> notes        . '"}';            
            $i = $i + 1;
        }
        $outp .="]}";
        
        if ($i > 0) {
           // need to turn off the cache on the results array so set the max-age to 0 by adding $results entity to the cache dependencies.
          // This will clear our cache when this entity updates.
          $renderer = \Drupal::service('renderer');
          $renderer->addCacheableDependency($results, null);

          // note decoding JSON before returning it to avoid embedded "'s being converted to escaped UTF characters
          // as we are passing a string to JsonResponse and not an array
          return  new \Symfony\Component\HttpFoundation\JsonResponse(json_decode($outp));
        }
    
        throw new NotFoundHttpException(t('No Topics found for unit id: ' . $unitid));
    }
    throw new NotFoundHttpException(t('Unit not provided'));
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
 