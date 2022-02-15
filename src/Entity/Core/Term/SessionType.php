<?php

/**
 * Created by PhpStorm.
 * User: erwan
 * Date: 6/17/16
 * Time: 5:31 PM.
 */
namespace App\Entity\Core\Term;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Core\Term\AbstractTerm;
use App\Entity\Core\Term\VocabularyInterface;

/**
 * Type de session.
 *
 * @ORM\Table(name="session_type")
 * @ORM\Entity
 */
class SessionType extends AbstractTerm implements VocabularyInterface
{
    /**
     * @return mixed
     */
    public function getVocabularyName()
    {
        return 'Type de session';
    }

    public static function getVocabularyStatus()
    {
        return VocabularyInterface::VOCABULARY_NATIONAL;
    }
}
