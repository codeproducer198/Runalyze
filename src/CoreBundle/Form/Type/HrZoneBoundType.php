<?php

namespace Runalyze\Bundle\CoreBundle\Form\Type;

use Runalyze\Calculation\Activity\HrZonesCalculator;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class HrZoneBoundType extends AbstractType implements DataTransformerInterface
{
    /** @var bool */
    protected $IsRequired = false;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer($this);

        if (isset($options['required'])) {
            $this->IsRequired = $options['required'];
        }
    }

    /**
     * @param bool $flag
     */
    public function setRequired($flag = true)
    {
        $this->IsRequired = $flag;
    }

    /**
     * @param  string $zone
     * @return string
     */
    public function transform($zone)
    {
        return $zone;
    }

    /**
     * @param  string|null $zone
     * @return string
     * @throws TransformationFailedException if $zone is null
     */
    public function reverseTransform($zone)
    {
        if (null === $zone) {
            if ($this->IsRequired) {
                throw new TransformationFailedException('Zone cannot be empty');
            }

            return null;
        } else {
            if (HrZonesCalculator::validateInputZone($zone)) {
                throw new TransformationFailedException('Zone is invalid');
            }
        }

        return $zone;
    }

    public function getParent()
    {
        return TextType::class;
    }
}
