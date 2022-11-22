<?php

namespace App\Controller\Core;

use App\Entity\Back\Institution;
use App\Entity\Back\Trainee;
use App\Entity\Back\Organization;
use App\Entity\Term\Publictype;
use App\Entity\Term\Title;
use App\Form\Type\AbstractTraineeType;
use App\Repository\TraineeSearchRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use App\Entity\Core\AbstractTrainee;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use App\Form\Type\ChangeOrganizationType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Class TraineeController.
 *
 * @Route("/trainee")
 */
abstract class AbstractTraineeController extends AbstractController
{
    /**
     * @var string
     */
    protected $traineeClass = AbstractTrainee::class;

    /**
     * @param Request $request
     * 
     * @Route("/search", name="trainee.search", options={"expose"=true}, defaults={"_format" = "json"})
     * @Rest\View(serializerGroups={"Default", "trainee"}, serializerEnableMaxDepthChecks=true)
     * 
     * @return array
     * 
     * @throws \Exception
     */
    public function searchAction(Request $request, ManagerRegistry $doctrine, TraineeSearchRepository $traineeRepository)
    {
        $keywords = $request->request->get('keywords', 'NO KEYWORDS');
        $filters = $request->request->get('filters', 'NO FILTERS');
        $query_filters = $request->request->get('query_filters', 'NO QUERY FILTERS');
        $aggs = $request->request->get('aggs', 'NO AGGS');
        $query = $request->request->get('query', 'NO QUERY');
        $page = $request->request->get('page', 'NO PAGE');
        $size = $request->request->get('size', 'NO SIZE');
        $sorts = $request->request->get('sorts', 'NO SORTS');
        $fields = $request->request->get('fields', 'NO FIELDS');

        // Recherche avec les filtres
        $ret = $traineeRepository->getTraineesList($keywords, $filters, $page, $size, $sorts, $fields);

        // Recherche pour aggs et query_filters
        $tabAggs = array();
        $tabAggs = $this->constructAggs($aggs, $keywords, $query_filters, $doctrine, $traineeRepository);

        // Recherche avec query (pour autocompletion)
        if (isset($query)) {
            // on transforme le champ 'query' en 'keywords'
            if (isset($query['match']['fullname.autocomplete']['query'])) {
                $keywords = $query['match']['fullname.autocomplete']['query'];
                $ret = $traineeRepository->getTraineesList($keywords, $filters, $page, $size, $sorts, $fields);
            }
        }

        // Concatenation des resultats
        $ret['aggs'] = $tabAggs;

        return $ret;
    }

