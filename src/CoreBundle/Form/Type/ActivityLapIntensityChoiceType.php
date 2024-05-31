<?php

namespace Runalyze\Bundle\CoreBundle\Form\Type;

use Runalyze\Activity\LapIntensity;
use Runalyze\Bundle\CoreBundle\Form\AbstractTokenStorageAwareType;
use Runalyze\Bundle\CoreBundle\Form\ConfigurationManagerAwareTrait;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * ChoiceType for LapIntensity in the activity forms.
 * #TSC
 */
class ActivityLapIntensityChoiceType extends AbstractTokenStorageAwareType
{
    use ConfigurationManagerAwareTrait;

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'choices' => LapIntensity::getAll(),
            'choice_label' => function($intensity, $key, $index) {
                /** @var LapIntensity $intensity */
                return $intensity->getLabel();
            },
            'choice_value' => function (LapIntensity $intensity = null) {
                return $intensity ? $intensity->getValue() : '';
            }
        ));
    }

    public function getParent()
    {
        return ChoiceType::class;
    }
}
