<?php

namespace App\Repository;

use App\Entity\Core\AbstractSession;
use App\Entity\Back\Session;
use App\Entity\Term\Theme;
use App\Entity\Back\Internship;
use App\Entity\Back\Organization;
use App\Entity\Back\Trainer;
use App\Entity\Back\Participation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

class SessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Session::class);
    }

    public function getSessionsProgram($keyword, $filters)
    {
        $qb = $this->createQueryBuilder('s');
        $qb
            ->select(' s')
            ->innerJoin('s.training', 'tr', 'WITH', 'tr = s.training')
            ->innerJoin('tr.organization', 'o', 'WITH', 'o = tr.organization')
            ->innerJoin('tr.theme', 'th', 'WITH', 'th = tr.theme');

        // FILTRE KEYWORD
        if ($keyword != 'NO KEYWORDS') {
            $qb
                ->where('s.name LIKE :keyword')
                /* addcslashes empêchera des manipulations malveillantes éventuelles */
                ->setParameter('keyword', '%' . addcslashes($keyword, '%_') . '%');
        }

        // FILTRE DISPLAYONLINE pour affichage stagiaire
        $qb->andWhere('s.displayonline = 1');

        // FILTRE CENTRE
        if (isset($filters['training.organization.name.source'])) {
            $qb
                ->andWhere('s.training = tr.id')
                ->andWhere('tr.organization = o.id')
                ->andWhere('o.code in (:centers)')
                ->setParameter('centers', $filters['training.organization.name.source']);
        }

        //FILTRE DATE
        if( isset($filters['datebegin']) ) {
            /* La date envoyée par le formulaire en JS a un format : "dd/mm/yy - dd/mm/yy" il faut donc séparer les 2 dates */
            $dates = explode('-', $filters["datebegin"]);
            /* on retire les caractères non utiles */
            $from = str_replace('/','-', $dates[0]);
            $to = str_replace('/', '-', $dates[1]);
            /* on convertit au même format qu'en base de données */
            $dateFrom = date('Y/m/d 00:00:00' ,strtotime($from));
            $dateTo = date('Y/m/d 00:00:00',strtotime($to));

            $qb
                /* si la date de début d'une session est entre les 2 dates envoyées dans le formulaire */
                ->andWhere("s.datebegin BETWEEN :dateFrom AND :dateTo")
                ->setParameter('dateFrom', $dateFrom)
                ->setParameter('dateTo', $dateTo);
        }

        //FILTRE THEME
        if( isset($filters['theme.name'])) {
            $qb
                ->andWhere('s.training = tr.id')
                ->andWhere('tr.theme = th.id')
                ->andWhere('th.name in (:themes)')
                ->setParameter('themes', $filters['theme.name']);
        }

        // TRI DES RESULTATS
        $qb->addOrderBy('th.name')
            ->addOrderBy('s.datebegin')
            ->addOrderBy('s.name');

        $query = $qb->getQuery();

        return $result = $query->getResult();
    }

    public function getSessionsList($keyword, $filters, $page, $pageSize, $sorts, $fields)
    {
        $qb = $this->createQueryBuilder('s');
        $qb
            ->select(' s');

            // FILTRE KEYWORD
        $qb
            ->where('s.name LIKE :keyword')
            /* addcslashes empêchera des manipulations malveillantes éventuelles */
            ->setParameter('keyword', '%' . addcslashes($keyword, '%_') . '%');


        if ((isset($filters['training.organization.name.source'])) ||
            ( isset($filters['theme.name']))
            ){
            // join sur training
            $qb->innerJoin('s.training', 'tr', 'WITH', 'tr = s.training');

            // FILTRE CENTRE
            if (isset($filters['training.organization.name.source'])) {
                $qb
                    ->innerJoin('tr.organization', 'o', 'WITH', 'o = tr.organization')
                    ->andWhere('o.name in (:centers)')
                    ->setParameter('centers', $filters['training.organization.name.source']);
            }

            //FILTRE THEME
            if( isset($filters['theme.name'])) {
                $qb
                    ->innerJoin('tr.theme', 'th', 'WITH', 'th = tr.theme')
                    ->andWhere('th.name in (:themes)')
                    ->setParameter('themes', $filters['theme.name']);
            }
        }


        // FILTRE ANNEE
        if (isset($filters['year'])) {
            $qb
                /* On récupère l'année du dateBegin (à l'aide d'une doctrine extension) */
                ->andWhere('YEAR(s.datebegin) in (:years)')
                ->setParameter('years', $filters['year']);
        }

        // FILTRE SEMESTRE
        if( isset($filters['semester']) ) {
            if ($filters['semester']  == 1) {
                $monthFrom = 1; $monthTo = 6;
            } else {
                $monthFrom = 7; $monthTo = 12;
            }
            $qb
                ->andWhere('MONTH(s.datebegin) BETWEEN :monthFrom and :monthTo')
                ->setParameter('monthFrom', $monthFrom)
                ->setParameter('monthTo', $monthTo);
        }

        //FILTRE DATE
        if( isset($filters['datebegin']) ) {
            /* La date envoyée par le formulaire en JS a un format : "dd/mm/yy - dd/mm/yy" il faut donc séparer les 2 dates */
            $dates = explode('-', $filters["datebegin"]);
            /* on retire les caractères non utiles */
            $from = str_replace('/','-', $dates[0]);
            $to = str_replace('/', '-', $dates[1]);
            /* on convertit au même format qu'en base de données */
            $dateFrom = date('Y/m/d 00:00:00' ,strtotime($from));
            $dateTo = date('Y/m/d 00:00:00',strtotime($to));

            $qb
                /* si la date de début d'une session est entre les 2 dates envoyées dans le formulaire */
                ->andWhere("s.datebegin BETWEEN :dateFrom AND :dateTo")
                ->setParameter('dateFrom', $dateFrom)
                ->setParameter('dateTo', $dateTo);
        }

        // FILTRE INSCRIPTION (0,1,2,3)
        if( isset($filters['registration']) ) {
            $qb
                ->andWhere('s.registration in (:registrations)')
                ->setParameter('registrations', $filters['registration']);
        }

        // FILTRE STATUT (0,1,2)
        if( isset($filters['status']) ){
            $qb
                ->andWhere('s.status in (:status)')
                ->setParameter('status', $filters['status']);
        }

        // FILTRE DISPLAYONLINE (F,T) ou (0,1) ?
        if( isset($filters['displayOnline']) ){
            $qb
                ->andWhere('s.displayonline = :displayOnline')
                ->setParameter('displayOnline', $filters['displayOnline']);
        }

        // FILTRE FORMATION (nom de la formation)
        if( isset($filters['training.name.source']) ) {
            $qb
                ->andWhere('s.name in (:trainings)')
                ->setParameter('trainings', $filters['training.name.source']);
        }

        //FILTRE PROMOTION (true,false) = (0,1)
        if( isset($filters['promote']) ) {
            $qb
                ->andWhere('s.promote = :promote')
                ->setParameter('promote', $filters['promote']);
        }

        // FILTRE FORMATEUR
        if( isset($filters['participations.trainer.fullName']) ) {
            /* le front envoie un full name (prénom+nom), je le découpe et ne récupère que le nom de famille */
            $fullName = explode(" ", $filters['participations.trainer.fullName']);
            $lastName = array_pop($fullName);
            $firstName = array_shift($fullName);
            $qb
                ->innerJoin(Participation::class, 'p', 'WITH', 'p.session = s')
                ->innerJoin(Trainer::class, 'trainer', 'WITH', 'trainer = p.trainer')
                ->andWhere('trainer.lastname = :trainerLastName AND trainer.firstname = :trainerFirstName')
                ->setParameter('trainerLastName', $lastName)
                ->setParameter('trainerFirstName', $firstName);
        }

        // TRI DES RESULTATS
        if (isset($sorts['training.name.source']))
            $qb->addOrderBy('s.name', $sorts['training.name.source']);
        elseif (isset($sorts['datebegin']))
            $qb->addOrderBy('s.datebegin', $sorts['datebegin']);
        else
            $qb->addOrderBy('s.datebegin')
                ->addOrderBy('s.name');

        // PAGINATION
        $offset = ($page-1) * $pageSize;
        $qb->setFirstResult($offset)
            ->setMaxResults($pageSize);

        $query = $qb->getQuery();

        $paginator = new Paginator($query, $fetchJoinCollection = true);

        $c = count($paginator);
        $tabSession = array();
        $sessionES = array();
        foreach($paginator as $session) {
            if ((is_array($fields)) && (in_array("_id", $fields))) {
                $tabSession[]['id'] = $session->getId();
            } else {
                // On ne garde que les infos de la session dont on a besoin
                $sessionES['id'] = $session->getId();
                $sessionES['name'] = $session->getName();
                $sessionES['datebegin'] = $session->getDatebegin();
                $sessionES['dateend'] = $session->getDateend();
                $sessionES['hournumber'] = $session->getHournumber();
                $sessionES['daynumber'] = $session->getDaynumber();
                $sessionES['year'] = $session->getYear();
                $sessionES['semester'] = $session->getSemester();
                $sessionES['semesterLabel'] = $session->getSemesterLabel();
                $sessionES['limitRegistrationDate'] = $session->getLimitregistrationdate();
                $sessionES['numberofregistrations'] = $session->getNumberofregistrations();
                $sessionES['numberofacceptedregistrations'] = $session->getNumberofacceptedregistrations();
                $sessionES['maximumnumberofregistrations'] = $session->getMaximumnumberofregistrations();
                $sessionES['numberofparticipants'] = $session->getNumberofparticipants();
                $sessionES['registrable'] = $session->isRegistrable();
                $sessionES['registration'] = $session->getRegistration();
                $sessionES['status'] = $session->getStatus();
                $sessionES['displayonline'] = $session->getDisplayonline();
                $sessionES['sessiontype'] = $session->getSessiontype();
                $sessionES['availablePlaces'] = $session->getAvailablePlaces();
                $sessionES['promote'] = $session->getPromote();

                $sessionES['training']['id'] = $session->getTraining()->getId();
                $sessionES['training']['type'] = $session->getTraining()->getType();
                $sessionES['training']['name'] = $session->getTraining()->getName();
                $sessionES['training']['typeLabel'] = $session->getTraining()->getTypeLabel();
                $sessionES['training']['organization'] = $session->getTraining()->getOrganization();
                $sessionES['training']['number'] = $session->getTraining()->getNumber();
                $sessionES['training']['theme'] = $session->getTraining()->getTheme();
                $sessionES['training']['tags'] = $session->getTraining()->getTags();
                $sessionES['training']['program'] = $session->getTraining()->getProgram();
                $sessionES['training']['description'] = $session->getTraining()->getDescription();
                $sessionES['training']['interventionType'] = $session->getTraining()->getInterventionType();
                $sessionES['training']['externalInitiative'] = $session->getTraining()->isExternalInitiative();
                $sessionES['training']['category'] = $session->getTraining()->getCategory();
                $sessionES['training']['comments'] = $session->getTraining()->getComments();
                $sessionES['training']['firstSessionPeriodSemester'] = $session->getTraining()->getFirstSessionPeriodSemester();
                $sessionES['training']['firstSessionPeriodYear'] = $session->getTraining()->getFirstSessionPeriodSemester();
                $sessionES['training']['publictypes'] = $session->getTraining()->getPublicTypes();

                $statsInsc = array();
                foreach ($session->getInscriptions() as $insc) {
                    $sessionES['inscriptions'][]['id'] = $insc->getId();

                    // création des stats de statut d'inscription
                    $flagExiste = 0;
                    $i=0;
                    // On parcourt le tableau des stats
                    foreach ($statsInsc as $stat) {
                        // si le statut de l'inscription est trouvé dans le tableau
                        if ($stat['id'] == $insc->getInscriptionStatus()->getId()) {
                            // on incrémente le compteur
                            $statsInsc[$i]['count']++;
                            $flagExiste = 1;
                        }
                        $i++;
                    }
                    // si on n'a pas trouvé le statut de l'inscription dans le tableau, on l'ajoute
                    if ($flagExiste == 0) {
                        $statsInsc [] = array( 'id' => $insc->getInscriptionStatus()->getId(),
                            'name' => $insc->getInscriptionStatus()->getName(),
                            'status' => $insc->getInscriptionStatus()->getStatus(),
                            'count' => 1);
                    }
                }

                foreach ($session->getParticipations() as $part) {
                    $sessionES['participations'][]['id'] = $part->getId();
                }

                $sessionES['theme'] = $session->getTraining()->getTheme();

                $sessionES['inscriptionStats'] = $statsInsc;

                $tabSession[] = $sessionES;
            }
        }

        $res = array('total' => $c,
            'pageSize' => $pageSize,
            'items' => $tabSession);

        return $res;

    }

    public function getNbSessions($query_filters, $keyword, $aggs, $name)
    {
        $qb = $this->createQueryBuilder('s');
        $qb
            ->select(' s');

            // FILTRE KEYWORD
        $qb
            ->where('s.name LIKE :keyword')
            /* addcslashes empêchera des manipulations malveillantes éventuelles */
            ->setParameter('keyword', '%' . addcslashes($keyword, '%_') . '%');

        if ((isset( $aggs['training.organization.name.source'])) || (isset($query_filters['training.organization.name.source'])) ||
            (isset( $aggs['theme.name'])) || (isset($query_filters['theme.name']))
        ) {
            // join sur training
            $qb->innerJoin('s.training', 'tr', 'WITH', 'tr = s.training');

            // FILTRE CENTRE
            if(isset( $aggs['training.organization.name.source'])) {
                $qb
                    ->innerJoin('tr.organization', 'o', 'WITH', 'o = tr.organization')
                    ->andWhere('o.name = :center')
                    ->setParameter('center', $name);
            } elseif (isset($query_filters['training.organization.name.source'])) {
                $qb
                    ->innerJoin('tr.organization', 'o', 'WITH', 'o = tr.organization')
                    ->andWhere('o.name in (:centers)')
                    ->setParameter('centers', $query_filters['training.organization.name.source']);
            }

            //FILTRE THEME
            if(isset( $aggs['theme.name'])) {
                $qb
                    ->innerJoin('tr.theme', 'th', 'WITH', 'th = tr.theme')
                    ->andWhere('th.name = :theme')
                    ->setParameter('theme', $name);
            } elseif (isset($query_filters['theme.name'])) {
                $qb
                    ->innerJoin('tr.theme', 'th', 'WITH', 'th = tr.theme')
                    ->andWhere('th.name in (:themes)')
                    ->setParameter('themes', $query_filters['theme.name']);
            }
        }

        // FILTRE ANNEE
        if (isset($aggs['year'])) {
            $qb
                ->andWhere('YEAR(s.datebegin) = :year')
                ->setParameter('year', $name);
        } elseif (isset($query_filters['year'])) {
            $qb
                /* On récupère l'année du dateBegin (à l'aide d'une doctrine extension) */
                ->andWhere('YEAR(s.datebegin) in (:years)')
                ->setParameter('years', $query_filters['year']);
        }

        // FILTRE SEMESTRE
        if (isset($aggs['semester'])) {
            if ($name  == 1) {
                $monthFrom = 1; $monthTo = 6;
            } else {
                $monthFrom = 7; $monthTo = 12;
            }
            $qb
                ->andWhere('MONTH(s.datebegin) BETWEEN :monthFrom and :monthTo')
                ->setParameter('monthFrom', $monthFrom)
                ->setParameter('monthTo', $monthTo);
        } elseif( isset($query_filters['semester']) ) {
            if ($query_filters['semester']  == 1) {
                $monthFrom = 1; $monthTo = 6;
            } else {
                $monthFrom = 7; $monthTo = 12;
            }
            $qb
                ->andWhere('MONTH(s.datebegin) BETWEEN :monthFrom and :monthTo')
                ->setParameter('monthFrom', $monthFrom)
                ->setParameter('monthTo', $monthTo);
        }

        //FILTRE DATE
        if( isset($query_filters['datebegin']) ) {
            /* La date envoyée par le formulaire en JS a un format : "dd/mm/yy - dd/mm/yy" il faut donc séparer les 2 dates */
            $dates = explode('-', $query_filters["datebegin"]);
            /* on retire les caractères non utiles */
            $from = str_replace('/','-', $dates[0]);
            $to = str_replace('/', '-', $dates[1]);
            /* on convertit au même format qu'en base de données */
            $dateFrom = date('Y/m/d 00:00:00' ,strtotime($from));
            $dateTo = date('Y/m/d 00:00:00',strtotime($to));

            $qb
                /* si la date de début d'une session est entre les 2 dates envoyées dans le formulaire */
                ->andWhere("s.datebegin BETWEEN :dateFrom AND :dateTo")
                ->setParameter('dateFrom', $dateFrom)
                ->setParameter('dateTo', $dateTo);
        }

        // FILTRE INSCRIPTION (0,1,2,3)
        if(isset( $aggs['registration'])) {
            $qb
                ->andWhere('s.registration = :registration')
                ->setParameter('registration', $name);
        } elseif( isset($query_filters['registration']) ) {
            $qb
                ->andWhere('s.registration in (:registrations)')
                ->setParameter('registrations', $query_filters['registration']);
        }

        // FILTRE STATUT (0,1,2)
        if(isset( $aggs['status'])) {
            $qb
                ->andWhere('s.status = :status')
                ->setParameter('status', $name);
        } elseif( isset($query_filters['status']) ){
            $qb
                ->andWhere('s.status in (:status)')
                ->setParameter('status', $query_filters['status']);
        }

        // FILTRE DISPLAYONLINE (F,T) ou (0,1) ?
        if(isset( $aggs['displayOnline'])) {
            $qb
                ->andWhere('s.displayonline = :displayOnline')
                ->setParameter('displayOnline', $name);
        } elseif( isset($query_filters['displayOnline']) ){
            $qb
                ->andWhere('s.displayonline = :displayOnline')
                ->setParameter('displayOnline', $query_filters['displayOnline']);
        }

        // FILTRE FORMATION (nom de la formation)
        if(isset( $aggs['training.name.source'])) {
            $qb
                ->andWhere('s.name = :training')
                ->setParameter('training', $name);
        } elseif( isset($query_filters['training.name.source']) ) {
            $qb
                ->andWhere('s.name in (:trainings)')
                ->setParameter('trainings', $query_filters['training.name.source']);
        }

        //FILTRE PROMOTION (true,false) = (0,1)
        if(isset( $aggs['promote'])) {
            $qb
                ->andWhere('s.promote = :promote')
                ->setParameter('promote', $name);
        } elseif( isset($query_filters['promote']) ) {
            $qb
                ->andWhere('s.promote = :promote')
                ->setParameter('promote', $query_filters['promote']);
        }

        // FILTRE FORMATEUR
        if(isset( $aggs['participations.trainer.fullName'])) {
            $qb
                ->innerJoin(Participation::class, 'p', 'WITH', 'p.session = s')
                ->innerJoin(Trainer::class, 'trainer', 'WITH', 'trainer = p.trainer')
                ->andWhere('trainer.id = :id ')
                ->setParameter('id', $name);
        } elseif( isset($query_filters['participations.trainer.fullName']) ) {
            /* le front envoie un full name (prénom+nom), je le découpe et ne récupère que le nom de famille */
            $fullName = explode(" ", $query_filters['participations.trainer.fullName']);
            $lastName = array_pop($fullName);
            $firstName = array_shift($fullName);
            $qb
                ->innerJoin(Participation::class, 'p', 'WITH', 'p.session = s')
                ->innerJoin(Trainer::class, 'trainer', 'WITH', 'trainer = p.trainer')
                ->andWhere('trainer.lastname = :trainerLastName AND trainer.firstname = :trainerFirstName')
                ->setParameter('trainerLastName', $lastName)
                ->setParameter('trainerFirstName', $firstName);
        }

        // On compte le nb de sessions en résultat
        $paginator = new Paginator($qb->getQuery());
        $totalRows = count($paginator);

        return $totalRows;
    }

}
