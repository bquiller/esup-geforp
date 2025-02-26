<?php

namespace App\Entity\Term;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use App\Entity\Term\AbstractTerm;
use App\Entity\Term\VocabularyInterface;
use App\Form\Type\TrainingcategoryType;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Catégorie de formation.
 *
 * @ORM\Table(name="training_category")
 * @ORM\Entity
 */
class Trainingcategory extends AbstractTerm implements VocabularyInterface
{
    /**
     * @var string
     * @ORM\Column(name="trainingType", type="string", length=256, nullable=true)
     * @Assert\NotNull(message="Vous devez renseigner un type de formation")
     * @Serializer\Groups({"Default", "api"})
     */
    private $trainingType;

    /**
     * @return string
     */
    public function getTrainingType()
    {
        return $this->trainingType;
    }

    /**
     * @param string $trainingType
     */
    public function setTrainingType($trainingType)
    {
        $this->trainingType = $trainingType;
    }

    /**
     * @return mixed
     */
    public function getVocabularyName()
    {
        return 'Catégorie de formation';
    }

    public static function getFormType()
    {
        return TrainingcategoryType::class;
    }

    public static function getVocabularyStatus()
    {
        return VocabularyInterface::VOCABULARY_NATIONAL;
    }
}
