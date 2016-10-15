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
                             m.name as missionname, m.description as missiondescription, 
                             m.country_id as countryid, c.name as countryname 
                             FROM kastrand s, kamission m, kacountry c where 
                             s.mission_id = m.id and m.country_id = c.id 
                             and s.mission_id = :missionid", array(':missionid' => $missionid))->fetchAll();


        $i = 0;
        // create missionstrands array
        $missionstrands = array();
        
        foreach($results as $row)
        {
           // if defined retrieve reference to existing missionstrand information else create and initialise a new one for this row of data
          if (isset($missionstrands['missionstrands']))
          { 
            $missionstranditem = $missionstrands['missionstrands'];
          }
          else
          {
            $missionstranditem = array
            (
              'countryid' => $row -> countryid,
              'countryname' => $row -> countryname,
              'missionid' => $row -> missionid,
              'missionname' => $row -> missionname,              
              'missiondescription' => $row -> missiondescription,
              'strands' => array() // create strand array for mission
            );
            $missionstrands['missionstrands'] = $missionstranditem;
          }
                    
          $strandid = $row -> id;
          
          // if defined retrieve reference to existing strand information else create and initialise a new one for this row of data
          $newstranditem = false;
          $stranditem = null;
          $strandkey = null;
          
          foreach ($missionstranditem['strands']['id'] as $key => $value) 
          {
            if($missionstranditem['strands'][$key] === $strandid)
            {
              $stranditem = $missionstranditem['strands'][$key];
              $strandkey = $key;
              break;
            }
          }
          
          if(isset($stranditem) == false)
          {
            $stranditem = array
            (
              'id' => $strandid,
              'name' => $row -> name,
              'description' => $row -> description,
              'last_update' => $row -> last_update
            );
            $newstranditem = true;            
          }
          
          // if previously flagged that we created a new strand item add to the strands array for the mission, otherwise update the exising strand item for the country
          if($newstranditem)
          {
            $missionstranditem['strands'][] = $stranditem;
          }
          else {
            $missionstranditem['strands'][$strandkey] = $stranditem;
          }
          // update the root array with the updated arrays
          $missionstrands['missionstrands'] = $missionstranditem;
          $i = $i + 1;
        }
            
        if ($i > 0) {
           // need to turn off the cache on the results array so set the max-age to 0 by adding $results entity to the cache dependencies.
          // This will clear our cache when this entity updates.
          $renderer = \Drupal::service('renderer');
          $renderer->addCacheableDependency($results, null);

          return  new \Symfony\Component\HttpFoundation\JsonResponse($missionstrands);
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
 