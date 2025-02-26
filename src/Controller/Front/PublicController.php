<?php
/**
 * Created by PhpStorm.
 * User: erwan
 * Date: 9/15/16
 * Time: 11:00 AM
 */

namespace App\Controller\Front;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Back\Alert;
use App\Entity\Back\MultipleAlert;
use App\Entity\Back\SingleAlert;
use App\Form\Type\ProgramAlertType;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * @Route("/")
 */
class PublicController extends AbstractController
{

    /**
     * @Route("/{page}", name="front.public.index", requirements={"page": "\d+"})
     * @Template("Front/Public/index.html.twig")
     */
    public function indexAction(Request $request, ManagerRegistry $doctrine, $page = 1)
    {
        if ($request->get('shibboleth') == 1) {
            if ($request->get('error') == "activation") {
                $this->get('session')->getFlashBag()->add('warning', "Votre compte doit être activé par un administrateur avant de pouvoir vous connecter.");
            }
        }
        
        return array('user' => $this->getUser());
    }

    /**
     * @Route("/login", name="front.public.login")
     * @Template("Front/Public/login.html.twig")
     */
    public function loginAction(Request $request)
    {
        return array('user' => $this->getUser());
    }

    /**
     * @Route("/contact", name="front.public.contact")
     * @Template("Front/Public/contact.html.twig")
     */
    public function contactAction(ManagerRegistry $doctrine, Request $request)
    {
        // Récupération des établissements de la plate-forme
        $institutions = $doctrine->getRepository('App\Entity\Back\Institution')->findBy(array(), array('name' => 'ASC'));
        $instContacts = array();
        foreach ($institutions as $institution) {
            if ($institution->getEmail() !== null)
                $instContacts[] = $institution;
        }
        return array('etablissements' => $instContacts);
    }

    /**
     * @Route("/faq", name="front.public.faq", options={"expose"=true})
     * @Template("Front/Public/faq.html.twig")
     */
    public function faqAction(Request $request)
    {
        return array('contact_mail' => $this->getParameter('contact_mail'), 'front_url' => $this->getParameter('front_url'));
    }

    /**
     * @Route("/about", name="front.public.about")
     * @Template("Front/Public/about.html.twig")
     */
    public function aboutAction(Request $request)
    {
        return array('user' => $this->getUser());
    }

    /**
     * @Route("/legalNotice", name="front.public.legalNotice")
     * @Template("Front/Public/legalNotice.html.twig")
     */
    public function legalNoticeAction(Request $request)
    {
        return array('user' => $this->getUser());
    }

}
