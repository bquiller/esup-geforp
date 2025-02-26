<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 17/07/14
 * Time: 11:51.
 */
namespace App\Security\Authorization\AccessRight\Trainer;

use App\AccessRight\AbstractAccessRight;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class AllTrainerCreateAccessRight extends AbstractAccessRight
{
    /**
     * @return string
     */
    public function getLabel()
    {
        return 'Création des formateurs de tous les centres';
    }

    /**
     * Checks if the access right supports the given class.
     *
     * @param string
     *
     * @return bool
     */
    public function supportsClass($class)
    {
        if ($class === 'App\Entity\Back\Trainer') {
            return true;
        }

        return false;
    }

    /**
     * Returns the vote for the given parameters.
     */
    public function isGranted(TokenInterface $token, $object = null, $attribute)
    {
        if ($attribute !== 'CREATE') return false;

        return true;
    }
}