    /**
     * @param Request $request
     *
     * @Route("/create", name="trainee.create", options={"expose"=true}, defaults={"_format" = "json"})
     * @Rest\View(serializerGroups={"Default", "trainee"}, serializerEnableMaxDepthChecks=true)
     * 
     * @return array
     */
    public function createAction(Request $request, ManagerRegistry $doctrine)
    {
        /** @var AbstractTrainee $trainee */
        $trainee = new $this->traineeClass();

        //trainee can't be created if user has no rights for it
        if (!$this->isGranted('CREATE', $trainee)) {
            throw new AccessDeniedException('Action non autorisée');
        }

        $form = $this->createForm(AbstractTraineeType::class, $trainee);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $trainee->setCreatedAt(new \DateTime('now'));
                $trainee->setUpdatedAt(new \DateTime('now'));
                $em = $doctrine->getManager();
                $em->persist($trainee);
                $em->flush();
            }
        }

        return array('form' => $form->createView(), 'trainee' => $trainee);
    }

    /**
     * @param Request $request
     * @param AbstractTrainee $trainee
     * 
     * @Route("/{id}/view", requirements={"id" = "\d+"}, name="trainee.view", options={"expose"=true}, defaults={"_format" = "json"})
     * @IsGranted("VIEW", subject="trainee")
     * @ParamConverter("trainee", class="App\Entity\Core\AbstractTrainee", options={"id" = "id"})
     * @Rest\View(serializerGroups={"Default", "trainee"}, serializerEnableMaxDepthChecks=true)
     * 
     * @return array
     */
    public function viewAction(Request $request,  ManagerRegistry $doctrine, AbstractTrainee $trainee)
    {
        // access right is checked inside controller, so to be able to send specific error message
        if (!$this->isGranted('EDIT', $trainee)) {
            if ($this->isGranted('VIEW', $trainee)) {
                return array('trainee' => $trainee);
            }

            throw new AccessDeniedException("Vous n'avez pas accès aux informations détaillées de cet utilisateur");
        }

        $form = $this->createForm(AbstractTraineeType::class, $trainee);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $trainee->setUpdatedAt(new \DateTime('now'));
                $em = $doctrine->getManager();
                $em->persist($trainee);
                $em->flush();
            }
        }

        return array('form' => $form->createView(), 'trainee' => $trainee);
    }

    /**
     * @param Request $request
     * @param AbstractTrainee $trainee
     * 
     * @Route("/{id}/toggleActivation", requirements={"id" = "\d+"}, name="trainee.toggleActivation", options={"expose"=true}, defaults={"_format" = "json"})
     * @ParamConverter("trainee", class="SygeforCoreBundle:AbstractTrainee", options={"id" = "id"})
     * @Rest\View(serializerGroups={"Default", "trainee"}, serializerEnableMaxDepthChecks=true)
     * @Method("POST")
     * 
     * @return array
     */
    public function toggleActivationAction(Request $request, AbstractTrainee $trainee)
    {
        //access right is checked inside controller, so to be able to send specific error message
        if (!$this->isGranted('EDIT', $trainee)) {
            throw new AccessDeniedException("Vous n'avez pas accès aux informations détaillées de cet utilisateur");
        }

        $trainee->setIsActive(!$trainee->getIsActive());
        $this->getDoctrine()->getManager()->flush();

        if ($trainee->getIsActive()) {
	        $this->get('notification.mailer')->send('trainee.activated', $trainee, [
		        'trainee' => $trainee,
	        ]);
        }

        return array('trainee' => $trainee);
    }

    /**
     * @param AbstractTrainee $trainee
     *
     * @Route("/{id}/remove", name="trainee.delete", options={"expose"=true}, defaults={"_format" = "json"})
     * @IsGranted("DELETE", subject="trainee")
     * @Method("POST")
     * @ParamConverter("trainee", class="SygeforCoreBundle:AbstractTrainee", options={"id" = "id"})
     * @Rest\View(serializerGroups={"Default", "trainee"}, serializerEnableMaxDepthChecks=true)
     * 
     * @return array
     */
    public function deleteAction(AbstractTrainee $trainee, ManagerRegistry $doctrine)
    {
        $em = $doctrine->getManager();
        $em->remove($trainee);
        $em->flush();

        return array();
    }

    private function constructAggs($aggs, $keyword, $query_filters, $doctrine, $traineeRepository)
    {
        $tabAggs = array();

        // CONSTRUCTION CIVILITE
        if(isset( $aggs['title'])){
            $allTitles = $doctrine->getRepository(Title::class)->findAll();

            $i = 0; $tabTitles = array();
            //Pour chaque civilité on teste la requête
            foreach($allTitles as $title){
                $nbTraineesTitles = $traineeRepository->getNbTrainees($query_filters, $keyword, $aggs, $title->getName());
                if ($nbTraineesTitles > 0) {
                    $tabTitles[$i] = [ 'key' => $title->getName(), 'doc_count' => $nbTraineesTitles];
                    $i++;
                }
            }
            $tabAggs['title']['buckets'] = $tabTitles;
        }

        // CONSTRUCTION ETABLISSEMENT
        if (isset($aggs['institution.name.source'])) {
            $allInst = $doctrine->getRepository(Institution::class)->findAll();

            $i = 0; $tabInst = array();
            //Pour chaque établissement on teste la requête
            foreach($allInst as $inst){
                $nbTraineesInst = $traineeRepository->getNbTrainees($query_filters, $keyword, $aggs, $inst->getName());
                if ($nbTraineesInst > 0) {
                    $tabInst[$i] = [ 'key' => $inst->getName(), 'doc_count' => $nbTraineesInst];
                    $i++;
                }
            }
            $tabAggs['institution.name.source']['buckets'] = $tabInst;
        }

        // CONSTRUCTION PUBLIC TYPE
        if(isset( $aggs['publicType.source'])){
            $allPublictypes = $doctrine->getRepository(Publictype::class)->findAll();

            $i = 0; $tabPublicTypes = array();
            //Pour chaque public type on teste la requête
            foreach($allPublictypes as $pt){
                $nbTraineesPt = $traineeRepository->getNbTrainees($query_filters, $keyword, $aggs, $pt->getName());
                if ($nbTraineesPt > 0) {
                    $tabPublicTypes[$i] = [ 'key' => $pt->getName(), 'doc_count' => $nbTraineesPt];
                    $i++;
                }
            }
            $tabAggs['publicType.source']['buckets'] = $tabPublicTypes;
        }

        return $tabAggs;
    }
}
