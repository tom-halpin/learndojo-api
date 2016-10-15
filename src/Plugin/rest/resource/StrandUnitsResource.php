<?php
/**
 * @file
 * Contains Drupal\learndojoapi\Plugin\rest\resource\StrandUnitsResource.
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
 * Provides a resource to get learning dojo units by strand id.
 *
 * @RestResource(
 *   id = "strandunits",
 *   label = @Translation("Learn Dojo Strand Units"),
 *   uri_paths = {
 *     "canonical" = "/api/strandunits/{strandid}"
 *   }
 * )

 */
class StrandUnitsResource extends ResourceBase {


  /**
   *  A instance of entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */

  protected $entityManager;

  /*
   * Responds to GET requests.
   */
  public function get($strandid = NULL) {
      if ($strandid) {

        $results = db_query("SELECT u.id, u.strand_id as strandid, u.name, u.description, u.last_update, 
                             s.name as strandname, s.description as stranddescription, 
                             s.mission_id as missionid, m.name as missionname,
                             m.country_id as countryid, c.name as countryname  
                             FROM kaunit u, kastrand s, kacountry c, kamission m where 
                             s.mission_id = m.id and m.country_id = c.id and
                             u.strand_id = s.id and u.strand_id = :strandid", array(':strandid' => $strandid))->fetchAllAssoc('id');

        $i = 0;
        // create strandunits array
        $strandunits = array();

        foreach($results as $row)
        {
           // if defined retrieve reference to existing strandunit information else create and initialise a new one for this row of data
          if (isset($strandunits['strandunits']))
          { 
            $strandunititem = $strandunits['strandunits'];
          }
          else
          {
            $strandunititem = array
            (
              'countryid' => $row -> countryid,
              'countryname' => $row -> countryname,
              'missionid' => $row -> missionid,
              'missionname' => $row -> missionname,              
              'strandid' => $row -> strandid,
              'strandname' => $row -> strandname,
              'stranddescription' => $row -> stranddescription,
              'units' => array() // create unit array for strand
            );
            $strandunits['strandunits'] = $strandunititem;
          }
                    
          $unitid = $row -> id;
          
          // if defined retrieve reference to existing unit information else create and initialise a new one for this row of data
          $newunititem = false;
          $unititem = null;
          $unitkey = null;
          
          foreach ($strandunititem['units']['id'] as $key => $value) 
          {
            if($strandunititem['units'][$key] === $unitid)
            {
              $unititem = $strandunititem['units'][$key];
              $unitkey = $key;
              break;
            }
          }
          
          if(isset($unititem) == false)
          {
            $unititem = array
            (
              'id' => $unitid,
              'name' => $row -> name,
              'description' => $row -> description,
              'last_update' => $row -> last_update
            );
            $newunititem = true;            
          }
          
          // if previously flagged that we created a new unit item add to the units array for the strand, otherwise update the exising unit item for the strand
          if($newunititem)
          {
            $strandunititem['units'][] = $unititem;
          }
          else {
            $strandunititem['units'][$unitkey] = $unititem;
          }
          // update the root array with the updated arrays
          $strandunits['strandunits'] = $strandunititem;
          $i = $i + 1;
        }
    
        if ($i > 0) {
           // need to turn off the cache on the results array so set the max-age to 0 by adding $results entity to the cache dependencies.
          // This will clear our cache when this entity updates.
          $renderer = \Drupal::service('renderer');
          $renderer->addCacheableDependency($results, null);

          return  new \Symfony\Component\HttpFoundation\JsonResponse($strandunits);
        }
    
        throw new NotFoundHttpException(t('No Units found for strandid: ' . $strandid));
    }
    throw new NotFoundHttpException(t('strand not provided'));
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
 