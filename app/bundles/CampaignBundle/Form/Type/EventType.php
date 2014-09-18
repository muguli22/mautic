<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Form\Type;

use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class EventType
 *
 * @package Mautic\CampaignBundle\Form\Type
 */
class EventType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber());

        $builder->add('name', 'text', array(
            'label'      => 'mautic.campaign.event.name',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control'),
            'required'   => false
        ));

        $builder->add('description', 'text', array(
            'label'      => 'mautic.campaign.event.description',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control'),
            'required'   => false
        ));

        if ($options['data']['eventType'] == 'action') {
            $triggerMode = (empty($options['data']['triggerMode'])) ? 'immediate' : $options['data']['triggerMode'];
            $builder->add('triggerMode', 'button_group', array(
                'choices' => array(
                    'immediate' => 'mautic.campaign.form.type.immediate',
                    'interval'  => 'mautic.campaign.form.type.interval',
                    'date'      => 'mautic.campaign.form.type.date'
                ),
                'expanded'    => true,
                'multiple'    => false,
                'label_attr'  => array('class' => 'control-label'),
                'label'       => 'mautic.campaign.form.type',
                'empty_value' => false,
                'required'    => false,
                'attr'        => array(
                    'onchange' => 'Mautic.campaignToggleTimeframes();'
                ),
                'data'        => $triggerMode
            ));

            $attr = array(
                'class'    => 'form-control',
                'preaddon' => 'symbol-hashtag',
                'data-toggle' => 'datetime'
            );
            if ($triggerMode != 'date') {
                $attr['disabled'] = 'disabled';
            }
            $builder->add('triggerDate', 'text', array(
                'label'      => 'mautic.campaign.event.triggerdate',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => $attr
            ));

            $data = (empty($options['data']['triggerInterval'])) ? 1 : $options['data']['triggerInterval'];
            $attr = array(
                'class'    => 'form-control',
                'preaddon' => 'symbol-hashtag'
            );
            if ($triggerMode != 'interval') {
                $attr['disabled'] = 'disabled';
            }
            $builder->add('triggerInterval', 'number', array(
                'label'      => 'mautic.campaign.event.triggerinterval',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => $attr,
                'data'       => $data
            ));

            $data = (!empty($options['data']['triggerIntervalUnit'])) ? $options['data']['triggerIntervalUnit'] : 'd';
            $attr = array(
                'class' => 'form-control'
            );
            if ($triggerMode != 'interval') {
                $attr['disabled'] = 'disabled';
            }
            $builder->add('triggerIntervalUnit', 'choice', array(
                'choices'     => array(
                    'i' => 'mautic.campaign.event.intervalunit.i',
                    'h' => 'mautic.campaign.event.intervalunit.h',
                    'd' => 'mautic.campaign.event.intervalunit.d',
                    'm' => 'mautic.campaign.event.intervalunit.m',
                    'y' => 'mautic.campaign.event.intervalunit.y',
                ),
                'multiple'    => false,
                'label_attr'  => array('class' => 'control-label'),
                'label'       => false,
                'attr'        => $attr,
                'empty_value' => false,
                'required'    => false,
                'data'        => $data
            ));
        }

        if (!empty($options['settings']['formType'])) {
            $properties = (!empty($options['data']['properties'])) ? $options['data']['properties'] : null;
            $builder->add('properties', $options['settings']['formType'], array(
                'label' => false,
                'data'  => $properties
            ));
        }

        $builder->add('type', 'hidden');
        $builder->add('eventType', 'hidden');

        $update = !empty($properties);
        if (!empty($update)) {
            $btnValue = 'mautic.core.form.update';
            $btnIcon  = 'fa fa-pencil';
        } else {
            $btnValue = 'mautic.core.form.add';
            $btnIcon  = 'fa fa-plus';
        }

        $builder->add('buttons', 'form_buttons', array(
            'save_text' => $btnValue,
            'save_icon' => $btnIcon,
            'apply_text' => false,
            'container_class' => 'bottom-campaignevent-buttons'
        ));

        if (!empty($options["action"])) {
            $builder->setAction($options["action"]);
        }
    }


    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(array('settings'));
    }

    /**
     * @return string
     */
    public function getName() {
        return "campaignevent";
    }
}