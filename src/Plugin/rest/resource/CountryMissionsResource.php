<?php
/**
 * @file
 * Contains Drupal\learndojoapi\Plugin\rest\resource\CountryMissionsResource.
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
 * Provides a resource to get learning dojo missions by country.
 *
 * @RestResource(
 *   id = "countrymissions",
 *   label = @Translation("Learn Dojo Country Missions"),
 *   uri_paths = {
 *     "canonical" = "/api/countrymissions/{countryid}"
 *   }
 * )

 */
class CountryMissionsResource extends ResourceBase {


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

        $results = db_query("SELECT m.id, m.country_id as countryid, m.name, m.description, m.last_update, 
                             c.name as countryname, c.description as countrydescription FROM kamission m, 
                             kacountry c where m.country_id = c.id and m.country_id = :countryid", array(':countryid' => $countryid))->fetchAll();
                             
        $i = 0;
        // create countrymissions array
        $countrymissions = array();

        foreach($results as $row)
        {
           // if defined retrieve reference to existing countrymission information else create and initialise a new one for this row of data
          if (isset($countrymissions['countrymissions']))
          { 
            $countrymissionitem = $countrymissions['countrymissions'];
          }
          else
          {
            $countrymissionitem = array
            (
              'countryid' => $row -> countryid,
              'countryname' => $row -> countryname,
              'countrydescription' => $row -> countrydescription,
              'missions' => array() // create mission array for country
            );
            $countrymissions['countrymissions'] = $countrymissionitem;
          }
                    
          $missionid = $row -> id;
          
          // if defined retrieve reference to existing mission information else create and initialise a new one for this row of data
          $newmissionitem = false;
          $missionitem = null;
          $missionkey = null;
          
          foreach ($countrymissionitem['missions']['id'] as $key => $value) 
          {
            if($countrymissionitem['missions'][$key] === $missionid)
            {
              $missionitem = $countrymissionitem['missions'][$key];
              $missionkey = $key;
              break;
            }
          }
          
          if(isset($missionitem) == false)
          {
            $missionitem = array
            (
              'id' => $missionid,
              'name' => $row -> name,
              'description' => $row -> description,
              'last_update' => $row -> last_update
            );
            $newmissionitem = true;            
          }
          
          // if previously flagged that we created a new mission item add to the missions array for the country, otherwise update the exising mission item for the country
          if($newmissionitem)
          {
            $countrymissionitem['missions'][] = $missionitem;
          }
          else {
            $countrymissionitem['missions'][$missionkey] = $missionitem;
          }
          // update the root array with the updated arrays
          $countrymissions['countrymissions'] = $countrymissionitem;
          $i = $i + 1;
        }

        if ($i > 0) {
           // need to turn off the cache on the results array so set the max-age to 0 by adding $results entity to the cache dependencies.
          // This will clear our cache when this entity updates.
          $renderer = \Drupal::service('renderer');
          $renderer->addCacheableDependency($results, null);

          return  new \Symfony\Component\HttpFoundation\JsonResponse($countrymissions);
        }
    
        throw new NotFoundHttpException(t('No Missions found for countryid: ' . $countryid));
    }
    throw new NotFoundHttpException(t('countryid not provided'));
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
 