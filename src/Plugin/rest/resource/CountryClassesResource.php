<?php
/**
 * @file
 * Contains Drupal\learndojoapi\Plugin\rest\resource\CountryClassesResource.
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
 * Provides a resource to get learning dojo classes by countryid.
 *
 * @RestResource(
 *   id = "countryclasses",
 *   label = @Translation("Learn Dojo Country Classes"),
 *   uri_paths = {
 *     "canonical" = "/api/countryclasses/{countryid}"
 *   }
 * )

 */
class CountryClassesResource extends ResourceBase {


  /**
   *  A instance of entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */

  protected $entityManager;

  /*
   * Responds to GET requests.
   */
  public function get($countryid = NULL) {
      if ($countryid) {

        $results = db_query('SELECT  
                    x.name as countryname,
                    a.id as missionid, a.name as missionname, 
                    b.id as strandid, b.name as strandname,
                    c.id as unitid, c.name as unitname,
                    d.id as topicid, d.name as topicname, d.ka_url as externalUrl
                FROM kacountry x, kamission a, kastrand b, kaunit c, katopic d
                where 
                    x.id = a.country_id AND
                    a.id = b.mission_id AND 
                    b.id = c.strand_id AND
                    c.id = d.unit_id AND  
                    a.country_id =:countryid 
                ORDER BY a.country_id, b.mission_id, c.strand_id, d.unit_id, topicid', array(':countryid' => $countryid))->fetchAll();

       // create classes array
        $classes = array();

        
        $i = 0;
        foreach($results as $row)
        {
           // if defined retrieve reference to existing country information else create and initialise a new one for this row of data
          if (isset($classes['classes']))
          { 
            $countryitem = $classes['classes'];
          }
          else
          {
            $countryitem = array
            (
             'countryid' => $countryid,
             'countryname' => $row->countryname,
             'missions' => array() // create strands array for mission
            );
            $classes['classes'] = $countryitem;
          }
                    
          $missionid = $row -> missionid;
          $strandid = $row -> strandid;
          $unitid = $row -> unitid;
          $topicid = $row -> topicid;
          
          // if defined retrieve reference to existing mission information else create and initialise a new one for this row of data
          $newmissionitem = false;
          $missionitem = null;
          $missionkey = null;
          
          foreach ($countryitem['missions'] as $key => $value) 
          {
            if($countryitem['missions'][$key][missionid] === $missionid)
            {
              $missionitem = $countryitem['missions'][$key];
              $missionkey = $key;
              break;
            }
          }
          
          if(isset($missionitem) == false)
          {
            $missionitem = array
            (
             'missionid' => $missionid,
             'missionname' => $row -> missionname,
             'strands' => array() // create strands array for mission
            );
            $newmissionitem = true;            
          }
          
          // if defined retrieve reference to existing strand information else create and initialise a new one for this row of data
          $newstranditem = false;
          $stranditem = null;
          $strandkey = null;
          
          foreach ($missionitem['strands'] as $key => $value) 
          {
            if($missionitem['strands'][$key][strandid] === $strandid)
            {
              $stranditem = $missionitem['strands'][$key];
              $strandkey = $key;
              break;
            }
          }
          
          if(isset($stranditem) == false)
          {
            $stranditem = array
            (
             'strandid' => $strandid,
             'strandname' => $row -> strandname,
             'units' => array() // create units array for strand
            );
            $newstranditem = true;            
          }
          
          // if defined retrieve reference to existing unit information else create and initialise a new one for this row of data      
          $newunititem = false;
          $unititem = null;
          $unitkey = null;
          
          foreach ($stranditem['units'] as $key => $value) 
          {
            if($stranditem['units'][$key][unitid] === $unitid)
            {
              $unititem = $stranditem['units'][$key];
              $unitkey = $key;
              break;
            }
          }
          
          if(isset($unititem) == false)
          {
            $unititem = array
            (
                  'unitid' => $unitid,
                  'unitname' => $row -> unitname,
                  'topics' => array() // create topics array for unit
            );
            $newunititem = true;           
          }
          
          // add the topic information to the unititem one unique topic per row in the result set
          $topicitem = array
          (
             'topicid' => $topicid,
             'topicname' => $row -> topicname,
             'externalurl' => $row -> externalUrl
          );
          $unititem['topics'][] = $topicitem;
          
          // if previously flagged that we created a new unit item add to the units array for the strand, otherwise update the exising unit item for the strand
          if($newunititem)
          {
            $stranditem['units'][] = $unititem;
          }
          else {
            $stranditem['units'][$unitkey] = $unititem;
          }
          // if previously flagged that we created a new strand item add to the strands array for the mission, otherwise update the exising strand item for the mission
          if($newstranditem)
          {
            $missionitem['strands'][] = $stranditem;
          }
          else {
            $missionitem['strands'][$strandkey] = $stranditem;
          }
          // if previously flagged that we created a new mission item add to the missions array for the country, otherwise update the exising mission item for the country
          if($newmissionitem)
          {
            $countryitem['missions'][] = $missionitem;
          }
          else {
            $countryitem['missions'][$missionkey] = $missionitem;
          }
          // update the root array with the updated arrays
          $classes['classes'] = $countryitem;
          $i = $i + 1;
        }
    
        if ($i > 0) {
           // need to turn off the cache on the results array so set the max-age to 0 by adding $results entity to the cache dependencies.
          // This will clear our cache when this entity updates.
          $renderer = \Drupal::service('renderer');
          $renderer->addCacheableDependency($results, null);

          return  new \Symfony\Component\HttpFoundation\JsonResponse($classes);
        }
    
        throw new NotFoundHttpException(t('No Content found for country id: ' . $countryid));
    }
    throw new NotFoundHttpException(t('Country not provided'));
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
 