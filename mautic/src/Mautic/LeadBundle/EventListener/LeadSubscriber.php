<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\EventListener;


use Mautic\ApiBundle\ApiEvents;
use Mautic\ApiBundle\Event\RouteEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\FormBundle\Event\FormBuilderEvent;
use Mautic\FormBundle\FormEvents;
use Mautic\LeadBundle\Event as Events;
use Mautic\LeadBundle\LeadEvents;
use Mautic\UserBundle\Event\UserEvent;
use Mautic\UserBundle\UserEvents;

/**
 * Class LeadSubscriber
 *
 * @package Mautic\LeadBundle\EventListener
 */
class LeadSubscriber extends CommonSubscriber
{

    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            CoreEvents::BUILD_MENU         => array('onBuildMenu', 0),
            CoreEvents::BUILD_ROUTE        => array('onBuildRoute', 0),
            CoreEvents::GLOBAL_SEARCH      => array('onGlobalSearch', 0),
            CoreEvents::BUILD_COMMAND_LIST => array('onBuildCommandList', 0),
            ApiEvents::BUILD_ROUTE         => array('onBuildApiRoute', 0),
            LeadEvents::LEAD_PRE_SAVE      => array('onLeadPreSave', 0),
            LeadEvents::LEAD_POST_SAVE     => array('onLeadPostSave', 0),
            LeadEvents::LEAD_POST_DELETE   => array('onLeadDelete', 0),
            LeadEvents::FIELD_PRE_SAVE      => array('onFieldPreSave', 0),
            LeadEvents::FIELD_POST_SAVE     => array('onFieldPostSave', 0),
            LeadEvents::FIELD_POST_DELETE   => array('onFieldDelete', 0),
            UserEvents::USER_PRE_DELETE    => array('onUserDelete', 0),
            FormEvents::FORM_ON_BUILD      => array('onFormBuilder', 0)
        );
    }

    public function onFormBuilder(FormBuilderEvent $event)
    {

    }
    /**
     * @param MenuEvent $event
     */
    public function onBuildMenu(MauticEvents\MenuEvent $event)
    {
        $security = $event->getSecurity();
        $path = __DIR__ . "/../Resources/config/menu/main.php";
        $items = include $path;
        $event->addMenuItems($items);
    }


    /**
     * @param RouteEvent $event
     */
    public function onBuildRoute(MauticEvents\RouteEvent $event)
    {
        $path = __DIR__ . "/../Resources/config/routing/routing.php";
        $event->addRoutes($path);
    }

    /**
     * @param MauticEvents\GlobalSearchEvent $event
     */
    public function onGlobalSearch(MauticEvents\GlobalSearchEvent $event)
    {
        $str = $event->getSearchString();
        if (empty($str)) {
            return;
        }

        $isCommand  = $this->translator->trans('mautic.core.searchcommand.is');
        $anonymous  = $this->translator->trans('mautic.lead.lead.searchcommand.isanonymous');
        $mine       = $this->translator->trans('mautic.core.searchcommand.ismine');
        $filter     = array("string" => $str, "force" => '');

        //only show results that are not anonymous so as to not clutter up things
        if (strpos($str, "$isCommand:$anonymous") === false) {
            $filter['force'] = " !$isCommand:$anonymous";
        }

        $permissions = $this->security->isGranted(
            array('lead:leads:viewown', 'lead:leads:viewother'),
            'RETURN_ARRAY'
        );
        if ($permissions['lead:leads:viewown'] || $permissions['lead:leads:viewother']) {
            //only show own leads if the user does not have permission to view others
            if (!$permissions['lead:leads:viewother']) {
                $filter['force'] .= " $isCommand:$mine";
            }

            $leads = $this->factory->getModel('lead')->getEntities(
                array(
                    'limit'  => 5,
                    'filter' => $filter
                ));

            if (count($leads) > 0) {
                $leadResults = array();

                foreach ($leads as $lead) {
                    $leadResults[] = $this->templating->renderResponse(
                        'MauticLeadBundle:Search:lead.html.php',
                        array('lead' => $lead)
                    )->getContent();
                }
                if (count($leads) > 5) {
                    $leadResults[] = $this->templating->renderResponse(
                        'MauticLeadBundle:Search:lead.html.php',
                        array(
                            'showMore'     => true,
                            'searchString' => $str,
                            'remaining'    => (count($leads) - 5)
                        )
                    )->getContent();
                }
                $leadResults['count'] = count($leads);
                $event->addResults('mautic.lead.lead.header.index', $leadResults);
            }
        }
    }

    /**
     * @param RouteEvent $event
     */
    public function onBuildApiRoute(RouteEvent $event)
    {
        $path = __DIR__ . "/../Resources/config/routing/api.php";
        $event->addRoutes($path);
    }

    /**
     * @param MauticEvents\CommandListEvent $event
     */
    public function onBuildCommandList(MauticEvents\CommandListEvent $event)
    {
        if ($this->security->isGranted(array('lead:leads:viewown', 'lead:leads:viewother'), "MATCH_ONE")) {
            $event->addCommands(
                'mautic.lead.lead.header.index',
                $this->factory->getModel('lead')->getCommandList()
            );
        }
    }

    /**
     * Obtain changes to enter into audit log
     *
     * @param Events\LeadEvent $event
     */
    public function onLeadPreSave(Events\LeadEvent $event)
    {
        //stash changes
        $this->changes = $event->getChanges();
    }

    /**
     * Add a lead entry to the audit log
     *
     * @param Events\LeadEvent $event
     */
    public function onLeadPostSave(Events\LeadEvent $event)
    {
        $lead = $event->getLead();
        if (!empty($this->changes)) {
            $details = $this->serializer->serialize($this->changes, 'json');
            if (isset($this->changes["fieldChangeset"])) {
                //a bit overkill but the only way I could get around JMS Serilizer's exposed settings for the lead entity
                $details         = json_decode($details);
                $details->fields = $this->changes["fieldChangeset"];
                $details         = json_encode($details);
            }
            $log = array(
                "bundle"    => "lead",
                "object"    => "lead",
                "objectId"  => $lead->getId(),
                "action"    => ($event->isNew()) ? "create" : "update",
                "details"   => $details,
                "ipAddress" => $this->request->server->get('REMOTE_ADDR')
            );
            $this->factory->getModel('auditlog')->writeToLog($log);

            //trigger the score change event
            if (!$event->isNew() && isset($this->changes["score"])) {
                $scoreEvent = new Events\ScoreChangeEvent($lead, $this->changes['score'][0], $this->changes['score'][0]);
                $this->dispatcher->dispatch(LeadEvents::LEAD_SCORE_CHANGE, $scoreEvent);
            }
        }
    }

    /**
     * Add a lead delete entry to the audit log
     *
     * @param Events\LeadEvent $event
     */
    public function onLeadDelete(Events\LeadEvent $event)
    {
        $lead = $event->getLead();
        $details = $this->serializer->serialize($lead, 'json');
        $log = array(
            "bundle"     => "lead",
            "object"     => "lead",
            "objectId"   => $lead->getId(),
            "action"     => "delete",
            "details"    => $details,
            "ipAddress"  => $this->request->server->get('REMOTE_ADDR')
        );
        $this->factory->getModel('auditlog')->writeToLog($log);
    }


    /**
     * Obtain changes to enter into audit log
     *
     * @param Events\LeadFieldEvent $event
     */
    public function onFieldPreSave(Events\LeadFieldEvent $event)
    {
        //stash changes
        $this->changes = $event->getChanges();
    }

    /**
     * Add a field entry to the audit log
     *
     * @param Events\LeadFieldEvent $event
     */
    public function onFieldPostSave(Events\LeadFieldEvent $event)
    {
        $field = $event->getField();
        if (!empty($this->changes)) {
            $details = $this->serializer->serialize($this->changes, 'json');

            $log = array(
                "bundle"    => "lead",
                "object"    => "field",
                "objectId"  => $field->getId(),
                "action"    => ($event->isNew()) ? "create" : "update",
                "details"   => $details,
                "ipAddress" => $this->request->server->get('REMOTE_ADDR')
            );
            $this->factory->getModel('auditlog')->writeToLog($log);
        }
    }

    /**
     * Add a field delete entry to the audit log
     *
     * @param Events\LeadEvent $event
     */
    public function onFieldDelete(Events\LeadFieldEvent $event)
    {
        $field = $event->getField();
        $details = $this->serializer->serialize($field, 'json');
        $log = array(
            "bundle"     => "lead",
            "object"     => "field",
            "objectId"   => $field->getId(),
            "action"     => "delete",
            "details"    => $details,
            "ipAddress"  => $this->request->server->get('REMOTE_ADDR')
        );
        $this->factory->getModel('auditlog')->writeToLog($log);
    }

    /**
     * Disassociate user from leads prior to user delete
     *
     * @param UserEvent $event
     */
    public function onUserDelete(UserEvent $event)
    {
        $this->factory->getModel('lead')->disassociateOwner($event->getUser()->getId());
    }
}