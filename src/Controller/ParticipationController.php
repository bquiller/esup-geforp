<?php

namespace App\Controller;

use App\Controller\Core\AbstractParticipationController;
use App\Entity\Participation;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormError;
use Doctrine\Common\Collections\ArrayCollection;



/**
 * @Route("/participation")
 */
class ParticipationController extends AbstractParticipationController
{
    protected $participationClass = Participation::class;
}