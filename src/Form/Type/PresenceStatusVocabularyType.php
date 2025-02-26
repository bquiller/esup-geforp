<?php

namespace App\Form\Type;

use App\Entity\Term\Presencestatus;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

class PresenceStatusVocabularyType extends VocabularyType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('status', ChoiceType::class, array(
            'label' => 'Statut élémentaire',
            'expanded' => true,
            'multiple' => false,
            'required' => true,
            'choices' => array(
                'Présent' => Presencestatus::STATUS_PRESENT,
                'Absent' => Presencestatus::STATUS_ABSENT,
            ),
        ));
        $builder->add('machinename', null, array(
            'label' => 'Libellé court',
        ));
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return VocabularyType::class;
    }
}
