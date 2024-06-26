<?php

namespace Runalyze\Bundle\CoreBundle\Form;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\Equipment;
use Runalyze\Bundle\CoreBundle\Entity\EquipmentTypeRepository;
use Runalyze\Bundle\CoreBundle\Form\Type\DistanceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class EquipmentType extends AbstractType
{
    /** @var EquipmentTypeRepository */
    protected $EquipmentRepository;

    /** @var TokenStorage */
    protected $TokenStorage;

    public function __construct(EquipmentTypeRepository $equipmentTypeRepository, TokenStorage $tokenStorage)
    {
        $this->EquipmentRepository = $equipmentTypeRepository;
        $this->TokenStorage = $tokenStorage;
    }

    /**
     * @return Account
     */
    protected function getAccount()
    {
        $account = $this->TokenStorage->getToken() ? $this->TokenStorage->getToken()->getUser() : null;

        if (!($account instanceof Account)) {
            throw new \RuntimeException('Equipment type must have a valid account token.');
        }

        return $account;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Equipment $equipment */
        $equipment = $builder->getData();

        $builder
            ->add('name', TextType::class, [
                'label' => 'Name',
                'required' => true,
                'attr' => [
                    'autofocus' => true
                ]
            ])
            ->add('additionalKm', DistanceType::class, [
                'label' => 'prev. distance',
                'required' => true,
            ])
            ->add('dateStart', DateType::class, [
                'label' => 'Start of use',
                'widget' => 'single_text',
                'format' => 'dd.MM.yyyy',
                'html5' => false,
                'required' => false,
                // #TSC: fix if new equip set current date, else use stored date
                'data' => (null === $equipment->getId() ? new \DateTime("now") : $equipment->getDateStart()),
                'attr' => ['class' => 'pick-a-date small-size', 'placeholder' => 'dd.mm.YYYY']
            ])
            ->add('dateEnd', DateType::class, [
                'label' => 'End of use',
                'widget' => 'single_text',
                'format' => 'dd.MM.yyyy',
                'html5' => false,
                'required' => false,
                'attr' => ['class' => 'pick-a-date small-size', 'placeholder' => 'dd.mm.YYYY']
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes',
                'required' => false,
                'empty_data' => '',
                'attr' => ['class' => 'fullwidth']
            ])
            // #TSC allow only sports from the type
            ->add('sport', EntityType::class, [
                'class' => Sport::class,
                'choices' => $equipment->getType()->getSport(),
                'choice_label' => 'name',
                'label' => 'Assigned sports',
                'attr' => [
                    'class' => 'chosen-select full-size',
                    'data-placeholder' => 'Choose sport(s)'
                ],
                'multiple' => true,
                'required' => false,
                'expanded' => false
            ]);

        if (null === $equipment->getId()) {
            $builder
                ->add('type', ChoiceType::class, [
                    'choices' => $this->EquipmentRepository->findAllFor($this->getAccount()),
                    'choice_label' => 'name',
                    'choice_value' => 'getId',
                    'label' => 'Category',
                    'disabled' => true
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Runalyze\Bundle\CoreBundle\Entity\Equipment'
        ]);
    }
}
